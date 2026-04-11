<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['pair_id'];
    $stmt = $conn->prepare("DELETE FROM breeding_pairs WHERE pair_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: ../pages/breeding.php?msg=pair_deleted");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>