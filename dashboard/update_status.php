<?php
// update_status.php - Call this to update all users' status to offline if inactive
require_once __DIR__ . '/../db.php';
session_start();

$timeout_seconds = 60; // 1 minute
$conn->query("UPDATE users SET status = 'offline' WHERE last_seen < (NOW() - INTERVAL $timeout_seconds SECOND) AND status != 'offline'");

if (isset($_GET['action']) && $_GET['action'] === 'read_messages' && isset($_GET['user_id']) && isset($_SESSION['user_id'])) {
    $other_user_id = (int)$_GET['user_id'];
    $current_user_id = (int)$_SESSION['user_id'];
    // Mark all messages from other_user_id to current_user_id as read
    $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
    $stmt->bind_param('ii', $other_user_id, $current_user_id);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true]);
    exit;
}
?>