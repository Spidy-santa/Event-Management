<?php
session_start();
require '../includes/db.php';

// Restrict to attendees
if ($_SESSION['role'] !== 'attendee') {
  header("Location: ../login.php");
  exit();
}

// Fetch attendee data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Fetch upcoming events
$stmt = $pdo->prepare("SELECT events.* FROM registrations 
                      JOIN events ON registrations.event_id = events.event_id
                      WHERE registrations.user_id = ? AND events.date >= CURDATE()
                      ORDER BY events.date ASC LIMIT 5");
$stmt->execute([$user_id]);
$upcoming_events = $stmt->fetchAll();

// Fetch event recommendations
$stmt = $pdo->prepare("SELECT * FROM events 
                      WHERE category IN (
                        SELECT category FROM registrations
                        JOIN events ON registrations.event_id = events.event_id
                        WHERE user_id = ?
                      ) AND date >= CURDATE()
                      ORDER BY RAND() LIMIT 3");
$stmt->execute([$user_id]);
$recommended_events = $stmt->fetchAll();

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications 
                      WHERE user_id = ? AND is_read = 0
                      ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
  <title>Attendee Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    .dashboard-card {
      transition: transform 0.2s;
    }
    .dashboard-card:hover {
      transform: translateY(-5px);
    }
    .notification-badge {
      position: absolute;
      top: -10px;
      right: -10px;
    }
  </style>
</head>
<body class="container mt-5">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Welcome, <?= htmlspecialchars($user['name']) ?>!</h1>
    <div class="position-relative">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#notificationsModal">
        <i class="bi bi-bell"></i>
        <?php if(count($notifications) > 0): ?>
        <span class="badge bg-danger notification-badge"><?= count($notifications) ?></span>
        <?php endif; ?>
      </button>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="row mb-4">
    <div class="col-md-4">
      <a href="events.php" class="card dashboard-card text-decoration-none">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-calendar-event"></i> Browse Events</h5>
          <p class="card-text">Find and register for upcoming events.</p>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="tickets.php" class="card dashboard-card text-decoration-none">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-ticket-perforated"></i> My Tickets</h5>
          <p class="card-text">View and manage your event tickets.</p>
        </div>
      </a>
    </div>
    <div class="col-md-4">
      <a href="profile.php" class="card dashboard-card text-decoration-none">
        <div class="card-body">
          <h5 class="card-title"><i class="bi bi-person-circle"></i> My Profile</h5>
          <p class="card-text">Update your personal information.</p>
        </div>
      </a>
    </div>
  </div>

  <!-- Upcoming Events -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Upcoming Events</h5>
    </div>
    <div class="card-body">
      <?php if(empty($upcoming_events)): ?>
      <p class="text-muted">No upcoming events. <a href="events.php">Browse events</a> to get started!</p>
      <?php else: ?>
      <div class="list-group">
        <?php foreach($upcoming_events as $event): ?>
        <a href="event_details.php?id=<?= $event['event_id'] ?>" class="list-group-item list-group-item-action">
          <div class="d-flex justify-content-between">
            <div>
              <h6><?= htmlspecialchars($event['title']) ?></h6>
              <small class="text-muted"><?= date('M j, Y', strtotime($event['date'])) ?></small>
            </div>
            <div>
              <span class="badge bg-primary"><?= $event['category'] ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recommended Events -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-star"></i> Recommended for You</h5>
    </div>
    <div class="card-body">
      <?php if(empty($recommended_events)): ?>
      <p class="text-muted">No recommendations yet. Start exploring events!</p>
      <?php else: ?>
      <div class="row">
        <?php foreach($recommended_events as $event): ?>
        <div class="col-md-4">
          <div class="card dashboard-card">
            <img src="<?= $event['image_url'] ?>" class="card-img-top" alt="<?= $event['title'] ?>">
            <div class="card-body">
              <h6 class="card-title"><?= htmlspecialchars($event['title']) ?></h6>
              <p class="card-text"><?= date('M j, Y', strtotime($event['date'])) ?></p>
              <a href="event_details.php?id=<?= $event['event_id'] ?>" class="btn btn-primary btn-sm">
                View Details
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Notifications Modal -->
  <div class="modal fade" id="notificationsModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Notifications</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <?php if(empty($notifications)): ?>
          <p class="text-muted">No new notifications.</p>
          <?php else: ?>
          <div class="list-group">
            <?php foreach($notifications as $notification): ?>
            <div class="list-group-item">
              <h6><?= htmlspecialchars($notification['title']) ?></h6>
              <p><?= htmlspecialchars($notification['message']) ?></p>
              <small class="text-muted"><?= date('M j, Y h:i A', strtotime($notification['created_at'])) ?></small>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>