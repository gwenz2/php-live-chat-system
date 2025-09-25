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
            $sql = "INSERT INTO users (username, display_name, password_hash, avatar_url, created_at)
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $display_name, $password_hash, $avatar_url);
            // After successful user insertion:
            if ($stmt->execute()) {
                $new_user_id = $stmt->insert_id;
                $stmt->close();

                // Auto-friend with user 1 (optional)
                $friend_sql = "INSERT INTO friends (user_id, friend_id, status, created_at) VALUES (?, 13, 'accepted', NOW())";
                $friend_stmt = $conn->prepare($friend_sql);
                $friend_stmt->bind_param("i", $new_user_id);
                $friend_stmt->execute();
                $friend_stmt->close();


                // Set session to log user in immediately
                $_SESSION['user_id'] = $new_user_id;
                $_SESSION['username'] = $username;
                $_SESSION['display_name'] = $display_name;

                // Redirect directly to dashboard
                header('Location: dashboard/index.php');
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
