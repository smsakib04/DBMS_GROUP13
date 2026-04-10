<?php
require_once '../includes/session.php';
require_once '../config/db.php';

// Fetch data for the lists
$tortoises = $conn->query("SELECT t.*, s.common_name FROM tortoises t JOIN species s ON t.species_id = s.species_id ORDER BY t.tortoise_id ASC");
$tortoise_list = $conn->query("SELECT tortoise_id, microchip_id, name FROM tortoises ORDER BY name");
$assessments = $conn->query("SELECT ha.*, t.name FROM health_assessments ha JOIN tortoises t ON ha.tortoise_id = t.tortoise_id ORDER BY ha.assessment_date DESC");

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
<title>Veterinarian | TCCMS</title>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Source+Sans+3:wght@300;400;600&display=swap');
  :root{--green-dark:#1a3a2a;--green-mid:#2d6a4f;--green-light:#52b788;--cream:#f8f4e8;--gold:#c9a84c;--text-dark:#1a1a1a;--text-light:#5a5a5a;--blue:#2471a3;--red:#c0392b;}
  *{box-sizing:border-box;margin:0;padding:0;}
  body{font-family:'Source Sans 3',sans-serif;background:var(--cream);color:var(--text-dark);}
  .page-header{background:linear-gradient(135deg,#0a2a3a 0%,#1a5a7a 100%);color:white;padding:36px 40px;display:flex;align-items:center;gap:20px;}
  .page-header h1{font-family:'Playfair Display',serif;font-size:30px;margin-bottom:6px;}
  .main{max-width:1100px;margin:0 auto;padding:30px 24px 50px;}
  .tabs{display:flex;gap:0;margin-bottom:24px;border-bottom:2px solid #dde8e0;}
  .tab{padding:10px 22px;cursor:pointer;font-size:14px;font-weight:600;color:var(--text-light);border-bottom:3px solid transparent;}
  .tab.active{color:var(--blue);border-bottom-color:var(--blue);}
  .tab-content{display:none;}
  .tab-content.active{display:block;}
  .card{background:white;border-radius:12px;padding:28px;border:1.5px solid #dde8e0;box-shadow:0 2px 12px rgba(0,0,0,.05);margin-bottom:24px;}
  .two-col{display:grid;grid-template-columns:1fr 1.2fr;gap:28px;}
  label{display:block;font-size:13px;font-weight:600;color:var(--text-light);margin-bottom:5px;margin-top:14px;text-transform:uppercase;}
  input,select,textarea{width:100%;padding:10px 12px;border:1.5px solid #d0dbd3;border-radius:7px;font-size:14px;background:#fafcfb;}
  .btn{background:#1a5a7a;color:white;border:none;padding:11px 28px;border-radius:7px;font-weight:600;cursor:pointer;margin-top:18px;}
  .btn-danger{background:var(--red);}
  table{width:100%;border-collapse:collapse;margin-top:10px;}
  th,td{text-align:left;padding:12px;border-bottom:1px solid #e4ece6;font-size:13px;}
  th{background:#1a5a7a;color:white;}
  .alert{padding:15px;border-radius:8px;margin-bottom:20px;font-weight:600;text-align:center;}
  .alert-success{background:#d4edda;color:#155724;}
</style>
</head>
<body>

<div class="page-header">
  <div class="icon">🩺</div>
  <div><h1>Veterinarian Portal</h1><p>Tortoise Health Management System</p></div>
</div>

<div class="main">
  <?php if ($msg === 'added'): ?><div class="alert alert-success">Assessment successfully saved to database!</div><?php endif; ?>
  <?php if ($msg === 'deleted'): ?><div class="alert alert-success">Assessment deleted successfully.</div><?php endif; ?>

  <div class="tabs">
    <div class="tab active" onclick="switchTab('records', this)">📋 Health Records</div>
    <div class="tab" onclick="switchTab('assessment', this)">🩺 New Assessment</div>
  </div>

  <div class="tab-content active" id="tab-records">
    <div class="card">
      <h2>📋 Registered Tortoises</h2>
      <table>
        <tr><th>ID</th><th>Name</th><th>Species</th><th>Status</th><th>Action</th></tr>
        <?php while($row = $tortoises->fetch_assoc()): ?>
        <tr>
          <td><?= $row['microchip_id'] ?></td>
          <td><?= htmlspecialchars($row['name']) ?></td>
          <td><?= htmlspecialchars($row['common_name']) ?></td>
          <td><?= $row['health_status'] ?></td>
          <td><a href="formEditTortoiseInfo.php?id=<?= $row['tortoise_id'] ?>"><button class="btn" style="margin:0;padding:5px 10px">Edit</button></a></td>
        </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>

  <div class="tab-content" id="tab-assessment">
    <div class="two-col">
      <div class="card">
        <h2>🩺 Perform Health Assessment</h2>
        <form action="../process/add_assessment.php" method="POST">
            <label>Assessment ID</label>
            <input type="text" name="assessment_code" placeholder="e.g. ASS-001" required>
            
            <label>Date</label>
            <input type="date" name="assessment_date" value="<?= date('Y-m-d') ?>" required>
            
            <label>Remarks</label>
            <textarea name="remarks" placeholder="Enter clinical notes..."></textarea>
            
            <label>Tortoise Selection</label>
            <select name="tortoise_id" required>
                <option value="">— Select Tortoise —</option>
                <?php $tortoise_list->data_seek(0); while($t = $tortoise_list->fetch_assoc()): ?>
                <option value="<?= $t['tortoise_id'] ?>"><?= $t['name'] ?> (<?= $t['microchip_id'] ?>)</option>
                <?php endwhile; ?>
            </select>
            
            <button type="submit" class="btn">Submit Assessment</button>
        </form>
      </div>

      <div class="card">
        <h2>Recent History</h2>
        <table>
          <tr><th>Date</th><th>Tortoise</th><th>Remarks</th><th>Actions</th></tr>
          <?php while($row = $assessments->fetch_assoc()): ?>
          <tr>
            <td><?= $row['assessment_date'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['remarks']) ?></td>
            <td>
              <a href="formEditAssessment.php?id=<?= $row['assessment_id'] ?>">
                <button class="btn" style="margin:0;padding:5px 10px">Edit</button>
              </a>
              <a href="../process/delete_assessment.php?id=<?= $row['assessment_id'] ?>" onclick="return confirm('Delete record?')">
                <button class="btn btn-danger" style="margin:0;padding:5px 10px">Delete</button>
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function switchTab(name, el) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  el.classList.add('active');
}
</script>
</body>
</html>