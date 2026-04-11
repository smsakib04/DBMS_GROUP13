<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
require_once '../includes/session.php';
// requireLogin();
require_once '../config/db.php';
 
$tortoises     = $conn->query("SELECT t.tortoise_id, t.microchip_id, t.name, t.estimated_age_years, t.sex, t.health_status, s.common_name FROM tortoises t JOIN species s ON t.species_id = s.species_id");
$assessments   = $conn->query("SELECT h.assessment_id, h.assessment_code, h.assessment_date, t.name AS tortoise_name, h.diagnosis, h.treatment, h.remarks FROM health_assessments h JOIN tortoises t ON h.tortoise_id = t.tortoise_id ORDER BY h.assessment_date DESC");
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
$vet_list      = $conn->query("SELECT staff_id, full_name FROM staff WHERE role = 'veterinarian'");
 
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Veterinarian Portal | TCCMS</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f8f4e8; font-family: Arial, sans-serif; }
        .page-header { background: linear-gradient(135deg,#0a2a3a,#1a5a7a); color: white; padding: 24px 32px; display: flex; align-items: center; gap: 16px; }
        .page-header h1 { font-size: 26px; }
        .page-header p  { opacity: .8; font-size: 14px; }
        .tabs { display: flex; background: #0a2a3a; padding: 0 20px; }
        .tab { color: #cde8d8; padding: 14px 24px; cursor: pointer; border-bottom: 3px solid transparent; font-size: 14px; font-weight: 600; }
        .tab.active { color: white; border-bottom-color: #52b788; }
        .tab-content { display: none; padding: 24px; }
        .tab-content.active { display: block; }
        .card { background: white; border-radius: 10px; padding: 24px; border: 1px solid #dde8e0; box-shadow: 0 2px 8px rgba(0,0,0,.05); margin-bottom: 20px; }
        .card h2 { font-size: 18px; color: #0a2a3a; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 2px solid #e0ece4; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; font-size: 13px; border-bottom: 1px solid #e4ece6; }
        th { background: #1a5a7a; color: white; }
        tr:nth-child(even) td { background: #f5faf7; }
        label { display: block; font-size: 12px; font-weight: 700; color: #555; text-transform: uppercase; margin: 12px 0 4px; }
        input, select, textarea { width: 100%; padding: 9px 12px; border: 1.5px solid #ccc; border-radius: 6px; font-size: 14px; background: #fafcfb; }
        textarea { min-height: 70px; resize: vertical; }
        .btn { display: inline-block; padding: 9px 20px; background: #1a5a7a; color: white; border: none; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 14px; text-decoration: none; }
        .btn:hover { background: #0a2a3a; }
        .btn-danger { background: #c0392b; }
        .btn-success { background: #27ae60; }
        .alert { padding: 12px 18px; border-radius: 6px; margin: 0 24px 0; font-weight: 600; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error   { background: #f8d7da; color: #721c24; }
        .two-col { display: grid; grid-template-columns: 1fr 1.4fr; gap: 24px; }
        @media(max-width:700px){ .two-col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
 
<div class="page-header">
    <div style="font-size:40px">🩺</div>
    <div>
        <h1>Veterinarian Portal</h1>
        <p>Tortoise Conservation &amp; Health Management System</p>
    </div>
</div>
 
<div class="tabs">
    <div class="tab active" onclick="switchTab('records', this)">📋 Health Records</div>
    <div class="tab" onclick="switchTab('assessment', this)">🩺 New Assessment</div>
</div>
 
<?php if ($msg === 'added'):   ?><div class="alert alert-success" style="margin:16px 24px">✅ Assessment saved successfully!</div><?php endif; ?>
<?php if ($msg === 'updated'): ?><div class="alert alert-success" style="margin:16px 24px">✅ Assessment updated successfully!</div><?php endif; ?>
<?php if ($msg === 'deleted'): ?><div class="alert alert-success" style="margin:16px 24px">✅ Assessment deleted successfully.</div><?php endif; ?>
<?php if ($msg === 'error'):   ?><div class="alert alert-error"   style="margin:16px 24px">❌ Something went wrong. Please try again.</div><?php endif; ?>
 
<!-- TAB: HEALTH RECORDS -->
<div id="tab-records" class="tab-content active">
    <div class="card">
        <h2>📋 Registered Tortoises</h2>
        <table>
            <thead>
                <tr><th>Microchip ID</th><th>Name</th><th>Species</th><th>Age</th><th>Sex</th><th>Health Status</th></tr>
            </thead>
            <tbody>
            <?php while($row = $tortoises->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['microchip_id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['common_name']) ?></td>
                    <td><?= $row['estimated_age_years'] ?> yrs</td>
                    <td><?= $row['sex'] ?></td>
                    <td><?= $row['health_status'] ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
 
<!-- TAB: NEW ASSESSMENT -->
<div id="tab-assessment" class="tab-content">
    <div class="two-col">
 
        <!-- ADD FORM -->
        <div class="card">
            <h2>🩺 Perform Health Assessment</h2>
            <form action="../process/add_assessment.php" method="POST">
 
                <label>Assessment Code</label>
                <input type="text" name="assessment_code" placeholder="e.g. ASS-2026-002" required>
 
                <label>Assessment Date</label>
                <input type="date" name="assessment_date" value="<?= date('Y-m-d') ?>" required>
 
                <label>Select Tortoise</label>
                <select name="tortoise_id" required>
                    <option value="">— Select Tortoise —</option>
                    <?php while($t = $tortoise_list->fetch_assoc()): ?>
                    <option value="<?= $t['tortoise_id'] ?>"><?= htmlspecialchars($t['name']) ?> (<?= $t['microchip_id'] ?>)</option>
                    <?php endwhile; ?>
                </select>
 
                <label>Veterinarian</label>
                <select name="vet_id">
                    <option value="">— Select Vet (optional) —</option>
                    <?php while($v = $vet_list->fetch_assoc()): ?>
                    <option value="<?= $v['staff_id'] ?>"><?= htmlspecialchars($v['full_name']) ?></option>
                    <?php endwhile; ?>
                </select>
 
                <label>Health Condition</label>
                <input type="text" name="health_condition" placeholder="e.g. Healthy, Critical">
 
                <label>Diagnosis</label>
                <textarea name="diagnosis" placeholder="Describe diagnosis..."></textarea>
 
                <label>Treatment</label>
                <textarea name="treatment" placeholder="Describe treatment..."></textarea>
 
                <label>Remarks</label>
                <textarea name="remarks" placeholder="Additional remarks..."></textarea>
 
                <label>Next Checkup Date</label>
                <input type="date" name="next_checkup_date">
 
                <button type="submit" class="btn btn-success">✅ Submit Assessment</button>
            </form>
        </div>
 
        <!-- RECENT ASSESSMENTS TABLE -->
        <div class="card">
            <h2>📄 Recent Assessments</h2>
            <table>
                <thead>
                    <tr><th>Code</th><th>Date</th><th>Tortoise</th><th>Diagnosis</th><th>Remarks</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php while($row = $assessments->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['assessment_code']) ?></td>
                        <td><?= $row['assessment_date'] ?></td>
                        <td><?= htmlspecialchars($row['tortoise_name']) ?></td>
                        <td><?= htmlspecialchars($row['diagnosis'] ?? '') ?></td>
                        <td><?= htmlspecialchars($row['remarks'] ?? '') ?></td>
                        <td>
                            <a href="formEditAssessment.php?id=<?= $row['assessment_id'] ?>">
                                <button class="btn" style="margin:0;padding:5px 10px">Edit</button>
                            </a>
                            <a href="../process/delete_assessment.php?id=<?= $row['assessment_id'] ?>" onclick="return confirm('Delete this assessment?')">
                                <button class="btn btn-danger" style="margin:0;padding:5px 10px;margin-top:4px">Delete</button>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
 
    </div>
</div>
 
<script>
function switchTab(name, el) {
    document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    el.classList.add('active');
}
</script>
</body>
</html>