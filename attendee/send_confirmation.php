<?php
session_start();
require '../vendor/autoload.php'; // Include PHPMailer
include '../includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];

// Fetch user and event details
$stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT title FROM events WHERE event_id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// Send email
$mail = new PHPMailer(true);
try {
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com'; // Replace with your SMTP server
  $mail->SMTPAuth = true;
  $mail->Username = 'your-email@gmail.com'; // Replace with your email
  $mail->Password = 'your-email-password'; // Replace with your email password
  $mail->SMTPSecure = 'tls';
  $mail->Port = 587;

  $mail->setFrom('your-email@gmail.com', 'Event Management System');
  $mail->addAddress($user['email']);
  $mail->isHTML(true);
  $mail->Subject = 'Event Registration Confirmation';
  $mail->Body = "You have successfully registered for the event: <b>{$event['title']}</b>.";

  $mail->send();
  echo 'Confirmation email sent!';
} catch (Exception $e) {
  echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>