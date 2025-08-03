<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$message = '';
$error = '';

// Handle alert acknowledgment
if (isset($_POST['acknowledge']) && isset($_POST['alert_id'])) {
    $alertId = (int)$_POST['alert_id'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE t_alerts 
            SET acknowledged = TRUE, 
                acknowledged_by = ?, 
                acknowledged_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $alertId]);
        
        $message = 'Alert acknowledged successfully!';
    } catch (PDOException $e) {
        $error = 'Failed to acknowledge alert.';
    }
}

// Handle bulk acknowledgment
if (isset($_POST['acknowledge_all'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE t_alerts 
            SET acknowledged = TRUE, 
                acknowledged_by = ?, 
                acknowledged_at = NOW() 
            WHERE acknowledged = FALSE
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        $affectedRows = $stmt->rowCount();
        $message = "$affectedRows alerts acknowledged successfully!";
    } catch (PDOException $e) {
        $error = 'Failed to acknowledge alerts.';
    }
}

// Filter parameters
$filterType = isset($_GET['type']) ? $_GET['type'] : 'all';
$filterDevice = isset($_GET['device']) ? $_GET['device'] : 'all';
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'active';
$filterDate = isset($_GET['date']) ? $_GET['date'] : '';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT a.*, u.username as acknowledged_by_user 
        FROM t_alerts a 
        LEFT JOIN t_users u ON a.acknowledged_by = u.id 
        WHERE 1=1";
$params = [];

// Apply filters
if ($filterType !== 'all') {
    $sql .= " AND a.alert_type = ?";
    $params[] = $filterType;
}

if ($filterDevice !== 'all') {
    $sql .= " AND a.device_id = ?";
    $params[] = $filterDevice;
}

if ($filterStatus === 'active') {
    $sql .= " AND a.acknowledged = FALSE";
} elseif ($filterStatus === 'acknowledged') {
    $sql .= " AND a.acknowledged = TRUE";
}

if ($filterDate) {
    $sql .= " AND DATE(a.created_at) = ?";
    $params[] = $filterDate;
}

// Get total count for pagination
$countSql = str_replace("SELECT a.*, u.username as acknowledged_by_user", "SELECT COUNT(*)", $sql);
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Add order and limit
$sql .= " ORDER BY a.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

// Execute main query
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param);
}
/* $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT); */
$stmt->execute();
$alerts = $stmt->fetchAll();

// Get unique devices for filter
$stmt = $pdo->query("SELECT DISTINCT device_id FROM t_alerts ORDER BY device_id");
$devices = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get statistics
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN acknowledged = FALSE THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN alert_type = 'danger' AND acknowledged = FALSE THEN 1 ELSE 0 END) as danger,
        SUM(CASE WHEN alert_type = 'warning' AND acknowledged = FALSE THEN 1 ELSE 0 END) as warning
    FROM t_alerts
