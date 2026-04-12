<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$inventory_id = (int)($_POST['inventory_id'] ?? 0);

if (!$inventory_id) {
    echo json_encode(['success'=>false,'message'=>'Invalid inventory ID.']);
    exit;
}

$stmt = $conn->prepare("DELETE FROM inventory WHERE inventory_id=?");
$stmt->bind_param('i', $inventory_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
