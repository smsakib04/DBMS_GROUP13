<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';
 
// Only supervisors may delete tasks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: supervisor.php");
    exit();
}
 
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
 
if (!$id) {
    header("Location: supervisor.php");
    exit();
}
 
// Verify the task exists before deleting
$check = $conn->prepare("SELECT task_id FROM tasks WHERE task_id = ?");
$check->bind_param("i", $id);
$check->execute();
$check->store_result();
 
if ($check->num_rows === 0) {
    $check->close();
    header("Location: supervisor.php");
    exit();
}
$check->close();
 
// Perform deletion
$stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $id);
 
if ($stmt->execute()) {
    $stmt->close();
    header("Location: supervisor.php?msg=deleted");
} else {
    $stmt->close();
    header("Location: supervisor.php?msg=error");
}
exit();