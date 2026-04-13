<?php
require_once '../includes/session.php';

require_once '../config/db.php';

$todayFeedings = $conn->query("SELECT f.schedule_id, f.feeding_time, t.name, f.food_type, f.amount_grams, f.is_done FROM feeding_schedules f JOIN tortoises t ON f.tortoise_id = t.tortoise_id WHERE f.scheduled_date = CURDATE() ORDER BY f.feeding_time");
$dietaryItems = $conn->query("SELECT item_name, amount_grams, notes FROM dietary_items");
$specialNeeds = $conn->query("SELECT s.tortoise_id, s.restriction, s.note FROM special_dietary_needs s JOIN tortoises t ON s.tortoise_id = t.tortoise_id");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Feeder Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body{font-family:'Segoe UI',sans-serif;background:#fff8e7;margin:0;padding:20px;}
        .tabs-container{background:white;border-radius:16px;padding:20px;margin:20px auto;max-width:1200px;}
        .tab-buttons{display:flex;gap:10px;border-bottom:2px solid #e1e8ed;}
        .tab-btn{padding:12px 24px;background:none;border:none;cursor:pointer;font-weight:600;}
        .tab-btn.active{color:#c85a3a;border-bottom:3px solid #c85a3a;}
        .tab-content{display:none;padding:20px;}
        .tab-content.active{display:block;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:12px;border-bottom:1px solid #ddd;}
        th{background:#c85a3a;color:white;}
        .done-btn{background:#27ae60;color:white;border:none;padding:6px 12px;border-radius:6px;cursor:pointer;}
    </style>
</head>
<body>
<div class="tabs-container">
    <div class="tab-buttons">
        <button class="tab-btn active" onclick="showTab(event,'todays-plan')">Today's Feeding Schedule</button>
        <button class="tab-btn" onclick="showTab(event,'current-plan')">Current Dietary Plan</button>
        <button class="tab-btn" onclick="showTab(event,'special-needs')">Special Dietary Needs</button>
    </div>
    <div id="todays-plan" class="tab-content active">
        <h2>Today's Feeding Schedule</h2>
        <button onclick="window.location.href='add_feeding_schedule.html'">Add Schedule</button>
        <table>
            <thead><tr><th>Time</th><th>Tortoise</th><th>Food Type</th><th>Amount (g)</th><th>Done</th><th>Action</th></tr></thead>
            <tbody><?php while($row = $todayFeedings->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['feeding_time']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['food_type']; ?></td>
                    <td><?php echo $row['amount_grams']; ?></td>
                    <td><?php echo $row['is_done'] ? '✓' : '❌'; ?></td>
                    <td><form action="../process/update_feeding_status.php" method="POST" style="display:inline;"><input type="hidden" name="schedule_id" value="<?php echo $row['schedule_id']; ?>"><select name="is_done"><option value="1">Mark Done</option><option value="0">Pending</option></select><button type="submit">Update</button></form></td>
                </tr>
            <?php endwhile; ?></tbody>
        </table>
    </div>
    <div id="current-plan" class="tab-content">
        <h2>Current Dietary Plan</h2>
        <button onclick="window.location.href='add_inventory_item.html'">Add Item</button>
        <table><thead><tr><th>Item Name</th><th>Amount (g)</th><th>Notes</th></tr></thead><tbody>
        <?php while($row = $dietaryItems->fetch_assoc()): ?>
            <tr><td><?php echo $row['item_name']; ?></td><td><?php echo $row['amount_grams']; ?></td><td><?php echo $row['notes']; ?></td></tr>
        <?php endwhile; ?>
        </tbody></table>
    </div>
    <div id="special-needs" class="tab-content">
        <h2>Special Dietary Needs</h2>
        <button onclick="window.location.href='add_special_dietary_need.html'">Add Need</button>
        <table><thead><tr><th>Tortoise ID</th><th>Restriction</th><th>Note</th></tr></thead><tbody>
        <?php while($row = $specialNeeds->fetch_assoc()): ?>
            <tr><td><?php echo $row['tortoise_id']; ?></td><td><?php echo $row['restriction']; ?></td><td><?php echo $row['note']; ?></td></tr>
        <?php endwhile; ?>
        </tbody></table>
    </div>
</div>
<script>
function showTab(evt, tabName){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    evt.currentTarget.classList.add('active');
}
</script>
</body>
</html>