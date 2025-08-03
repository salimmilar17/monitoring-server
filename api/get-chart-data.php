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
$type = isset($_GET['type']) ? $_GET['type'] : 'temperature';
$period = isset($_GET['period']) ? $_GET['period'] : '24h';
$device_id = isset($_GET['device_id']) ? $_GET['device_id'] : null;

// Determine time interval - FIXED FOR YOUR DATABASE
switch ($period) {
    case '1h':
        $interval = '1 HOUR';
        $groupBy = "DATE_FORMAT(inputDate, '%H:%i')";
        break;
    case '24h':
        $interval = '24 HOUR';
        $groupBy = "DATE_FORMAT(inputDate, '%H:00')";
        break;
    case '7d':
        $interval = '7 DAY';
        $groupBy = "DATE(inputDate)";
        break;
    case '30d':
        $interval = '30 DAY';
        $groupBy = "DATE(inputDate)";
        break;
    default:
        $interval = '24 HOUR';
        $groupBy = "DATE_FORMAT(inputDate, '%H:00')";
}

try {
    // Debug: Check if table has data
    $checkStmt = $pdo->query("SELECT COUNT(*) as count FROM t_sensor_data");
    $dataCount = $checkStmt->fetch()['count'];
    
    if ($dataCount == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'No data in database',
            'chartData' => [
                'labels' => [],
                'datasets' => []
            ]
        ]);
        exit;
    }
    
    // Build query based on type - FIXED COLUMN NAMES
    $sql = "SELECT 
                $groupBy as time_label,
                AVG(temperature) as avg_temperature,
                AVG(humidity) as avg_humidity,
                AVG(smoke_level) as avg_smoke,
                AVG(fire_risk) as avg_risk,
                MAX(flame_detected) as any_flame,
                COUNT(*) as reading_count
            FROM t_sensor_data 
            WHERE inputDate > DATE_SUB(NOW(), INTERVAL $interval)";
    
    $params = [];
    
    if ($device_id) {
        $sql .= " AND device_id = ?";
        $params[] = $device_id;
    }
    
    $sql .= " GROUP BY time_label ORDER BY MIN(inputDate) ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    // Debug output
    error_log("Query: " . $sql);
    error_log("Data points found: " . count($data));
    
    // Format data for Chart.js
    $labels = [];
    $datasets = [];
    
    switch ($type) {
        case 'temperature':
            $values = [];
            foreach ($data as $row) {
                $labels[] = $row['time_label'];
                $values[] = $row['avg_temperature'] !== null ? round($row['avg_temperature'], 1) : 0;
            }
            $datasets[] = [
                'label' => 'Temperature (°C)',
                'data' => $values,
                'borderColor' => 'rgb(255, 99, 132)',
                'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                'tension' => 0.1,
                'fill' => true
            ];
            break;
            
        case 'humidity':
            $values = [];
            foreach ($data as $row) {
                $labels[] = $row['time_label'];
                $values[] = $row['avg_humidity'] !== null ? round($row['avg_humidity'], 1) : 0;
            }
            $datasets[] = [
                'label' => 'Humidity (%)',
                'data' => $values,
                'borderColor' => 'rgb(54, 162, 235)',
                'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                'tension' => 0.1,
                'fill' => true
            ];
            break;
            
        case 'smoke':
            $values = [];
            foreach ($data as $row) {
                $labels[] = $row['time_label'];
                $values[] = $row['avg_smoke'] !== null ? round($row['avg_smoke'], 0) : 0;
            }
            $datasets[] = [
                'label' => 'Smoke Level (ppm)',
                'data' => $values,
                'borderColor' => 'rgb(75, 192, 192)',
                'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                'tension' => 0.1,
                'fill' => true
            ];
            break;
            
        case 'all':
            $temp = [];
            $humidity = [];
            $smoke = [];
            $risk = [];
            
            foreach ($data as $row) {
                $labels[] = $row['time_label'];
                $temp[] = $row['avg_temperature'] !== null ? round($row['avg_temperature'], 1) : 0;
                $humidity[] = $row['avg_humidity'] !== null ? round($row['avg_humidity'], 1) : 0;
                $smoke[] = $row['avg_smoke'] !== null ? round($row['avg_smoke'], 0) : 0;
                $risk[] = $row['avg_risk'] !== null ? round($row['avg_risk'], 1) : 0;
            }
            
            $datasets = [
                [
                    'label' => 'Temperature (°C)',
                    'data' => $temp,
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
                    'yAxisID' => 'y',
                    'tension' => 0.1
                ],
                [
                    'label' => 'Humidity (%)',
                    'data' => $humidity,
                    'borderColor' => 'rgb(54, 162, 235)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                    'yAxisID' => 'y',
                    'tension' => 0.1
                ],
                [
                    'label' => 'Smoke (ppm)',
                    'data' => $smoke,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                    'yAxisID' => 'y1',
                    'tension' => 0.1
                ],
                [
                    'label' => 'Fire Risk (%)',
                    'data' => $risk,
                    'borderColor' => 'rgb(255, 206, 86)',
                    'backgroundColor' => 'rgba(255, 206, 86, 0.1)',
                    'yAxisID' => 'y',
                    'tension' => 0.1
                ]
            ];
            break;
    }
    
    // If no data, provide empty dataset
    if (count($data) == 0) {
        $labels = ['No Data'];
        $datasets = [[
            'label' => 'No Data Available',
            'data' => [0],
            'borderColor' => 'rgb(200, 200, 200)',
            'backgroundColor' => 'rgba(200, 200, 200, 0.1)'
        ]];
    }
    
    echo json_encode([
        'success' => true,
        'chartData' => [
            'labels' => $labels,
            'datasets' => $datasets
        ],
        'summary' => [
            'period' => $period,
            'data_points' => count($data),
            'type' => $type,
            'total_records' => $dataCount
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Chart API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'chartData' => [
            'labels' => [],
            'datasets' => []
        ]
    ]);
}
?>