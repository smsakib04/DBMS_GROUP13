<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';

// Fetch real data for tables
$devices = $conn->query("SELECT device_code, device_type, location, battery_level, status FROM iot_devices");
$readings = $conn->query("SELECT d.location, r.temperature_c, r.humidity_percent, r.co2_ppm, r.reading_time FROM iot_readings r JOIN iot_devices d ON r.device_id = d.device_id ORDER BY r.reading_time DESC LIMIT 10");
$alerts = $conn->query("SELECT a.alert_id, d.device_code, a.parameter, a.actual_value, a.threshold_value, a.severity, a.alert_time FROM iot_alerts a JOIN iot_devices d ON a.device_id = d.device_id WHERE a.is_acknowledged = 0");

// Prepare data for charts (last 10 temperature/humidity readings)
$chartReadings = $conn->query("
    SELECT r.temperature_c, r.humidity_percent, r.reading_time, d.location 
    FROM iot_readings r 
    JOIN iot_devices d ON r.device_id = d.device_id 
    ORDER BY r.reading_time DESC 
    LIMIT 10
");
$tempData = [];
$humidityData = [];
$timeLabels = [];
$locationLabels = [];
while ($row = $chartReadings->fetch_assoc()) {
    // Store in reverse order to show oldest first on chart
    array_unshift($tempData, $row['temperature_c']);
    array_unshift($humidityData, $row['humidity_percent']);
    array_unshift($timeLabels, date('H:i', strtotime($row['reading_time'])));
    array_unshift($locationLabels, $row['location']);
}

// If no real data, generate dummy data for demo
if (empty($tempData)) {
    for ($i = 0; $i < 10; $i++) {
        $tempData[] = rand(20, 35);
        $humidityData[] = rand(40, 80);
        $timeLabels[] = date('H:i', strtotime("-$i hours"));
        $locationLabels[] = "Enclosure " . chr(65 + $i % 3);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>IoT Device Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body{background:#eef3f0;font-family:Arial;margin:0;padding:20px;}
        .navbar{background:white;border-radius:60px;padding:10px 20px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;}
        .nav-links{display:flex;gap:20px;}
        .nav-item{cursor:pointer;padding:8px;color:#2b6e53;font-weight:600;}
        .nav-item.active{border-bottom:3px solid #2a8b65;}
        .logout-btn{background:#fff3ec;border:1.5px solid #f3bc9a;color:#aa4e1e;padding:6px 18px;border-radius:40px;cursor:pointer;}
        .table-wrapper{display:none;background:white;padding:20px;margin-top:20px;border-radius:16px;}
        .table-wrapper.active-table{display:block;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:8px;border-bottom:1px solid #ccc;}
        th{background:#1a6d4e;color:white;}
        .graph-container{background:white;border-radius:16px;padding:20px;margin-top:20px;}
        canvas{max-height:300px;width:100%;}
        .charts-row{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
        @media (max-width:700px){ .charts-row{grid-template-columns:1fr;} .navbar{flex-direction:column;gap:10px;} }
    </style>
</head>
<body>
<div class="navbar">
    <div class="nav-links">
        <span class="nav-item active" data-table="sensors"><i class="fas fa-microchip"></i> Active Sensors</span>
        <span class="nav-item" data-table="enviro"><i class="fas fa-chart-line"></i> Enclosure Readings</span>
        <span class="nav-item" data-table="alerts"><i class="fas fa-bell"></i> Alerts</span>
    </div>
    <div class="logout-btn" onclick="window.location.href='logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</div>
</div>

<!-- Graphs Row -->
<div class="charts-row">
    <div class="graph-container">
        <h3><i class="fas fa-thermometer-half"></i> Temperature Trend (last 10 readings)</h3>
        <canvas id="tempChart"></canvas>
    </div>
    <div class="graph-container">
        <h3><i class="fas fa-tint"></i> Humidity Trend (last 10 readings)</h3>
        <canvas id="humidityChart"></canvas>
    </div>
</div>

<div id="sensors" class="table-wrapper active-table">
    <h2>Active IoT Sensors</h2>
    <table><thead><tr><th>Device Code</th><th>Type</th><th>Location</th><th>Battery (%)</th><th>Status</th></tr></thead><tbody>
    <?php while($row = $devices->fetch_assoc()): ?>
        <tr><td><?php echo htmlspecialchars($row['device_code']); ?></td>
        <td><?php echo htmlspecialchars($row['device_type']); ?></td>
        <td><?php echo htmlspecialchars($row['location']); ?></td>
        <td><?php echo $row['battery_level']; ?>%</td>
        <td><?php echo htmlspecialchars($row['status']); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody></table>
</div>

<div id="enviro" class="table-wrapper">
    <h2>Enclosure Environmental Readings</h2>
    <table><thead><tr><th>Location</th><th>Temp (°C)</th><th>Humidity (%)</th><th>CO₂ (ppm)</th><th>Reading Time</th></tr></thead><tbody>
    <?php
    // Re-fetch readings for the table (the previous $readings result may have been used)
    $readingsTable = $conn->query("SELECT d.location, r.temperature_c, r.humidity_percent, r.co2_ppm, r.reading_time FROM iot_readings r JOIN iot_devices d ON r.device_id = d.device_id ORDER BY r.reading_time DESC LIMIT 10");
    while($row = $readingsTable->fetch_assoc()): ?>
        <tr><td><?php echo htmlspecialchars($row['location']); ?></td>
        <td><?php echo $row['temperature_c']; ?></td>
        <td><?php echo $row['humidity_percent']; ?></td>
        <td><?php echo $row['co2_ppm']; ?></td>
        <td><?php echo htmlspecialchars($row['reading_time']); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody></table>
</div>

<div id="alerts" class="table-wrapper">
    <h2>Active Alerts</h2>
    <table><thead><tr><th>Device</th><th>Parameter</th><th>Value</th><th>Threshold</th><th>Severity</th><th>Time</th></tr></thead><tbody>
    <?php while($row = $alerts->fetch_assoc()): ?>
        <tr><td><?php echo htmlspecialchars($row['device_code']); ?></td>
        <td><?php echo htmlspecialchars($row['parameter']); ?></td>
        <td><?php echo htmlspecialchars($row['actual_value']); ?></td>
        <td><?php echo htmlspecialchars($row['threshold_value']); ?></td>
        <td><?php echo htmlspecialchars($row['severity']); ?></td>
        <td><?php echo htmlspecialchars($row['alert_time']); ?></td>
    </tr>
    <?php endwhile; ?>
    </tbody></table>
</div>

<script>
    // Data passed from PHP
    const timeLabels = <?php echo json_encode($timeLabels); ?>;
    const tempData = <?php echo json_encode($tempData); ?>;
    const humidityData = <?php echo json_encode($humidityData); ?>;

    // Temperature Chart
    const tempCtx = document.getElementById('tempChart').getContext('2d');
    new Chart(tempCtx, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Temperature (°C)',
                data: tempData,
                borderColor: '#e67e22',
                backgroundColor: 'rgba(230,126,34,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { title: { display: true, text: 'Temperature (°C)' } } }
        }
    });

    // Humidity Chart
    const humidityCtx = document.getElementById('humidityChart').getContext('2d');
    new Chart(humidityCtx, {
        type: 'line',
        data: {
            labels: timeLabels,
            datasets: [{
                label: 'Humidity (%)',
                data: humidityData,
                borderColor: '#3498db',
                backgroundColor: 'rgba(52,152,219,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: { y: { title: { display: true, text: 'Humidity (%)' }, min: 0, max: 100 } }
        }
    });

    // Navigation tabs
    const navItems = document.querySelectorAll('.nav-item');
    const tables = { sensors:document.getElementById('sensors'), enviro:document.getElementById('enviro'), alerts:document.getElementById('alerts') };
    function deactivateAll(){ Object.values(tables).forEach(t=>t.classList.remove('active-table')); navItems.forEach(i=>i.classList.remove('active')); }
    function showTable(id,el){ deactivateAll(); tables[id].classList.add('active-table'); el.classList.add('active'); }
    navItems.forEach(item=>{ item.addEventListener('click',function(){ showTable(this.dataset.table,this); }); });
</script>
</body>
</html>