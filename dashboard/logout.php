<?php
session_start();
if (isset($_SESSION['user_id'])) {
    require_once '../db.php';
    $stmt = $conn->prepare('UPDATE users SET status = "offline" WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}
session_unset();
session_destroy();
header('Location: ../index.php?msg=' . urlencode('You have been logged out.'));
exit;
