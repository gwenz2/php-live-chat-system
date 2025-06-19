<?php
require_once '../db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$sql = "SELECT id, display_name, username, last_seen, status,
    (SELECT message_text FROM messages
        WHERE (sender_id = users.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = users.id)
        ORDER BY sent_at DESC LIMIT 1) AS last_message,
    (SELECT is_read FROM messages
        WHERE (sender_id = users.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = users.id)
        ORDER BY sent_at DESC LIMIT 1) AS last_is_read,
    (SELECT sender_id FROM messages
        WHERE (sender_id = users.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = users.id)
        ORDER BY sent_at DESC LIMIT 1) AS last_sender_id
    FROM users WHERE id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('iiiiiii', $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($user = $result->fetch_assoc()) {
    // Do NOT update status here, just return DB values
    $users[] = $user;
}
$stmt->close();
echo json_encode($users);
