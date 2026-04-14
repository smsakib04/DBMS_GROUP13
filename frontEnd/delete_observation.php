<?php
// ─────────────────────────────────────────────
//  DELETE — Remove an observation
//  Called by: careTaker.php (Delete button)
//  Location:  frontEnd/delete_observation.php
// ─────────────────────────────────────────────
session_start();
require_once '../backEnd/config/db.php';
require_once '../backEnd/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id === 0) redirect('careTaker.php?msg=error');

$stmt = $conn->prepare("DELETE FROM observations WHERE observation_id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    redirect('careTaker.php?msg=obs_deleted');
} else {
    redirect('careTaker.php?msg=error');
}
?>