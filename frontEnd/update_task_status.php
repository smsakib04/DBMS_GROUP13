<?php
session_start();
require_once '../backEnd/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['task_id'];
    $status = $_POST['status'];
    $notes = $_POST['completion_notes'];

    $stmt = $conn->prepare("UPDATE tasks SET status = ?, completion_notes = ? WHERE task_id = ?");
    $stmt->bind_param("ssi", $status, $notes, $id);

    if ($stmt->execute()) {
       echo "<p style='color:green;'>✅ Task updated successfully!</p>";
    } else {
        die("Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Update Task Status</title>
</head>
<body>

<div class="form-card">
    <h2>🔄 Change Task Status</h2>

    <!-- FIXED HERE -->
    <form method="POST">

        <label>Task ID</label>
        <input type="number" name="task_id" required>

        <label>New Status</label>
        <select name="status">
            <option>Pending</option>
            <option>In Progress</option>
            <option>Completed</option>
            <option>Cancelled</option>
        </select>

        <label>Completion Notes (optional)</label>
        <textarea name="completion_notes"></textarea>

        <button type="submit">Update</button>
    </form>
</div>

</body>
</html>