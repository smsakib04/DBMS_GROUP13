<?php
require_once '../config/db.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;
$result = $conn->query("SELECT * FROM tortoises WHERE tortoise_id = $id");
echo json_encode($result->fetch_assoc());
?>