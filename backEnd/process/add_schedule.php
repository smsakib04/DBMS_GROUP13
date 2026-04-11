<?php
// add_schedule.php - Insert a new task/schedule into the database
require_once 'config.php';

// Redirect back to the form with message
function redirectWithMessage($message, $type = 'error') {
    $encodedMsg = urlencode($message);
    header("Location: ../Frontend/add_schedule.html?msg=$encodedMsg&type=$type");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithMessage('Invalid request method.');
}

// Get and sanitize input
$task_name = trim($_POST['task_name'] ?? '');
$assigned_to = trim($_POST['assigned_to'] ?? '');
$due_date = trim($_POST['due_date'] ?? '');
$status = trim($_POST['status'] ?? 'Pending');
$completion_notes = trim($_POST['completion_notes'] ?? '');

// Validation
if (empty($task_name)) {
    redirectWithMessage('Task name is required.');
}
if (empty($assigned_to)) {
    redirectWithMessage('Assigned person is required.');
}
if (empty($due_date)) {
    redirectWithMessage('Due date is required.');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
    redirectWithMessage('Invalid date format. Use YYYY-MM-DD.');
}

// Insert into database
$sql = "INSERT INTO tasks (task_name, assigned_to, due_date, status, completion_notes) 
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $task_name, $assigned_to, $due_date, $status, $completion_notes);

if ($stmt->execute()) {
    redirectWithMessage('Schedule added successfully!', 'success');
} else {
    redirectWithMessage('Database error: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>