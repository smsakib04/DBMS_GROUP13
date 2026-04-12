<?php
require_once '../backEnd/config/db.php';
session_start();

$error = '';
$success = '';
$pair_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pair_id = intval($_POST['pair_id']);
    if ($pair_id <= 0) {
        $error = "Invalid pair ID.";
    } else {
        $conn->begin_transaction();
        try {
            // Delete related nests first
            $delNests = $conn->prepare("DELETE FROM nests WHERE pair_id = ?");
            $delNests->bind_param("i", $pair_id);
            $delNests->execute();
            $delNests->close();

            // Delete the pair
            $stmt = $conn->prepare("DELETE FROM breeding_pairs WHERE pair_id = ?");
            $stmt->bind_param("i", $pair_id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                throw new Exception("No breeding pair found with ID: $pair_id");
            }
            $stmt->close();
            $conn->commit();
            header("Location: breeding.php?msg=pair_deleted");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delete Breeding Pair</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:'Inter',sans-serif;}
        body{background:#ecf6f1;padding:2rem;display:flex;justify-content:center;}
        .form-card{max-width:500px;width:100%;background:white;border-radius:28px;padding:2rem;box-shadow:0 12px 28px rgba(0,0,0,0.08);}
        h2{margin-bottom:1.5rem;color:#1a6d4e;}
        label{display:block;margin-top:1rem;font-weight:600;color:#2b6e53;}
        input{width:100%;padding:0.7rem;margin-top:0.3rem;border-radius:12px;border:1px solid #cae5d9;}
        button{background:#dc3545;color:white;border:none;padding:0.8rem 1.5rem;border-radius:40px;margin-top:1.5rem;cursor:pointer;}
        .cancel-btn{background:#6c757d;margin-left:1rem;}
        .button-group{display:flex;gap:1rem;justify-content:flex-end;}
        .info-text{font-size:0.8rem;color:#6c8b7a;margin-top:0.2rem;}
        .error{background:#f8d7da;color:#721c24;padding:0.8rem;border-radius:12px;margin-bottom:1rem;}
    </style>
</head>
<body>
<div class="form-card">
    <h2>🗑️ Delete Breeding Pair</h2>
    <?php if ($error): ?>
        <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Pair ID</label>
        <input type="number" name="pair_id" value="<?php echo $pair_id; ?>" required readonly>
        <div class="info-text">⚠️ This will also delete all related nests.</div>
        <div class="button-group">
            <button type="submit">Delete Pair</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>