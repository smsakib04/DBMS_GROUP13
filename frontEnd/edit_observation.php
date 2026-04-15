<?php
// ─────────────────────────────────────────────────────────────
//  EDIT OBSERVATION — Standalone form page
//  GET  → loads existing record and shows pre-filled form
//  POST → runs UPDATE, redirects to careTaker.php
//  Location: frontEnd/edit_observation.php
// ─────────────────────────────────────────────────────────────
session_start();
require_once '../backEnd/includes/session.php';
require_once '../backEnd/config/db.php';
require_once '../backEnd/includes/functions.php';

$id = (int)($_GET['id'] ?? $_POST['observation_id'] ?? 0);
if ($id === 0) redirect('careTaker.php');

// ── Handle POST: UPDATE and redirect ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observation_id   = (int)$_POST['observation_id'];
    $tortoise_id      = (int)$_POST['tortoise_id'];
    $enclosure_id     = !empty($_POST['enclosure_id']) ? (int)$_POST['enclosure_id'] : null;
    $observation_date = sanitizeInput($_POST['observation_date']);
    $category         = sanitizeInput($_POST['category']);
    $description      = sanitizeInput($_POST['description']);

    if ($tortoise_id === 0 || empty($description)) {
        redirect('edit_observation.php?id='.$observation_id.'&error=1');
    }

    $stmt = $conn->prepare("
        UPDATE observations
        SET tortoise_id      = ?,
            enclosure_id     = ?,
            observation_date = ?,
            category         = ?,
            description      = ?
        WHERE observation_id = ?
    ");
    $stmt->bind_param("iisssi",
        $tortoise_id, $enclosure_id,
        $observation_date, $category, $description,
        $observation_id
    );

    if ($stmt->execute()) {
        redirect('careTaker.php?msg=obs_updated');
    } else {
        redirect('edit_observation.php?id='.$observation_id.'&error=1');
    }
}

// ── GET: load existing record ─────────────────────────────────
$stmt = $conn->prepare("
    SELECT o.*, t.name AS tortoise_name
    FROM observations o
    LEFT JOIN tortoises t ON o.tortoise_id = t.tortoise_id
    WHERE o.observation_id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) redirect('careTaker.php');

