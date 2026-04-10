<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['task_id'];
    $status = $_POST['status'];
    $notes = $_POST['completion_notes'];

    $stmt = $conn->prepare("UPDATE tasks SET status = ?, completion_notes = ? WHERE task_id = ?");
    $stmt->bind_param("ssi", $status, $notes, $id);
    if ($stmt->execute()) {
        header("Location: ../pages/supervisor.php?msg=task_updated");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>