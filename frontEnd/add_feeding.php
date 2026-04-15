<?php
// ─────────────────────────────────────────────
//  CREATE — Add a new feeding schedule entry
//  Called by: careTaker.php (Add Feeding form)
//  Location:  frontEnd/add_feeding.php
// ─────────────────────────────────────────────
session_start();
require_once '../backEnd/config/db.php';
require_once '../backEnd/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('careTaker.php?tab=feeding');
}

$feeder_id      = $_SESSION['staff_id'];
$tortoise_id    = (int)$_POST['tortoise_id'];
$food_type      = sanitizeInput($_POST['food_type']);
$feeding_time   = sanitizeInput($_POST['feeding_time']);
$amount_grams   = !empty($_POST['amount_grams']) ? (float)$_POST['amount_grams'] : null;
$scheduled_date = sanitizeInput($_POST['scheduled_date']);
$notes          = sanitizeInput($_POST['notes']);

if ($tortoise_id === 0 || empty($food_type) || empty($feeding_time)) {
    redirect('careTaker.php?tab=feeding&msg=error');
}

$stmt = $conn->prepare("
    INSERT INTO feeding_schedules
        (tortoise_id, feeding_time, food_type, amount_grams, is_done, scheduled_date, feeder_id, notes)
    VALUES (?, ?, ?, ?, 0, ?, ?, ?)
");
$stmt->bind_param("issdsis",
    $tortoise_id, $feeding_time, $food_type, $amount_grams,
    $scheduled_date, $feeder_id, $notes
);

if ($stmt->execute()) {
    redirect('careTaker.php?tab=feeding&msg=feed_added');
} else {
    redirect('careTaker.php?tab=feeding&msg=error');
}
?>