<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$item_name     = trim($_POST['item_name'] ?? '');
$category      = trim($_POST['category'] ?? 'other');
$quantity      = trim($_POST['quantity'] ?? '');
$unit          = trim($_POST['unit'] ?? '');
$reorder_level = trim($_POST['reorder_level'] ?? '0');
$supplier      = trim($_POST['supplier'] ?? '');

if (!$item_name || $quantity === '' || !$unit) {
    echo json_encode(['success'=>false,'message'=>'Item name, quantity, and unit are required.']);
    exit;
}

$allowed_cats = ['food','medical','cleaning','equipment','other'];
if (!in_array($category, $allowed_cats)) $category = 'other';

$quantity      = (float)$quantity;
$reorder_level = (float)$reorder_level;
$today         = date('Y-m-d');

$stmt = $conn->prepare(
    "INSERT INTO inventory (item_name, category, quantity, unit, reorder_level, supplier, last_updated) VALUES (?,?,?,?,?,?,?)"
);
$stmt->bind_param('ssdsdss', $item_name, $category, $quantity, $unit, $reorder_level, $supplier, $today);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'id'=>$conn->insert_id]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
