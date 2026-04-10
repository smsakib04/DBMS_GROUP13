<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $microchip = $_POST['microchip_id'] ?? null;
    $name = $_POST['name'] ?? null;
    $species_id = $_POST['species_id'] ?? null;
    $sex = $_POST['sex'] ?? 'Unknown';
    $age = $_POST['estimated_age_years'] ?? null;
    $weight = $_POST['weight_grams'] ?? null;
    $health = $_POST['health_status'] ?? 'Healthy';
    $enclosure_id = $_POST['enclosure_id'] ?? null;
    $acq_date = $_POST['acquisition_date'] ?? null;
    $acq_source = $_POST['acquisition_source'] ?? 'Bred in captivity';
    $notes = $_POST['notes'] ?? null;

    $stmt = $conn->prepare("INSERT INTO tortoises (microchip_id, name, species_id, sex, estimated_age_years, weight_grams, health_status, enclosure_id, acquisition_date, acquisition_source, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisiddssss", $microchip, $name, $species_id, $sex, $age, $weight, $health, $enclosure_id, $acq_date, $acq_source, $notes);

    if ($stmt->execute()) {
        header("Location: ../pages/caretaker.php?msg=tortoise_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>