<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$pair_id            = (int)($_POST['pair_id'] ?? 0);
$pair_code          = trim($_POST['pair_code'] ?? '');
$male_tortoise_id   = (int)($_POST['male_tortoise_id'] ?? 0);
$female_tortoise_id = (int)($_POST['female_tortoise_id'] ?? 0);
$pairing_date       = trim($_POST['pairing_date'] ?? '');
$status             = trim($_POST['status'] ?? 'paired');
$notes              = trim($_POST['notes'] ?? '');

if (!$pair_id || !$pair_code || !$male_tortoise_id || !$female_tortoise_id || !$pairing_date) {
    echo json_encode(['success'=>false,'message'=>'All required fields must be filled.']);
    exit;
}

$allowed = ['paired','courting','incubating','hatched','separated'];
if (!in_array($status, $allowed)) $status = 'paired';

$stmt = $conn->prepare("UPDATE breeding_pairs SET pair_code=?, male_tortoise_id=?, female_tortoise_id=?, pairing_date=?, status=?, notes=? WHERE pair_id=?");
$stmt->bind_param('siisssi', $pair_code, $male_tortoise_id, $female_tortoise_id, $pairing_date, $status, $notes, $pair_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
