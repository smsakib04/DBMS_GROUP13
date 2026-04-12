<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$task_id          = (int)($_POST['task_id'] ?? 0);
$task_name        = trim($_POST['task_name'] ?? '');
$due_date         = trim($_POST['due_date'] ?? '');
$status           = trim($_POST['status'] ?? 'Pending');
$completion_notes = trim($_POST['completion_notes'] ?? '');

if (!$task_id || !$task_name || !$due_date) {
    echo json_encode(['success'=>false,'message'=>'Task ID, name, and due date are required.']);
    exit;
}

$allowed = ['Pending','In Progress','Completed','Cancelled'];
if (!in_array($status, $allowed)) $status = 'Pending';

$stmt = $conn->prepare("UPDATE tasks SET task_name=?, due_date=?, status=?, completion_notes=? WHERE task_id=?");
$stmt->bind_param('ssssi', $task_name, $due_date, $status, $completion_notes, $task_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
