<?php
// Start session first — no whitespace above this line!
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?msg=' . urlencode('Please log in to access the chatroom.'));
    exit;
}

$other_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$current_user_id = $_SESSION['user_id'];

// Fetch other user's info
$other_user = null;
if ($other_user_id) {
    $stmt = $conn->prepare('SELECT display_name, username, avatar_url FROM users WHERE id = ?');
    $stmt->bind_param('i', $other_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $other_user = $result->fetch_assoc();
    $stmt->close();
}

$current_avatar = '../assets/user_male_80px.png';
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
$other_avatar = $other_user && $other_user['avatar_url'] ? htmlspecialchars($other_user['avatar_url']) : '../assets/user_male_96px.png';
?>
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
    <?php include_once 'navbar.php'; ?>
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
                    <div class="flex-grow-1 overflow-auto p-3" style="background: #f8fafc;" id="chat-box"></div>
                    <!-- Chat Input -->
                    <div class="card-footer bg-white border-0 d-flex align-items-center gap-2 rounded-bottom-4">
                        <input type="text" class="form-control rounded-pill" placeholder="Type a message..." id="message" <?php if (!$other_user_id) echo 'disabled'; ?>>
                        <button class="btn btn-primary rounded-pill px-4" id="send" <?php if (!$other_user_id) echo 'disabled'; ?>>Send</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script type="module">
        // Pass PHP user info to JS
        const currentUser = {
            id: <?php echo json_encode($current_user_id); ?>,
            name: <?php echo json_encode($_SESSION['display_name'] ?? $_SESSION['username']); ?>,
            avatar: <?php echo json_encode($current_avatar); ?>
        };
        const otherUserId = <?php echo json_encode($other_user_id); ?>;
        const otherUserAvatar = <?php echo json_encode($other_avatar); ?>;

        // Firebase config
        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import { getDatabase, ref, push, onChildAdded, get, update } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js";

        const firebaseConfig = {
          apiKey: "AIzaSyDXixUNrcWNE1telIVZ_0L5KGQWLrElIEE",
          authDomain: "onetalk-116de.firebaseapp.com",
          databaseURL: "https://onetalk-116de-default-rtdb.asia-southeast1.firebasedatabase.app",
          projectId: "onetalk-116de",
          storageBucket: "onetalk-116de.firebasestorage.app",
          messagingSenderId: "175655177771",
          appId: "1:175655177771:web:a95b4032228b4209eca46e",
          measurementId: "G-B87YLF9WW4"
        };

        const app = initializeApp(firebaseConfig);
        const db = getDatabase(app);

        // Reference for messages between these two users
        const chatRef = ref(db, `chats/${Math.min(currentUser.id, otherUserId)}_${Math.max(currentUser.id, otherUserId)}`);

        // Mark all messages sent to current user as read
        function markMessagesAsRead() {
            get(chatRef).then(snapshot => {
                if (snapshot.exists()) {
                    const updates = {};
                    snapshot.forEach(childSnap => {
                        const msg = childSnap.val();
                        // If message is sent by other user and not yet read
                        if (msg.sender_id == otherUserId && !msg.is_read) {
                            updates[childSnap.key + "/is_read"] = true;
                        }
                    });
                    if (Object.keys(updates).length > 0) {
                        update(chatRef, updates);
                    }
                }
            });
        }
        if (otherUserId) markMessagesAsRead();

        // Send message
        document.getElementById("send").addEventListener("click", () => {
            const msg = document.getElementById("message").value.trim();
            if (msg) {
                push(chatRef, {
                    sender_id: currentUser.id,
                    sender_name: currentUser.name,
                    sender_avatar: currentUser.avatar,
                    message: msg,
                    timestamp: Date.now(),
                    is_read: false
                });
                document.getElementById("message").value = "";
            }
        });

        // Receive messages
        onChildAdded(chatRef, (snapshot) => {
            const msg = snapshot.val();
            const div = document.createElement("div");
            div.className = msg.sender_id == currentUser.id ? "d-flex flex-row-reverse mb-3" : "d-flex mb-3";
                div.innerHTML = `
                    <img src="${msg.sender_avatar}" class="rounded-circle ${msg.sender_id == currentUser.id ? 'ms-2' : 'me-2'}" width="36" height="36" alt="User">
                    <div>
                        <div class="${msg.sender_id == currentUser.id ? 'bg-primary text-white' : 'bg-white border'} rounded-3 p-2 px-3 mb-1">
                            ${msg.message}
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">${new Date(msg.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</small>
                            ${msg.sender_id == currentUser.id && msg.is_read ? '<span class="text-success small ms-2" title="Read">Read</span>' : ''}
                        </div>
                    </div>
                `;
            document.getElementById("chat-box").appendChild(div);
            document.getElementById("chat-box").scrollTop = document.getElementById("chat-box").scrollHeight;
        });
    </script>
</body>

</html>