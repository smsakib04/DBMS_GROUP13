<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['pair_code'];
    $male = $_POST['male_tortoise_id'];
    $female = $_POST['female_tortoise_id'];
    $date = $_POST['pairing_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO breeding_pairs (pair_code, male_tortoise_id, female_tortoise_id, pairing_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisss", $code, $male, $female, $date, $status, $notes);
    if ($stmt->execute()) {
        header("Location: ../pages/breeding.php?msg=pair_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>