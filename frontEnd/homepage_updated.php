<?php
require_once '../backEnd/config/db.php';

// Fetch real statistics
$total_tortoises = $conn->query("SELECT COUNT(*) AS cnt FROM tortoises")->fetch_assoc()['cnt'];
$total_species = $conn->query("SELECT COUNT(*) AS cnt FROM species")->fetch_assoc()['cnt'];
$successful_hatchlings = $conn->query("SELECT SUM(egg_count) AS cnt FROM nests WHERE actual_hatch_date IS NOT NULL")->fetch_assoc()['cnt'] ?: 0;
$active_breeding_pairs = $conn->query("SELECT COUNT(*) AS cnt FROM breeding_pairs WHERE status IN ('paired','courting','incubating')")->fetch_assoc()['cnt'];

// Species distribution for pie chart
$speciesQuery = "
    SELECT s.common_name, COUNT(t.tortoise_id) AS count
    FROM tortoises t
    JOIN species s ON t.species_id = s.species_id
    GROUP BY s.species_id
";
$speciesResult = $conn->query($speciesQuery);
$speciesLabels = [];
$speciesCounts = [];
while ($row = $speciesResult->fetch_assoc()) {
    $speciesLabels[] = $row['common_name'];
    $speciesCounts[] = $row['count'];
}

// Monthly hatchling success (last 6 months)
$monthlyHatchlings = [];
$months = [];
for ($i = 5; $i >= 0; $i--) {
    $monthStart = date('Y-m-01', strtotime("-$i months"));
    $monthEnd = date('Y-m-t', strtotime("-$i months"));
    $months[] = date('M Y', strtotime($monthStart));
    $count = $conn->query("
        SELECT SUM(egg_count) AS cnt FROM nests 
        WHERE actual_hatch_date BETWEEN '$monthStart' AND '$monthEnd'
    ")->fetch_assoc()['cnt'] ?: 0;
    $monthlyHatchlings[] = $count;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tortoise Conservation Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e8f3ef 0%, #d4e8df 100%);
            min-height: 100vh;
        }

        /* Hero Section – solid colour #103227, logo fills full height on the left */
        .hero {
            background: #103227;
            color: white;
            text-align: center;
            padding: 2rem 2rem 3rem;
            border-radius: 0 0 48px 48px;
            margin-bottom: 2rem;
            position: relative;
            min-height: 220px;
            padding-left: 140px;
        }

        .hero-logo {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: auto;
            object-fit: cover;
        }

        .hero h1 {
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            padding-top: 0.5rem;
        }

        .hero .tagline {
            font-size: 1.2rem;
            font-style: italic;
            opacity: 0.9;
            letter-spacing: 1px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 1rem 2rem 3rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 28px;
            text-align: center;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
            border: 1px solid #d7eee2;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        /* For the stat card icon (image or font icon) */
        .stat-card .stat-icon {
            width: 70px;
            height: 70px;
            object-fit: contain;
            margin-bottom: 1rem;
            display: inline-block;
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #328f68;
            background: #e3f4ed;
            padding: 0.8rem;
            border-radius: 60px;
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 800;
            color: #1c5f45;
        }

        .stat-card p {
            color: #5b8672;
            font-weight: 500;
        }

        /* Info Sections */
        .info-section {
            background: white;
            border-radius: 32px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
        }

        .info-section h2 {
            color: #1c5d44;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        /* Icon grid for species */
        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .icon-item {
            background: #f9fdfb;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.2s;
            border: 1px solid #d0e8dd;
        }

        .icon-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }

        .icon-item i {
            font-size: 3rem;
            color: #2a7f5c;
            margin-bottom: 0.8rem;
        }

        .icon-item h4 {
            color: #1b6349;
            margin-bottom: 0.3rem;
        }

        .icon-item p {
            color: #5a8874;
            font-size: 0.85rem;
        }

        /* Two‑column layout for charts */
        .charts-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin: 2rem 0;
        }

        .chart-card {
            background: white;
            border-radius: 28px;
            padding: 1.5rem;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.08);
        }

        .chart-card h3 {
            color: #1b6349;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        canvas {
            max-height: 300px;
            width: 100%;
        }

        /* Role cards */
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .role-card {
            background: #f9fdfb;
            border-radius: 24px;
            padding: 1.5rem;
            border: 1px solid #d0e8dd;
            transition: transform 0.2s;
        }

        .role-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .role-card i {
            font-size: 2rem;
            color: #2a7f5c;
            margin-bottom: 0.8rem;
        }

        .role-card h3 {
            color: #1b6349;
            margin-bottom: 0.5rem;
        }

        .role-card p {
            color: #5a8874;
            font-size: 0.9rem;
        }

        .login-btn-container {
            text-align: center;
            margin: 2rem 0;
        }

        .login-btn {
            background: #1f7356;
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            border-radius: 60px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.8rem;
            box-shadow: 0 8px 16px rgba(31, 115, 86, 0.3);
        }

        .login-btn:hover {
            background: #155e46;
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(31, 115, 86, 0.4);
        }

        footer {
            text-align: center;
            padding: 2rem;
            color: #4f7c68;
            border-top: 1px solid #cbe2d7;
            margin-top: 2rem;
        }

        @media (max-width: 850px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
            .hero h1 { font-size: 2rem; }
            .container { padding: 1rem; }
            .hero {
                padding-left: 100px;
            }
        }
    </style>
</head>
<body>

<div class="hero">
    <img src="images/logo.png" alt="Tortoise Conservation Center Logo" class="hero-logo">
    <h1>Tortoise Conservation Center</h1>
    <div class="tagline">PROTECTING THE SLOWEST SINCE 1995</div>
</div>

