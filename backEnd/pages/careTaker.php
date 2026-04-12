<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

// -------------------------------
// Fetch tasks
// -------------------------------
$tasksQuery = "
    SELECT 
        t.task_id,
        t.task_name,
        s.full_name AS assigned_to,
        t.due_date,
        t.status,
        t.completion_notes
    FROM tasks t
    JOIN staff s ON t.assigned_to = s.staff_id
    ORDER BY t.due_date ASC
";
$tasksResult = $conn->query($tasksQuery);
$tasks = [];
while ($row = $tasksResult->fetch_assoc()) {
    $tasks[] = $row;
}

// -------------------------------
// Fetch inventory items
// -------------------------------
$inventoryQuery = "
    SELECT 
        inventory_id,
        item_name,
        quantity,
        unit,
        reorder_level,
        supplier,
        last_updated
    FROM inventory
    ORDER BY item_name
";
$inventoryResult = $conn->query($inventoryQuery);
$inventoryItems = [];
while ($row = $inventoryResult->fetch_assoc()) {
    $inventoryItems[] = $row;
}

// -------------------------------
// Fetch feeding schedules for today (to display in inventory section)
// -------------------------------
$todayFeedingsQuery = "
    SELECT 
        f.schedule_id,
        f.feeding_time,
        f.food_type,
        f.amount_grams,
        f.is_done,
        t.name AS tortoise_name,
        s.common_name AS species
    FROM feeding_schedules f
    JOIN tortoises t ON f.tortoise_id = t.tortoise_id
    JOIN species s ON t.species_id = s.species_id
    WHERE f.scheduled_date = CURDATE()
    ORDER BY f.feeding_time
";
$todayFeedings = $conn->query($todayFeedingsQuery);

// For the combined "Feeding & inventory" table, we'll show inventory items + today's feedings
// But to match the original design, we'll create a separate section for today's feedings under inventory.
// We'll adapt the table to show both: first rows for today's feedings, then inventory items.
// Alternatively, we keep two separate tables inside the same tab. We'll follow the original layout: a single table with columns: Species/group, Dietary requirement, Last feeding, Next feeding, Food item, Stock left.
// We'll populate with today's feedings (as "next feeding") and inventory stock.

// For simplicity, we'll display today's feedings as separate rows under "Feeding schedule" and inventory items as separate rows.
// We'll restructure the HTML to have two tables: one for today's feedings, one for inventory.
// This is clearer and matches database structure.

// -------------------------------
// Compute statistics
// -------------------------------
$pendingTasks = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE status != 'Completed'")->fetch_assoc()['cnt'];
$completedThisWeek = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE status = 'Completed' AND due_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['cnt'];
$lowStockCount = $conn->query("SELECT COUNT(*) AS cnt FROM inventory WHERE quantity < reorder_level")->fetch_assoc()['cnt'];
$totalInventory = $conn->query("SELECT COUNT(*) AS cnt FROM inventory")->fetch_assoc()['cnt'];

// -------------------------------
// Data for charts
// -------------------------------
// Tasks trend last 7 days
$days = [];
$completedData = [];
$pendingData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $days[] = date('d M', strtotime($date));
    $completed = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE status = 'Completed' AND DATE(due_date) = '$date'")->fetch_assoc()['cnt'];
    $pending = $conn->query("SELECT COUNT(*) AS cnt FROM tasks WHERE status != 'Completed' AND DATE(due_date) = '$date'")->fetch_assoc()['cnt'];
    $completedData[] = $completed;
    $pendingData[] = $pending;
}

