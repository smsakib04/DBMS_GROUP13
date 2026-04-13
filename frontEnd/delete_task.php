<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';
 
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
 
if (!$id) {
    header("Location: supervisor.php");
    exit();
}
 
$stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
$stmt->bind_param("i", $id);
 
if ($stmt->execute()) {
    $stmt->close();
    header("Location: supervisor.php?msg=deleted");
    exit();
} else {
    $err = $stmt->error;
    $stmt->close();
    header("Location: supervisor.php?msg=error&detail=" . urlencode($err));
    exit();
}
 