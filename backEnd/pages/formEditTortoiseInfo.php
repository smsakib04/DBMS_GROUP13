<?php
require_once '../includes/session.php';
requireLogin();
require_once '../config/db.php';

$tortoise = null;
$tortoise_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($tortoise_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tortoises WHERE tortoise_id = ?");
    $stmt->bind_param("i", $tortoise_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tortoise = $result->fetch_assoc();
}

// Fetch species list for dropdown
$species_list = $conn->query("SELECT species_id, common_name FROM species ORDER BY common_name");

// Fetch enclosures for dropdown
$enclosures = $conn->query("SELECT enclosure_id, enclosure_code FROM enclosures WHERE status = 'Active'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tortoise ? 'Edit' : 'Add'; ?> Tortoise Information</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
        body{background:#ecf6f1;padding:2rem;display:flex;justify-content:center;}
        .form-card{max-width:800px;width:100%;background:white;border-radius:28px;padding:2rem;box-shadow:0 12px 28px rgba(0,0,0,0.08);}
        h2{margin-bottom:1.5rem;color:#1a6d4e;}
        label{display:block;margin-top:1rem;font-weight:600;color:#2b6e53;}
        input,select,textarea{width:100%;padding:0.7rem;margin-top:0.3rem;border-radius:12px;border:1px solid #cae5d9;background:#fefefe;}
        button{background:#1f7356;color:white;border:none;padding:0.8rem 1.5rem;border-radius:40px;margin-top:1.5rem;cursor:pointer;font-weight:600;}
        button:hover{background:#155e46;}
        .cancel-btn{background:#dc3545;margin-left:1rem;}
        .cancel-btn:hover{background:#bb2d3b;}
        .button-group{display:flex;gap:1rem;justify-content:flex-end;}
    </style>
</head>
<body>
<div class="form-card">
    <h2><?php echo $tortoise ? '✏️ Edit Tortoise' : '➕ Add New Tortoise'; ?></h2>
    <form action="../process/<?php echo $tortoise ? 'edit_tortoise.php' : 'add_tortoise.php'; ?>" method="POST">
        <?php if ($tortoise): ?>
            <input type="hidden" name="tortoise_id" value="<?php echo $tortoise['tortoise_id']; ?>">
        <?php endif; ?>

        <label>Microchip ID</label>
        <input type="text" name="microchip_id" value="<?php echo htmlspecialchars($tortoise['microchip_id'] ?? ''); ?>">

        <label>Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($tortoise['name'] ?? ''); ?>">

        <label>Species</label>
        <select name="species_id" required>
            <option value="">Select Species</option>
            <?php while($row = $species_list->fetch_assoc()): ?>
                <option value="<?php echo $row['species_id']; ?>" <?php echo (isset($tortoise['species_id']) && $tortoise['species_id'] == $row['species_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($row['common_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Sex</label>
        <select name="sex">
            <option value="Male" <?php echo (isset($tortoise['sex']) && $tortoise['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
            <option value="Female" <?php echo (isset($tortoise['sex']) && $tortoise['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
            <option value="Unknown" <?php echo (isset($tortoise['sex']) && $tortoise['sex'] == 'Unknown') ? 'selected' : ''; ?>>Unknown</option>
        </select>

        <label>Estimated Age (years)</label>
        <input type="number" step="0.1" name="estimated_age_years" value="<?php echo htmlspecialchars($tortoise['estimated_age_years'] ?? ''); ?>">

        <label>Weight (grams)</label>
        <input type="number" step="0.01" name="weight_grams" value="<?php echo htmlspecialchars($tortoise['weight_grams'] ?? ''); ?>">

        <label>Health Status</label>
        <select name="health_status">
            <?php
            $health_options = ['Healthy', 'Under observation', 'Recovering', 'Critical', 'Minor injury'];
            foreach ($health_options as $opt):
                $selected = (isset($tortoise['health_status']) && $tortoise['health_status'] == $opt) ? 'selected' : '';
            ?>
                <option value="<?php echo $opt; ?>" <?php echo $selected; ?>><?php echo $opt; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Enclosure</label>
        <select name="enclosure_id">
            <option value="">None</option>
            <?php while($row = $enclosures->fetch_assoc()): ?>
                <option value="<?php echo $row['enclosure_id']; ?>" <?php echo (isset($tortoise['enclosure_id']) && $tortoise['enclosure_id'] == $row['enclosure_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($row['enclosure_code']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Acquisition Date</label>
        <input type="date" name="acquisition_date" value="<?php echo htmlspecialchars($tortoise['acquisition_date'] ?? ''); ?>">

        <label>Acquisition Source</label>
        <select name="acquisition_source">
            <?php
            $sources = ['Wild', 'Rescue', 'Donation', 'Bred in captivity'];
            foreach ($sources as $src):
                $selected = (isset($tortoise['acquisition_source']) && $tortoise['acquisition_source'] == $src) ? 'selected' : '';
            ?>
                <option value="<?php echo $src; ?>" <?php echo $selected; ?>><?php echo $src; ?></option>
            <?php endforeach; ?>
        </select>

        <label>Notes</label>
        <textarea name="notes" rows="3"><?php echo htmlspecialchars($tortoise['notes'] ?? ''); ?></textarea>

        <div class="button-group">
            <button type="submit"><?php echo $tortoise ? 'Update Tortoise' : 'Save Tortoise'; ?></button>
            <button type="button" class="cancel-btn" onclick="window.location.href='veterenian.php'">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>