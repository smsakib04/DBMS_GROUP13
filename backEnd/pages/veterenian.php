<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

$tortoises = $conn->query("SELECT t.tortoise_id, t.name, t.estimated_age_years, t.sex, t.health_status, s.common_name FROM tortoises t JOIN species s ON t.species_id = s.species_id");
$assessments = $conn->query("SELECT h.assessment_id, h.assessment_date, t.name, h.diagnosis, h.treatment, h.remarks FROM health_assessments h JOIN tortoises t ON h.tortoise_id = t.tortoise_id ORDER BY h.assessment_date DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Veterinarian Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body{background:#f8f4e8;font-family:Arial;}
        .tabs{display:flex;gap:10px;background:#0a2a3a;padding:10px;}
        .tab{background:#1a5a7a;color:white;padding:10px 20px;cursor:pointer;}
        .tab.active{background:#52b788;}
        .tab-content{display:none;background:white;padding:20px;}
        .tab-content.active{display:block;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:8px;border-bottom:1px solid #ccc;}
        th{background:#1a5a7a;color:white;}
        .btn{padding:8px 16px;background:#1a5a7a;color:white;border:none;border-radius:4px;cursor:pointer;}
    </style>
</head>
<body>
<div class="tabs">
    <div class="tab active" onclick="switchTab('records')">Health Records</div>
    <div class="tab" onclick="switchTab('assessment')">New Assessment</div>
    <div style="margin-left:auto;"><button class="btn" onclick="window.location.href='../logout.php'">Logout</button></div>
</div>
<div id="records" class="tab-content active">
    <h2>Tortoise Health Records</h2>
    <button class="btn" onclick="window.location.href='formEditTortoiseInfo.html'">Add Tortoise</button>
    <table><thead><tr><th>ID</th><th>Name</th><th>Species</th><th>Age</th><th>Gender</th><th>Health Status</th></tr></thead><tbody>
    <?php while($row = $tortoises->fetch_assoc()): ?>
        <tr><td><?php echo $row['tortoise_id']; ?></td><td><?php echo $row['name']; ?></td><td><?php echo $row['common_name']; ?></td><td><?php echo $row['estimated_age_years']; ?> yrs</td><td><?php echo $row['sex']; ?></td><td><?php echo $row['health_status']; ?></td></tr>
    <?php endwhile; ?>
    </tbody></table>
</div>
<div id="assessment" class="tab-content">
    <h2>Perform Health Assessment</h2>
    <form action="../process/add_health_assessment.php" method="POST">
        <label>Tortoise ID</label><input type="number" name="tortoise_id" required>
        <label>Vet ID</label><input type="number" name="vet_id" required>
        <label>Assessment Date</label><input type="date" name="assessment_date" required>
        <label>Diagnosis</label><textarea name="diagnosis"></textarea>
        <label>Treatment</label><textarea name="treatment"></textarea>
        <label>Remarks</label><textarea name="remarks"></textarea>
        <label>Next Checkup Date</label><input type="date" name="next_checkup_date">
        <button type="submit" class="btn">Submit Assessment</button>
    </form>
    <h3>Recent Assessments</h3>
    <table><thead><tr><th>Date</th><th>Tortoise</th><th>Diagnosis</th><th>Treatment</th><th>Remarks</th></tr></thead><tbody>
    <?php while($row = $assessments->fetch_assoc()): ?>
        <tr><td><?php echo $row['assessment_date']; ?></td><td><?php echo $row['name']; ?></td><td><?php echo $row['diagnosis']; ?></td><td><?php echo $row['treatment']; ?></td><td><?php echo $row['remarks']; ?></td></tr>
    <?php endwhile; ?>
    </tbody></table>
</div>
<script>
function switchTab(name){
    document.querySelectorAll('.tab-content').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.getElementById(name).classList.add('active');
    event.target.classList.add('active');
}
</script>
</body>
</html>