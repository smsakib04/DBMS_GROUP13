<?php
require_once '../backEnd/includes/session.php';
//requireLogin();
require_once '../backEnd/config/db.php';
 
$error = '';
 
// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task_name        = trim($_POST['task_name'] ?? '');
    $assigned_to      = intval($_POST['assigned_to'] ?? 0);
    $assigned_by      = $_SESSION['staff_id'] ?? null; // auto-set to logged-in supervisor
    $due_date         = $_POST['due_date'] ?? '';
    $status           = $_POST['status'] ?? 'Pending';
    $completion_notes = trim($_POST['completion_notes'] ?? '');
 
    if (!$task_name || !$assigned_to || !$due_date) {
        $error = 'Please fill in all required fields.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO tasks (task_name, assigned_to, assigned_by, due_date, status, completion_notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisss", $task_name, $assigned_to, $assigned_by, $due_date, $status, $completion_notes);
 
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: supervisor.php?msg=added");
            exit();
        } else {
            $error = "Database error: " . $stmt->error;
            $stmt->close();
        }
    }
}
 
// Load staff list for dropdown (only caretakers/staff roles)
$staffList = $conn->query("SELECT staff_id, full_name, role FROM staff WHERE status = 'active' ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Task | TCCMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root {
      --text: #382f2b;
      --primary: #6a3a2d;
      --accent: #2a6b5f;
      --border: rgba(90,69,61,0.18);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      min-height: 100vh;
      background: linear-gradient(180deg, #f7f2ef 0%, #ede5df 100%);
      font-family: 'Inter', sans-serif;
      color: var(--text);
      display: flex;
      flex-direction: column;
    }
    .page-header {
      background: linear-gradient(135deg, #2a1a1a 0%, #6a3a2d 100%);
      color: white;
      padding: 28px 36px;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .page-header h1 {
      margin: 0;
      font-family: 'Playfair Display', serif;
      font-size: 26px;
    }
    .back-link {
      color: rgba(255,255,255,0.8);
      text-decoration: none;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-left: auto;
    }
    .back-link:hover { color: white; }
    .container {
      width: min(600px, calc(100% - 40px));
      margin: 40px auto;
    }
    .form-card {
      background: white;
      border-radius: 24px;
      box-shadow: 0 24px 60px rgba(69,52,46,0.1);
      padding: 36px;
    }
    .form-card h2 {
      margin: 0 0 24px;
      font-size: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .alert-error {
      background: #fde7e4;
      color: #c94f3f;
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 20px;
      font-weight: 600;
    }
    .form-group { margin-bottom: 20px; }
    label {
      display: block;
      font-weight: 600;
      font-size: 14px;
      margin-bottom: 7px;
      color: #5a3e38;
    }
    label span.required { color: #c94f3f; margin-left: 3px; }
    input[type="text"],
    input[type="date"],
    select,
    textarea {
      width: 100%;
      padding: 13px 16px;
      border: 1px solid var(--border);
      border-radius: 14px;
      font-family: 'Inter', sans-serif;
      font-size: 15px;
      background: #faf3ef;
      color: var(--text);
      outline: none;
      transition: border-color 0.2s;
    }
    input:focus, select:focus, textarea:focus {
      border-color: rgba(106,58,45,0.4);
      background: white;
    }
    textarea { resize: vertical; min-height: 90px; }
    .form-actions { display: flex; gap: 12px; margin-top: 28px; }
    .btn-submit {
      flex: 1;
      padding: 14px;
      background: var(--accent);
      color: white;
      border: none;
      border-radius: 16px;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.2s;
    }
    .btn-submit:hover { transform: translateY(-1px); }
    .btn-cancel {
      flex: 1;
      padding: 14px;
      background: #f0e8e4;
      color: var(--text);
      border: none;
      border-radius: 16px;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      text-align: center;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: transform 0.2s;
    }
    .btn-cancel:hover { transform: translateY(-1px); }
  </style>
</head>
<body>
<header class="page-header">
  <div>
    <h1>📋 Assign New Task</h1>
  </div>
  <a href="supervisor.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</header>
 
<div class="container">
  <div class="form-card">
    <h2><i class="fas fa-tasks" style="color:#2a6b5f"></i> New Task Details</h2>
 
    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
 
    <form method="POST" action="add_task.php">
 
      <div class="form-group">
        <label>Task Name <span class="required">*</span></label>
        <input type="text" name="task_name" placeholder="e.g. Clean Arid Zone E1"
               value="<?= htmlspecialchars($_POST['task_name'] ?? '') ?>" required>
      </div>
 
      <div class="form-group">
        <label>Assign To <span class="required">*</span></label>
        <select name="assigned_to" required>
          <option value="">— Select Staff Member —</option>
          <?php while ($s = $staffList->fetch_assoc()): ?>
            <option value="<?= $s['staff_id'] ?>"
              <?= (isset($_POST['assigned_to']) && $_POST['assigned_to'] == $s['staff_id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['role']) ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>
 
      <div class="form-group">
        <label>Due Date <span class="required">*</span></label>
        <input type="date" name="due_date"
               value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>" required>
      </div>
 
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <?php foreach (['Pending','In Progress','Completed','Cancelled'] as $opt): ?>
            <option value="<?= $opt ?>"
              <?= (isset($_POST['status']) && $_POST['status'] === $opt) ? 'selected' : ($opt === 'Pending' ? 'selected' : '') ?>>
              <?= $opt ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
 
      <div class="form-group">
        <label>Completion Notes</label>
        <textarea name="completion_notes" placeholder="Optional notes..."><?= htmlspecialchars($_POST['completion_notes'] ?? '') ?></textarea>
      </div>
 
      <div class="form-actions">
        <a href="supervisor.php" class="btn-cancel"><i class="fas fa-times"></i>&nbsp; Cancel</a>
        <button type="submit" class="btn-submit"><i class="fas fa-check"></i>&nbsp; Create Task</button>
      </div>
 
    </form>
  </div>
</div>
</body>
</html>