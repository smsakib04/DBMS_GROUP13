<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$nest_id = (int)($_POST['nest_id'] ?? 0);

if (!$nest_id) {
    echo json_encode(['success'=>false,'message'=>'Invalid nest ID.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM nests WHERE nest_id=?");
$stmt->bind_param('i', $nest_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
