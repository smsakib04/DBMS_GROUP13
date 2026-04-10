<?php
// ─────────────────────────────────────────────────────────────────
//  UPDATE (part 1) — Show the pre-filled Edit Assessment form
//  Called by: veterenian.php (Edit button) with ?id=assessment_id
//  Submits to: process/update_assessment.php
// ─────────────────────────────────────────────────────────────────
 
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';
 
// ── 1. Get the assessment_id from the URL ────────────────────────
$id = (int) ($_GET['id'] ?? 0);
 
if ($id === 0) {
    header("Location: veterenian.php");
    exit();
}
 
// ── 2. Load the existing assessment record ───────────────────────
$stmt = $conn->prepare("
    SELECT
        ha.assessment_id,
        ha.assessment_code,
        ha.assessment_date,
        ha.health_condition,
        ha.diagnosis,
        ha.treatment,
        ha.remarks,
        ha.next_checkup_date,
        ha.tortoise_id,
        ha.vet_id
    FROM health_assessments ha
    WHERE ha.assessment_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
 
// If no record found, redirect back
if (!$row) {
    header("Location: veterenian.php");
    exit();
}
 
// ── 3. Load tortoise list for the dropdown ───────────────────────
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Assessment | TCCMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',system-ui,sans-serif; }
        body { background:#ecf6f1; display:flex; justify-content:center; padding:2rem 1rem; }
        .card { background:#fff; border-radius:20px; padding:2rem;
                width:100%; max-width:750px;
                box-shadow:0 2px 16px rgba(0,40,20,0.08); }
        .card-header { display:flex; align-items:center; gap:1rem; margin-bottom:1.5rem;
                       padding-bottom:1rem; border-bottom:2px solid #e4f3ec; }
        .card-header i { font-size:1.6rem; color:#2a7f5c; }
        .card-header h1 { font-size:1.3rem; color:#1c5d44; }
        .card-header p  { color:#5a7a6e; font-size:0.85rem; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .full-width { grid-column:1 / -1; }
        label { display:block; font-size:0.75rem; font-weight:700; color:#5a7a6e;
                margin-bottom:4px; text-transform:uppercase; letter-spacing:0.05em; }
        input, select, textarea {
            width:100%; padding:0.65rem 0.9rem; border:1.5px solid #c2e0d2;
            border-radius:8px; font-size:0.9rem; background:#fafcfb; color:#1a1a1a;
        }
        input:focus, select:focus, textarea:focus {
            outline:none; border-color:#2a7f5c; background:#fff;
        }
        textarea { resize:vertical; min-height:90px; }
        .form-actions { display:flex; gap:0.8rem; margin-top:1.5rem; justify-content:flex-end; }
        .btn { padding:0.6rem 1.4rem; border-radius:40px; cursor:pointer;
               font-size:0.88rem; font-weight:600; border:1.5px solid #cae5d9;
               background:#fff; color:#1c5d44; }
        .btn:hover { background:#c2e0d2; }
        .btn-primary { background:#2a7f5c; color:#fff; border-color:#2a7f5c; }
        .btn-primary:hover { background:#1c5d44; }
        .field-group { margin-top:0.8rem; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <i class="fas fa-edit"></i>
        <div>
            <h1>Edit Health Assessment</h1>
            <p>Update the details for assessment
               <strong><?= htmlspecialchars($row['assessment_code'] ?? '#'.$row['assessment_id']) ?></strong>
            </p>
        </div>
    </div>
 
    <!-- Posts to process/update_assessment.php -->
    <form method="POST" action="../process/update_assessment.php">
 
        <!-- Hidden: pass the assessment_id so the UPDATE knows which row to change -->
        <input type="hidden" name="assessment_id" value="<?= $row['assessment_id'] ?>">
 
        <div class="form-grid">
 
            <div class="field-group">
                <label for="assessment_code">Assessment ID</label>
                <input type="text" id="assessment_code" name="assessment_code"
                       value="<?= htmlspecialchars($row['assessment_code'] ?? '') ?>"
                       placeholder="e.g. ASS-2025-001" required>
            </div>
 
            <div class="field-group">
                <label for="assessment_date">Date</label>
                <input type="date" id="assessment_date" name="assessment_date"
                       value="<?= $row['assessment_date'] ?>" required>
            </div>
 
            <div class="field-group">
                <label for="tortoise_id">Tortoise</label>
                <select id="tortoise_id" name="tortoise_id" required>
                    <option value="">— Select tortoise —</option>
                    <?php while($t = $tortoise_list->fetch_assoc()): ?>
                    <option value="<?= $t['tortoise_id'] ?>"
                        <?= ($t['tortoise_id'] == $row['tortoise_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['microchip_id'] . ' — ' . $t['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
 
            <div class="field-group">
                <label for="health_condition">Health Condition</label>
                <input type="text" id="health_condition" name="health_condition"
                       value="<?= htmlspecialchars($row['health_condition'] ?? '') ?>"
                       placeholder="e.g. Healthy, Critical">
            </div>
 
            <div class="field-group full-width">
                <label for="diagnosis">Diagnosis</label>
                <textarea id="diagnosis" name="diagnosis"
                          placeholder="Describe diagnosis..."><?= htmlspecialchars($row['diagnosis'] ?? '') ?></textarea>
            </div>
 
            <div class="field-group full-width">
                <label for="treatment">Treatment</label>
                <textarea id="treatment" name="treatment"
                          placeholder="Describe treatment..."><?= htmlspecialchars($row['treatment'] ?? '') ?></textarea>
            </div>
 
            <div class="field-group full-width">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks"
                          placeholder="Additional remarks..."><?= htmlspecialchars($row['remarks'] ?? '') ?></textarea>
            </div>
 
            <div class="field-group">
                <label for="next_checkup_date">Next Checkup Date</label>
                <input type="date" id="next_checkup_date" name="next_checkup_date"
                       value="<?= $row['next_checkup_date'] ?? '' ?>">
            </div>
 
        </div>
 
        <div class="form-actions">
            <a href="veterenian.php">
                <button type="button" class="btn">Cancel</button>
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Assessment
            </button>
        </div>
    </form>
</div>
</body>
</html>