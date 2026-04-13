<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';
 
$error = '';
 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name    = trim($_POST['item_name'] ?? '');
    $category     = $_POST['category'] ?? 'other';
    $quantity     = trim($_POST['quantity'] ?? '');
    $unit         = trim($_POST['unit'] ?? '');
    $reorder_level = trim($_POST['reorder_level'] ?? '');
    $supplier     = trim($_POST['supplier'] ?? '');
    $last_updated = $_POST['last_updated'] ?? date('Y-m-d');
    $managed_by   = !empty($_POST['managed_by']) ? intval($_POST['managed_by']) : null;
 
    if (!$item_name || $quantity === '') {
        $error = 'Item name and quantity are required.';
    } else {
        $stmt = $conn->prepare("
            INSERT INTO inventory (item_name, category, quantity, unit, reorder_level, supplier, last_updated, managed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssddsssi",
            $item_name, $category, $quantity, $reorder_level, $unit, $supplier, $last_updated, $managed_by
        );
 
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: supervisor.php?msg=inv_added&tab=inventory");
            exit();
        } else {
            $error = "Database error: " . $stmt->error;
            $stmt->close();
        }
    }
}
 
// Staff list for managed_by dropdown
$staffList = $conn->query("SELECT staff_id, full_name, role FROM staff WHERE status = 'active' ORDER BY full_name");
$categories = ['food', 'medical', 'cleaning', 'equipment', 'other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add Inventory Item | TCCMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    :root { --text: #382f2b; --accent: #2a6b5f; --border: rgba(90,69,61,0.18); }
    * { box-sizing: border-box; }
    body {
      margin: 0; min-height: 100vh;
      background: linear-gradient(180deg, #f7f2ef 0%, #ede5df 100%);
      font-family: 'Inter', sans-serif; color: var(--text);
    }
    .page-header {
      background: linear-gradient(135deg, #2a1a1a 0%, #6a3a2d 100%);
      color: white; padding: 28px 36px;
      display: flex; align-items: center; gap: 16px;
    }
    .page-header h1 { margin: 0; font-family: 'Playfair Display', serif; font-size: 26px; }
    .back-link {
      color: rgba(255,255,255,0.8); text-decoration: none; font-size: 14px;
      display: flex; align-items: center; gap: 6px; margin-left: auto;
    }
    .back-link:hover { color: white; }
    .container { width: min(620px, calc(100% - 40px)); margin: 40px auto; }
    .form-card {
      background: white; border-radius: 24px;
      box-shadow: 0 24px 60px rgba(69,52,46,0.1); padding: 36px;
    }
    .form-card h2 { margin: 0 0 24px; font-size: 20px; display: flex; align-items: center; gap: 10px; }
    .alert-error {
      background: #fde7e4; color: #c94f3f; border-radius: 10px;
      padding: 12px 16px; margin-bottom: 20px; font-weight: 600;
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group { margin-bottom: 20px; }
    label { display: block; font-weight: 600; font-size: 14px; margin-bottom: 7px; color: #5a3e38; }
    label .required { color: #c94f3f; margin-left: 3px; }
    input[type="text"], input[type="number"], input[type="date"], select, textarea {
      width: 100%; padding: 13px 16px;
      border: 1px solid var(--border); border-radius: 14px;
      font-family: 'Inter', sans-serif; font-size: 15px;
      background: #faf3ef; color: var(--text); outline: none; transition: border-color 0.2s;
    }
    input:focus, select:focus, textarea:focus { border-color: rgba(106,58,45,0.4); background: white; }
    .category-badge {
      display: inline-block; padding: 3px 10px; border-radius: 999px;
      font-size: 11px; font-weight: 700; text-transform: uppercase; margin-left: 6px;
    }
    .form-actions { display: flex; gap: 12px; margin-top: 28px; }
    .btn-submit {
      flex: 1; padding: 14px; background: var(--accent); color: white;
      border: none; border-radius: 16px; font-size: 16px; font-weight: 700;
      cursor: pointer; transition: transform 0.2s;
    }
    .btn-submit:hover { transform: translateY(-1px); }
    .btn-cancel {
      flex: 1; padding: 14px; background: #f0e8e4; color: var(--text);
      border: none; border-radius: 16px; font-size: 16px; font-weight: 600;
      cursor: pointer; text-align: center; text-decoration: none;
      display: flex; align-items: center; justify-content: center; transition: transform 0.2s;
    }
    .btn-cancel:hover { transform: translateY(-1px); }
    @media (max-width: 520px) { .form-row { grid-template-columns: 1fr; } }
  </style>
</head>
<body>
<header class="page-header">
  <div><h1>📦 Add Inventory Item</h1></div>
  <a href="supervisor.php?tab=inventory" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</header>
 
<div class="container">
  <div class="form-card">
    <h2><i class="fas fa-box-open" style="color:#2a6b5f"></i> New Item Details</h2>
 
    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
 
    <form method="POST" action="add_inventory_item.php">
 
      <div class="form-group">
        <label>Item Name <span class="required">*</span></label>
        <input type="text" name="item_name" placeholder="e.g. Calcium Blocks"
               value="<?= htmlspecialchars($_POST['item_name'] ?? '') ?>" required>
      </div>
 
      <div class="form-row">
        <div class="form-group">
          <label>Category</label>
          <select name="category">
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat ?>"
                <?= (($_POST['category'] ?? 'other') === $cat) ? 'selected' : '' ?>>
                <?= ucfirst($cat) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Unit <small style="font-weight:400;color:#8a7068">(e.g. kg, pieces)</small></label>
          <input type="text" name="unit" placeholder="kg"
                 value="<?= htmlspecialchars($_POST['unit'] ?? '') ?>">
        </div>
      </div>
 
      <div class="form-row">
        <div class="form-group">
          <label>Quantity <span class="required">*</span></label>
          <input type="number" name="quantity" step="0.01" min="0" placeholder="0.00"
                 value="<?= htmlspecialchars($_POST['quantity'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Reorder Level</label>
          <input type="number" name="reorder_level" step="0.01" min="0" placeholder="0.00"
                 value="<?= htmlspecialchars($_POST['reorder_level'] ?? '') ?>">
        </div>
      </div>
 
      <div class="form-group">
        <label>Supplier</label>
        <input type="text" name="supplier" placeholder="e.g. ReptiSupply"
               value="<?= htmlspecialchars($_POST['supplier'] ?? '') ?>">
      </div>
 
      <div class="form-row">
        <div class="form-group">
          <label>Last Updated</label>
          <input type="date" name="last_updated"
                 value="<?= htmlspecialchars($_POST['last_updated'] ?? date('Y-m-d')) ?>">
        </div>
        <div class="form-group">
          <label>Managed By</label>
          <select name="managed_by">
            <option value="">— None —</option>
            <?php while ($s = $staffList->fetch_assoc()): ?>
              <option value="<?= $s['staff_id'] ?>"
                <?= (isset($_POST['managed_by']) && $_POST['managed_by'] == $s['staff_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['full_name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
 
      <div class="form-actions">
        <a href="supervisor.php?tab=inventory" class="btn-cancel"><i class="fas fa-times"></i>&nbsp; Cancel</a>
        <button type="submit" class="btn-submit"><i class="fas fa-check"></i>&nbsp; Add Item</button>
      </div>
    </form>
  </div>
</div>
</body>
</html>
 