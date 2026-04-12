<?php
require_once '../backEnd/config/db.php';
session_start();

// -------------------------------
// Handle form submission (POST)
// -------------------------------
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pair_code = trim($_POST['pair_code']);
    $male_id = intval($_POST['male_tortoise_id']);
    $female_id = intval($_POST['female_tortoise_id']);
    $pairing_date = $_POST['pairing_date'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);

    // Basic validation
    if (empty($pair_code) || $male_id <= 0 || $female_id <= 0 || empty($pairing_date)) {
        $error = "All required fields must be filled.";
    } elseif ($male_id === $female_id) {
        $error = "Male and female tortoises must be different.";
    } else {
        // Check if pair code already exists
        $check = $conn->prepare("SELECT pair_id FROM breeding_pairs WHERE pair_code = ?");
        $check->bind_param("s", $pair_code);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $error = "Pair code already exists. Please use a unique code.";
        } else {
            $stmt = $conn->prepare("INSERT INTO breeding_pairs (pair_code, male_tortoise_id, female_tortoise_id, pairing_date, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siisss", $pair_code, $male_id, $female_id, $pairing_date, $status, $notes);

            if ($stmt->execute()) {
                $success = "Breeding pair added successfully!";
                // Clear form fields after success (optional)
                // header("Location: breeding.php?msg=pair_added");
                // exit();
            } else {
                $error = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
        $check->close();
    }
}

// -------------------------------
// Fetch tortoises for dropdowns (filtered by sex)
// -------------------------------
$males = $conn->query("SELECT tortoise_id, name, microchip_id, species_id FROM tortoises WHERE sex = 'Male' OR sex = 'Unknown' ORDER BY name");
$females = $conn->query("SELECT tortoise_id, name, microchip_id, species_id FROM tortoises WHERE sex = 'Female' OR sex = 'Unknown' ORDER BY name");

// Helper to get species name (optional, for display)
function getSpeciesName($conn, $species_id) {
    $result = $conn->query("SELECT common_name FROM species WHERE species_id = $species_id");
    return $result->fetch_assoc()['common_name'] ?? 'Unknown';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Breeding Pair</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
        body{background:#ecf6f1;padding:2rem;display:flex;justify-content:center;}
        .form-card{max-width:650px;width:100%;background:white;border-radius:28px;padding:2rem;box-shadow:0 12px 28px rgba(0,0,0,0.08);}
        h2{margin-bottom:1.5rem;color:#1a6d4e; display:flex; align-items:center; gap:0.5rem;}
        .alert{padding:0.8rem; border-radius:12px; margin-bottom:1rem;}
        .alert-success{background:#d4edda; color:#155724; border:1px solid #c3e6cb;}
        .alert-error{background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;}
        label{display:block;margin-top:1rem;font-weight:600;color:#2b6e53;}
        input,select,textarea{width:100%;padding:0.7rem;margin-top:0.3rem;border-radius:12px;border:1px solid #cae5d9;background:#fefefe;}
        button{background:#1f7356;color:white;border:none;padding:0.8rem 1.5rem;border-radius:40px;margin-top:1.5rem;cursor:pointer;}
        .cancel-btn{background:#dc3545;margin-left:1rem;}
        .button-group{display:flex;gap:1rem;justify-content:flex-end;}
        .info-text{font-size:0.8rem; color:#6c8b7a; margin-top:0.2rem;}
    </style>
</head>
<body>
<div class="form-card">
    <h2><i class="fas fa-paw"></i> Add New Breeding Pair</h2>

    <?php if ($success): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?> 
            <a href="breeding.php" style="float:right; color:#155724;">Go back to dashboard →</a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <label>Pair Code *</label>
        <input type="text" name="pair_code" placeholder="e.g., BP-025" value="<?php echo isset($_POST['pair_code']) ? htmlspecialchars($_POST['pair_code']) : ''; ?>" required>
        <div class="info-text">Unique identifier for this breeding pair</div>

        <label>Male Tortoise *</label>
        <select name="male_tortoise_id" required>
            <option value="">-- Select Male --</option>
            <?php while($m = $males->fetch_assoc()): ?>
                <option value="<?php echo $m['tortoise_id']; ?>" 
                    <?php echo (isset($_POST['male_tortoise_id']) && $_POST['male_tortoise_id'] == $m['tortoise_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($m['name'] . ' (' . $m['microchip_id'] . ')'); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Female Tortoise *</label>
        <select name="female_tortoise_id" required>
            <option value="">-- Select Female --</option>
            <?php while($f = $females->fetch_assoc()): ?>
                <option value="<?php echo $f['tortoise_id']; ?>"
                    <?php echo (isset($_POST['female_tortoise_id']) && $_POST['female_tortoise_id'] == $f['tortoise_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($f['name'] . ' (' . $f['microchip_id'] . ')'); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Pairing Date *</label>
        <input type="date" name="pairing_date" value="<?php echo isset($_POST['pairing_date']) ? $_POST['pairing_date'] : date('Y-m-d'); ?>" required>

        <label>Status</label>
        <select name="status">
            <option value="paired" <?php echo (isset($_POST['status']) && $_POST['status'] == 'paired') ? 'selected' : ''; ?>>Paired</option>
            <option value="courting" <?php echo (isset($_POST['status']) && $_POST['status'] == 'courting') ? 'selected' : ''; ?>>Courting</option>
            <option value="incubating" <?php echo (isset($_POST['status']) && $_POST['status'] == 'incubating') ? 'selected' : ''; ?>>Incubating</option>
            <option value="hatched" <?php echo (isset($_POST['status']) && $_POST['status'] == 'hatched') ? 'selected' : ''; ?>>Hatched</option>
            <option value="separated" <?php echo (isset($_POST['status']) && $_POST['status'] == 'separated') ? 'selected' : ''; ?>>Separated</option>
        </select>

        <label>Notes (optional)</label>
        <textarea name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>

        <div class="button-group">
            <button type="submit"><i class="fas fa-save"></i> Save Pair</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'"><i class="fas fa-times"></i> Cancel</button>
        </div>
    </form>
</div>
</body>
</html>