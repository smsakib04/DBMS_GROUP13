
Copy

<?php
require_once '../backEnd/includes/session.php';
//requireLogin();
require_once '../backEnd/config/db.php';
 
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
 
if (!$id) {
    header("Location: supervisor.php?tab=inventory");
    exit();
}
 
$stmt = $conn->prepare("DELETE FROM inventory WHERE inventory_id = ?");
$stmt->bind_param("i", $id);
 
if ($stmt->execute()) {
    $stmt->close();
    header("Location: supervisor.php?msg=inv_deleted&tab=inventory");
    exit();
} else {
    $err = $stmt->error;
    $stmt->close();
    header("Location: supervisor.php?msg=inv_error&detail=" . urlencode($err) . "&tab=inventory");
    exit();
}
 