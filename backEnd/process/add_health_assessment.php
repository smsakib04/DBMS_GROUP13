<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tortoise_id = $_POST['tortoise_id'];
    $vet_id = $_POST['vet_id'];
    $date = $_POST['assessment_date'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $remarks = $_POST['remarks'];
    $next = $_POST['next_checkup_date'];

    $stmt = $conn->prepare("INSERT INTO health_assessments (tortoise_id, vet_id, assessment_date, diagnosis, treatment, remarks, next_checkup_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $tortoise_id, $vet_id, $date, $diagnosis, $treatment, $remarks, $next);
    if ($stmt->execute()) {
        header("Location: ../pages/veterenian.php?msg=assessment_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>