// Stock chart data (top 5 items by quantity)
$stockQuery = "SELECT item_name, quantity FROM inventory ORDER BY quantity DESC LIMIT 5";
$stockResult = $conn->query($stockQuery);
$stockLabels = [];
$stockValues = [];
while ($row = $stockResult->fetch_assoc()) {
    $stockLabels[] = $row['item_name'];
    $stockValues[] = (float)$row['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tortoise Center · Caretaker Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        /* Copy all CSS from the original caretaker.html – same styles */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #ecf6f1; display: flex; justify-content: center; padding: 2rem 1.5rem; }
        .dashboard { max-width: 1400px; width: 100%; }
        .header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .title-section h1 { font-size: 2.2rem; font-weight: 600; color: #1a6d4e; }
        .badge-role { background: #d4efe3; padding: 0.25rem 1rem; border-radius: 40px; font-size: 0.9rem; color: #1b5e44; }
        .date-info { background: white; padding: 0.6rem 1.5rem; border-radius: 40px; }
        .navbar { background: white; border-radius: 60px; padding: 0.8rem 2rem; margin-bottom: 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        .nav-links { display: flex; gap: 1.8rem; flex-wrap: wrap; }
        .nav-item { cursor: pointer; display: flex; align-items: center; gap: 0.5rem; color: #2b6e53; font-weight: 600; border-bottom: 3px solid transparent; }
        .nav-item.active { border-bottom-color: #2a8b65; }
        .logout-btn { background: #fef4ed; border: 1.5px solid #f5c9ae; color: #b45a2e; padding: 0.6rem 1.8rem; border-radius: 40px; cursor: pointer; }
        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.2rem; margin-bottom: 2.5rem; }
        .stat-card { background: white; padding: 1.2rem; border-radius: 24px; display: flex; align-items: center; gap: 0.8rem; }
        .stat-card i { font-size: 2rem; color: #368f6b; background: #e1f5ec; padding: 0.8rem; border-radius: 50%; }
        .stat-info h3 { font-size: 1.6rem; color: #1b6148; }
        .table-wrapper { background: white; border-radius: 28px; padding: 1.5rem; margin-bottom: 2.5rem; display: none; }
        .table-wrapper.active-table { display: block; }
        .table-header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .btn { background: white; border: 1.5px solid #cae5d9; padding: 0.6rem 1.3rem; border-radius: 40px; cursor: pointer; }
        .btn-add { background: #1f7356; color: white; }
        .btn-search { background: #eff8f3; color: #256e4f; }
        .btn-sm { padding: 0.3rem 0.8rem; font-size: 0.8rem; }
        .btn-edit-row { background: #eaf3fe; border-color: #b9d4fa; color: #2a6291; margin-right: 0.5rem; }
        .btn-delete-row { background: #fef2ea; border-color: #f5caae; color: #b75728; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.85rem 0.5rem; border-bottom: 1px solid #e4f3ec; text-align: left; }
        th { color: #286e53; border-bottom: 2px solid #d0ece0; }
        .badge-status { background: #dff0e9; padding: 0.2rem 0.8rem; border-radius: 50px; font-size: 0.8rem; }
        .badge-pending { background: #fef1df; color: #bc7a38; }
        .badge-complete { background: #dcf5e7; color: #1f7855; }
        .graph-container { margin-top: 2rem; padding: 1rem; background: #f9fdfb; border-radius: 20px; }
        canvas { max-height: 300px; width: 100%; }
        .search-container { margin-bottom: 1rem; display: flex; justify-content: flex-end; }
        .search-input { padding: 0.5rem 1rem; border-radius: 40px; border: 1px solid #c0ddcf; width: 250px; margin-right: 0.5rem; }
        @media (max-width: 750px) { .navbar { flex-direction: column; } }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="header">
        <div class="title-section">
            <h1><i class="fas fa-hand-holding-heart"></i> Caretaker · Tortoise Center</h1>
            <p><i class="fas fa-clinic-medical"></i> Task management & feeding inventory</p>
        </div>
        <div class="date-info">
            <i class="far fa-calendar-alt"></i> <?php echo date('d M Y'); ?> · Morning shift
            <span class="badge-role">CARETAKER</span>
        </div>
    </div>

    <div class="navbar">
        <div class="nav-links">
            <span class="nav-item active" data-table="tasks"><i class="fas fa-clipboard-list"></i> Tasks & schedules</span>
            <span class="nav-item" data-table="feeding"><i class="fas fa-utensils"></i> Feeding & inventory</span>
        </div>
        <div class="logout-btn" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</div>
    </div>

    <div class="stats-cards">
        <div class="stat-card"><i class="fas fa-tasks"></i><div class="stat-info"><h3><?php echo $pendingTasks; ?></h3><span>pending tasks</span></div></div>
        <div class="stat-card"><i class="fas fa-check-circle"></i><div class="stat-info"><h3><?php echo $completedThisWeek; ?></h3><span>completed tasks (this week)</span></div></div>
        <div class="stat-card"><i class="fas fa-apple-alt"></i><div class="stat-info"><h3><?php echo $lowStockCount; ?></h3><span>items below reorder level</span></div></div>
        <div class="stat-card"><i class="fas fa-boxes"></i><div class="stat-info"><h3><?php echo $totalInventory; ?></h3><span>inventory items</span></div></div>
    </div>

    <!-- TASKS SECTION -->
    <div class="table-wrapper active-table" id="tasks">
        <div class="table-header">
            <h2><i class="fas fa-calendar-check"></i> Staff tasks & schedule</h2>
            <div class="action-bar">
                <button class="btn btn-add" onclick="window.location.href='add_task.html'"><i class="fas fa-plus"></i> Add task</button>
                <button class="btn btn-search" id="searchTaskBtn"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
        <div class="search-container" id="taskSearchBox" style="display: none;">
            <input type="text" id="taskSearchInput" class="search-input" placeholder="Search by task, assignee...">
        </div>
        <table id="tasksTable">
            <thead>
                <tr><th>Task</th><th>Assigned to</th><th>Due date</th><th>Status</th><th>Completion notes</th><th>Actions</th></tr>
            </thead>
            <tbody id="tasksTableBody">
                <?php foreach ($tasks as $task): ?>
                <tr data-id="<?php echo $task['task_id']; ?>">
                    <td><?php echo htmlspecialchars($task['task_name']); ?></td>
                    <td><?php echo htmlspecialchars($task['assigned_to']); ?></td>
                    <td><?php echo htmlspecialchars($task['due_date']); ?></td>
                    <td>
                        <?php
                        $statusClass = 'badge-status';
                        if ($task['status'] == 'Completed') $statusClass = 'badge-complete';
                        elseif ($task['status'] == 'Pending') $statusClass = 'badge-pending';
                        ?>
                        <span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($task['status']); ?></span>
                    </td>
                    <td><?php echo htmlspecialchars($task['completion_notes']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-edit-row" data-id="<?php echo $task['task_id']; ?>" data-type="task"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-sm btn-delete-row" data-id="<?php echo $task['task_id']; ?>" data-type="task"><i class="fas fa-trash-alt"></i> Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="graph-container">
            <h3><i class="fas fa-chart-bar"></i> Tasks completed vs pending (last 7 days)</h3>
            <canvas id="tasksChart"></canvas>
        </div>
    </div>

    <!-- FEEDING & INVENTORY SECTION -->
    <div class="table-wrapper" id="feeding">
        <div class="table-header">
            <h2><i class="fas fa-apple-alt"></i> Feeding schedule & food inventory</h2>
            <div class="action-bar">
                <button class="btn btn-add" onclick="window.location.href='add_inventory_item.html'"><i class="fas fa-plus"></i> Add item</button>
                <button class="btn btn-search" id="searchInventoryBtn"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
        <div class="search-container" id="inventorySearchBox" style="display: none;">
            <input type="text" id="inventorySearchInput" class="search-input" placeholder="Search by food item...">
        </div>
        <table id="inventoryTable">
            <thead>
                <tr><th>Item name</th><th>Quantity</th><th>Unit</th><th>Reorder level</th><th>Supplier</th><th>Last updated</th><th>Actions</th></tr>
            </thead>
            <tbody id="inventoryTableBody">
                <?php foreach ($inventoryItems as $item): ?>
                <tr data-id="<?php echo $item['inventory_id']; ?>">
                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                    <td><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                    <td><?php echo $item['reorder_level']; ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                    <td><?php echo htmlspecialchars($item['supplier']); ?></td>
                    <td><?php echo htmlspecialchars($item['last_updated']); ?></td>
                    <td>
                        <button class="btn btn-sm btn-edit-row" data-id="<?php echo $item['inventory_id']; ?>" data-type="inventory"><i class="fas fa-edit"></i> Edit</button>
                        <button class="btn btn-sm btn-delete-row" data-id="<?php echo $item['inventory_id']; ?>" data-type="inventory"><i class="fas fa-trash-alt"></i> Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="graph-container">
            <h3><i class="fas fa-chart-line"></i> Current stock levels (kg) – top food items</h3>
            <canvas id="stockChart"></canvas>
        </div>
    </div>
</div>

<script>
// Data passed from PHP
const days = <?php echo json_encode($days); ?>;
const completedData = <?php echo json_encode($completedData); ?>;
const pendingData = <?php echo json_encode($pendingData); ?>;
const stockLabels = <?php echo json_encode($stockLabels); ?>;
const stockValues = <?php echo json_encode($stockValues); ?>;

let tasksChart, stockChart;

function initCharts() {
    const ctxTasks = document.getElementById('tasksChart').getContext('2d');
    tasksChart = new Chart(ctxTasks, {
        type: 'bar',
        data: {
            labels: days,
            datasets: [
                { label: 'Completed', data: completedData, backgroundColor: '#1f7356' },
                { label: 'Pending', data: pendingData, backgroundColor: '#f3bc9a' }
            ]
        },
        options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true, title: { display: true, text: 'Number of tasks' } } } }
    });

    const ctxStock = document.getElementById('stockChart').getContext('2d');
    stockChart = new Chart(ctxStock, {
        type: 'bar',
        data: {
            labels: stockLabels,
            datasets: [{ label: 'Stock (kg / units)', data: stockValues, backgroundColor: '#368f6b' }]
        },
        options: { responsive: true, maintainAspectRatio: true, scales: { y: { beginAtZero: true, title: { display: true, text: 'Quantity' } } } }
    });
}

// Navigation
function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = {
        tasks: document.getElementById('tasks'),
        feeding: document.getElementById('feeding')
    };
    function showSection(sectionId, el) {
        Object.values(sections).forEach(s => s.classList.remove('active-table'));
        navItems.forEach(i => i.classList.remove('active'));
        sections[sectionId].classList.add('active-table');
        el.classList.add('active');
        setTimeout(() => { if (tasksChart) tasksChart.resize(); if (stockChart) stockChart.resize(); }, 100);
    }
    navItems.forEach(item => {
        item.addEventListener('click', () => showSection(item.dataset.table, item));
    });
}

// Search filters
function setupSearch() {
    const searchTaskBtn = document.getElementById('searchTaskBtn');
    const searchInvBtn = document.getElementById('searchInventoryBtn');
    const taskSearchBox = document.getElementById('taskSearchBox');
    const invSearchBox = document.getElementById('inventorySearchBox');
    const taskInput = document.getElementById('taskSearchInput');
    const invInput = document.getElementById('inventorySearchInput');

    searchTaskBtn.addEventListener('click', () => {
        taskSearchBox.style.display = taskSearchBox.style.display === 'none' ? 'flex' : 'none';
        if (taskSearchBox.style.display === 'flex') taskInput.focus();
    });
    searchInvBtn.addEventListener('click', () => {
        invSearchBox.style.display = invSearchBox.style.display === 'none' ? 'flex' : 'none';
        if (invSearchBox.style.display === 'flex') invInput.focus();
    });

    taskInput.addEventListener('input', () => {
        const term = taskInput.value.toLowerCase();
        document.querySelectorAll('#tasksTableBody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });
    invInput.addEventListener('input', () => {
        const term = invInput.value.toLowerCase();
        document.querySelectorAll('#inventoryTableBody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
        });
    });
}

// Handle Edit/Delete via redirect or AJAX
function attachRowButtons() {
    // Edit buttons
    document.querySelectorAll('.btn-edit-row').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const type = btn.dataset.type;
            if (type === 'task') {
                window.location.href = `edit_task.html?id=${id}`;
            } else if (type === 'inventory') {
                window.location.href = `edit_inventory_item.html?id=${id}`;
            }
        });
    });

    // Delete buttons (AJAX)
    document.querySelectorAll('.btn-delete-row').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this record permanently?')) return;
            const id = btn.dataset.id;
            const type = btn.dataset.type;
            let url = '';
            let body = '';
            if (type === 'task') {
                url = '../process/delete_task.php';
                body = `task_id=${id}`;
            } else if (type === 'inventory') {
                url = '../process/delete_inventory_item.php';
                body = `inventory_id=${id}`;
            }
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                });
                if (res.ok) {
                    location.reload();
                } else {
                    alert('Delete failed');
                }
            } catch(err) {
                alert('Error: ' + err.message);
            }
        });
    });
}

window.addEventListener('DOMContentLoaded', () => {
    initCharts();
    initNavigation();
    setupSearch();
    attachRowButtons();
});
</script>
</body>
</html>