<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $alert = '';

    if (empty($username) || empty($password)) {
        $alert = 'Please fill in all fields.';
    } else {
        $sql = "SELECT id, username, display_name, password_hash FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password_hash'])) {
                // Login success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['display_name'] = $user['display_name'];
                header('Location: dashboard/index.php');
                exit;
            } else {
                $alert = 'Incorrect password.';
                header('Location: index.php?msg=' . urlencode($alert));
            }
        } else {
            $alert = 'Username not found.';
            header('Location: index.php?msg=' . urlencode($alert));
        }
        $stmt->close();
        $conn->close();
    }
}
?>