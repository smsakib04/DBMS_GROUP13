<?php
session_start();
require_once '../backEnd/config/db.php';

// Mapping from role name (as in dropdown) to dashboard file and display name
$roleMap = [
    'collecting'   => ['file' => 'collectingOfficer.php', 'label' => 'Collecting Officer'],
    'supervisor'   => ['file' => 'supervisor.php',       'label' => 'Supervisor'],
    'caretaker'    => ['file' => 'caretaker.php',        'label' => 'Caretaker'],
    'feeder'       => ['file' => 'feeder.php',           'label' => 'Feeder'],
    'veterenian'   => ['file' => 'veterenian.php',       'label' => 'Veterinarian'],
    'breeding'     => ['file' => 'breeding.php',         'label' => 'Breeding Officer'],
    'iot'          => ['file' => 'iot_dashboard.php',    'label' => 'IoT Device']
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_role = $_POST['role'] ?? '';
    if (isset($roleMap[$selected_role])) {
        // Set session variables (simulate login)
        $_SESSION['staff_id'] = 1;   // dummy ID – you can fetch a real staff ID later if needed
        $_SESSION['full_name'] = $roleMap[$selected_role]['label'];
        $_SESSION['role'] = $selected_role;
        
        // Redirect to the corresponding dashboard
        header("Location: " . $roleMap[$selected_role]['file']);
        exit();
    } else {
        $error = "Please select a valid role.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Tortoise Conservation Center</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e8f3ef 0%, #d4e8df 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: white;
            border-radius: 32px;
            padding: 2.5rem;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 35px -12px rgba(0, 40, 20, 0.2);
            border: 1px solid #d4ede1;
        }

        .login-card h2 {
            color: #1c5d44;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .login-card p {
            color: #5a8874;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }

        label {
            display: block;
            font-weight: 600;
            color: #2b6e53;
            margin-bottom: 0.5rem;
        }

        select {
            width: 100%;
            padding: 0.8rem;
            border-radius: 16px;
            border: 1.5px solid #cae5d9;
            background: #fefefe;
            font-size: 1rem;
            margin-bottom: 1.5rem;
        }

        button {
            background: #1f7356;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        button:hover {
            background: #155e46;
            transform: translateY(-2px);
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.8rem;
            border-radius: 16px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #5a8874;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="login-card">
    <h2><i class="fas fa-sign-in-alt"></i> System Login</h2>
    <p>Select your role to access the dashboard.</p>

    <?php if ($error): ?>
        <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <label for="role">Choose role:</label>
        <select name="role" id="role" required>
            <option value="">-- Select role --</option>
            <option value="collecting">Collecting Officer</option>
            <option value="supervisor">Supervisor</option>
            <option value="caretaker">Caretaker</option>
            <option value="feeder">Feeder</option>
            <option value="veterenian">Veterinarian</option>
            <option value="breeding">Breeding Officer</option>
            <option value="iot">IoT Device</option>
        </select>
        <button type="submit"><i class="fas fa-arrow-right"></i> Login</button>
    </form>
    <a href="homepage_updated.php" class="back-link"><i class="fas fa-home"></i> Back to Homepage</a>
</div>
</body>
</html>