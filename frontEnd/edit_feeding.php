<?php
// ─────────────────────────────────────────────────────────────
//  EDIT FEEDING — Standalone form page
//  GET  → loads existing feeding record, shows pre-filled form
//  POST → runs UPDATE, redirects to careTaker.php (feeding tab)
//  Location: frontEnd/edit_feeding.php
// ─────────────────────────────────────────────────────────────
session_start();
require_once '../backEnd/includes/session.php';
require_once '../backEnd/config/db.php';
require_once '../backEnd/includes/functions.php';
 
$id = (int)($_GET['id'] ?? $_POST['schedule_id'] ?? 0);
if ($id === 0) redirect('careTaker.php?tab=feeding');
 
// ── Handle POST: UPDATE and redirect ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id    = (int)$_POST['schedule_id'];
    $tortoise_id    = (int)$_POST['tortoise_id'];
    $food_type      = sanitizeInput($_POST['food_type']);
    $feeding_time   = sanitizeInput($_POST['feeding_time']);
    $amount_grams   = !empty($_POST['amount_grams']) ? (float)$_POST['amount_grams'] : null;
    $scheduled_date = sanitizeInput($_POST['scheduled_date']);
    $is_done        = isset($_POST['is_done']) ? 1 : 0;
    $notes          = sanitizeInput($_POST['notes']);
 
    if ($tortoise_id === 0 || empty($food_type) || empty($feeding_time)) {
        redirect('edit_feeding.php?id='.$schedule_id.'&error=1');
    }
 
    $stmt = $conn->prepare("
        UPDATE feeding_schedules
        SET tortoise_id    = ?,
            food_type      = ?,
            feeding_time   = ?,
            amount_grams   = ?,
            scheduled_date = ?,
            is_done        = ?,
            notes          = ?
        WHERE schedule_id  = ?
    ");
    $stmt->bind_param("issdsiis",
        $tortoise_id, $food_type, $feeding_time,
        $amount_grams, $scheduled_date, $is_done, $notes,
        $schedule_id
    );
 
    if ($stmt->execute()) {
        redirect('careTaker.php?tab=feeding&msg=feed_updated');
    } else {
        redirect('edit_feeding.php?id='.$schedule_id.'&error=1');
    }
}
 
