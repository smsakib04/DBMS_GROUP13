<?php
// ─────────────────────────────────────────────────────────────
//  ADD OBSERVATION — Standalone form page
//  GET  → shows the form
//  POST → inserts into observations, redirects to careTaker.php
//  Location: frontEnd/add_observation.php
// ─────────────────────────────────────────────────────────────
session_start();
require_once '../backEnd/includes/session.php';
require_once '../backEnd/config/db.php';
require_once '../backEnd/includes/functions.php';

// ── Handle POST: save and redirect back ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observer_id      = $_SESSION['staff_id'];
    $tortoise_id      = (int)$_POST['tortoise_id'];
    $enclosure_id     = !empty($_POST['enclosure_id']) ? (int)$_POST['enclosure_id'] : null;
    $observation_date = sanitizeInput($_POST['observation_date']);
    $category         = sanitizeInput($_POST['category']);
    $description      = sanitizeInput($_POST['description']);

    if ($tortoise_id === 0 || empty($description)) {
        redirect('add_observation.php?error=1');
    }

    $stmt = $conn->prepare("
        INSERT INTO observations
            (tortoise_id, enclosure_id, observer_id, observation_date, category, description)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiisss",
        $tortoise_id, $enclosure_id, $observer_id,
        $observation_date, $category, $description
    );

    if ($stmt->execute()) {
        redirect('careTaker.php?msg=obs_added');
    } else {
        redirect('add_observation.php?error=1');
    }
}

