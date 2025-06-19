<?php
// fetch_messages.php - Returns messages between two users as JSON
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = (int)$_GET['user_id'];

$stmt = $conn->prepare('SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY sent_at ASC');
$stmt->bind_param('iiii', $current_user_id, $other_user_id, $other_user_id, $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
$messages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header('Content-Type: application/json');
echo json_encode($messages);
