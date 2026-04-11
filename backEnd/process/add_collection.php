<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tortoise_id = $_POST['tortoise_id'] ?? null;
    $date = $_POST['collection_date'];
    $source = $_POST['source_type'];
    $location = $_POST['location'];
    $health = $_POST['initial_health'];
    $notes = $_POST['notes'];
    $collected_by = $_POST['collected_by'];

    $stmt = $conn->prepare("INSERT INTO collections (tortoise_id, collection_date, source_type, location, initial_health, notes, collected_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssi", $tortoise_id, $date, $source, $location, $health, $notes, $collected_by);
    if ($stmt->execute()) {
        header("Location: ../pages/collectingOfficer.php?msg=collection_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>