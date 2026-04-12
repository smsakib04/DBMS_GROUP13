<?php
require_once '../config/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pair_id = intval($_POST['pair_id']);
    if ($pair_id <= 0) {
        $error = "Invalid pair ID.";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        try {
            // Delete related nests first (if ON DELETE CASCADE not set)
            $deleteNests = $conn->prepare("DELETE FROM nests WHERE pair_id = ?");
            $deleteNests->bind_param("i", $pair_id);
            $deleteNests->execute();
            $deleteNests->close();

            // Delete the breeding pair
            $stmt = $conn->prepare("DELETE FROM breeding_pairs WHERE pair_id = ?");
            $stmt->bind_param("i", $pair_id);
            $stmt->execute();
            if ($stmt->affected_rows === 0) {
                throw new Exception("No breeding pair found with ID: $pair_id");
            }
            $stmt->close();

            $conn->commit();
            // Redirect with success message
            header("Location: ../frontEnd/breeding.php?msg=pair_deleted");
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
    <title>Delete Result</title>
    <style>
        body{font-family:monospace; padding:2rem; background:#f8f9fa;}
        .error{color:#dc3545; background:#f8d7da; padding:1rem; border-radius:8px;}
    </style>
</head>
<body>
<?php if ($error): ?>
    <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
    <a href="../frontEnd/delete_breeding_pair.html">← Go back</a>
<?php else: ?>
    <p>✅ Redirecting... If not, <a href="../frontEnd/breeding.php">click here</a>.</p>
    <script>window.location.href = "../frontEnd/breeding.php?msg=pair_deleted";</script>
<?php endif; ?>
</body>
</html>