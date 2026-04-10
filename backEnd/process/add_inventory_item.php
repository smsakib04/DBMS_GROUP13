<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['item_name'];
    $category = $_POST['category'];
    $qty = $_POST['quantity'];
    $unit = $_POST['unit'];
    $reorder = $_POST['reorder_level'];
    $supplier = $_POST['supplier'];
    $last_upd = $_POST['last_updated'];
    $managed_by = $_POST['managed_by'];

    $stmt = $conn->prepare("INSERT INTO inventory (item_name, category, quantity, unit, reorder_level, supplier, last_updated, managed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssdssdsi", $name, $category, $qty, $unit, $reorder, $supplier, $last_upd, $managed_by);
    if ($stmt->execute()) {
        header("Location: ../pages/supervisor.php?msg=inventory_added");
    } else {
        die("Error: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}
?>