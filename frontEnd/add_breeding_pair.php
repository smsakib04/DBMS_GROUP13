<?php
require_once '../backEnd/config/db.php';
session_start();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pair_code = trim($_POST['pair_code']);
    $male_id = intval($_POST['male_tortoise_id']);
    $female_id = intval($_POST['female_tortoise_id']);
    $pairing_date = $_POST['pairing_date'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);

    if (empty($pair_code) || $male_id <= 0 || $female_id <= 0 || empty($pairing_date)) {
        $error = "All required fields must be filled.";
    } elseif ($male_id === $female_id) {
        $error = "Male and female tortoises must be different.";
    } else {
        $check = $conn->prepare("SELECT pair_id FROM breeding_pairs WHERE pair_code = ?");
        $check->bind_param("s", $pair_code);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Pair code already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO breeding_pairs (pair_code, male_tortoise_id, female_tortoise_id, pairing_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisss", $pair_code, $male_id, $female_id, $pairing_date, $status, $notes);
            if ($stmt->execute()) {
                header("Location: breeding.php?msg=pair_added");
                exit();
            } else {
                $error = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}

$males = $conn->query("SELECT tortoise_id, name, microchip_id FROM tortoises WHERE sex IN ('Male','Unknown') ORDER BY name");
$females = $conn->query("SELECT tortoise_id, name, microchip_id FROM tortoises WHERE sex IN ('Female','Unknown') ORDER BY name");
?>
<!DOCTYPE html>
<html>
<head><title>Add Breeding Pair</title>
<style>/* same as your existing style */</style>
</head>
<body>
<div class="form-card">
    <h2>➕ Add New Breeding Pair</h2>
    <?php if ($error): ?><div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <label>Pair Code *</label>
        <input type="text" name="pair_code" required placeholder="e.g., BP-025">

        <label>Male Tortoise *</label>
        <select name="male_tortoise_id" required>
            <option value="">-- Select Male --</option>
            <?php while($m = $males->fetch_assoc()): ?>
                <option value="<?= $m['tortoise_id'] ?>"><?= htmlspecialchars($m['name'] . ' (' . $m['microchip_id'] . ')') ?></option>
            <?php endwhile; ?>
        </select>

        <label>Female Tortoise *</label>
        <select name="female_tortoise_id" required>
            <option value="">-- Select Female --</option>
            <?php while($f = $females->fetch_assoc()): ?>
                <option value="<?= $f['tortoise_id'] ?>"><?= htmlspecialchars($f['name'] . ' (' . $f['microchip_id'] . ')') ?></option>
            <?php endwhile; ?>
        </select>

        <label>Pairing Date *</label>
        <input type="date" name="pairing_date" value="<?= date('Y-m-d') ?>" required>

        <label>Status</label>
        <select name="status">
            <option value="paired">Paired</option>
            <option value="courting">Courting</option>
            <option value="incubating">Incubating</option>
            <option value="hatched">Hatched</option>
            <option value="separated">Separated</option>
        </select>

        <label>Notes</label>
        <textarea name="notes" rows="3"></textarea>

        <div class="button-group">
            <button type="submit">Save Pair</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>