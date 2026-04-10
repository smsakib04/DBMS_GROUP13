<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['schedule_id'];
    $done = $_POST['is_done'];

    $stmt = $conn->prepare("UPDATE feeding_schedules SET is_done = ? WHERE schedule_id = ?");
    $stmt->bind_param("ii", $done, $id);
    if ($stmt->execute()) {
        header("Location: ../pages/feeder.php?msg=status_updated");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>