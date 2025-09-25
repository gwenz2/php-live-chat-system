<?php
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?msg=' . urlencode('Please log in.'));
    exit;
}

$current_user_id = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add friend request
    if (isset($_POST['friend_id'])) {
        $friend_id = intval($_POST['friend_id']);
        if ($friend_id && $friend_id != $current_user_id) {
            $stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending') ON DUPLICATE KEY UPDATE status='pending'");
            $stmt->bind_param('ii', $current_user_id, $friend_id);
            $stmt->execute();
            $stmt->close();
            $msg = 'Buddy request sent!';
        } else {
            $msg = 'Invalid user.';
        }
    }
    // Respond to friend request
    elseif (isset($_POST['request_id'], $_POST['action'])) {
        $request_id = intval($_POST['request_id']);
        $action = $_POST['action'];
        if ($request_id && ($action === 'accept' || $action === 'decline')) {
            if ($action === 'accept') {
                $stmt = $conn->prepare("UPDATE friends SET status='accepted' WHERE id=? AND friend_id=? AND status='pending'");
                $stmt->bind_param('ii', $request_id, $current_user_id);
                $stmt->execute();
                $stmt->close();
                $msg = 'Buddy request accepted!';
            } else {
                $stmt = $conn->prepare("DELETE FROM friends WHERE id=? AND friend_id=? AND status='pending'");
                $stmt->bind_param('ii', $request_id, $current_user_id);
                $stmt->execute();
                $stmt->close();
                $msg = 'Buddy request declined.';
            }
        } else {
            $msg = 'Invalid request.';
        }
    }
}

header('Location: index.php?msg=' . urlencode($msg));
exit;
?>
