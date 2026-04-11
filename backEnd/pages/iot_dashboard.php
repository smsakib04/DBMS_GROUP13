<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

$devices = $conn->query("SELECT device_code, device_type, location, battery_level, status FROM iot_devices");
$readings = $conn->query("SELECT d.location, r.temperature_c, r.humidity_percent, r.co2_ppm, r.reading_time FROM iot_readings r JOIN iot_devices d ON r.device_id = d.device_id ORDER BY r.reading_time DESC LIMIT 10");
$alerts = $conn->query("SELECT a.alert_id, d.device_code, a.parameter, a.actual_value, a.threshold_value, a.severity, a.alert_time FROM iot_alerts a JOIN iot_devices d ON a.device_id = d.device_id WHERE a.is_acknowledged = 0");
?>
<!DOCTYPE html>
<html>
<head>
    <title>IoT Device Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body{background:#eef3f0;font-family:Arial;margin:0;padding:20px;}
        .navbar{background:white;border-radius:60px;padding:10px 20px;display:flex;justify-content:space-between;}
        .nav-links{display:flex;gap:20px;}
        .nav-item{cursor:pointer;padding:8px;}
        .nav-item.active{border-bottom:3px solid #2a8b65;}
        .table-wrapper{display:none;background:white;padding:20px;margin-top:20px;border-radius:16px;}
        .table-wrapper.active-table{display:block;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:8px;border-bottom:1px solid #ccc;}
        th{background:#1a6d4e;color:white;}
    </style>
</head>
<body>
<div class="navbar">
    <div class="nav-links">
        <span class="nav-item active" data-table="sensors">Active Sensors</span>
        <span class="nav-item" data-table="enviro">Enclosure Readings</span>
        <span class="nav-item" data-table="alerts">Alerts</span>
    </div>
    <div><button onclick="window.location.href='../logout.php'">Logout</button></div>
</div>
<div id="sensors" class="table-wrapper active-table">
    <h2>Active IoT Sensors</h2>
    <table><thead><tr><th>Device Code</th><th>Type</th><th>Location</th><th>Battery (%)</th><th>Status</th></tr></thead><tbody>
    <?php while($row = $devices->fetch_assoc()): ?>
        <tr><td><?php echo $row['device_code']; ?></td><td><?php echo $row['device_type']; ?></td><td><?php echo $row['location']; ?></td><td><?php echo $row['battery_level']; ?>%</td><td><?php echo $row['status']; ?></td></tr>
    <?php endwhile; ?>
    </tbody></table>
</div>
<div id="enviro" class="table-wrapper">
    <h2>Enclosure Environmental Readings</h2>
    <table><thead><tr><th>Location</th><th>Temp (°C)</th><th>Humidity (%)</th><th>CO₂ (ppm)</th><th>Reading Time</th></tr></thead><tbody>
    <?php while($row = $readings->fetch_assoc()): ?>
        <tr><td><?php echo $row['location']; ?></td><td><?php echo $row['temperature_c']; ?></td><td><?php echo $row['humidity_percent']; ?></td><td><?php echo $row['co2_ppm']; ?></td><td><?php echo $row['reading_time']; ?></td></tr>
    <?php endwhile; ?>
    </tbody></table>
</div>
<div id="alerts" class="table-wrapper">
    <h2>Active Alerts</h2>
    <table><thead><tr><th>Device</th><th>Parameter</th><th>Value</th><th>Threshold</th><th>Severity</th><th>Time</th></tr></thead><tbody>
    <?php while($row = $alerts->fetch_assoc()): ?>
        <tr><td><?php echo $row['device_code']; ?></td><td><?php echo $row['parameter']; ?></td><td><?php echo $row['actual_value']; ?></td><td><?php echo $row['threshold_value']; ?></td><td><?php echo $row['severity']; ?></td><td><?php echo $row['alert_time']; ?></td></tr>
    <?php endwhile; ?>
    </tbody></table>
</div>
<script>
    const navItems = document.querySelectorAll('.nav-item');
    const tables = { sensors:document.getElementById('sensors'), enviro:document.getElementById('enviro'), alerts:document.getElementById('alerts') };
    function deactivateAll(){ Object.values(tables).forEach(t=>t.classList.remove('active-table')); navItems.forEach(i=>i.classList.remove('active')); }
    function showTable(id,el){ deactivateAll(); tables[id].classList.add('active-table'); el.classList.add('active'); }
    navItems.forEach(item=>{ item.addEventListener('click',function(){ showTable(this.dataset.table,this); }); });
</script>
</body>
</html>