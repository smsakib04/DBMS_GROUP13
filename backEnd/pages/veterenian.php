<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';
 
// ─── READ: Fetch all tortoises for the top table ───────────────────────────
$tortoises = $conn->query("
    SELECT
        t.tortoise_id,
        t.microchip_id,
        t.name,
        t.estimated_age_years,
        t.sex,
        t.health_status,
        t.classification,
        s.common_name AS species_name
    FROM tortoises t
    JOIN species s ON t.species_id = s.species_id
    ORDER BY t.tortoise_id ASC
");
 
// ─── READ: Fetch all health assessments for the bottom table ──────────────
$assessments = $conn->query("
    SELECT
        ha.assessment_id,
        ha.assessment_code,
        ha.assessment_date,
        ha.health_condition,
        ha.diagnosis,
        ha.treatment,
        ha.remarks,
        ha.next_checkup_date,
        t.name      AS tortoise_name,
        t.microchip_id,
        st.full_name AS vet_name
    FROM health_assessments ha
    JOIN tortoises t  ON ha.tortoise_id = t.tortoise_id
    JOIN staff     st ON ha.vet_id      = st.staff_id
    ORDER BY ha.assessment_date DESC
");
 
// ─── For the Add Assessment form: dropdown list of tortoises ──────────────
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
 
// ─── Success/error message passed after a process file redirects back ─────
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veterinarian | TCCMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ── Base ── */
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',system-ui,sans-serif; }
        body { background:#ecf6f1; padding:2rem 1.5rem; }
        .dashboard { max-width:1400px; margin:0 auto; }
 
        /* ── Header ── */
        .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; }
        .page-header h1 { font-size:1.6rem; color:#1c5d44; }
        .page-header p  { color:#5a7a6e; font-size:0.9rem; margin-top:4px; }
        .date-badge { background:#2a7f5c; color:#fff; padding:0.5rem 1.2rem; border-radius:50px; font-size:0.85rem; }
 
        /* ── Navbar ── */
        .navbar { background:#fff; border-radius:60px; padding:0.8rem 2rem;
                  display:flex; justify-content:space-between; align-items:center;
                  margin-bottom:2rem; box-shadow:0 2px 12px rgba(0,40,20,0.08); }
        .nav-links { display:flex; gap:1.8rem; }
        .nav-item  { cursor:pointer; display:flex; align-items:center; gap:0.5rem;
                     color:#2b6e53; font-weight:600; padding-bottom:3px;
                     border-bottom:3px solid transparent; font-size:0.9rem; }
        .nav-item.active { border-bottom-color:#2a8b65; }
        .logout-btn { background:#f0faf5; border:1.5px solid #c2e0d2;
                      padding:0.5rem 1.2rem; border-radius:40px; cursor:pointer;
                      font-size:0.85rem; color:#1c5d44; font-weight:600; }
        .logout-btn:hover { background:#c2e0d2; }
 
        /* ── Notification banner ── */
        .alert { padding:0.8rem 1.2rem; border-radius:10px; margin-bottom:1.2rem;
                 font-weight:600; font-size:0.9rem; }
        .alert-success { background:#d1fae5; color:#065f46; }
        .alert-error   { background:#fee2e2; color:#991b1b; }
 
        /* ── Section cards ── */
        .table-wrapper { background:#fff; border-radius:28px; padding:1.8rem;
                         margin-bottom:2.5rem; display:none;
                         box-shadow:0 2px 12px rgba(0,40,20,0.06); }
        .table-wrapper.active-table { display:block; }
        .table-header  { display:flex; justify-content:space-between; align-items:center;
                         margin-bottom:1.4rem; flex-wrap:wrap; gap:0.8rem; }
        .table-header h2 { font-size:1.1rem; color:#1c5d44; }
 
        /* ── Buttons ── */
        .btn          { padding:0.5rem 1.2rem; border-radius:40px; cursor:pointer;
                        font-size:0.85rem; font-weight:600; border:1.5px solid #cae5d9;
                        background:#fff; color:#1c5d44; transition:background 0.2s; }
        .btn:hover    { background:#c2e0d2; }
        .btn-primary  { background:#2a7f5c; color:#fff; border-color:#2a7f5c; }
        .btn-primary:hover { background:#1c5d44; }
        .btn-danger   { background:#fee2e2; color:#991b1b; border-color:#fca5a5; }
        .btn-danger:hover { background:#fca5a5; }
        .btn-sm       { padding:0.3rem 0.8rem; font-size:0.78rem; }
 
        /* ── Table ── */
        table { width:100%; border-collapse:collapse; font-size:0.88rem; }
        th    { padding:0.7rem 0.6rem; border-bottom:2px solid #c2e0d2;
                text-align:left; color:#2b6e53; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.04em; }
        td    { padding:0.75rem 0.6rem; border-bottom:1px solid #e4f3ec; color:#1a1a1a; }
        tr:hover td { background:#f5fbf8; }
        .badge { padding:0.2rem 0.8rem; border-radius:50px; font-size:0.78rem; font-weight:600; }
        .badge-healthy    { background:#d1fae5; color:#065f46; }
        .badge-critical   { background:#fee2e2; color:#991b1b; }
        .badge-observing  { background:#fef3c7; color:#92400e; }
        .badge-recovering { background:#dbeafe; color:#1e40af; }
 
        /* ── Add Assessment Form ── */
        .form-card { background:#f5fbf8; border:1.5px solid #c2e0d2; border-radius:16px;
                     padding:1.5rem; margin-bottom:1.5rem; display:none; }
        .form-card.open { display:block; }
        .form-card h3 { color:#1c5d44; margin-bottom:1rem; font-size:1rem; }
        .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
        .form-grid .full-width { grid-column:1 / -1; }
        label { display:block; font-size:0.78rem; font-weight:600; color:#5a7a6e;
                margin-bottom:4px; text-transform:uppercase; letter-spacing:0.04em; }
        input, select, textarea {
            width:100%; padding:0.6rem 0.8rem; border:1.5px solid #c2e0d2;
            border-radius:8px; font-size:0.88rem; background:#fff; color:#1a1a1a;
        }
        input:focus, select:focus, textarea:focus {
            outline:none; border-color:#2a7f5c;
        }
        textarea { resize:vertical; min-height:80px; }
        .form-actions { display:flex; gap:0.8rem; margin-top:1rem; justify-content:flex-end; }
    </style>
</head>
<body>
<div class="dashboard">
 
    <!-- ── Page header ── -->
    <div class="page-header">
        <div>
            <h1><i class="fas fa-stethoscope"></i> Veterinarian Portal</h1>
            <p>Health assessments, medical records, tortoise health management</p>
        </div>
        <div class="date-badge"><?php echo date('d M Y'); ?> · Veterinarian</div>
    </div>
 
    <!-- ── Navbar ── -->
    <div class="navbar">
        <div class="nav-links" id="navLinks">
            <span class="nav-item active" data-table="tortoises">
                <i class="fas fa-list"></i> Tortoise Records
            </span>
            <span class="nav-item" data-table="assessments">
                <i class="fas fa-notes-medical"></i> Health Assessments
            </span>
        </div>
        <a href="../logout.php"><button class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button></a>
    </div>
 
    <!-- ── Flash message ── -->
    <?php if ($msg === 'added'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Assessment added successfully.</div>
    <?php elseif ($msg === 'updated'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Assessment updated successfully.</div>
    <?php elseif ($msg === 'deleted'): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> Assessment deleted successfully.</div>
    <?php elseif ($msg === 'error'): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> Something went wrong. Please try again.</div>
    <?php endif; ?>
 
    <!-- ════════════════════════════════════════════════════════════
         TABLE 1 — Tortoise Records  (READ + Edit link + Delete)
    ═══════════════════════════════════════════════════════════════ -->
    <div class="table-wrapper active-table" id="tortoises">
        <div class="table-header">
            <h2><i class="fas fa-list"></i> Tortoise Health Records</h2>
        </div>
 
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Microchip</th>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Gender</th>
                    <th>Health Status</th>
                    <th>Classification</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $tortoises->fetch_assoc()): ?>
                <tr>
                    <td><?= $row['tortoise_id'] ?></td>
                    <td><?= htmlspecialchars($row['microchip_id']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= $row['estimated_age_years'] ?? '—' ?> yrs</td>
                    <td><?= $row['sex'] ?></td>
                    <td>
                        <?php
                        $status = $row['health_status'];
                        $badge  = match($status) {
                            'Healthy'           => 'badge-healthy',
                            'Critical'          => 'badge-critical',
                            'Under observation' => 'badge-observing',
                            'Recovering'        => 'badge-recovering',
                            default             => ''
                        };
                        ?>
                        <span class="badge <?= $badge ?>"><?= $status ?></span>
                    </td>
                    <td><?= htmlspecialchars($row['classification'] ?? '—') ?></td>
                    <td>
                        <!-- Edit links to formEditTortoiseInfo.php passing tortoise_id -->
                        <a href="formEditTortoiseInfo.php?id=<?= $row['tortoise_id'] ?>">
                            <button class="btn btn-sm">Edit</button>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
 
    <!-- ════════════════════════════════════════════════════════════
         TABLE 2 — Health Assessments  (READ + Add form + Edit + Delete)
    ═══════════════════════════════════════════════════════════════ -->
    <div class="table-wrapper" id="assessments">
        <div class="table-header">
            <h2><i class="fas fa-notes-medical"></i> Health Assessments</h2>
            <button class="btn btn-primary" onclick="toggleForm()">
                <i class="fas fa-plus"></i> Add Assessment
            </button>
        </div>
 
        <!-- ── CREATE: Add Assessment Form ── -->
        <div class="form-card" id="addForm">
            <h3><i class="fas fa-plus-circle"></i> New Health Assessment</h3>
            <!-- Posts to process/add_assessment.php -->
            <form method="POST" action="../process/add_assessment.php">
                <div class="form-grid">
 
                    <div>
                        <label for="assessment_code">Assessment ID</label>
                        <input type="text" id="assessment_code" name="assessment_code"
                               placeholder="e.g. ASS-2025-001" required>
                    </div>
 
                    <div>
                        <label for="assessment_date">Date</label>
                        <input type="date" id="assessment_date" name="assessment_date" required>
                    </div>
 
                    <div>
                        <label for="tortoise_id">Tortoise</label>
                        <select id="tortoise_id" name="tortoise_id" required>
                            <option value="">— Select tortoise —</option>
                            <?php while($t = $tortoise_list->fetch_assoc()): ?>
                            <option value="<?= $t['tortoise_id'] ?>">
                                <?= htmlspecialchars($t['microchip_id'] . ' — ' . $t['name']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
 
                    <div>
                        <label for="health_condition">Health Condition</label>
                        <input type="text" id="health_condition" name="health_condition"
                               placeholder="e.g. Healthy, Critical">
                    </div>
 
                    <div class="full-width">
                        <label for="diagnosis">Diagnosis</label>
                        <textarea id="diagnosis" name="diagnosis"
                                  placeholder="Describe diagnosis..."></textarea>
                    </div>
 
                    <div class="full-width">
                        <label for="treatment">Treatment</label>
                        <textarea id="treatment" name="treatment"
                                  placeholder="Describe treatment plan..."></textarea>
                    </div>
 
                    <div class="full-width">
                        <label for="remarks">Remarks</label>
                        <textarea id="remarks" name="remarks"
                                  placeholder="Additional remarks..."></textarea>
                    </div>
 
                    <div>
                        <label for="next_checkup_date">Next Checkup Date</label>
                        <input type="date" id="next_checkup_date" name="next_checkup_date">
                    </div>
 
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="toggleForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Assessment</button>
                </div>
            </form>
        </div>
 
        <!-- ── READ: Assessments Table ── -->
        <table>
            <thead>
                <tr>
                    <th>Assess. ID</th>
                    <th>Date</th>
                    <th>Tortoise</th>
                    <th>Condition</th>
                    <th>Diagnosis</th>
                    <th>Treatment</th>
                    <th>Remarks</th>
                    <th>Next Checkup</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $assessments->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['assessment_code'] ?? '—') ?></td>
                    <td><?= $row['assessment_date'] ?></td>
                    <td><?= htmlspecialchars($row['tortoise_name']) ?></td>
                    <td><?= htmlspecialchars($row['health_condition'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['diagnosis'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['treatment'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($row['remarks'] ?? '—') ?></td>
                    <td><?= $row['next_checkup_date'] ?? '—' ?></td>
                    <td>
                        <!-- UPDATE: links to formEditAssessment.php -->
                        <a href="formEditAssessment.php?id=<?= $row['assessment_id'] ?>">
                            <button class="btn btn-sm">Edit</button>
                        </a>
                        <!-- DELETE: links to process/delete_assessment.php -->
                        <a href="../process/delete_assessment.php?id=<?= $row['assessment_id'] ?>"
                           onclick="return confirm('Delete this assessment?')">
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
 
</div><!-- end .dashboard -->
 
<script>
// ── Tab navigation ──────────────────────────────────────────────
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
        document.querySelectorAll('.table-wrapper').forEach(t => t.classList.remove('active-table'));
        this.classList.add('active');
        document.getElementById(this.dataset.table).classList.add('active-table');
    });
});
 
// ── Toggle Add Assessment form ───────────────────────────────────
function toggleForm() {
    document.getElementById('addForm').classList.toggle('open');
}
</script>
</body>
</html>