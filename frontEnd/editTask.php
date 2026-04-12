<?php
require_once '../backEnd/config/db.php';

// ✅ FIX: use task_id instead of id
$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    die("No task selected. Please go back and choose a task.");
}

// ✅ UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = $_POST['task_name'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';
    $due = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? '';
    $notes = $_POST['completion_notes'] ?? '';

    $stmt = $conn->prepare("
        UPDATE tasks 
        SET task_name=?, assigned_to=?, due_date=?, status=?, completion_notes=? 
        WHERE task_id=?
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

// ✅ FETCH TASK
$stmt = $conn->prepare("SELECT * FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$task = $result->fetch_assoc();

if (!$task) {
    die("Task not found");
}
?>