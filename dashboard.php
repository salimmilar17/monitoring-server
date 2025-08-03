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
    <title>Dashboard - Fire Detection System</title>
    <link rel="stylesheet" href="assets/css/style.css">
     <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
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
                <li class="active">
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
                <h1>Dashboard</h1>
                <div class="header-time">
                    <span id="current-time"></span>
                </div>
            </header>
            
            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                    <div class="stat-icon">🌡️</div>
                    <div class="stat-content">
                        <h3>Temperature</h3>
                        <p class="stat-value" data-sensor="temperature">
                            <?php echo $latestData ? number_format($latestData['temperature'], 1) . '°C' : 'N/A'; ?>
                        </p>
                     </div>
                    </div>
                    
                   <div class="stat-card">
                    <div class="stat-icon">💧</div>
                    <div class="stat-content">
                        <h3>Humidity</h3>
                        <p class="stat-value" data-sensor="humidity">
                            <?php echo $latestData ? number_format($latestData['humidity'], 1) . '%' : 'N/A'; ?>
                        </p>
                    </div>
                </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">💨</div>
                        <div class="stat-content">
                            <h3>Smoke Level</h3>
                            <p class="stat-value" data-sensor="smoke">
                                <?php echo $latestData ? $latestData['smoke_level'] . ' ppm' : 'N/A'; ?>
                            </p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🔥</div>
                        <div class="stat-content">
                            <h3>Fire Risk</h3>
                            <p class="stat-value" data-sensor="fire-risk">
                                <?php echo $latestData ? number_format($latestData['fire_risk'], 1) . '%' : 'N/A'; ?>
                            </p>
                        </div>
                    </div>
                </div>

              <!-- Charts Section -->
<div class="charts-section">
    <h2>Sensor Data Trends</h2>
    
    <!-- Chart Controls -->
    <div class="chart-controls">
        <div class="btn-group">
            <button class="btn btn-sm btn-secondary" onclick="updateChartPeriod('1h')">1 Hour</button>
            <button class="btn btn-sm btn-primary" onclick="updateChartPeriod('24h')">24 Hours</button>
            <button class="btn btn-sm btn-secondary" onclick="updateChartPeriod('7d')">7 Days</button>
            <button class="btn btn-sm btn-secondary" onclick="updateChartPeriod('30d')">30 Days</button>
        </div>
        
        <div class="btn-group" style="margin-left: 20px;">
            <button class="btn btn-sm btn-secondary" onclick="updateChartType('temperature')">Temperature</button>
            <button class="btn btn-sm btn-secondary" onclick="updateChartType('humidity')">Humidity</button>
            <button class="btn btn-sm btn-secondary" onclick="updateChartType('smoke')">Smoke</button>
            <button class="btn btn-sm btn-primary" onclick="updateChartType('all')">All Sensors</button>
        </div>
    </div>
    
    <!-- Chart Container -->
    <div class="chart-container">
        <canvas id="sensorChart"></canvas>
    </div>
