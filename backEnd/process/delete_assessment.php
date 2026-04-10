<?php
session_start();
require_once '../config/db.php';

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM health_assessments WHERE assessment_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
header("Location: ../pages/veterenian.php?msg=deleted");
?>