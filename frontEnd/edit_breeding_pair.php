<?php
require_once '../backEnd/config/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['pair_id'];
    $code = $_POST['pair_code'];
    $male = $_POST['male_tortoise_id'];
    $female = $_POST['female_tortoise_id'];
    $date = $_POST['pairing_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE breeding_pairs SET pair_code=?, male_tortoise_id=?, female_tortoise_id=?, pairing_date=?, status=?, notes=? WHERE pair_id=?");
    $stmt->bind_param("siisssi", $code, $male, $female, $date, $status, $notes, $id);
    if ($stmt->execute()) {
        header("Location: breeding.php?msg=pair_updated");
        exit();
    } else {
        $error = "Update failed: " . $stmt->error;
    }
    $stmt->close();
}

$pair_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$pair_id) die("Invalid pair ID.");
$result = $conn->query("SELECT * FROM breeding_pairs WHERE pair_id = $pair_id");
$pair = $result->fetch_assoc();
if (!$pair) die("Pair not found.");

// Fetch tortoises with species
$males = $conn->query("
    SELECT t.tortoise_id, t.name, t.microchip_id, s.common_name AS species
    FROM tortoises t
    JOIN species s ON t.species_id = s.species_id
    WHERE t.sex IN ('Male', 'Unknown')
    ORDER BY t.name
");
$females = $conn->query("
    SELECT t.tortoise_id, t.name, t.microchip_id, s.common_name AS species
    FROM tortoises t
    JOIN species s ON t.species_id = s.species_id
    WHERE t.sex IN ('Female', 'Unknown')
    ORDER BY t.name
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Breeding Pair</title>
    <style>
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
    <h2><i class="fas fa-edit"></i> Edit Breeding Pair</h2>
    <?php if (isset($error)): ?>
        <div class="alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="hidden" name="pair_id" value="<?php echo $pair['pair_id']; ?>">

        <label class="required">Pair Code</label>
        <input type="text" name="pair_code" value="<?php echo htmlspecialchars($pair['pair_code']); ?>" required>
        <div class="info-text">Unique identifier – changing it may affect references</div>

        <label class="required">Male Tortoise</label>
        <select name="male_tortoise_id" required>
            <option value="">-- Select Male --</option>
            <?php while($m = $males->fetch_assoc()): ?>
                <option value="<?php echo $m['tortoise_id']; ?>" <?php echo ($pair['male_tortoise_id'] == $m['tortoise_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($m['name'] . ' (' . $m['microchip_id'] . ') – ' . $m['species']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label class="required">Female Tortoise</label>
        <select name="female_tortoise_id" required>
            <option value="">-- Select Female --</option>
            <?php while($f = $females->fetch_assoc()): ?>
                <option value="<?php echo $f['tortoise_id']; ?>" <?php echo ($pair['female_tortoise_id'] == $f['tortoise_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($f['name'] . ' (' . $f['microchip_id'] . ') – ' . $f['species']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label class="required">Pairing Date</label>
        <input type="date" name="pairing_date" value="<?php echo $pair['pairing_date']; ?>" required>

        <label>Status</label>
        <select name="status">
            <?php foreach (['paired','courting','incubating','hatched','separated'] as $opt): ?>
                <option value="<?php echo $opt; ?>" <?php echo ($pair['status'] == $opt) ? 'selected' : ''; ?>>
                    <?php echo ucfirst($opt); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Notes</label>
        <textarea name="notes" rows="3"><?php echo htmlspecialchars($pair['notes']); ?></textarea>

        <div class="button-group">
            <button type="submit"><i class="fas fa-save"></i> Update Pair</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'"><i class="fas fa-times"></i> Cancel</button>
        </div>
    </form>
</div>
</body>
</html>