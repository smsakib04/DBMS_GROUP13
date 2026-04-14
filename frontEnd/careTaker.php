<?php
require_once '../backEnd/includes/session.php';
require_once '../backEnd/config/db.php';

// ── TAB 1: Daily Monitoring ─────────────────────────────────────
// All tortoises with their enclosure + latest observation
$tortoises = $conn->query("
    SELECT
        t.tortoise_id, t.microchip_id, t.name,
        s.common_name  AS species,
        t.sex, t.estimated_age_years,
        t.health_status, t.weight_grams,
        e.enclosure_name, e.enclosure_code,
        (SELECT o.description FROM observations o
         WHERE o.tortoise_id = t.tortoise_id
         ORDER BY o.created_at DESC LIMIT 1) AS last_observation,
        (SELECT o.observation_date FROM observations o
         WHERE o.tortoise_id = t.tortoise_id
         ORDER BY o.created_at DESC LIMIT 1) AS last_obs_date
    FROM tortoises t
    JOIN species   s ON t.species_id    = s.species_id
    LEFT JOIN enclosures e ON t.enclosure_id = e.enclosure_id
    ORDER BY t.name ASC
");

// All observations log
$observations = $conn->query("
    SELECT
        o.observation_id, o.observation_date, o.category,
        o.description, o.created_at,
        t.name AS tortoise_name, t.microchip_id,
        st.full_name AS observer_name
    FROM observations o
    LEFT JOIN tortoises t  ON o.tortoise_id  = t.tortoise_id
    LEFT JOIN staff     st ON o.observer_id  = st.staff_id
    ORDER BY o.created_at DESC
    LIMIT 50
");

// ── TAB 2: Feeding Monitoring ───────────────────────────────────
$today = date('Y-m-d');
$feeding_today = $conn->query("
    SELECT
        fs.schedule_id, fs.feeding_time, fs.food_type,
        fs.amount_grams, fs.is_done, fs.notes,
        fs.scheduled_date,
        t.tortoise_id, t.name AS tortoise_name,
        t.microchip_id, t.health_status,
        st.full_name AS feeder_name
    FROM feeding_schedules fs
    JOIN tortoises t  ON fs.tortoise_id = t.tortoise_id
    LEFT JOIN staff st ON fs.feeder_id  = st.staff_id
    WHERE fs.scheduled_date = '$today'
    ORDER BY fs.feeding_time ASC
");

// Feeding stats
$fed_count       = $conn->query("SELECT COUNT(*) c FROM feeding_schedules WHERE scheduled_date='$today' AND is_done=1")->fetch_assoc()['c'];
$pending_count   = $conn->query("SELECT COUNT(*) c FROM feeding_schedules WHERE scheduled_date='$today' AND is_done=0")->fetch_assoc()['c'];
$total_scheduled = $fed_count + $pending_count;

// All tortoises for "Add Feeding" dropdown
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");

// ── TAB 3: Health Reports ───────────────────────────────────────
$health_reports = $conn->query("
    SELECT
        ha.assessment_id, ha.assessment_code,
        ha.assessment_date, ha.health_condition,
        ha.diagnosis, ha.treatment, ha.remarks,
        ha.next_checkup_date,
        t.name AS tortoise_name, t.microchip_id,
        st.full_name AS vet_name
    FROM health_assessments ha
    JOIN tortoises t  ON ha.tortoise_id = t.tortoise_id
    JOIN staff     st ON ha.vet_id      = st.staff_id
    ORDER BY ha.assessment_date DESC
");

// Tortoises for health report form
$tortoise_list2 = $conn->query("SELECT tortoise_id, microchip_id, name, health_status FROM tortoises ORDER BY name");

// ── Stats bar ───────────────────────────────────────────────────
$total_tortoises   = $conn->query("SELECT COUNT(*) c FROM tortoises")->fetch_assoc()['c'];
$critical_count    = $conn->query("SELECT COUNT(*) c FROM tortoises WHERE health_status IN ('Critical','Under observation')")->fetch_assoc()['c'];
$pending_tasks     = $conn->query("SELECT COUNT(*) c FROM tasks WHERE status != 'Completed'")->fetch_assoc()['c'];
$obs_today         = $conn->query("SELECT COUNT(*) c FROM observations WHERE observation_date='$today'")->fetch_assoc()['c'];

// Flash message
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Caretaker Dashboard | TCCMS</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Serif+Display&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
/* ════════════════════════════════
   TOKENS
════════════════════════════════ */
:root {
  --forest:   #1b4332;
  --forest-m: #2d6a4f;
  --forest-l: #52b788;
  --mint:     #b7e4c7;
  --cream:    #f8f6f0;
  --sand:     #ede8dc;
  --text:     #1a1a1a;
  --text-s:   #4a5568;
  --white:    #ffffff;
  --warn:     #b45309;
  --warn-bg:  #fef3c7;
  --danger:   #991b1b;
  --danger-bg:#fee2e2;
  --ok-bg:    #d1fae5;
  --ok:       #065f46;
  --radius:   14px;
  --shadow:   0 4px 24px rgba(27,67,50,.10);
  --shadow-s: 0 2px 10px rgba(27,67,50,.07);
}

/* ════════════════════════════════
   RESET & BASE
════════════════════════════════ */
*, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
body {
  font-family:'DM Sans', sans-serif;
  background: var(--cream);
  color: var(--text);
  min-height: 100vh;
}

/* ════════════════════════════════
   TOP NAV
════════════════════════════════ */
.topnav {
  background: var(--forest);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  height: 60px;
  position: sticky; top: 0; z-index: 100;
  box-shadow: 0 2px 12px rgba(0,0,0,.2);
}
.topnav-brand {
  display: flex; align-items: center; gap: .6rem;
  font-family:'DM Serif Display', serif;
  font-size: 1.1rem; color: var(--mint); letter-spacing:.02em;
}
.topnav-brand i { font-size:1.2rem; }
.topnav-right { display: flex; align-items: center; gap: 1rem; }
.topnav-date { font-size:.8rem; color: rgba(255,255,255,.6); }
.nav-btn {
  display: inline-flex; align-items: center; gap:.4rem;
  padding: .4rem 1rem; border-radius:999px; font-size:.82rem;
  font-weight:600; cursor:pointer; border:none; transition:all .18s;
  text-decoration:none;
}
.nav-btn-home { background:rgba(255,255,255,.12); color:#fff; }
.nav-btn-home:hover { background:rgba(255,255,255,.22); }
.nav-btn-logout { background:var(--forest-l); color:var(--forest); }
.nav-btn-logout:hover { background:var(--mint); }

/* ════════════════════════════════
   LAYOUT
════════════════════════════════ */
.page { max-width:1280px; margin:0 auto; padding:1.8rem 1.5rem 3rem; }

/* ════════════════════════════════
   PAGE HEADER
════════════════════════════════ */
.page-header {
  display:flex; align-items:flex-start;
  justify-content:space-between; flex-wrap:wrap;
  gap:1rem; margin-bottom:1.6rem;
}
.page-title h1 {
  font-family:'DM Serif Display', serif;
  font-size:1.9rem; color:var(--forest); line-height:1.2;
}
.page-title p { color:var(--text-s); font-size:.9rem; margin-top:.3rem; }

/* ════════════════════════════════
   FLASH ALERT
════════════════════════════════ */
.flash {
  padding:.8rem 1.2rem; border-radius:10px;
  font-weight:600; font-size:.88rem; margin-bottom:1.2rem;
  display:flex; align-items:center; gap:.6rem;
}
.flash-ok     { background:var(--ok-bg); color:var(--ok); }
.flash-err    { background:var(--danger-bg); color:var(--danger); }

/* ════════════════════════════════
   STATS ROW
════════════════════════════════ */
.stats-row {
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:1rem; margin-bottom:1.8rem;
}
@media(max-width:700px){ .stats-row{ grid-template-columns:repeat(2,1fr); } }
.stat-card {
  background:var(--white); border-radius:var(--radius);
  padding:1.2rem 1.4rem; box-shadow:var(--shadow-s);
  display:flex; flex-direction:column; gap:.3rem;
}
.stat-card .stat-val {
  font-family:'DM Serif Display',serif;
  font-size:2rem; color:var(--forest); line-height:1;
}
.stat-card .stat-lbl { font-size:.78rem; color:var(--text-s); font-weight:500; text-transform:uppercase; letter-spacing:.05em; }
.stat-card .stat-icon { font-size:1.2rem; color:var(--forest-l); margin-bottom:.2rem; }
.stat-warn .stat-val { color:var(--warn); }

/* ════════════════════════════════
   TAB BAR
════════════════════════════════ */
.tab-bar {
  display:flex; gap:.4rem;
  background:var(--sand); border-radius:999px;
  padding:.35rem; margin-bottom:1.6rem;
  width:fit-content;
}
.tab-btn {
  display:inline-flex; align-items:center; gap:.5rem;
  padding:.55rem 1.3rem; border-radius:999px;
  border:none; cursor:pointer; font-weight:600;
  font-size:.88rem; font-family:'DM Sans',sans-serif;
  color:var(--text-s); background:transparent;
  transition:all .2s;
}
.tab-btn.active {
  background:var(--forest); color:#fff;
  box-shadow: 0 2px 10px rgba(27,67,50,.25);
}
.tab-btn i { font-size:.85rem; }

/* ════════════════════════════════
   TAB PANELS
════════════════════════════════ */
.tab-panel { display:none; }
.tab-panel.active { display:block; }

/* ════════════════════════════════
   CARD
════════════════════════════════ */
.card {
  background:var(--white); border-radius:20px;
  padding:1.6rem; box-shadow:var(--shadow);
  margin-bottom:1.4rem;
}
.card-header {
  display:flex; align-items:center;
  justify-content:space-between; flex-wrap:wrap;
  gap:.8rem; margin-bottom:1.2rem;
}
.card-header h2 {
  font-family:'DM Serif Display',serif;
  font-size:1.15rem; color:var(--forest);
  display:flex; align-items:center; gap:.5rem;
}
.card-actions { display:flex; gap:.6rem; flex-wrap:wrap; }

/* ════════════════════════════════
   BUTTONS
════════════════════════════════ */
.btn {
  display:inline-flex; align-items:center; gap:.45rem;
  padding:.55rem 1.1rem; border-radius:999px;
  border:none; cursor:pointer; font-weight:600;
  font-size:.83rem; font-family:'DM Sans',sans-serif;
  transition:all .18s; text-decoration:none;
}
.btn:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,.1); }
.btn-primary  { background:var(--forest);   color:#fff; }
.btn-primary:hover { background:var(--forest-m); }
.btn-light    { background:var(--sand);     color:var(--forest); }
.btn-danger   { background:var(--danger-bg);color:var(--danger); }
.btn-warn     { background:var(--warn-bg);  color:var(--warn); }
.btn-ok       { background:var(--ok-bg);    color:var(--ok); }
.btn-sm       { padding:.35rem .8rem; font-size:.78rem; }

/* ════════════════════════════════
   TABLE
════════════════════════════════ */
.tbl-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:.88rem; }
th {
  padding:.65rem .8rem; border-bottom:2px solid var(--mint);
  text-align:left; color:var(--forest-m);
  font-size:.75rem; text-transform:uppercase; letter-spacing:.06em; font-weight:700;
}
td { padding:.75rem .8rem; border-bottom:1px solid #edf1f0; vertical-align:top; }
tbody tr:hover td { background:#f5fbf8; }

/* ════════════════════════════════
   BADGES
════════════════════════════════ */
.badge {
  display:inline-block; padding:.18rem .75rem;
  border-radius:999px; font-size:.75rem; font-weight:700;
}
.badge-healthy   { background:var(--ok-bg);     color:var(--ok); }
.badge-critical  { background:var(--danger-bg); color:var(--danger); }
.badge-observe   { background:var(--warn-bg);   color:var(--warn); }
.badge-recover   { background:#dbeafe;          color:#1e40af; }
.badge-done      { background:var(--ok-bg);     color:var(--ok); }
.badge-pending   { background:var(--warn-bg);   color:var(--warn); }
.badge-health    { background:#fce7f3;          color:#9d174d; }
.badge-behavior  { background:#ede9fe;          color:#5b21b6; }
.badge-feeding   { background:#ecfdf5;          color:#065f46; }
.badge-general   { background:var(--sand);      color:var(--text-s); }
.badge-nesting   { background:#fef9c3;          color:#713f12; }

/* ════════════════════════════════
   INLINE ADD FORM
════════════════════════════════ */
.form-panel {
  background:var(--cream); border:1.5px solid var(--mint);
  border-radius:16px; padding:1.4rem;
  margin-bottom:1.2rem; display:none;
}
.form-panel.open { display:block; }
.form-panel h3 {
  font-size:.95rem; font-weight:700; color:var(--forest);
  margin-bottom:1rem;
}
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:.9rem; }
.form-grid .span2 { grid-column:1/-1; }
@media(max-width:600px){ .form-grid{ grid-template-columns:1fr; } .form-grid .span2{ grid-column:1; } }
.form-group label {
  display:block; font-size:.75rem; font-weight:700;
  color:var(--text-s); text-transform:uppercase;
  letter-spacing:.05em; margin-bottom:.35rem;
}
.form-group input,
.form-group select,
.form-group textarea {
  width:100%; padding:.6rem .85rem;
  border:1.5px solid #d1d9d4; border-radius:10px;
  font-size:.88rem; font-family:'DM Sans',sans-serif;
  background:#fff; color:var(--text);
}
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline:none; border-color:var(--forest-l);
  box-shadow:0 0 0 3px rgba(82,183,136,.15);
}
.form-group textarea { resize:vertical; min-height:80px; }
.form-actions { display:flex; gap:.7rem; justify-content:flex-end; margin-top:1rem; }

/* ════════════════════════════════
   SEARCH
════════════════════════════════ */
.search-box {
  display:none; margin-bottom:1rem;
}
.search-box.open { display:flex; gap:.6rem; align-items:center; }
.search-box input {
  flex:1; padding:.6rem 1rem; border-radius:999px;
  border:1.5px solid var(--mint); font-size:.88rem;
  font-family:'DM Sans',sans-serif;
}
.search-box input:focus { outline:none; border-color:var(--forest-l); }

/* ════════════════════════════════
   FEEDING PROGRESS BAR
════════════════════════════════ */
.feed-progress {
  background:var(--sand); border-radius:999px;
  height:10px; overflow:hidden; margin-bottom:1.4rem;
}
.feed-progress-fill {
  height:100%; background:var(--forest-l);
  border-radius:999px; transition:width .6s ease;
}
.feed-summary {
  display:flex; gap:1.2rem; margin-bottom:1rem;
  font-size:.85rem; font-weight:600; color:var(--text-s);
}
.feed-summary span i { margin-right:.3rem; }

/* ════════════════════════════════
   TORTOISE GRID (monitoring view)
════════════════════════════════ */
.tortoise-grid {
  display:grid;
  grid-template-columns:repeat(auto-fill, minmax(260px, 1fr));
  gap:1rem; margin-bottom:1.2rem;
}
.tortoise-card {
  background:var(--white); border:1.5px solid #e8f0eb;
  border-radius:16px; padding:1.1rem;
  cursor:pointer; transition:all .18s;
}
.tortoise-card:hover {
  border-color:var(--forest-l);
  box-shadow:0 4px 16px rgba(27,67,50,.10);
  transform:translateY(-2px);
}
.tortoise-card.selected {
  border-color:var(--forest);
  background:#f0fbf5;
}
.tc-top { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:.5rem; }
.tc-name { font-weight:700; font-size:.95rem; color:var(--forest); }
.tc-chip { font-size:.72rem; color:var(--text-s); margin-top:.1rem; }
.tc-meta { font-size:.78rem; color:var(--text-s); margin-top:.4rem; display:flex; gap:.8rem; flex-wrap:wrap; }
.tc-obs { font-size:.78rem; color:var(--text-s); margin-top:.6rem; padding-top:.6rem; border-top:1px solid #edf1f0; font-style:italic; }

/* ════════════════════════════════
   HEALTH STATUS TOGGLE BUTTON
════════════════════════════════ */
.status-btn {
  display:inline-flex; align-items:center; gap:.35rem;
  padding:.28rem .75rem; border-radius:999px;
  font-size:.72rem; font-weight:700; cursor:pointer; border:none;
}
</style>
</head>
<body>

<!-- ═══════════════════ TOP NAV ═══════════════════ -->
<nav class="topnav">
  <div class="topnav-brand">
    <i class="fas fa-shield-alt"></i>
    TCCMS · Caretaker
  </div>
  <div class="topnav-right">
    <span class="topnav-date"><?php echo date('l, d M Y'); ?></span>
    <a href="homepage.php" class="nav-btn nav-btn-home">
      <i class="fas fa-home"></i> Home
    </a>
    <a href="http://localhost/DBMS_GROUP13/frontEnd/login.php" class="nav-btn nav-btn-logout">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </div>
</nav>

<div class="page">

  <!-- ═══════ PAGE HEADER ═══════ -->
  <div class="page-header">
    <div class="page-title">
      <h1>Caretaker Dashboard</h1>
      <p>Daily monitoring, feeding schedules, and health reporting</p>
    </div>
  </div>

  <!-- ═══════ FLASH ═══════ -->
  <?php if($msg === 'obs_added'):   ?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Observation logged successfully.</div><?php endif; ?>
  <?php if($msg === 'obs_updated'): ?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Observation updated successfully.</div><?php endif; ?>
  <?php if($msg === 'obs_deleted'): ?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Observation deleted.</div><?php endif; ?>
  <?php if($msg === 'feed_done'):   ?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Feeding marked as done.</div><?php endif; ?>
  <?php if($msg === 'feed_added'):  ?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Feeding entry added.</div><?php endif; ?>
  <?php if($msg === 'feed_updated'):?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Feeding entry updated.</div><?php endif; ?>
  <?php if($msg === 'feed_deleted'):?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Feeding entry removed.</div><?php endif; ?>
  <?php if($msg === 'report_sent'): ?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Health report sent to veterinarian.</div><?php endif; ?>
  <?php if($msg === 'report_deleted'):?><div class="flash flash-ok"><i class="fas fa-check-circle"></i> Health report deleted.</div><?php endif; ?>
  <?php if($msg === 'error'):       ?><div class="flash flash-err"><i class="fas fa-exclamation-circle"></i> Something went wrong. Please try again.</div><?php endif; ?>

  <!-- ═══════ STATS ROW ═══════ -->
  <div class="stats-row">
    <div class="stat-card">
      <span class="stat-icon"><i class="fas fa-circle-dot"></i></span>
      <span class="stat-val"><?= $total_tortoises ?></span>
      <span class="stat-lbl">Total Tortoises</span>
    </div>
    <div class="stat-card stat-warn">
      <span class="stat-icon"><i class="fas fa-triangle-exclamation"></i></span>
      <span class="stat-val"><?= $critical_count ?></span>
      <span class="stat-lbl">Need Attention</span>
    </div>
    <div class="stat-card">
      <span class="stat-icon"><i class="fas fa-utensils"></i></span>
      <span class="stat-val"><?= $fed_count ?>/<?= $total_scheduled ?></span>
      <span class="stat-lbl">Fed Today</span>
    </div>
    <div class="stat-card">
      <span class="stat-icon"><i class="fas fa-eye"></i></span>
      <span class="stat-val"><?= $obs_today ?></span>
      <span class="stat-lbl">Observations Today</span>
    </div>
  </div>

  <!-- ═══════ TAB BAR ═══════ -->
  <div class="tab-bar">
    <button class="tab-btn active" onclick="switchTab('monitoring',this)">
      <i class="fas fa-binoculars"></i> Daily Monitoring
    </button>
    <button class="tab-btn" onclick="switchTab('feeding',this)">
      <i class="fas fa-utensils"></i> Feeding
    </button>
    <button class="tab-btn" onclick="switchTab('health',this)">
      <i class="fas fa-stethoscope"></i> Health Reports
    </button>
  </div>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  TAB 1 — DAILY MONITORING                           ║
       ╚══════════════════════════════════════════════════════╝ -->
  <div class="tab-panel active" id="tab-monitoring">

    <!-- Tortoise Overview Grid (READ) -->
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-list-check"></i> Tortoise Overview</h2>
        <div class="card-actions">
          <button class="btn btn-light btn-sm" onclick="toggleSearch('obs-search')">
            <i class="fas fa-search"></i> Search
          </button>
        </div>
      </div>
      <div class="search-box" id="obs-search">
        <input type="text" id="tort-search-input" placeholder="Search by name, microchip, species, health…" oninput="filterCards(this.value)">
      </div>
      <div class="tortoise-grid" id="tortoiseGrid">
        <?php
        $tort_rows = [];
        while($r = $tortoises->fetch_assoc()) $tort_rows[] = $r;
        foreach($tort_rows as $t):
          $badge = match($t['health_status']) {
            'Healthy'           => 'badge-healthy',
            'Critical'          => 'badge-critical',
            'Under observation' => 'badge-observe',
            'Recovering'        => 'badge-recover',
            default             => 'badge-observe'
          };
        ?>
        <div class="tortoise-card"
             data-search="<?= strtolower($t['name'].' '.$t['microchip_id'].' '.$t['species'].' '.$t['health_status']) ?>">
          <div class="tc-top">
            <div>
              <div class="tc-name"><?= htmlspecialchars($t['name']) ?></div>
              <div class="tc-chip"><?= htmlspecialchars($t['microchip_id']) ?></div>
            </div>
            <span class="badge <?= $badge ?>"><?= $t['health_status'] ?></span>
          </div>
          <div class="tc-meta">
            <span><i class="fas fa-dna" style="color:var(--forest-l)"></i> <?= htmlspecialchars($t['species']) ?></span>
            <span><i class="fas fa-venus-mars" style="color:var(--forest-l)"></i> <?= $t['sex'] ?></span>
            <?php if($t['enclosure_name']): ?>
            <span><i class="fas fa-location-dot" style="color:var(--forest-l)"></i> <?= htmlspecialchars($t['enclosure_name']) ?></span>
            <?php endif; ?>
          </div>
          <?php if($t['last_observation']): ?>
          <div class="tc-obs">
            <i class="fas fa-clock" style="color:var(--forest-l)"></i>
            <?= htmlspecialchars(substr($t['last_observation'],0,80)) ?>…
            <span style="font-style:normal;font-size:.72rem;color:var(--forest-l)"> — <?= $t['last_obs_date'] ?></span>
          </div>
          <?php else: ?>
          <div class="tc-obs" style="color:#bbb">No observations yet</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Observations Log (READ + CREATE link + EDIT + DELETE) -->
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-clipboard-list"></i> Observations Log</h2>
        <div class="card-actions">
          <!-- Links to standalone add_observation.php page -->
          <a href="add_observation.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Log Observation
          </a>
          <button class="btn btn-light btn-sm" onclick="toggleSearch('obs-log-search')">
            <i class="fas fa-search"></i> Search
          </button>
        </div>
      </div>

      <!-- Search -->
      <div class="search-box" id="obs-log-search">
        <input type="text" placeholder="Search observations…" oninput="filterTable('obsTable',this.value)">
      </div>

      <!-- READ: Observations Table -->
      <div class="tbl-wrap">
        <table id="obsTable">
          <thead><tr>
            <th>Date</th><th>Tortoise</th><th>Category</th>
            <th>Description</th><th>Logged By</th><th>Actions</th>
          </tr></thead>
          <tbody>
          <?php while($o = $observations->fetch_assoc()): ?>
            <tr>
              <td><?= $o['observation_date'] ?></td>
              <td>
                <strong><?= htmlspecialchars($o['tortoise_name'] ?? '—') ?></strong><br>
                <small style="color:var(--text-s)"><?= htmlspecialchars($o['microchip_id'] ?? '') ?></small>
              </td>
              <td><span class="badge badge-<?= $o['category'] ?>"><?= ucfirst($o['category']) ?></span></td>
              <td style="max-width:260px"><?= htmlspecialchars($o['description']) ?></td>
              <td><?= htmlspecialchars($o['observer_name'] ?? '—') ?></td>
              <td style="white-space:nowrap">
                <!-- EDIT observation -->
                <a href="edit_observation.php?id=<?= $o['observation_id'] ?>">
                  <button class="btn btn-light btn-sm"><i class="fas fa-pen"></i> Edit</button>
                </a>
                <!-- DELETE observation -->
                <a href="delete_observation.php?id=<?= $o['observation_id'] ?>"
                   onclick="return confirm('Delete this observation?')" style="margin-left:4px">
                  <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete</button>
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div><!-- /tab-monitoring -->


  <!-- ╔══════════════════════════════════════════════════════╗
       ║  TAB 2 — FEEDING MONITORING                         ║
       ╚══════════════════════════════════════════════════════╝ -->
  <div class="tab-panel" id="tab-feeding">

    <!-- Progress Summary -->
    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-utensils"></i> Today's Feeding — <?= date('d M Y') ?></h2>
        <div class="card-actions">
          <button class="btn btn-primary btn-sm" onclick="togglePanel('add-feed-form')">
            <i class="fas fa-plus"></i> Add Entry
          </button>
        </div>
      </div>

      <?php $pct = $total_scheduled > 0 ? round(($fed_count/$total_scheduled)*100) : 0; ?>
      <div class="feed-summary">
        <span><i class="fas fa-check-circle" style="color:var(--ok)"></i> <?= $fed_count ?> Fed</span>
        <span><i class="fas fa-clock" style="color:var(--warn)"></i> <?= $pending_count ?> Pending</span>
        <span style="color:var(--text-s)"><?= $pct ?>% complete</span>
      </div>
      <div class="feed-progress">
        <div class="feed-progress-fill" style="width:<?= $pct ?>%"></div>
      </div>

      <!-- CREATE: Add Feeding Form -->
      <div class="form-panel" id="add-feed-form">
        <h3><i class="fas fa-plus-circle"></i> Add Feeding Entry</h3>
        <form method="POST" action="add_feeding.php">
          <div class="form-grid">
            <div class="form-group">
              <label>Tortoise</label>
              <select name="tortoise_id" required>
                <option value="">— Select tortoise —</option>
                <?php while($t = $tortoise_list->fetch_assoc()): ?>
                <option value="<?= $t['tortoise_id'] ?>"><?= htmlspecialchars($t['microchip_id'].' — '.$t['name']) ?></option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Food Type</label>
              <input type="text" name="food_type" placeholder="e.g. Mixed Greens" required>
            </div>
            <div class="form-group">
              <label>Feeding Time</label>
              <input type="time" name="feeding_time" required>
            </div>
            <div class="form-group">
              <label>Amount (grams)</label>
              <input type="number" name="amount_grams" step="0.01" placeholder="e.g. 500">
            </div>
            <div class="form-group">
              <label>Scheduled Date</label>
              <input type="date" name="scheduled_date" value="<?= $today ?>">
            </div>
            <div class="form-group">
              <label>Notes (optional)</label>
              <input type="text" name="notes" placeholder="Any special notes…">
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-light" onclick="togglePanel('add-feed-form')">Cancel</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Entry</button>
          </div>
        </form>
      </div>

      <!-- READ: Feeding Schedule Table -->
      <div class="tbl-wrap">
        <table id="feedTable">
          <thead><tr>
            <th>Time</th><th>Tortoise</th><th>Food Type</th>
            <th>Amount</th><th>Status</th><th>Notes</th><th>Actions</th>
          </tr></thead>
          <tbody>
          <?php while($f = $feeding_today->fetch_assoc()): ?>
            <tr>
              <td><strong><?= substr($f['feeding_time'],0,5) ?></strong></td>
              <td>
                <strong><?= htmlspecialchars($f['tortoise_name']) ?></strong><br>
                <small style="color:var(--text-s)"><?= htmlspecialchars($f['microchip_id']) ?></small>
              </td>
              <td><?= htmlspecialchars($f['food_type']) ?></td>
              <td><?= $f['amount_grams'] ? number_format($f['amount_grams'],0).'g' : '—' ?></td>
              <td>
                <span class="badge <?= $f['is_done'] ? 'badge-done' : 'badge-pending' ?>">
                  <?= $f['is_done'] ? 'Fed ✓' : 'Pending' ?>
                </span>
              </td>
              <td><?= htmlspecialchars($f['notes'] ?? '—') ?></td>
              <td style="white-space:nowrap">
                <?php if(!$f['is_done']): ?>
                <!-- UPDATE: Mark as Fed -->
                <a href="mark_fed.php?id=<?= $f['schedule_id'] ?>">
                  <button class="btn btn-ok btn-sm"><i class="fas fa-check"></i> Mark Fed</button>
                </a>
                <?php endif; ?>
                <!-- EDIT feeding entry -->
                <a href="edit_feeding.php?id=<?= $f['schedule_id'] ?>" style="margin-left:4px">
                  <button class="btn btn-light btn-sm"><i class="fas fa-pen"></i> Edit</button>
                </a>
                <!-- DELETE -->
                <a href="delete_feeding.php?id=<?= $f['schedule_id'] ?>"
                   onclick="return confirm('Remove this feeding entry?')" style="margin-left:4px">
                  <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div><!-- /tab-feeding -->


  <!-- ╔══════════════════════════════════════════════════════╗
       ║  TAB 3 — HEALTH REPORTS                             ║
       ╚══════════════════════════════════════════════════════╝ -->
  <div class="tab-panel" id="tab-health">

    <div class="card">
      <div class="card-header">
        <h2><i class="fas fa-stethoscope"></i> Health Reports to Veterinarian</h2>
        <div class="card-actions">
          <button class="btn btn-primary btn-sm" onclick="togglePanel('add-health-form')">
            <i class="fas fa-plus"></i> Report Health Issue
          </button>
          <button class="btn btn-light btn-sm" onclick="toggleSearch('health-search')">
            <i class="fas fa-search"></i> Search
          </button>
        </div>
      </div>

      <!-- CREATE: Report Health Issue -->
      <div class="form-panel" id="add-health-form">
        <h3><i class="fas fa-notes-medical"></i> Report Health Issue to Veterinarian</h3>
        <form method="POST" action="add_health_report.php">
          <div class="form-grid">
            <div class="form-group">
              <label>Assessment Code</label>
              <input type="text" name="assessment_code" placeholder="e.g. ASS-2025-001" required>
            </div>
            <div class="form-group">
              <label>Date</label>
              <input type="date" name="assessment_date" value="<?= $today ?>" required>
            </div>
            <div class="form-group">
              <label>Tortoise</label>
              <select name="tortoise_id" required>
                <option value="">— Select tortoise —</option>
                <?php while($t = $tortoise_list2->fetch_assoc()): ?>
                <option value="<?= $t['tortoise_id'] ?>">
                  <?= htmlspecialchars($t['microchip_id'].' — '.$t['name'].' ('.$t['health_status'].')') ?>
                </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Health Condition</label>
              <input type="text" name="health_condition" placeholder="e.g. Lethargic, Not eating">
            </div>
            <div class="form-group span2">
              <label>Diagnosis / Issue Observed</label>
              <textarea name="diagnosis" placeholder="Describe what you observed that needs vet attention…" required></textarea>
            </div>
            <div class="form-group span2">
              <label>Remarks</label>
              <textarea name="remarks" placeholder="Any additional notes for the vet…"></textarea>
            </div>
          </div>
          <div class="form-actions">
            <button type="button" class="btn btn-light" onclick="togglePanel('add-health-form')">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-paper-plane"></i> Send to Veterinarian
            </button>
          </div>
        </form>
      </div>

      <!-- Search -->
      <div class="search-box" id="health-search">
        <input type="text" placeholder="Search health reports…" oninput="filterTable('healthTable',this.value)">
      </div>

      <!-- READ: Health Reports Table -->
      <div class="tbl-wrap">
        <table id="healthTable">
          <thead><tr>
            <th>Code</th><th>Date</th><th>Tortoise</th>
            <th>Condition</th><th>Diagnosis</th>
            <th>Vet Assigned</th><th>Next Checkup</th>
          </tr></thead>
          <tbody>
          <?php while($h = $health_reports->fetch_assoc()): ?>
            <tr>
              <td><code style="font-size:.78rem;background:var(--sand);padding:.1rem .4rem;border-radius:5px"><?= htmlspecialchars($h['assessment_code'] ?? '—') ?></code></td>
              <td><?= $h['assessment_date'] ?></td>
              <td>
                <strong><?= htmlspecialchars($h['tortoise_name']) ?></strong><br>
                <small style="color:var(--text-s)"><?= htmlspecialchars($h['microchip_id']) ?></small>
              </td>
              <td><?= htmlspecialchars($h['health_condition'] ?? '—') ?></td>
              <td style="max-width:200px"><?= htmlspecialchars($h['diagnosis'] ?? '—') ?></td>
              <td><?= htmlspecialchars($h['vet_name']) ?></td>
              <td><?= $h['next_checkup_date'] ?? '—' ?></td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div><!-- /tab-health -->

</div><!-- /page -->

<script>
/* ── Tab Switching ─────────────────────────────────────────────── */
function switchTab(id, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-'+id).classList.add('active');
  btn.classList.add('active');
}

/* ── Toggle inline form panels ─────────────────────────────────── */
function togglePanel(id) {
  document.getElementById(id).classList.toggle('open');
}

/* ── Toggle search boxes ───────────────────────────────────────── */
function toggleSearch(id) {
  const box = document.getElementById(id);
  box.classList.toggle('open');
  if (box.classList.contains('open')) box.querySelector('input').focus();
}

/* ── Filter table rows ─────────────────────────────────────────── */
function filterTable(tableId, query) {
  query = query.toLowerCase();
  document.querySelectorAll('#'+tableId+' tbody tr').forEach(row => {
    row.style.display = row.innerText.toLowerCase().includes(query) ? '' : 'none';
  });
}

/* ── Filter tortoise cards ─────────────────────────────────────── */
function filterCards(query) {
  query = query.toLowerCase();
  document.querySelectorAll('.tortoise-card').forEach(card => {
    card.style.display = card.dataset.search.includes(query) ? '' : 'none';
  });
}

/* ── Auto-open tab from URL param ──────────────────────────────── */
const urlParams = new URLSearchParams(window.location.search);
const tab = urlParams.get('tab');
if (tab) {
  const btn = document.querySelector(`.tab-btn[onclick*="'${tab}'"]`);
  if (btn) switchTab(tab, btn);
}
</script>
</body>
</html>