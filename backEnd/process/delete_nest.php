<?php
require_once '../backEnd/config/db.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$nest_id = isset($_POST['nest_id']) ? intval($_POST['nest_id']) : 0;
if ($nest_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid nest ID']);
    exit();
}

$stmt = $conn->prepare("DELETE FROM nests WHERE nest_id = ?");
$stmt->bind_param("i", $nest_id);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Nest not found']);
} else {
    echo json_encode(['success' => true, 'message' => 'Nest deleted']);
}
$stmt->close();
$conn->close();
?>