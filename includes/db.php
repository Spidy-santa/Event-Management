<?php
$host = 'localhost';      // Database host
$dbname = 'event';     // Database name
$username = 'root';       // Database username (default for XAMPP/WAMP is 'root')
$password = '';           // Database password (default is empty for XAMPP/WAMP)

try {
  // Create a PDO connection
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
  
  // Set PDO error mode to exception
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
  // Optional: Set default fetch mode to associative array
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Handle connection errors
  die("Database connection failed: " . $e->getMessage());
}
?>