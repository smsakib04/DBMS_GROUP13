<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$task_name        = trim($_POST['task_name'] ?? '');
$assigned_to      = (int)($_POST['assigned_to'] ?? 0);
$due_date         = trim($_POST['due_date'] ?? '');
$status           = trim($_POST['status'] ?? 'Pending');
$completion_notes = trim($_POST['completion_notes'] ?? '');

if (!$task_name || !$assigned_to || !$due_date) {
    echo json_encode(['success'=>false,'message'=>'Task name, assignee, and due date are required.']);
    exit;
}

$allowed_statuses = ['Pending','In Progress','Completed','Cancelled'];
if (!in_array($status, $allowed_statuses)) $status = 'Pending';

$stmt = $conn->prepare("INSERT INTO tasks (task_name, assigned_to, due_date, status, completion_notes) VALUES (?,?,?,?,?)");
$stmt->bind_param('sisss', $task_name, $assigned_to, $due_date, $status, $completion_notes);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'id'=>$conn->insert_id]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
