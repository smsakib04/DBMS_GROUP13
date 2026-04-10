<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $microchip = $_POST['microchip_id'];
    $name = $_POST['name'];
    $species_id = $_POST['species_id'];
    $sex = $_POST['sex'];
    $age = $_POST['estimated_age_years'];
    $weight = $_POST['weight_grams'];
    $health = $_POST['health_status'];
    $enclosure_id = $_POST['enclosure_id'];
    $acquisition_date = $_POST['acquisition_date'];
    $acquisition_source = $_POST['acquisition_source'];

    $stmt = $conn->prepare("INSERT INTO tortoises (microchip_id, name, species_id, sex, estimated_age_years, weight_grams, health_status, enclosure_id, acquisition_date, acquisition_source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisidsiss", $microchip, $name, $species_id, $sex, $age, $weight, $health, $enclosure_id, $acquisition_date, $acquisition_source);

    if ($stmt->execute()) {
        redirect("../pages/caretaker.php?msg=added");
    } else {
        die("Error: " . $stmt->error);
    }
}
?>