<?php
session_start();

// Redirect to login if user not logged in
function requireLogin() {
    if (!isset($_SESSION['staff_id'])) {
        header("Location: login.php");
        exit();
    }
}

// Optional: check role (if needed)
function requireRole($allowed_roles) {
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        die("Access denied.");
    }
}
?>