// ── GET: load dropdowns and show the form ────────────────────
$today         = date('Y-m-d');
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
$enclosures    = $conn->query("SELECT enclosure_id, enclosure_code, enclosure_name FROM enclosures WHERE status='Active'");
$error         = isset($_GET['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log Observation | TCCMS</title>
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
  background: #1b4332;
  height: 56px;
  display: flex;
  align-items: center;
  padding: 0 2rem;
  gap: 1rem;
}
.back-btn {
  display: inline-flex; align-items: center; gap: .4rem;
  color: rgba(255,255,255,.75); font-size: .85rem;
  text-decoration: none; font-weight: 600;
  padding: .35rem .9rem; border-radius: 999px;
  border: 1.5px solid rgba(255,255,255,.2);
  transition: all .18s;
}
.back-btn:hover { background: rgba(255,255,255,.12); color: #fff; }
.topbar-title {
  font-family: 'DM Serif Display', serif;
  color: #b7e4c7; font-size: 1rem; margin-left: .4rem;
}
.page {
  flex: 1;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 2.5rem 1rem 3rem;
}
.card {
  background: #fff;
  border-radius: 22px;
  padding: 2.2rem 2rem;
  width: 100%;
  max-width: 680px;
  box-shadow: 0 4px 32px rgba(27,67,50,.10);
}
.card-head {
  display: flex; align-items: center; gap: 1rem;
  margin-bottom: 1.8rem;
  padding-bottom: 1.2rem;
  border-bottom: 2px solid #b7e4c7;
}
.card-head-icon {
  width: 48px; height: 48px;
  background: #d1fae5;
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; color: #065f46;
  flex-shrink: 0;
}
.card-head h1 {
  font-family: 'DM Serif Display', serif;
  font-size: 1.35rem; color: #1b4332;
}
.card-head p { font-size: .83rem; color: #4a5568; margin-top: .2rem; }
.alert-err {
  background: #fee2e2; color: #991b1b;
  border-radius: 10px; padding: .75rem 1rem;
  font-size: .85rem; font-weight: 600;
  display: flex; align-items: center; gap: .5rem;
  margin-bottom: 1.2rem;
}
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.span2 { grid-column: 1 / -1; }
@media(max-width: 540px) {
  .form-grid { grid-template-columns: 1fr; }
  .span2 { grid-column: 1; }
}
.fg { display: flex; flex-direction: column; gap: .35rem; }
.fg label {
  font-size: .74rem; font-weight: 700;
  color: #4a5568; text-transform: uppercase;
  letter-spacing: .05em;
}
.fg input, .fg select, .fg textarea {
  width: 100%;
  padding: .65rem .9rem;
  border: 1.5px solid #c8d8ce;
  border-radius: 10px;
  font-size: .9rem;
  font-family: 'DM Sans', sans-serif;
  background: #fafcfb;
  color: #1a1a1a;
  transition: border-color .18s, box-shadow .18s;
}
.fg input:focus, .fg select:focus, .fg textarea:focus {
  outline: none;
  border-color: #52b788;
  box-shadow: 0 0 0 3px rgba(82,183,136,.15);
  background: #fff;
}
.fg textarea { resize: vertical; min-height: 120px; }
.req { color: #dc2626; margin-left: 2px; }
.helper { font-size: .75rem; color: #6b7280; margin-top: .2rem; }
.form-actions {
  display: flex; gap: .8rem;
  justify-content: flex-end;
  margin-top: 1.5rem;
  padding-top: 1.2rem;
  border-top: 1px solid #edf1f0;
}
.btn {
  display: inline-flex; align-items: center; gap: .45rem;
  padding: .6rem 1.4rem; border-radius: 999px;
  border: none; cursor: pointer; font-weight: 600;
  font-size: .88rem; font-family: 'DM Sans', sans-serif;
  transition: all .18s; text-decoration: none;
}
.btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.10); }
.btn-primary { background: #1b4332; color: #fff; }
.btn-primary:hover { background: #2d6a4f; }
.btn-light { background: #ede8dc; color: #1b4332; }
.btn-light:hover { background: #c8d8ce; }
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
        <i class="fas fa-clipboard-list"></i>
      </div>
      <div>
        <h1>Log Observation</h1>
        <p>Record what you observed — it will appear instantly in the observations log.</p>
      </div>
    </div>

    <?php if($error): ?>
    <div class="alert-err">
      <i class="fas fa-exclamation-circle"></i>
      Please fill in all required fields correctly and try again.
    </div>
    <?php endif; ?>

    <form method="POST" action="add_observation.php">
      <div class="form-grid">

        <div class="fg">
          <label>Tortoise <span class="req">*</span></label>
          <select name="tortoise_id" required>
            <option value="">— Select a tortoise —</option>
            <?php while($t = $tortoise_list->fetch_assoc()): ?>
            <option value="<?= $t['tortoise_id'] ?>">
              <?= htmlspecialchars($t['microchip_id'] . ' — ' . $t['name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="fg">
          <label>Category</label>
          <select name="category">
            <option value="general">General</option>
            <option value="behavior">Behavior</option>
            <option value="health">Health</option>
            <option value="feeding">Feeding</option>
            <option value="nesting">Nesting</option>
          </select>
        </div>

        <div class="fg">
          <label>Observation Date <span class="req">*</span></label>
          <input type="date" name="observation_date" value="<?= $today ?>" required>
        </div>

        <div class="fg">
          <label>Enclosure <small style="font-weight:400;text-transform:none">(optional)</small></label>
          <select name="enclosure_id">
            <option value="">— None —</option>
            <?php while($enc = $enclosures->fetch_assoc()): ?>
            <option value="<?= $enc['enclosure_id'] ?>">
              <?= htmlspecialchars($enc['enclosure_code'] . ' — ' . $enc['enclosure_name']) ?>
            </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="fg span2">
          <label>Description <span class="req">*</span></label>
          <textarea name="description"
            placeholder="Describe what you observed in detail — behavior, physical condition, eating habits, movement, anything unusual…"
            required></textarea>
          <span class="helper">Be as specific as possible. This record goes directly into the observations log on the dashboard.</span>
        </div>

      </div>

      <div class="form-actions">
        <a href="careTaker.php" class="btn btn-light">
          <i class="fas fa-times"></i> Cancel
        </a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Observation
        </button>
      </div>
    </form>

  </div>
</div>

</body>
</html>