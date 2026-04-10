<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

// Fetch counts
$total_tortoises = $conn->query("SELECT COUNT(*) AS cnt FROM tortoises")->fetch_assoc()['cnt'];
$active_incubating = $conn->query("SELECT COUNT(*) AS cnt FROM nests WHERE actual_hatch_date IS NULL")->fetch_assoc()['cnt'];
$pending_tasks = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE status = 'Pending'")->fetch_assoc()['cnt'];
$hatch_success = $conn->query("SELECT AVG(hatch_success_rate) AS avg FROM nests WHERE hatch_success_rate IS NOT NULL")->fetch_assoc()['avg'];
$hatch_success = round($hatch_success ?: 0);

// For chart – we'll get action logs count per role (simplified)
$role_counts = [];
$roles = ['Collecting', 'Supervisor', 'Caretaker', 'Feeder', 'Veterinarian', 'Breeding', 'IoT'];
// For demo, we can query tasks assigned per role or just use static numbers from original chart.
// We'll keep the original Chart.js data but we can also fetch from DB if needed.
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tortoise Conservation Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* copy all styles from original homepage.html – omitted for brevity */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
        body { background: linear-gradient(135deg, #e8f3ef 0%, #d4e8df 100%); }
        .navbar { background: white; padding: 1rem 2rem; border-bottom: 2px solid #c2e0d2; }
        .nav-container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .logo { display: flex; align-items: center; gap: 0.8rem; }
        .nav-links { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .user-nav-btn { background: #f0f8f4; border: 1px solid #cfe6dc; padding: 0.7rem 1.2rem; border-radius: 40px; cursor: pointer; }
        .hero { background: linear-gradient(120deg, #1f6e4f, #2c8f68); color: white; text-align: center; padding: 3rem 2rem; border-radius: 0 0 48px 48px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 1.5rem; margin: 2rem 0; }
        .stat-box { background: white; border-radius: 28px; padding: 1.5rem; text-align: center; box-shadow: 0 12px 28px rgba(0,0,0,0.08); }
        .stat-box i { font-size: 2.5rem; color: #328f68; background: #e3f4ed; padding: 0.8rem; border-radius: 60px; }
        .stat-box h3 { font-size: 2rem; color: #1c5f45; }
        .dashboard-row { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin: 2rem 0; }
        .card { background: white; border-radius: 32px; padding: 1.5rem; box-shadow: 0 20px 35px -12px rgba(0,0,0,0.15); }
        .chart-container { height: 280px; }
        .main-container { max-width: 1400px; margin: 0 auto; padding: 1rem 2rem; }
        .feature-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px,1fr)); gap: 1.5rem; margin-top: 2rem; }
        footer { text-align: center; padding: 2rem; color: #4f7c68; border-top: 1px solid #cbe2d7; }
        @media (max-width:850px){ .dashboard-row{ grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="navbar">
    <div class="nav-container">
        <div class="logo"><i class="fas fa-shield-tortoise"></i><div><h1>Tortoise Conservation <span>Center</span></h1></div></div>
        <div class="nav-links">
            <button class="user-nav-btn" data-user="collecting"><i class="fas fa-clipboard-list"></i> Collecting Officer</button>
            <button class="user-nav-btn" data-user="supervisor"><i class="fas fa-chalkboard-user"></i> Supervisor</button>
            <button class="user-nav-btn" data-user="caretaker"><i class="fas fa-hand-holding-heart"></i> Caretaker</button>
            <button class="user-nav-btn" data-user="feeder"><i class="fas fa-apple-alt"></i> Feeder</button>
            <button class="user-nav-btn" data-user="veterenian"><i class="fas fa-stethoscope"></i> Veterenian</button>
            <button class="user-nav-btn" data-user="breeding"><i class="fas fa-paw"></i> Breeding Officer</button>
            <button class="user-nav-btn" data-user="iot"><i class="fas fa-microchip"></i> IoT Device</button>
        </div>
    </div>
</div>
<div class="hero">
    <h2><i class="fas fa-leaf"></i> Preserving Giants, Protecting Futures</h2>
    <p>Integrated Management System for Tortoise Conservation</p>
</div>
<div class="main-container">
    <div class="stats-grid">
        <div class="stat-box"><i class="fas fa-turtle"></i><h3><?php echo $total_tortoises; ?></h3><p>Total Tortoises</p></div>
        <div class="stat-box"><i class="fas fa-egg"></i><h3><?php echo $active_incubating; ?></h3><p>Eggs Incubating</p></div>
        <div class="stat-box"><i class="fas fa-calendar-check"></i><h3><?php echo $pending_tasks; ?></h3><p>Active Tasks</p></div>
        <div class="stat-box"><i class="fas fa-chart-line"></i><h3><?php echo $hatch_success; ?>%</h3><p>Hatching Success</p></div>
    </div>
    <div class="dashboard-row">
        <div class="card"><h3><i class="fas fa-chart-simple"></i> Staff Activity (last 30d)</h3><div class="chart-container"><canvas id="activityChart"></canvas></div></div>
        <div class="card"><h3><i class="fas fa-users"></i> User Role Overview</h3>
            <ul class="user-activity-list">
                <li><span class="user-name"><i class="fas fa-clipboard-list"></i> Collecting Officer</span> <span class="badge-count">342 records</span></li>
                <li><span class="user-name"><i class="fas fa-chalkboard-user"></i> Supervisor</span> <span class="badge-count">28 reports</span></li>
                <li><span class="user-name"><i class="fas fa-hand-holding-heart"></i> Caretaker</span> <span class="badge-count">156 tasks</span></li>
                <li><span class="user-name"><i class="fas fa-apple-alt"></i> Feeder</span> <span class="badge-count">89 feeding logs</span></li>
                <li><span class="user-name"><i class="fas fa-stethoscope"></i> Veterenian</span> <span class="badge-count">47 treatments</span></li>
                <li><span class="user-name"><i class="fas fa-paw"></i> Breeding Officer</span> <span class="badge-count">23 clutches</span></li>
                <li><span class="user-name"><i class="fas fa-microchip"></i> IoT Devices</span> <span class="badge-count">12 sensors</span></li>
            </ul>
        </div>
    </div>
    <div class="feature-grid">...</div>
</div>
<script>
    // Chart.js initialization same as original – keep static numbers
    const ctx = document.getElementById('activityChart').getContext('2d');
    new Chart(ctx, { type:'bar', data:{ labels:['Collecting','Supervisor','Caretaker','Feeder','Veterinarian','Breeding','IoT'], datasets:[{ label:'Actions / Records', data:[342,128,456,289,147,203,980], backgroundColor:'#2a9d6e' }] }, options:{ responsive:true } });
    // Navigation to role pages
    document.querySelectorAll('.user-nav-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const role = btn.getAttribute('data-user');
            window.location.href = role + '.php';
        });
    });
</script>
</body>
</html>