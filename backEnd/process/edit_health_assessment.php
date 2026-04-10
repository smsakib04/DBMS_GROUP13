<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['assessment_id'];
    $tortoise_id = $_POST['tortoise_id'];
    $vet_id = $_POST['vet_id'];
    $date = $_POST['assessment_date'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $remarks = $_POST['remarks'];
    $next = $_POST['next_checkup_date'];

    $stmt = $conn->prepare("UPDATE health_assessments SET tortoise_id=?, vet_id=?, assessment_date=?, diagnosis=?, treatment=?, remarks=?, next_checkup_date=? WHERE assessment_id=?");
    $stmt->bind_param("iisssssi", $tortoise_id, $vet_id, $date, $diagnosis, $treatment, $remarks, $next, $id);
    if ($stmt->execute()) {
        header("Location: ../pages/veterenian.php?msg=assessment_updated");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>