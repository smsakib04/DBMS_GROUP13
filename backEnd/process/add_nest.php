<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['nest_code'];
    $pair_id = $_POST['pair_id'];
    $nesting_date = $_POST['nesting_date'];
    $egg_count = $_POST['egg_count'];
    $fertile = $_POST['fertile_eggs'];
    $incubator_id = $_POST['incubator_id'];
    $est_hatch = $_POST['estimated_hatch_date'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO nests (nest_code, pair_id, nesting_date, egg_count, fertile_eggs, incubator_id, estimated_hatch_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisiiiss", $code, $pair_id, $nesting_date, $egg_count, $fertile, $incubator_id, $est_hatch, $notes);
    if ($stmt->execute()) {
        header("Location: ../pages/breeding.php?msg=nest_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>