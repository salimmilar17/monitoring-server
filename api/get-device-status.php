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

try {
    // Get all unique devices and their last status
    $stmt = $pdo->query("
        SELECT DISTINCT 
            sd1.device_id,
            sd1.status,
            sd1.temperature,
            sd1.humidity,
            sd1.smoke_level,
            sd1.fire_risk,
            sd1.timestamp as last_seen,
            sd1.mqtt_connected,
            sd1.wifi_rssi,
            CASE 
                WHEN sd1.timestamp < DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 'offline'
                ELSE 'online'
            END as connection_status
        FROM sensor_data sd1
        INNER JOIN (
            SELECT device_id, MAX(inputDate) as max_inputDate
            FROM t_sensor_data
            GROUP BY device_id
        ) sd2 ON sd1.device_id = sd2.device_id AND sd1.inputDate = sd2.max_inputDate
        WHERE sd1.inputDate = sd2.max_inputDate
        ORDER BY sd1.device_id
    ");
    
    $devices = $stmt->fetchAll();
    
    // Get alert count per device
    foreach ($devices as &$device) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as alert_count 
            FROM t_alerts 
            WHERE device_id = ? AND acknowledged = FALSE
        ");
        $stmt->execute([$device['device_id']]);
        $device['active_alerts'] = $stmt->fetchColumn();
        
        // Calculate time since last update
        $lastUpdate = strtotime($device['last_seen']);
        $timeDiff = time() - $lastUpdate;
        
        if ($timeDiff < 60) {
            $device['last_update'] = $timeDiff . ' seconds ago';
        } elseif ($timeDiff < 3600) {
            $device['last_update'] = round($timeDiff / 60) . ' minutes ago';
        } else {
            $device['last_update'] = round($timeDiff / 3600) . ' hours ago';
        }
    }
    
    echo json_encode([
        'success' => true,
        'devices' => $devices,
        'count' => count($devices)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error'
    ]);
}
?>