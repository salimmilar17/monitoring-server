<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log logout activity (optional)
if (isset($_SESSION['user_id'])) {
    // If you want to log logout activities, you can add database logging here
    // For example:
    /*
    require_once 'config/database.php';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, 'logout', NOW())");
    $stmt->execute([$_SESSION['user_id']]);
    */
}

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php?message=logged_out');
exit();
?>