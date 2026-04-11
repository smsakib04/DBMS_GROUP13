<?php
require_once '../backEnd/config/db.php';
session_start();

// -------------------------------
// Handle form submission (POST)
// -------------------------------
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
        // Success: redirect to breeding dashboard with success message
        header("Location: breeding.php?msg=pair_updated");
        exit();
    } else {
        die("Error updating pair: " . $stmt->error);
    }
    $stmt->close();
    $conn->close();
}

// -------------------------------
// Display edit form (GET request)
// -------------------------------
$pair_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($pair_id == 0) {
    die("Invalid pair ID. Please go back and select a valid breeding pair.");
}

// Fetch the existing data
$stmt = $conn->prepare("SELECT * FROM breeding_pairs WHERE pair_id = ?");
$stmt->bind_param("i", $pair_id);
$stmt->execute();
$result = $stmt->get_result();
$pair = $result->fetch_assoc();
$stmt->close();

if (!$pair) {
    die("Breeding pair not found for ID: " . $pair_id);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Edit Breeding Pair</title>
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
    <h2>✏️ Edit Breeding Pair</h2>
    <form action="" method="POST">
        <input type="hidden" name="pair_id" value="<?php echo htmlspecialchars($pair['pair_id']); ?>">
        
        <label>Pair Code</label>
        <input type="text" name="pair_code" value="<?php echo htmlspecialchars($pair['pair_code']); ?>">
        
        <label>Male Tortoise ID</label>
        <input type="number" name="male_tortoise_id" value="<?php echo htmlspecialchars($pair['male_tortoise_id']); ?>" required>
        
        <label>Female Tortoise ID</label>
        <input type="number" name="female_tortoise_id" value="<?php echo htmlspecialchars($pair['female_tortoise_id']); ?>" required>
        
        <label>Pairing Date</label>
        <input type="date" name="pairing_date" value="<?php echo htmlspecialchars($pair['pairing_date']); ?>" required>
        
        <label>Status</label>
        <select name="status" required>
            <option value="paired" <?php echo $pair['status'] == 'paired' ? 'selected' : ''; ?>>Paired</option>
            <option value="courting" <?php echo $pair['status'] == 'courting' ? 'selected' : ''; ?>>Courting</option>
            <option value="incubating" <?php echo $pair['status'] == 'incubating' ? 'selected' : ''; ?>>Incubating</option>
            <option value="hatched" <?php echo $pair['status'] == 'hatched' ? 'selected' : ''; ?>>Hatched</option>
            <option value="separated" <?php echo $pair['status'] == 'separated' ? 'selected' : ''; ?>>Separated</option>
        </select>
        
        <label>Notes</label>
        <textarea name="notes" rows="3"><?php echo htmlspecialchars($pair['notes']); ?></textarea>
        
        <div class="button-group">
            <button type="submit">Update Pair</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>