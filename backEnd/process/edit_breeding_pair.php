<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['pair_id'];
    $code = $_POST['pair_code'];
    $male = $_POST['male_tortoise_id'];
    $female = $_POST['female_tortoise_id'];
    $date = $_POST['pairing_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE breeding_pairs SET pair_code=?, male_tortoise_id=?, female_tortoise_id=?, pairing_date=?, status=?, notes=? WHERE pair_id=?");
    $stmt->bind_param("siisssi", $code, $male, $female, $date, $status, $notes, $id);
    if ($stmt->execute()) {
        header("Location: ../pages/breeding.php?msg=pair_updated");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>