// ── GET: load existing record ─────────────────────────────────
$stmt = $conn->prepare("
    SELECT fs.*, t.name AS tortoise_name, t.microchip_id
    FROM feeding_schedules fs
    JOIN tortoises t ON fs.tortoise_id = t.tortoise_id
    WHERE fs.schedule_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) redirect('careTaker.php?tab=feeding');
 
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
$error         = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Feeding Entry | TCCMS</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body { font-family:'DM Sans',sans-serif; background:#f8f6f0; min-height:100vh; display:flex; flex-direction:column; }
.topbar { background:#1b4332; height:56px; display:flex; align-items:center; padding:0 2rem; gap:1rem; }
.back-btn {
  display:inline-flex; align-items:center; gap:.4rem; color:rgba(255,255,255,.75);
  font-size:.85rem; text-decoration:none; font-weight:600; padding:.35rem .9rem;
  border-radius:999px; border:1.5px solid rgba(255,255,255,.2); transition:all .18s;
}
.back-btn:hover { background:rgba(255,255,255,.12); color:#fff; }
.topbar-title { font-family:'DM Serif Display',serif; color:#b7e4c7; font-size:1rem; margin-left:.4rem; }
.page { flex:1; display:flex; align-items:flex-start; justify-content:center; padding:2.5rem 1rem 3rem; }
.card { background:#fff; border-radius:22px; padding:2.2rem 2rem; width:100%; max-width:680px; box-shadow:0 4px 32px rgba(27,67,50,.10); }
.card-head { display:flex; align-items:center; gap:1rem; margin-bottom:1.8rem; padding-bottom:1.2rem; border-bottom:2px solid #b7e4c7; }
.card-head-icon { width:48px; height:48px; background:#ecfdf5; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; color:#065f46; flex-shrink:0; }
.card-head h1 { font-family:'DM Serif Display',serif; font-size:1.35rem; color:#1b4332; }
.card-head p  { font-size:.83rem; color:#4a5568; margin-top:.2rem; }
.record-ref { background:#f5fbf8; border:1.5px solid #b7e4c7; border-radius:10px; padding:.7rem 1rem; font-size:.82rem; color:#2d6a4f; margin-bottom:1.2rem; display:flex; align-items:center; gap:.5rem; }
.alert-err { background:#fee2e2; color:#991b1b; border-radius:10px; padding:.75rem 1rem; font-size:.85rem; font-weight:600; display:flex; align-items:center; gap:.5rem; margin-bottom:1.2rem; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.span2 { grid-column:1/-1; }
@media(max-width:540px){ .form-grid{grid-template-columns:1fr;} .span2{grid-column:1;} }
.fg { display:flex; flex-direction:column; gap:.35rem; }
.fg label { font-size:.74rem; font-weight:700; color:#4a5568; text-transform:uppercase; letter-spacing:.05em; }
.fg input, .fg select, .fg textarea {
  width:100%; padding:.65rem .9rem; border:1.5px solid #c8d8ce; border-radius:10px;
  font-size:.9rem; font-family:'DM Sans',sans-serif; background:#fafcfb; color:#1a1a1a;
  transition:border-color .18s,box-shadow .18s;
}
.fg input:focus, .fg select:focus, .fg textarea:focus { outline:none; border-color:#52b788; box-shadow:0 0 0 3px rgba(82,183,136,.15); background:#fff; }
/* Checkbox row */
.checkbox-row { display:flex; align-items:center; gap:.6rem; padding:.55rem 0; }
.checkbox-row input[type=checkbox] { width:18px; height:18px; accent-color:#1b4332; cursor:pointer; }
.checkbox-row label { font-size:.88rem; font-weight:600; color:#1b4332; cursor:pointer; text-transform:none; letter-spacing:0; }
.req { color:#dc2626; margin-left:2px; }
.helper { font-size:.75rem; color:#6b7280; margin-top:.2rem; }
.form-actions { display:flex; gap:.8rem; justify-content:flex-end; margin-top:1.5rem; padding-top:1.2rem; border-top:1px solid #edf1f0; }
.btn { display:inline-flex; align-items:center; gap:.45rem; padding:.6rem 1.4rem; border-radius:999px; border:none; cursor:pointer; font-weight:600; font-size:.88rem; font-family:'DM Sans',sans-serif; transition:all .18s; text-decoration:none; }
.btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.10); }
.btn-primary { background:#1b4332; color:#fff; }
.btn-primary:hover { background:#2d6a4f; }
.btn-light { background:#ede8dc; color:#1b4332; }
.btn-light:hover { background:#c8d8ce; }
</style>
</head>
<body>
 
<div class="topbar">
  <a href="careTaker.php?tab=feeding" class="back-btn">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
  </a>
  <span class="topbar-title">TCCMS · Caretaker</span>
</div>
 
<div class="page">
  <div class="card">
 
    <div class="card-head">
      <div class="card-head-icon">
        <i class="fas fa-utensils"></i>
      </div>
      <div>
        <h1>Edit Feeding Entry</h1>
        <p>Updating schedule for <strong><?= htmlspecialchars($row['tortoise_name']) ?></strong> (<?= htmlspecialchars($row['microchip_id']) ?>)</p>
      </div>
    </div>
 
    <div class="record-ref">
      <i class="fas fa-info-circle"></i>
      Schedule #<?= $row['schedule_id'] ?> &nbsp;·&nbsp;
      Originally set for <strong><?= substr($row['feeding_time'],0,5) ?></strong>
      on <strong><?= $row['scheduled_date'] ?></strong>
    </div>
 
    <?php if($error): ?>
    <div class="alert-err">
      <i class="fas fa-exclamation-circle"></i>
      Please fill in all required fields correctly and try again.
    </div>
    <?php endif; ?>
 
    <form method="POST" action="edit_feeding.php">
      <input type="hidden" name="schedule_id" value="<?= $row['schedule_id'] ?>">
 
      <div class="form-grid">
 
        <div class="fg">
          <label>Tortoise <span class="req">*</span></label>
          <select name="tortoise_id" required>
            <option value="">— Select —</option>
            <?php while($t = $tortoise_list->fetch_assoc()): ?>
            <option value="<?= $t['tortoise_id'] ?>"
              <?= $t['tortoise_id'] == $row['tortoise_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['microchip_id'] . ' — ' . $t['name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>
 
        <div class="fg">
          <label>Food Type <span class="req">*</span></label>
          <input type="text" name="food_type"
                 value="<?= htmlspecialchars($row['food_type']) ?>"
                 placeholder="e.g. Mixed Greens" required>
        </div>
 
        <div class="fg">
          <label>Feeding Time <span class="req">*</span></label>
          <input type="time" name="feeding_time"
                 value="<?= substr($row['feeding_time'],0,5) ?>" required>
        </div>
 
        <div class="fg">
          <label>Amount (grams)</label>
          <input type="number" name="amount_grams" step="0.01" min="0"
                 value="<?= $row['amount_grams'] ?? '' ?>"
                 placeholder="e.g. 500">
        </div>
 
        <div class="fg">
          <label>Scheduled Date</label>
          <input type="date" name="scheduled_date"
                 value="<?= $row['scheduled_date'] ?>">
        </div>
 
        <div class="fg" style="justify-content:flex-end">
          <label>Status</label>
          <div class="checkbox-row">
            <input type="checkbox" id="is_done" name="is_done"
                   <?= $row['is_done'] ? 'checked' : '' ?>>
            <label for="is_done"><i class="fas fa-check-circle" style="color:#065f46"></i> Mark as Fed</label>
          </div>
          <span class="helper">Check this if the feeding has been completed.</span>
        </div>
 
        <div class="fg span2">
          <label>Notes <small style="font-weight:400;text-transform:none">(optional)</small></label>
          <input type="text" name="notes"
                 value="<?= htmlspecialchars($row['notes'] ?? '') ?>"
                 placeholder="Any special notes about this feeding…">
        </div>
 
      </div>
 
      <div class="form-actions">
        <a href="careTaker.php?tab=feeding" class="btn btn-light">
          <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </form>
 
  </div>
</div>
 
</body>
</html>
