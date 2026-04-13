<?php
require_once '../backEnd/includes/session.php';
//requireLogin();
require_once '../backEnd/config/db.php';

$staffSchedule = $conn->query("SELECT t.task_id, s.full_name, t.task_name, t.due_date, t.status, t.completion_notes FROM tasks t JOIN staff s ON t.assigned_to = s.staff_id ORDER BY t.due_date");
$inventory = $conn->query("SELECT inventory_id, item_name, quantity, supplier, last_updated FROM inventory");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Supervisor Dashboard | TCCMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --bg: #f5f1ed;
      --surface: #ffffff;
      --surface-soft: #f7efe9;
      --text: #382f2b;
      --muted: #7d6b64;
      --primary: #6a3a2d;
      --primary-soft: #f1d8d1;
      --accent: #2a6b5f;
      --danger: #c94f3f;
      --border: rgba(90, 69, 61, 0.14);
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      background: linear-gradient(180deg, #f7f2ef 0%, #ede5df 100%);
      font-family: 'Inter', sans-serif;
      color: var(--text);
    }

    body a { color: inherit; }

    .page-header {
      background: linear-gradient(135deg, #2a1a1a 0%, #6a3a2d 100%);
      color: white;
      padding: 32px 36px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 24px;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .page-header h1 {
      margin: 0;
      font-family: 'Playfair Display', serif;
      font-size: 32px;
      letter-spacing: 0.02em;
    }

    .page-header p {
      margin: 8px 0 0;
      max-width: 560px;
      font-size: 15px;
      line-height: 1.8;
      color: rgba(255,255,255,0.86);
    }

    .container {
      width: min(1180px, calc(100% - 40px));
      margin: 0 auto;
      padding: 28px 0 48px;
    }

    .tab-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 20px;
    }

    .tab-button,
    .logout-btn {
      border: none;
      border-radius: 16px;
      padding: 12px 22px;
      cursor: pointer;
      font-weight: 600;
      transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .tab-button {
      background: linear-gradient(135deg, #2a1a1a 0%, #6a3a2d 100%);
      color: white;
      box-shadow: 0 10px 24px rgba(0,0,0,0.12);
    }

    .tab-button.active {
      background: #7a4a3d;
      box-shadow: 0 12px 28px rgba(0,0,0,0.14);
    }

    .logout-btn {
      margin-left: auto;
      background: rgba(255,255,255,0.08);
      color: white;
      border: 1px solid rgba(255,255,255,0.22);
      display: inline-flex;
      align-items: center;
      gap: 10px;
    }

    .tab-button:hover,
    .logout-btn:hover {
      transform: translateY(-1px);
    }

    .panel {
      display: none;
      gap: 24px;
    }

    .panel.active {
      display: block;
    }

    .panel-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 24px;
      box-shadow: 0 24px 60px rgba(69, 52, 46, 0.08);
      padding: 28px;
    }

    .card-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
    }

    .card-title h2 {
      margin: 0;
      font-size: 22px;
      letter-spacing: -0.02em;
    }

    .action-toolbar {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
    }

    .search-section {
      display: flex;
      gap: 12px;
      flex: 1;
      min-width: 240px;
    }

    .search-input {
      flex: 1;
      border: 1px solid rgba(106, 58, 45, 0.16);
      border-radius: 18px;
      background: #faf3ef;
      padding: 14px 18px;
      font-size: 15px;
      outline: none;
      transition: border-color 0.2s ease;
    }

    .search-input:focus {
      border-color: rgba(106, 58, 45, 0.4);
    }

    .search-btn,
    .action-btn {
      border-radius: 16px;
      font-weight: 600;
      letter-spacing: 0.01em;
      transition: transform 0.2s ease, background 0.2s ease;
    }

    .search-btn,
    .action-btn.add {
      background: var(--accent);
      color: white;
      border: 1px solid transparent;
      padding: 12px 20px;
      min-width: 128px;
    }

    .action-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 12px;
      align-items: center;
    }

    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      padding: 12px 18px;
      border: 1px solid rgba(106, 58, 45, 0.1);
      background: #f7efe9;
      color: var(--text);
      min-width: 110px;
    }

    .action-btn.edit {
      background: #eef5fb;
      border-color: #b9d4ea;
      color: #234f76;
    }

    .action-btn.remove {
      background: #fff2ef;
      border-color: #e9c4be;
      color: #a03f35;
    }

    .action-btn:hover,
    .search-btn:hover {
      transform: translateY(-1px);
    }

    .table-wrap {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0 12px;
      min-width: 740px;
    }

    thead th {
      text-align: left;
      font-size: 13px;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      color: #6f5c54;
      padding: 12px 18px 10px;
    }

    tbody tr {
      background: rgba(255,255,255,0.95);
      border: 1px solid rgba(106, 58, 45, 0.08);
      border-radius: 18px;
      box-shadow: 0 12px 26px rgba(92, 73, 65, 0.06);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    tbody tr:hover {
      transform: translateY(-1px);
      box-shadow: 0 16px 34px rgba(92, 73, 65, 0.1);
    }

    tbody td {
      padding: 18px;
      vertical-align: middle;
      color: var(--text);
    }

    tbody td:first-child {
      border-top-left-radius: 18px;
      border-bottom-left-radius: 18px;
    }

    tbody td:last-child {
      border-top-right-radius: 18px;
      border-bottom-right-radius: 18px;
    }

    .status-btn {
      border: none;
      border-radius: 999px;
      padding: 10px 14px;
      min-width: 96px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }

    .status-btn.done {
      background: #d8f1dd;
      color: #2d6a3f;
    }

    .status-btn.in-progress {
      background: #fff3d9;
      color: #b86f26;
    }

    .status-btn.pending {
      background: #fde7e4;
      color: #c94f3f;
    }

    @media (max-width: 980px) {
      .table-wrap { min-width: unset; }
      table { min-width: 100%; }
    }

    @media (max-width: 760px) {
      .page-header { flex-direction: column; align-items: flex-start; }
      .tab-buttons { justify-content: center; }
      .action-toolbar { flex-direction: column; }
      .logout-btn { width: 100%; justify-content: center; }
      .search-section { flex-direction: column; }
      .search-btn, .action-btn.add { width: 100%; }
    }
  </style>
</head>
<body>
  <header class="page-header">
    <div>
      <h1>Supervisor Dashboard</h1>
      <p>Manage assignments, monitor status, and keep the team aligned in a clean, professional workspace.</p>
    </div>
    <button class="logout-btn" onclick="logout()">
      <i class="fas fa-sign-out-alt"></i>
      Logout
    </button>
  </header>

  <main class="container">
    <div class="tab-buttons">
<<<<<<< HEAD
      <button class="tab-button active" onclick="showTab('staff-schedule', event)">Staff Schedule</button>
      <button class="tab-button" onclick="showTab('inventory', event)">Inventory</button>
    </div>

    <section id="staff-schedule" class="panel active">
      <div class="panel-card">
        <div class="card-title">
          <h2>Staff Schedule</h2>
          <div class="action-buttons">
            <button class="action-btn add" onclick="window.location.href='add_task.html'"><i class="fas fa-plus"></i> New Task</button>
          </div>
        </div>

        <div class="action-toolbar">
          <div class="search-section">
            <input type="text" class="search-input" id="staff-search" placeholder="Search by caretaker, task, or time" oninput="filterStaffTable()">
            <button class="search-btn"><i class="fas fa-search"></i> Search</button>
          </div>
        </div>

        <div class="table-wrap">
          <table id="staff-table">
            <thead>
              <tr>
                <th>Caretaker</th>
                <th>Task</th>
                <th>Time</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php while($row = $staffSchedule->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                <td><?php echo htmlspecialchars($row['task_name']); ?></td>
                <td><?php echo htmlspecialchars($row['due_date']); ?></td>
                <td><button class="status-btn <?php echo strtolower(str_replace(' ', '-', $row['status'])); ?>" onclick="changeStatus(this)"><?php echo htmlspecialchars($row['status']); ?></button></td>
                <td>
                  <a href="editTask.php?id=<?= $row['task_id'] ?>" class="action-btn edit">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <button class="action-btn remove" onclick="if(confirm('Delete this task?')) window.location.href='delete_task.php?id=<?= $row['task_id'] ?>'"><i class="fas fa-trash"></i> Remove</button>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <section id="inventory" class="panel">
      <div class="panel-card">
        <div class="card-title">
          <h2>Inventory</h2>
          <div class="action-buttons">
            <button class="action-btn add" onclick="window.location.href='add_inventory_item.html'"><i class="fas fa-plus"></i> Add Item</button>
          </div>
        </div>

        <div class="action-toolbar">
          <div class="search-section">
            <input type="text" class="search-input" id="inventory-search" placeholder="Search inventory items" oninput="filterInventoryTable()">
            <button class="search-btn"><i class="fas fa-search"></i> Search</button>
          </div>
        </div>

        <div class="table-wrap">
          <table id="inventory-table">
            <thead>
              <tr>
                <th>Item Name</th>
                <th>Quantity</th>
                <th>Supplier</th>
                <th>Last Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php while($row = $inventory->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td><?php echo htmlspecialchars($row['supplier']); ?></td>
                <td><?php echo htmlspecialchars($row['last_updated']); ?></td>
                <td>
                  <a href="edit_inventory_item.html?id=<?= $row['inventory_id'] ?>" class="action-btn edit">
                    <i class="fas fa-edit"></i> Edit
                  </a>
                  <button class="action-btn remove" onclick="if(confirm('Delete this item?')) window.location.href='delete_inventory_item.php?id=<?= $row['inventory_id'] ?>'"><i class="fas fa-trash"></i> Remove</button>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

  <script>
    function showTab(tabId, event) {
      const panels = document.querySelectorAll('.panel');
      panels.forEach(panel => panel.classList.remove('active'));
      document.getElementById(tabId).classList.add('active');

      const buttons = document.querySelectorAll('.tab-button');
      buttons.forEach(button => button.classList.remove('active'));
      if (event && event.currentTarget) {
        event.currentTarget.classList.add('active');
      }
    }

    function changeStatus(button) {
      const states = ['Done', 'In Progress', 'Pending'];
      const classes = ['done', 'in-progress', 'pending'];
      const current = button.textContent.trim();
      const index = states.indexOf(current);
      const next = (index + 1) % states.length;
      button.textContent = states[next];
      button.className = 'status-btn ' + classes[next];
    }

    function filterStaffTable() {
      const query = document.getElementById('staff-search').value.toLowerCase().trim();
      document.querySelectorAll('#staff-table tbody tr').forEach(row => {
        const text = Array.from(row.querySelectorAll('td')).map(td => td.textContent.toLowerCase()).join(' ');
        row.style.display = text.includes(query) ? '' : 'none';
      });
    }

    function filterInventoryTable() {
      const query = document.getElementById('inventory-search').value.toLowerCase().trim();
      document.querySelectorAll('#inventory-table tbody tr').forEach(row => {
        const text = Array.from(row.querySelectorAll('td')).map(td => td.textContent.toLowerCase()).join(' ');
        row.style.display = text.includes(query) ? '' : 'none';
      });
    }

    function logout() {
      // Add logout logic here, e.g., redirect to login page
      alert('Logging out...');
      window.location.href = 'logout.php'; // Assuming logout.php exists
    }
  </script>
=======
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
>>>>>>> 180fd74d3d9aac65544801ea2fb3434259923349
</body>
</html>
