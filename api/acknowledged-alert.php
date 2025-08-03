<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['alert_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Alert ID required']);
    exit;
}

$alertId = (int)$data['alert_id'];

try {
    // Update alert
    $stmt = $pdo->prepare("
        UPDATE t_alerts 
        SET acknowledged = TRUE, 
            acknowledged_by = ?, 
            acknowledged_at = NOW() 
        WHERE id = ? AND acknowledged = FALSE
    ");
    
    $stmt->execute([$_SESSION['user_id'], $alertId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Alert acknowledged successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Alert not found or already acknowledged']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>