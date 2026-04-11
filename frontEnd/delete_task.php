<?php
require_once '../includes/session.php';
require_once '../backEnd/config/db.php';

/* 🔐 Restrict to supervisor */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: ../login.php");
    exit();
}

$message = "";

/* 🗑️ Handle delete */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['task_id']) || empty($_POST['task_id'])) {
        $message = "❌ No task selected.";
    } else {

        $task_id = intval($_POST['task_id']);

        $stmt = $conn->prepare("DELETE FROM tasks WHERE task_id = ?");
        $stmt->bind_param("i", $task_id);

        if ($stmt->execute()) {
            $message = "✅ Task deleted successfully!";
        } else {
            $message = "❌ Error deleting task.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Task</title>
    <style>
        body {
            font-family: Arial;
            background: #f4f0e6;
        }
        .form-card {
            width: 350px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; }
        label { display:block; margin-top:10px; }
        input {
            width:100%;
            padding:8px;
            margin-top:5px;
        }
        button {
            margin-top:15px;
            width:100%;
            padding:10px;
            background:#dc3545;
            color:white;
            border:none;
            cursor:pointer;
        }
        .message {
            text-align:center;
            margin-top:10px;
            font-weight:bold;
        }
    </style>
</head>

<body>

<div class="form-card">
    <h2>🗑️ Delete Task</h2>

    <!-- ✅ Show message -->
    <?php if ($message != ""): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>

    <!-- 📝 Form -->
    <form method="POST">
        <label>Task ID</label>
        <input type="number" name="task_id" required>

        <button type="submit">Delete</button>
    </form>

</div>

</body>
</html>