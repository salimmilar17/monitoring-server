<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set JSON header
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check API key
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
$deviceId = verifyApiKey($apiKey);

if (!$deviceId) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON data']);
    exit;
}

try {
    // Insert sensor data
    $stmt = $pdo->prepare("
        INSERT INTO t_sensor_data 
        (device_id, temperature, humidity, smoke_level, flame_detected, 
         fire_risk, status, mqtt_connected, wifi_rssi) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $deviceId,
        $data['temperature'] ?? null,
        $data['humidity'] ?? null,
        $data['smoke'] ?? null,
        $data['flame'] ?? false,
        $data['fire_risk'] ?? null,
        $data['status'] ?? 'UNKNOWN',
        $data['mqtt_connected'] ?? false,
        $data['wifi_rssi'] ?? null
    ]);
    
    // Check if alert needed
    $status = $data['status'] ?? 'SAFE';
    
    if ($status === 'WARNING' || $status === 'DANGER') {
        // Check if similar alert exists in last 5 minutes
        $stmt = $pdo->prepare("
            SELECT id FROM t_alerts 
            WHERE device_id = ? 
            AND alert_type = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            LIMIT 1
        ");
        
        $alertType = ($status === 'DANGER') ? 'danger' : 'warning';
        $stmt->execute([$deviceId, $alertType]);
        
        if (!$stmt->fetch()) {
            // Create new alert
            $message = "Fire risk detected! Status: $status";
            if ($data['flame'] ?? false) {
                $message = "FLAME DETECTED! Immediate action required!";
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO t_alerts 
                (device_id, alert_type, message, temperature, humidity, 
                 smoke_level, flame_detected, fire_risk) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $deviceId,
                $alertType,
                $message,
                $data['temperature'] ?? null,
                $data['humidity'] ?? null,
                $data['smoke'] ?? null,
                $data['flame'] ?? false,
                $data['fire_risk'] ?? null
            ]);
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Data received successfully',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>