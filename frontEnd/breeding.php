<?php
require_once '../backEnd/includes/session.php';
require_once '../backEnd/config/db.php';

// -------------------------------
// Fetch all breeding pairs with details
// -------------------------------
$pairsQuery = "
    SELECT 
        bp.pair_id,
        bp.pair_code,
        CONCAT(m.name, ' (', m.microchip_id, ')') AS male,
        CONCAT(f.name, ' (', f.microchip_id, ')') AS female,
        s.common_name AS species,
        bp.pairing_date AS mating_date,
        COALESCE(n.nest_code, '—') AS nest_id,
        bp.status
    FROM breeding_pairs bp
    JOIN tortoises m ON bp.male_tortoise_id = m.tortoise_id
    JOIN tortoises f ON bp.female_tortoise_id = f.tortoise_id
    JOIN species s ON m.species_id = s.species_id
    LEFT JOIN nests n ON bp.pair_id = n.pair_id AND n.actual_hatch_date IS NULL
    ORDER BY bp.pairing_date DESC
";
$pairsResult = $conn->query($pairsQuery);
$breedingPairs = [];
while ($row = $pairsResult->fetch_assoc()) {
    $breedingPairs[] = $row;
}

// -------------------------------
// Fetch all nests with pair codes
// -------------------------------
$nestsQuery = "
    SELECT 
        n.nest_id,
        n.nest_code,
        bp.pair_code,
        n.nesting_date,
        n.egg_count,
        n.fertile_eggs AS fertile,
        i.incubator_code AS incubator,
        IFNULL(n.actual_hatch_date, n.estimated_hatch_date) AS est_hatch
    FROM nests n
    JOIN breeding_pairs bp ON n.pair_id = bp.pair_id
    LEFT JOIN incubators i ON n.incubator_id = i.incubator_id
    ORDER BY n.nesting_date DESC
";
$nestsResult = $conn->query($nestsQuery);
$nests = [];
while ($row = $nestsResult->fetch_assoc()) {
    $nests[] = $row;
}

// -------------------------------
// Compute statistics
// -------------------------------
$activePairs = 0;
$totalEggs = 0;
$totalFertile = 0;
$totalHatched = 0;
foreach ($breedingPairs as $pair) {
    if (in_array($pair['status'], ['paired', 'incubating', 'courting'])) {
        $activePairs++;
    }
}
foreach ($nests as $nest) {
    $totalEggs += $nest['egg_count'];
    $totalFertile += $nest['fertile'];
    if (strpos($nest['est_hatch'], 'hatched') !== false) {
        $totalHatched += $nest['egg_count'];
    }
}
$fertilityRate = $totalEggs > 0 ? round(($totalFertile / $totalEggs) * 100, 1) : 0;
$hatchRate = $totalFertile > 0 ? round(($totalHatched / $totalFertile) * 100, 1) : 0;

