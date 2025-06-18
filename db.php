<?php
// Database connection settings
$host = 'localhost';
$db   = 'php_live_chat';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
?>