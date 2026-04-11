<?php
require_once '../includes/session.php';
//requireLogin();
require_once '../config/db.php';

$staffSchedule = $conn->query("SELECT s.full_name, t.task_name, t.due_date, t.status, t.completion_notes FROM tasks t JOIN staff s ON t.assigned_to = s.staff_id ORDER BY t.due_date");
$inventory = $conn->query("SELECT item_name, quantity, unit, supplier, last_updated FROM inventory");
$restockRequests = $conn->query("SELECT item_name, quantity_needed, priority, needed_by_date, status FROM restock_requests ORDER BY needed_by_date");
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
        <button class="tab-button" onclick="showTab('request-restock')">Request Restock</button>
        <button style="margin-left:auto;" onclick="window.location.href='../logout.php'">Logout</button>
    </div>
    <div id="staff-schedule" class="tab-content active">
        <h2>Staff Tasks</h2>
        <button class="btn" onclick="window.location.href='add_task.html'">Add Task</button>
        <table><thead><tr><th>Staff</th><th>Task</th><th>Due Date</th><th>Status</th><th>Notes</th></tr></thead><tbody>
        <?php while($row = $staffSchedule->fetch_assoc()): ?>
            <tr><td><?php echo $row['full_name']; ?></td><td><?php echo $row['task_name']; ?></td><td><?php echo $row['due_date']; ?></td><td><?php echo $row['status']; ?></td><td><?php echo $row['completion_notes']; ?></td></tr>
        <?php endwhile; ?>
        </tbody></table>
    </div>
    <div id="inventory" class="tab-content">
        <h2>Inventory Items</h2>
        <button class="btn" onclick="window.location.href='add_inventory_item.html'">Add Item</button>
        <table><thead><tr><th>Item</th><th>Quantity</th><th>Unit</th><th>Supplier</th><th>Last Updated</th></tr></thead><tbody>
        <?php while($row = $inventory->fetch_assoc()): ?>
            <tr><td><?php echo $row['item_name']; ?></td><td><?php echo $row['quantity']; ?></td><td><?php echo $row['unit']; ?></td><td><?php echo $row['supplier']; ?></td><td><?php echo $row['last_updated']; ?></td></tr>
        <?php endwhile; ?>
        </tbody></table>
    </div>
    <div id="request-restock" class="tab-content">
        <h2>Restock Requests</h2>
        <button class="btn" onclick="window.location.href='add_restock_request.html'">New Request</button>
        <table><thead><tr><th>Item</th><th>Qty Needed</th><th>Priority</th><th>Needed By</th><th>Status</th></tr></thead><tbody>
        <?php while($row = $restockRequests->fetch_assoc()): ?>
            <tr><td><?php echo $row['item_name']; ?></td><td><?php echo $row['quantity_needed']; ?></td><td><?php echo $row['priority']; ?></td><td><?php echo $row['needed_by_date']; ?></td><td><?php echo $row['status']; ?></td></tr>
        <?php endwhile; ?>
        </tbody></table>
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