</div>
                
                <!-- Current Status -->
                <div class="status-section">
                    <h2>Current Status</h2>
                    <div class="status-display">
                        <?php if ($latestData): ?>
                            <div class="status-main">
                                <div id="status-badge"><?php echo getStatusBadge($latestData['status']); ?></div>
                                <p>Last update: <span id="last-update"><?php echo timeAgo($latestData['inputDate']); ?></span></p>
                            </div>
                           <div class="status-details">
                            <p data-sensor="flame">🔥 Flame: <?php echo $latestData['flame_detected'] ? '<span class="text-danger">DETECTED</span>' : '<span class="text-success">Not detected</span>'; ?></p>
                            <p data-sensor="mqtt">📡 MQTT: <?php echo $latestData['mqtt_connected'] ? '<span class="text-success">Connected</span>' : '<span class="text-danger">Disconnected</span>'; ?></p>
                            <p data-sensor="wifi">📶 WiFi: <?php echo $latestData['wifi_rssi'] ?? 'N/A'; ?> dBm</p>
                        </div>
                        <?php else: ?>
                            <p>No data received yet</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Alerts -->
                <div class="alerts-section">
                    <h2>Recent Alerts</h2>
                    <div id="alerts-list-container">
                        <?php if (count($activeAlerts) > 0): ?>
                            <div class="alerts-list">
                                <?php foreach ($activeAlerts as $alert): ?>
                                    <div class="alert-item alert-<?php echo $alert['alert_type']; ?>">
                                        <div class="alert-header">
                                            <span class="alert-type"><?php echo strtoupper($alert['alert_type']); ?></span>
                                            <span class="alert-time"><?php echo timeAgo($alert['created_at']); ?></span>
                                        </div>
                                        <p><?php echo htmlspecialchars($alert['message']); ?></p>
                                        <div class="alert-data">
                                            <span>🌡️ <?php echo number_format($alert['temperature'], 1); ?>°C</span>
                                            <span>💨 <?php echo $alert['smoke_level']; ?> ppm</span>
                                            <span>🔥 <?php echo $alert['flame_detected'] ? 'Yes' : 'No'; ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="alerts.php" class="btn btn-secondary">View All Alerts</a>
                        <?php else: ?>
                            <p>No active alerts</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="statistics-section">
                    <h2>Today's Statistics</h2>
                   <div class="stats-info">
                    <p>📊 Total Readings: <span data-stat="readings-today"><?php echo $todayReadings; ?></span></p>
                    <p>🚨 Active Alerts: <span data-stat="active-alerts"><?php echo $activeAlertCount; ?></span></p>
                </div>
                </div>
            </div>
        </div>
    </div>
    <script>
            // Wait for Chart.js to load
            window.addEventListener('load', function() {
                console.log('Page loaded, initializing chart...');
                
                // Check if Chart.js is loaded
                if (typeof Chart === 'undefined') {
                    console.error('Chart.js not loaded!');
                    return;
                }
                
                initializeChart();
            });

            // Chart variables
            let sensorChart = null;
            let currentChartType = 'all';
            let currentPeriod = '24h';

            // Initialize chart
            function initializeChart() {
                console.log('Initializing chart...');
                
                const canvas = document.getElementById('sensorChart');
                if (!canvas) {
                    console.error('Canvas element not found!');
                    return;
                }
                
                const ctx = canvas.getContext('2d');
                
                // Create initial empty chart
                sensorChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: [],
                        datasets: []
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Sensor Data Chart'
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Time'
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Value'
                                },
                                beginAtZero: true
                            }
                        }
                    }
                });
                
                console.log('Chart created, loading data...');
                
                // Load initial data
                loadChartData(currentChartType, currentPeriod);
            }

            // Load chart data from API
            function loadChartData(type, period) {
                console.log(`Loading chart data: type=${type}, period=${period}`);
                
                // Show loading state
                const container = document.querySelector('.chart-container');
                if (container) {
                    container.classList.add('loading');
                }
                
                // Build API URL
                const apiUrl = `api/get-chart-data.php?type=${type}&period=${period}`;
                console.log('API URL:', apiUrl);
                
                fetch(apiUrl)
                    .then(response => {
                        console.log('Response status:', response.status);
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('API Response:', data);
                        
                        if (data.success && data.chartData) {
                            updateChart(data.chartData, type);
                            
                            // Update chart title
                            let titleText = '';
                            switch(type) {
                                case 'temperature':
                                    titleText = 'Temperature Trends';
                                    break;
                                case 'humidity':
                                    titleText = 'Humidity Trends';
                                    break;
                                case 'smoke':
                                    titleText = 'Smoke Level Trends';
                                    break;
                                case 'all':
                                    titleText = 'All Sensor Data';
                                    break;
                            }
                            
                            let periodText = '';
                            switch(period) {
                                case '1h':
                                    periodText = 'Last Hour';
                                    break;
                                case '24h':
                                    periodText = 'Last 24 Hours';
                                    break;
                                case '7d':
                                    periodText = 'Last 7 Days';
                                    break;
                                case '30d':
                                    periodText = 'Last 30 Days';
                                    break;
                            }
                            
                            sensorChart.options.plugins.title.text = `${titleText} - ${periodText}`;
                            sensorChart.update();
                            
                            // Show data summary
                            if (data.summary) {
                                console.log(`Loaded ${data.summary.data_points} data points`);
                            }
                        } else {
                            console.error('Invalid data format or no data');
                            // Show no data message
                            showNoDataMessage();
                        }
                        
                        // Remove loading state
                        if (container) {
                            container.classList.remove('loading');
                        }
                    })
                    .catch(error => {
                        console.error('Error loading chart data:', error);
                        
                        // Show error message
                        showErrorMessage(error.message);
                        
                        // Remove loading state
                        if (container) {
                            container.classList.remove('loading');
                        }
                    });
            }

            // Show no data message
            function showNoDataMessage() {
                if (sensorChart) {
                    sensorChart.data.labels = ['No Data'];
                    sensorChart.data.datasets = [{
                        label: 'No Data Available',
                        data: [0],
                        borderColor: 'rgb(200, 200, 200)',
                        backgroundColor: 'rgba(200, 200, 200, 0.1)'
                    }];
                    sensorChart.options.plugins.title.text = 'No Data Available';
                    sensorChart.update();
                }
            }

            // Show error message
            function showErrorMessage(message) {
                console.error('Chart Error:', message);
                const container = document.querySelector('.chart-container');
                if (container) {
                    container.innerHTML = `
                        <div style="text-align: center; padding: 50px; color: #666;">
                            <p>Error loading chart data</p>
                            <p style="font-size: 14px; color: #999;">${message}</p>
                            <button class="btn btn-primary" onclick="location.reload()">Reload Page</button>
                        </div>
                    `;
                }
            }

            // Update chart with new data
            function updateChart(chartData, type) {
                sensorChart.data.labels = chartData.labels;
                sensorChart.data.datasets = chartData.datasets;
                
                // Update scales based on type
                if (type === 'all') {
                    // For multiple datasets, we might need dual y-axis
                    sensorChart.options.scales = {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Temperature (°C) / Humidity (%) / Risk (%)'
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Smoke Level (ppm)'
                            },
                            grid: {
                                drawOnChartArea: false,
                            }
                        }
                    };
                } else {
                    // Single dataset
                    let yAxisTitle = '';
                    switch(type) {
                        case 'temperature':
                            yAxisTitle = 'Temperature (°C)';
                            break;
                        case 'humidity':
                            yAxisTitle = 'Humidity (%)';
                            break;
                        case 'smoke':
                            yAxisTitle = 'Smoke Level (ppm)';
                            break;
                    }
                    
                    sensorChart.options.scales = {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Time'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: yAxisTitle
                            }
                        }
                    };
                }
                
                sensorChart.update();
            }

            // Update chart period
            function updateChartPeriod(period) {
                currentPeriod = period;
                
                // Update button states
                document.querySelectorAll('.chart-controls .btn-group:first-child .btn').forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-secondary');
                });
                event.target.classList.remove('btn-secondary');
                event.target.classList.add('btn-primary');
                
                // Reload chart data
                loadChartData(currentChartType, currentPeriod);
            }

            // Update chart type
            function updateChartType(type) {
                currentChartType = type;
                
                // Update button states
                document.querySelectorAll('.chart-controls .btn-group:last-child .btn').forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-secondary');
                });
                event.target.classList.remove('btn-secondary');
                event.target.classList.add('btn-primary');
                
                // Reload chart data
                loadChartData(currentChartType, currentPeriod);
            }

    // Auto-refresh chart every 30 seconds
    setInterval(function() {
        if (document.visibilityState === 'visible') {
            loadChartData(currentChartType, currentPeriod);
        }
    }, 30000);
    </script>
</body>
</html>