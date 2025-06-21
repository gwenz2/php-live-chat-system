<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="icon" href="../iconMO.svg" type="image/svg+xml">
    <title>Gwez - Live-Chat</title>
</head>
<style>
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: linear-gradient(135deg, #e3f0ff 0%, #f9f9f9 100%);
        }
</style>
<body class="p-3">
    <?php 
    include_once 'navbar.php';
    require_once '../db.php';
    require_once 'update_status.php';

    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php?msg=' . urlencode('Please log in to access the chatroom.'));
        exit;
    }

    // Get the user to chat with
    $other_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
    $current_user_id = $_SESSION['user_id'];

    // Fetch other user's info
    $other_user = null;
    if ($other_user_id) {
        $stmt = $conn->prepare('SELECT display_name, username FROM users WHERE id = ?');
        $stmt->bind_param('i', $other_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $other_user = $result->fetch_assoc();
        $stmt->close();
    }

    // Fetch messages between the two users
    $messages = [];
    if ($other_user_id) {
        $stmt = $conn->prepare('SELECT * FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY sent_at ASC');
        $stmt->bind_param('iiii', $current_user_id, $other_user_id, $other_user_id, $current_user_id);
        $stmt->execute();
        $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Mark messages as read when the chat is opened
    if ($other_user_id) {
        $stmt = $conn->prepare('UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0');
        $stmt->bind_param('ii', $other_user_id, $current_user_id);
        $stmt->execute();
        $stmt->close();
    }

    // Handle message sending
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $other_user_id && isset($_POST['message']) && trim($_POST['message']) !== '') {
        $msg_text = trim($_POST['message']);
        $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $current_user_id, $other_user_id, $msg_text);
        $stmt->execute();
        $stmt->close();
        // Redirect to avoid resubmission
        header('Location: chatroom.php?user_id=' . $other_user_id);
        exit;
    }

    // Fetch avatars for current user and other user
    $current_avatar = '../assets/user_male_80px.png';
    $other_avatar = '../assets/user_male_96px.png';
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare('SELECT avatar_url FROM users WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($avatar_url);
        if ($stmt->fetch() && $avatar_url) {
            $current_avatar = htmlspecialchars($avatar_url);
        }
        $stmt->close();
    }
    if ($other_user_id) {
        $stmt = $conn->prepare('SELECT avatar_url FROM users WHERE id = ?');
        $stmt->bind_param('i', $other_user_id);
        $stmt->execute();
        $stmt->bind_result($avatar_url);
        if ($stmt->fetch() && $avatar_url) {
            $other_avatar = htmlspecialchars($avatar_url);
        }
        $stmt->close();
    }
    ?>
    <div class="container mt-3" style="max-width: 90vw;">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 w-100" style="max-width: 90vw;">
                <div class="card shadow-sm rounded-4" style="height: 86vh; display: flex; flex-direction: column;">
                    <!-- Chat Header -->
                    <div class="card-header bg-primary text-white d-flex align-items-center gap-3 rounded-top-4">
                        <a href="index.php" class="btn btn-light btn-sm me-2">&larr;</a>
                        <img src="<?php echo $other_avatar; ?>" class="rounded-circle border border-light" width="48" height="48" alt="User">
                        <div>
                            <h6 class="mb-0"><?php echo $other_user ? htmlspecialchars($other_user['display_name']) : 'Select a user'; ?></h6>
                            <small class="text-light"><?php echo $other_user ? '@' . htmlspecialchars($other_user['username']) : ''; ?></small>
                        </div>
                    </div>
                    <!-- Chat Messages -->
                    <div class="flex-grow-1 overflow-auto p-3" style="background: #f8fafc;">
                        <?php if ($other_user_id && $messages): ?>
                            <?php foreach ($messages as $msg): ?>
                                <?php if ($msg['sender_id'] == $current_user_id): ?>
                                    <!-- Sent message -->
                                    <div class="d-flex flex-row-reverse mb-3">
                                        <img src="<?php echo $current_avatar; ?>" class="rounded-circle ms-2" width="36" height="36" alt="Me">
                                        <div>
                                            <div class="bg-primary text-white rounded-3 p-2 px-3 mb-1<?php if ($msg['is_read']) echo ' border border-success'; ?>">
                                                <?php echo htmlspecialchars($msg['message_text']); ?>
                                            </div>
                                            <div class="d-flex justify-content-end align-items-center gap-2">
                                                <small class="text-muted d-block text-end mb-0"><?php echo date('H:i', strtotime($msg['sent_at'])); ?></small>
                                                <?php if ($msg['is_read']): ?>
                                                    <span class="text-success small ms-1" title="Read">Read</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- Received message -->
                                    <div class="d-flex mb-3">
                                        <img src="<?php echo $other_avatar; ?>" class="rounded-circle me-2" width="36" height="36" alt="User">
                                        <div>
                                            <div class="bg-white border rounded-3 p-2 px-3 mb-1"><?php echo htmlspecialchars($msg['message_text']); ?></div>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($msg['sent_at'])); ?></small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php elseif ($other_user_id): ?>
                            <div class="text-center text-muted mt-5">No messages yet. Say hello!</div>
                        <?php else: ?>
                            <div class="text-center text-muted mt-5">Select a user to start chatting.</div>
                        <?php endif; ?>
                    </div>
                    <!-- Chat Input -->
                    <form class="card-footer bg-white border-0 d-flex align-items-center gap-2 rounded-bottom-4" method="POST" action="?user_id=<?php echo $other_user_id; ?>">
                        <input type="text" class="form-control rounded-pill" placeholder="Type a message..." name="message" required <?php if (!$other_user_id) echo 'disabled'; ?>>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" <?php if (!$other_user_id) echo 'disabled'; ?>>Send</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    // AJAX polling for new messages
    const chatBox = document.querySelector('.flex-grow-1.overflow-auto.p-3');
    const otherUserId = <?php echo json_encode($other_user_id); ?>;
    const currentAvatar = <?php echo json_encode($current_avatar); ?>;
    const otherAvatar = <?php echo json_encode($other_avatar); ?>;
    let lastMessages = '';

    function renderMessages(messages) {
        let html = '';
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        messages.forEach(msg => {
            if (msg.sender_id == currentUserId) {
                html += `<div class="d-flex flex-row-reverse mb-3">
                    <img src="${currentAvatar}" class="rounded-circle ms-2" width="36" height="36" alt="Me">
                    <div>
                        <div class="bg-primary text-white rounded-3 p-2 px-3 mb-1${msg.is_read == 1 ? ' border border-success' : ''}">${msg.message_text}</div>
                        <div class="d-flex justify-content-end align-items-center gap-2">
                            <small class="text-muted d-block text-end mb-0">${msg.sent_at.substring(11,16)}</small>
                            ${msg.is_read == 1 ? '<span class="text-success small ms-1" title="Read">Read</span>' : ''}
                        </div>
                    </div>
                </div>`;
            } else {
                html += `<div class="d-flex mb-3">
                    <img src="${otherAvatar}" class="rounded-circle me-2" width="36" height="36" alt="User">
                    <div>
                        <div class="bg-white border rounded-3 p-2 px-3 mb-1">${msg.message_text}</div>
                        <small class="text-muted">${msg.sent_at.substring(11,16)}</small>
                    </div>
                </div>`;
            }
        });
        chatBox.innerHTML = html || '<div class="text-center text-muted mt-5">No messages yet. Say hello!</div>';
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function markMessagesAsRead() {
        fetch(`update_status.php?action=read_messages&user_id=${otherUserId}`, { method: 'POST' });
    }

    function fetchMessages() {
        fetch(`fetch_messages.php?user_id=${otherUserId}`)
            .then(res => res.json())
            .then(messages => {
                const msgString = JSON.stringify(messages);
                if (msgString !== lastMessages) {
                    renderMessages(messages);
                    lastMessages = msgString;
                }
                // Mark messages as read if there are any from the other user
                if (messages.some(msg => msg.sender_id == otherUserId && msg.is_read == 0)) {
                    markMessagesAsRead();
                }
            });
    }

    setInterval(fetchMessages, 2000); // Poll every 2 seconds
    fetchMessages(); // Initial load
    </script>
</body>

</html>