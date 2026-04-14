<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
require_once '../backEnd/includes/session.php';
// requireLogin();
require_once '../backEnd/config/db.php';
 
$tortoises     = $conn->query("SELECT t.tortoise_id, t.microchip_id, t.name, t.estimated_age_years, t.sex, t.health_status, s.common_name FROM tortoises t JOIN species s ON t.species_id = s.species_id ORDER BY t.name");
$assessments   = $conn->query("SELECT h.assessment_id, h.assessment_code, h.assessment_date, t.name AS tortoise_name, h.diagnosis, h.treatment, h.remarks FROM health_assessments h JOIN tortoises t ON h.tortoise_id = t.tortoise_id ORDER BY h.assessment_date DESC");
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
$vet_list      = $conn->query("SELECT staff_id, full_name FROM staff WHERE role = 'veterinarian'");
 
$health_result = $conn->query("SELECT health_status, COUNT(*) as count FROM tortoises GROUP BY health_status");
$chart_labels = []; $chart_data = [];
while ($h = $health_result->fetch_assoc()) {
    $chart_labels[] = $h['health_status'] ?: 'Unknown';
    $chart_data[]   = (int)$h['count'];
}
 
$bar_result = $conn->query("
    SELECT DATE_FORMAT(assessment_date,'%b %Y') as month_label,
           DATE_FORMAT(assessment_date,'%Y-%m') as month_sort,
           COUNT(*) as count
    FROM health_assessments
    WHERE assessment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month_sort, month_label ORDER BY month_sort ASC
");
$bar_labels = []; $bar_data = [];
while ($b = $bar_result->fetch_assoc()) { $bar_labels[] = $b['month_label']; $bar_data[] = (int)$b['count']; }
 
$upcoming = $conn->query("
    SELECT h.assessment_code, h.next_checkup_date, t.name AS tortoise_name,
           t.microchip_id, h.remarks, h.assessment_id,
           DATEDIFF(h.next_checkup_date, CURDATE()) as days_left
    FROM health_assessments h JOIN tortoises t ON h.tortoise_id = t.tortoise_id
    WHERE h.next_checkup_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY h.next_checkup_date ASC
");
$upcoming_rows = [];
while ($u = $upcoming->fetch_assoc()) $upcoming_rows[] = $u;
 
$total_tortoises   = $conn->query("SELECT COUNT(*) as c FROM tortoises")->fetch_assoc()['c'];
$healthy_count     = $conn->query("SELECT COUNT(*) as c FROM tortoises WHERE health_status='Healthy'")->fetch_assoc()['c'];
$observation_count = $conn->query("SELECT COUNT(*) as c FROM tortoises WHERE health_status='Under observation'")->fetch_assoc()['c'];
$critical_count    = $conn->query("SELECT COUNT(*) as c FROM tortoises WHERE health_status='Critical'")->fetch_assoc()['c'];
$total_assessments = $conn->query("SELECT COUNT(*) as c FROM health_assessments")->fetch_assoc()['c'];
 
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Veterinarian Portal — TCCMS</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
:root {
  --ink:      #0f1f2e;
  --ink2:     #2c4a5e;
  --slate:    #5a7a8e;
  --mist:     #e8eff4;
  --fog:      #f4f7fa;
  --paper:    #fafbfc;
  --white:    #ffffff;
  --accent:   #1a6b5a;
  --accent2:  #22897a;
  --gold:     #c9893a;
  --rose:     #c0434b;
  --sky:      #2a7ab8;
  --amber:    #c47d1a;
  --border:   #dce5ec;
  --r-sm:     10px;
  --r-md:     16px;
  --r-lg:     22px;
  --shadow-sm: 0 1px 4px rgba(15,31,46,.06);
  --shadow-md: 0 4px 20px rgba(15,31,46,.08);
  --shadow-lg: 0 12px 40px rgba(15,31,46,.12);
  --t: .18s ease;
}
 
* { box-sizing: border-box; margin: 0; padding: 0; }
html { font-size: 15px; }
body { font-family: 'DM Sans', sans-serif; background: var(--fog); color: var(--ink); min-height: 100vh; }
 
/* ── HEADER ─────────────────────────────────── */
.header {
  background: var(--ink);
  padding: 0 40px;
  display: flex;
  align-items: center;
  gap: 0;
  height: 68px;
  position: relative;
}
.header::after {
  content: '';
  position: absolute;
  bottom: 0; left: 0; right: 0;
  height: 2px;
  background: linear-gradient(90deg, var(--accent) 0%, var(--sky) 60%, transparent 100%);
}
.header-brand {
  display: flex;
  align-items: center;
  gap: 14px;
  text-decoration: none;
}
.header-logo {
  width: 36px; height: 36px;
  background: linear-gradient(135deg, var(--accent), var(--sky));
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: white;
  font-size: 16px;
  flex-shrink: 0;
}
.header-title {
  font-family: 'DM Serif Display', serif;
  font-size: 18px;
  color: white;
  letter-spacing: .3px;
}
.header-sub {
  font-size: 11px;
  color: rgba(255,255,255,.45);
  font-weight: 300;
  letter-spacing: .4px;
  margin-top: 1px;
}
.header-right {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 20px;
}
.header-date {
  font-size: 12px;
  color: rgba(255,255,255,.4);
  letter-spacing: .3px;
}
 
/* ── NAV TABS ────────────────────────────────── */
.nav {
  background: var(--white);
  border-bottom: 1px solid var(--border);
  padding: 0 40px;
  display: flex;
  gap: 0;
  box-shadow: var(--shadow-sm);
}
.nav-tab {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 0 22px;
  height: 52px;
  font-size: 13px;
  font-weight: 500;
  color: var(--slate);
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: color var(--t), border-color var(--t);
  white-space: nowrap;
  letter-spacing: .2px;
  user-select: none;
}
.nav-tab i { font-size: 14px; opacity: .7; }
.nav-tab:hover { color: var(--ink2); }
.nav-tab.active {
  color: var(--accent);
  border-bottom-color: var(--accent);
  font-weight: 600;
}
.nav-tab.active i { opacity: 1; }
 
/* ── ALERTS ─────────────────────────────────── */
.alert-wrap { padding: 16px 40px 0; }
.alert {
  padding: 13px 18px;
  border-radius: var(--r-sm);
  font-size: 13px;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 10px;
}
.alert-success { background: #edf7f2; color: #1a5c3a; border: 1px solid #b8e2ce; }
.alert-error   { background: #fdf1f1; color: #8c1f1f; border: 1px solid #f0b8b8; }
 
/* ── PANES ───────────────────────────────────── */
.pane { display: none; padding: 28px 40px 60px; }
.pane.active { display: block; }
 
/* ── STAT CARDS ──────────────────────────────── */
.stats {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 14px;
  margin-bottom: 26px;
}
.stat {
  background: var(--white);
  border-radius: var(--r-md);
  padding: 20px 20px 18px;
  border: 1px solid var(--border);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 10px;
  transition: box-shadow var(--t), transform var(--t);
}
.stat:hover { box-shadow: var(--shadow-md); transform: translateY(-1px); }
.stat-icon-wrap {
  width: 40px; height: 40px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 15px;
}
.si-teal   { background: #e5f3f0; color: #1a6b5a; }
.si-green  { background: #e6f4ea; color: #2e7d32; }
.si-amber  { background: #fef3e2; color: #c47d1a; }
.si-rose   { background: #fdedef; color: #c0434b; }
.si-sky    { background: #e3f0fb; color: #2a7ab8; }
.stat-num  { font-family: 'DM Serif Display', serif; font-size: 28px; color: var(--ink); line-height: 1; }
.stat-lbl  { font-size: 11px; color: var(--slate); text-transform: uppercase; letter-spacing: .7px; font-weight: 500; }
 
/* ── SECTION GRID ────────────────────────────── */
.grid-2-right { display: grid; grid-template-columns: 1fr 300px; gap: 20px; align-items: start; }
.grid-2-equal { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-form    { display: grid; grid-template-columns: 1fr 1.4fr; gap: 20px; }
 
/* ── CARD ────────────────────────────────────── */
.card {
  background: var(--white);
  border-radius: var(--r-lg);
  border: 1px solid var(--border);
  box-shadow: var(--shadow-md);
  overflow: hidden;
}
.card-head {
  padding: 20px 24px 16px;
  border-bottom: 1px solid var(--mist);
  display: flex;
  align-items: center;
  gap: 10px;
}
.card-head-icon {
  width: 34px; height: 34px;
  border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
  background: var(--fog);
  color: var(--ink2);
  flex-shrink: 0;
}
.card-head h2 {
  font-family: 'DM Serif Display', serif;
  font-size: 17px;
  color: var(--ink);
  font-weight: 400;
  letter-spacing: .2px;
}
.card-body { padding: 20px 24px; }
 
/* ── TABLE ───────────────────────────────────── */
.tbl { width: 100%; border-collapse: collapse; }
.tbl thead th {
  font-size: 10.5px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .7px;
  color: var(--slate);
  padding: 0 14px 10px;
  text-align: left;
  border-bottom: 1px solid var(--border);
}
.tbl tbody td {
  padding: 11px 14px;
  font-size: 13px;
  border-bottom: 1px solid var(--mist);
  color: var(--ink2);
}
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl tbody tr { transition: background var(--t); }
.tbl tbody tr:hover td { background: var(--fog); }
.mono {
  font-family: 'SF Mono', 'Fira Mono', monospace;
  font-size: 11.5px;
  color: var(--accent);
  background: #edf5f3;
  padding: 2px 7px;
  border-radius: 5px;
}
 
/* ── BADGES ──────────────────────────────────── */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: .3px;
}
.badge::before { content:''; width:6px; height:6px; border-radius:50%; }
.b-healthy    { background:#e6f4ea; color:#2e7d32; } .b-healthy::before    { background:#2e7d32; }
.b-obs        { background:#fef3e2; color:#c47d1a; } .b-obs::before        { background:#c47d1a; }
.b-critical   { background:#fdedef; color:#c0434b; } .b-critical::before   { background:#c0434b; }
.b-recovering { background:#e3f0fb; color:#2a7ab8; } .b-recovering::before { background:#2a7ab8; }
.b-injury     { background:#f3eef9; color:#7b4fa6; } .b-injury::before     { background:#7b4fa6; }
 
/* ── SEARCH ──────────────────────────────────── */
.search-wrap {
  position: relative;
  margin-bottom: 16px;
}
.search-wrap i {
  position: absolute;
  left: 13px; top: 50%;
  transform: translateY(-50%);
  color: var(--slate);
  font-size: 13px;
  pointer-events: none;
}
.search-input {
  width: 100%;
  max-width: 400px;
  padding: 9px 14px 9px 36px;
  border: 1.5px solid var(--border);
  border-radius: 24px;
  font-size: 13px;
  font-family: 'DM Sans', sans-serif;
  background: var(--fog);
  color: var(--ink);
  transition: border-color var(--t), box-shadow var(--t);
}
.search-input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(26,107,90,.1);
  background: var(--white);
}
 
/* ── FORM ────────────────────────────────────── */
.form-grid   { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-full   { grid-column: 1 / -1; }
.form-group  { display: flex; flex-direction: column; gap: 5px; }
.form-label  { font-size: 11px; font-weight: 600; color: var(--slate); text-transform: uppercase; letter-spacing: .6px; }
.form-ctrl {
  padding: 10px 14px;
  border: 1.5px solid var(--border);
  border-radius: var(--r-sm);
  font-size: 13.5px;
  font-family: 'DM Sans', sans-serif;
  background: var(--paper);
  color: var(--ink);
  transition: border-color var(--t), box-shadow var(--t);
}
.form-ctrl:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(26,107,90,.09);
  background: var(--white);
}
textarea.form-ctrl { min-height: 84px; resize: vertical; }
 
/* ── BUTTONS ─────────────────────────────────── */
.btn {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 9px 20px; border-radius: 8px;
  font-size: 13px; font-weight: 600;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer; border: none;
  text-decoration: none;
  transition: all var(--t);
  letter-spacing: .2px;
}
.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: #145749; box-shadow: 0 4px 14px rgba(26,107,90,.3); }
.btn-submit  { background: var(--ink); color: white; margin-top: 22px; padding: 11px 28px; }
.btn-submit:hover { background: var(--ink2); box-shadow: 0 4px 16px rgba(15,31,46,.25); }
.btn-danger  { background: white; color: var(--rose); border: 1.5px solid #f0c0c3; }
.btn-danger:hover  { background: var(--rose); color: white; }
.btn-sm { padding: 5px 12px; font-size: 12px; border-radius: 6px; }
.btn-outline { background: white; color: var(--ink2); border: 1.5px solid var(--border); }
.btn-outline:hover { border-color: var(--accent); color: var(--accent); }
 
/* ── DONUT ───────────────────────────────────── */
.donut-wrap { position: relative; width: 200px; margin: 8px auto 0; }
.donut-center {
  position: absolute; top: 50%; left: 50%;
  transform: translate(-50%,-50%);
  text-align: center; pointer-events: none;
}
.donut-center .d-num { font-family: 'DM Serif Display', serif; font-size: 30px; color: var(--ink); line-height: 1; }
.donut-center .d-lbl { font-size: 10px; color: var(--slate); text-transform: uppercase; letter-spacing: .8px; margin-top: 3px; }
.d-legend { margin-top: 20px; display: flex; flex-direction: column; gap: 9px; }
.d-row { display: flex; align-items: center; gap: 9px; font-size: 12.5px; color: var(--ink2); }
.d-dot { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
.d-pct { color: var(--slate); font-size: 11.5px; }
.d-num-right { margin-left: auto; font-weight: 700; font-size: 13px; color: var(--ink); }
 
/* ── UPCOMING CHECKUPS ───────────────────────── */
.checkup-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid var(--mist);
  transition: background var(--t);
}
.checkup-item:last-child { border-bottom: none; }
.day-pill {
  min-width: 60px; height: 60px;
  border-radius: 14px;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  flex-shrink: 0;
}
.dp-urgent { background: #fdedef; }
.dp-soon   { background: #fef3e2; }
.dp-ok     { background: #e6f4ea; }
.day-num { font-family: 'DM Serif Display', serif; font-size: 22px; line-height: 1; }
.day-lbl { font-size: 9.5px; text-transform: uppercase; letter-spacing: .6px; font-weight: 600; margin-top: 2px; }
.dp-urgent .day-num, .dp-urgent .day-lbl { color: #c0434b; }
.dp-soon   .day-num, .dp-soon   .day-lbl { color: #c47d1a; }
.dp-ok     .day-num, .dp-ok     .day-lbl { color: #2e7d32; }
.checkup-info { flex: 1; }
.checkup-name { font-weight: 600; font-size: 14px; color: var(--ink); }
.checkup-meta { font-size: 12px; color: var(--slate); margin-top: 4px; display: flex; gap: 14px; flex-wrap: wrap; }
.checkup-meta span { display: flex; align-items: center; gap: 5px; }
.empty-state { padding: 56px 0; text-align: center; }
.empty-icon {
  width: 64px; height: 64px;
  background: var(--fog);
  border-radius: 20px;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 16px;
  font-size: 24px;
  color: var(--accent);
}
.empty-title { font-family: 'DM Serif Display', serif; font-size: 18px; color: var(--ink); margin-bottom: 6px; }
.empty-sub   { font-size: 13px; color: var(--slate); }
 
/* ── DIVIDER ─────────────────────────────────── */
.section-label {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .9px;
  color: var(--slate);
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.section-label::after { content:''; flex:1; height:1px; background:var(--border); }
 
@media(max-width:1100px){
  .stats { grid-template-columns: repeat(3,1fr); }
  .grid-2-right, .grid-2-equal, .grid-form { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
 
<!-- HEADER -->
<header class="header">
  <a class="header-brand" href="#">
    <div class="header-logo"><i class="fa-solid fa-stethoscope"></i></div>
    <div>
      <div class="header-title">Veterinarian Portal</div>
      <div class="header-sub">Tortoise Conservation &amp; Clinical Management</div>
    </div>
  </a>
  <div class="header-right">
    <span class="header-date" id="hdate"></span>
  </div>
</header>
 
<!-- NAV -->
<nav class="nav">
  <div class="nav-tab active" onclick="switchTab('records',this)">
    <i class="fa-regular fa-clipboard"></i> Health Records
  </div>
  <div class="nav-tab" onclick="switchTab('assessment',this)">
    <i class="fa-regular fa-file-medical"></i> New Assessment
  </div>
  <div class="nav-tab" onclick="switchTab('preventive',this)">
    <i class="fa-regular fa-calendar-check"></i> Preventive Care
    <?php if(count($upcoming_rows)): ?>
      <span style="background:var(--rose);color:white;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:2px"><?= count($upcoming_rows) ?></span>
    <?php endif; ?>
  </div>
  <div class="nav-tab" onclick="switchTab('analytics',this)">
    <i class="fa-regular fa-chart-bar"></i> Analytics
  </div>
</nav>
 
<!-- ALERTS -->
<?php if ($msg): ?>
<div class="alert-wrap">
  <?php if ($msg==='added'||$msg==='updated'||$msg==='deleted'): ?>
  <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i>
    <?= $msg==='added'?'Assessment saved successfully.':($msg==='updated'?'Assessment updated successfully.':'Assessment deleted successfully.') ?>
  </div>
  <?php elseif ($msg==='error'): ?>
  <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i> Something went wrong. Please try again.</div>
  <?php endif; ?>
</div>
<?php endif; ?>
 
<!-- ══════════════════════════════════════
     PANE 1 — HEALTH RECORDS
══════════════════════════════════════ -->
<div id="pane-records" class="pane active">
 
  <!-- Stats -->
  <div class="stats">
    <div class="stat">
      <div class="stat-icon-wrap si-teal"><i class="fa-solid fa-shield"></i></div>
      <div><div class="stat-num"><?= $total_tortoises ?></div><div class="stat-lbl">Total Tortoises</div></div>
    </div>
    <div class="stat">
      <div class="stat-icon-wrap si-green"><i class="fa-solid fa-heart-pulse"></i></div>
      <div><div class="stat-num"><?= $healthy_count ?></div><div class="stat-lbl">Healthy</div></div>
    </div>
    <div class="stat">
      <div class="stat-icon-wrap si-amber"><i class="fa-solid fa-eye"></i></div>
      <div><div class="stat-num"><?= $observation_count ?></div><div class="stat-lbl">Under Observation</div></div>
    </div>
    <div class="stat">
      <div class="stat-icon-wrap si-rose"><i class="fa-solid fa-triangle-exclamation"></i></div>
      <div><div class="stat-num"><?= $critical_count ?></div><div class="stat-lbl">Critical</div></div>
    </div>
    <div class="stat">
      <div class="stat-icon-wrap si-sky"><i class="fa-regular fa-file-lines"></i></div>
      <div><div class="stat-num"><?= $total_assessments ?></div><div class="stat-lbl">Assessments</div></div>
    </div>
  </div>
 
  <div class="grid-2-right">
 
    <!-- Table -->
    <div class="card">
      <div class="card-head">
        <div class="card-head-icon"><i class="fa-solid fa-list-ul"></i></div>
        <h2>Registered Tortoises</h2>
      </div>
      <div class="card-body">
        <div class="search-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="tort-search" class="search-input" placeholder="Search name, microchip, species, status…" oninput="filterTortoises()">
        </div>
        <table class="tbl" id="tort-tbl">
          <thead>
            <tr>
              <th>Microchip</th><th>Name</th><th>Species</th><th>Age</th><th>Sex</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php while($row=$tortoises->fetch_assoc()):
            $bclass='b-healthy';
            if($row['health_status']==='Under observation') $bclass='b-obs';
            elseif($row['health_status']==='Critical')      $bclass='b-critical';
            elseif($row['health_status']==='Recovering')    $bclass='b-recovering';
            elseif($row['health_status']==='Minor injury')  $bclass='b-injury';
          ?>
            <tr>
              <td><span class="mono"><?= htmlspecialchars($row['microchip_id']) ?></span></td>
              <td><strong style="color:var(--ink)"><?= htmlspecialchars($row['name']) ?></strong></td>
              <td><?= htmlspecialchars($row['common_name']) ?></td>
              <td><?= $row['estimated_age_years'] ?> yrs</td>
              <td><?= $row['sex'] ?></td>
              <td><span class="badge <?= $bclass ?>"><?= $row['health_status'] ?></span></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
 
    <!-- Donut -->
    <div class="card">
      <div class="card-head">
        <div class="card-head-icon"><i class="fa-solid fa-chart-pie"></i></div>
        <h2>Health Overview</h2>
      </div>
      <div class="card-body">
        <div class="donut-wrap">
          <canvas id="donut1" height="200"></canvas>
          <div class="donut-center">
            <div class="d-num"><?= array_sum($chart_data) ?></div>
            <div class="d-lbl">Total</div>
          </div>
        </div>
        <div class="d-legend" id="leg1"></div>
      </div>
    </div>
 
  </div>
</div>
 
<!-- ══════════════════════════════════════
     PANE 2 — NEW ASSESSMENT
══════════════════════════════════════ -->
<div id="pane-assessment" class="pane">
  <div class="grid-form">
 
    <div class="card">
      <div class="card-head">
        <div class="card-head-icon"><i class="fa-solid fa-notes-medical"></i></div>
        <h2>Perform Health Assessment</h2>
      </div>
      <div class="card-body">
        <form action="add_assessment.php" method="POST">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Assessment Code</label>
              <input type="text" name="assessment_code" class="form-ctrl" placeholder="e.g. ASS-2026-002" required>
            </div>
            <div class="form-group">
              <label class="form-label">Assessment Date</label>
              <input type="date" name="assessment_date" class="form-ctrl" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Select Tortoise</label>
              <select name="tortoise_id" class="form-ctrl" required>
                <option value="">— Select Tortoise —</option>
                <?php while($t=$tortoise_list->fetch_assoc()): ?>
                <option value="<?= $t['tortoise_id'] ?>"><?= htmlspecialchars($t['name']) ?> · <?= $t['microchip_id'] ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Attending Veterinarian</label>
              <select name="vet_id" class="form-ctrl">
                <option value="">— Optional —</option>
                <?php while($v=$vet_list->fetch_assoc()): ?>
                <option value="<?= $v['staff_id'] ?>"><?= htmlspecialchars($v['full_name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Health Condition</label>
              <input type="text" name="health_condition" class="form-ctrl" placeholder="e.g. Stable, Critical">
            </div>
            <div class="form-group">
              <label class="form-label">Next Checkup Date</label>
              <input type="date" name="next_checkup_date" class="form-ctrl">
            </div>
            <div class="form-group form-full">
              <label class="form-label">Diagnosis</label>
              <textarea name="diagnosis" class="form-ctrl" placeholder="Clinical findings and diagnosis…"></textarea>
            </div>
            <div class="form-group form-full">
              <label class="form-label">Treatment Plan</label>
              <textarea name="treatment" class="form-ctrl" placeholder="Prescribed treatment and medications…"></textarea>
            </div>
            <div class="form-group form-full">
              <label class="form-label">Remarks</label>
              <textarea name="remarks" class="form-ctrl" placeholder="Additional clinical notes…"></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-submit">
            <i class="fa-solid fa-floppy-disk"></i> Save Assessment
          </button>
        </form>
      </div>
    </div>
 
    <div class="card">
      <div class="card-head">
        <div class="card-head-icon"><i class="fa-regular fa-clock"></i></div>
        <h2>Recent Assessments</h2>
      </div>
      <div class="card-body" style="padding:0">
        <table class="tbl">
          <thead>
            <tr style="padding-left:24px">
              <th style="padding-left:24px">Code</th><th>Date</th><th>Tortoise</th><th>Diagnosis</th><th style="padding-right:24px">Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php while($row=$assessments->fetch_assoc()): ?>
            <tr>
              <td style="padding-left:24px"><span class="mono"><?= htmlspecialchars($row['assessment_code']) ?></span></td>
              <td style="white-space:nowrap;color:var(--slate)"><?= $row['assessment_date'] ?></td>
              <td><strong><?= htmlspecialchars($row['tortoise_name']) ?></strong></td>
              <td style="color:var(--slate)"><?= htmlspecialchars(mb_substr($row['diagnosis']??'',0,45)) ?><?= mb_strlen($row['diagnosis']??'')>45?'…':'' ?></td>
              <td style="padding-right:24px;white-space:nowrap">
                <a href="edit_health_assessment.php?id=<?= $row['assessment_id'] ?>">
                  <button class="btn btn-outline btn-sm"><i class="fa-regular fa-pen-to-square"></i> Edit</button>
                </a>
                <a href="delete_assessment.php?id=<?= $row['assessment_id'] ?>" onclick="return confirm('Delete this assessment?')" style="margin-left:6px">
                  <button class="btn btn-danger btn-sm"><i class="fa-regular fa-trash-can"></i></button>
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
 
<!-- ══════════════════════════════════════
     PANE 3 — PREVENTIVE CARE
══════════════════════════════════════ -->
<div id="pane-preventive" class="pane">
  <div class="card">
    <div class="card-head">
      <div class="card-head-icon"><i class="fa-regular fa-calendar-check"></i></div>
      <h2>Upcoming Checkups — Next 30 Days</h2>
      <?php if(count($upcoming_rows)): ?>
        <span style="margin-left:auto;background:var(--fog);border:1px solid var(--border);border-radius:20px;padding:3px 12px;font-size:12px;color:var(--slate);font-weight:600">
          <?= count($upcoming_rows) ?> scheduled
        </span>
      <?php endif; ?>
    </div>
    <div class="card-body">
      <?php if(!count($upcoming_rows)): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fa-solid fa-circle-check"></i></div>
          <div class="empty-title">All clear</div>
          <div class="empty-sub">No checkups are scheduled in the next 30 days.</div>
        </div>
      <?php else: ?>
        <?php foreach($upcoming_rows as $u):
          $d  = (int)$u['days_left'];
          $dc = $d<=5?'dp-urgent':($d<=14?'dp-soon':'dp-ok');
        ?>
        <div class="checkup-item">
          <div class="day-pill <?= $dc ?>">
            <div class="day-num"><?= $d ?></div>
            <div class="day-lbl"><?= $d===1?'day':'days' ?></div>
          </div>
          <div class="checkup-info">
            <div class="checkup-name"><?= htmlspecialchars($u['tortoise_name']) ?></div>
            <div class="checkup-meta">
              <span><i class="fa-solid fa-microchip" style="font-size:11px"></i><?= htmlspecialchars($u['microchip_id']) ?></span>
              <span><i class="fa-regular fa-calendar" style="font-size:11px"></i><?= date('D, d M Y', strtotime($u['next_checkup_date'])) ?></span>
              <span><i class="fa-solid fa-tag" style="font-size:11px"></i><?= htmlspecialchars($u['assessment_code']) ?></span>
              <?php if(!empty($u['remarks'])): ?>
              <span><?= htmlspecialchars(mb_substr($u['remarks'],0,55)) ?><?= mb_strlen($u['remarks'])>55?'…':'' ?></span>
              <?php endif; ?>
            </div>
          </div>
          <a href="edit_health_assessment.php?id=<?= $u['assessment_id'] ?>">
            <button class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-right"></i> View</button>
          </a>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
 
<!-- ══════════════════════════════════════
     PANE 4 — ANALYTICS
══════════════════════════════════════ -->
<div id="pane-analytics" class="pane">
  <div class="grid-2-equal">
 
    <div class="card">
      <div class="card-head">
        <div class="card-head-icon"><i class="fa-solid fa-chart-column"></i></div>
        <h2>Assessments per Month</h2>
      </div>
      <div class="card-body">
        <?php if(empty($bar_labels)): ?>
          <div class="empty-state">
            <div class="empty-icon"><i class="fa-solid fa-chart-column"></i></div>
            <div class="empty-title">No data yet</div>
            <div class="empty-sub">Assessment data will appear here once records exist.</div>
          </div>
        <?php else: ?>
          <canvas id="barChart" height="240"></canvas>
        <?php endif; ?>
      </div>
    </div>
 
    <div class="card">
      <div class="card-head">
        <div class="card-head-icon"><i class="fa-solid fa-chart-pie"></i></div>
        <h2>Health Status Distribution</h2>
      </div>
      <div class="card-body">
        <div class="donut-wrap">
          <canvas id="donut2" height="200"></canvas>
          <div class="donut-center">
            <div class="d-num"><?= array_sum($chart_data) ?></div>
            <div class="d-lbl">Total</div>
          </div>
        </div>
        <div class="d-legend" id="leg2"></div>
      </div>
    </div>
 
  </div>
</div>
 
<script>
// Date in header
const d = new Date();
document.getElementById('hdate').textContent =
  d.toLocaleDateString('en-GB',{weekday:'long',day:'numeric',month:'long',year:'numeric'});
 
// Tab switching
function switchTab(name, el) {
  document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('pane-' + name).classList.add('active');
  el.classList.add('active');
}
 
// Search
function filterTortoises() {
  const q = document.getElementById('tort-search').value.toLowerCase();
  document.querySelectorAll('#tort-tbl tbody tr').forEach(r => {
    r.style.display = r.innerText.toLowerCase().includes(q) ? '' : 'none';
  });
}
 
// Colors
const C = ['#1a6b5a','#c47d1a','#2a7ab8','#c0434b','#7b4fa6','#6b8fa3'];
 
// Donut builder
function buildDonut(id, legId) {
  const labels = <?= json_encode($chart_labels) ?>;
  const data   = <?= json_encode($chart_data) ?>;
  const total  = data.reduce((a,b)=>a+b,0);
  new Chart(document.getElementById(id),{
    type:'doughnut',
    data:{ labels, datasets:[{ data,
      backgroundColor: C.slice(0,labels.length),
      borderWidth:3, borderColor:'#fff', hoverOffset:6
    }]},
    options:{ cutout:'68%',
      plugins:{ legend:{display:false},
        tooltip:{ callbacks:{ label: c=>` ${c.label}: ${c.parsed}` }}
      }
    }
  });
  const el = document.getElementById(legId);
  labels.forEach((lbl,i)=>{
    const pct = total>0?Math.round(data[i]/total*100):0;
    el.innerHTML += `<div class="d-row">
      <span class="d-dot" style="background:${C[i]}"></span>
      <span>${lbl}</span>
      <span class="d-pct">${pct}%</span>
      <span class="d-num-right">${data[i]}</span>
    </div>`;
  });
}
 
// Bar chart
function buildBar(){
  const labels = <?= json_encode($bar_labels) ?>;
  const data   = <?= json_encode($bar_data) ?>;
  if(!labels.length) return;
  new Chart(document.getElementById('barChart'),{
    type:'bar',
    data:{ labels, datasets:[{
      label:'Assessments', data,
      backgroundColor:'rgba(26,107,90,.15)',
      borderColor:'#1a6b5a',
      borderWidth:2, borderRadius:8,
      hoverBackgroundColor:'rgba(26,107,90,.3)'
    }]},
    options:{ responsive:true,
      plugins:{ legend:{display:false},
        tooltip:{ callbacks:{ label: c=>` ${c.parsed.y} assessment${c.parsed.y!==1?'s':''}` }}
      },
      scales:{
        y:{ beginAtZero:true, ticks:{ stepSize:1, font:{family:'DM Sans',size:12}, color:'#5a7a8e' },
            grid:{ color:'#edf0f3' }, border:{display:false} },
        x:{ ticks:{ font:{family:'DM Sans',size:12}, color:'#5a7a8e' },
            grid:{display:false}, border:{display:false} }
      }
    }
  });
}
 
buildDonut('donut1','leg1');
buildDonut('donut2','leg2');
buildBar();
</script>
</body>
</html>