<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';

$message = '';
$error = '';

// Get collection ID from URL
$collection_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($collection_id <= 0) {
    header('Location: collectingOfficer.php');
    exit();
}

// Fetch collection data
$collectionQuery = "
    SELECT c.*, t.tortoise_id, t.name as tortoise_name, t.species_id, t.sex, t.estimated_age_years, t.microchip_id,
           s.common_name as species_name
    FROM collections c
    LEFT JOIN tortoises t ON c.tortoise_id = t.tortoise_id
    LEFT JOIN species s ON t.species_id = s.species_id
    WHERE c.collection_id = ?
";
$stmt = $conn->prepare($collectionQuery);
$stmt->bind_param("i", $collection_id);
$stmt->execute();
$collection = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$collection) {
    header('Location: collectingOfficer.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tortoise_name = trim($_POST['tortoise_name'] ?? '');
    $species_name = trim($_POST['species'] ?? '');
    $estimated_age = intval($_POST['estimated_age'] ?? 0);
    $sex = $_POST['sex'] ?? 'Unknown';
    $source_type = $_POST['source_type'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $collection_date = $_POST['collection_date'] ?? '';
    $initial_health = $_POST['initial_health'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $collected_by = !empty($_POST['collected_by']) ? intval($_POST['collected_by']) : null;

    if (empty($species_name) || empty($collection_date) || empty($source_type) || empty($location) || empty($initial_health)) {
        $error = "All required fields must be filled.";
    } else {
        // Update or create species
        $species_id = null;
        $speciesStmt = $conn->prepare("SELECT species_id FROM species WHERE common_name LIKE ?");
        $likeName = "%$species_name%";
        $speciesStmt->bind_param("s", $likeName);
        $speciesStmt->execute();
        $speciesRes = $speciesStmt->get_result();
        
        if ($speciesRes->num_rows > 0) {
            $species_id = $speciesRes->fetch_assoc()['species_id'];
        } else {
            $insertSpecies = $conn->prepare("INSERT INTO species (common_name, scientific_name) VALUES (?, ?)");
            $scientific = "Unknown";
            $insertSpecies->bind_param("ss", $species_name, $scientific);
            if ($insertSpecies->execute()) {
                $species_id = $insertSpecies->insert_id;
            }
            $insertSpecies->close();
        }
        $speciesStmt->close();

        if ($species_id) {
            // Update tortoise
            $health_status = ($initial_health == 'Healthy') ? 'Healthy' : (($initial_health == 'Weak') ? 'Under observation' : 'Minor injury');
            
            $updateTortoise = $conn->prepare("UPDATE tortoises SET name = ?, species_id = ?, sex = ?, estimated_age_years = ?, health_status = ?, acquisition_source = ?, acquisition_date = ?, notes = ? WHERE tortoise_id = ?");
            $updateTortoise->bind_param("sisissssi", $tortoise_name, $species_id, $sex, $estimated_age, $health_status, $source_type, $collection_date, $notes, $collection['tortoise_id']);
            
            if ($updateTortoise->execute()) {
                // Update collection
                $updateCollection = $conn->prepare("UPDATE collections SET collection_date = ?, source_type = ?, location = ?, initial_health = ?, notes = ?, collected_by = ? WHERE collection_id = ?");
                $updateCollection->bind_param("sssssii", $collection_date, $source_type, $location, $initial_health, $notes, $collected_by, $collection_id);
                
                if ($updateCollection->execute()) {
                    $message = "Collection record updated successfully!";
                    // Refresh data
                    header("Refresh:2; url=edit_collectingOfficer.php?id=$collection_id");
                } else {
                    $error = "Error updating collection: " . $updateCollection->error;
                }
                $updateCollection->close();
            } else {
                $error = "Error updating tortoise: " . $updateTortoise->error;
            }
            $updateTortoise->close();
        } else {
            $error = "Could not determine or create species.";
        }
    }
}

// Fetch staff members for dropdown
$staffQuery = "SELECT staff_id, CONCAT(first_name, ' ', last_name) as full_name FROM staff WHERE role IN ('collecting_officer', 'field_worker')";
$staffResult = $conn->query($staffQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Collection - Collecting Officer</title>
    <link