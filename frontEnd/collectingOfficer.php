<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';

// -------------------------------
// Handle form submission for new collection
// -------------------------------
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_collection') {
    $tortoise_id = !empty($_POST['tortoise_id']) ? intval($_POST['tortoise_id']) : null;
    $collection_date = $_POST['collection_date'];
    $source_type = $_POST['source_type'];
    $location = $_POST['location'];
    $initial_health = $_POST['initial_health'];
    $notes = $_POST['notes'];
    $collected_by = !empty($_POST['collected_by']) ? intval($_POST['collected_by']) : null;

    $stmt = $conn->prepare("INSERT INTO collections (tortoise_id, collection_date, source_type, location, initial_health, notes, collected_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssi", $tortoise_id, $collection_date, $source_type, $location, $initial_health, $notes, $collected_by);
    if ($stmt->execute()) {
        $message = "Collection record saved successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

// -------------------------------
// Fetch collection records
// -------------------------------
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

// -------------------------------
// Fetch transport logs
// -------------------------------
$transportQuery = "
    SELECT tl.transport_id, 
           COALESCE(t.name, 'ID: ' . tl.tortoise_id) AS tortoise_name,
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
    <h2>Add New Collection Record</h2>
    <?php if ($message): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form method="POST" action="">
        <input type="hidden" name="action" value="add_collection">
        <input type="number" name="tortoise_id" placeholder="Tortoise ID (optional)">
        <input type="text" name="species" placeholder="Species" required>
        <input type="number" name="estimated_age" placeholder="Estimated Age" required>
        
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
<!-- Code injected by live-server -->
<script>
	// <![CDATA[  <-- For SVG support
	if ('WebSocket' in window) {
		(function () {
			function refreshCSS() {
				var sheets = [].slice.call(document.getElementsByTagName("link"));
				var head = document.getElementsByTagName("head")[0];
				for (var i = 0; i < sheets.length; ++i) {
					var elem = sheets[i];
					var parent = elem.parentElement || head;
					parent.removeChild(elem);
					var rel = elem.rel;
					if (elem.href && typeof rel != "string" || rel.length == 0 || rel.toLowerCase() == "stylesheet") {
						var url = elem.href.replace(/(&|\?)_cacheOverride=\d+/, '');
						elem.href = url + (url.indexOf('?') >= 0 ? '&' : '?') + '_cacheOverride=' + (new Date().valueOf());
					}
					parent.appendChild(elem);
				}
			}
			var protocol = window.location.protocol === 'http:' ? 'ws://' : 'wss://';
			var address = protocol + window.location.host + window.location.pathname + '/ws';
			var socket = new WebSocket(address);
			socket.onmessage = function (msg) {
				if (msg.data == 'reload') window.location.reload();
				else if (msg.data == 'refreshcss') refreshCSS();
			};
			if (sessionStorage && !sessionStorage.getItem('IsThisFirstTime_Log_From_LiveServer')) {
				console.log('Live reload enabled.');
				sessionStorage.setItem('IsThisFirstTime_Log_From_LiveServer', true);
			}
		})();
	}
	else {
		console.error('Upgrade your browser. This Browser is NOT supported WebSocket for Live-Reloading.');
	}
	// ]]>
</script>
</body>
</html>