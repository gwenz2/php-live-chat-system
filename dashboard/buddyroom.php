<?php
// Modified PHP section - Remove all PHP status logic
session_start();
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?msg=' . urlencode('Please log in to access the chatroom.'));
    exit;
}

$other_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$current_user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?msg=' . urlencode('Invalid request'));
    exit;
}

$other_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if ($other_user_id <= 0) {
    header('Location: index.php?msg=' . urlencode('Invalid user'));
    exit;
}

// REMOVE: All PHP status update code
// REMOVE: Status fetching from database
// Just get basic user info without status

// Fetch other user's basic info (NO status from database)
$other_user = null;
if ($other_user_id) {
    $stmt = $conn->prepare('SELECT display_name, username, avatar_url FROM users WHERE id = ?');
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

// REMOVE: All PHP status formatting logic - Firebase will handle this
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
            box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
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
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(40, 167, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }

        /* Rest of your CSS remains the same */
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

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes typing {

            0%,
            80%,
            100% {
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
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

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 25px 25px 0 0;
            padding: 15px 20px;
        }

        .online-indicator {
            font-size: 0.8em;
            color: rgba(255, 255, 255, 0.9);
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
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.2s;
        }

        .scroll-to-bottom:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
        }

            .chatroom-card {
        height: calc(95vh - 70px); /* Desktop: subtract navbar height */
    }

        @media (max-width: 991.98px) {
                    .chatroom-card {
            height: 83vh; /* Mobile: full height since navbar hidden */
        }

            .chat-header {
                padding: 1px 2px;
            }

            .chat-input-container {
                padding: 5px 10px;
            }

            .send-btn {
                width: 35px;
                height: 35px;
            }

            .chat-input {
                min-height: 30px;
            }
        }
    </style>
</head>

<body class="p-3">
    <div class="d-none d-md-block">
        <?php include_once 'navbar.php'; ?>
    </div>

    <div class="container mt-3" style="max-width: 90vw;">
        <div class="row justify-content-center">
            <div class="col-md-3 col-lg-3 w-100" style="max-width: 90vw;">
                <div class="card shadow-lg border-0 rounded-4 chatroom-card" style="display: flex; flex-direction: column;">

                    <!-- Enhanced Chat Header -->
                    <div class="chat-header text-white d-flex align-items-center gap-2 px-md-3 py-md-2 px-1 py-1">


                        <a href="index.php" class="btn btn-light btn-sm me-2 rounded-pill">
                            <i class="bi bi-arrow-left"></i>
                        </a>
                        <div class="position-relative">
                            <img src="<?php echo $other_avatar; ?>" class="rounded-circle border border-light" width="48" height="48" alt="User">
                            <!-- Status indicator will be updated by Firebase -->
                            <?php if ($other_user): ?>
                                <span class="status-indicator status-offline" id="statusIndicator" title="Checking status..."></span>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?php echo $other_user ? htmlspecialchars($other_user['display_name']) : 'Select a user'; ?></h6>
                            <small class="online-indicator" id="userStatus">
                                <?php echo $other_user ? 'Checking status...' : ''; ?>
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
                            <span id="typingText"></span>
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
                                <?php if (!$other_user_id) echo 'disabled'; ?>></textarea>
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

        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import {
            getDatabase,
            ref,
            push,
            onChildAdded,
            get,
            update,
            onValue,
            set,
            serverTimestamp,
            onDisconnect
        } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js";

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

        // Firebase references
        const chatRef = ref(db, `chats/${Math.min(currentUser.id, otherUserId)}_${Math.max(currentUser.id, otherUserId)}`);
        const typingRef = ref(db, `typing/${Math.min(currentUser.id, otherUserId)}_${Math.max(currentUser.id, otherUserId)}/${currentUser.id}`);
        const otherTypingRef = ref(db, `typing/${Math.min(currentUser.id, otherUserId)}_${Math.max(currentUser.id, otherUserId)}/${otherUserId}`);
        const userStatusRef = ref(db, `users/${currentUser.id}/status`);
        const otherUserStatusRef = ref(db, `users/${otherUserId}/status`);

        // Replace the entire JavaScript status management section with this fixed version:

        // DOM elements
        const chatBox = document.getElementById("chat-box");
        const loadingDiv = document.getElementById('loadingMessage');
        const messageInput = document.getElementById("message");
        const sendBtn = document.getElementById("send");
        const typingIndicator = document.getElementById('typingIndicator');
        const scrollToBottomBtn = document.getElementById('scrollToBottom');
        const userStatusEl = document.getElementById('userStatus');
        const statusIndicator = document.getElementById('statusIndicator');

        let firstMessageLoaded = false;
        let typingTimeout;
        let isTyping = false;
        let isAtBottom = true;
        let lastActivity = Date.now();
        let isUserActive = true;
        let statusInterval;
        let activityInterval;
        let isPageVisible = !document.hidden;

        // Activity tracking
        function trackUserActivity() {
            lastActivity = Date.now();
            isUserActive = true;
        }

        // Add activity listeners
        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
            document.addEventListener(event, trackUserActivity, {
                passive: true
            });
        });

        // Determine user status based on activity and page visibility
        function getUserStatus() {
            const now = Date.now();
            const inactiveTime = now - lastActivity;

            // If inactive for more than 5 minutes, set as away
            if (inactiveTime > 300000) {
                return 'away';
            }

            return 'online';
        }

        // Update user status in Firebase - with proper cleanup
        function updateUserStatus() {
            if (!currentUser.id) return;

            const status = getUserStatus();
            const statusData = {
                online: true,
                status: status,
                lastSeen: serverTimestamp(),
                inChat: otherUserId,
                name: currentUser.name,
                avatar: currentUser.avatar,
                timestamp: Date.now() // Add timestamp for debugging
            };

            set(userStatusRef, statusData).catch(console.error);
        }



        // Handle page visibility changes
        function handleVisibilityChange() {
            isPageVisible = !document.hidden;

            if (isPageVisible) {
                // User came back - mark as active and update immediately
                trackUserActivity();
                updateUserStatus();
            } else {
                // User left - set as away after a short delay
                setTimeout(() => {
                    if (!isPageVisible) { // Double check they're still away
                        set(userStatusRef, {
                            online: true,
                            status: 'away',
                            lastSeen: serverTimestamp(),
                            inChat: null, // Clear inChat when away
                            name: currentUser.name,
                            avatar: currentUser.avatar,
                            timestamp: Date.now()
                        }).catch(console.error);
                    }
                }, 10000); // 10 seconds delay before marking as away
            }
        }

        document.addEventListener('visibilitychange', handleVisibilityChange);

        // Initialize presence system
        updateUserStatus();

        // Set up intervals with proper cleanup
        statusInterval = setInterval(() => {
            updateUserStatus();
        }, 5000); // Every 5 seconds

        activityInterval = setInterval(() => {
            // Only update if status actually changed
            const newStatus = getUserStatus();
            updateUserStatus();
        }, 120000); // Every 2 minutes

        // Monitor other user's status with improved logic
        let lastStatusUpdate = 0;
        const STATUS_UPDATE_DEBOUNCE = 2000; // 2 seconds

        onValue(otherUserStatusRef, (snapshot) => {
            const now = Date.now();

            // Debounce rapid status updates
            if (now - lastStatusUpdate < STATUS_UPDATE_DEBOUNCE) {
                return;
            }
            lastStatusUpdate = now;

            const status = snapshot.val();

            if (status && status.timestamp) {
                const isOnline = status.online === true;
                const userStatus = status.status || 'offline';
                const statusAge = now - (status.timestamp || 0);

                // If status is very old (more than 1 minutes), consider user offline
                if (statusAge > 60000 && isOnline) {
                    // Status is stale, treat as offline
                    updateStatusUI('offline', 'Offline');
                    return;
                }

                // Update status indicator
                if (statusIndicator) {
                    statusIndicator.className = `status-indicator status-${isOnline ? userStatus : 'offline'}`;
                    statusIndicator.title = userStatus.charAt(0).toUpperCase() + userStatus.slice(1);
                }

                // Update status text
                if (userStatusEl) {

                    let statusText = '';

                    if (isOnline) {
                        switch (userStatus) {
                            case 'online':
                                statusText = (String(status.inChat) === String(currentUser.id)) ?
                                    'Online • In this chat' :
                                    'Online';
                                break;
                            case 'away':
                                statusText = 'Away';
                                break;
                            default:
                                statusText = 'Online';
                        }
                    } else {
                        // Calculate offline duration
                        const lastSeenTime = (typeof status.lastSeen === 'number') ? status.lastSeen : now;
                        const minutesOffline = Math.floor((now - lastSeenTime) / 60000);

                        if (minutesOffline < 1) {
                            statusText = 'Offline • Just now';
                        } else if (minutesOffline < 60) {
                            statusText = `Last seen ${minutesOffline}m ago`;
                        } else if (minutesOffline < 1440) {
                            const hours = Math.floor(minutesOffline / 60);
                            statusText = `Last seen ${hours}h ago`;
                        } else {
                            const days = Math.floor(minutesOffline / 1440);
                            statusText = `Last seen ${days}d ago`;
                        }
                    }
                    userStatusEl.textContent = statusText;

                }
            } else {
                // No status data or invalid data - user is offline
                updateStatusUI('offline', 'Offline');
            }
        });

        function updateStatusUI(status, text) {
            if (statusIndicator) {
                statusIndicator.className = `status-indicator status-${status}`;
                statusIndicator.title = status.charAt(0).toUpperCase() + status.slice(1);
            }
            if (userStatusEl) {
                userStatusEl.textContent = text;
            }
        }

        // Auto-expand textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
        });

        // Typing indicator functionality
        messageInput.addEventListener('input', () => {
            trackUserActivity(); // Mark as active when typing

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
            }, 500);
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
            trackUserActivity(); // Mark as active when scrolling

            const {
                scrollTop,
                scrollHeight,
                clientHeight
            } = chatBox;
            isAtBottom = scrollTop + clientHeight >= scrollHeight - 100;

            if (isAtBottom) {
                scrollToBottomBtn.style.display = 'none';
            } else {
                scrollToBottomBtn.style.display = 'flex';
            }
        });

        scrollToBottomBtn.addEventListener('click', () => {
            trackUserActivity();
            scrollToBottom();
        });

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

        // Listen for new messages
        onChildAdded(chatRef, (snapshot) => {
            const msg = snapshot.val();
            if (!firstMessageLoaded && loadingDiv) {
                loadingDiv.style.display = 'none';
                firstMessageLoaded = true;
                const noMsgEl = chatBox.querySelector('.text-center.text-muted');
                if (noMsgEl) noMsgEl.remove();
            }

            const div = document.createElement("div");
            const isSent = msg.sender_id == currentUser.id;

            div.className = isSent ? "d-flex flex-row-reverse mb-3 align-items-end" : "d-flex mb-3 align-items-end";
            div.setAttribute('data-message-id', snapshot.key);

            const timeStr = new Date(msg.timestamp).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });

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

        // Listen for message updates
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

        // Mark messages as read
        function markMessagesAsRead() {
            if (!otherUserId) return;

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

        if (otherUserId) {
            markMessagesAsRead();
            chatBox.addEventListener('scroll', markMessagesAsRead);
            messageInput.addEventListener('focus', markMessagesAsRead);
            setInterval(markMessagesAsRead, 3000);
        }

        // Send message function
        function sendMessage() {
            const msg = messageInput.value.trim();
            if (msg) {
                trackUserActivity(); // Mark as active when sending

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

        // Enhanced cleanup on page unload
        function cleanup() {
            // Clear all intervals
            if (statusInterval) clearInterval(statusInterval);
            if (activityInterval) clearInterval(activityInterval);
            if (typingTimeout) clearTimeout(typingTimeout);

            // Set offline status
            set(userStatusRef, {
                online: false,
                status: 'offline',
                lastSeen: serverTimestamp(),
                inChat: null,
                name: currentUser.name,
                avatar: currentUser.avatar,
                timestamp: Date.now()
            }).catch(() => {}); // Ignore errors during cleanup 
        }

        //window.addEventListener('beforeunload', cleanup);
        //window.addEventListener('unload', cleanup);

        // Focus input on load
        if (otherUserId && messageInput) {
            messageInput.focus();
        }
    </script>
</body>

</html>