<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'tortoise_conservation';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper encoding
$conn->set_charset("utf8mb4");
?>