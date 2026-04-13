<?php
require_once '../backEnd/config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['nest_id'];
    $nest_code = $_POST['nest_code'];
    $pair_id = $_POST['pair_id'];
    $nesting_date = $_POST['nesting_date'];
    $egg_count = $_POST['egg_count'];
    $fertile = $_POST['fertile'];
    $incubator_id = !empty($_POST['incubator_id']) ? intval($_POST['incubator_id']) : null;
    $est_hatch = !empty($_POST['estimated_hatch_date']) ? $_POST['estimated_hatch_date'] : null;
    $actual_hatch = !empty($_POST['actual_hatch_date']) ? $_POST['actual_hatch_date'] : null;
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE nests SET nest_code=?, pair_id=?, nesting_date=?, egg_count=?, fertile_eggs=?, incubator_id=?, estimated_hatch_date=?, actual_hatch_date=?, notes=? WHERE nest_id=?");
    $stmt->bind_param("sisiiisssi", $nest_code, $pair_id, $nesting_date, $egg_count, $fertile, $incubator_id, $est_hatch, $actual_hatch, $notes, $id);
    if ($stmt->execute()) {
        header("Location: breeding.php?msg=nest_updated");
        exit();
    } else {
        $error = "Update failed: " . $stmt->error;
    }
    $stmt->close();
}

$nest_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$nest_id) die("Invalid nest ID.");
$result = $conn->query("SELECT * FROM nests WHERE nest_id = $nest_id");
$nest = $result->fetch_assoc();
if (!$nest) die("Nest not found.");

$pairs = $conn->query("SELECT pair_id, pair_code FROM breeding_pairs ORDER BY pair_code");
$incubators = $conn->query("SELECT incubator_id, incubator_code FROM incubators WHERE status = 'active' ORDER BY incubator_code");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Nest</title>
    <style>
        /* Same as edit_breeding_pair.php styles */
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
        body{background:#ecf6f1;padding:2rem;display:flex;justify-content:center;}
        .form-card{max-width:650px;width:100%;background:white;border-radius:28px;padding:2rem;box-shadow:0 12px 28px rgba(0,0,0,0.08);}
        h2{margin-bottom:1.5rem;color:#1a6d4e;display:flex;align-items:center;gap:0.5rem;}
        .alert-error{background:#f8d7da;color:#721c24;padding:0.8rem;border-radius:12px;margin-bottom:1rem;}
        label{display:block;margin-top:1rem;font-weight:600;color:#2b6e53;}
        input,select,textarea{width:100%;padding:0.7rem;margin-top:0.3rem;border-radius:12px;border:1px solid #cae5d9;background:#fefefe;}
        button{background:#1f7356;color:white;border:none;padding:0.8rem 1.5rem;border-radius:40px;margin-top:1.5rem;cursor:pointer;font-weight:600;}
        .cancel-btn{background:#dc3545;margin-left:1rem;}
        .button-group{display:flex;gap:1rem;justify-content:flex-end;}
        .info-text{font-size:0.8rem;color:#6c8b7a;margin-top:0.2rem;}
        .required:after{content:" *";color:#dc3545;}
    </style>
</head>
<body>
<div class="form-card">
    <h2><i class="fas fa-edit"></i> Edit Nest</h2>
    <?php if (isset($error)): ?>
        <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="nest_id" value="<?php echo $nest['nest_id']; ?>">

        <label class="required">Nest Code</label>
        <input type="text" name="nest_code" value="<?php echo htmlspecialchars($nest['nest_code']); ?>" required>

        <label class="required">Breeding Pair</label>
        <select name="pair_id" required>
            <?php while($p = $pairs->fetch_assoc()): ?>
                <option value="<?php echo $p['pair_id']; ?>" <?php echo ($nest['pair_id'] == $p['pair_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($p['pair_code']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label class="required">Nesting Date</label>
        <input type="date" name="nesting_date" value="<?php echo $nest['nesting_date']; ?>" required>

        <label class="required">Egg Count</label>
        <input type="number" name="egg_count" value="<?php echo $nest['egg_count']; ?>" required>

        <label>Fertile Eggs</label>
        <input type="number" name="fertile" value="<?php echo $nest['fertile_eggs']; ?>">

        <label>Incubator</label>
        <select name="incubator_id">
            <option value="">None</option>
            <?php while($i = $incubators->fetch_assoc()): ?>
                <option value="<?php echo $i['incubator_id']; ?>" <?php echo ($nest['incubator_id'] == $i['incubator_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($i['incubator_code']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Estimated Hatch Date</label>
        <input type="date" name="estimated_hatch_date" value="<?php echo $nest['estimated_hatch_date']; ?>">

        <label>Actual Hatch Date</label>
        <input type="date" name="actual_hatch_date" value="<?php echo $nest['actual_hatch_date']; ?>">

        <label>Notes</label>
        <textarea name="notes" rows="3"><?php echo htmlspecialchars($nest['notes']); ?></textarea>

        <div class="button-group">
            <button type="submit"><i class="fas fa-save"></i> Update Nest</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'"><i class="fas fa-times"></i> Cancel</button>
        </div>
    </form>
</div>
</body>
</html>