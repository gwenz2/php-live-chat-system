
<?php
require_once '../db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT id, display_name, username, avatar_url FROM users WHERE id != ?";
$params = [$current_user_id];
$types = 'i';
if ($q !== '') {
    $sql .= " AND (display_name LIKE ? OR username LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $types .= 'ss';
}
$sql .= " ORDER BY display_name ASC LIMIT 30";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$users = [];
while ($user = $result->fetch_assoc()) {
    $users[] = $user;
}
$stmt->close();
echo json_encode($users);
