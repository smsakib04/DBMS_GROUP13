<?php
require_once '../backEnd/includes/session.php';
requireLogin();
require_once '../backEnd/config/db.php';

$message = '';
$error = '';
$deleted = false;

// Get collection ID from URL
$collection_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($collection_id <= 0 && !isset($_POST['confirm'])) {
    header('Location: collectingOfficer.php');
    exit();
}

// Fetch collection data for confirmation display
if ($collection_id > 0 && !isset($_POST['confirm'])) {
    $collectionQuery = "
        SELECT c.collection_id, 
               COALESCE(t.name, 'Unnamed Tortoise') AS tortoise_name,
               t.microchip_id,
               c.source_type, 
               c.location, 
               c.collection_date,
               c.initial_health
        FROM collections c
        LEFT JOIN tortoises t ON c.tortoise_id = t.tortoise_id
        WHERE c.collection_id = ?
    ";
    $stmt = $conn->prepare($collectionQuery);
    $stmt->bind_param("i", $collection_id);
    $stmt->execute();
    $collection = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$collection) {
        header('Location: collectingOfficer.php');
        exit();
    }
}

// Handle deletion confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    $collection_id = intval($_POST['collection_id']);
    $delete_tortoise = isset($_POST['delete_tortoise']) ? true : false;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get tortoise_id from collection
        $getTortoise = $conn->prepare("SELECT tortoise_id FROM collections WHERE collection_id = ?");
        $getTortoise->bind_param("i", $collection_id);
        $getTortoise->execute();
        $result = $getTortoise->get_result();
        $tortoise_data = $result->fetch_assoc();
        $tortoise_id = $tortoise_data['tortoise_id'] ?? null;
        $getTortoise->close();
        
        // Delete collection record
        $deleteCollection = $conn->prepare("DELETE FROM collections WHERE collection_id = ?");
        $deleteCollection->bind_param("i", $collection_id);
        
        if (!$deleteCollection->execute()) {
            throw new Exception("Error deleting collection record: " . $deleteCollection->error);
        }
        $deleteCollection->close();
        
        // Optionally delete the associated tortoise
        if ($delete_tortoise && $tortoise_id) {
            // First check if tortoise has any other references (transport logs, medical records, etc.)
            $checkRefs = $conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM transport_logs WHERE tortoise_id = ?) as transport_count,
                    (SELECT COUNT(*) FROM medical_records WHERE tortoise_id = ?) as medical_count,
                    (SELECT COUNT(*) FROM feeding_schedules WHERE tortoise_id = ?) as feeding_count,
                    (SELECT COUNT(*) FROM breeding_pairs WHERE male_tortoise_id = ? OR female_tortoise_id = ?) as breeding_count
            ");
            $checkRefs->bind_param("iiiii", $tortoise_id, $tortoise_id, $tortoise_id, $tortoise_id, $tortoise_id);
            $checkRefs->execute();
            $refs = $checkRefs->get_result()->fetch_assoc();
            $checkRefs->close();
            
            if ($refs['transport_count'] > 0 || $refs['medical_count'] > 0 || $refs['feeding_count'] > 0 || $refs['breeding_count'] > 0) {
                // Tortoise has references, cannot delete
                $error = "Cannot delete tortoise because it has existing records in other modules. The collection record has been deleted, but the tortoise remains in the system.";
                $conn->commit();
                $deleted = true;
            } else {
                // Delete tortoise
                $deleteTortoise = $conn->prepare("DELETE FROM tortoises WHERE tortoise_id = ?");
                $deleteTortoise->bind_param("i", $tortoise_id);
                
                if (!$deleteTortoise->execute()) {
                    throw new Exception("Error deleting tortoise record: " . $deleteTortoise->error);
                }
                $deleteTortoise->close();
                $message = "Collection record and associated tortoise have been permanently deleted.";
                $conn->commit();
                $deleted = true;
            }
        } else {
            $message = "Collection record has been permanently deleted. The tortoise record remains in the system.";
            $conn->commit();
            $deleted = true;
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Handle deletion of multiple records (bulk delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete']) && isset($_POST['selected_ids'])) {
    $selected_ids = $_POST['selected_ids'];
    
    if (is_array($selected_ids) && count($selected_ids) > 0) {
        $conn->begin_transaction();
        $deleted_count = 0;
        $errors = [];
        
        foreach ($selected_ids as $col_id) {
            $col_id = intval($col_id);
            
            // Get tortoise_id
            $getTortoise = $conn->prepare("SELECT tortoise_id FROM collections WHERE collection_id = ?");
            $getTortoise->bind_param("i", $col_id);
            $getTortoise->execute();
            $result = $getTortoise->get_result();
            $tortoise_data = $result->fetch_assoc();
            $tortoise_id = $tortoise_data['tortoise_id'] ?? null;
            $getTortoise->close();
            
            // Delete collection
            $deleteCollection = $conn->prepare("DELETE FROM collections WHERE collection_id = ?");
            $deleteCollection->bind_param("i", $col_id);
            
            if ($deleteCollection->execute()) {
                $deleted_count++;
            } else {
                $errors[] = "Failed to delete collection ID: $col_id";
            }
            $deleteCollection->close();
        }
        
        if (count($errors) == 0) {
            $conn->commit();
            $message = "Successfully deleted $deleted_count collection record(s).";
            $deleted = true;
        } else {
            $conn->rollback();
            $error = "Some records could not be deleted: " . implode(", ", $errors);
        }
    }
}

// Get health badge class
function getHealthClass($health) {
    switch($health) {
        case 'Healthy': return 'good';
        case 'Weak': return 'warning';
        case 'Injured': return 'critical';
        default: return '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Collection - Collecting Officer</title>
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
            max-width: 700px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #0f5132, #198754);
            color: white;
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 20px;
            text-align: center;
        }
        .warning-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            text-align: center;
        }
        .warning-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        .warning-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #721c24;
            margin-bottom: 15px;
        }
        .warning-message {
            color: #856404;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .collection-details {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2ece6;
        }
        .detail-label {
            font-weight: 600;
            color: #2c3e2f;
        }
        .detail-value {
            color: #6c757d;
        }
        .status {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
        }
        .good { background: #d1e7dd; color: #0f5132; }
        .warning { background: #fff3cd; color: #856404; }
        .critical { background: #f8d7da; color: #842029; }
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }
        .btn {
            padding: 12px 28px;
            border: none;
            border-radius: 40px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-warning {
            background: #ffc107;
            color: #856404;
        }
        .btn-warning:hover {
            background: #e0a800;
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
        .checkbox-group {
            background: #fff3cd;
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            text-align: left;
        }
        .checkbox-group label {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input {
            width: 18px;
            height: 18px;
            cursor: pointer;
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
        .bulk-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px dashed #e2ece6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-trash-alt"></i> Delete Collection Record</h1>
            <p>Permanently remove collection and related data</p>
        </div>

        <?php if ($message): ?>
            <div class="message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($deleted): ?>
            <div class="warning-card">
                <div class="warning-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="warning-title">Operation Complete</div>
                <div class="warning-message">
                    <?php echo htmlspecialchars($message ?: 'The operation has been completed.'); ?>
                </div>
                <div class="button-group">
                    <a href="collectingOfficer.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        <?php elseif (isset($collection)): ?>
            <div class="warning-card">
                <div class="warning-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="warning-title">Warning: This action cannot be undone!</div>
                <div class="warning-message">
                    You are about to permanently delete the following collection record. 
                    Please review the details below before confirming.
                </div>

                <div class="collection-details">
                    <div class="detail-row">
                        <span class="detail-label">Collection ID:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($collection['collection_id']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tortoise Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($collection['tortoise_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Microchip ID:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($collection['microchip_id'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Source Type:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($collection['source_type']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Collection Location:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($collection['location']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Collection Date:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($collection['collection_date']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Initial Health:</span>
                        <span class="detail-value">
                            <span class="status <?php echo getHealthClass($collection['initial_health']); ?>">
                                <?php echo htmlspecialchars($collection['initial_health']); ?>
                            </span>
                        </span>
                    </div>
                </div>

                <form method="POST" action="">
                    <input type="hidden" name="collection_id" value="<?php echo $collection_id; ?>">
                    <input type="hidden" name="confirm" value="yes">
                    
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="delete_tortoise" value="1">
                            <strong>Also permanently delete the associated tortoise record</strong>
                            <small style="display: block; margin-top: 5px; color: #856404;">
                                <i class="fas fa-info-circle"></i> 
                                Warning: This will also remove the tortoise from all related records if no other references exist.
                                If the tortoise has transport, medical, or breeding records, it cannot be deleted.
                            </small>
                        </label>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you ABSOLUTELY sure? This action cannot be undone!');">
                            <i class="fas fa-trash-alt"></i> Permanently Delete
                        </button>
                        <a href="collectingOfficer.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <!-- Bulk Delete Section -->
        <div class="bulk-section">
            <div class="warning-card">
                <div class="warning-icon" style="font-size: 2rem;">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="warning-title">Bulk Delete</div>
                <div class="warning-message">
                    Delete multiple collection records at once. This will only delete collection records, 
                    not the associated tortoises.
                </div>

                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete the selected records? This action cannot be undone!');">
                    <input type="hidden" name="bulk_delete" value="1">
                    
                    <div class="collection-details">
                        <p style="margin-bottom: 15px; font-weight: 600;">Select collections to delete (from dashboard):</p>
                        <p style="color: #6c757d; font-size: 0.9rem;">
                            <i class="fas fa-info-circle"></i> 
                            To use bulk delete, go to the Collection Records section, select multiple records using checkboxes,
                            then click "Bulk Delete" button.
                        </p>
                        <hr>
                        <p style="margin-top: 15px;">
                            <a href="collectingOfficer.php" class="btn btn-secondary" style="font-size: 0.85rem;">
                                <i class="fas fa-arrow-left"></i> Go to Collection Records
                            </a>
                        </p>
                    </div>
                </form>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="collectingOfficer.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        // Additional confirmation for checkbox warning
        document.querySelector('input[name="delete_tortoise"]')?.addEventListener('change', function(e) {
            if (this.checked) {
                const confirmMsg = confirm(
                    "WARNING: You are about to delete the tortoise record as well. " +
                    "This will permanently remove the tortoise from the system. " +
                    "Continue only if you are sure this tortoise has no other records.\n\n" +
                    "Click OK to continue or Cancel to uncheck."
                );
                if (!confirmMsg) {
                    this.checked = false;
                }
            }
        });
    </script>
</body>
</html>