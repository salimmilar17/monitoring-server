// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    
    const timeElement = document.getElementById('current-time');
    if (timeElement) {
        timeElement.textContent = timeString;
    }
}

// Update time every second
setInterval(updateTime, 1000);
updateTime();

// Sidebar toggle for mobile
const sidebarToggle = document.getElementById('sidebar-toggle');
const sidebar = document.querySelector('.sidebar');

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', () => {
        sidebar.classList.toggle('active');
    });
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
            sidebar.classList.remove('active');
        }
    }
});

// Alert acknowledgment
function acknowledgeAlert(alertId) {
    if (!confirm('Are you sure you want to acknowledge this alert?')) {
        return;
    }
    
    fetch('api/acknowledge-alert.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ alert_id: alertId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to acknowledge alert');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred');
    });
}

// Delete user confirmation
function deleteUser(userId) {
    if (!confirm('Are you sure you want to delete this user?')) {
        return;
    }
    
    window.location.href = `users.php?delete=${userId}`;
}

// Chart initialization (if using Chart.js)
function initializeCharts() {
    const tempChartElement = document.getElementById('temperatureChart');
    if (tempChartElement) {
        // Temperature chart code here
    }
    
    const smokeChartElement = document.getElementById('smokeChart');
    if (smokeChartElement) {
        // Smoke chart code here
    }
}

// Auto-refresh for real-time data
let autoRefresh = true;
let refreshInterval;

function toggleAutoRefresh() {
    autoRefresh = !autoRefresh;
    const btn = document.getElementById('auto-refresh-btn');
    
    if (autoRefresh) {
        btn.textContent = 'Disable Auto-refresh';
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-primary');
        startAutoRefresh();
    } else {
        btn.textContent = 'Enable Auto-refresh';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
        stopAutoRefresh();
    }
}

function startAutoRefresh() {
    refreshInterval = setInterval(() => {
        fetchLatestData();
    }, 5000); // Refresh every 5 seconds
}

function stopAutoRefresh() {
    clearInterval(refreshInterval);
}

function fetchLatestData() {
    fetch('api/get-latest-data.php')
        .then(response => response.json())
        .then(data => {
            updateDashboard(data);
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
}

function updateDashboard(data) {
    // Update temperature
    const tempElement = document.querySelector('.stat-value.temperature');
    if (tempElement && data.temperature) {
        tempElement.textContent = data.temperature.toFixed(1) + '°C';
    }
    
    // Update humidity
    const humidityElement = document.querySelector('.stat-value.humidity');
    if (humidityElement && data.humidity) {
        humidityElement.textContent = data.humidity.toFixed(1) + '%';
    }
    
    // Update smoke level
    const smokeElement = document.querySelector('.stat-value.smoke');
    if (smokeElement && data.smoke_level) {
        smokeElement.textContent = data.smoke_level + ' ppm';
    }
    
    // Update fire risk
    const riskElement = document.querySelector('.stat-value.risk');
    if (riskElement && data.fire_risk) {
        riskElement.textContent = data.fire_risk.toFixed(1) + '%';
    }
}

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    initializeCharts();
    if (autoRefresh && window.location.pathname.includes('dashboard.php')) {
        startAutoRefresh();
    }
});

// Update the fetchLatestData function in main.js
function fetchLatestData() {
    fetch('api/get-latest-data.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data) {
                updateDashboard(data.data, data.statistics);
            } else {
                console.error('No data received');
            }
        })
        .catch(error => {
            console.error('Error fetching data:', error);
        });
}

function updateDashboard(data, statistics) {
    // Update temperature
    const tempElements = document.querySelectorAll('[data-sensor="temperature"]');
    tempElements.forEach(el => {
        el.textContent = data.temperature.toFixed(1) + '°C';
    });
    
    // Update humidity
    const humidityElements = document.querySelectorAll('[data-sensor="humidity"]');
    humidityElements.forEach(el => {
        el.textContent = data.humidity.toFixed(1) + '%';
    });
    
    // Update smoke level
    const smokeElements = document.querySelectorAll('[data-sensor="smoke"]');
    smokeElements.forEach(el => {
        el.textContent = data.smoke_level + ' ppm';
    });
    
    // Update fire risk
    const riskElements = document.querySelectorAll('[data-sensor="fire-risk"]');
    riskElements.forEach(el => {
        el.textContent = data.fire_risk.toFixed(1) + '%';
    });
    
    // Update status badge
    const statusElement = document.querySelector('.status-main .badge');
    if (statusElement) {
        // Remove all status classes
        statusElement.classList.remove('badge-success', 'badge-warning', 'badge-danger');
        
        // Add appropriate class
        if (data.status === 'SAFE') {
            statusElement.classList.add('badge-success');
            statusElement.textContent = 'SAFE';
        } else if (data.status === 'WARNING') {
            statusElement.classList.add('badge-warning');
            statusElement.textContent = 'WARNING';
        } else if (data.status === 'DANGER') {
            statusElement.classList.add('badge-danger');
            statusElement.textContent = 'DANGER';
        }
    }
    
    // Update flame status
    const flameElement = document.querySelector('[data-sensor="flame"]');
    if (flameElement) {
        if (data.flame_detected) {
            flameElement.innerHTML = '<span class="text-danger">🔥 DETECTED</span>';
        } else {
            flameElement.innerHTML = '<span class="text-success">✓ Not detected</span>';
        }
    }
    
    // Update connection status
    const mqttElement = document.querySelector('[data-sensor="mqtt"]');
    if (mqttElement) {
        if (data.mqtt_connected) {
            mqttElement.innerHTML = '<span class="text-success">Connected</span>';
        } else {
            mqttElement.innerHTML = '<span class="text-danger">Disconnected</span>';
        }
    }
    
    // Update WiFi RSSI
    const wifiElement = document.querySelector('[data-sensor="wifi"]');
    if (wifiElement) {
        wifiElement.textContent = data.wifi_rssi + ' dBm';
    }
    
    // Update last update time
    const timeElement = document.querySelector('.status-main p');
    if (timeElement) {
        timeElement.textContent = 'Last update: ' + data.time_ago;
    }
    
    // Update statistics if provided
    if (statistics) {
        // Update active alerts count
        const alertBadge = document.querySelector('.sidebar-menu .badge');
        if (alertBadge) {
            if (statistics.active_alerts > 0) {
                alertBadge.textContent = statistics.active_alerts;
                alertBadge.style.display = 'inline-block';
            } else {
                alertBadge.style.display = 'none';
            }
        }
        
        // Update today's readings count
        const readingsElement = document.querySelector('[data-stat="readings-today"]');
        if (readingsElement) {
            readingsElement.textContent = statistics.readings_today;
        }
    }
}

// Export data function
function exportData(format) {
    window.location.href = `export.php?format=${format}`;
}

// Print function
function printReport() {
    window.print();
}