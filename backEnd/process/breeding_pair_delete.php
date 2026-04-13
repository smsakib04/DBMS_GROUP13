<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

header('Content-Type: application/json');

$pair_id = (int)($_POST['pair_id'] ?? 0);

if (!$pair_id) {
    echo json_encode(['success'=>false,'message'=>'Invalid pair ID.']);
    exit;
}

// Nests referencing this pair will be deleted by CASCADE (nests_ibfk_1 ON DELETE CASCADE)
$stmt = $conn->prepare("DELETE FROM breeding_pairs WHERE pair_id=?");
$stmt->bind_param('i', $pair_id);

if ($stmt->execute()) {
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB error: '.$conn->error]);
}
$stmt->close();
