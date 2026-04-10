<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

$collections = $conn->query("SELECT c.collection_id, t.name, c.source_type, c.location, c.initial_health, c.collection_date FROM collections c LEFT JOIN tortoises t ON c.tortoise_id = t.tortoise_id ORDER BY c.collection_date DESC");
$transports = $conn->query("SELECT tortoise_id, vehicle_id, from_location, to_location, status, transport_date FROM transport_logs ORDER BY transport_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Collecting Officer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
        body{background:#eef6f2;padding:20px;}
        .header{background:linear-gradient(135deg,#0f5132,#198754);color:white;padding:25px;border-radius:16px;margin-bottom:20px;}
        .nav{display:flex;gap:15px;margin-bottom:20px;}
        .nav button{padding:10px 18px;border:none;border-radius:25px;cursor:pointer;background:#d1e7dd;}
        .nav button.active{background:#198754;color:white;}
        .section{display:none;background:white;padding:20px;border-radius:16px;margin-bottom:20px;}
        .section.active{display:block;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:10px;border-bottom:1px solid #ddd;}
        th{background:#198754;color:white;}
        .btn{padding:8px 14px;border-radius:20px;border:none;cursor:pointer;margin-right:5px;}
        .btn-add{background:#198754;color:white;}
    </style>
</head>
<body>
<div class="header"><h1><i class="fas fa-truck"></i> Collecting Officer Dashboard</h1><p>Collection, transport & intake management</p></div>
<div class="nav"><button class="tab active" onclick="openTab('collection')">New Collection</button><button class="tab" onclick="openTab('records')">Collection Records</button><button class="tab" onclick="openTab('transport')">Transport Log</button></div>

<div id="collection" class="section active">
    <h2>Add New Tortoise</h2>
    <form action="../process/add_collection.php" method="POST">
        <input type="text" name="tortoise_id" placeholder="Tortoise ID (optional)">
        <input type="date" name="collection_date" required>
        <select name="source_type" required><option>Wild</option><option>Rescue</option><option>Donation</option></select>
        <input type="text" name="location" placeholder="Collection Location">
        <select name="initial_health" required><option>Healthy</option><option>Weak</option><option>Injured</option></select>
        <textarea name="notes" placeholder="Notes..."></textarea>
        <input type="number" name="collected_by" placeholder="Staff ID">
        <button type="submit" class="btn btn-add">Save Record</button>
    </form>
</div>

<div id="records" class="section">
    <h2>Collected Tortoises</h2>
    <button class="btn btn-add" onclick="window.location.href='add_collection.html'">Add</button>
    <table>
        <thead><tr><th>ID</th><th>Tortoise</th><th>Source</th><th>Location</th><th>Health</th><th>Date</th></tr></thead>
        <tbody><?php while($row = $collections->fetch_assoc()): ?>
            <tr><td><?php echo $row['collection_id']; ?></td><td><?php echo $row['name'] ?? 'N/A'; ?></td><td><?php echo $row['source_type']; ?></td><td><?php echo $row['location']; ?></td><td><?php echo $row['initial_health']; ?></td><td><?php echo $row['collection_date']; ?></td></tr>
        <?php endwhile; ?></tbody>
    </table>
</div>

<div id="transport" class="section">
    <h2>Transport Log</h2>
    <button class="btn btn-add" onclick="window.location.href='add_transport_log.html'">Add</button>
    <table><thead><tr><th>Tortoise ID</th><th>Vehicle</th><th>From</th><th>To</th><th>Status</th><th>Date</th></tr></thead>
    <tbody><?php while($row = $transports->fetch_assoc()): ?>
        <tr><td><?php echo $row['tortoise_id']; ?></td><td><?php echo $row['vehicle_id']; ?></td><td><?php echo $row['from_location']; ?></td><td><?php echo $row['to_location']; ?></td><td><?php echo $row['status']; ?></td><td><?php echo $row['transport_date']; ?></td></tr>
    <?php endwhile; ?></tbody></table>
</div>

<script>
function openTab(id){
    document.querySelectorAll('.section').forEach(sec=>sec.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(btn=>btn.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>