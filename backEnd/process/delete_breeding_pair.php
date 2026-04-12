<?php
require_once '../backEnd/config/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$pair_id = isset($_POST['pair_id']) ? intval($_POST['pair_id']) : 0;
if ($pair_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid pair ID']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // First delete related nests (if cascade not set)
    $deleteNests = $conn->prepare("DELETE FROM nests WHERE pair_id = ?");
    $deleteNests->bind_param("i", $pair_id);
    $deleteNests->execute();
    $deleteNests->close();

    // Then delete the breeding pair
    $stmt = $conn->prepare("DELETE FROM breeding_pairs WHERE pair_id = ?");
    $stmt->bind_param("i", $pair_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("No breeding pair found with ID: $pair_id");
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Breeding pair deleted']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
$conn->close();
?>