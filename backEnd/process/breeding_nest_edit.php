<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$nest_id              = (int)($_POST['nest_id'] ?? 0);
$nest_code            = trim($_POST['nest_code'] ?? '');
$pair_id              = (int)($_POST['pair_id'] ?? 0);
$nesting_date         = trim($_POST['nesting_date'] ?? '');
$egg_count            = (int)($_POST['egg_count'] ?? 0);
$fertile_eggs         = (isset($_POST['fertile_eggs']) && $_POST['fertile_eggs'] !== '') ? (int)$_POST['fertile_eggs'] : null;
$incubator_id         = (isset($_POST['incubator_id']) && $_POST['incubator_id'] !== '') ? (int)$_POST['incubator_id'] : null;
$estimated_hatch_date = (isset($_POST['estimated_hatch_date']) && $_POST['estimated_hatch_date'] !== '') ? trim($_POST['estimated_hatch_date']) : null;
$actual_hatch_date    = (isset($_POST['actual_hatch_date']) && $_POST['actual_hatch_date'] !== '') ? trim($_POST['actual_hatch_date']) : null;

if (!$nest_id || !$nest_code || !$pair_id || !$nesting_date) {
    echo json_encode(['success'=>false,'message'=>'Nest ID, code, pair, and nesting date are required.']);
    exit;
}

$stmt = $conn->prepare("UPDATE nests SET nest_code=?, pair_id=?, nesting_date=?, egg_count=?, fertile_eggs=?, incubator_id=?, estimated_hatch_date=?, actual_hatch_date=? WHERE nest_id=?");
$stmt->bind_param('sisiisssi', $nest_code, $pair_id, $nesting_date, $egg_count, $fertile_eggs, $incubator_id, $estimated_hatch_date, $actual_hatch_date, $nest_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
