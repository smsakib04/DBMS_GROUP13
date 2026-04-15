<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_type = isset($_GET['type']) ? $_GET['type'] : 'collection';

$collection_results = [];
$transport_results = [];
$tortoise_results = [];

if (!empty($search_term)) {
    $search_pattern = "%$search_term%";
    
    // Search in collections
    $collectionQuery = "
        SELECT c.collection_id, 
               COALESCE(t.name, 'N/A') AS tortoise_name, 
               t.microchip_id,
               c.source_type, 
               c.location, 
               c.initial_health, 
               c.collection_date,
               c.notes
        FROM collections c
        LEFT JOIN tortoises t ON c.tortoise_id = t.tortoise_id
        WHERE t.name LIKE ? 
           OR t.microchip_id LIKE ?
           OR c.location LIKE ?
           OR c.source_type LIKE ?
           OR c.initial_health LIKE ?
        ORDER BY c.collection_date DESC
    ";
    
    $stmt = $conn->prepare($collectionQuery);
    $stmt->bind_param("sssss", $search_pattern, $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    $stmt->execute();
    $collection_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Search in transport logs
    $transportQuery = "
        SELECT tl.transport_id, 
               COALESCE(t.name, CONCAT('ID: ', tl.tortoise_id)) AS tortoise_name,
               tl.vehicle_id, 
               tl.from_location, 
               tl.to_location, 
               tl.status,
               tl.transport_date
        FROM transport_logs tl
        LEFT JOIN tortoises t ON tl.tortoise_id = t.tortoise_id
        WHERE t.name LIKE ? 
           OR tl.from_location LIKE ?
           OR tl.to_location LIKE ?
           OR tl.status LIKE ?
           OR tl.vehicle_id LIKE ?
        ORDER BY tl.transport_date DESC
    ";
    
    $stmt = $conn->prepare($transportQuery);
    $stmt->bind_param("sssss", $search_pattern, $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    $stmt->execute();
    $transport_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Search in tortoises
    $tortoiseQuery = "
        SELECT tortoise_id, name, microchip_id, species_id, sex, estimated_age_years, health_status, acquisition_source
        FROM tortoises
        WHERE name LIKE ? 
           OR microchip_id LIKE ?
           OR health_status LIKE ?
        ORDER BY tortoise_id DESC
    ";
    
    $stmt = $conn->prepare($tortoiseQuery);
    $stmt->bind_param("sss", $search_pattern, $search_pattern, $search_pattern);
    $stmt->execute();
    $tortoise_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get health badge class
function getHealthClass($health) {
    switch($health) {
        case 'Healthy': return 'good';
        case 'Weak': return 'warning';
        case 'Injured': return 'critical';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Records - Collecting Officer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        body {
            background: #eef6f2;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #0f5132, #198754);
            color: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 20px;
        }
        .search-box {
            background: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .search-form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #cfdfd7;
            border-radius: 40px;
            font-size: 1rem;
        }
        .search-input:focus {
            outline: none;
            border-color: #198754;
        }
        .search-btn {
            background: #198754;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
        }
        .search-btn:hover {
            background: #0f5132;
        }
        .results-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .results-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #198754;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2ece6;
        }
        .results-count {
            font-size: 0.9rem;
            color: #6c757d;
            margin-left: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e2ece6;
        }
        th {
            background: #f8f9fa;
            color: #2c3e2f;
            font-weight: 600;
        }
        tr:hover td {
            background-color: #f8fff9;
        }
        .status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        .good { background: #d1e7dd; color: #0f5132; }
        .warning { background: #fff3cd; color: #856404; }
        .critical { background: #f8d7da; color: #842029; }
        .badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-wild { background: #cfe2ff; color: #084298; }
        .badge-rescue { background: #cff4fc; color: #055160; }
        .badge-donation { background: #d1e7dd; color: #0f5132; }
        .no-results {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: #198754;
            text-decoration: none;
            font-weight: 600;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2ece6;
        }
        .nav-tab {
            padding: 10px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
        }
        .nav-tab.active {
            color: #198754;
            border-bottom: 2px solid #198754;
            margin-bottom: -2px;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-search"></i> Search Records</h1>
            <p>Search collections, transport logs, and tortoise records</p>
        </div>

        <div class="search-box">
            <form method="GET" action="" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search by tortoise name, location, microchip ID, health status, vehicle, etc..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                <?php if (!empty($search_term)): ?>
                    <a href="search_collectingOfficer.php" class="search-btn" style="background: #6c757d;"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <?php if (!empty($search_term)): ?>
            <div class="nav-tabs">
                <button class="nav-tab active" onclick="showTab('collections')">Collections (<?php echo count($collection_results); ?>)</button>
                <button class="nav-tab" onclick="showTab('transport')">Transport Logs (<?php echo count($transport_results); ?>)</button>
                <button class="nav-tab" onclick="showTab('tortoises')">Tortoises (<?php echo count($tortoise_results); ?>)</button>
            </div>

            <!-- Collections Tab -->
            <div id="collections-tab" class="tab-content active">
                <div class="results-section">
                    <div class="results-title">
                        <i class="fas fa-clipboard-list"></i> Collection Records
                        <span class="results-count"><?php echo count($collection_results); ?> results</span>
                    </div>
                    <?php if (count($collection_results) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tortoise Name</th>
                                    <th>Microchip ID</th>
                                    <th>Source</th>
                                    <th>Location</th>
                                    <th>Health</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($collection_results as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['collection_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tortoise_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['microchip_id'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($row['source_type']); ?>">
                                            <?php echo htmlspecialchars($row['source_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['location']); ?></td>
                                    <td>
                                        <span class="status <?php echo getHealthClass($row['initial_health']); ?>">
                                            <?php echo htmlspecialchars($row['initial_health']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['collection_date']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-results">No collection records found matching "<?php echo htmlspecialchars($search_term); ?>"</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Transport Tab -->
            <div id="transport-tab" class="tab-content">
                <div class="results-section">
                    <div class="results-title">
                        <i class="fas fa-truck"></i> Transport Logs
                        <span class="results-count"><?php echo count($transport_results); ?> results</span>
                    </div>
                    <?php if (count($transport_results) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tortoise</th>
                                    <th>Vehicle</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($transport_results as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['transport_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['tortoise_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['vehicle_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['from_location']); ?></td>
                                    <td><?php echo htmlspecialchars($row['to_location']); ?></td>
                                    <td>
                                        <span class="status <?php echo strtolower($row['status']) == 'completed' ? 'good' : (strtolower($row['status']) == 'ongoing' ? 'warning' : ''); ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['transport_date']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-results">No transport logs found matching "<?php echo htmlspecialchars($search_term); ?>"</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Tortoises Tab -->
            <div id="tortoises-tab" class="tab-content">
                <div class="results-section">
                    <div class="results-title">
                        <i class="fas fa-paw"></i> Tortoise Records
                        <span class="results-count"><?php echo count($tortoise_results); ?> results</span>
                    </div>
                    <?php if (count($tortoise_results) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Microchip ID</th>
                                    <th>Sex</th>
                                    <th>Age (years)</th>
                                    <th>Health Status</th>
                                    <th>Source</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tortoise_results as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['tortoise_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['name'] ?? 'Unnamed'); ?></td>
                                    <td><?php echo htmlspecialchars($row['microchip_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sex']); ?></td>
                                    <td><?php echo htmlspecialchars($row['estimated_age_years']); ?></td>
                                    <td>
                                        <span class="status <?php echo getHealthClass($row['health_status']); ?>">
                                            <?php echo htmlspecialchars($row['health_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['acquisition_source']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="no-results">No tortoise records found matching "<?php echo htmlspecialchars($search_term); ?>"</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <a href="collectingOfficer.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            // Remove active class from all nav tabs
            document.querySelectorAll('.nav-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>