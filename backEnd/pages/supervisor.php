<?php
// ==========================================
// File: config.php
// Database configuration
// ==========================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$host = 'localhost';
$dbname = 'supervisor_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// ==========================================
// File: api.php
// Main API endpoint for all operations
// ==========================================

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        // ---------- TASKS (Staff Schedule) ----------
        case 'get_tasks':
            $tortoise_id = $_GET['tortoise_id'] ?? '';
            if ($tortoise_id) {
                $stmt = $pdo->prepare("SELECT * FROM tasks WHERE tortoise_id LIKE ? ORDER BY id");
                $stmt->execute(["%$tortoise_id%"]);
            } else {
                $stmt = $pdo->query("SELECT * FROM tasks ORDER BY id");
            }
            echo json_encode($stmt->fetchAll());
            break;

        case 'add_task':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO tasks (tortoise_id, caretaker, task, time, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$data['tortoise_id'], $data['caretaker'], $data['task'], $data['time'], $data['status']]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'update_task':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE tasks SET tortoise_id=?, caretaker=?, task=?, time=?, status=? WHERE id=?");
            $stmt->execute([$data['tortoise_id'], $data['caretaker'], $data['task'], $data['time'], $data['status'], $data['id']]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_task':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id=?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true]);
            break;

        case 'update_task_status':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE tasks SET status=? WHERE id=?");
            $stmt->execute([$data['status'], $data['id']]);
            echo json_encode(['success' => true]);
            break;

        // ---------- INVENTORY ----------
        case 'get_inventory':
            $search = $_GET['search'] ?? '';
            if ($search) {
                $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_name LIKE ? ORDER BY id");
                $stmt->execute(["%$search%"]);
            } else {
                $stmt = $pdo->query("SELECT * FROM inventory ORDER BY id");
            }
            echo json_encode($stmt->fetchAll());
            break;

        case 'add_inventory':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("INSERT INTO inventory (item_name, quantity, supplier, last_updated) VALUES (?, ?, ?, ?)");
            $stmt->execute([$data['item_name'], $data['quantity'], $data['supplier'], $data['last_updated']]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;

        case 'update_inventory':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("UPDATE inventory SET item_name=?, quantity=?, supplier=?, last_updated=? WHERE id=?");
            $stmt->execute([$data['item_name'], $data['quantity'], $data['supplier'], $data['last_updated'], $data['id']]);
            echo json_encode(['success' => true]);
            break;

        case 'delete_inventory':
            $data = json_decode(file_get_contents('php://input'), true);
            $stmt = $pdo->prepare("DELETE FROM inventory WHERE id=?");
            $stmt->execute([$data['id']]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// ==========================================
// File: database.sql
// Run this to set up your database
// ==========================================
/*
CREATE DATABASE IF NOT EXISTS supervisor_db;
USE supervisor_db;

-- Tasks table for staff schedule
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tortoise_id VARCHAR(50) NOT NULL,
    caretaker VARCHAR(100) NOT NULL,
    task VARCHAR(255) NOT NULL,
    time VARCHAR(50) NOT NULL,
    status ENUM('Done', 'In Progress', 'Pending') DEFAULT 'Pending'
);

-- Inventory table
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    supplier VARCHAR(255),
    last_updated DATE
);

-- Sample data
INSERT INTO tasks (tortoise_id, caretaker, task, time, status) VALUES
('TRT-2025-007', 'Sakira', 'Temp & humidity Test', '07:00 AM', 'Done'),
('TRT-2025-008', 'Samanta', 'Water Quality Log', '08:00 AM', 'Done'),
('TRT-2025-009', 'Salman', 'Substrate Cleaning', '09:00 AM', 'In Progress'),
('TRT-2025-010', 'Muzahid', 'Substrate Cleaning', '02:00 AM', 'Pending'),
('TRT-2025-011', 'Nahin', 'Evening Report', '03:00 PM', 'Pending'),
('TRT-2025-012', 'Nabiha', 'Afternoon Monitoring', '09:30 AM', 'In Progress');

INSERT INTO inventory (item_name, quantity, supplier, last_updated) VALUES
('Water Quality Kit', 5, 'Lab Supplies Inc.', '2026-03-15'),
('Temperature Sensor', 3, 'Tech Equip Ltd.', '2026-03-10'),
('Cleaning Supplies', 20, 'General Store', '2026-03-12'),
('Substrate Material', 10, 'Aquarium World', '2026-03-08');
*/
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supervisor Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #f9f5f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .page-header {
            background: linear-gradient(135deg, #2a1a1a 0%, #6a3a2d 100%);
            color: white;
            padding: 36px 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .page-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 30px;
            margin-bottom: 6px;
        }
        .page-header p {
            opacity: 0.8;
            font-size: 15px;
            font-weight: 300;
        }
        .tab-container {
            margin: 20px;
        }
        .tab-buttons {
            display: flex;
            border-bottom: 1px solid #ccc;
            gap: 10px;
            align-items: center;
        }
        .tab-button {
            padding: 10px 20px;
            background: linear-gradient(135deg, #2a1a1a 0%, #6a3a2d 100%);
            color: white;
            border: none;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: bold;
        }
        .tab-button.active {
            background: linear-gradient(135deg, #3a2a2a 0%, #7a4a3d 100%);
            border-bottom: 2px solid #fff;
        }
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #2a1a1a;
            color: #ffffff;
            border: 2px solid #6a3a2d;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: auto;
        }
        .logout-btn:hover {
            background-color: rgba(255, 204, 128, 0.2);
            border-color: #ffb366;
        }
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        .tab-content.active {
            display: block;
        }
        .action-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-section {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .search-input {
            padding: 12px 16px;
            background-color: #f5f5f5;
            border: 2px solid #a8d5ba;
            border-radius: 16px;
            font-size: 16px;
            outline: none;
            min-width: 250px;
        }
        .search-btn {
            padding: 12px 24px;
            background-color: #008080;
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        .action-btn {
            background: white;
            border: 1.5px solid #cae5d9;
            color: #2f735a;
            font-weight: 600;
            padding: 0.6rem 1.3rem;
            border-radius: 40px;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        .action-btn.add {
            background: #1f7356;
            border-color: #1f7356;
            color: white;
        }
        .action-btn.remove {
            background: #fef2ea;
            border-color: #f5caae;
            color: #b75728;
        }
        .action-btn.edit {
            background: #eaf3fe;
            border-color: #b9d4fa;
            color: #2a6291;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        th, td {
            text-align: left;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        tr:nth-child(even) {
            background-color: #faf6f0;
        }
        th {
            background-color: #68382d;
            color: rgb(232, 207, 207);
        }
        .status-btn {
            padding: 5px 12px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        .status-btn.done { background-color: #4CAF50; color: white; }
        .status-btn.in-progress { background-color: #FF9800; color: white; }
        .status-btn.pending { background-color: #F44336; color: white; }
        .selected-row {
            background-color: #ffe0b5 !important;
        }
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .modal-content h3 {
            margin-bottom: 20px;
            color: #2a1a1a;
        }
        .modal-content input, .modal-content select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-buttons button {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .save-btn { background: #1f7356; color: white; }
        .cancel-btn { background: #ccc; }
        @media (max-width: 768px) {
            .action-toolbar { flex-direction: column; align-items: stretch; }
            .search-section { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
<header class="page-header">
    <div>
        <h1>Supervisor Dashboard</h1>
        <p>Manage your team and tasks efficiently</p>
    </div>
</header>

<div class="tab-container">
    <div class="tab-buttons">
        <button class="tab-button active" data-tab="staff-schedule">Staff Schedule</button>
        <button class="tab-button" data-tab="inventory">Inventory</button>
        <button class="logout-btn" onclick="logout()">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16,17 21,12 16,7"/>
                <line x1="21" x2="9" y1="12" y2="12"/>
            </svg>
            Logout
        </button>
    </div>

    <!-- Staff Schedule Tab -->
    <div id="staff-schedule" class="tab-content active">
        <div class="action-toolbar">
            <div class="search-section">
                <input type="text" id="taskSearchInput" class="search-input" placeholder="Tortoise ID (e.g. TRT-2025-007)">
                <button class="search-btn" onclick="searchTasks()">Search</button>
            </div>
            <div class="action-buttons">
                <button class="action-btn edit" onclick="openEditModal()"><i class="fas fa-edit"></i> Edit</button>
                <button class="action-btn remove" onclick="deleteSelectedItem()"><i class="fas fa-trash"></i> Remove</button>
                <button class="action-btn add" onclick="openAddModal()"><i class="fas fa-plus"></i> Add</button>
            </div>
        </div>
        <table id="tasksTable">
            <thead>
                <tr><th>Caretaker</th><th>Task</th><th>Time</th><th>Status</th></tr>
            </thead>
            <tbody id="tasksTableBody"></tbody>
        </table>
    </div>

    <!-- Inventory Tab -->
    <div id="inventory" class="tab-content">
        <div class="action-toolbar">
            <div class="search-section">
                <input type="text" id="inventorySearchInput" class="search-input" placeholder="Search by item name">
                <button class="search-btn" onclick="searchInventory()">Search</button>
            </div>
            <div class="action-buttons">
                <button class="action-btn edit" onclick="openEditModal()"><i class="fas fa-edit"></i> Edit</button>
                <button class="action-btn remove" onclick="deleteSelectedItem()"><i class="fas fa-trash"></i> Remove</button>
                <button class="action-btn add" onclick="openAddModal()"><i class="fas fa-plus"></i> Add</button>
            </div>
        </div>
        <table id="inventoryTable">
            <thead><tr><th>Item Name</th><th>Quantity</th><th>Supplier</th><th>Last Updated</th></tr></thead>
            <tbody id="inventoryTableBody"></tbody>
        </table>
    </div>
</div>

<!-- Modal for Add/Edit -->
<div id="itemModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle">Add Item</h3>
        <div id="modalFields"></div>
        <div class="modal-buttons">
            <button class="cancel-btn" onclick="closeModal()">Cancel</button>
            <button class="save-btn" onclick="saveItem()">Save</button>
        </div>
    </div>
</div>

<script>
    let currentTab = 'staff-schedule';
    let selectedRowId = null;
    let currentData = []; // store current displayed data for reference

    // Helper: Show tab
    function showTab(tabId) {
        currentTab = tabId;
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
        document.querySelector(`.tab-button[data-tab="${tabId}"]`).classList.add('active');
        if (tabId === 'staff-schedule') loadTasks();
        else loadInventory();
        selectedRowId = null;
    }

    // Load Tasks
    async function loadTasks(searchTerm = '') {
        let url = 'api.php?action=get_tasks';
        if (searchTerm) url += `&tortoise_id=${encodeURIComponent(searchTerm)}`;
        const res = await fetch(url);
        const tasks = await res.json();
        currentData = tasks;
        const tbody = document.getElementById('tasksTableBody');
        tbody.innerHTML = '';
        tasks.forEach(task => {
            const row = tbody.insertRow();
            row.setAttribute('data-id', task.id);
            row.setAttribute('data-type', 'task');
            row.onclick = (e) => { if(e.target.tagName!=='BUTTON') selectRow(row, task.id); };
            row.insertCell(0).textContent = task.caretaker;
            row.insertCell(1).textContent = task.task;
            row.insertCell(2).textContent = task.time;
            const statusCell = row.insertCell(3);
            const btn = document.createElement('button');
            btn.textContent = task.status;
            btn.className = `status-btn ${getStatusClass(task.status)}`;
            btn.onclick = (e) => { e.stopPropagation(); updateTaskStatus(task.id, task.status); };
            statusCell.appendChild(btn);
        });
    }

    // Load Inventory
    async function loadInventory(searchTerm = '') {
        let url = 'api.php?action=get_inventory';
        if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
        const res = await fetch(url);
        const items = await res.json();
        currentData = items;
        const tbody = document.getElementById('inventoryTableBody');
        tbody.innerHTML = '';
        items.forEach(item => {
            const row = tbody.insertRow();
            row.setAttribute('data-id', item.id);
            row.setAttribute('data-type', 'inventory');
            row.onclick = () => selectRow(row, item.id);
            row.insertCell(0).textContent = item.item_name;
            row.insertCell(1).textContent = item.quantity;
            row.insertCell(2).textContent = item.supplier || '-';
            row.insertCell(3).textContent = item.last_updated;
        });
    }

    function getStatusClass(status) {
        if (status === 'Done') return 'done';
        if (status === 'In Progress') return 'in-progress';
        return 'pending';
    }

    async function updateTaskStatus(id, currentStatus) {
        const statuses = ['Done', 'In Progress', 'Pending'];
        const nextStatus = statuses[(statuses.indexOf(currentStatus) + 1) % statuses.length];
        const res = await fetch('api.php?action=update_task_status', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id, status: nextStatus })
        });
        if (res.ok) loadTasks(document.getElementById('taskSearchInput').value);
    }

    function selectRow(row, id) {
        document.querySelectorAll('.selected-row').forEach(r => r.classList.remove('selected-row'));
        row.classList.add('selected-row');
        selectedRowId = id;
    }

    function getSelectedItemData() {
        if (!selectedRowId) return null;
        return currentData.find(item => item.id == selectedRowId);
    }

    function openAddModal() {
        const modal = document.getElementById('itemModal');
        document.getElementById('modalTitle').innerText = `Add New ${currentTab === 'staff-schedule' ? 'Task' : 'Inventory Item'}`;
        const fieldsDiv = document.getElementById('modalFields');
        if (currentTab === 'staff-schedule') {
            fieldsDiv.innerHTML = `
                <input type="text" id="tortoise_id" placeholder="Tortoise ID (e.g. TRT-2025-007)" required>
                <input type="text" id="caretaker" placeholder="Caretaker" required>
                <input type="text" id="task" placeholder="Task" required>
                <input type="text" id="time" placeholder="Time (e.g. 09:00 AM)" required>
                <select id="status"><option>Pending</option><option>In Progress</option><option>Done</option></select>
            `;
        } else {
            fieldsDiv.innerHTML = `
                <input type="text" id="item_name" placeholder="Item Name" required>
                <input type="number" id="quantity" placeholder="Quantity" required>
                <input type="text" id="supplier" placeholder="Supplier">
                <input type="date" id="last_updated" placeholder="Last Updated" required>
            `;
        }
        modal.style.display = 'flex';
        window.editMode = false;
    }

    function openEditModal() {
        if (!selectedRowId) { alert('Please select a row first.'); return; }
        const item = getSelectedItemData();
        if (!item) return;
        const modal = document.getElementById('itemModal');
        document.getElementById('modalTitle').innerText = `Edit ${currentTab === 'staff-schedule' ? 'Task' : 'Inventory Item'}`;
        const fieldsDiv = document.getElementById('modalFields');
        if (currentTab === 'staff-schedule') {
            fieldsDiv.innerHTML = `
                <input type="text" id="tortoise_id" value="${escapeHtml(item.tortoise_id)}" required>
                <input type="text" id="caretaker" value="${escapeHtml(item.caretaker)}" required>
                <input type="text" id="task" value="${escapeHtml(item.task)}" required>
                <input type="text" id="time" value="${escapeHtml(item.time)}" required>
                <select id="status">${getStatusOptions(item.status)}</select>
            `;
        } else {
            fieldsDiv.innerHTML = `
                <input type="text" id="item_name" value="${escapeHtml(item.item_name)}" required>
                <input type="number" id="quantity" value="${item.quantity}" required>
                <input type="text" id="supplier" value="${escapeHtml(item.supplier || '')}">
                <input type="date" id="last_updated" value="${item.last_updated}" required>
            `;
        }
        modal.style.display = 'flex';
        window.editMode = true;
    }

    function getStatusOptions(selected) {
        const opts = ['Pending', 'In Progress', 'Done'];
        return opts.map(s => `<option ${s === selected ? 'selected' : ''}>${s}</option>`).join('');
    }

    function escapeHtml(str) { if(!str) return ''; return str.replace(/[&<>]/g, function(m){if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }

    async function saveItem() {
        const modalFields = document.getElementById('modalFields');
        if (currentTab === 'staff-schedule') {
            const tortoise_id = modalFields.querySelector('#tortoise_id').value;
            const caretaker = modalFields.querySelector('#caretaker').value;
            const task = modalFields.querySelector('#task').value;
            const time = modalFields.querySelector('#time').value;
            const status = modalFields.querySelector('#status').value;
            if (!tortoise_id || !caretaker || !task || !time) { alert('Please fill all fields'); return; }
            const payload = { tortoise_id, caretaker, task, time, status };
            let url = 'api.php?action=add_task';
            if (window.editMode && selectedRowId) { url = 'api.php?action=update_task'; payload.id = selectedRowId; }
            const res = await fetch(url, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            if (res.ok) { closeModal(); loadTasks(document.getElementById('taskSearchInput').value); }
            else alert('Error saving task');
        } else {
            const item_name = modalFields.querySelector('#item_name').value;
            const quantity = modalFields.querySelector('#quantity').value;
            const supplier = modalFields.querySelector('#supplier').value;
            const last_updated = modalFields.querySelector('#last_updated').value;
            if (!item_name || !quantity || !last_updated) { alert('Please fill required fields'); return; }
            const payload = { item_name, quantity, supplier, last_updated };
            let url = 'api.php?action=add_inventory';
            if (window.editMode && selectedRowId) { url = 'api.php?action=update_inventory'; payload.id = selectedRowId; }
            const res = await fetch(url, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(payload) });
            if (res.ok) { closeModal(); loadInventory(document.getElementById('inventorySearchInput').value); }
            else alert('Error saving inventory');
        }
    }

    async function deleteSelectedItem() {
        if (!selectedRowId) { alert('Please select a row first.'); return; }
        if (!confirm('Are you sure you want to delete this item?')) return;
        const action = currentTab === 'staff-schedule' ? 'delete_task' : 'delete_inventory';
        const res = await fetch(`api.php?action=${action}`, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ id: selectedRowId }) });
        if (res.ok) {
            selectedRowId = null;
            if (currentTab === 'staff-schedule') loadTasks(document.getElementById('taskSearchInput').value);
            else loadInventory(document.getElementById('inventorySearchInput').value);
        } else alert('Delete failed');
    }

    function searchTasks() { loadTasks(document.getElementById('taskSearchInput').value); }
    function searchInventory() { loadInventory(document.getElementById('inventorySearchInput').value); }
    function closeModal() { document.getElementById('itemModal').style.display = 'none'; }
    function logout() { alert('Logging out...'); window.location.href = 'login.html'; }

    // Event listeners for tabs
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.addEventListener('click', () => showTab(btn.getAttribute('data-tab')));
    });

    // Initial load
    loadTasks();
    window.onclick = function(e) { if(e.target === document.getElementById('itemModal')) closeModal(); };
</script>
</body>
</html>