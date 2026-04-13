<?php
require_once '../backEnd/includes/session.php';
//requireLogin();
require_once '../backEnd/config/db.php';

$staffSchedule = $conn->query("SELECT t.task_id, s.full_name, t.task_name, t.due_date, t.status, t.completion_notes FROM tasks t JOIN staff s ON t.assigned_to = s.staff_id ORDER BY t.due_date");
$inventory = $conn->query("SELECT inventory_id, item_name, quantity, unit, supplier, last_updated FROM inventory");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Supervisor Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body{background:#f4f0e6;margin:0;font-family:Arial;}
        .tab-container{margin:20px;}
        .tab-buttons{display:flex;gap:10px;background:#2a1a1a;padding:10px;}
        .tab-button{background:#6a3a2d;color:white;padding:10px 20px;border:none;cursor:pointer;}
        .tab-button.active{background:#7a4a3d;}
        .tab-content{display:none;background:white;padding:20px;border-radius:8px;}
        .tab-content.active{display:block;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:8px;border-bottom:1px solid #ccc;}
        th{background:#68382d;color:white;}
        .btn{padding:8px 16px;background:#198754;color:white;border:none;border-radius:4px;cursor:pointer;}
    </style>
</head>
<body>
<div class="tab-container">
    <div class="tab-buttons">
        <button class="tab-button active" onclick="showTab('staff-schedule')">Staff Schedule</button>
        <button class="tab-button" onclick="showTab('inventory')">Inventory</button>
        <button style="margin-left:auto;" onclick="window.location.href='logout.php'">Logout</button>
    </div>
    <div id="staff-schedule" class="tab-content active">
        <h2>Staff Tasks</h2>
        <button class="btn" onclick="window.location.href='add_task.html'">Add Task</button>
        <table>
            <thead><tr><th>Staff</th><th>Task</th><th>Due Date</th><th>Status</th><th>Notes</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while($row = $staffSchedule->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo htmlspecialchars($row['completion_notes']); ?></td>
                    <td>
                        <a href="editTask.html?id=<?= $row['task_id'] ?>"><button class="btn">Edit</button></a>
                        <a href="delete_task.php?id=<?= $row['task_id'] ?>" onclick="return confirm('Delete this task?')"><button class="btn" style="background:#dc3545;">Delete</button></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div id="inventory" class="tab-content">
        <h2>Inventory Items</h2>
        <button class="btn" onclick="window.location.href='add_inventory_item.html'">Add Item</button>
        <table>
            <thead><tr><th>Item</th><th>Quantity</th><th>Unit</th><th>Supplier</th><th>Last Updated</th><th>Actions</th></tr></thead>
            <tbody>
                <?php while($row = $inventory->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo $row['quantity']; ?></td>
                    <td><?php echo htmlspecialchars($row['unit']); ?></td>
                    <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_updated']); ?></td>
                    <td>
                        <a href="edit_inventory_item.html?id=<?= $row['inventory_id'] ?>"><button class="btn">Edit</button></a>
                        <a href="delete_inventory_item.php?id=<?= $row['inventory_id'] ?>" onclick="return confirm('Delete this item?')"><button class="btn" style="background:#dc3545;">Delete</button></a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function showTab(tabId){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(b=>b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>