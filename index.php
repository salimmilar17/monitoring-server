<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if (login($username, $password)) {
        header('Location: pages/dashboard.php');
        exit;
    } else {
        $_SESSION['error'] = 'Username atau password salah';
        header('Location: login.php');
        exit;
    }
}

// Check if user is already logged in
if (isLoggedIn()) {
    header('Location: home.php');
    exit;
} else {
    header('Location: login.php');
    exit;
}