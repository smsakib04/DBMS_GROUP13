<?php
// ─────────────────────────────────────────────
//  DELETE — Remove a feeding schedule entry
//  Called by: careTaker.php (trash button)
//  Location:  frontEnd/delete_feeding.php
// ─────────────────────────────────────────────
session_start();
require_once '../backEnd/config/db.php';
require_once '../backEnd/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) redirect('careTaker.php?tab=feeding&msg=error');

$stmt = $conn->prepare("DELETE FROM feeding_schedules WHERE schedule_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect('careTaker.php?tab=feeding&msg=feed_deleted');
} else {
    redirect('careTaker.php?tab=feeding&msg=error');
}
?>