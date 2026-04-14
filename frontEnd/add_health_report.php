<?php
// ─────────────────────────────────────────────
//  CREATE — Caretaker reports a health issue to vet
//  Inserts into health_assessments table.
//  The vet_id is set to the first available veterinarian
//  (or you can hard-code the assigned vet's staff_id).
//  Called by: careTaker.php (Report Health Issue form)
//  Location:  frontEnd/add_health_report.php
// ─────────────────────────────────────────────
session_start();
require_once '../backEnd/config/db.php';
require_once '../backEnd/includes/functions.php';
 
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('careTaker.php?tab=health');
}
 
// Get the first available veterinarian as the assigned vet
$vet_row = $conn->query("SELECT staff_id FROM staff WHERE role='veterinarian' AND status='active' LIMIT 1")->fetch_assoc();
$vet_id  = $vet_row ? $vet_row['staff_id'] : 7; // fallback to staff_id 7 (Dr. James Wilson from sample data)
 
$assessment_code  = sanitizeInput($_POST['assessment_code']);
$assessment_date  = sanitizeInput($_POST['assessment_date']);
$tortoise_id      = (int)$_POST['tortoise_id'];
$health_condition = sanitizeInput($_POST['health_condition']);
$diagnosis        = sanitizeInput($_POST['diagnosis']);
$remarks          = sanitizeInput($_POST['remarks']);
 
if (empty($assessment_code) || $tortoise_id === 0 || empty($diagnosis)) {
    redirect('careTaker.php?tab=health&msg=error');
}
 
$stmt = $conn->prepare("
    INSERT INTO health_assessments
        (assessment_code, tortoise_id, vet_id, assessment_date,
         health_condition, diagnosis, remarks)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("siissss",
    $assessment_code, $tortoise_id, $vet_id,
    $assessment_date, $health_condition, $diagnosis, $remarks
);
 
if ($stmt->execute()) {
    redirect('careTaker.php?tab=health&msg=report_sent');
} else {
    redirect('careTaker.php?tab=health&msg=error');
}
?>
 