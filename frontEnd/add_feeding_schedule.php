<?php
session_start();
require_once '../backEnd/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tortoise_id = $_POST['tortoise_id'];
    $time = $_POST['feeding_time'];
    $food = $_POST['food_type'];
    $amount = $_POST['amount_grams'];
    $date = $_POST['scheduled_date'];
    $feeder_id = $_POST['feeder_id'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO feeding_schedules 
        (tortoise_id, feeding_time, food_type, amount_grams, scheduled_date, feeder_id, notes) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("issdiss", $tortoise_id, $time, $food, $amount, $date, $feeder_id, $notes);

    if ($stmt->execute()) {
        header("Location: feeder.php?msg=feeding_added");
        exit();
    } else {
        die("Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Feeding Schedule</title>
</head>
<body>

<div class="form-card">
    <h2>🍽️ Schedule Feeding</h2>

    <!-- Submit to same file -->
    <form method="POST">
        <label>Tortoise ID</label>
        <input type="number" name="tortoise_id" required>

        <label>Feeding Time</label>
        <input type="time" name="feeding_time" required>

        <label>Food Type</label>
        <input type="text" name="food_type" required>

        <label>Amount (grams)</label>
        <input type="number" step="0.01" name="amount_grams">

        <label>Scheduled Date</label>
        <input type="date" name="scheduled_date" required>

        <label>Feeder ID (staff)</label>
        <input type="number" name="feeder_id">

        <label>Notes</label>
        <textarea name="notes"></textarea>

        <button type="submit">Schedule</button>
    </form>
</div>

</body>
</html>