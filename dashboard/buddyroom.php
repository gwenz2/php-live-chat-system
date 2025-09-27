<?php
// Firebase Authentication - no more MySQL dependency for auth
require_once '../firebase-auth.php';

// The authentication check is now handled by JavaScript
// We'll create temporary session variables for backward compatibility
$other_user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
$current_user_id = 'firebase-user'; // Will be set by JavaScript

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php?msg=' . urlencode('Invalid request'));
    exit;
}

if (empty($other_user_id)) {
    header('Location: index.php?msg=' . urlencode('Invalid user'));
    exit;
}

// Firebase will handle all user data and status
// Default values that will be updated by JavaScript
$other_user = [
    'displayName' => 'User',
    'username' => 'user',
    'avatar' => '../assets/user_male_80px.png'
];

$current_avatar = '../assets/user_male_80px.png';
$other_avatar = '../assets/user_male_80px.png';
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

        /* Status indicator circles removed - clean chat interface */

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
                        <div>
                            <img id="otherUserAvatar" src="../assets/user_male_80px.png" class="rounded-circle border border-light" width="48" height="48" alt="User">
                        </div>
                        <div class="flex-grow-1">
                            <h6 id="otherUserName" class="mb-0">Loading...</h6>
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
        // Firebase Authentication - get current user data
        let currentUser = null;
        let otherUserId = <?php echo json_encode($other_user_id); ?>;
        let otherUserAvatar = null;
        let otherUserName = null;

        // Get user data from localStorage
        const firebaseUserData = localStorage.getItem('firebaseUser');
        if (firebaseUserData) {
            const userData = JSON.parse(firebaseUserData);
            currentUser = {
                id: userData.uid,
                name: userData.displayName || userData.username || 'User',
                avatar: userData.avatar || '../assets/user_male_80px.png'
            };
        }

        // Redirect if not authenticated
        if (!currentUser) {
            window.location.href = '../index.php?msg=' + encodeURIComponent('Please log in to access the chatroom.');
        }

        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import {
            getAuth,
            onAuthStateChanged
        } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js";
        import {
            getDatabase,
            ref,
            push,
            get,
            onValue,
            onChildAdded,
            update,
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
        const auth = getAuth(app);
        const db = getDatabase(app);

        // Helper function to optimize avatar URLs
        function optimizeAvatarUrl(photoURL) {
            if (!photoURL) return '../assets/user_male_80px.png';
            
            // If it's a Google avatar, optimize the URL for better loading
            if (photoURL.includes('googleusercontent.com')) {
                // Remove size restrictions and add better parameters
                let optimizedUrl = photoURL.replace(/=s\d+(-c)?/, '=s200-c');
                // Ensure it uses https
                optimizedUrl = optimizedUrl.replace(/^http:/, 'https:');
                return optimizedUrl;
            }
            
            // For other photo URLs, return as-is
            return photoURL;
        }

        // Load other user data from Firebase
        async function loadOtherUserData() {
            if (!otherUserId) {
                console.error('No otherUserId provided');
                return;
            }
            
            console.log('Loading other user data for:', otherUserId);
            
            try {
                const otherUserRef = ref(db, `users/${otherUserId}`);
                const snapshot = await get(otherUserRef);
                
                console.log('Other user data snapshot:', snapshot.exists(), snapshot.val());
                
                if (snapshot.exists()) {
                    const userData = snapshot.val();
                    otherUserName = userData.displayName || userData.name || 'User';
                    // Prioritize custom avatar URL over Google photo
                    const avatarUrl = userData.customAvatarUrl || userData.photoURL || userData.avatar;
                    otherUserAvatar = optimizeAvatarUrl(avatarUrl);
                    
                    console.log('Other user loaded:', {
                        name: otherUserName,
                        avatar: otherUserAvatar
                    });
                    
                    // Update UI with other user info
                    document.getElementById('otherUserName').textContent = otherUserName;
                    
                    // Optimize and set avatar with error handling
                    const otherAvatarElement = document.getElementById('otherUserAvatar');
                    const optimizedAvatar = optimizeAvatarUrl(otherUserAvatar);
                    otherAvatarElement.src = optimizedAvatar;
                    otherAvatarElement.onerror = function() {
                        this.onerror = null;
                        this.src = '../assets/user_male_80px.png';
                    };
                } else {
                    console.error('Other user data not found in Firebase for ID:', otherUserId);
                    // Create a basic user record if it doesn't exist
                    await createMissingUserRecord(otherUserId);
                }
            } catch (error) {
                console.error('Error loading other user data:', error);
                // Show error in UI
                document.getElementById('otherUserName').textContent = 'Error loading user';
            }
        }

        // Create missing user record (fallback)
        async function createMissingUserRecord(userId) {
            try {
                console.log('Creating missing user record for:', userId);
                
                // Try to get user info from Auth
                const userRef = ref(db, `users/${userId}`);
                const defaultUserData = {
                    uid: userId,
                    name: 'User',
                    displayName: 'User',
                    email: '',
                    avatar: '../assets/user_male_80px.png',
                    status: 'offline',
                    lastSeen: Date.now(),
                    createdAt: Date.now(),
                    authMethod: 'google'
                };
                
                await set(userRef, defaultUserData);
                console.log('Created default user record');
                
                // Update UI with default data
                otherUserName = 'User';
                otherUserAvatar = '../assets/user_male_80px.png';
                document.getElementById('otherUserName').textContent = otherUserName;
                
            } catch (error) {
                console.error('Error creating missing user record:', error);
            }
        }

        // Optimized authentication check
        let isAuthenticated = false;
        
        // Check if we already have user data to avoid waiting for Firebase
        if (currentUser) {
            isAuthenticated = true;
            initializeChat();
        }
        
        // Also listen for auth changes as backup
        onAuthStateChanged(auth, (user) => {
            if (!user && !isAuthenticated) {
                window.location.href = '../index.php?msg=' + encodeURIComponent('Please log in to access the chatroom.');
                return;
            }
            
            if (user && !isAuthenticated) {
                isAuthenticated = true;
                initializeChat();
            }
        });

        // Initialize chat functionality
        async function initializeChat() {
            console.log('Initializing chat...', {
                currentUser: currentUser,
                otherUserId: otherUserId
            });
            
            // Show loading state
            const loadingDiv = document.getElementById('loadingMessage');
            if (loadingDiv) {
                loadingDiv.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Loading chat...</p></div>';
                loadingDiv.style.display = 'block';
            }

            try {
                // Ensure current user exists in Firebase
                await ensureCurrentUserExists();
                
                // Load other user data and start chat
                await loadOtherUserData();
                setupChatInterface();
                console.log('Chat initialization completed');
            } catch (error) {
                console.error('Error during chat initialization:', error);
                if (loadingDiv) {
                    loadingDiv.innerHTML = '<div class="text-center text-danger">Error initializing chat: ' + error.message + '</div>';
                }
            }
        }

        // Ensure current user exists in Firebase Database
        async function ensureCurrentUserExists() {
            if (!currentUser) return;
            
            try {
                const currentUserRef = ref(db, `users/${currentUser.id}`);
                const snapshot = await get(currentUserRef);
                
                if (!snapshot.exists()) {
                    console.log('Current user not found in Firebase, creating record...');
                    
                    const userData = {
                        uid: currentUser.id,
                        name: currentUser.name,
                        displayName: currentUser.name,
                        email: '',
                        avatar: optimizeAvatarUrl(currentUser.avatar),
                        status: 'online',
                        lastSeen: Date.now(),
                        createdAt: Date.now(),
                        authMethod: 'google'
                    };
                    
                    await set(currentUserRef, userData);
                    console.log('Created current user record in Firebase');
                    
                    // Update current user data
                    currentUser.avatar = userData.avatar;
                } else {
                    const existingData = snapshot.val();
                    console.log('Current user found in Firebase:', existingData);
                    
                    // Update current user avatar if custom avatar is available
                    const prioritizedAvatar = existingData.customAvatarUrl || existingData.photoURL || existingData.avatar || currentUser.avatar;
                    currentUser.avatar = prioritizedAvatar;
                }
                
            } catch (error) {
                console.error('Error ensuring current user exists:', error);
            }
        }

        // Setup chat interface after data is loaded
        function setupChatInterface() {
            // Initialize all chat functionality here
            if (!otherUserId || !currentUser) {
                console.error('Cannot setup chat - missing user data');
                return;
            }
            
            console.log('Setting up chat interface for:', otherUserId, 'currentUser:', currentUser.id);
            console.log('Current user ID type:', typeof currentUser.id, 'Other user ID type:', typeof otherUserId);
            console.log('Current user ID value:', currentUser.id, 'Other user ID value:', otherUserId);
            
            // Setup Firebase references with loaded data
            const refsInitialized = initializeFirebaseReferences();
            if (!refsInitialized) {
                console.error('Failed to initialize Firebase references');
                if (loadingDiv) {
                    loadingDiv.innerHTML = '<div class="text-center text-danger">Failed to initialize chat</div>';
                }
                return;
            }
            
            setupEventListeners();
            startPresenceTracking();
            startOtherUserStatusMonitoring();
            setupMessageReadTracking();
            
            // Load messages after Firebase refs are ready
            loadChatMessages();
        }

        // Start monitoring other user's status
        function startOtherUserStatusMonitoring() {
            if (!otherUserStatusRef) {
                console.log('otherUserStatusRef not initialized yet');
                return;
            }
            
            console.log('Starting other user status monitoring for:', otherUserId);
            onValue(otherUserStatusRef, (snapshot) => {
                const status = snapshot.val();
                console.log('Other user status update:', status);
                updateOtherUserStatus(status);
            });
        }

        // Update other user's status in UI - only show "In this chat" text, no circles
        function updateOtherUserStatus(status) {
            const userStatusEl = document.getElementById('userStatus');
            
            if (!status) {
                if (userStatusEl) userStatusEl.textContent = '';
                return;
            }
            
            const isActive = status.online === true;
            let statusText = '';
            
            // Check if user is in this specific chat
            if (isActive && status.inChat === currentUser.id) {
                statusText = 'In this chat';
            }
            // Otherwise leave blank - no status shown
            
            if (userStatusEl) {
                userStatusEl.textContent = statusText;
            }
        }

        // Initialize Firebase references
        function initializeFirebaseReferences() {
            if (!currentUser || !otherUserId) {
                console.error('Cannot initialize Firebase refs - missing user data');
                return false;
            }
            
            try {
                // Ensure both IDs are strings for proper comparison
                const currentId = String(currentUser.id);
                const otherId = String(otherUserId);
                const chatId = `${currentId < otherId ? currentId : otherId}_${currentId < otherId ? otherId : currentId}`;
                console.log('Initializing Firebase references with chatId:', chatId);
                console.log('Chat ID components - Current ID:', currentId, 'Other ID:', otherId);
                
                chatRef = ref(db, `chats/${chatId}`);
                typingRef = ref(db, `typing/${chatId}/${currentId}`);
                otherTypingRef = ref(db, `typing/${chatId}/${otherId}`);
                userStatusRef = ref(db, `users/${currentId}/status`);
                otherUserStatusRef = ref(db, `users/${otherId}/status`);
                
                console.log('Firebase references initialized successfully');
                return true;
            } catch (error) {
                console.error('Error initializing Firebase references:', error);
                return false;
            }
        }

        // Firebase references (will be initialized after authentication)
        let chatRef, typingRef, otherTypingRef, userStatusRef, otherUserStatusRef;

        // Replace the entire JavaScript status management section with this fixed version:

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

        // Update user status in Firebase - simplified with inChat tracking
        function updateUserStatus() {
            if (!currentUser.id || !userStatusRef) {
                console.log('Cannot update status - missing user or userStatusRef');
                return;
            }

            const statusData = {
                online: true,
                lastSeen: serverTimestamp(),
                inChat: otherUserId, // Set which chat this user is currently in
                name: currentUser.name,
                avatar: currentUser.avatar,
                timestamp: Date.now(),
                heartbeat: Date.now()
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

        // Setup event listeners for chat functionality
        function setupEventListeners() {
            console.log('Setting up event listeners...');
            
            // Listen for new messages
            if (chatRef) {
                onChildAdded(chatRef, (snapshot) => {
                    const msg = snapshot.val();
                    if (!firstMessageLoaded && loadingDiv) {
                        loadingDiv.style.display = 'none';
                        firstMessageLoaded = true;
                        const noMsgEl = chatBox.querySelector('.text-center.text-muted');
                        if (noMsgEl) noMsgEl.remove();
                    }

                    addMessageToUI(snapshot.key, msg);

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

                            if (messageDiv && String(msg.sender_id) === String(currentUser.id)) {
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
            }

            // Listen for other user typing
            if (otherTypingRef) {
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
            }
        }

        // Function to add message to UI
        function addMessageToUI(messageId, msg) {
            const div = document.createElement("div");
            const isSent = String(msg.sender_id) === String(currentUser.id);

            div.className = isSent ? "d-flex flex-row-reverse mb-3 align-items-end" : "d-flex mb-3 align-items-end";
            div.setAttribute('data-message-id', messageId);

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

            // Optimize avatar display with error handling
            const avatarSrc = optimizeAvatarUrl(msg.sender_avatar);

            div.innerHTML = `
        <img src="${avatarSrc}" class="rounded-circle ${isSent ? 'ms-2' : 'me-2'}" width="32" height="32" alt="User" onerror="this.onerror=null;this.src='../assets/user_male_80px.png';">
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
        }

        // Initialize presence tracking
        function startPresenceTracking() {
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
        }

        // Status monitoring is now handled by startOtherUserStatusMonitoring() function

        // Combined input handler for auto-expand and typing indicator
        messageInput.addEventListener('input', function() {
            // Auto-expand textarea
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 100) + 'px';
            
            // Track user activity
            trackUserActivity();

            // Handle typing indicator only if Firebase refs are ready
            if (typingRef && currentUser) {
                if (!isTyping) {
                    isTyping = true;
                    set(typingRef, {
                        typing: true,
                        name: currentUser.name,
                        timestamp: serverTimestamp()
                    }).catch(console.error);
                }

                clearTimeout(typingTimeout);
                typingTimeout = setTimeout(() => {
                    isTyping = false;
                    if (typingRef) {
                        set(typingRef, null).catch(console.error);
                    }
                }, 500);
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

        // Load chat messages (called after Firebase refs are initialized)
        function loadChatMessages() {
            console.log('Loading chat messages...');
            
            if (!chatRef) {
                console.error('chatRef not initialized');
                return;
            }
            
            // Show loading state
            if (loadingDiv) loadingDiv.style.display = 'block';

            get(chatRef).then(snapshot => {
                console.log('Chat messages loaded:', snapshot.exists());
                if (!snapshot.exists()) {
                    if (loadingDiv) loadingDiv.style.display = 'none';
                    const noMsg = document.createElement("div");
                    noMsg.className = "text-center text-muted mt-5";
                    noMsg.innerHTML = '<i class="bi bi-chat-dots"></i><br><em>No messages yet. Start the conversation!</em>';
                    chatBox.appendChild(noMsg);
                } else {
                    if (loadingDiv) loadingDiv.style.display = 'none';
                }
            }).catch(error => {
                console.error('Error loading messages:', error);
                if (loadingDiv) {
                    loadingDiv.innerHTML = '<div class="text-center text-danger">Error loading messages</div>';
                }
            });
        }



        // Mark messages as read
        function markMessagesAsRead() {
            if (!otherUserId || !chatRef) {
                console.log('Cannot mark messages as read - missing otherUserId or chatRef');
                return;
            }

            get(chatRef).then(snapshot => {
                if (snapshot.exists()) {
                    const updates = {};
                    snapshot.forEach(childSnap => {
                        const msg = childSnap.val();
                        if (String(msg.sender_id) === String(otherUserId) && !msg.is_read) {
                            updates[childSnap.key + "/is_read"] = true;
                            updates[childSnap.key + "/read_at"] = Date.now();
                        }
                    });
                    if (Object.keys(updates).length > 0) {
                        update(chatRef, updates);
                    }
                }
            }).catch(error => {
                console.error('Error marking messages as read:', error);
            });
        }

        // Setup message read tracking (called after Firebase refs are initialized)
        function setupMessageReadTracking() {
            if (otherUserId && chatRef) {
                console.log('Setting up message read tracking...');
                markMessagesAsRead();
                chatBox.addEventListener('scroll', markMessagesAsRead);
                messageInput.addEventListener('focus', markMessagesAsRead);
                setInterval(markMessagesAsRead, 3000);
            }
        }

        // Send message function
        function sendMessage() {
            const msg = messageInput.value.trim();
            if (msg && chatRef && currentUser) {
                trackUserActivity(); // Mark as active when sending

                if (isTyping && typingRef) {
                    isTyping = false;
                    set(typingRef, null).catch(console.error);
                }

                push(chatRef, {
                    sender_id: String(currentUser.id),
                    sender_name: currentUser.name,
                    sender_avatar: currentUser.avatar,
                    message: msg,
                    timestamp: Date.now(),
                    is_read: false
                }).then(() => {
                    console.log('Message sent successfully');
                }).catch(error => {
                    console.error('Error sending message:', error);
                });

                messageInput.value = "";
                messageInput.style.height = 'auto';
                messageInput.focus();
            } else if (!chatRef) {
                console.error('Cannot send message - chat not initialized');
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

            // Remove from chat when leaving - don't set offline, just clear inChat
            if (userStatusRef && currentUser) {
                set(userStatusRef, {
                    online: true, // Keep online, just not in this chat
                    lastSeen: serverTimestamp(),
                    inChat: null, // Clear the chat room they're in
                    name: currentUser.name,
                    avatar: currentUser.avatar,
                    timestamp: Date.now(),
                    heartbeat: Date.now()
                }).catch(() => {}); // Ignore errors during cleanup 
            }
        }

        window.addEventListener('beforeunload', cleanup);
        window.addEventListener('unload', cleanup);

        // Focus input on load
        if (otherUserId && messageInput) {
            messageInput.focus();
        }
    </script>
</body>

</html>