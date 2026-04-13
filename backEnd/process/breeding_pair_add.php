<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$pair_code          = trim($_POST['pair_code'] ?? '');
$male_tortoise_id   = (int)($_POST['male_tortoise_id'] ?? 0);
$female_tortoise_id = (int)($_POST['female_tortoise_id'] ?? 0);
$pairing_date       = trim($_POST['pairing_date'] ?? '');
$status             = trim($_POST['status'] ?? 'paired');
$notes              = trim($_POST['notes'] ?? '');

if (!$pair_code || !$male_tortoise_id || !$female_tortoise_id || !$pairing_date) {
    echo json_encode(['success'=>false,'message'=>'Pair code, male, female, and pairing date are required.']);
    exit;
}

$allowed = ['paired','courting','incubating','hatched','separated'];
if (!in_array($status, $allowed)) $status = 'paired';

$stmt = $conn->prepare("INSERT INTO breeding_pairs (pair_code, male_tortoise_id, female_tortoise_id, pairing_date, status, notes) VALUES (?,?,?,?,?,?)");
$stmt->bind_param('siisss', $pair_code, $male_tortoise_id, $female_tortoise_id, $pairing_date, $status, $notes);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'id'=>$conn->insert_id]);
} else {
    $err = $conn->error;
    if (strpos($err,'Duplicate') !== false)
        echo json_encode(['success'=>false,'message'=>"Pair code '$pair_code' already exists."]);
    else
        echo json_encode(['success'=>false,'message'=>'DB error: '.$err]);
}
$stmt->close();
