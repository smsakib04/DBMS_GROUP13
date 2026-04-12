<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

// ── Breeding pairs ────────────────────────────────────────────
$breedingPairs = [];
$res = $conn->query("
    SELECT
        bp.pair_id,
        bp.pair_code,
        bp.pairing_date,
        bp.status,
        bp.notes,
        bp.male_tortoise_id,
        bp.female_tortoise_id,
        CONCAT(m.name,' (',m.microchip_id,')') AS male,
        CONCAT(f.name,' (',f.microchip_id,')') AS female,
        s.common_name AS species,
        COALESCE(n.nest_code,'—') AS nest_code
    FROM breeding_pairs bp
    JOIN tortoises m ON bp.male_tortoise_id   = m.tortoise_id
    JOIN tortoises f ON bp.female_tortoise_id  = f.tortoise_id
    JOIN species   s ON m.species_id           = s.species_id
    LEFT JOIN nests n ON bp.pair_id = n.pair_id AND n.actual_hatch_date IS NULL
    ORDER BY bp.pairing_date DESC
");
while ($row = $res->fetch_assoc()) $breedingPairs[] = $row;

// ── Nests ─────────────────────────────────────────────────────
$nests = [];
$res = $conn->query("
    SELECT
        n.nest_id,
        n.nest_code,
        n.pair_id,
        n.nesting_date,
        n.egg_count,
        n.fertile_eggs,
        n.incubator_id,
        n.estimated_hatch_date,
        n.actual_hatch_date,
        n.notes,
        bp.pair_code,
        COALESCE(i.incubator_code,'—') AS incubator_code,
        COALESCE(n.actual_hatch_date, n.estimated_hatch_date) AS est_hatch
    FROM nests n
    JOIN breeding_pairs bp ON n.pair_id = bp.pair_id
    LEFT JOIN incubators i ON n.incubator_id = i.incubator_id
    ORDER BY n.nesting_date DESC
");
while ($row = $res->fetch_assoc()) $nests[] = $row;

// ── Dropdowns for modals ──────────────────────────────────────
$males = [];
$res = $conn->query("SELECT tortoise_id, name, microchip_id FROM tortoises WHERE sex='Male' ORDER BY name");
while ($r = $res->fetch_assoc()) $males[] = $r;

$females = [];
$res = $conn->query("SELECT tortoise_id, name, microchip_id FROM tortoises WHERE sex='Female' ORDER BY name");
while ($r = $res->fetch_assoc()) $females[] = $r;

$incubators = [];
$res = $conn->query("SELECT incubator_id, incubator_code FROM incubators WHERE status='active' ORDER BY incubator_code");
while ($r = $res->fetch_assoc()) $incubators[] = $r;

$pairsList = [];
$res = $conn->query("SELECT pair_id, pair_code FROM breeding_pairs ORDER BY pair_code");
while ($r = $res->fetch_assoc()) $pairsList[] = $r;

// ── Stats ─────────────────────────────────────────────────────
$activePairs = 0;
$totalEggs   = 0;
$totalFertile = 0;
foreach ($breedingPairs as $p) {
    if (in_array($p['status'], ['paired','courting','incubating'])) $activePairs++;
}
foreach ($nests as $n) {
    $totalEggs    += (int)$n['egg_count'];
    $totalFertile += (int)$n['fertile_eggs'];
}
$fertilityRate = $totalEggs > 0 ? round(($totalFertile / $totalEggs) * 100, 1) : 0;

$hatched = (int)$conn->query("SELECT COUNT(*) c FROM breeding_pairs WHERE status='hatched'")->fetch_assoc()['c'];
$total   = (int)$conn->query("SELECT COUNT(*) c FROM breeding_pairs")->fetch_assoc()['c'];
$hatchRate = $total > 0 ? round(($hatched / $total) * 100, 1) : 0;

// ── Chart: pairs trend last 6 months ─────────────────────────
$months = []; $monthlyActive = [];
for ($i = 5; $i >= 0; $i--) {
    $months[] = date('M Y', strtotime("-$i months"));
    $end = date('Y-m-t', strtotime("-$i months"));
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM breeding_pairs WHERE pairing_date<=? AND status!='separated'");
    $stmt->bind_param('s', $end);
    $stmt->execute();
    $monthlyActive[] = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
}

// ── Chart: eggs per nest ──────────────────────────────────────
$nestLabels  = array_column($nests, 'nest_code');
$eggCounts   = array_map('intval', array_column($nests, 'egg_count'));
$fertileCounts = array_map('intval', array_column($nests, 'fertile_eggs'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Breeding Officer Dashboard · Tortoise Center</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',system-ui,sans-serif}
body{background:#e7f0ea;display:flex;justify-content:center;padding:2rem 1.5rem}
.dashboard{max-width:1400px;width:100%}
.header{display:flex;justify-content:space-between;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem}
.title-section h1{font-size:2.2rem;font-weight:600;color:#1f4f3d}
.badge-role{background:#cfe8db;padding:.25rem 1rem;border-radius:40px;font-size:.9rem;font-weight:600;color:#0f4633;border:1px solid #a2cdbb}
.date-info{background:white;padding:.6rem 1.5rem;border-radius:40px;box-shadow:0 4px 8px rgba(0,40,20,.04);font-weight:500;color:#23634b;border:1px solid #bcddce}
.navbar{background:white;border-radius:60px;padding:.8rem 2rem;margin-bottom:2rem;box-shadow:0 12px 24px -10px rgba(28,78,58,.2);border:1px solid #c0e0d0;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between}
.nav-links{display:flex;flex-wrap:wrap;gap:1.8rem;align-items:center}
.nav-item{display:flex;align-items:center;gap:.5rem;color:#1f5b43;font-weight:600;font-size:1rem;padding:.4rem 0;border-bottom:3px solid transparent;cursor:pointer;transition:.1s}
.nav-item.active{border-bottom-color:#2a7254;color:#0c402e}
.logout-btn{background:#fff3ec;border:1.5px solid #f3bc9a;color:#aa4e1e;padding:.6rem 1.8rem;border-radius:40px;font-weight:700;display:inline-flex;align-items:center;gap:.6rem;cursor:pointer;text-decoration:none;transition:.2s}
.logout-btn:hover{background:#fde8dc}
.stats-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1.2rem;margin-bottom:2.5rem}
.stat-card{background:white;padding:1.2rem 1rem;border-radius:24px;box-shadow:0 10px 20px rgba(26,80,60,.08);border:1px solid #d2eadf;display:flex;align-items:center;gap:.8rem}
.stat-card i{font-size:2rem;color:#2c7a5a;background:#e2f3ea;padding:.8rem;border-radius:50%}
.stat-info h3{font-size:1.6rem;font-weight:700;color:#144b38;line-height:1.2}
.stat-info span{color:#487a64;font-weight:500;font-size:.9rem}
.table-wrapper{background:white;border-radius:28px;padding:1.5rem 1.5rem 2rem;margin-bottom:2.5rem;box-shadow:0 18px 30px -8px rgba(28,78,58,.14);border:1px solid #cfeadb;display:none}
.table-wrapper.active-table{display:block}
.table-header{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:1.5rem}
.table-header h2{font-size:1.7rem;font-weight:600;color:#1a4e3a;display:flex;align-items:center;gap:.6rem}
.action-bar{display:flex;gap:.8rem;flex-wrap:wrap}
.btn{background:white;border:1.5px solid #c0ddcf;color:#215d45;font-weight:600;padding:.6rem 1.3rem;border-radius:40px;font-size:.9rem;display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;transition:.2s;text-decoration:none}
.btn-add{background:#1e5f45;border-color:#1e5f45;color:white}
.btn-search{background:#f0f6f2;border-color:#b0d5c2;color:#1b6144}
.btn-sm{padding:.35rem .85rem;font-size:.8rem}
.btn-edit-row{background:#e6f0fe;border-color:#a9c9fa;color:#1e5090}
.btn-delete-row{background:#fef3e9;border-color:#f3bc9a;color:#9b4e20}
table{width:100%;border-collapse:collapse;font-size:.95rem;color:#173e2f}
th{text-align:left;padding:1rem .5rem .8rem;font-weight:700;color:#1f5b43;border-bottom:2px solid #c6e2d4}
td{padding:.85rem .5rem;border-bottom:1px solid #dcf0e7;vertical-align:middle}
.badge-status{padding:.2rem .8rem;border-radius:50px;font-weight:600;font-size:.8rem;display:inline-block}
.badge-paired{background:#ddf0e8;color:#16674a}
.badge-courting{background:#e8f4fe;color:#1a5a9e}
.badge-incubating{background:#fff8dc;color:#8a6a00}
.badge-hatched{background:#d4f7e2;color:#1a6e3f}
.badge-separated{background:#ffe8e8;color:#a01e1e}
.graph-container{margin-top:2rem;padding:1rem;background:#f9fdfb;border-radius:20px;border:1px solid #d0e8dd}
.graph-container h3{color:#1f5b43;margin-bottom:1rem;font-size:1.2rem}
canvas{max-height:300px;width:100%}
.search-container{margin-bottom:1rem;display:flex;justify-content:flex-end}
.search-input{padding:.5rem 1rem;border-radius:40px;border:1px solid #c0ddcf;width:260px}
/* Modals */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:white;border-radius:24px;padding:2rem;width:100%;max-width:540px;box-shadow:0 30px 60px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto}
.modal h3{color:#1a4e3a;margin-bottom:1.2rem;font-size:1.3rem}
.form-group{margin-bottom:1rem}
.form-group label{display:block;font-weight:600;color:#265740;margin-bottom:.3rem;font-size:.9rem}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:.65rem 1rem;border:1.5px solid #c0ddd0;border-radius:12px;font-size:.95rem;color:#1a3d2f;background:#f9fdfa;outline:none;transition:.2s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#2a7254;background:white}
.form-group textarea{resize:vertical;min-height:70px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.modal-footer{display:flex;gap:.8rem;justify-content:flex-end;margin-top:1.5rem}
.btn-cancel{background:#f4f4f4;border-color:#ddd;color:#555}
.btn-save{background:#1e5f45;border-color:#1e5f45;color:white}
.flash{padding:.8rem 1.2rem;border-radius:14px;margin-bottom:1rem;font-weight:600;font-size:.9rem}
.flash-success{background:#d4edda;color:#155724;border:1px solid #c3e6cb}
.flash-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb}
@media(max-width:750px){.navbar{flex-direction:column;gap:1rem;border-radius:32px}.form-row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="dashboard">

  <!-- Header -->
  <div class="header">
    <div class="title-section">
      <h1><i class="fas fa-leaf" style="color:#2c8f65"></i> Breeding Officer · Tortoise Center</h1>
      <p style="color:#3d6b58;font-weight:500;margin-top:.2rem"><i class="fas fa-paw"></i> Pairing, nesting, and incubation management</p>
    </div>
    <div class="date-info">
      <i class="far fa-calendar-alt"></i> <?php echo date('d M Y'); ?>
      <span class="badge-role">BREEDING OFFICER</span>
    </div>
  </div>

  <!-- Navbar -->
  <div class="navbar">
    <div class="nav-links">
      <span class="nav-item active" data-table="pairs"><i class="fas fa-paw"></i> Breeding pairs</span>
      <span class="nav-item" data-table="nesting"><i class="fas fa-egg"></i> Nesting</span>
    </div>
    <a class="logout-btn" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </div>

  <!-- Stats -->
  <div class="stats-cards">
    <div class="stat-card"><i class="fas fa-heart"></i><div class="stat-info"><h3><?php echo $activePairs; ?></h3><span>active pairs</span></div></div>
    <div class="stat-card"><i class="fas fa-egg"></i><div class="stat-info"><h3><?php echo $totalEggs; ?></h3><span>total eggs</span></div></div>
    <div class="stat-card"><i class="fas fa-seedling"></i><div class="stat-info"><h3><?php echo $fertilityRate; ?>%</h3><span>fertility rate</span></div></div>
    <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-info"><h3><?php echo $hatchRate; ?>%</h3><span>pairs hatched</span></div></div>
  </div>

  <!-- ══ BREEDING PAIRS ══════════════════════════════════════ -->
  <div class="table-wrapper active-table" id="pairs">
    <div class="table-header">
      <h2><i class="fas fa-hand-holding-heart"></i> Breeding pairs</h2>
      <div class="action-bar">
        <button class="btn btn-add" onclick="openModal('addPairModal')"><i class="fas fa-plus"></i> Add pair</button>
        <button class="btn btn-search" onclick="toggleSearch('pairSearchBox')"><i class="fas fa-search"></i> Search</button>
      </div>
    </div>
    <div class="search-container" id="pairSearchBox" style="display:none">
      <input type="text" id="pairSearchInput" class="search-input" placeholder="Search pair, tortoise, species…"
             oninput="filterTable('pairsTable','pairSearchInput')">
    </div>
    <table id="pairsTable">
      <thead><tr><th>Pair code</th><th>Male</th><th>Female</th><th>Species</th><th>Pairing date</th><th>Nest</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($breedingPairs as $p): ?>
        <?php
          $sc = 'badge-'.strtolower(str_replace(' ','-',$p['status']));
          $notesEsc = addslashes($p['notes'] ?? '');
        ?>
        <tr>
          <td><?php echo htmlspecialchars($p['pair_code']); ?></td>
          <td><?php echo htmlspecialchars($p['male']); ?></td>
          <td><?php echo htmlspecialchars($p['female']); ?></td>
          <td><?php echo htmlspecialchars($p['species']); ?></td>
          <td><?php echo htmlspecialchars($p['pairing_date']); ?></td>
          <td><?php echo htmlspecialchars($p['nest_code']); ?></td>
          <td><span class="badge-status <?php echo $sc; ?>"><?php echo htmlspecialchars($p['status']); ?></span></td>
          <td>
            <button class="btn btn-sm btn-edit-row"
              onclick="openEditPairModal(<?php echo $p['pair_id']; ?>,'<?php echo addslashes($p['pair_code']); ?>',<?php echo $p['male_tortoise_id']; ?>,<?php echo $p['female_tortoise_id']; ?>,'<?php echo $p['pairing_date']; ?>','<?php echo $p['status']; ?>','<?php echo $notesEsc; ?>')">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-sm btn-delete-row"
              onclick="deleteRecord('pair',<?php echo $p['pair_id']; ?>)">
              <i class="fas fa-trash-alt"></i> Delete
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="graph-container">
      <h3><i class="fas fa-chart-line"></i> Active breeding pairs trend (last 6 months)</h3>
      <canvas id="pairsTrendChart"></canvas>
    </div>
  </div>

  <!-- ══ NESTING ═══════════════════════════════════════════════ -->
  <div class="table-wrapper" id="nesting">
    <div class="table-header">
      <h2><i class="fas fa-egg"></i> Nesting &amp; egg records</h2>
      <div class="action-bar">
        <button class="btn btn-add" onclick="openModal('addNestModal')"><i class="fas fa-plus"></i> Add nest</button>
        <button class="btn btn-search" onclick="toggleSearch('nestSearchBox')"><i class="fas fa-search"></i> Search</button>
      </div>
    </div>
    <div class="search-container" id="nestSearchBox" style="display:none">
      <input type="text" id="nestSearchInput" class="search-input" placeholder="Search nest, pair, incubator…"
             oninput="filterTable('nestsTable','nestSearchInput')">
    </div>
    <table id="nestsTable">
      <thead><tr><th>Nest code</th><th>Pair</th><th>Nesting date</th><th>Eggs</th><th>Fertile</th><th>Incubator</th><th>Est. hatch</th><th>Actual hatch</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($nests as $n): ?>
        <tr>
          <td><?php echo htmlspecialchars($n['nest_code']); ?></td>
          <td><?php echo htmlspecialchars($n['pair_code']); ?></td>
          <td><?php echo htmlspecialchars($n['nesting_date']); ?></td>
          <td><?php echo (int)$n['egg_count']; ?></td>
          <td><?php echo (int)$n['fertile_eggs']; ?></td>
          <td><?php echo htmlspecialchars($n['incubator_code']); ?></td>
          <td><?php echo htmlspecialchars($n['estimated_hatch_date'] ?? '—'); ?></td>
          <td><?php echo htmlspecialchars($n['actual_hatch_date'] ?? '—'); ?></td>
          <td>
            <button class="btn btn-sm btn-edit-row"
              onclick="openEditNestModal(<?php echo $n['nest_id']; ?>,'<?php echo addslashes($n['nest_code']); ?>',<?php echo $n['pair_id']; ?>,'<?php echo $n['nesting_date']; ?>',<?php echo (int)$n['egg_count']; ?>,<?php echo (int)$n['fertile_eggs']; ?>,<?php echo ($n['incubator_id'] ?? 'null'); ?>,'<?php echo $n['estimated_hatch_date'] ?? ''; ?>','<?php echo $n['actual_hatch_date'] ?? ''; ?>')">
              <i class="fas fa-edit"></i> Edit
            </button>
            <button class="btn btn-sm btn-delete-row"
              onclick="deleteRecord('nest',<?php echo $n['nest_id']; ?>)">
              <i class="fas fa-trash-alt"></i> Delete
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="graph-container">
      <h3><i class="fas fa-chart-bar"></i> Egg production &amp; fertility (by nest)</h3>
      <canvas id="eggsChart"></canvas>
    </div>
  </div>
</div>

<!-- ══════════════ MODALS ══════════════ -->

<!-- ADD PAIR -->
<div class="modal-overlay" id="addPairModal">
  <div class="modal">
    <h3><i class="fas fa-plus-circle"></i> Add Breeding Pair</h3>
    <div id="addPairFlash"></div>
    <form id="addPairForm">
      <div class="form-group">
        <label>Pair code *</label>
        <input type="text" name="pair_code" required placeholder="e.g. BP-025">
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Male tortoise *</label>
          <select name="male_tortoise_id" required>
            <option value="">— select male —</option>
            <?php foreach($males as $m): ?>
            <option value="<?php echo $m['tortoise_id']; ?>"><?php echo htmlspecialchars($m['name'].' ('.$m['microchip_id'].')'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Female tortoise *</label>
          <select name="female_tortoise_id" required>
            <option value="">— select female —</option>
            <?php foreach($females as $f): ?>
            <option value="<?php echo $f['tortoise_id']; ?>"><?php echo htmlspecialchars($f['name'].' ('.$f['microchip_id'].')'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Pairing date *</label>
          <input type="date" name="pairing_date" required>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <option value="paired">Paired</option>
            <option value="courting">Courting</option>
            <option value="incubating">Incubating</option>
            <option value="hatched">Hatched</option>
            <option value="separated">Separated</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" placeholder="Optional notes…"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-cancel" onclick="closeModal('addPairModal')">Cancel</button>
        <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Save pair</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT PAIR -->
<div class="modal-overlay" id="editPairModal">
  <div class="modal">
    <h3><i class="fas fa-edit"></i> Edit Breeding Pair</h3>
    <div id="editPairFlash"></div>
    <form id="editPairForm">
      <input type="hidden" name="pair_id" id="editPairId">
      <div class="form-group">
        <label>Pair code *</label>
        <input type="text" name="pair_code" id="editPairCode" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Male tortoise *</label>
          <select name="male_tortoise_id" id="editPairMale" required>
            <?php foreach($males as $m): ?>
            <option value="<?php echo $m['tortoise_id']; ?>"><?php echo htmlspecialchars($m['name'].' ('.$m['microchip_id'].')'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Female tortoise *</label>
          <select name="female_tortoise_id" id="editPairFemale" required>
            <?php foreach($females as $f): ?>
            <option value="<?php echo $f['tortoise_id']; ?>"><?php echo htmlspecialchars($f['name'].' ('.$f['microchip_id'].')'); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Pairing date *</label>
          <input type="date" name="pairing_date" id="editPairDate" required>
        </div>
        <div class="form-group">
          <label>Status</label>
          <select name="status" id="editPairStatus">
            <option value="paired">Paired</option>
            <option value="courting">Courting</option>
            <option value="incubating">Incubating</option>
            <option value="hatched">Hatched</option>
            <option value="separated">Separated</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" id="editPairNotes"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-cancel" onclick="closeModal('editPairModal')">Cancel</button>
        <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Update pair</button>
      </div>
    </form>
  </div>
</div>

<!-- ADD NEST -->
<div class="modal-overlay" id="addNestModal">
  <div class="modal">
    <h3><i class="fas fa-plus-circle"></i> Add Nest</h3>
    <div id="addNestFlash"></div>
    <form id="addNestForm">
      <div class="form-row">
        <div class="form-group">
          <label>Nest code *</label>
          <input type="text" name="nest_code" required placeholder="e.g. Nest-D50">
        </div>
        <div class="form-group">
          <label>Breeding pair *</label>
          <select name="pair_id" required>
            <option value="">— select pair —</option>
            <?php foreach($pairsList as $pr): ?>
            <option value="<?php echo $pr['pair_id']; ?>"><?php echo htmlspecialchars($pr['pair_code']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Nesting date *</label>
          <input type="date" name="nesting_date" required>
        </div>
        <div class="form-group">
          <label>Incubator</label>
          <select name="incubator_id">
            <option value="">— none —</option>
            <?php foreach($incubators as $inc): ?>
            <option value="<?php echo $inc['incubator_id']; ?>"><?php echo htmlspecialchars($inc['incubator_code']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Egg count *</label>
          <input type="number" min="0" name="egg_count" required placeholder="e.g. 8">
        </div>
        <div class="form-group">
          <label>Fertile eggs</label>
          <input type="number" min="0" name="fertile_eggs" placeholder="e.g. 6">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Estimated hatch date</label>
          <input type="date" name="estimated_hatch_date">
        </div>
        <div class="form-group">
          <label>Actual hatch date</label>
          <input type="date" name="actual_hatch_date">
        </div>
      </div>
      <div class="form-group">
        <label>Notes</label>
        <textarea name="notes" placeholder="Optional notes…"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-cancel" onclick="closeModal('addNestModal')">Cancel</button>
        <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Save nest</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT NEST -->
<div class="modal-overlay" id="editNestModal">
  <div class="modal">
    <h3><i class="fas fa-edit"></i> Edit Nest</h3>
    <div id="editNestFlash"></div>
    <form id="editNestForm">
      <input type="hidden" name="nest_id" id="editNestId">
      <div class="form-row">
        <div class="form-group">
          <label>Nest code *</label>
          <input type="text" name="nest_code" id="editNestCode" required>
        </div>
        <div class="form-group">
          <label>Breeding pair *</label>
          <select name="pair_id" id="editNestPair" required>
            <?php foreach($pairsList as $pr): ?>
            <option value="<?php echo $pr['pair_id']; ?>"><?php echo htmlspecialchars($pr['pair_code']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Nesting date *</label>
          <input type="date" name="nesting_date" id="editNestDate" required>
        </div>
        <div class="form-group">
          <label>Incubator</label>
          <select name="incubator_id" id="editNestIncubator">
            <option value="">— none —</option>
            <?php foreach($incubators as $inc): ?>
            <option value="<?php echo $inc['incubator_id']; ?>"><?php echo htmlspecialchars($inc['incubator_code']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Egg count *</label>
          <input type="number" min="0" name="egg_count" id="editNestEggs" required>
        </div>
        <div class="form-group">
          <label>Fertile eggs</label>
          <input type="number" min="0" name="fertile_eggs" id="editNestFertile">
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Estimated hatch date</label>
          <input type="date" name="estimated_hatch_date" id="editNestEstHatch">
        </div>
        <div class="form-group">
          <label>Actual hatch date</label>
          <input type="date" name="actual_hatch_date" id="editNestActHatch">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-cancel" onclick="closeModal('editNestModal')">Cancel</button>
        <button type="submit" class="btn btn-save"><i class="fas fa-save"></i> Update nest</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Charts ───────────────────────────────────────────────────
const months       = <?php echo json_encode($months); ?>;
const monthlyAct   = <?php echo json_encode($monthlyActive); ?>;
const nestLabels   = <?php echo json_encode($nestLabels); ?>;
const eggCounts    = <?php echo json_encode($eggCounts); ?>;
const fertileCts   = <?php echo json_encode($fertileCounts); ?>;

let pairsChart, eggsChart;

function initCharts(){
  pairsChart = new Chart(document.getElementById('pairsTrendChart').getContext('2d'),{
    type:'line',
    data:{labels:months,datasets:[{label:'Active pairs',data:monthlyAct,borderColor:'#2c7a5a',backgroundColor:'rgba(44,122,90,.1)',tension:.3,fill:true}]},
    options:{responsive:true,maintainAspectRatio:true,scales:{y:{beginAtZero:true}}}
  });
  eggsChart = new Chart(document.getElementById('eggsChart').getContext('2d'),{
    type:'bar',
    data:{labels:nestLabels,datasets:[
      {label:'Total eggs',data:eggCounts,backgroundColor:'#34855e',borderRadius:6},
      {label:'Fertile eggs',data:fertileCts,backgroundColor:'#f3bc9a',borderRadius:6}
    ]},
    options:{responsive:true,maintainAspectRatio:true,scales:{y:{beginAtZero:true}}}
  });
}

// ── Navigation ───────────────────────────────────────────────
function initNav(){
  document.querySelectorAll('.nav-item').forEach(item=>{
    item.addEventListener('click',()=>{
      document.querySelectorAll('.nav-item').forEach(n=>n.classList.remove('active'));
      document.querySelectorAll('.table-wrapper').forEach(w=>w.classList.remove('active-table'));
      item.classList.add('active');
      document.getElementById(item.dataset.table).classList.add('active-table');
      setTimeout(()=>{pairsChart?.resize();eggsChart?.resize();},100);
    });
  });
}

// ── Search ───────────────────────────────────────────────────
function toggleSearch(id){
  const el=document.getElementById(id);
  el.style.display=el.style.display==='none'?'flex':'none';
  if(el.style.display==='flex') el.querySelector('input').focus();
}
function filterTable(tableId,inputId){
  const q=document.getElementById(inputId).value.toLowerCase();
  document.querySelectorAll(`#${tableId} tbody tr`).forEach(r=>{
    r.style.display=r.innerText.toLowerCase().includes(q)?'':'none';
  });
}

// ── Modals ───────────────────────────────────────────────────
function openModal(id){ document.getElementById(id).classList.add('open'); }
function closeModal(id){ document.getElementById(id).classList.remove('open'); }
document.querySelectorAll('.modal-overlay').forEach(o=>{
  o.addEventListener('click',e=>{ if(e.target===o) o.classList.remove('open'); });
});

function openEditPairModal(id,code,maleId,femaleId,date,status,notes){
  document.getElementById('editPairId').value=id;
  document.getElementById('editPairCode').value=code;
  document.getElementById('editPairMale').value=maleId;
  document.getElementById('editPairFemale').value=femaleId;
  document.getElementById('editPairDate').value=date;
  document.getElementById('editPairStatus').value=status;
  document.getElementById('editPairNotes').value=notes;
  document.getElementById('editPairFlash').innerHTML='';
  openModal('editPairModal');
}

function openEditNestModal(id,code,pairId,date,eggs,fertile,incubatorId,estHatch,actHatch){
  document.getElementById('editNestId').value=id;
  document.getElementById('editNestCode').value=code;
  document.getElementById('editNestPair').value=pairId;
  document.getElementById('editNestDate').value=date;
  document.getElementById('editNestEggs').value=eggs;
  document.getElementById('editNestFertile').value=fertile;
  document.getElementById('editNestIncubator').value=incubatorId||'';
  document.getElementById('editNestEstHatch').value=estHatch||'';
  document.getElementById('editNestActHatch').value=actHatch||'';
  document.getElementById('editNestFlash').innerHTML='';
  openModal('editNestModal');
}

// ── Generic AJAX post ────────────────────────────────────────
async function postForm(url, data, flashEl, successMsg){
  try{
    const res=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams(data).toString()});
    const text=await res.text();
    let j={};
    try{ j=JSON.parse(text); }catch(_){}
    if(res.ok && j.success){
      flashEl.innerHTML=`<div class="flash flash-success">${successMsg}</div>`;
      setTimeout(()=>location.reload(),900);
    } else {
      flashEl.innerHTML=`<div class="flash flash-error">${j.message||'An error occurred.'}</div>`;
    }
  }catch(err){
    flashEl.innerHTML=`<div class="flash flash-error">Network error: ${err.message}</div>`;
  }
}

// ── Form handlers ────────────────────────────────────────────
document.getElementById('addPairForm').addEventListener('submit',async e=>{
  e.preventDefault();
  await postForm('../process/breeding_pair_add.php',Object.fromEntries(new FormData(e.target)),document.getElementById('addPairFlash'),'Breeding pair added!');
});
document.getElementById('editPairForm').addEventListener('submit',async e=>{
  e.preventDefault();
  await postForm('../process/breeding_pair_edit.php',Object.fromEntries(new FormData(e.target)),document.getElementById('editPairFlash'),'Breeding pair updated!');
});
document.getElementById('addNestForm').addEventListener('submit',async e=>{
  e.preventDefault();
  await postForm('../process/breeding_nest_add.php',Object.fromEntries(new FormData(e.target)),document.getElementById('addNestFlash'),'Nest record added!');
});
document.getElementById('editNestForm').addEventListener('submit',async e=>{
  e.preventDefault();
  await postForm('../process/breeding_nest_edit.php',Object.fromEntries(new FormData(e.target)),document.getElementById('editNestFlash'),'Nest record updated!');
});

// ── Delete ───────────────────────────────────────────────────
function deleteRecord(type, id){
  if(!confirm(`Permanently delete this ${type}?`)) return;
  let url='', body='';
  if(type==='pair'){ url='../process/breeding_pair_delete.php'; body=`pair_id=${id}`; }
  else             { url='../process/breeding_nest_delete.php';  body=`nest_id=${id}`; }
  fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body})
    .then(r=>r.json())
    .then(j=>{ if(j.success) location.reload(); else alert('Delete failed: '+j.message); })
    .catch(()=>alert('Network error'));
}

window.addEventListener('DOMContentLoaded',()=>{ initCharts(); initNav(); });
</script>
</body>
</html>
