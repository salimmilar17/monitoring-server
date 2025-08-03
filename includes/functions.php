<?php
function getLatestSensorData($deviceId = null) {
    global $pdo;
    
    $sql = "SELECT * FROM t_sensor_data";
    $params = [];
    
    if ($deviceId) {
        $sql .= " WHERE device_id = ?";
        $params[] = $deviceId;
    }
    
    $sql .= " ORDER BY inputDate DESC LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    return $stmt->fetch();
}

function getActiveAlerts($limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM t_alerts 
        WHERE acknowledged = FALSE 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    
    return $stmt->fetchAll();
}

function getStatusBadge($status) {
    $badges = [
        'SAFE' => '<span class="badge badge-success">SAFE</span>',
        'WARNING' => '<span class="badge badge-warning">WARNING</span>',
        'DANGER' => '<span class="badge badge-danger">DANGER</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge badge-secondary">UNKNOWN</span>';
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) {
        return $diff . ' seconds ago';
    } elseif ($diff < 3600) {
        return round($diff / 60) . ' minutes ago';
    } elseif ($diff < 86400) {
        return round($diff / 3600) . ' hours ago';
    } else {
        return round($diff / 86400) . ' days ago';
    }
}

function verifyApiKey($apiKey) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT device_id FROM t_api_keys 
        WHERE api_key = ? AND is_active = TRUE
    ");
    $stmt->execute([$apiKey]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Update last used
        $stmt = $pdo->prepare("UPDATE t_api_keys SET last_used = NOW() WHERE api_key = ?");
        $stmt->execute([$apiKey]);
        
        return $result['device_id'];
    }
    
    return false;
}
?>