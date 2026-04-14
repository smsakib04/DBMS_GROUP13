<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------- Feeding Schedule ----------
    if ($action === 'add_feeding') {
        $tortoise_id = intval($_POST['tortoise_id']);
        $feeding_time = $_POST['feeding_time'];
        $food_type = $_POST['food_type'];
        $amount_grams = floatval($_POST['amount_grams']);
        $scheduled_date = $_POST['scheduled_date'];
        $feeder_id = !empty($_POST['feeder_id']) ? intval($_POST['feeder_id']) : null;
        $notes = trim($_POST['notes'] ?? '');

        $stmt = $conn->prepare("INSERT INTO feeding_schedules (tortoise_id, feeding_time, food_type, amount_grams, scheduled_date, feeder_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdiss", $tortoise_id, $feeding_time, $food_type, $amount_grams, $scheduled_date, $feeder_id, $notes);
        if ($stmt->execute()) $message = "Feeding added.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }
    elseif ($action === 'edit_feeding') {
        $schedule_id = intval($_POST['schedule_id']);
        $feeding_time = $_POST['feeding_time'];
        $food_type = $_POST['food_type'];
        $amount_grams = floatval($_POST['amount_grams']);
        $notes = trim($_POST['notes'] ?? '');
        $stmt = $conn->prepare("UPDATE feeding_schedules SET feeding_time=?, food_type=?, amount_grams=?, notes=? WHERE schedule_id=?");
        $stmt->bind_param("ssdsi", $feeding_time, $food_type, $amount_grams, $notes, $schedule_id);
        if ($stmt->execute()) $message = "Feeding updated.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }
    elseif ($action === 'delete_feeding') {
        $schedule_id = intval($_POST['schedule_id']);
        $stmt = $conn->prepare("DELETE FROM feeding_schedules WHERE schedule_id = ?");
        $stmt->bind_param("i", $schedule_id);
        if ($stmt->execute()) $message = "Feeding deleted.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }
    elseif ($action === 'toggle_done') {
        $schedule_id = intval($_POST['schedule_id']);
        $is_done = intval($_POST['is_done']);
        $stmt = $conn->prepare("UPDATE feeding_schedules SET is_done = ? WHERE schedule_id = ?");
        $stmt->bind_param("ii", $is_done, $schedule_id);
        if ($stmt->execute()) $message = "Status updated.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }

    // ---------- Dietary Items ----------
    elseif ($action === 'add_dietary') {
        $item_name = $_POST['item_name'];
        $amount_grams = floatval($_POST['amount_grams']);
        $for_species_id = !empty($_POST['for_species_id']) ? intval($_POST['for_species_id']) : null;
        $notes = trim($_POST['notes'] ?? '');
        $stmt = $conn->prepare("INSERT INTO dietary_items (item_name, amount_grams, for_species_id, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sdds", $item_name, $amount_grams, $for_species_id, $notes);
        if ($stmt->execute()) $message = "Dietary item added.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }
    elseif ($action === 'edit_dietary') {
        $item_id = intval($_POST['item_id']);
        $item_name = $_POST['item_name'];
        $amount_grams = floatval($_POST['amount_grams']);
        $notes = trim($_POST['notes'] ?? '');
        $stmt = $conn->prepare("UPDATE dietary_items SET item_name=?, amount_grams=?, notes=? WHERE item_id=?");
        $stmt->bind_param("sdsi", $item_name, $amount_grams, $notes, $item_id);
        if ($stmt->execute()) $message = "Dietary item updated.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }
    elseif ($action === 'delete_dietary') {
        $item_id = intval($_POST['item_id']);
        $stmt = $conn->prepare("DELETE FROM dietary_items WHERE item_id = ?");
        $stmt->bind_param("i", $item_id);
        if ($stmt->execute()) $message = "Dietary item deleted.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }

    // ---------- Special Dietary Needs ----------
    elseif ($action === 'add_special') {
        $tortoise_id = intval($_POST['tortoise_id']);
        $restriction = $_POST['restriction'];
        $note = $_POST['note'];
        $created_by = !empty($_POST['created_by']) ? intval($_POST['created_by']) : null;
        $stmt = $conn->prepare("INSERT INTO special_dietary_needs (tortoise_id, restriction, note, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $tortoise_id, $restriction, $note, $created_by);
        if ($stmt->execute()) $message = "Special need added.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }
    elseif ($action === 'edit_special') {
        $need_id = intval($_POST['need_id']);
        $restriction = $_POST['restriction'];
        $note = $_POST['note'];
        $stmt = $conn->prepare("UPDATE special_dietary_needs SET restriction=?, note=? WHERE need_id=?");
        $stmt->bind_param("ssi", $restriction, $note, $need_id);
        if ($stmt->execute()) $message = "Special need updated.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }
    elseif ($action === 'delete_special') {
        $need_id = intval($_POST['need_id']);
        $stmt = $conn->prepare("DELETE FROM special_dietary_needs WHERE need_id = ?");
        $stmt->bind_param("i", $need_id);
        if ($stmt->execute()) $message = "Special need deleted.";
        else $error = "DB error: " . $stmt->error;
        $stmt->close();
    }
}

// -------------------------------
// Fetch data for display
// -------------------------------
// Show all feeding schedules (latest first) – so user sees newly added entries regardless of date
$feedings = $conn->query("
    SELECT f.*, t.name as tortoise_name 
    FROM feeding_schedules f
    JOIN tortoises t ON f.tortoise_id = t.tortoise_id
    ORDER BY f.scheduled_date DESC, f.feeding_time ASC
");

$dietaryItems = $conn->query("SELECT * FROM dietary_items ORDER BY item_name");

$specialNeeds = $conn->query("
    SELECT s.*, t.name as tortoise_name 
    FROM special_dietary_needs s
    JOIN tortoises t ON s.tortoise_id = t.tortoise_id
    ORDER BY t.name
");

$tortoises = $conn->query("SELECT tortoise_id, name FROM tortoises ORDER BY name");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Feeder Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #fff8e7 0%, #ffe5cc 50%, #ffd9a8 100%);
            color: #2c3e50;
        }
        .page-header { background: linear-gradient(135deg, #c85a3a 0%, #8b5a2b 100%); color: white; padding: 30px 40px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .page-header h1 { font-size: 28px; margin: 0; }
        .btn { background: #e98707; color: white; border: none; padding: 10px 20px; border-radius: 7px; cursor: pointer; }
        .tabs-container { margin: 20px 40px; background: white; border-radius: 16px; overflow: hidden; }
        .tab-buttons { display: flex; background: #f8f9fa; border-bottom: 1px solid #dee2e6; flex-wrap: wrap; }
        .tab-btn { padding: 12px 24px; background: none; border: none; cursor: pointer; font-weight: 600; }
        .tab-btn.active { color: #c85a3a; border-bottom: 3px solid #c85a3a; }
        .tab-content { display: none; padding: 20px; }
        .tab-content.active { display: block; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; overflow-x: auto; display: block; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #c85a3a; color: white; }
        .done-btn { background: #27ae60; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; }
        .done-btn.completed { background: #95a5a6; cursor: default; }
        .action-btn { padding: 6px 12px; margin: 0 4px; border: none; border-radius: 6px; cursor: pointer; }
        .edit-btn { background: #f39c12; color: white; }
        .delete-btn { background: #e74c3c; color: white; }
        .add-form { background: #f8f9fa; padding: 20px; border-radius: 12px; margin-top: 20px; }
        .add-form input, .add-form select, .add-form textarea { padding: 8px; margin: 5px; border: 1px solid #ccc; border-radius: 6px; width: calc(25% - 10px); }
        .add-form textarea { width: calc(50% - 10px); vertical-align: top; }
        .message { background: #d4edda; color: #155724; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 15px; }
        .search-container { margin-bottom: 20px; }
        .search-input { width: 300px; padding: 8px; border-radius: 20px; border: 1px solid #ccc; }
        @media (max-width: 768px) {
            .add-form input, .add-form select, .add-form textarea { width: 100%; margin: 5px 0; }
            .tab-btn { padding: 8px 16px; font-size: 14px; }
            .page-header { flex-direction: column; text-align: center; gap: 10px; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <div class="content">
        <h1><i class="fas fa-utensils"></i> Feeder Management System</h1>
        <p>Comprehensive animal feeding schedules and dietary management</p>
    </div>
    <button class="btn" onclick="window.location.href='logout.php'">Logout</button>
</div>

<div class="tabs-container">
    <div class="tab-buttons">
        <button class="tab-btn active" onclick="showTab('todays-plan')">📅 Feeding Schedules</button>
        <button class="tab-btn" onclick="showTab('current-plan')">📋 Current Dietary Plan</button>
        <button class="tab-btn" onclick="showTab('special-needs')">⚠️ Special Dietary Needs</button>
    </div>

    <!-- FEEDING SCHEDULES TAB (all schedules) -->
    <div id="todays-plan" class="tab-content active">
        <h2>All Feeding Schedules</h2>
        <?php if ($message): ?><div class="message">✅ <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <div class="search-container">
            <input type="text" id="searchFeeding" class="search-input" placeholder="Search (tortoise, food, date)..." onkeyup="filterTable('feedingTable', this.value)">
        </div>
        <table id="feedingTable">
            <thead>
                <tr><th>Date</th><th>Time</th><th>Tortoise</th><th>Food Type</th><th>Amount (g)</th><th>Done</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while($row = $feedings->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['scheduled_date']); ?></td>
                    <td><?php echo htmlspecialchars($row['feeding_time']); ?></td>
                    <td><?php echo htmlspecialchars($row['tortoise_name']); ?> (ID: <?php echo $row['tortoise_id']; ?>)</td>
                    <td><?php echo htmlspecialchars($row['food_type']); ?></td>
                    <td><?php echo $row['amount_grams']; ?> g</td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_done">
                            <input type="hidden" name="schedule_id" value="<?php echo $row['schedule_id']; ?>">
                            <input type="hidden" name="is_done" value="<?php echo $row['is_done'] ? 0 : 1; ?>">
                            <button type="submit" class="done-btn <?php echo $row['is_done'] ? 'completed' : ''; ?>" <?php echo $row['is_done'] ? 'disabled' : ''; ?>>
                                <?php echo $row['is_done'] ? '✓' : 'Mark Done'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_feeding">
                            <input type="hidden" name="schedule_id" value="<?php echo $row['schedule_id']; ?>">
                            <button type="submit" class="action-btn delete-btn" onclick="return confirm('Delete this feeding?')">Delete</button>
                        </form>
                        <button class="action-btn edit-btn" onclick="editFeeding(<?php echo $row['schedule_id']; ?>, '<?php echo htmlspecialchars($row['feeding_time']); ?>', '<?php echo htmlspecialchars($row['food_type']); ?>', <?php echo $row['amount_grams']; ?>, '<?php echo htmlspecialchars($row['notes']); ?>')">Edit</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="add-form">
            <h3>Add New Feeding Schedule</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_feeding">
                <input type="date" name="scheduled_date" required value="<?php echo date('Y-m-d'); ?>">
                <input type="time" name="feeding_time" required>
                <select name="tortoise_id" required>
                    <option value="">Select Tortoise</option>
                    <?php
                    $tortoisesSel = $conn->query("SELECT tortoise_id, name FROM tortoises ORDER BY name");
                    while($t = $tortoisesSel->fetch_assoc()):
                    ?>
                        <option value="<?php echo $t['tortoise_id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (ID: <?php echo $t['tortoise_id']; ?>)</option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="food_type" required placeholder="Food Type">
                <input type="number" step="0.01" name="amount_grams" required placeholder="Amount (g)">
                <input type="number" name="feeder_id" placeholder="Feeder ID (optional)">
                <textarea name="notes" placeholder="Notes" rows="2"></textarea>
                <button type="submit" class="action-btn edit-btn">Add Schedule</button>
            </form>
        </div>
    </div>

    <!-- CURRENT DIETARY PLAN TAB -->
    <div id="current-plan" class="tab-content">
        <h2>Current Dietary Plan</h2>
        <div class="search-container">
            <input type="text" id="searchDietary" class="search-input" placeholder="Search..." onkeyup="filterTable('dietaryTable', this.value)">
        </div>
        <table id="dietaryTable">
            <thead>
                <tr><th>Food/Supplements Name</th><th>Amount (g)</th><th>Notes</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while($row = $dietaryItems->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                    <td><?php echo $row['amount_grams']; ?> g</td>
                    <td><?php echo htmlspecialchars($row['notes']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_dietary">
                            <input type="hidden" name="item_id" value="<?php echo $row['item_id']; ?>">
                            <button type="submit" class="action-btn delete-btn" onclick="return confirm('Delete this dietary item?')">Delete</button>
                        </form>
                        <button class="action-btn edit-btn" onclick="editDietary(<?php echo $row['item_id']; ?>, '<?php echo htmlspecialchars($row['item_name']); ?>', <?php echo $row['amount_grams']; ?>, '<?php echo htmlspecialchars($row['notes']); ?>')">Edit</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="add-form">
            <h3>Add New Dietary Item</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_dietary">
                <input type="text" name="item_name" required placeholder="Food/Supplements Name">
                <input type="number" step="0.01" name="amount_grams" required placeholder="Amount (g)">
                <input type="number" name="for_species_id" placeholder="Species ID (optional)">
                <textarea name="notes" placeholder="Notes" rows="2"></textarea>
                <button type="submit" class="action-btn edit-btn">Add Item</button>
            </form>
        </div>
    </div>

    <!-- SPECIAL DIETARY NEEDS TAB -->
    <div id="special-needs" class="tab-content">
        <h2>Special Dietary Needs</h2>
        <div class="search-container">
            <input type="text" id="searchSpecial" class="search-input" placeholder="Search..." onkeyup="filterTable('specialTable', this.value)">
        </div>
        <table id="specialTable">
            <thead>
                <tr><th>Tortoise</th><th>Restriction</th><th>Note</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php while($row = $specialNeeds->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['tortoise_name']); ?> (ID: <?php echo $row['tortoise_id']; ?>)</td>
                    <td><?php echo htmlspecialchars($row['restriction']); ?></td>
                    <td><?php echo htmlspecialchars($row['note']); ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_special">
                            <input type="hidden" name="need_id" value="<?php echo $row['need_id']; ?>">
                            <button type="submit" class="action-btn delete-btn" onclick="return confirm('Delete this special need?')">Delete</button>
                        </form>
                        <button class="action-btn edit-btn" onclick="editSpecial(<?php echo $row['need_id']; ?>, '<?php echo htmlspecialchars($row['restriction']); ?>', '<?php echo htmlspecialchars($row['note']); ?>')">Edit</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="add-form">
            <h3>Add New Special Dietary Need</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_special">
                <select name="tortoise_id" required>
                    <option value="">Select Tortoise</option>
                    <?php
                    $tortoisesSpec = $conn->query("SELECT tortoise_id, name FROM tortoises ORDER BY name");
                    while($t = $tortoisesSpec->fetch_assoc()):
                    ?>
                        <option value="<?php echo $t['tortoise_id']; ?>"><?php echo htmlspecialchars($t['name']); ?> (ID: <?php echo $t['tortoise_id']; ?>)</option>
                    <?php endwhile; ?>
                </select>
                <input type="text" name="restriction" required placeholder="Restriction">
                <textarea name="note" placeholder="Note" rows="2"></textarea>
                <input type="number" name="created_by" placeholder="Staff ID (optional)">
                <button type="submit" class="action-btn edit-btn">Add Need</button>
            </form>
        </div>
    </div>
</div>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    event.currentTarget.classList.add('active');
}

function filterTable(tableId, searchText) {
    const table = document.getElementById(tableId);
    if (!table) return;
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    const filter = searchText.toLowerCase();
    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let match = false;
        for (let j = 0; j < cells.length; j++) {
            if (cells[j].innerText.toLowerCase().indexOf(filter) > -1) {
                match = true;
                break;
            }
        }
        rows[i].style.display = match ? '' : 'none';
    }
}

function editFeeding(id, time, food, amount, notes) {
    let newTime = prompt("Edit time (HH:MM:SS):", time);
    if (newTime === null) return;
    let newFood = prompt("Edit food type:", food);
    if (newFood === null) return;
    let newAmount = prompt("Edit amount (g):", amount);
    if (newAmount === null) return;
    let newNotes = prompt("Edit notes:", notes);
    if (newNotes === null) return;
    let form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = '<input type="hidden" name="action" value="edit_feeding">' +
                     '<input type="hidden" name="schedule_id" value="' + id + '">' +
                     '<input type="hidden" name="feeding_time" value="' + newTime + '">' +
                     '<input type="hidden" name="food_type" value="' + newFood + '">' +
                     '<input type="hidden" name="amount_grams" value="' + newAmount + '">' +
                     '<input type="hidden" name="notes" value="' + newNotes + '">';
    document.body.appendChild(form);
    form.submit();
}

function editDietary(id, name, amount, notes) {
    let newName = prompt("Edit name:", name);
    if (newName === null) return;
    let newAmount = prompt("Edit amount (g):", amount);
    if (newAmount === null) return;
    let newNotes = prompt("Edit notes:", notes);
    if (newNotes === null) return;
    let form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = '<input type="hidden" name="action" value="edit_dietary">' +
                     '<input type="hidden" name="item_id" value="' + id + '">' +
                     '<input type="hidden" name="item_name" value="' + newName + '">' +
                     '<input type="hidden" name="amount_grams" value="' + newAmount + '">' +
                     '<input type="hidden" name="notes" value="' + newNotes + '">';
    document.body.appendChild(form);
    form.submit();
}

function editSpecial(id, restriction, note) {
    let newRestriction = prompt("Edit restriction:", restriction);
    if (newRestriction === null) return;
    let newNote = prompt("Edit note:", note);
    if (newNote === null) return;
    let form = document.createElement("form");
    form.method = "POST";
    form.innerHTML = '<input type="hidden" name="action" value="edit_special">' +
                     '<input type="hidden" name="need_id" value="' + id + '">' +
                     '<input type="hidden" name="restriction" value="' + newRestriction + '">' +
                     '<input type="hidden" name="note" value="' + newNote + '">';
    document.body.appendChild(form);
    form.submit();
}
</script>
</body>
</html>