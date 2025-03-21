<?php
session_start();
include 'includes/db.php';

$event_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
  $_SESSION['error'] = "Event not found!";
  header("Location: index.php");
  exit();
}

$details = json_decode($event['details'], true);

// Fix image path if it's relative
if ($event['image_path'] && !filter_var($event['image_path'], FILTER_VALIDATE_URL)) {
    $event['image_path'] = str_replace('../', '', $event['image_path']);
}
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= $event['title'] ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .event-image {
      max-height: 400px;
      object-fit: cover;
      width: 100%;
    }
    .event-details {
      background-color: #f8f9fa;
      padding: 2rem;
      border-radius: 10px;
      margin-top: 2rem;
    }
    .registration-form {
      background-color: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      margin-top: 2rem;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
      <a class="navbar-brand" href="index.php">Event Management System</a>
    </div>
  </nav>

  <div class="container mt-4">
    <!-- Event Image -->
    <div class="row mb-4">
      <div class="col-12">
        <img src="<?= $event['image_path'] ?? 'assets/images/events/default.jpg' ?>" class="event-image rounded" alt="<?= $event['title'] ?>">
      </div>
    </div>

    <!-- Event Details -->
    <div class="event-details">
      <h1 class="mb-4"><?= $event['title'] ?></h1>
      
      <div class="row">
        <div class="col-md-6">
          <div class="card mb-4">
            <div class="card-body">
              <h5 class="card-title">Event Information</h5>
              <p><i class="fas fa-calendar-day me-2"></i><strong>Date:</strong> <?= $event['date'] ?></p>
              <p><i class="fas fa-map-marker-alt me-2"></i><strong>Location:</strong> <?= $event['location'] ?></p>
              <p><i class="fas fa-tag me-2"></i><strong>Category:</strong> <?= ucfirst($event['category']) ?></p>
              <p><i class="fas fa-ticket-alt me-2"></i><strong>Ticket Price:</strong> $<?= number_format($event['ticket_price'], 2) ?></p>
              <p><i class="fas fa-users me-2"></i><strong>Available Tickets:</strong> <?= $event['total_tickets'] ?></p>
              <p><i class="fas fa-calendar-alt me-2"></i><strong>Registration Period:</strong><br>
                 From: <?= $event['date'] ?><br>
                 To: <?= $event['reg_end_date'] ?></p>
            </div>
          </div>

          <!-- Organizer Information -->
          <?php
          $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
          $stmt->execute([$event['organizer_id']]);
          $organizer = $stmt->fetch();
          ?>
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Organizer Information</h5>
              <p><i class="fas fa-user-tie me-2"></i><strong>Name:</strong> <?= htmlspecialchars($organizer['name']) ?></p>
              <p><i class="fas fa-envelope me-2"></i><strong>Contact:</strong> <?= htmlspecialchars($organizer['email']) ?></p>
              <?php if (!empty($organizer['phone'])): ?>
                <p><i class="fas fa-phone me-2"></i><strong>Phone:</strong> <?= htmlspecialchars($organizer['phone']) ?></p>
              <?php endif; ?>
              <?php if (!empty($organizer['address'])): ?>
                <p><i class="fas fa-location-dot me-2"></i><strong>Address:</strong> <?= htmlspecialchars($organizer['address']) ?></p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <?php if ($event['category'] === 'wedding'): ?>
            <p><i class="fas fa-user-female me-2"></i><strong>Bride:</strong> <?= htmlspecialchars($details['bride_name'] ?? '') ?></p>
            <p><i class="fas fa-user-male me-2"></i><strong>Groom:</strong> <?= htmlspecialchars($details['groom_name'] ?? '') ?></p>
            <?php if (isset($details['venue_type'])): ?>
              <p><i class="fas fa-home me-2"></i><strong>Venue Type:</strong> <?= htmlspecialchars($details['venue_type']) ?></p>
            <?php endif; ?>
          <?php elseif ($event['category'] === 'conference'): ?>
            <p><i class="fas fa-chalkboard-teacher me-2"></i><strong>Speakers:</strong> <?= htmlspecialchars($details['speaker_list'] ?? '') ?></p>
            <?php if (isset($details['conference_type'])): ?>
              <p><i class="fas fa-building me-2"></i><strong>Conference Type:</strong> <?= htmlspecialchars($details['conference_type']) ?></p>
            <?php endif; ?>
          <?php elseif ($event['category'] === 'liveconcert'): ?>
            <?php if (isset($details['artist_name'])): ?>
              <p><i class="fas fa-microphone me-2"></i><strong>Artist:</strong> <?= htmlspecialchars($details['artist_name']) ?></p>
            <?php endif; ?>
            <?php if (isset($details['genre'])): ?>
              <p><i class="fas fa-music me-2"></i><strong>Genre:</strong> <?= htmlspecialchars($details['genre']) ?></p>
            <?php endif; ?>
          <?php elseif ($event['category'] === 'workshop'): ?>
            <?php if (isset($details['instructor'])): ?>
              <p><i class="fas fa-user-tie me-2"></i><strong>Instructor:</strong> <?= htmlspecialchars($details['instructor']) ?></p>
            <?php endif; ?>
            <?php if (isset($details['workshop_type'])): ?>
              <p><i class="fas fa-tools me-2"></i><strong>Workshop Type:</strong> <?= htmlspecialchars($details['workshop_type']) ?></p>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($event['description'])): ?>
        <div class="mt-4">
          <h4>Description</h4>
          <p><?= nl2br(htmlspecialchars($event['description'])) ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Registration Form -->
    <?php if (isset($_SESSION['user_id'])): ?>
      <div class="registration-form">
        <h3 class="mb-4">Register for this Event</h3>
        <form method="POST" action="register_event.php" class="needs-validation" novalidate>
          <input type="hidden" name="event_id" value="<?= $event_id ?>">
          <div class="mb-3">
            <label for="ticket_qty" class="form-label">Number of Tickets</label>
            <input type="number" class="form-control" id="ticket_qty" name="ticket_qty" min="1" required>
          </div>
          <button type="submit" class="btn btn-primary">Register Now</button>
        </form>
      </div>
    <?php else: ?>
      <div class="alert alert-info mt-4">
        <i class="fas fa-info-circle me-2"></i>Please <a href="login.php">login</a> to register for this event.
      </div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>