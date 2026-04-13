<?php
require_once '../backEnd/config/db.php';
session_start();

$error = '';
$nest_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nest_id = intval($_POST['nest_id']);
    if ($nest_id <= 0) {
        $error = "Invalid nest ID.";
    } else {
        $stmt = $conn->prepare("DELETE FROM nests WHERE nest_id = ?");
        $stmt->bind_param("i", $nest_id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $error = "No nest found with ID: $nest_id";
        } else {
            header("Location: breeding.php?msg=nest_deleted");
            exit();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Delete Nest</title>
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
        .error{background:#f8d7da;color:#721c24;padding:0.8rem;border-radius:12px;margin-bottom:1rem;}
    </style>
</head>
<body>
<div class="form-card">
    <h2>🗑️ Delete Nest</h2>
    <?php if ($error): ?>
        <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Nest ID</label>
        <input type="number" name="nest_id" value="<?php echo $nest_id; ?>" required readonly>
        <div class="button-group">
            <button type="submit">Delete Nest</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='breeding.php'">Cancel</button>
        </div>
    </form>
</div>
</body>
</html>