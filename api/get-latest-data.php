<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Set JSON header
header('Content-Type: application/json');

try {
    // Get latest sensor data
    $latestData = getLatestSensorData();
    
    if ($latestData) {
        // Get active alerts count
        $stmt = $pdo->query("SELECT COUNT(*) FROM t_alerts WHERE acknowledged = FALSE");
        $activeAlerts = $stmt->fetchColumn();
        
        // Get today's statistics
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as readings_today,
                AVG(temperature) as avg_temp,
                MAX(temperature) as max_temp,
                AVG(smoke_level) as avg_smoke,
                MAX(smoke_level) as max_smoke
            FROM t_sensor_data 
            WHERE DATE(inputDate) = CURDATE()
        ");
        $todayStats = $stmt->fetch();
        
        // Prepare response
        $response = [
            'success' => true,
            'data' => [
                'device_id' => $latestData['device_id'],
                'temperature' => (float)$latestData['temperature'],
                'humidity' => (float)$latestData['humidity'],
                'smoke_level' => (int)$latestData['smoke_level'],
                'flame_detected' => (bool)$latestData['flame_detected'],
                'fire_risk' => (float)$latestData['fire_risk'],
                'status' => $latestData['status'],
                'mqtt_connected' => (bool)$latestData['mqtt_connected'],
                'wifi_rssi' => (int)$latestData['wifi_rssi'],
                'timestamp' => $latestData['timestamp'],
                'time_ago' => timeAgo($latestData['inputDate']),
            ],
            'statistics' => [
                'active_alerts' => (int)$activeAlerts,
                'readings_today' => (int)$todayStats['readings_today'],
                'avg_temperature' => round((float)$todayStats['avg_temp'], 1),
                'max_temperature' => round((float)$todayStats['max_temp'], 1),
                'avg_smoke' => round((float)$todayStats['avg_smoke'], 0),
                'max_smoke' => (int)$todayStats['max_smoke']
            ],
            'server_time' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response);
        
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No data available',
            'data' => null
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}
?>