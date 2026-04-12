<?php
require_once '../config/db.php';
session_start();

$error = '';

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
            header("Location: ../frontEnd/breeding.php?msg=nest_deleted");
            exit();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Result</title>
    <style>
        body{font-family:monospace; padding:2rem; background:#f8f9fa;}
        .error{color:#dc3545; background:#f8d7da; padding:1rem; border-radius:8px;}
    </style>
</head>
<body>
<?php if ($error): ?>
    <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
    <a href="../frontEnd/delete_nest.html">← Go back</a>
<?php else: ?>
    <p>✅ Redirecting... If not, <a href="../frontEnd/breeding.php">click here</a>.</p>
    <script>window.location.href = "../frontEnd/breeding.php?msg=nest_deleted";</script>
<?php endif; ?>
</body>
</html>