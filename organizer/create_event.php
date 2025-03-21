<!DOCTYPE html>
<html>
<head>
  <title>Create Event</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="container mt-5"><?php
session_start();
include '../includes/db.php';

if ($_SESSION['role'] !== 'organizer' || !isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = htmlspecialchars($_POST['title']);
  $date = $_POST['date'];
  $location = htmlspecialchars($_POST['location']);
  $category = $_POST['category'];
  $organizer_id = $_SESSION['user_id'];
  $reg_start_date = $_POST['reg_start_date'];
  $reg_end_date = $_POST['reg_end_date'];
  $total_tickets = intval($_POST['total_tickets']);
  $ticket_price = floatval($_POST['ticket_price']);

  // Handle image upload
  $image_path = '';
  if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../assets/images/events/';
    if (!file_exists($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['event_image']['name'], PATHINFO_EXTENSION);
    $image_path = $upload_dir . uniqid('event_', true) . '.' . $file_extension;
    move_uploaded_file($_FILES['event_image']['tmp_name'], $image_path);
  }

  // Capture dynamic fields
  $details = [];
  foreach ($_POST as $key => $value) {
    if ($key !== 'title' && $key !== 'date' && $key !== 'location' && $key !== 'category') {
      $details[$key] = htmlspecialchars($value);
    }
  }
  $details_json = json_encode($details);

  // Insert into database
  // Check if columns exist before inserting
  $stmt = $pdo->prepare("SHOW COLUMNS FROM events LIKE 'reg_start_date'");
  $stmt->execute();
  $has_reg_dates = $stmt->fetch();
  
  if ($has_reg_dates) {
      // Insert with registration dates
      $stmt = $pdo->prepare("INSERT INTO events 
          (title, date, location, category, organizer_id, details, reg_start_date, reg_end_date, total_tickets, ticket_price, image_path) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->execute([$title, $date, $location, $category, $organizer_id, $details_json, $reg_start_date, $reg_end_date, $total_tickets, $ticket_price, $image_path]);
  } else {
      // Insert without registration dates
      $stmt = $pdo->prepare("INSERT INTO events 
          (title, date, location, category, organizer_id, details, total_tickets, ticket_price, image_path) 
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->execute([$title, $date, $location, $category, $organizer_id, $details_json, $total_tickets, $ticket_price, $image_path]);
  }
  $event_id = $pdo->lastInsertId(); // Get the new event ID

  // Save budget items
  if (isset($_POST['budget'])) {
    foreach ($_POST['budget'] as $item_name => $estimated_cost) {
      $stmt = $pdo->prepare("INSERT INTO event_budgets (event_id, item_name, estimated_cost)
                            VALUES (?, ?, ?)");
      $stmt->execute([$event_id, $item_name, $estimated_cost]);
    }
  }
    
    $_SESSION['success'] = "Event created successfully!";
    header("Location: manage_events.php");
  } else {
    $_SESSION['error'] = "Failed to create event.";
  }

?>
  <h2>Create Event</h2>
  <form method="POST" enctype="multipart/form-data">
    <div class="mb-3">
      <label class="form-label">Event Title</label>
      <input type="text" name="title" class="form-control" placeholder="Enter a descriptive title" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Event Description</label>
      <textarea name="description" class="form-control" rows="4" placeholder="Provide a detailed description of your event" required></textarea>
    </div>
    <div class="mb-3">
      <label class="form-label">Event Date and Time</label>
      <div class="row">
        <div class="col-md-6">
          <input type="date" name="date" class="form-control" required>
        </div>
        <div class="col-md-6">
          <input type="time" name="time" class="form-control" required>
        </div>
      </div>
    </div>
    <div class="mb-3">
      <label class="form-label">Registration Period</label>
      <div class="row">
        <div class="col">
          <label class="form-label">Start Date</label>
          <input type="date" name="reg_start_date" class="form-control" required>
        </div>
        <div class="col">
          <input type="date" name="reg_end_date" class="form-control" placeholder="End Date" required>
        </div>
      </div>
    </div>
    <div class="mb-3">
      <input type="text" name="location" class="form-control" placeholder="Location" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Event Image</label>
      <input type="file" name="event_image" class="form-control" accept="image/*" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Ticket Information</label>
      <div class="row">
        <div class="col">
          <input type="number" name="total_tickets" class="form-control" placeholder="Total Tickets Available" required min="1">
        </div>
        <div class="col">
          <input type="number" name="ticket_price" class="form-control" placeholder="Ticket Price" required min="0" step="0.01">
        </div>
      </div>
    </div>
    <div class="mb-3">
      <select name="category" id="event-category" class="form-control" required>
        <option value="">Select Event Category</option>
        <option value="wedding">Wedding</option>
        <option value="birthday">Birthday</option>
        <option value="conference">Conference</option>
        <option value="liveconcert">Live Concert</option>
        <option value="workshop">Workshop</option>
      </select>
    </div>
    <div id="category-options" class="mb-3"></div>
    <div class="mb-3">
      <label class="form-label">Total Price</label>
      <input type="text" id="total-price" class="form-control" readonly>
      <input type="hidden" name="total_price" id="total-price-input">
    </div>
    <div id="dynamic-fields" class="mb-3">
      <!-- Dynamic fields load here via AJAX -->
    </div>
    
    <!-- Add this to the existing form -->
    <div id="budget-items" class="mb-3">
      <h4>Budget Items</h4>
      <!-- Budget items will load here -->
    </div>
    
    <button type="submit" class="btn btn-primary">Create Event</button>
  </form>

  <script>
  // Load dynamic fields when category changes
  $("#category").change(function() {
    const category = $(this).val();
    $.ajax({
      url: "get_dynamic_fields.php",
      data: { category: category },
      success: function(response) {
        $("#dynamic-fields").html(response);
      }
    });
  });
  
  // Load budget template when category changes
  $("#category").change(function() {
    const category = $(this).val();
    $.ajax({
      url: "get_budget_template.php",
      data: { category: category },
      success: function(template) {
        let html = '';
        template.forEach(item => {
          html += `
            <div class="budget-item mb-3">
              <label>${item.item_name}</label>
              <input type="number" name="budget[${item.item_name}]" 
                     class="form-control" value="${item.suggested_cost}" step="0.01">
            </div>
          `;
        });
        $("#budget-items").html(html);
      }
    });
  });
  </script>

  <script>
  $(document).ready(function() {
    let basePrice = 0;
    let selectedOptions = {};
  
    $('#event-category').change(function() {
      const category = $(this).val();
      if (!category) {
        $('#category-options').empty();
        updateTotalPrice();
        return;
      }
  
      $.getJSON('get_category_options.php', { category: category }, function(data) {
        basePrice = data.base_price;
        const options = data.options;
        let html = '';
  
        for (const [key, option] of Object.entries(options)) {
          html += `<div class="mb-3">`;
          html += `<label class="form-label">${option.label}</label>`;
  
          if (option.type === 'select') {
            html += `<select name="${key}" class="form-control option-select" data-option="${key}">`;
            html += `<option value="">Select ${option.label}</option>`;
            option.options.forEach(opt => {
              html += `<option value="${opt.value}" data-price="${opt.price}">${opt.label}</option>`;
            });
            html += '</select>';
          } else if (option.type === 'checkbox') {
            option.options.forEach(opt => {
              html += `<div class="form-check">`;
              html += `<input class="form-check-input option-checkbox" type="checkbox" name="${key}[]" `;
              html += `value="${opt.value}" data-price="${opt.price}" data-option="${key}">`;
              html += `<label class="form-check-label">${opt.label}</label>`;
              html += `</div>`;
            });
          }
          html += '</div>';
        }
  
        $('#category-options').html(html);
        updateTotalPrice();
      });
    });
  
    $(document).on('change', '.option-select', function() {
      const option = $(this).data('option');
      const selectedOption = $(this).find(':selected');
      selectedOptions[option] = selectedOption.data('price') || 0;
      updateTotalPrice();
    });
  
    $(document).on('change', '.option-checkbox', function() {
      const option = $(this).data('option');
      const price = $(this).data('price');
      
      if (!selectedOptions[option]) {
        selectedOptions[option] = 0;
      }
  
      if ($(this).is(':checked')) {
        selectedOptions[option] += price;
      } else {
        selectedOptions[option] -= price;
      }
      updateTotalPrice();
    });
  
    function updateTotalPrice() {
      let total = basePrice;
      for (const price of Object.values(selectedOptions)) {
        total += price;
      }
      $('#total-price').val('$' + total.toLocaleString());
      $('#total-price-input').val(total);
    }
  });
  </script>
</body>
</html>