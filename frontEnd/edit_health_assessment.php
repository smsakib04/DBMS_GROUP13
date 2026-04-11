<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../backEnd/config/db.php';
 
// ── HANDLE POST: Save the updated assessment ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int)$_POST['assessment_id'];
    $tortoise_id = (int)$_POST['tortoise_id'];
    $vet_id      = !empty($_POST['vet_id']) ? (int)$_POST['vet_id'] : null;
    $code        = $_POST['assessment_code'] ?? '';
    $date        = $_POST['assessment_date'];
    $condition   = $_POST['health_condition'] ?? '';
    $diagnosis   = $_POST['diagnosis'] ?? '';
    $treatment   = $_POST['treatment'] ?? '';
    $remarks     = $_POST['remarks'] ?? '';
    $next        = !empty($_POST['next_checkup_date']) ? $_POST['next_checkup_date'] : null;
 
    $stmt = $conn->prepare("
        UPDATE health_assessments
        SET assessment_code=?, assessment_date=?, tortoise_id=?, vet_id=?,
            health_condition=?, diagnosis=?, treatment=?, remarks=?, next_checkup_date=?
        WHERE assessment_id=?
    ");
    $stmt->bind_param("ssiisssssi",
        $code, $date, $tortoise_id, $vet_id,
        $condition, $diagnosis, $treatment, $remarks, $next, $id
    );
 
    if ($stmt->execute()) {
        header("Location: veterenian.php?msg=updated");
    } else {
        header("Location: veterenian.php?msg=error");
    }
    exit();
}
 
// ── HANDLE GET: Load existing record and show form ────────────────
$id = (int)($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: veterenian.php");
    exit();
}
 
$stmt = $conn->prepare("SELECT * FROM health_assessments WHERE assessment_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
 
if (!$row) {
    header("Location: veterenian.php");
    exit();
}
 
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
$vet_list      = $conn->query("SELECT staff_id, full_name FROM staff WHERE role = 'veterinarian'");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Assessment | TCCMS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f8f4e8; font-family: Arial, sans-serif; display: flex; justify-content: center; padding: 30px 16px; }
        .card { background: white; border-radius: 12px; padding: 32px; width: 100%; max-width: 700px; box-shadow: 0 2px 16px rgba(0,0,0,.08); }
        .card-header { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #e0ece4; }
        .card-header h1 { font-size: 22px; color: #0a2a3a; }
        .card-header p  { font-size: 13px; color: #666; margin-top: 4px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .full { grid-column: 1 / -1; }
        label { display: block; font-size: 12px; font-weight: 700; color: #555; text-transform: uppercase; margin-bottom: 5px; }
        input, select, textarea { width: 100%; padding: 9px 12px; border: 1.5px solid #ccc; border-radius: 6px; font-size: 14px; background: #fafcfb; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #1a5a7a; }
        textarea { min-height: 80px; resize: vertical; }
        .actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e0ece4; }
        .btn { padding: 10px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; display: inline-block; }
        .btn-cancel { background: #e0e0e0; color: #333; }
        .btn-save { background: #1a5a7a; color: white; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <div style="font-size:32px">🩺</div>
        <div>
            <h1>Edit Health Assessment</h1>
            <p>Updating: <strong><?= htmlspecialchars($row['assessment_code']) ?></strong></p>
        </div>
    </div>
 
    <form method="POST" action="edit_health_assessment.php">
        <input type="hidden" name="assessment_id" value="<?= $row['assessment_id'] ?>">
 
        <div class="form-grid">
            <div>
                <label>Assessment Code</label>
                <input type="text" name="assessment_code" value="<?= htmlspecialchars($row['assessment_code']) ?>" required>
            </div>
            <div>
                <label>Assessment Date</label>
                <input type="date" name="assessment_date" value="<?= $row['assessment_date'] ?>" required>
            </div>
            <div>
                <label>Select Tortoise</label>
                <select name="tortoise_id" required>
                    <option value="">— Select Tortoise —</option>
                    <?php while($t = $tortoise_list->fetch_assoc()): ?>
                    <option value="<?= $t['tortoise_id'] ?>" <?= ($t['tortoise_id'] == $row['tortoise_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['name']) ?> (<?= $t['microchip_id'] ?>)
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label>Veterinarian</label>
                <select name="vet_id">
                    <option value="">— Select Vet (optional) —</option>
                    <?php while($v = $vet_list->fetch_assoc()): ?>
                    <option value="<?= $v['staff_id'] ?>" <?= ($v['staff_id'] == $row['vet_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($v['full_name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="full">
                <label>Health Condition</label>
                <input type="text" name="health_condition" value="<?= htmlspecialchars($row['health_condition'] ?? '') ?>">
            </div>
            <div class="full">
                <label>Diagnosis</label>
                <textarea name="diagnosis"><?= htmlspecialchars($row['diagnosis'] ?? '') ?></textarea>
            </div>
            <div class="full">
                <label>Treatment</label>
                <textarea name="treatment"><?= htmlspecialchars($row['treatment'] ?? '') ?></textarea>
            </div>
            <div class="full">
                <label>Remarks</label>
                <textarea name="remarks"><?= htmlspecialchars($row['remarks'] ?? '') ?></textarea>
            </div>
            <div>
                <label>Next Checkup Date</label>
                <input type="date" name="next_checkup_date" value="<?= $row['next_checkup_date'] ?? '' ?>">
            </div>
        </div>
 
        <div class="actions">
            <a href="veterenian.php" class="btn btn-cancel">Cancel</a>
            <button type="submit" class="btn btn-save">Save Changes</button>
        </div>
    </form>
</div>
</body>
</html>