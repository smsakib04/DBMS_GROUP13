<?php
require_once '../backEnd/config/db.php';
session_start();

// -------------------------------
// Handle form submission (POST)
// -------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['nest_id'];
    $nest_code = $_POST['nest_code'];
    $pair_id = $_POST['pair_id'];
    $nesting_date = $_POST['nesting_date'];
    $egg_count = $_POST['egg_count'];
    $fertile = $_POST['fertile'];
    $incubator_id = $_POST['incubator_id'];
    $est_hatch = $_POST['estimated_hatch_date'];
    $actual_hatch = $_POST['actual_hatch_date'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE nests SET nest_code=?, pair_id=?, nesting_date=?, egg_count=?, fertile_eggs=?, incubator_id=?, estimated_hatch_date=?, actual_hatch_date=?, notes=? WHERE nest_id=?");
    $stmt->bind_param("sisiiisssi", $nest_code, $pair_id, $nesting_date, $egg_count, $fertile, $incubator_id, $est_hatch, $actual_hatch, $notes, $id);

    if ($stmt->execute()) {
        header("Location: breeding.php?msg=nest_updated");
        exit();
    } else {
        die("Error updating nest: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}

// -------------------------------
// Display edit form (GET request)
// -------------------------------
$nest_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($nest_id == 0) {
    die("Invalid nest ID.");
}

$stmt = $conn->prepare("SELECT * FROM nests WHERE nest_id = ?");
$stmt->bind_param("i", $nest_id);
$stmt->execute();
$result = $stmt->get_result();
$nest = $result->fetch_assoc();
$stmt->close();

if (!$nest) {
    die("Nest not found for ID: " . $nest_id);
}

// Fetch breeding pairs for dropdown (optional)
$pairsResult = $conn->query("SELECT pair_id, pair_code FROM breeding_pairs ORDER BY pair_code");
$pairs = [];
while ($row = $pairsResult->fetch_assoc()) {
    $pairs[] = $row;
}

// Fetch incubators for dropdown
$incubatorsResult = $conn->query("SELECT incubator_id, incubator_code FROM incubators WHERE status = 'active'");
$incubators = [];
while ($row = $incubatorsResult->fetch_assoc()) {
    $incubators[] = $row;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Nest</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
        body{background:#ecf6f1;padding:2rem;display:flex;justify-content:center;}
        .form-card{max-width:600px;width:100%;background:white;border-radius:28px;padding:2rem;box-shadow:0 12px 28px rgba(0,0,0,0.08);}
        h2{margin-bottom:1.5rem;color:#1a6d4e;}
        label{display:block;margin-top:1rem;font-weight:600;color:#2b6e53;}
        input,select,textarea{width:100%;padding:0.7rem;margin-top:0.3rem;border-radius:12px;border:1px solid #cae5d9;background:#fefefe;}
        button{background:#1f7356;color:white;border:none;padding:0.8rem 1.5rem;border-radius:40px;margin-top:1.5rem;cursor:pointer;}
        .cancel-btn{background:#dc3545;margin-left:1rem;}
        .button-group{display:flex;gap:1rem;justify-content:flex-end;}
    </style>
</head>
<body>
<div class="form-card">
    <h2>✏️ Edit Nest</h2>
    <form action="" method="POST">
        <input type="hidden" name="nest_id" value="<?php echo htmlspecialchars($nest['nest_id']); ?>">

        <label>Nest Code</label>
        <input type="text" name="nest_code" value="<?php echo htmlspecialchars($nest['nest_code']); ?>" required>

        <label>Breeding Pair</label>
        <select name="pair_id" required>
            <option value="">Select Pair</option>
            <?php foreach ($pairs as $pair): ?>
                <option value="<?php echo $pair['pair_id']; ?>" <?php echo $nest['pair_id'] == $pair['pair_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($pair['pair_code']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Nesting Date</label>
        <input type="date" name="nesting_date" value="<?php echo htmlspecialchars($nest['nesting_date']); ?>" required>

        <label>Egg Count</label>
        <input type="number" name="egg_count" value="<?php echo $nest['egg_count']; ?>" required>

        <label>Fertile Eggs</label>
        <input type="number" name="fertile" value="<?php echo $nest['fertile_eggs']; ?>">

        <label>Incubator</label>
        <select name="incubator_id">
            <option value="">None</option>
            <?php foreach ($incubators as $inc): ?>
                <option value="<?php echo $inc['incubator_id']; ?>" <?php echo $nest['incubator_id'] == $inc['incubator_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($inc['incubator_code']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Estimated Hatch Date</label>
        <input type="date" name="estimated_hatch_date" value="<?php echo htmlspecialchars($nest['estimated_hatch_date']); ?>">

        <label>Actual Hatch Date</label>
        <input type="date" name="actual_hatch_date" value="<?php echo htmlspecialchars($nest['actual_hatch_date']); ?>">

        <label>Notes</label>
        <textarea name="notes" rows="3"><?php echo htmlspecialchars($nest['notes']); ?></textarea>

        <div class="button-group">
            <button type="submit">Update Nest</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>