<?php
// ─────────────────────────────────────────────────────────────────
//  UPDATE (part 2) — Save the edited assessment to the database
//  Called by: formEditAssessment.php (Update Assessment button)
//  Redirects back to: veterenian.php with success/error message
// ─────────────────────────────────────────────────────────────────
 
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
 
// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../pages/veterenian.php');
}
 
// ── 1. Get the assessment_id (was hidden in the form) ────────────
$assessment_id = (int) $_POST['assessment_id'];
 
if ($assessment_id === 0) {
    redirect('../pages/veterenian.php?msg=error');
}
 
// ── 2. Collect and sanitize form data ────────────────────────────
$assessment_code   = sanitizeInput($_POST['assessment_code']);
$assessment_date   = sanitizeInput($_POST['assessment_date']);
$tortoise_id       = (int) $_POST['tortoise_id'];
$health_condition  = sanitizeInput($_POST['health_condition']);
$diagnosis         = sanitizeInput($_POST['diagnosis']);
$treatment         = sanitizeInput($_POST['treatment']);
$remarks           = sanitizeInput($_POST['remarks']);
$next_checkup_date = !empty($_POST['next_checkup_date'])
                       ? sanitizeInput($_POST['next_checkup_date'])
                       : null;
 
// ── 3. UPDATE the record in health_assessments ───────────────────
$stmt = $conn->prepare("
    UPDATE health_assessments
    SET
        assessment_code   = ?,
        assessment_date   = ?,
        tortoise_id       = ?,
        health_condition  = ?,
        diagnosis         = ?,
        treatment         = ?,
        remarks           = ?,
        next_checkup_date = ?
    WHERE assessment_id   = ?
");
 
// s=string, i=integer
// assessment_code(s), assessment_date(s), tortoise_id(i),
// health_condition(s), diagnosis(s), treatment(s), remarks(s),
// next_checkup_date(s), assessment_id(i)
$stmt->bind_param(
    "ssisssssi",
    $assessment_code,
    $assessment_date,
    $tortoise_id,
    $health_condition,
    $diagnosis,
    $treatment,
    $remarks,
    $next_checkup_date,
    $assessment_id
);
 
if ($stmt->execute()) {
    redirect('../pages/veterenian.php?msg=updated');
} else {
    redirect('../pages/veterenian.php?msg=error');
}
?>