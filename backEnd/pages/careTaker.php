<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

// Fetch data for all sections
$tortoises = $conn->query("SELECT t.tortoise_id, t.name, s.common_name, t.estimated_age_years, t.sex, t.health_status, MAX(h.assessment_date) as last_check FROM tortoises t JOIN species s ON t.species_id = s.species_id LEFT JOIN health_assessments h ON t.tortoise_id = h.tortoise_id GROUP BY t.tortoise_id ORDER BY t.tortoise_id LIMIT 10");
$enclosures = $conn->query("SELECT enclosure_code, size_sq_meters, habitat_type, current_occupancy, last_cleaning, next_maintenance FROM enclosures WHERE status='Active'");
$tasks = $conn->query("SELECT task_name, assigned_to, due_date, status, completion_notes FROM tasks ORDER BY due_date ASC");
$healths = $conn->query("SELECT h.assessment_date, t.name, h.diagnosis, h.treatment, h.next_checkup_date FROM health_assessments h JOIN tortoises t ON h.tortoise_id = t.tortoise_id ORDER BY h.assessment_date DESC LIMIT 5");
$envs = $conn->query("SELECT d.location, r.temperature_c, r.humidity_percent, r.water_ph, r.reading_time FROM iot_readings r JOIN iot_devices d ON r.device_id = d.device_id ORDER BY r.reading_time DESC LIMIT 4");
$feedings = $conn->query("SELECT f.scheduled_date, f.feeding_time, t.name, f.food_type, f.amount_grams, f.is_done FROM feeding_schedules f JOIN tortoises t ON f.tortoise_id = t.tortoise_id WHERE f.scheduled_date = CURDATE() ORDER BY f.feeding_time");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Caretaker Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
        body{background:#ecf6f1;padding:2rem 1.5rem;display:flex;justify-content:center;}
        .dashboard{max-width:1600px;width:100%;}
        .header{display:flex;justify-content:space-between;margin-bottom:1.5rem;}
        .navbar{background:white;border-radius:60px;padding:0.8rem 2rem;margin-bottom:2rem;display:flex;justify-content:space-between;}
        .nav-links{display:flex;gap:1.8rem;}
        .nav-item{cursor:pointer;display:flex;align-items:center;gap:0.5rem;color:#2b6e53;font-weight:600;border-bottom:3px solid transparent;}
        .nav-item.active{border-bottom-color:#2a8b65;}
        .stats-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1.2rem;margin-bottom:2.5rem;}
        .stat-card{background:white;border-radius:24px;padding:1.2rem;display:flex;align-items:center;gap:0.8rem;}
        .table-wrapper{background:white;border-radius:28px;padding:1.5rem;margin-bottom:2.5rem;display:none;}
        .table-wrapper.active-table{display:block;}
        .table-header{display:flex;justify-content:space-between;margin-bottom:1.5rem;}
        .btn{background:white;border:1.5px solid #cae5d9;padding:0.6rem 1.3rem;border-radius:40px;cursor:pointer;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:0.85rem 0.5rem;border-bottom:1px solid #e4f3ec;}
        .badge-status{background:#dff0e9;padding:0.2rem 0.8rem;border-radius:50px;}
        @media (max-width:750px){.navbar{flex-direction:column;}}
    </style>
</head>
<body>
<div class="dashboard">
    <div class="header"><div class="title-section"><h1>Caretaker · Tortoise Center</h1><p>Daily husbandry, enclosures, health monitoring</p></div><div class="date-info"><?php echo date('d M Y'); ?> · Morning shift <span class="badge-role">CARETAKER</span></div></div>
    <div class="navbar"><div class="nav-links" id="navLinks"><span class="nav-item active" data-table="tortoises"><i class="fas fa-turtle"></i> Tortoise records</span><span class="nav-item" data-table="enclosures"><i class="fas fa-fence"></i> Enclosures</span><span class="nav-item" data-table="tasks"><i class="fas fa-clipboard-list"></i> Tasks</span><span class="nav-item" data-table="health"><i class="fas fa-notes-medical"></i> Health</span><span class="nav-item" data-table="environment"><i class="fas fa-temperature-low"></i> Environment</span><span class="nav-item" data-table="feeding"><i class="fas fa-utensils"></i> Feeding</span></div><div class="logout-btn" onclick="window.location.href='../logout.php'">Logout</div></div>
    <div class="stats-cards"><div class="stat-card"><i class="fas fa-turtle"></i><div class="stat-info"><h3><?php echo $tortoises->num_rows; ?></h3><span>tortoises</span></div></div><div class="stat-card"><i class="fas fa-fence"></i><div class="stat-info"><h3><?php echo $enclosures->num_rows; ?></h3><span>enclosures</span></div></div><div class="stat-card"><i class="fas fa-tasks"></i><div class="stat-info"><h3><?php echo $tasks->num_rows; ?></h3><span>pending tasks</span></div></div><div class="stat-card"><i class="fas fa-heartbeat"></i><div class="stat-info"><h3><?php echo $healths->num_rows; ?></h3><span>health checks</span></div></div></div>

    <!-- Tortoise Table -->
    <div class="table-wrapper" id="tortoises"><div class="table-header"><h2>Individual tortoise records</h2><div class="action-bar"><button class="btn" onclick="window.location.href='add_tortoise.html'">Add</button><button class="btn" onclick="window.location.href='edit_tortoise.html'">Edit</button><button class="btn" onclick="window.location.href='delete_tortoise.html'">Delete</button></div></div>
    <table><thead><tr><th>ID</th><th>Species</th><th>Age</th><th>Gender</th><th>Health status</th><th>Last check</th></tr></thead><tbody>
        <?php while($row = $tortoises->fetch_assoc()): ?>
            <tr><td><?php echo $row['tortoise_id']; ?></td><td><?php echo $row['common_name']; ?></td><td><?php echo $row['estimated_age_years'] ?? '?'; ?> yrs</td><td><?php echo $row['sex']; ?></td><td><span class="badge-status"><?php echo $row['health_status']; ?></span></td><td><?php echo $row['last_check'] ?? 'N/A'; ?></td></tr>
        <?php endwhile; ?>
    </tbody></table></div>

    <!-- Enclosures Table -->
    <div class="table-wrapper" id="enclosures"><div class="table-header"><h2>Enclosure management</h2></div>
    <table><thead><tr><th>Enclosure</th><th>Size (m²)</th><th>Habitat type</th><th>Current occupants</th><th>Last cleaning</th><th>Next maintenance</th></tr></thead><tbody>
        <?php while($row = $enclosures->fetch_assoc()): ?>
            <tr><td><?php echo $row['enclosure_code']; ?></td><td><?php echo $row['size_sq_meters']; ?></td><td><?php echo $row['habitat_type']; ?></td><td><?php echo $row['current_occupancy']; ?></td><td><?php echo $row['last_cleaning']; ?></td><td><?php echo $row['next_maintenance']; ?></td></tr>
        <?php endwhile; ?>
    </tbody></table></div>

    <!-- Tasks Table -->
    <div class="table-wrapper" id="tasks"><div class="table-header"><h2>Staff tasks & schedule</h2></div>
    <table><thead><tr><th>Task</th><th>Assigned to</th><th>Due date</th><th>Status</th><th>Completion notes</th></tr></thead><tbody>
        <?php while($row = $tasks->fetch_assoc()): ?>
            <tr><td><?php echo $row['task_name']; ?></td><td><?php echo $row['assigned_to']; ?></td><td><?php echo $row['due_date']; ?></td><td><span class="badge-status"><?php echo $row['status']; ?></span></td><td><?php echo $row['completion_notes']; ?></td></tr>
        <?php endwhile; ?>
    </tbody></table></div>

    <!-- Health assessments Table -->
    <div class="table-wrapper" id="health"><div class="table-header"><h2>Health assessments & treatments</h2></div>
    <table><thead><tr><th>Date</th><th>Tortoise</th><th>Diagnosis / treatment</th><th>Follow-up</th></tr></thead><tbody>
        <?php while($row = $healths->fetch_assoc()): ?>
            <tr><td><?php echo $row['assessment_date']; ?></td><td><?php echo $row['name']; ?></td><td><?php echo $row['diagnosis']; ?> / <?php echo $row['treatment']; ?></td><td><?php echo $row['next_checkup_date'] ?? '—'; ?></td></tr>
        <?php endwhile; ?>
    </tbody></table></div>

    <!-- Environment Table -->
    <div class="table-wrapper" id="environment"><div class="table-header"><h2>Environmental monitoring (IoT)</h2></div>
    <table><thead><tr><th>Location</th><th>Temp (°C)</th><th>Humidity (%)</th><th>Water pH</th><th>Last reading</th></tr></thead><tbody>
        <?php while($row = $envs->fetch_assoc()): ?>
            <tr><td><?php echo $row['location']; ?></td><td><?php echo $row['temperature_c']; ?></td><td><?php echo $row['humidity_percent']; ?></td><td><?php echo $row['water_ph']; ?></td><td><?php echo $row['reading_time']; ?></td></tr>
        <?php endwhile; ?>
    </tbody></table></div>

    <!-- Feeding Table -->
    <div class="table-wrapper" id="feeding"><div class="table-header"><h2>Feeding schedule & food inventory</h2></div>
    <table><thead><tr><th>Tortoise</th><th>Time</th><th>Food type</th><th>Amount (g)</th><th>Done</th></tr></thead><tbody>
        <?php while($row = $feedings->fetch_assoc()): ?>
            <tr><td><?php echo $row['name']; ?></td><td><?php echo $row['feeding_time']; ?></td><td><?php echo $row['food_type']; ?></td><td><?php echo $row['amount_grams']; ?></td><td><?php echo $row['is_done'] ? '✓' : '❌'; ?></td></tr>
        <?php endwhile; ?>
    </tbody></table></div>
</div>
<script>
    const navItems = document.querySelectorAll('.nav-item');
    const tables = { tortoises:document.getElementById('tortoises'), enclosures:document.getElementById('enclosures'), tasks:document.getElementById('tasks'), health:document.getElementById('health'), environment:document.getElementById('environment'), feeding:document.getElementById('feeding') };
    function deactivateAll(){ Object.values(tables).forEach(t=>t.classList.remove('active-table')); navItems.forEach(i=>i.classList.remove('active')); }
    function showTable(id,el){ deactivateAll(); tables[id].classList.add('active-table'); el.classList.add('active'); }
    navItems.forEach(item=>{ item.addEventListener('click',function(){ showTable(this.dataset.table,this); }); });
    showTable('tortoises', document.querySelector('.nav-item[data-table="tortoises"]'));
</script>
</body>
</html>