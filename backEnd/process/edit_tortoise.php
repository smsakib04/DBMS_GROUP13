<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['tortoise_id'];
    $microchip = $_POST['microchip_id'];
    $name = $_POST['name'];
    $species_id = $_POST['species_id'];
    $sex = $_POST['sex'];
    $age = $_POST['estimated_age_years'];
    $weight = $_POST['weight_grams'];
    $health = $_POST['health_status'];
    $enclosure_id = $_POST['enclosure_id'];
    $acq_date = $_POST['acquisition_date'];
    $acq_source = $_POST['acquisition_source'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE tortoises SET microchip_id=?, name=?, species_id=?, sex=?, estimated_age_years=?, weight_grams=?, health_status=?, enclosure_id=?, acquisition_date=?, acquisition_source=?, notes=? WHERE tortoise_id=?");
    $stmt->bind_param("ssisiddssssi", $microchip, $name, $species_id, $sex, $age, $weight, $health, $enclosure_id, $acq_date, $acq_source, $notes, $id);

    if ($stmt->execute()) {
        header("Location: ../pages/caretaker.php?msg=tortoise_updated");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>