<?php
require_once '../includes/session.php';

require_once '../config/db.php';

$pairs = $conn->query("SELECT bp.pair_id, bp.pair_code, m.name as male, f.name as female, bp.pairing_date, bp.status FROM breeding_pairs bp JOIN tortoises m ON bp.male_tortoise_id = m.tortoise_id JOIN tortoises f ON bp.female_tortoise_id = f.tortoise_id");
$nests = $conn->query("SELECT n.nest_id, n.nest_code, bp.pair_code, n.nesting_date, n.egg_count, n.fertile_eggs, n.estimated_hatch_date, n.actual_hatch_date FROM nests n JOIN breeding_pairs bp ON n.pair_id = bp.pair_id");
$incubators = $conn->query("SELECT i.incubator_code, i.location, i.status, r.temperature_c, r.humidity_percent FROM incubators i LEFT JOIN incubation_readings r ON i.incubator_id = r.incubator_id GROUP BY i.incubator_id");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Breeding Officer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body{background:#e7f0ea;margin:0;font-family:Arial;}
        .navbar{background:white;padding:10px 20px;display:flex;justify-content:space-between;}
        .nav-links{display:flex;gap:20px;}
        .nav-item{cursor:pointer;padding:8px;color:#1f5b43;}
        .nav-item.active{border-bottom:3px solid #2a7254;}
        .table-wrapper{display:none;background:white;padding:20px;margin:20px;border-radius:16px;}
        .table-wrapper.active-table{display:block;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:8px;border-bottom:1px solid #ccc;}
        th{background:#1f4f3d;color:white;}
        .btn{padding:6px 12px;background:#1e5f45;color:white;border:none;border-radius:4px;cursor:pointer;}
    </style>
</head>
<body>
<div class="navbar">
    <div class="nav-links">
        <span class="nav-item active" data-table="pairs">Breeding pairs</span>
        <span class="nav-item" data-table="nesting">Nesting</span>
        <span class="nav-item" data-table="incubation">Incubation</span>
    </div>
    <div><button class="btn" onclick="window.location.href='../logout.php'">Logout</button></div>
</div>
<div id="pairs" class="table-wrapper active-table">
    <h2>Breeding Pairs</h2>
    <button class="btn" onclick="window.location.href='add_breeding_pair.html'">Add Pair</button>
    <table><thead><tr><th>Pair Code</th><th>Male</th><th>Female</th><th>Pairing Date</th><th>Status</th></tr></thead><tbody>
    <?php while($row = $pairs->fetch_assoc()): ?>
        <tr><td><?php echo $row['pair_code']; ?></td><td><?php echo $row['male']; ?></td><td><?php echo $row['female']; ?></td><td><?php echo $row['pairing_date']; ?></td><td><?php echo $row['status']; ?></td></tr>
    <?php endwhile; ?>
    </tbody></table>
</div>
<div id="nesting" class="table-wrapper">
    <h2>Nesting & Egg Records</h2>
    <button class="btn" onclick="window.location.href='add_nest.html'">Add Nest</button>
    <table><thead><tr><th>Nest Code</th><th>Pair</th><th>Nesting Date</th><th>Egg Count</th><th>Fertile</th><th>Est. Hatch</th><th>Actual Hatch</th></tr></thead><tbody>
    <?php while($row = $nests->fetch_assoc()): ?>
        <tr><td><?php echo $row['nest_code']; ?></td><td><?php echo $row['pair_code']; ?></td><td><?php echo $row['nesting_date']; ?></td><td><?php echo $row['egg_count']; ?></td><td><?php echo $row['fertile_eggs']; ?></td><td><?php echo $row['estimated_hatch_date']; ?></td><td><?php echo $row['actual_hatch_date']; ?></td></tr>
    <?php endwhile; ?>
    </tbody></table>
</div>
<div id="incubation" class="table-wrapper">
    <h2>Incubation & Hatching</h2>
    <table><thead><tr><th>Incubator</th><th>Location</th><th>Temp (°C)</th><th>Humidity (%)</th><th>Status</th></tr></thead><tbody>
    <?php while($row = $incubators->fetch_assoc()): ?>
        <tr><td><?php echo $row['incubator_code']; ?></td><td><?php echo $row['location']; ?></td><td><?php echo $row['temperature_c']; ?></td><td><?php echo $row['humidity_percent']; ?></td><td><?php echo $row['status']; ?></td></tr>
    <?php endwhile; ?>
    </tbody></table>
</div>
<script>
    const navItems = document.querySelectorAll('.nav-item');
    const tables = { pairs:document.getElementById('pairs'), nesting:document.getElementById('nesting'), incubation:document.getElementById('incubation') };
    function deactivateAll(){ Object.values(tables).forEach(t=>t.classList.remove('active-table')); navItems.forEach(i=>i.classList.remove('active')); }
    function showTable(id,el){ deactivateAll(); tables[id].classList.add('active-table'); el.classList.add('active'); }
    navItems.forEach(item=>{ item.addEventListener('click',function(){ showTable(this.dataset.table,this); }); });
</script>
</body>
</html>