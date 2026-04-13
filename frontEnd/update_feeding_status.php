<?php
session_start();
require_once '../config/db.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = $_POST['schedule_id'];
    $done = $_POST['is_done'];
    
    // Validate inputs
    if (!empty($id) && isset($done)) {
        $stmt = $conn->prepare("UPDATE feeding_schedules SET is_done = ? WHERE schedule_id = ?");
        $stmt->bind_param("ii", $done, $id);
        
        if ($stmt->execute()) {
            $success_message = "Feeding status updated successfully!";
            $show_form = false;
        } else {
            $error_message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Please provide both Schedule ID and Status.";
    }
    $conn->close();
}

// Fetch existing feeding schedules to display
$conn = mysqli_connect($host, $username, $password, $database); // Add your connection variables
$schedules = [];
if ($conn) {
    $result = $conn->query("SELECT schedule_id, feeding_time, is_done FROM feeding_schedules ORDER BY feeding_time DESC");
    if ($result) {
        $schedules = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Feeding Status - Fish Feeder System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .form-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 30px;
            margin-bottom: 30px;
            transition: transform 0.3s;
        }
        
        .form-card:hover {
            transform: translateY(-5px);
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .schedules-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .schedules-table th,
        .schedules-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .schedules-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
        }
        
        .schedules-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .btn-quick-update {
            padding: 5px 10px;
            font-size: 12px;
            margin: 0 5px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .form-card {
                padding: 20px;
            }
            
            .schedules-table {
                font-size: 14px;
            }
            
            .schedules-table th,
            .schedules-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Update Form -->
        <div class="form-card">
            <h2>
                <span>✅</span>
                Update Feeding Completion Status
            </h2>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                
                <div class="form-group">
                    <label for="schedule_id">📅 Schedule ID</label>
                    <input type="number" 
                           id="schedule_id" 
                           name="schedule_id" 
                           required 
                           placeholder="Enter schedule ID">
                </div>
                
                <div class="form-group">
                    <label for="is_done">🔄 Status</label>
                    <select id="is_done" name="is_done" required>
                        <option value="">Select status...</option>
                        <option value="1">✅ Yes (Completed)</option>
                        <option value="0">⏳ No (Pending)</option>
                    </select>
                </div>
                
                <button type="submit">Update Status</button>
            </form>
        </div>
        
        <!-- Display Existing Schedules -->
        <?php if (!empty($schedules)): ?>
        <div class="form-card">
            <h2>
                <span>📊</span>
                Current Feeding Schedules
            </h2>
            
            <div style="overflow-x: auto;">
                <table class="schedules-table">
                    <thead>
                        <tr>
                            <th>Schedule ID</th>
                            <th>Feeding Time</th>
                            <th>Status</th>
                            <th>Quick Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($schedule['schedule_id']); ?></td>
                            <td><?php echo htmlspecialchars($schedule['feeding_time']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $schedule['is_done'] ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $schedule['is_done'] ? '✓ Completed' : '○ Pending'; ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="schedule_id" value="<?php echo $schedule['schedule_id']; ?>">
                                    <input type="hidden" name="is_done" value="<?php echo $schedule['is_done'] ? '0' : '1'; ?>">
                                    <button type="submit" class="btn-quick-update">
                                        <?php echo $schedule['is_done'] ? 'Mark Pending' : 'Mark Complete'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Navigation Links -->
        <div class="form-card" style="text-align: center;">
            <a href="../pages/feeder.php" style="color: #667eea; text-decoration: none; margin: 0 10px;">← Back to Feeder</a>
            <a href="../pages/dashboard.php" style="color: #667eea; text-decoration: none; margin: 0 10px;">📊 Dashboard</a>
        </div>
    </div>
</body>
</html>