<?php
require_once '../backEnd/config/db.php';

// Get ID safely
$id = $_GET['id'] ?? $_POST['id'] ?? null;

// Better handling instead of die()
if (!$id) {
    echo "<h3 style='color:red;text-align:center;'>No task selected. Please go back and choose a task.</h3>";
    exit();
}

// UPDATE logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['id']); // ensure it's number
    $name = $_POST['task_name'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';
    $due = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? '';
    $notes = $_POST['completion_notes'] ?? '';

    $stmt = $conn->prepare("
        UPDATE tasks 
        SET task_name=?, assigned_to=?, due_date=?, status=?, completion_notes=? 
        WHERE id=?
    ");

    $stmt->bind_param("sssssi", $name, $assigned_to, $due, $status, $notes, $id);

    if ($stmt->execute()) {
        header("Location: supervisor.php?msg=updated");
        exit();
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
}

// FETCH task data
$stmt = $conn->prepare("SELECT * FROM tasks WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    echo "<h3 style='color:red;text-align:center;'>Task not found.</h3>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Task</title>

<style>
body { font-family: Arial; background:#f4f4f4; }
.container { width: 500px; margin: 40px auto; background:#fff; padding:20px; border-radius:10px; }
input, select, textarea { width:100%; margin-top:10px; padding:10px; }
button { margin-top:15px; padding:10px; width:100%; }
.error { color:red; }
</style>
</head>

<body>

<div class="container">
<h2>Edit Task</h2>

<?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>

<form method="POST">
    <input type="hidden" name="id" value="<?= $task['id'] ?>">

    <label>Task Name</label>
    <input type="text" name="task_name" value="<?= htmlspecialchars($task['task_name']) ?>" required>

    <label>Assigned To</label>
    <input type="text" name="assigned_to" value="<?= htmlspecialchars($task['assigned_to']) ?>">

    <label>Due Date</label>
    <input type="date" name="due_date" value="<?= $task['due_date'] ?>">

    <label>Status</label>
    <select name="status">
        <option <?= $task['status']=='Pending'?'selected':'' ?>>Pending</option>
        <option <?= $task['status']=='In Progress'?'selected':'' ?>>In Progress</option>
        <option <?= $task['status']=='Completed'?'selected':'' ?>>Completed</option>
        <option <?= $task['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
    </select>

    <label>Notes</label>
    <textarea name="completion_notes"><?= htmlspecialchars($task['completion_notes']) ?></textarea>

    <button type="submit">Update Task</button>
</form>
</div>

</body>
</html>