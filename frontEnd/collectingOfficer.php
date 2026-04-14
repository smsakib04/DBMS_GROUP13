<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';

$message = '';

// Handle new collection & tortoise insertion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_collection') {
    // Get form data
    $tortoise_name = trim($_POST['tortoise_name'] ?? '');
    $species_name = trim($_POST['species'] ?? '');
    $estimated_age = intval($_POST['estimated_age'] ?? 0);
    $sex = $_POST['sex'] ?? 'Unknown';
    $source_type = $_POST['source_type'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $collection_date = $_POST['collection_date'] ?? '';
    $initial_health = $_POST['initial_health'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $collected_by = !empty($_POST['collected_by']) ? intval($_POST['collected_by']) : null;

    // Validate required fields
    if (empty($species_name) || empty($collection_date) || empty($source_type) || empty($location) || empty($initial_health)) {
        $message = "All required fields must be filled.";
    } else {
        // Get species_id from species table (or insert new species? We'll assume it exists)
        $species_id = null;
        $speciesStmt = $conn->prepare("SELECT species_id FROM species WHERE common_name LIKE ?");
        $likeName = "%$species_name%";
        $speciesStmt->bind_param("s", $likeName);
        $speciesStmt->execute();
        $speciesRes = $speciesStmt->get_result();
        if ($speciesRes->num_rows > 0) {
            $species_id = $speciesRes->fetch_assoc()['species_id'];
        } else {
            // If not found, insert a new species (simplified)
            $insertSpecies = $conn->prepare("INSERT INTO species (common_name, scientific_name) VALUES (?, ?)");
            $scientific = "Unknown";
            $insertSpecies->bind_param("ss", $species_name, $scientific);
            $insertSpecies->execute();
            $species_id = $insertSpecies->insert_id;
            $insertSpecies->close();
        }
        $speciesStmt->close();

        // Insert into tortoises
        $microchip = 'COL-' . time(); // temporary microchip
        $acquisition_source = $source_type;
        $health_status = ($initial_health == 'Healthy') ? 'Healthy' : (($initial_health == 'Weak') ? 'Under observation' : 'Minor injury');
        $tortoiseStmt = $conn->prepare("INSERT INTO tortoises (microchip_id, name, species_id, sex, estimated_age_years, health_status, acquisition_source, acquisition_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $tortoiseStmt->bind_param("ssissssss", $microchip, $tortoise_name, $species_id, $sex, $estimated_age, $health_status, $acquisition_source, $collection_date, $notes);
        if ($tortoiseStmt->execute()) {
            $new_tortoise_id = $tortoiseStmt->insert_id;
            $tortoiseStmt->close();

            // Insert into collections
            $colStmt = $conn->prepare("INSERT INTO collections (tortoise_id, collection_date, source_type, location, initial_health, notes, collected_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $colStmt->bind_param("isssssi", $new_tortoise_id, $collection_date, $source_type, $location, $initial_health, $notes, $collected_by);
            if ($colStmt->execute()) {
                $message = "New tortoise and collection record saved successfully!";
            } else {
                $message = "Error saving collection: " . $colStmt->error;
            }
            $colStmt->close();
        } else {
            $message = "Error saving tortoise: " . $tortoiseStmt->error;
            $tortoiseStmt->close();
        }
    }
}

// Fetch collection records with tortoise names
$collectionsQuery = "
    SELECT c.collection_id, 
           COALESCE(t.name, 'N/A') AS tortoise_name, 
           c.source_type, 
           c.location, 
           c.initial_health, 
           c.collection_date
    FROM collections c
    LEFT JOIN tortoises t ON c.tortoise_id = t.tortoise_id
    ORDER BY c.collection_date DESC
";
$collectionsResult = $conn->query($collectionsQuery);

// Fetch transport logs (fixed SQL)
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
    ORDER BY tl.transport_date DESC
";
$transportResult = $conn->query($transportQuery);
if (!$transportResult) {
    die("Transport query error: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Collecting Officer · Tortoise Center</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Inter', sans-serif;
}
body{
    background:#eef6f2;
    padding:20px;
}
.header{
    background:linear-gradient(135deg,#0f5132,#198754);
    color:white;
    padding:25px;
    border-radius:16px;
    margin-bottom:20px;
}
.nav{
    display:flex;
    gap:15px;
    margin-bottom:20px;
}
.nav button{
    padding:10px 18px;
    border:none;
    border-radius:25px;
    cursor:pointer;
    font-weight:600;
    background:#d1e7dd;
}
.nav button.active{
    background:#198754;
    color:white;
}
.section{
    display:none;
    background:white;
    padding:20px;
    border-radius:16px;
    box-shadow:0 10px 20px rgba(0,0,0,0.08);
}
.section.active{
    display:block;
}
input, select, textarea{
    width:100%;
    padding:10px;
    margin:8px 0;
    border-radius:10px;
    border:1px solid #ccc;
}
button.primary{
    background:#198754;
    color:white;
    border:none;
    padding:10px 18px;
    border-radius:20px;
    cursor:pointer;
}
.action-bar{
    display:flex;
    gap:10px;
    margin-bottom:10px;
    flex-wrap:wrap;
}
.btn{
    padding:8px 14px;
    border:none;
    border-radius:20px;
    font-weight:600;
    cursor:pointer;
}
.btn-add{background:#198754;color:white;}
.btn-edit{background:#0dcaf0;color:white;}
.btn-remove{background:#dc3545;color:white;}
.btn-search{background:#6c757d;color:white;}
table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}
th,td{
    padding:10px;
    border-bottom:1px solid #ddd;
}
th{
    background:#198754;
    color:white;
}
.status{
    padding:4px 10px;
    border-radius:20px;
    font-size:0.8rem;
    display:inline-block;
}
.good{background:#d1e7dd;color:#0f5132;}
.warning{background:#fff3cd;color:#856404;}
.critical{background:#f8d7da;color:#842029;}
.message{
    background:#d4edda;
    color:#155724;
    padding:10px;
    border-radius:8px;
    margin-bottom:15px;
}
</style>
</head>
<body>

<div class="header">
    <h1><i class="fas fa-truck"></i> Collecting Officer Dashboard</h1>
    <p>Collection, transport & intake management</p>
</div>

<div class="nav">
    <button class="tab active" onclick="openTab('collection')">New Collection</button>
    <button class="tab" onclick="openTab('records')">Collection Records</button>
    <button class="tab" onclick="openTab('transport')">Transport Log</button>
</div>

<!-- ================= NEW COLLECTION ================= -->
<div id="collection" class="section active">
    <h2>Add New Tortoise & Collection Record</h2>
    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="action" value="add_collection">
        
        <input type="text" name="tortoise_name" placeholder="Tortoise Name (optional)">
        <input type="text" name="species" placeholder="Species (e.g., Aldabra Giant Tortoise)" required>
        <input type="number" name="estimated_age" placeholder="Estimated Age (years)" required>
        
        <select name="sex">
            <option value="Unknown">Sex (Unknown)</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>

        <select name="source_type" required>
            <option value="">Source Type</option>
            <option>Wild</option>
            <option>Rescue</option>
            <option>Donation</option>
        </select>

        <input type="text" name="location" placeholder="Collection Location" required>
        <input type="date" name="collection_date" required>

        <select name="initial_health" required>
            <option value="">Initial Health</option>
            <option>Healthy</option>
            <option>Weak</option>
            <option>Injured</option>
        </select>

        <textarea name="notes" placeholder="Notes..."></textarea>
        <input type="number" name="collected_by" placeholder="Staff ID (collected by)">

        <button type="submit" class="primary">Save Record</button>
    </form>
</div>

<!-- ================= COLLECTION RECORDS ================= -->
<div id="records" class="section">
    <h2>Collected Tortoises</h2>
    <div class="action-bar">
        <button class="btn btn-add" onclick="window.location.href='add_collection.php'"><i class="fas fa-plus"></i> Add</button>
        <button class="btn btn-edit" onclick="alert('Edit: implement edit_collection.php?id=...')"><i class="fas fa-pen"></i> Edit</button>
        <button class="btn btn-remove" onclick="alert('Delete: implement delete_collection.php')"><i class="fas fa-trash"></i> Remove</button>
        <button class="btn btn-search" onclick="alert('Search functionality')"><i class="fas fa-search"></i> Search</button>
    </div>
    <input type="text" id="search" placeholder="Search..." onkeyup="searchTable()">
    <table id="dataTable">
        <thead>
            <tr><th>ID</th><th>Tortoise</th><th>Source</th><th>Location</th><th>Health</th><th>Date</th></tr>
        </thead>
        <tbody>
            <?php while($row = $collectionsResult->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['collection_id']); ?></td>
                <td><?php echo htmlspecialchars($row['tortoise_name']); ?></td>
                <td><?php echo htmlspecialchars($row['source_type']); ?></td>
                <td><?php echo htmlspecialchars($row['location']); ?></td>
                <td>
                    <?php
                    $healthClass = '';
                    if ($row['initial_health'] == 'Healthy') $healthClass = 'good';
                    elseif ($row['initial_health'] == 'Weak') $healthClass = 'warning';
                    elseif ($row['initial_health'] == 'Injured') $healthClass = 'critical';
                    ?>
                    <span class="status <?php echo $healthClass; ?>"><?php echo htmlspecialchars($row['initial_health']); ?></span>
                </td>
                <td><?php echo htmlspecialchars($row['collection_date']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<!-- ================= TRANSPORT LOG ================= -->
<div id="transport" class="section">
    <h2>Transport Log</h2>
    <div class="action-bar">
        <button class="btn btn-add" onclick="window.location.href='add_transport.php'"><i class="fas fa-plus"></i> Add</button>
        <button class="btn btn-edit" onclick="alert('Edit: implement edit_transport.php')"><i class="fas fa-pen"></i> Edit</button>
        <button class="btn btn-remove" onclick="alert('Delete: implement delete_transport.php')"><i class="fas fa-trash"></i> Remove</button>
        <button class="btn btn-search" onclick="alert('Search functionality')"><i class="fas fa-search"></i> Search</button>
    </div>
    <table>
        <thead>
            <tr><th>Tortoise</th><th>Vehicle</th><th>From</th><th>To</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
            <?php while($row = $transportResult->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['tortoise_name']); ?></td>
                <td><?php echo htmlspecialchars($row['vehicle_id']); ?></td>
                <td><?php echo htmlspecialchars($row['from_location']); ?></td>
                <td><?php echo htmlspecialchars($row['to_location']); ?></td>
                <td><?php echo htmlspecialchars($row['status']); ?></td>
                <td><?php echo htmlspecialchars($row['transport_date']); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
function openTab(id){
    document.querySelectorAll('.section').forEach(sec=>sec.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(btn=>btn.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    event.target.classList.add('active');
}

function searchTable(){
    const input = document.getElementById("search").value.toLowerCase();
    const rows = document.querySelectorAll("#dataTable tbody tr");
    rows.forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(input) ? "" : "none";
    });
}
</script>
</body>
</html>