");
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts - Fire Detection System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filters {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card-small {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .alert-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .alert-detail {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }
        
        .alert-meta {
            font-size: 12px;
            color: #718096;
            margin-top: 5px;
        }
        
        .acknowledge-btn {
            background-color: #48bb78;
            color: white;
            border: none;
            padding: 5px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .acknowledge-btn:hover {
            background-color: #38a169;
        }
        
        .acknowledged-info {
            background-color: #f7fafc;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
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
                <li>
                    <a href="dashboard.php">
                        <i class="icon">📊</i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="sensor-data.php">
                        <i class="icon">📈</i> Sensor Data
                    </a>
                </li>
                <li class="active">
                    <a href="alerts.php">
                        <i class="icon">🚨</i> Alerts
                        <?php if ($stats['active'] > 0): ?>
                            <span class="badge"><?php echo $stats['active']; ?></span>
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
                <h1>Alert Management</h1>
                <div class="header-time">
                    <span id="current-time"></span>
                </div>
            </header>
            
            <div class="content">
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Statistics -->
                <div class="stats-cards">
                    <div class="stat-card-small">
                        <div class="stat-icon">📊</div>
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div>Total Alerts</div>
                    </div>
                    
                    <div class="stat-card-small">
                        <div class="stat-icon">🔔</div>
                        <div class="stat-number text-warning"><?php echo $stats['active']; ?></div>
                        <div>Active Alerts</div>
                    </div>
                    
                    <div class="stat-card-small">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-number text-danger"><?php echo $stats['danger']; ?></div>
                        <div>Danger Alerts</div>
                    </div>
                    
                    <div class="stat-card-small">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-number text-warning"><?php echo $stats['warning']; ?></div>
                        <div>Warning Alerts</div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="filters">
                    <form method="GET" action="">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label for="type">Alert Type</label>
                                <select name="type" id="type" class="form-control">
                                    <option value="all" <?php echo $filterType === 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <option value="danger" <?php echo $filterType === 'danger' ? 'selected' : ''; ?>>Danger</option>
                                    <option value="warning" <?php echo $filterType === 'warning' ? 'selected' : ''; ?>>Warning</option>
                                    <option value="info" <?php echo $filterType === 'info' ? 'selected' : ''; ?>>Info</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="device">Device</label>
                                <select name="device" id="device" class="form-control">
                                    <option value="all">All Devices</option>
                                    <?php foreach ($devices as $device): ?>
                                        <option value="<?php echo $device; ?>" <?php echo $filterDevice === $device ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($device); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control">
                                    <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All</option>
                                    <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="acknowledged" <?php echo $filterStatus === 'acknowledged' ? 'selected' : ''; ?>>Acknowledged</option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label for="date">Date</label>
                                <input type="date" name="date" id="date" value="<?php echo $filterDate; ?>" class="form-control">
                            </div>
                            
                            <div class="filter-group">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="alerts.php" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Alert Actions -->
                <?php if ($stats['active'] > 0): ?>
                <div class="alert-actions">
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" name="acknowledge_all" class="btn btn-success" 
                                onclick="return confirm('Acknowledge all active alerts?')">
                            Acknowledge All Active Alerts
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <!-- Alerts List -->
                <div class="alerts-section">
                    <?php if (count($alerts) > 0): ?>
                        <?php foreach ($alerts as $alert): ?>
                            <div class="alert-item alert-<?php echo $alert['alert_type']; ?>">
                                <div class="alert-detail">
                                    <div>
                                        <div class="alert-header">
                                            <span class="alert-type"><?php echo strtoupper($alert['alert_type']); ?></span>
                                            <span class="alert-time"><?php echo timeAgo($alert['created_at']); ?></span>
                                        </div>
                                        <p><?php echo htmlspecialchars($alert['message']); ?></p>
                                        <div class="alert-data">
                                            <span>🌡️ <?php echo number_format($alert['temperature'], 1); ?>°C</span>
                                            <span>💧 <?php echo number_format($alert['humidity'], 1); ?>%</span>
                                            <span>💨 <?php echo $alert['smoke_level']; ?> ppm</span>
                                            <span>🔥 Flame: <?php echo $alert['flame_detected'] ? 'Yes' : 'No'; ?></span>
                                            <span>📈 Risk: <?php echo number_format($alert['fire_risk'], 1); ?>%</span>
                                        </div>
                                        <div class="alert-meta">
                                            Device: <?php echo htmlspecialchars($alert['device_id']); ?> | 
                                            Created: <?php echo date('Y-m-d H:i:s', strtotime($alert['created_at'])); ?>
                                        </div>
                                        
                                        <?php if ($alert['acknowledged']): ?>
                                        <div class="acknowledged-info">
                                            ✅ Acknowledged by <?php echo htmlspecialchars($alert['acknowledged_by_user']); ?> 
                                            at <?php echo date('Y-m-d H:i:s', strtotime($alert['acknowledged_at'])); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!$alert['acknowledged']): ?>
                                    <div>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                            <button type="submit" name="acknowledge" class="acknowledge-btn">
                                                Acknowledge
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <div class="pagination" style="margin-top: 20px; text-align: center;">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="btn btn-secondary">Previous</a>
                            <?php endif; ?>
                            
                            <span style="margin: 0 20px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="btn btn-secondary">Next</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; padding: 40px;">No alerts found matching your criteria.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>