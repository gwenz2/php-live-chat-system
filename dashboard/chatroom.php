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

// Update current user's status and last_seen
$stmt = $conn->prepare("UPDATE users SET last_seen = NOW(), status = 'online' WHERE id = ?");
$stmt->bind_param('i', $current_user_id);
$stmt->execute();
$stmt->close();

// Fetch other user's info with status
$other_user = null;
if ($other_user_id) {
    $stmt = $conn->prepare('SELECT display_name, username, avatar_url, status, last_seen, TIMESTAMPDIFF(MINUTE, last_seen, NOW()) as minutes_offline FROM users WHERE id = ?');
    $stmt->bind_param('i', $other_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $other_user = $result->fetch_assoc();
    $stmt->close();
}

// Get current user's avatar
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

// Format last seen time for other user
$last_seen_text = '';
if ($other_user) {
    $is_online = ($other_user['status'] === 'online');
    $is_away = ($other_user['status'] === 'away');
    $minutes_offline = $other_user['minutes_offline'];
    
    if ($is_online) {
        $last_seen_text = 'Online';
    } else if ($is_away) {
        $last_seen_text = 'Away';
    } else {
        if ($minutes_offline < 60) {
            $last_seen_text = 'Last seen ' . $minutes_offline . 'm ago';
        } else if ($minutes_offline < 1440) {
            $hours = floor($minutes_offline / 60);
            $last_seen_text = 'Last seen ' . $hours . 'h ago';
        } else {
            $days = floor($minutes_offline / 1440);
            $last_seen_text = 'Last seen ' . $days . 'd ago';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" href="../iconMO.svg" type="image/svg+xml">
    <title>OneTalk - Chatroom</title>
    <style>
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: linear-gradient(135deg, #e3f0ff 0%, #f9f9f9 100%);
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            border: 2px solid white;
            box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
            position: absolute;
            bottom: 2px;
            right: 2px;
        }
        
        .status-online {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }
        
        .status-offline {
            background-color: #6c757d;
        }
        
        .status-away {
            background-color: #ffc107;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
        
        .typing-indicator {
            display: none;
            color: #6c757d;
            font-style: italic;
            font-size: 0.9em;
            padding: 10px 15px;
            background: rgba(108, 117, 125, 0.1);
            border-radius: 15px;
            margin: 10px 0;
        }
        
        .typing-dots {
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }
        
        .typing-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: #6c757d;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typing {
            0%, 80%, 100% { 
                transform: scale(0.8);
                opacity: 0.5;
            }
            40% { 
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .message-bubble {
            max-width: 70%;
            word-wrap: break-word;
            position: relative;
        }
        
        .message-sent {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white;
            border-radius: 20px 20px 5px 20px !important;
        }
        
        .message-received {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 20px 20px 20px 5px !important;
        }
        
        .message-time {
            font-size: 0.75em;
            opacity: 0.7;
            margin-top: 5px;
        }
        
        .message-status {
            font-size: 0.7em;
            color: #28a745;
        }
        
        .chat-input-container {
            background: white;
            border-radius: 25px;
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chat-input {
            border: none;
            outline: none;
            flex: 1;
            padding: 10px 15px;
            background: transparent;
            border-radius: 20px;
            resize: none;
            max-height: 100px;
            min-height: 40px;
        }
        
        .send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            margin-left: 5px;
        }
        
        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .send-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .emoji-btn {
            background: none;
            border: none;
            font-size: 1.2em;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
            display: none; /* Hidden */
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 25px 25px 0 0;
            padding: 15px 20px;
        }
        
        .online-indicator {
            font-size: 0.8em;
            color: rgba(255,255,255,0.9);
        }
        
        .scroll-to-bottom {
            position: absolute;
            bottom: 80px;
            right: 20px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: all 0.2s;
        }
        
        .scroll-to-bottom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body class="p-3">
    <?php include_once 'navbar.php'; ?>
    <div class="container mt-3" style="max-width: 90vw;">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 w-100" style="max-width: 90vw;">
                <div class="card shadow-lg border-0 rounded-4" style="height: 86vh; display: flex; flex-direction: column;">
                    <!-- Enhanced Chat Header -->
                    <div class="chat-header text-white d-flex align-items-center gap-3">
                        <a href="index.php" class="btn btn-light btn-sm me-2 rounded-pill">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div class="position-relative">
                            <img src="<?php echo $other_avatar; ?>" class="rounded-circle border border-light" width="48" height="48" alt="User">
                            <?php if ($other_user): ?>
                                <span class="status-indicator status-<?php echo $other_user['status']; ?>" title="<?php echo ucfirst($other_user['status']); ?>"></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?php echo $other_user ? htmlspecialchars($other_user['display_name']) : 'Select a user'; ?></h6>
                            <small class="online-indicator" id="userStatus">
                                <?php echo $other_user ? $last_seen_text : ''; ?>
                            </small>
                        </div>

                    </div>
                    
                    <!-- Chat Messages -->
                    <div class="flex-grow-1 overflow-auto p-3 position-relative" style="background: #f8fafc;" id="chat-box">
                        <div id="loadingMessage" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10;text-align:center;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="mt-2 fw-semibold">Loading messages...</div>
                        </div>
                        
                        <!-- Typing Indicator -->
                        <div class="typing-indicator" id="typingIndicator">
                            <img src="<?php echo $other_avatar; ?>" class="rounded-circle me-2" width="24" height="24" alt="User">
                            <span id="typingText"></span> is typing
                            <div class="typing-dots ms-2">
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                                <div class="typing-dot"></div>
                            </div>
                        </div>
                        
                        <!-- Scroll to bottom button -->
                        <button class="scroll-to-bottom" id="scrollToBottom">
                            <i class="bi bi-arrow-down"></i>
                        </button>
                    </div>
                    
                    <!-- Enhanced Chat Input -->
                    <div class="card-footer bg-white border-0 p-3 rounded-bottom-4">
                        <div class="chat-input-container">
                            <textarea 
                                class="chat-input" 
                                placeholder="Type a message..." 
                                id="message" 
                                rows="1"
                                <?php if (!$other_user_id) echo 'disabled'; ?>
                            ></textarea>
                            <button class="send-btn" id="send" <?php if (!$other_user_id) echo 'disabled'; ?> title="Send message">
                                <i class="bi bi-send-fill"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script type="module">
        const currentUser = {
            id: <?php echo json_encode($current_user_id); ?>,
            name: <?php echo json_encode($_SESSION['display_name'] ?? $_SESSION['username']); ?>,
            avatar: <?php echo json_encode($current_avatar); ?>
        };
        const otherUserId = <?php echo json_encode($other_user_id); ?>;
        const otherUserAvatar = <?php echo json_encode($other_avatar); ?>;
        const otherUserName = <?php echo json_encode($other_user ? $other_user['display_name'] : ''); ?>;

        import { initializeApp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import { getDatabase, ref, push, onChildAdded, get, update, onValue, set, serverTimestamp, onDisconnect } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js";

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

        // Chat and presence references
        const chatRef = ref(db, `chats/${Math.min(currentUser.id, otherUserId)}_${Math.max(currentUser.id, otherUserId)}`);
        const typingRef = ref(db, `typing/${Math.min(currentUser.id, otherUserId)}_${Math.max(currentUser.id, otherUserId)}/${currentUser.id}`);
        const otherTypingRef = ref(db, `typing/${Math.min(currentUser.id, otherUserId)}_${Math.max(currentUser.id, otherUserId)}/${otherUserId}`);
        const userStatusRef = ref(db, `users/${currentUser.id}/status`);
        const otherUserStatusRef = ref(db, `users/${otherUserId}/status`);

        // DOM elements
        const chatBox = document.getElementById("chat-box");
        const loadingDiv = document.getElementById('loadingMessage');
        const messageInput = document.getElementById("message");
        const sendBtn = document.getElementById("send");
        const typingIndicator = document.getElementById('typingIndicator');
        const scrollToBottomBtn = document.getElementById('scrollToBottom');
        const userStatusEl = document.getElementById('userStatus');

        let firstMessageLoaded = false;
        let typingTimeout;
        let isTyping = false;
        let isAtBottom = true;

        // Auto-expand textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Enhanced heartbeat system
        function updateHeartbeat() {
            set(userStatusRef, {
                online: true,
                lastSeen: serverTimestamp(),
                inChat: otherUserId
            });
            
            fetch('../update_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'heartbeat'})
            }).catch(console.error);
        }

        // Set up presence system
        updateHeartbeat();
        const heartbeatInterval = setInterval(updateHeartbeat, 30000);

        // Handle disconnect
        onDisconnect(userStatusRef).set({
            online: false,
            lastSeen: serverTimestamp(),
            inChat: null
        });

        // Monitor other user's status
        onValue(otherUserStatusRef, (snapshot) => {
            const status = snapshot.val();
            if (status) {
                const statusEl = document.querySelector('.status-indicator');
                if (statusEl) {
                    statusEl.className = `status-indicator status-${status.online ? 'online' : 'offline'}`;
                }
                
                if (userStatusEl) {
                    if (status.online) {
                        if (status.inChat == currentUser.id) {
                            userStatusEl.textContent = 'Online • In this chat';
                        } else {
                            userStatusEl.textContent = 'Online';
                        }
                    } else {
                        userStatusEl.textContent = 'Offline';
                    }
                }
            }
        });

        // Typing indicator functionality
        messageInput.addEventListener('input', () => {
            if (!isTyping) {
                isTyping = true;
                set(typingRef, {
                    typing: true,
                    name: currentUser.name,
                    timestamp: serverTimestamp()
                });
            }

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                isTyping = false;
                set(typingRef, null);
            }, 2000);
        });

        // Listen for other user typing
        onValue(otherTypingRef, (snapshot) => {
            const typingData = snapshot.val();
            if (typingData && typingData.typing) {
                document.getElementById('typingText').textContent = otherUserName;
                typingIndicator.style.display = 'flex';
                scrollToBottom();
            } else {
                typingIndicator.style.display = 'none';
            }
        });

        // Scroll handling
        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
            isAtBottom = true;
            scrollToBottomBtn.style.display = 'none';
        }

        chatBox.addEventListener('scroll', () => {
            const { scrollTop, scrollHeight, clientHeight } = chatBox;
            isAtBottom = scrollTop + clientHeight >= scrollHeight - 100;
            
            if (isAtBottom) {
                scrollToBottomBtn.style.display = 'none';
            } else {
                scrollToBottomBtn.style.display = 'flex';
            }
        });

        scrollToBottomBtn.addEventListener('click', scrollToBottom);

        // Load existing messages
        if (loadingDiv) loadingDiv.style.display = 'block';

        get(chatRef).then(snapshot => {
            if (!snapshot.exists()) {
                if (loadingDiv) loadingDiv.style.display = 'none';
                const noMsg = document.createElement("div");
                noMsg.className = "text-center text-muted mt-5";
                noMsg.innerHTML = '<i class="bi bi-chat-dots"></i><br><em>No messages yet. Start the conversation!</em>';
                chatBox.appendChild(noMsg);
            } else {
                if (loadingDiv) loadingDiv.style.display = 'none';
            }
        });

        // Listen for new messages with real-time read status updates
        onChildAdded(chatRef, (snapshot) => {
            const msg = snapshot.val();
            if (!firstMessageLoaded && loadingDiv) {
                loadingDiv.style.display = 'none';
                firstMessageLoaded = true;
                // Remove "no messages" placeholder
                const noMsgEl = chatBox.querySelector('.text-center.text-muted');
                if (noMsgEl) noMsgEl.remove();
            }

            const div = document.createElement("div");
            const isSent = msg.sender_id == currentUser.id;
            
            div.className = isSent ? "d-flex flex-row-reverse mb-3 align-items-end" : "d-flex mb-3 align-items-end";
            div.setAttribute('data-message-id', snapshot.key);
            
            const timeStr = new Date(msg.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            
            // Enhanced read status logic
            let readStatus = '';
            if (isSent) {
                if (msg.is_read && msg.read_at) {
                    readStatus = '<i class="bi bi-check2-all text-success ms-1" title="Read"></i>';
                } else {
                    readStatus = '<i class="bi bi-check2 text-muted ms-1" title="Sent"></i>';
                }
            }

            div.innerHTML = `
                <img src="${msg.sender_avatar}" class="rounded-circle ${isSent ? 'ms-2' : 'me-2'}" width="32" height="32" alt="User">
                <div class="message-bubble">
                    <div class="${isSent ? 'message-sent' : 'message-received'} p-3 mb-1">
                        ${msg.message}
                    </div>
                    <div class="message-time ${isSent ? 'text-end' : ''}">
                        <small class="text-muted">${timeStr}</small>
                        <span class="read-status">${readStatus}</span>
                    </div>
                </div>
            `;
            
            chatBox.appendChild(div);
            
            if (isAtBottom) {
                setTimeout(scrollToBottom, 100);
            }
        });

        // Listen for message updates (like read status changes)
        onValue(chatRef, (snapshot) => {
            if (snapshot.exists()) {
                snapshot.forEach((childSnapshot) => {
                    const messageId = childSnapshot.key;
                    const msg = childSnapshot.val();
                    const messageDiv = document.querySelector(`[data-message-id="${messageId}"]`);
                    
                    if (messageDiv && msg.sender_id == currentUser.id) {
                        const readStatusSpan = messageDiv.querySelector('.read-status');
                        if (readStatusSpan) {
                            if (msg.is_read && msg.read_at) {
                                readStatusSpan.innerHTML = '<i class="bi bi-check2-all text-success ms-1" title="Read"></i>';
                            } else {
                                readStatusSpan.innerHTML = '<i class="bi bi-check2 text-muted ms-1" title="Sent"></i>';
                            }
                        }
                    }
                });
            }
        });

        // Mark messages as read when user is actively viewing
        function markMessagesAsRead() {
            get(chatRef).then(snapshot => {
                if (snapshot.exists()) {
                    const updates = {};
                    snapshot.forEach(childSnap => {
                        const msg = childSnap.val();
                        if (msg.sender_id == otherUserId && !msg.is_read) {
                            updates[childSnap.key + "/is_read"] = true;
                            updates[childSnap.key + "/read_at"] = Date.now();
                        }
                    });
                    if (Object.keys(updates).length > 0) {
                        update(chatRef, updates);
                    }
                }
            });
        }
        
        // Mark as read when chat loads and when user is active
        if (otherUserId) {
            markMessagesAsRead();
            
            // Mark as read when user scrolls or interacts
            chatBox.addEventListener('scroll', markMessagesAsRead);
            messageInput.addEventListener('focus', markMessagesAsRead);
            
            // Periodically check for unread messages
            setInterval(markMessagesAsRead, 3000);
        }

        // Send message function
        function sendMessage() {
            const msg = messageInput.value.trim();
            if (msg) {
                // Clear typing indicator
                if (isTyping) {
                    isTyping = false;
                    set(typingRef, null);
                }

                push(chatRef, {
                    sender_id: currentUser.id,
                    sender_name: currentUser.name,
                    sender_avatar: currentUser.avatar,
                    message: msg,
                    timestamp: Date.now(),
                    is_read: false
                });
                
                messageInput.value = "";
                messageInput.style.height = 'auto';
                messageInput.focus();
            }
        }

        // Event listeners
        sendBtn.addEventListener("click", sendMessage);
        
        messageInput.addEventListener("keypress", (e) => {
            if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Focus input on load
        if (otherUserId && messageInput) {
            messageInput.focus();
        }
    </script>
</body>
</html>