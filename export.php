<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

requireLogin();

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Get data to export
$stmt = $pdo->query("
    SELECT 
        device_id,
        temperature,
        humidity,
        smoke_level,
        flame_detected,
        fire_risk,
        status,
        timestamp
    FROM t_sensor_data 
    ORDER BY timestamp DESC 
    LIMIT 1000
");
$data = $stmt->fetchAll();

if ($format === 'csv') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sensor_data_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, [
        'Device ID',
        'Temperature (°C)',
        'Humidity (%)',
        'Smoke Level (ppm)',
        'Flame Detected',
        'Fire Risk (%)',
        'Status',
        'Timestamp'
    ]);
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, [
            $row['device_id'],
            $row['temperature'],
            $row['humidity'],
            $row['smoke_level'],
            $row['flame_detected'] ? 'Yes' : 'No',
            $row['fire_risk'],
            $row['status'],
            $row['timestamp']
        ]);
    }
    
    fclose($output);
    exit;
}
?>