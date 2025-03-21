<?php
session_start();
include '../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    try {
        // Delete the organizer
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'organizer'");
        $result = $stmt->execute([$user_id]);
        
        if ($result) {
            $_SESSION['success'] = "Organizer removed successfully.";
        } else {
            $_SESSION['error'] = "Failed to remove organizer.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: manage_organizers.php");
    exit();
}