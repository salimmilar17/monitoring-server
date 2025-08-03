<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$latestData = getLatestSensorData();
$activeAlerts = getActiveAlerts(5);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM t_sensor_data WHERE DATE(inputDate) = CURDATE()");
$todayReadings = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM t_alerts WHERE acknowledged = FALSE");
$activeAlertCount = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Fire Detection System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
               <a href="home.php"><h3>Server Monitor</h3></a> 
                <p>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            
            <ul class="sidebar-menu">
                    <a href="dashboard.php">
                        <i class="icon">📊</i> Dashboard
                    </a>
                </li>
                <li>
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
        
        <!-- Main Content -->
        <div class="main-content">
            <header class="header">
                <h1>Home</h1>
                <div class="header-time">
                    <span id="current-time"></span>
                </div>
            </header>
            
            <div class="content">
               <h2>Selamat Datang di Web Monitor</h2>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        // Auto refresh every 10 seconds
        setInterval(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>