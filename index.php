<?php
session_start();
include 'includes/db.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Event Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-card {
            transition: transform 0.3s;
            margin-bottom: 2rem;
            height: 100%;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
        .event-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
            border-radius: 8px 8px 0 0;
        }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('assets/images/liveconcert.jfif');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Event Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin/login.php"><i class="fas fa-user-shield"></i> Admin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="organizer/login.php"><i class="fas fa-calendar-alt"></i> Organizer</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="attendee/login.php"><i class="fas fa-user"></i> User</a>
                    </li>
                    <li class="nav-item">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        <?php else: ?>
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1>Discover Exciting Events Near You</h1>
            <p class="lead">Explore, register, and manage your events with ease</p>
        </div>
    </div>

    <!-- Events Section -->
    <div class="container mt-4">
        <div class="row">
            <?php
            $stmt = $pdo->prepare("SELECT e.*, u.name as organizer_name FROM events e 
                                  JOIN users u ON e.organizer_id = u.user_id 
                                  WHERE e.date >= CURDATE() 
                                  ORDER BY e.date ASC");
            $stmt->execute();
            $events = $stmt->fetchAll();

            foreach ($events as $event): 
                $image_path = $event['image_path'] ?? 'assets/images/events/default.jpg';
                if (!filter_var($image_path, FILTER_VALIDATE_URL)) {
                    $image_path = str_replace('../', '', $image_path);
                }
            ?>
            <div class="col-md-4 mb-4">
                <div class="card event-card h-100">
                    <img src="<?= $image_path ?>" class="event-image" alt="<?= htmlspecialchars($event['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($event['title']) ?></h5>
                        <p class="card-text">
                            <i class="fas fa-calendar-day"></i> <?= $event['date'] ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?><br>
                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($event['organizer_name']) ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-primary"><?= ucfirst($event['category']) ?></span>
                            <span class="text-muted">$<?= number_format($event['ticket_price'], 2) ?></span>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="event_details.php?id=<?= $event['event_id'] ?>" class="btn btn-primary w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Search/Filter Form -->
    <div class="container mt-4">
        <form method="GET" class="mb-4">
          <div class="row">
            <div class="col-md-4">
              <input type="text" name="search" placeholder="Search by title" class="form-control">
            </div>
            <div class="col-md-3">
              <select name="category" class="form-select">
                <option value="">All Categories</option>
                <option value="wedding">Wedding</option>
                <option value="conference">Conference</option>
              </select>
            </div>
            <div class="col-md-3">
              <input type="date" name="date" class="form-control">
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary">Filter</button>
            </div>
          </div>
        </form>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row">
            <?php
            // Build the SQL query dynamically
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $date = $_GET['date'] ?? '';

            $sql = "SELECT * FROM events WHERE 1=1";
            $params = [];

            if (!empty($search)) {
              $sql .= " AND title LIKE ?";
              $params[] = "%$search%";
            }

            if (!empty($category)) {
              $sql .= " AND category = ?";
              $params[] = $category;
            }

            if (!empty($date)) {
              $sql .= " AND date = ?";
              $params[] = $date;
            }

            $sql .= " ORDER BY date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $events = $stmt->fetchAll();

            if (empty($events)) {
                echo '<div class="col-12"><div class="alert alert-info">No events found. Check back later!</div></div>';
            } else {
                foreach ($events as $event):
                    $details = json_decode($event['details'], true);
            ?>
            <div class="col-md-4 mb-4">
                <div class="card event-card h-100 shadow">
                    <img src="<?= $event['image_path'] ?? 'assets/images/events/default.jpg' ?>" class="event-image" alt="<?= $event['title'] ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= $event['title'] ?></h5>
                        <p class="card-text">
                            <i class="fas fa-calendar-day"></i> <?= $event['date'] ?><br>
                            <i class="fas fa-map-marker-alt"></i> <?= $event['location'] ?><br>
                            <i class="fas fa-tag"></i> <?= ucfirst($event['category']) ?>
                        </p>
                        <a href="event_details.php?id=<?= $event['event_id'] ?>" class="btn btn-primary w-100">
                            <i class="fas fa-info-circle"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>