<?php
require_once 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = trim($_POST['username']);
    $display_name = trim($_POST['display_name']);
    $password = $_POST['password'];
    $avatar_url = isset($_POST['avatar_url']) && trim($_POST['avatar_url']) !== '' ? trim($_POST['avatar_url']) : '../assets/user_male_80px.png';

    // Basic validation
    if (empty($username) || empty($display_name) || empty($password)) {
        header('Location: signupForm.php?msg=' . urlencode('All fields are required.'));
        exit;
    } else {
        // Check if username already exists
        $check_sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            header('Location: signupForm.php?msg=' . urlencode('Username already taken.'));
            exit;
        } else {
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            // Insert new user
            $sql = "INSERT INTO users (username, display_name, password_hash, avatar_url, status, last_seen, created_at)
                    VALUES (?, ?, ?, ?, 'offline', NOW(), NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $display_name, $password_hash, $avatar_url);
            if ($stmt->execute()) {
                $stmt->close();
                header('Location: index.php?msg=' . urlencode('Registration successful! You can now log in.'));
                exit;
            } else {
                $stmt->close();
                header('Location: signupForm.php?msg=' . urlencode('Error: ' . htmlspecialchars($stmt->error)));
                exit;
            }
        }
    }
} else {
    header('Location: signupForm.php?msg=' . urlencode('Invalid request.'));
    exit;
}
?>