<div class="container">
    <!-- Stats Cards (dynamic) -->
    <div class="stats-grid">
        <div class="stat-card">
            <!-- Tortoise logo image (replace with your actual image path) -->
            <img src="images/tortoise-icon.png" alt="Tortoise Logo" class="stat-icon">
            <h3><?php echo $total_tortoises; ?></h3>
            <p>Total Tortoises</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-dna"></i>
            <h3><?php echo $total_species; ?></h3>
            <p>Species</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-egg"></i>
            <h3><?php echo $successful_hatchlings; ?></h3>
            <p>Hatchlings (all time)</p>
        </div>
        <div class="stat-card">
            <i class="fas fa-heart"></i>
            <h3><?php echo $active_breeding_pairs; ?></h3>
            <p>Active Breeding Pairs</p>
        </div>
    </div>

    <!-- Featured Species (Icon Grid) -->
    <div class="info-section">
        <h2><i class="fas fa-star-of-life"></i> Featured Species</h2>
        <div class="icon-grid">
            <div class="icon-item">
                <i class="fas fa-turtle"></i>
                <h4>Aldabra Giant</h4>
                <p>One of the largest tortoise species, native to the Aldabra Atoll.</p>
            </div>
            <div class="icon-item">
                <i class="fas fa-paw"></i>
                <h4>African Spurred</h4>
                <p>Third-largest species, known for its distinctive spurred legs.</p>
            </div>
            <div class="icon-item">
                <i class="fas fa-leaf"></i>
                <h4>Leopard Tortoise</h4>
                <p>Named for its beautiful shell patterns resembling a leopard.</p>
            </div>
            <div class="icon-item">
                <i class="fas fa-globe"></i>
                <h4>Galápagos</h4>
                <p>Iconic giant tortoises from the Galápagos Islands.</p>
            </div>
        </div>
        <p style="margin-top: 1rem; font-size: 0.9rem; color: #5a8874;"><i class="fas fa-info-circle"></i> These species are part of our conservation and breeding programs.</p>
    </div>

    <!-- Charts Row -->
    <div class="charts-row">
        <div class="chart-card">
            <h3><i class="fas fa-chart-pie"></i> Species Distribution</h3>
            <canvas id="speciesChart"></canvas>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-chart-line"></i> Monthly Hatchlings (last 6 months)</h3>
            <canvas id="hatchlingsChart"></canvas>
        </div>
    </div>

    <!-- About the Center -->
    <div class="info-section">
        <h2><i class="fas fa-info-circle"></i> About the Center</h2>
        <p>Our conservation center is dedicated to the protection, breeding, and rehabilitation of endangered tortoise species. We combine modern veterinary care, IoT‑enabled habitat monitoring, and data‑driven breeding programs to ensure the highest standards of animal welfare and species preservation.</p>
        <p>Since our founding, we have successfully hatched over 200 tortoises, reintroduced several species into protected areas, and built a comprehensive digital management system that tracks every aspect of our operations – from daily feeding to genetic lineage.</p>
    </div>

    <!-- System Roles -->
    <div class="info-section">
        <h2><i class="fas fa-users"></i> System Roles</h2>
        <div class="roles-grid">
            <div class="role-card">
                <i class="fas fa-clipboard-list"></i>
                <h3>Collecting Officer</h3>
                <p>Manages wild rescues, donations, and intake of new tortoises. Records collection details and transport logs.</p>
            </div>
            <div class="role-card">
                <i class="fas fa-chalkboard-user"></i>
                <h3>Supervisor</h3>
                <p>Oversees staff tasks, inventory, and restock requests. Ensures smooth daily operations.</p>
            </div>
            <div class="role-card">
                <i class="fas fa-hand-holding-heart"></i>
                <h3>Caretaker</h3>
                <p>Handles daily husbandry, enclosure maintenance, feeding schedules, and health observations.</p>
            </div>
            <div class="role-card">
                <i class="fas fa-apple-alt"></i>
                <h3>Feeder</h3>
                <p>Manages dietary plans, feeding schedules, and food inventory. Tracks special nutritional needs.</p>
            </div>
            <div class="role-card">
                <i class="fas fa-stethoscope"></i>
                <h3>Veterinarian</h3>
                <p>Conducts health assessments, diagnoses, treatments, and preventive care programs.</p>
            </div>
            <div class="role-card">
                <i class="fas fa-paw"></i>
                <h3>Breeding Officer</h3>
                <p>Manages breeding pairs, nesting, egg incubation, and hatchling success tracking.</p>
            </div>
            <div class="role-card">
                <i class="fas fa-microchip"></i>
                <h3>IoT Device</h3>
                <p>Monitors real‑time environmental data: temperature, humidity, water quality, and incubator conditions.</p>
            </div>
        </div>
    </div>

    <!-- Login Button -->
    <div class="login-btn-container">
        <button class="login-btn" onclick="window.location.href='login.php'">
            <i class="fas fa-sign-in-alt"></i> Login to Dashboard
        </button>
    </div>
</div>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Tortoise Conservation Center – All rights reserved.</p>
</footer>

<script>
    // Species Pie Chart
    const speciesCtx = document.getElementById('speciesChart').getContext('2d');
    new Chart(speciesCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($speciesLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($speciesCounts); ?>,
                backgroundColor: ['#2a9d6e', '#3fb581', '#57cb99', '#7adbb0', '#9be6c2', '#bdf0d4', '#c8f5df', '#a0d6b3'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // Hatchlings Line Chart
    const hatchCtx = document.getElementById('hatchlingsChart').getContext('2d');
    new Chart(hatchCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Hatchlings',
                data: <?php echo json_encode($monthlyHatchlings); ?>,
                borderColor: '#2c7a5a',
                backgroundColor: 'rgba(44,122,90,0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Number of hatchlings' } }
            }
        }
    });
</script>
</body>
</html>