$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
$enclosures    = $conn->query("SELECT enclosure_id, enclosure_code, enclosure_name FROM enclosures WHERE status='Active'");
$error         = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Observation | TCCMS</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body {
  font-family:'DM Sans', sans-serif;
  background: #f8f6f0;
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}
.topbar {
  background: #1b4332; height: 56px;
  display: flex; align-items: center; padding: 0 2rem; gap: 1rem;
}
.back-btn {
  display:inline-flex; align-items:center; gap:.4rem;
  color:rgba(255,255,255,.75); font-size:.85rem;
  text-decoration:none; font-weight:600;
  padding:.35rem .9rem; border-radius:999px;
  border:1.5px solid rgba(255,255,255,.2); transition:all .18s;
}
.back-btn:hover { background:rgba(255,255,255,.12); color:#fff; }
.topbar-title { font-family:'DM Serif Display',serif; color:#b7e4c7; font-size:1rem; margin-left:.4rem; }
.page { flex:1; display:flex; align-items:flex-start; justify-content:center; padding:2.5rem 1rem 3rem; }
.card {
  background:#fff; border-radius:22px; padding:2.2rem 2rem;
  width:100%; max-width:680px; box-shadow:0 4px 32px rgba(27,67,50,.10);
}
.card-head {
  display:flex; align-items:center; gap:1rem;
  margin-bottom:1.8rem; padding-bottom:1.2rem; border-bottom:2px solid #b7e4c7;
}
.card-head-icon {
  width:48px; height:48px; background:#fef3c7;
  border-radius:14px; display:flex; align-items:center; justify-content:center;
  font-size:1.3rem; color:#92400e; flex-shrink:0;
}
.card-head h1 { font-family:'DM Serif Display',serif; font-size:1.35rem; color:#1b4332; }
.card-head p  { font-size:.83rem; color:#4a5568; margin-top:.2rem; }
.record-ref {
  background:#f5fbf8; border:1.5px solid #b7e4c7;
  border-radius:10px; padding:.7rem 1rem;
  font-size:.82rem; color:#2d6a4f; margin-bottom:1.2rem;
  display:flex; align-items:center; gap:.5rem;
}
.alert-err {
  background:#fee2e2; color:#991b1b; border-radius:10px;
  padding:.75rem 1rem; font-size:.85rem; font-weight:600;
  display:flex; align-items:center; gap:.5rem; margin-bottom:1.2rem;
}
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:1rem; }
.span2 { grid-column:1/-1; }
@media(max-width:540px){ .form-grid{grid-template-columns:1fr;} .span2{grid-column:1;} }
.fg { display:flex; flex-direction:column; gap:.35rem; }
.fg label { font-size:.74rem; font-weight:700; color:#4a5568; text-transform:uppercase; letter-spacing:.05em; }
.fg input, .fg select, .fg textarea {
  width:100%; padding:.65rem .9rem; border:1.5px solid #c8d8ce;
  border-radius:10px; font-size:.9rem; font-family:'DM Sans',sans-serif;
  background:#fafcfb; color:#1a1a1a; transition:border-color .18s,box-shadow .18s;
}
.fg input:focus, .fg select:focus, .fg textarea:focus {
  outline:none; border-color:#52b788; box-shadow:0 0 0 3px rgba(82,183,136,.15); background:#fff;
}
.fg textarea { resize:vertical; min-height:120px; }
.req { color:#dc2626; margin-left:2px; }
.helper { font-size:.75rem; color:#6b7280; margin-top:.2rem; }
.form-actions {
  display:flex; gap:.8rem; justify-content:flex-end;
  margin-top:1.5rem; padding-top:1.2rem; border-top:1px solid #edf1f0;
}
.btn {
  display:inline-flex; align-items:center; gap:.45rem;
  padding:.6rem 1.4rem; border-radius:999px; border:none; cursor:pointer;
  font-weight:600; font-size:.88rem; font-family:'DM Sans',sans-serif;
  transition:all .18s; text-decoration:none;
}
.btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.10); }
.btn-primary { background:#1b4332; color:#fff; }
.btn-primary:hover { background:#2d6a4f; }
.btn-light { background:#ede8dc; color:#1b4332; }
.btn-light:hover { background:#c8d8ce; }
</style>
</head>
<body>

<div class="topbar">
  <a href="careTaker.php" class="back-btn">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
  </a>
  <span class="topbar-title">TCCMS · Caretaker</span>
</div>

<div class="page">
  <div class="card">

    <div class="card-head">
      <div class="card-head-icon">
        <i class="fas fa-pen-to-square"></i>
      </div>
      <div>
        <h1>Edit Observation</h1>
        <p>Update the observation record for <strong><?= htmlspecialchars($row['tortoise_name'] ?? 'this tortoise') ?></strong></p>
      </div>
    </div>

    <!-- Info strip showing which record is being edited -->
    <div class="record-ref">
      <i class="fas fa-info-circle"></i>
      Editing observation #<?= $row['observation_id'] ?> &nbsp;·&nbsp;
      Originally logged on <strong><?= $row['observation_date'] ?></strong>
    </div>

    <?php if($error): ?>
    <div class="alert-err">
      <i class="fas fa-exclamation-circle"></i>
      Please fill in all required fields correctly and try again.
    </div>
    <?php endif; ?>

    <form method="POST" action="edit_observation.php">
      <!-- Hidden: pass the ID so UPDATE knows which row to change -->
      <input type="hidden" name="observation_id" value="<?= $row['observation_id'] ?>">

      <div class="form-grid">

        <div class="fg">
          <label>Tortoise <span class="req">*</span></label>
          <select name="tortoise_id" required>
            <option value="">— Select a tortoise —</option>
            <?php while($t = $tortoise_list->fetch_assoc()): ?>
            <option value="<?= $t['tortoise_id'] ?>"
              <?= $t['tortoise_id'] == $row['tortoise_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['microchip_id'] . ' — ' . $t['name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="fg">
          <label>Category</label>
          <select name="category">
            <?php foreach(['general','behavior','health','feeding','nesting'] as $cat): ?>
            <option value="<?= $cat ?>" <?= $cat === $row['category'] ? 'selected' : '' ?>>
              <?= ucfirst($cat) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="fg">
          <label>Observation Date <span class="req">*</span></label>
          <input type="date" name="observation_date"
                 value="<?= $row['observation_date'] ?>" required>
        </div>

        <div class="fg">
          <label>Enclosure <small style="font-weight:400;text-transform:none">(optional)</small></label>
          <select name="enclosure_id">
            <option value="">— None —</option>
            <?php while($enc = $enclosures->fetch_assoc()): ?>
            <option value="<?= $enc['enclosure_id'] ?>"
              <?= $enc['enclosure_id'] == $row['enclosure_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($enc['enclosure_code'] . ' — ' . $enc['enclosure_name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="fg span2">
          <label>Description <span class="req">*</span></label>
          <textarea name="description" required><?= htmlspecialchars($row['description']) ?></textarea>
          <span class="helper">Update the description with any corrections or additional details.</span>
        </div>

      </div>

      <div class="form-actions">
        <a href="careTaker.php" class="btn btn-light">
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