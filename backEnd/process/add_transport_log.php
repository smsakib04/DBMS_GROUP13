<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tortoise_id = $_POST['tortoise_id'];
    $vehicle = $_POST['vehicle_id'];
    $from = $_POST['from_location'];
    $to = $_POST['to_location'];
    $date = $_POST['transport_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO transport_logs (tortoise_id, vehicle_id, from_location, to_location, transport_date, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $tortoise_id, $vehicle, $from, $to, $date, $status, $notes);
    if ($stmt->execute()) {
        header("Location: ../pages/collectingOfficer.php?msg=transport_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>