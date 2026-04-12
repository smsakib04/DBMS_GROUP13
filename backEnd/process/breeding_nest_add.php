<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$nest_code            = trim($_POST['nest_code'] ?? '');
$pair_id              = (int)($_POST['pair_id'] ?? 0);
$nesting_date         = trim($_POST['nesting_date'] ?? '');
$egg_count            = (int)($_POST['egg_count'] ?? 0);
$fertile_eggs         = $_POST['fertile_eggs'] !== '' ? (int)$_POST['fertile_eggs'] : null;
$incubator_id         = $_POST['incubator_id'] !== '' ? (int)$_POST['incubator_id'] : null;
$estimated_hatch_date = trim($_POST['estimated_hatch_date'] ?? '') ?: null;
$actual_hatch_date    = trim($_POST['actual_hatch_date'] ?? '') ?: null;
$notes                = trim($_POST['notes'] ?? '');

if (!$nest_code || !$pair_id || !$nesting_date || $egg_count < 0) {
    echo json_encode(['success'=>false,'message'=>'Nest code, pair, nesting date, and egg count are required.']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO nests (nest_code, pair_id, nesting_date, egg_count, fertile_eggs, incubator_id, estimated_hatch_date, actual_hatch_date, notes) VALUES (?,?,?,?,?,?,?,?,?)");
$stmt->bind_param('sisiiiiss', $nest_code, $pair_id, $nesting_date, $egg_count, $fertile_eggs, $incubator_id, $estimated_hatch_date, $actual_hatch_date, $notes);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'id'=>$conn->insert_id]);
} else {
    $err = $conn->error;
    if (strpos($err,'Duplicate') !== false)
        echo json_encode(['success'=>false,'message'=>"Nest code '$nest_code' already exists."]);
    else
        echo json_encode(['success'=>false,'message'=>'DB error: '.$err]);
}
$stmt->close();
