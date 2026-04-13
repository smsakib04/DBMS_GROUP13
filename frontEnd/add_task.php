<?php
session_start();
require_once '../backEnd/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['task_name'];
    $assigned_to = $_POST['assigned_to'];
    $assigned_by = $_POST['assigned_by'];
    $due = $_POST['due_date'];
    $status = $_POST['status'];
    $notes = $_POST['completion_notes'];

    $stmt = $conn->prepare("INSERT INTO tasks (task_name, assigned_to, assigned_by, due_date, status, completion_notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisss", $name, $assigned_to, $assigned_by, $due, $status, $notes);

    if ($stmt->execute()) {
        header("Location: supervisor.php?msg=task_added");
        exit();
    } else {
        $error = $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Task</title>
</head>
<body>

<div class="form-card">
    <h2>📋 Assign New Task</h2>

    <!-- Show error if exists -->
    <?php if (!empty($error)) { ?>
        <p style="color:red;">Error: <?php echo $error; ?></p>
    <?php } ?>

    <form method="POST" action="">
        <label>Task Name</label>
        <input type="text" name="task_name" required>

        <label>Assigned To (staff ID)</label>
        <input type="number" name="assigned_to" required>

        <label>Assigned By (staff ID)</label>
        <input type="number" name="assigned_by">

        <label>Due Date</label>
        <input type="date" name="due_date" required>

        <label>Status</label>
        <select name="status">
            <option>Pending</option>
            <option>In Progress</option>
            <option>Completed</option>
            <option>Cancelled</option>
        </select>

        <label>Completion Notes</label>
        <textarea name="completion_notes"></textarea>

        <button type="submit">Create Task</button>
    </form>
</div>

</body>
</html>