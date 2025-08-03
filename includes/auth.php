<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: home.php');
        exit;
    }
}

function login($username, $password) {
    global $pdo;
    
    try {
        // Debug: Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM t_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        // Debug output (hapus setelah selesai debugging)
        if (!$user) {
            error_log("Login failed: User '$username' not found");
            return false;
        }
        
        // Debug: Check password
        error_log("Username found: " . $user['username']);
        error_log("Stored hash: " . $user['password']);
        error_log("Password verify result: " . (password_verify($password, $user['password']) ? 'true' : 'false'));
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE t_users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            return true;
        }
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
    }
    
    return false;
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>