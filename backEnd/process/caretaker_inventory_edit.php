<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$inventory_id  = (int)($_POST['inventory_id'] ?? 0);
$item_name     = trim($_POST['item_name'] ?? '');
$quantity      = trim($_POST['quantity'] ?? '');
$unit          = trim($_POST['unit'] ?? '');
$reorder_level = trim($_POST['reorder_level'] ?? '0');
$supplier      = trim($_POST['supplier'] ?? '');

if (!$inventory_id || !$item_name || $quantity === '') {
    echo json_encode(['success'=>false,'message'=>'ID, item name, and quantity are required.']);
    exit;
}

$quantity      = (float)$quantity;
$reorder_level = (float)$reorder_level;
$today         = date('Y-m-d');

$stmt = $conn->prepare(
    "UPDATE inventory SET item_name=?, quantity=?, unit=?, reorder_level=?, supplier=?, last_updated=? WHERE inventory_id=?"
);
$stmt->bind_param('sdsdssi', $item_name, $quantity, $unit, $reorder_level, $supplier, $today, $inventory_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
