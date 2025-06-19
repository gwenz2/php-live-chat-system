<?php
// update_status.php - Call this to update all users' status to offline if inactive
require_once __DIR__ . '/../db.php';
$timeout_seconds = 60; // 1 minute
$conn->query("UPDATE users SET status = 'offline' WHERE last_seen < (NOW() - INTERVAL $timeout_seconds SECOND) AND status != 'offline'");
?>