
Copy

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../backEnd/config/db.php';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $assessment_code  = $_POST['assessment_code'] ?? '';
    $assessment_date  = $_POST['assessment_date'] ?? '';
    $tortoise_id      = (int)($_POST['tortoise_id'] ?? 0);
    $vet_id           = !empty($_POST['vet_id']) ? (int)$_POST['vet_id'] : null;
    $health_condition = $_POST['health_condition'] ?? '';
    $diagnosis        = $_POST['diagnosis'] ?? '';
    $treatment        = $_POST['treatment'] ?? '';
    $remarks          = $_POST['remarks'] ?? '';
    $next_checkup     = !empty($_POST['next_checkup_date']) ? $_POST['next_checkup_date'] : null;
 
    $stmt = $conn->prepare("
        INSERT INTO health_assessments
            (assessment_code, assessment_date, tortoise_id, vet_id, health_condition, diagnosis, treatment, remarks, next_checkup_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssiisssss",
        $assessment_code,
        $assessment_date,
        $tortoise_id,
        $vet_id,
        $health_condition,
        $diagnosis,
        $treatment,
        $remarks,
        $next_checkup
    );
 
    if ($stmt->execute()) {
        header("Location: veterenian.php?msg=added");
    } else {
        header("Location: veterenian.php?msg=error");
    }
    exit();
}
?>