// -------------------------------
// Trend data: active pairs per month (last 6 months)
// -------------------------------
$months = [];
$monthlyActive = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('M Y', strtotime("-$i months"));
    $months[] = $month;
    $end = date('Y-m-t', strtotime("-$i months"));
    $countQuery = $conn->prepare("
        SELECT COUNT(*) as cnt FROM breeding_pairs 
        WHERE pairing_date <= ? AND status != 'separated'
    ");
    $countQuery->bind_param("s", $end);
    $countQuery->execute();
    $cnt = $countQuery->get_result()->fetch_assoc()['cnt'];
    $monthlyActive[] = $cnt;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Breeding Officer Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: #e7f0ea; display: flex; justify-content: center; padding: 2rem 1.5rem; }
        .dashboard { max-width: 1400px; width: 100%; }
        .header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .title-section h1 { font-size: 2.2rem; font-weight: 600; color: #1f4f3d; }
        .badge-role { background: #cfe8db; padding: 0.25rem 1rem; border-radius: 40px; font-size: 0.9rem; color: #0f4633; }
        .date-info { background: white; padding: 0.6rem 1.5rem; border-radius: 40px; }
        .navbar { background: white; border-radius: 60px; padding: 0.8rem 2rem; margin-bottom: 2rem; display: flex; justify-content: space-between; flex-wrap: wrap; }
        .nav-links { display: flex; gap: 1.8rem; flex-wrap: wrap; }
        .nav-item { cursor: pointer; display: flex; align-items: center; gap: 0.5rem; color: #1f5b43; font-weight: 600; border-bottom: 3px solid transparent; }
        .nav-item.active { border-bottom-color: #2a7254; }
        .logout-btn { background: #fff3ec; border: 1.5px solid #f3bc9a; color: #aa4e1e; padding: 0.6rem 1.8rem; border-radius: 40px; cursor: pointer; }
        .stats-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1.2rem; margin-bottom: 2.5rem; }
        .stat-card { background: white; padding: 1.2rem; border-radius: 24px; display: flex; align-items: center; gap: 0.8rem; }
        .stat-card i { font-size: 2rem; color: #2c7a5a; background: #e2f3ea; padding: 0.8rem; border-radius: 50%; }
        .stat-info h3 { font-size: 1.6rem; color: #144b38; }
        .table-wrapper { background: white; border-radius: 28px; padding: 1.5rem; margin-bottom: 2.5rem; display: none; }
        .table-wrapper.active-table { display: block; }
        .table-header { display: flex; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; }
        .btn { background: white; border: 1.5px solid #c0ddcf; padding: 0.6rem 1.3rem; border-radius: 40px; cursor: pointer; }
        .btn-add { background: #1e5f45; color: white; }
        .btn-search { background: #f0f6f2; color: #1b6144; }
        .btn-sm { padding: 0.3rem 0.8rem; font-size: 0.8rem; }
        .btn-edit-row { background: #e6f0fe; border-color: #a9c9fa; color: #1e5090; margin-right: 0.5rem; text-decoration: none; display: inline-block; }
        .btn-delete-row { background: #fef3e9; border-color: #f3bc9a; color: #9b4e20; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.85rem 0.5rem; border-bottom: 1px solid #dcf0e7; text-align: left; }
        th { color: #1f5b43; border-bottom: 2px solid #c6e2d4; }
        .badge-status { background: #daf1e8; padding: 0.2rem 0.8rem; border-radius: 50px; font-size: 0.8rem; }
        .graph-container { margin-top: 2rem; padding: 1rem; background: #f9fdfb; border-radius: 20px; }
        canvas { max-height: 300px; width: 100%; }
        .search-container { margin-bottom: 1rem; display: flex; justify-content: flex-end; }
        .search-input { padding: 0.5rem 1rem; border-radius: 40px; border: 1px solid #c0ddcf; width: 250px; margin-right: 0.5rem; }
        @media (max-width: 700px) { .navbar { flex-direction: column; } }
    </style>
</head>
<body>
<div class="dashboard">
    <div class="header">
        <div class="title-section">
            <h1><i class="fas fa-leaf"></i> Breeding Officer · Tortoise Center</h1>
            <p><i class="fas fa-paw"></i> Pairing, nesting, and incubation management</p>
        </div>
        <div class="date-info">
            <i class="far fa-calendar-alt"></i> <?php echo date('d M Y'); ?>
            <span class="badge-role">BREEDING OFFICER</span>
        </div>
    </div>

    <div class="navbar">
        <div class="nav-links">
            <span class="nav-item active" data-table="pairs"><i class="fas fa-paw"></i> Breeding pairs</span>
            <span class="nav-item" data-table="nesting"><i class="fas fa-egg"></i> Nesting</span>
        </div>
        <div class="logout-btn" onclick="window.location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> Logout</div>
    </div>

    <div class="stats-cards">
        <div class="stat-card"><i class="fas fa-heart"></i><div class="stat-info"><h3><?php echo $activePairs; ?></h3><span>active pairs</span></div></div>
        <div class="stat-card"><i class="fas fa-egg"></i><div class="stat-info"><h3><?php echo $totalEggs; ?></h3><span>total eggs (active nests)</span></div></div>
        <div class="stat-card"><i class="fas fa-seedling"></i><div class="stat-info"><h3><?php echo $fertilityRate; ?>%</h3><span>fertility rate</span></div></div>
        <div class="stat-card"><i class="fas fa-chart-line"></i><div class="stat-info"><h3><?php echo $hatchRate; ?>%</h3><span>hatch success (last 12m)</span></div></div>
    </div>

    <!-- BREEDING PAIRS SECTION -->
    <div class="table-wrapper active-table" id="pairs">
        <div class="table-header">
            <h2><i class="fas fa-hand-holding-heart"></i> Breeding pairs</h2>
            <div class="action-bar">
                <button class="btn btn-add" onclick="window.location.href='add_breeding_pair.php'"><i class="fas fa-plus"></i> Add pair</button>
                <button class="btn btn-search" id="searchPairBtn"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
        <div class="search-container" id="pairSearchBox" style="display: none;">
            <input type="text" id="pairSearchInput" class="search-input" placeholder="Search by Pair ID, Male, Female...">
        </div>
        <table id="pairsTable">
            <thead>
                <tr><th>Pair ID</th><th>Male</th><th>Female</th><th>Species</th><th>Mating date</th><th>Nest ID</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="pairsTableBody">
                <?php foreach ($breedingPairs as $pair): ?>
                <tr data-id="<?php echo $pair['pair_id']; ?>">
                    <td><?php echo htmlspecialchars($pair['pair_code']); ?></td>
                    <td><?php echo htmlspecialchars($pair['male']); ?></td>
                    <td><?php echo htmlspecialchars($pair['female']); ?></td>
                    <td><?php echo htmlspecialchars($pair['species']); ?></td>
                    <td><?php echo htmlspecialchars($pair['mating_date']); ?></td>
                    <td><?php echo htmlspecialchars($pair['nest_id']); ?></td>
                    <td><span class="badge-status"><?php echo htmlspecialchars($pair['status']); ?></span></td>
                    <td>
                        <a href="edit_breeding_pair.php?id=<?php echo $pair['pair_id']; ?>" class="btn btn-sm btn-edit-row"><i class="fas fa-edit"></i> Edit</a>
                        <button class="btn btn-sm btn-delete-row" data-id="<?php echo $pair['pair_id']; ?>" data-type="pair"><i class="fas fa-trash-alt"></i> Delete</button>
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

    <!-- NESTING SECTION -->
    <div class="table-wrapper" id="nesting">
        <div class="table-header">
            <h2><i class="fas fa-egg"></i> Nesting & egg records</h2>
            <div class="action-bar">
                <button class="btn btn-add" onclick="window.location.href='add_nest.php'"><i class="fas fa-plus"></i> Add nest</button>
                <button class="btn btn-search" id="searchNestBtn"><i class="fas fa-search"></i> Search</button>
            </div>
        </div>
        <div class="search-container" id="nestSearchBox" style="display: none;">
            <input type="text" id="nestSearchInput" class="search-input" placeholder="Search by Nest ID, Pair ID...">
        </div>
        <table id="nestsTable">
            <thead>
                <tr><th>Nest ID</th><th>Pair ID</th><th>Nesting date</th><th>Egg count</th><th>Fertile</th><th>Incubator</th><th>Est. hatch</th><th>Actions</th></tr>
            </thead>
            <tbody id="nestsTableBody">
                <?php foreach ($nests as $nest): ?>
                <tr data-id="<?php echo $nest['nest_id']; ?>">
                    <td><?php echo htmlspecialchars($nest['nest_code']); ?></td>
                    <td><?php echo htmlspecialchars($nest['pair_code']); ?></td>
                    <td><?php echo htmlspecialchars($nest['nesting_date']); ?></td>
                    <td><?php echo $nest['egg_count']; ?></td>
                    <td><?php echo $nest['fertile']; ?></td>
                    <td><?php echo htmlspecialchars($nest['incubator'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($nest['est_hatch']); ?></td>
                    <td>
                        <a href="edit_nest.php?id=<?php echo $nest['nest_id']; ?>" class="btn btn-sm btn-edit-row"><i class="fas fa-edit"></i> Edit</a>
                        <button class="btn btn-sm btn-delete-row" data-id="<?php echo $nest['nest_id']; ?>" data-type="nest"><i class="fas fa-trash-alt"></i> Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="graph-container">
            <h3><i class="fas fa-chart-bar"></i> Egg production & fertility (by nest)</h3>
            <canvas id="eggsChart"></canvas>
        </div>
    </div>
</div>

<script>
// Pass PHP data to JavaScript for charts
const months = <?php echo json_encode($months); ?>;
const monthlyActive = <?php echo json_encode($monthlyActive); ?>;
const nestLabels = <?php echo json_encode(array_column($nests, 'nest_code')); ?>;
const eggCounts = <?php echo json_encode(array_column($nests, 'egg_count')); ?>;
const fertileCounts = <?php echo json_encode(array_column($nests, 'fertile')); ?>;

let pairsChart, eggsChart;

function initCharts() {
    const ctxPairs = document.getElementById('pairsTrendChart').getContext('2d');
    pairsChart = new Chart(ctxPairs, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Active breeding pairs',
                data: monthlyActive,
                borderColor: '#2c7a5a',
                backgroundColor: 'rgba(44,122,90,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });

    const ctxEggs = document.getElementById('eggsChart').getContext('2d');
    eggsChart = new Chart(ctxEggs, {
        type: 'bar',
        data: {
            labels: nestLabels,
            datasets: [
                { label: 'Total eggs', data: eggCounts, backgroundColor: '#34855e', borderRadius: 8 },
                { label: 'Fertile eggs', data: fertileCounts, backgroundColor: '#f3bc9a', borderRadius: 8 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: true }
    });
}

function initNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const tables = {
        pairs: document.getElementById('pairs'),
        nesting: document.getElementById('nesting')
    };
    function showTable(tableId, el) {
        Object.values(tables).forEach(t => t.classList.remove('active-table'));
        navItems.forEach(i => i.classList.remove('active'));
        tables[tableId].classList.add('active-table');
        el.classList.add('active');
        setTimeout(() => { if (pairsChart) pairsChart.resize(); if (eggsChart) eggsChart.resize(); }, 100);
    }
    navItems.forEach(item => {
        item.addEventListener('click', () => showTable(item.dataset.table, item));
    });
}

function setupSearch() {
    const searchPairBtn = document.getElementById('searchPairBtn');
    const searchNestBtn = document.getElementById('searchNestBtn');
    const pairSearchBox = document.getElementById('pairSearchBox');
    const nestSearchBox = document.getElementById('nestSearchBox');
    const pairInput = document.getElementById('pairSearchInput');
    const nestInput = document.getElementById('nestSearchInput');

    if (searchPairBtn) {
        searchPairBtn.addEventListener('click', () => {
            pairSearchBox.style.display = pairSearchBox.style.display === 'none' ? 'flex' : 'none';
            if (pairSearchBox.style.display === 'flex') pairInput.focus();
        });
    }
    if (searchNestBtn) {
        searchNestBtn.addEventListener('click', () => {
            nestSearchBox.style.display = nestSearchBox.style.display === 'none' ? 'flex' : 'none';
            if (nestSearchBox.style.display === 'flex') nestInput.focus();
        });
    }

    if (pairInput) {
        pairInput.addEventListener('input', () => {
            const term = pairInput.value.toLowerCase();
            document.querySelectorAll('#pairsTableBody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }
    if (nestInput) {
        nestInput.addEventListener('input', () => {
            const term = nestInput.value.toLowerCase();
            document.querySelectorAll('#nestsTableBody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
            });
        });
    }
}

function attachDeleteButtons() {
    document.querySelectorAll('.btn-delete-row').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Delete this record permanently? This action cannot be undone.')) return;
            
            const id = btn.dataset.id;
            const type = btn.dataset.type;
            let url = '';
            let body = '';
            
            if (type === 'pair') {
                url = '../backEnd/process/delete_breeding_pair.php';
                body = `pair_id=${id}`;
            } else if (type === 'nest') {
                url = '../backEnd/process/delete_nest.php';
                body = `nest_id=${id}`;
            }
            
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body
                });
                
                if (!response.ok) {
                    const text = await response.text();
                    console.error('Server response:', text);
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ Delete failed: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                console.error('Fetch error:', err);
                alert('Error connecting to server. Please check the console for details.\n\n' + err.message);
            }
        });
    });
}

window.addEventListener('DOMContentLoaded', () => {
    initCharts();
    initNavigation();
    setupSearch();
    attachDeleteButtons();
});
</script>
</body>
</html>