<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 50;
$offset = ($page - 1) * $limit;

// Get total records
$stmt = $pdo->query("SELECT COUNT(*) FROM t_sensor_data");
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Get sensor data with pagination
$stmt = $pdo->prepare("
    SELECT * FROM t_sensor_data 
    ORDER BY inputDate DESC 
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$sensorData = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) as total FROM t_alerts WHERE acknowledged = FALSE");
$activeAlertCount = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Data - Fire Detection System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Include sidebar here (same as dashboard) -->
        <nav class="sidebar">
            <div class="sidebar-header">
               <a href="home.php"><h3>Server Monitor</h3></a> 
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard.php">
                        <i class="icon">📊</i> Dashboard
                    </a>
                </li>
                <li class="active">
                    <a href="sensor-data.php">
                        <i class="icon">📈</i> Sensor Data
                    </a>
                </li>
                <li>
                    <a href="alerts.php">
                        <i class="icon">🚨</i> Alerts
                        <?php if ($activeAlertCount > 0): ?>
                            <span class="badge"><?php echo $activeAlertCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li>
                    <a href="users.php">
                        <i class="icon">👥</i> Users
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="logout.php">
                        <i class="icon">🚪</i> Logout
                    </a>
                </li>
            </ul>
        </nav>
        
        <div class="main-content">
            <header class="header">
                <h1>Sensor Data History</h1>
                <div class="header-actions">
                    <button onclick="exportData('csv')" class="btn btn-secondary">Export CSV</button>
                    <button onclick="location.reload()" class="btn btn-primary">Refresh</button>
                </div>
            </header>
            
            <div class="content">
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Device</th>
                                <th>Temp (°C)</th>
                                <th>Humidity (%)</th>
                                <th>Smoke (ppm)</th>
                                <th>Flame</th>
                                <th>Risk (%)</th>
                                <th>Status</th>
                                <th>MQTT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sensorData as $data): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($data['inputDate'])); ?></td>
                                <td><?php echo htmlspecialchars($data['device_id']); ?></td>
                                <td><?php echo number_format($data['temperature'], 1); ?></td>
                                <td><?php echo number_format($data['humidity'], 1); ?></td>
                                <td><?php echo $data['smoke_level']; ?></td>
                                <td><?php echo $data['flame_detected'] ? '🔥' : '✗'; ?></td>
                                <td><?php echo number_format($data['fire_risk'], 1); ?></td>
                                <td><?php echo getStatusBadge($data['status']); ?></td>
                                <td><?php echo $data['mqtt_connected'] ? '✓' : '✗'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                    <?php endif; ?>
                    
                    <span>Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>