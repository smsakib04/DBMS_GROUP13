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
        die("Update failed: " . $stmt->error);
    }
}

$pair_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$pair_id) die("Invalid pair ID.");
$result = $conn->query("SELECT * FROM breeding_pairs WHERE pair_id = $pair_id");
$pair = $result->fetch_assoc();
if (!$pair) die("Pair not found.");

$males = $conn->query("SELECT tortoise_id, name, microchip_id FROM tortoises WHERE sex IN ('Male','Unknown')");
$females = $conn->query("SELECT tortoise_id, name, microchip_id FROM tortoises WHERE sex IN ('Female','Unknown')");
?>
<!DOCTYPE html>
<html>
<head><title>Edit Breeding Pair</title>
<style>/* same as add form style */</style>
</head>
<body>
<div class="form-card">
    <h2>✏️ Edit Breeding Pair</h2>
    <form method="POST">
        <input type="hidden" name="pair_id" value="<?= $pair['pair_id'] ?>">
        <label>Pair Code</label>
        <input type="text" name="pair_code" value="<?= htmlspecialchars($pair['pair_code']) ?>" required>

        <label>Male Tortoise</label>
        <select name="male_tortoise_id" required>
            <?php while($m = $males->fetch_assoc()): ?>
                <option value="<?= $m['tortoise_id'] ?>" <?= $pair['male_tortoise_id'] == $m['tortoise_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['name'] . ' (' . $m['microchip_id'] . ')') ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Female Tortoise</label>
        <select name="female_tortoise_id" required>
            <?php while($f = $females->fetch_assoc()): ?>
                <option value="<?= $f['tortoise_id'] ?>" <?= $pair['female_tortoise_id'] == $f['tortoise_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($f['name'] . ' (' . $f['microchip_id'] . ')') ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Pairing Date</label>
        <input type="date" name="pairing_date" value="<?= $pair['pairing_date'] ?>" required>

        <label>Status</label>
        <select name="status">
            <?php foreach (['paired','courting','incubating','hatched','separated'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $pair['status'] == $opt ? 'selected' : '' ?>><?= ucfirst($opt) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Notes</label>
        <textarea name="notes" rows="3"><?= htmlspecialchars($pair['notes']) ?></textarea>

        <div class="button-group">
            <button type="submit">Update Pair</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>