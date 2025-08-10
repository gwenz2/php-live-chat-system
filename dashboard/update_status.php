<?php
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only allow authenticated users
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Handle different types of requests
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'heartbeat':
            // User is actively using the app
            $stmt = $conn->prepare("UPDATE users SET last_seen = NOW(), status = 'online' WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->close();
            break;
            
        case 'away':
            // User has been inactive for a while
            $stmt = $conn->prepare("UPDATE users SET status = 'away', last_seen = NOW() WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->close();
            break;
            
        case 'offline':
            // User is closing/leaving the app
            $stmt = $conn->prepare("UPDATE users SET status = 'offline', last_seen = NOW() WHERE id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $stmt->close();
            break;
            
        case 'get_status':
            // Get current status of specific users
            $user_ids = $input['user_ids'] ?? [];
            if (!empty($user_ids)) {
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                $stmt = $conn->prepare("SELECT id, status, last_seen, TIMESTAMPDIFF(MINUTE, last_seen, NOW()) as minutes_offline FROM users WHERE id IN ($placeholders)");
                $stmt->bind_param(str_repeat('i', count($user_ids)), ...$user_ids);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $statuses = [];
                while ($row = $result->fetch_assoc()) {
                    $statuses[$row['id']] = [
                        'status' => $row['status'],
                        'last_seen' => $row['last_seen'],
                        'minutes_offline' => $row['minutes_offline']
                    ];
                }
                $stmt->close();
                
                echo json_encode(['success' => true, 'statuses' => $statuses]);
                exit;
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit;
    }
    
    // Clean up offline users (set to offline if last_seen > 2 minutes ago)
    $stmt = $conn->prepare("UPDATE users SET status = 'offline' WHERE last_seen < DATE_SUB(NOW(), INTERVAL 2 MINUTE) AND status != 'offline'");
    $stmt->execute();
    $stmt->close();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}