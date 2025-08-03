<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Get parameters
$hours = isset($_GET['hours']) ? (int)$_GET['hours'] : 24;
$device_id = isset($_GET['device_id']) ? $_GET['device_id'] : null;

try {
    // Build query
    $sql = "SELECT 
                device_id,
                temperature,
                humidity,
                smoke_level,
                flame_detected,
                fire_risk,
                status,
                inputDate
            FROM t_sensor_data 
            WHERE timestamp > DATE_SUB(NOW(), INTERVAL ? HOUR)";
    
    $params = [$hours];
    
    if ($device_id) {
        $sql .= " AND device_id = ?";
        $params[] = $device_id;
    }
    
    $sql .= " ORDER BY inputDate ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    // Format data for charts
    $chartData = [
        'labels' => [],
        'temperature' => [],
        'humidity' => [],
        'smoke' => [],
        'fire_risk' => []
    ];
    
    foreach ($data as $row) {
        $chartData['labels'][] = date('H:i', strtotime($row['timestamp']));
        $chartData['temperature'][] = (float)$row['temperature'];
        $chartData['humidity'][] = (float)$row['humidity'];
        $chartData['smoke'][] = (int)$row['smoke_level'];
        $chartData['fire_risk'][] = (float)$row['fire_risk'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'chartData' => $chartData,
        'count' => count($data)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error'
    ]);
}
?>