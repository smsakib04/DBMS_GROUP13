<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['task_name'];
    $assigned_to = $_POST['assigned_to'];
    $assigned_by = $_POST['assigned_by'];
    $due = $_POST['due_date'];
    $status = $_POST['status'];
    $notes = $_POST['completion_notes'];

    $stmt = $conn->prepare("INSERT INTO tasks (task_name, assigned_to, assigned_by, due_date, status, completion_notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisss", $name, $assigned_to, $assigned_by, $due, $status, $notes);
    if ($stmt->execute()) {
        header("Location: ../pages/supervisor.php?msg=task_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>