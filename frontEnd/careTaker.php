<?php
require_once '../backEnd/includes/session.php';
require_once '../backEnd/config/db.php';

// ---------------- Fetch Data ----------------
$tasks = [];
$res = $conn->query("
    SELECT t.task_id, t.task_name, s.full_name AS assigned_to,
           t.due_date, t.status, t.completion_notes
    FROM tasks t
    JOIN staff s ON t.assigned_to = s.staff_id
    ORDER BY t.due_date ASC
");
while ($row = $res->fetch_assoc()) $tasks[] = $row;

$inventoryItems = [];
$res = $conn->query("SELECT * FROM inventory ORDER BY item_name");
while ($row = $res->fetch_assoc()) $inventoryItems[] = $row;

// Stats
$pendingTasks = $conn->query("SELECT COUNT(*) c FROM tasks WHERE status!='Completed'")->fetch_assoc()['c'];
$completedThisWeek = $conn->query("SELECT COUNT(*) c FROM tasks WHERE status='Completed' AND due_date>=DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['c'];
$lowStockCount = $conn->query("SELECT COUNT(*) c FROM inventory WHERE quantity<reorder_level")->fetch_assoc()['c'];
$totalInventory = $conn->query("SELECT COUNT(*) c FROM inventory")->fetch_assoc()['c'];

// Chart Data
$days=[]; $completedData=[]; $pendingData=[];
for($i=6;$i>=0;$i--){
    $date=date('Y-m-d',strtotime("-$i days"));
    $days[]=date('d M',strtotime($date));
    $completedData[]=$conn->query("SELECT COUNT(*) c FROM tasks WHERE status='Completed' AND DATE(due_date)='$date'")->fetch_assoc()['c'];
    $pendingData[]=$conn->query("SELECT COUNT(*) c FROM tasks WHERE status!='Completed' AND DATE(due_date)='$date'")->fetch_assoc()['c'];
}

$stockLabels=[]; $stockValues=[];
$res=$conn->query("SELECT item_name,quantity FROM inventory ORDER BY quantity DESC LIMIT 5");
while($r=$res->fetch_assoc()){
    $stockLabels[]=$r['item_name'];
    $stockValues[]=(float)$r['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body {background:#ecf6f1;font-family:Inter,system-ui,sans-serif;padding:20px;margin:0;}
.dashboard{max-width:1200px;margin:auto;}
.header-row{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;}
.header-row h2{margin:0;font-size:2rem;}
.stats-badge{background:#ffffff;padding:12px 16px;border-radius:18px;box-shadow:0 12px 30px rgba(0,0,0,.06);margin-top:16px;display:flex;flex-wrap:wrap;gap:12px;font-size:.95rem;color:#333;}
.table-wrapper{background:white;padding:24px;border-radius:20px;margin-top:20px;box-shadow:0 18px 40px rgba(46,76,71,.08);}
.section-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;}
.section-title{margin:0;font-size:1.25rem;color:#1f5a4f;}
.section-actions{display:flex;gap:10px;flex-wrap:wrap;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:.75rem 1rem;border-radius:999px;border:none;font-weight:600;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease;}
.btn:hover{transform:translateY(-1px);box-shadow:0 12px 24px rgba(0,0,0,.08);}
.btn-add{background:#2a6b5f;color:#fff;}
.btn-search{background:#e8f3ef;color:#2a6b5f;}
.btn-edit{background:#447c6f;color:#fff;}
.btn-delete{background:#d9534f;color:#fff;}
.search-container{display:none;margin-bottom:18px;gap:10px;align-items:center;}
.search-input{flex:1 1 260px;padding:.85rem 1rem;border:1px solid #cbd5d2;border-radius:20px;font-size:.95rem;}
table{width:100%;border-collapse:collapse;border-radius:16px;overflow:hidden;}
th,td{padding:14px 12px;text-align:left;border-bottom:1px solid #edf1f0;}
th{background:#f6fbf8;color:#2e4e47;font-weight:700;font-size:.95rem;}
tbody tr:hover{background:#f8faf9;}
.actions-col{width:180px;}
.action-btn{display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:12px;border:none;font-size:.85rem;cursor:pointer;transition:background .18s ease;}
.action-btn.edit{background:#2a6b5f;color:#fff;}
.action-btn.remove{background:#d9534f;color:#fff;}
.action-btn:hover{opacity:.92;}
.card-notice{font-size:.95rem;color:#556a62;margin:0;}
@media(max-width:900px){.actions-col{width:auto;}}
@media(max-width:680px){.header-row,.section-header{flex-direction:column;align-items:flex-start;}}
</style>
</head>

<body>
<div class="dashboard">

<h2>📊 Caretaker Dashboard</h2>

<!-- Stats -->
<p>
Pending: <?php echo $pendingTasks; ?> |
Completed: <?php echo $completedThisWeek; ?> |
Low Stock: <?php echo $lowStockCount; ?> |
Inventory: <?php echo $totalInventory; ?>
</p>

<!-- TASKS -->
<div class="table-wrapper">
<div class="section-header">
<div>
<h3 class="section-title">Tasks</h3>
<p class="card-notice">Manage assigned tasks, search by keyword, and update task details quickly.</p>
</div>
<div class="section-actions">
<a class="btn btn-add" href="create_task.php"><i class="fas fa-plus"></i> Add Schedule</a>
<button class="btn btn-search" type="button" onclick="toggleSearch('taskSearchBox')"><i class="fas fa-search"></i> Search</button>
</div>
</div>
<div class="search-container" id="taskSearchBox">
<input type="text" id="taskSearchInput" class="search-input" placeholder="Search by task, assignee, status, or notes" oninput="filterTable('tasksTable','taskSearchInput')">
</div>
<table id="tasksTable">
<tr>
<th>Task</th><th>Assigned</th><th>Date</th><th>Status</th><th>Notes</th><th class="actions-col">Actions</th>
</tr>
<tbody>
<?php foreach($tasks as $t): ?>
<tr>
<td><?php echo htmlspecialchars($t['task_name']); ?></td>
<td><?php echo htmlspecialchars($t['assigned_to']); ?></td>
<td><?php echo htmlspecialchars($t['due_date']); ?></td>
<td><?php echo htmlspecialchars($t['status']); ?></td>
<td><?php echo htmlspecialchars($t['completion_notes']); ?></td>
<td>
<a class="action-btn edit" href="editTask.html?task_id=<?php echo urlencode($t['task_id']); ?>"><i class="fas fa-pen"></i> Edit</a>
<button class="action-btn remove" type="button" onclick="confirmDelete('task', <?php echo $t['task_id']; ?>)"><i class="fas fa-trash-alt"></i> Delete</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- INVENTORY -->
<div class="table-wrapper">
<div class="section-header">
<div>
<h3 class="section-title">Inventory</h3>
<p class="card-notice">Track inventory items, reorder levels, and manage stock from one page.</p>
</div>
<div class="section-actions">
<a class="btn btn-add" href="add_inventory_item.html"><i class="fas fa-plus"></i> Add Item</a>
<button class="btn btn-search" type="button" onclick="toggleSearch('inventorySearchBox')"><i class="fas fa-search"></i> Search</button>
</div>
</div>
<div class="search-container" id="inventorySearchBox">
<input type="text" id="inventorySearchInput" class="search-input" placeholder="Search by item name, category, or supplier" oninput="filterTable('inventoryTable','inventorySearchInput')">
</div>
<table id="inventoryTable">
<tr>
<th>Item</th><th>Qty</th><th>Unit</th><th>Reorder</th><th class="actions-col">Actions</th>
</tr>
<tbody>
<?php foreach($inventoryItems as $i): ?>
<tr>
<td><?php echo htmlspecialchars($i['item_name']); ?></td>
<td><?php echo htmlspecialchars($i['quantity']); ?></td>
<td><?php echo htmlspecialchars($i['unit']); ?></td>
<td><?php echo htmlspecialchars($i['reorder_level']); ?></td>
<td>
<button class="action-btn edit" type="button" onclick="alert('Inventory edit page not yet implemented.')"><i class="fas fa-pen"></i> Edit</button>
<button class="action-btn remove" type="button" onclick="confirmDelete('inventory item', <?php echo htmlspecialchars($i['inventory_id'] ?? 0); ?>)"><i class="fas fa-trash-alt"></i> Delete</button>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Charts -->
<canvas id="tasksChart" style="margin-top:20px;max-height:320px;width:100%"></canvas>
<canvas id="stockChart" style="margin-top:20px;max-height:320px;width:100%"></canvas>

</div>

<script>
const days = <?php echo json_encode($days); ?>;
const completed = <?php echo json_encode($completedData); ?>;
const pending = <?php echo json_encode($pendingData); ?>;

new Chart(document.getElementById('tasksChart'), {
    type:'bar',
    data:{
        labels:days,
        datasets:[
            {label:'Completed',data:completed},
            {label:'Pending',data:pending}
        ]
    }
});

new Chart(document.getElementById('stockChart'), {
    type:'bar',
    data:{
        labels: <?php echo json_encode($stockLabels); ?>,
        datasets:[{label:'Stock',data: <?php echo json_encode($stockValues); ?>}]
    }
});

function toggleSearch(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.style.display = container.style.display === 'flex' ? 'none' : 'flex';
    if (container.style.display === 'flex') {
        const input = container.querySelector('input');
        if (input) input.focus();
    }
}

function filterTable(tableId, inputId) {
    const query = document.getElementById(inputId).value.toLowerCase();
    const rows = document.querySelectorAll(`#${tableId} tbody tr`);
    rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(query) ? '' : 'none';
    });
}

function confirmDelete(type, id) {
    if (!confirm(`Delete this ${type}?`)) return;
    alert('Delete functionality is not connected to a backend endpoint yet.');
}
</script>

</body>
</html>