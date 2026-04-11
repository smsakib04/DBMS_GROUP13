<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../backEnd/config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['pair_code'];
    $male = $_POST['male_tortoise_id'];
    $female = $_POST['female_tortoise_id'];
    $date = $_POST['pairing_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO breeding_pairs (pair_code, male_tortoise_id, female_tortoise_id, pairing_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siisss", $code, $male, $female, $date, $status, $notes);

    if ($stmt->execute()) {
        echo "<p style='color:green;'>✅ Pair added successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Error: " . $stmt->error . "</p>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Breeding Pair</title>
</head>
<body>

<div class="form-card">
    <h2>🐢 Create Breeding Pair</h2>

    <!-- IMPORTANT CHANGE: action="" -->
    <form method="POST">
        <label>Pair Code</label>
        <input type="text" name="pair_code">

        <label>Male Tortoise ID</label>
        <input type="number" name="male_tortoise_id" required>

        <label>Female Tortoise ID</label>
        <input type="number" name="female_tortoise_id" required>

        <label>Pairing Date</label>
        <input type="date" name="pairing_date" required>

        <label>Status</label>
        <select name="status">
            <option>paired</option>
            <option>courting</option>
            <option>incubating</option>
            <option>hatched</option>
            <option>separated</option>
        </select>

        <label>Notes</label>
        <textarea name="notes" rows="2"></textarea>

        <button type="submit">Create Pair</button>
    </form>
</div>

</body>
</html>