<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$task_id = (int)($_POST['task_id'] ?? 0);

if (!$task_id) {
    echo json_encode(['success'=>false,'message'=>'Invalid task ID.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM tasks WHERE task_id=?");
$stmt->bind_param('i', $task_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
