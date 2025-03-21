<html>
    <head><title>Registration</title></head><body><?php
session_start();
include '../includes/db.php'; // Database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = htmlspecialchars($_POST['name']);
  $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = $_POST['role'];
  $is_approved = ($role === 'organizer') ? 0 : 1; // Organizers need approval

  // Insert into database
  $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, is_approved) 
                        VALUES (?, ?, ?, ?, ?)");
  if ($stmt->execute([$name, $email, $password, $role, $is_approved])) {
    $_SESSION['success'] = "Registration successful!";
    header("Location: login.php");
    exit();
  } else {
    $_SESSION['error'] = "Registration failed!";
  }
}
?>
<form action="register.php" method="POST">
  <input type="text" name="name" placeholder="Full Name" required>
  <input type="email" name="email" placeholder="Email" required>
  <input type="password" name="password" placeholder="Password" required>
  <select name="role" required>
    <option value="attendee">Attendee</option>
    <option value="organizer">Organizer</option>
  </select>
  <button type="submit">Register</button>
</form></body>
</html>