<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tortoise_id = $_POST['tortoise_id'];
    $time = $_POST['feeding_time'];
    $food = $_POST['food_type'];
    $amount = $_POST['amount_grams'];
    $date = $_POST['scheduled_date'];
    $feeder_id = $_POST['feeder_id'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO feeding_schedules (tortoise_id, feeding_time, food_type, amount_grams, scheduled_date, feeder_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdiss", $tortoise_id, $time, $food, $amount, $date, $feeder_id, $notes);
    if ($stmt->execute()) {
        header("Location: ../pages/feeder.php?msg=feeding_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>