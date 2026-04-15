<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $tortoise_name = trim($_POST['tortoise_name'] ?? '');
    $species_name = trim($_POST['species'] ?? '');
    $estimated_age = intval($_POST['estimated_age'] ?? 0);
    $sex = $_POST['sex'] ?? 'Unknown';
    $source_type = $_POST['source_type'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $collection_date = $_POST['collection_date'] ?? '';
    $initial_health = $_POST['initial_health'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    $collected_by = !empty($_POST['collected_by']) ? intval($_POST['collected_by']) : null;

    // Validate required fields
    if (empty($species_name) || empty($collection_date) || empty($source_type) || empty($location) || empty($initial_health)) {
        $error = "All required fields must be filled.";
    } else {
        // Get or create species_id
        $species_id = null;
        $speciesStmt = $conn->prepare("SELECT species_id FROM species WHERE common_name LIKE ?");
        $likeName = "%$species_name%";
        $speciesStmt->bind_param("s", $likeName);
        $speciesStmt->execute();
        $speciesRes = $speciesStmt->get_result();
        
        if ($speciesRes->num_rows > 0) {
            $species_id = $speciesRes->fetch_assoc()['species_id'];
        } else {
            // Insert new species
            $insertSpecies = $conn->prepare("INSERT INTO species (common_name, scientific_name) VALUES (?, ?)");
            $scientific = "Unknown";
            $insertSpecies->bind_param("ss", $species_name, $scientific);
            if ($insertSpecies->execute()) {
                $species_id = $insertSpecies->insert_id;
            }
            $insertSpecies->close();
        }
        $speciesStmt->close();

        if ($species_id) {
            // Insert into tortoises
            $microchip = 'COL-' . time() . '-' . rand(100, 999);
            $health_status = ($initial_health == 'Healthy') ? 'Healthy' : (($initial_health == 'Weak') ? 'Under observation' : 'Minor injury');
            
            $tortoiseStmt = $conn->prepare("INSERT INTO tortoises (microchip_id, name, species_id, sex, estimated_age_years, health_status, acquisition_source, acquisition_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $tortoiseStmt->bind_param("ssissssss", $microchip, $tortoise_name, $species_id, $sex, $estimated_age, $health_status, $source_type, $collection_date, $notes);
            
            if ($tortoiseStmt->execute()) {
                $new_tortoise_id = $tortoiseStmt->insert_id;
                $tortoiseStmt->close();

                // Insert into collections
                $colStmt = $conn->prepare("INSERT INTO collections (tortoise_id, collection_date, source_type, location, initial_health, notes, collected_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $colStmt->bind_param("isssssi", $new_tortoise_id, $collection_date, $source_type, $location, $initial_health, $notes, $collected_by);
                
                if ($colStmt->execute()) {
                    $message = "New tortoise and collection record saved successfully!";
                    // Clear form data after successful submission
                    echo "<script>setTimeout(function() { window.location.href = 'collectingOfficer.php'; }, 1500);</script>";
                } else {
                    $error = "Error saving collection: " . $colStmt->error;
                }
                $colStmt->close();
            } else {
                $error = "Error saving tortoise: " . $tortoiseStmt->error;
                $tortoiseStmt->close();
            }
        } else {
            $error = "Could not determine or create species.";
        }
    }
}

// Fetch staff members for dropdown
$staffQuery = "SELECT staff_id, CONCAT(first_name, ' ', last_name) as full_name FROM staff WHERE role IN ('collecting_officer', 'field_worker')";
$staffResult = $conn->query($staffQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Collection - Collecting Officer</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }
        body {
            background: #eef6f2;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0f5132, #198754);
            color: white;
            padding: 25px;
            text-align: center;
        }
        .content {
            padding: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #2c3e2f;
            margin-bottom: 8px;
        }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #cfdfd7;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #198754;
            box-shadow: 0 0 0 3px rgba(25,135,84,0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        button.primary {
            background: #198754;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            width: 100%;
            transition: background 0.2s;
        }
        button.primary:hover {
            background: #0f5132;
        }
        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            color: #198754;
            text-decoration: none;
            font-weight: 600;
        }
        .back-btn:hover {
            text-decoration: underline;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid #e2ece6;
        }
        .required:after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-plus-circle"></i> Add New Collection</h1>
            <p>Register a new tortoise and collection record</p>
        </div>
        <div class="content">
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Tortoise Name (Optional)</label>
                        <input type="text" name="tortoise_name" placeholder="e.g., Speedy" value="<?php echo isset($_POST['tortoise_name']) ? htmlspecialchars($_POST['tortoise_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="required">Species</label>
                        <input type="text" name="species" placeholder="e.g., Aldabra Giant Tortoise" required value="<?php echo isset($_POST['species']) ? htmlspecialchars($_POST['species']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Estimated Age (years)</label>
                        <input type="number" name="estimated_age" placeholder="Age in years" value="<?php echo isset($_POST['estimated_age']) ? htmlspecialchars($_POST['estimated_age']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Sex</label>
                        <select name="sex">
                            <option value="Unknown">Unknown</option>
                            <option value="Male" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo (isset($_POST['sex']) && $_POST['sex'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Source Type</label>
                        <select name="source_type" required>
                            <option value="">Select Source</option>
                            <option value="Wild" <?php echo (isset($_POST['source_type']) && $_POST['source_type'] == 'Wild') ? 'selected' : ''; ?>>Wild</option>
                            <option value="Rescue" <?php echo (isset($_POST['source_type']) && $_POST['source_type'] == 'Rescue') ? 'selected' : ''; ?>>Rescue</option>
                            <option value="Donation" <?php echo (isset($_POST['source_type']) && $_POST['source_type'] == 'Donation') ? 'selected' : ''; ?>>Donation</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="required">Collection Location</label>
                        <input type="text" name="location" placeholder="Collection location" required value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="required">Collection Date</label>
                        <input type="date" name="collection_date" required value="<?php echo isset($_POST['collection_date']) ? htmlspecialchars($_POST['collection_date']) : date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="required">Initial Health</label>
                        <select name="initial_health" required>
                            <option value="">Select Health Status</option>
                            <option value="Healthy" <?php echo (isset($_POST['initial_health']) && $_POST['initial_health'] == 'Healthy') ? 'selected' : ''; ?>>Healthy</option>
                            <option value="Weak" <?php echo (isset($_POST['initial_health']) && $_POST['initial_health'] == 'Weak') ? 'selected' : ''; ?>>Weak</option>
                            <option value="Injured" <?php echo (isset($_POST['initial_health']) && $_POST['initial_health'] == 'Injured') ? 'selected' : ''; ?>>Injured</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Collected By (Staff ID)</label>
                    <select name="collected_by">
                        <option value="">Select Staff Member</option>
                        <?php while($staff = $staffResult->fetch_assoc()): ?>
                            <option value="<?php echo $staff['staff_id']; ?>" <?php echo (isset($_POST['collected_by']) && $_POST['collected_by'] == $staff['staff_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($staff['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" rows="4" placeholder="Additional information..."><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                </div>

                <button type="submit" class="primary"><i class="fas fa-save"></i> Save Collection Record</button>
            </form>

            <hr>
            <a href="collectingOfficer.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>