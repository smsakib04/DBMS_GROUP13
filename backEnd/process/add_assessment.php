<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code    = $_POST['assessment_code'];
    $date    = $_POST['assessment_date'];
    $t_id    = (int)$_POST['tortoise_id'];
    $remarks = $_POST['remarks'];

    // SQL command to insert into our new 4-column table
    $stmt = $conn->prepare("INSERT INTO health_assessments (assessment_code, assessment_date, remarks, tortoise_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $code, $date, $remarks, $t_id);

    if ($stmt->execute()) {
        header("Location: ../pages/veterenian.php?msg=added");
    } else {
        header("Location: ../pages/veterenian.php?msg=error");
    }
}
?>