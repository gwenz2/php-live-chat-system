<?php
// Firebase Authentication - no more MySQL dependency for auth
require_once '../firebase-auth.php';

// The authentication check is now handled by JavaScript
// We'll create temporary session variables for backward compatibility
$current_avatar = '../assets/user_male_80px.png';
$current_user_display_name = 'User'; // Default, will be updated by JavaScript
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="icon" href="../iconMO.svg" type="image/svg+xml">
    <title>OneTalk - Live-Chat</title>
</head>
<style>
    body {
        font-family: 'Segoe UI', 'Arial', sans-serif;
        background: linear-gradient(135deg, #e3f0ff 0%, #f9f9f9 100%);
    }
    
    /* Enhanced status indicators */
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
        border: 2px solid white;
        box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
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
    
    .last-seen-time {
        font-size: 11px;
        color: #6c757d;
    }

    .btn-gradient {
        background: linear-gradient(90deg, #4f8cff 0%, #6f6fff 100%);
        color: #fff;
        border: none;
        transition: box-shadow 0.2s;
    }

    .btn-gradient:hover,
    .btn-gradient:focus {
        box-shadow: 0 0 0 0.2rem #4f8cff44;
        color: #fff;
    }

    #contactsList .list-group-item {
        transition: background 0.15s, box-shadow 0.15s;
        border-radius: 1rem;
        margin-bottom: 0.5rem;
        background: #fff;
        box-shadow: 0 1px 4px #0001;
    }

    #contactsList .list-group-item:hover {
        background: #f0f6ff;
        box-shadow: 0 2px 8px #4f8cff22;
    }

    #newUsersList .list-group-item {
        border-radius: 1rem;
        margin-bottom: 0.5rem;
        background: #fff;
        box-shadow: 0 1px 4px #0001;
        transition: background 0.15s, box-shadow 0.15s;
    }

    #newUsersList .list-group-item:hover {
        background: #eaf2ff;
        box-shadow: 0 2px 8px #4f8cff22;
    }

    .modal-content {
        border-radius: 1.5rem;
    }

    .badge-sm {
        font-size: 0.7em;
    }
    @media (max-width: 576px) {
    #searchContacts {
        max-width: 130px !important; /* narrower input on mobile */
        font-size: 13px; /* slightly smaller text */
        padding-left: 2px;
        padding-right: 0px;
    }
    .btn-search {
        padding-left: 2px;
        padding-right: 2px;
        font-size: 10px;
    }
}
</style>

<body class="bg-light p-3">
    <?php include_once 'navbar.php'; ?>
    
    <!-- Notification Container -->
    <div id="notificationContainer" style="position: fixed; top: 80px; right: 20px; z-index: 1050; max-width: 350px;">
        <!-- Dynamic notifications will appear here -->
    </div>
    
    <div class="container mt-5" style="max-width: 90vw;">
        <?php if (isset($_GET['msg']) && $_GET['msg']): ?>
            <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="row mb-3">
            <div class="col d-flex justify-content-end align-items-center">
                <div class="bg-white shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2" style="min-width: 50px;" id="userProfileDisplay">
                    <img id="currentUserAvatar" src="<?php echo $current_avatar; ?>" alt="User" width="32" height="32" class="rounded-circle border border-primary">
                    <span id="currentUserName" class="fw-semibold text-primary"><?php echo htmlspecialchars($current_user_display_name); ?></span>
                    <span class="status-indicator status-online" title="Online"></span>
                </div>
            </div>
        </div>
        <div class="col-md-8 col-lg-6 w-100" style="max-width: 90vw;">
            <div class="card shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="card-title text-center mb-4">BUDDIES</h5>
                    <div class="d-flex flex-wrap align-items-center mb-2 justify-content-between">
                        <form method="get" class="d-flex align-items-center gap-2 mb-0" style="max-width: 350px;">
    <input type="text" name="search" id="searchContacts"
        class="form-control shadow-sm rounded-pill px-1"
        style="max-width: 210px;"
        placeholder="🔍 Search buddy..."
        value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
    <button type="submit" class="btn btn-outline-primary rounded-pill px-2 btn-search">Search</button>
                        </form>
                        <div class="d-flex gap-2">
                            <button class="btn btn-gradient px-4 py-1 fw-semibold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#newChatModal">
                                <i class="bi bi-plus-circle me-1"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="list-group list-group-flush position-relative" id="contactsList" style="min-height: 100px;">
                    <div id="loadingContacts" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10;text-align:center;">
                        <span class="spinner-border text-primary" role="status"></span>
                        <div class="mt-2 fw-semibold">Loading...</div>
                    </div>
                    <!-- Firebase-based friend list will be populated by JavaScript -->
                    <div id="friendsList">
                        <!-- Friends will be loaded dynamically from Firebase -->
                    </div>
                    
                    <div id="noFriendsMessage" class="text-center text-muted py-4" style="display: none;">
                        <i class="bi bi-people"></i>
                        <p class="mt-2">You don't have any buddies yet.</p>
                        <p><small>Add some friends to start chatting!</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start New Chat Modal -->
    <div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable">
            <div class="modal-content rounded-4 shadow-lg border-0">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title" id="newChatModalLabel"><i class="bi bi-person-plus me-2"></i>Start New Chat</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <input type="text" id="searchNewUsers" class="form-control mb-3 rounded-pill px-3 shadow-sm" placeholder="🔍 Search users...">
                    <div id="newUsersList">
                        <!-- Buddy Requests Section -->
                        <h6 class="mb-3 text-center text-warning"><i class="bi bi-person-check me-2"></i>Pending Buddy Requests</h6>
                        <div id="pendingRequests">
                            <!-- Pending requests will be loaded from Firebase -->
                        </div>
                        <div id="noPendingRequests" class="text-muted text-center" style="display: none;">
                            No pending requests.
                        </div>
                        <hr>
                        <!-- New Chat Section with Firebase status -->
                        <h6 class="mb-3 text-center text-primary"><i class="bi bi-person-plus me-2"></i>Add New Buddy</h6>
                        <div id="availableUsers">
                            <!-- Available users will be loaded from Firebase -->
                        </div>
                        <div id="noAvailableUsers" class="text-muted text-center" style="display: none;">
                            <i class="bi bi-search"></i>
                            <p class="mt-2">No users found.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
     
    <script type="module">
        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
        import {
            getDatabase,
            ref,
            query,
            limitToLast,
            onValue,
            serverTimestamp,
            set,
            get,
            remove,
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

        // Notification system
        function showNotification(message, type = 'info', duration = 4000) {
            const container = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            
            // Determine alert class based on type
            let alertClass = 'alert-info';
            let icon = 'bi-info-circle';
            
            switch (type) {
                case 'success':
                    alertClass = 'alert-success';
                    icon = 'bi-check-circle';
                    break;
                case 'error':
                    alertClass = 'alert-danger';
                    icon = 'bi-exclamation-circle';
                    break;
                case 'warning':
                    alertClass = 'alert-warning';
                    icon = 'bi-exclamation-triangle';
                    break;
            }
            
            notification.className = `alert ${alertClass} alert-dismissible fade show shadow-sm mb-2`;
            notification.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="bi ${icon} me-2"></i>
                    <span>${message}</span>
                    <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            container.appendChild(notification);
            
            // Auto-dismiss after duration
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.remove();
                        }
                    }, 150);
                }
            }, duration);
        }

        // Firebase Authentication - get current user data
        let currentUserId = null;
        let currentUserName = null;
        let currentUserAvatar = null;
        let currentUserData = null;

        // Get user data from localStorage
        const firebaseUserData = localStorage.getItem('firebaseUser');
        if (firebaseUserData) {
            currentUserData = JSON.parse(firebaseUserData);
            currentUserId = currentUserData.uid;
            currentUserName = currentUserData.displayName || currentUserData.username;
            currentUserAvatar = currentUserData.avatar || '../assets/user_male_80px.png';
        }

        // Update the UI with current user info
        if (currentUserData) {
            const avatarElement = document.getElementById('currentUserAvatar');
            avatarElement.src = getOptimizedAvatar(currentUserAvatar);
            avatarElement.onerror = function() {
                this.onerror = null;
                this.src = '../assets/user_male_80px.png';
            };
            document.getElementById('currentUserName').textContent = currentUserName;
        }

        let lastActivity = Date.now();
        let isUserActive = true;
        let isPageVisible = !document.hidden;

        // Firebase Authentication check
        import { getAuth, onAuthStateChanged } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-auth.js";
        const auth = getAuth(app);

        // Ensure user is authenticated
        onAuthStateChanged(auth, (user) => {
            console.log('Auth state changed:', user ? 'User logged in' : 'No user', user);
            
            if (!user) {
                // Only redirect if we don't have valid localStorage data AND we're not already redirecting
                const storedUser = localStorage.getItem('firebaseUser');
                console.log('No Firebase user, checking localStorage:', storedUser ? 'Found' : 'Not found');
                
                if (!storedUser && !window.location.href.includes('index.php')) {
                    localStorage.removeItem('firebaseUser');
                    console.log('Redirecting to login page');
                    window.location.href = '../index.php?msg=' + encodeURIComponent('Please log in to access the dashboard.');
                    return;
                }
            } else {
                // We have a Firebase user, ensure localStorage is updated
                if (!currentUserData) {
                    const userData = {
                        uid: user.uid,
                        displayName: user.displayName || 'User',
                        username: user.displayName || 'User',
                        email: user.email || '',
                        avatar: user.photoURL || '../assets/user_male_80px.png'
                    };
                    localStorage.setItem('firebaseUser', JSON.stringify(userData));
                    currentUserData = userData;
                    currentUserId = userData.uid;
                    currentUserName = userData.displayName;
                    currentUserAvatar = userData.avatar;
                    
                    // Update UI with optimized avatar
                    const avatarElement = document.getElementById('currentUserAvatar');
                    avatarElement.src = getOptimizedAvatar(currentUserAvatar);
                    avatarElement.onerror = function() {
                        this.onerror = null;
                        this.src = '../assets/user_male_80px.png';
                    };
                    document.getElementById('currentUserName').textContent = currentUserName;
                    
                    // Load users now that we have authentication
                    loadUsers();
                    loadFriendRequests();
                }
            }
        });

        // Also try to load users if we already have currentUserId set
        if (currentUserId) {
            // Small delay to let UI render first
            setTimeout(() => {
                loadUsers();
                loadFriendRequests();
            }, 100);
        }

        // Add modal event listener to load users when modal opens
        document.addEventListener('DOMContentLoaded', () => {
            const newChatModal = document.getElementById('newChatModal');
            if (newChatModal) {
                newChatModal.addEventListener('shown.bs.modal', () => {
                    console.log('Modal opened, loading users...');
                    loadUsers();
                    loadFriendRequests();
                });
            }
        });

        // Add friend function        // Cache for user data to avoid repeated Firebase calls
        let usersCache = null;
        let lastCacheTime = 0;
        const CACHE_DURATION = 30000; // 30 seconds

        // Load friends and available users from Firebase (optimized)
        async function loadUsers() {
            if (!currentUserId) return;
            
            // Show loading state
            const loadingElement = document.getElementById('loadingContacts');
            if (loadingElement) loadingElement.style.display = 'block';
            
            try {
                // Use cache if recent
                const now = Date.now();
                if (usersCache && (now - lastCacheTime) < CACHE_DURATION) {
                    console.log('Using cached user data');
                    renderUsers(usersCache);
                    return;
                }

                // Fetch data efficiently - only what we need
                const [usersSnapshot, friendsSnapshot] = await Promise.all([
                    get(ref(db, 'users')),
                    get(ref(db, `friends/${currentUserId}`))
                ]);
                
                if (!usersSnapshot.exists()) {
                    showEmptyState();
                    return;
                }
                
                const allUsers = usersSnapshot.val();
                const userFriendsData = friendsSnapshot.exists() ? friendsSnapshot.val() : {};
                
                // Cache the data
                usersCache = { allUsers, friendsData: userFriendsData };
                lastCacheTime = now;
                
                renderUsers(usersCache);
                
            } catch (error) {
                console.error('Error loading users:', error);
                showEmptyState();
            }
        }

        // Separate render function for better performance
        async function renderUsers(data) {
            const { allUsers, friendsData } = data;
            const friendsList = document.getElementById('friendsList');
            const availableUsers = document.getElementById('availableUsers');
                
            // Clear containers and prepare for fast rendering
            friendsList.innerHTML = '';
            availableUsers.innerHTML = '';
            
            let hasFriends = false;
            let hasAvailableUsers = false;
            
            // Load friend request data to check button states
            let sentRequestsData = {};
            let receivedRequestsData = {};
            
            try {
                // Check for sent requests
                const sentRequestsRef = ref(db, `friendRequests`);
                const sentSnapshot = await get(sentRequestsRef);
                if (sentSnapshot.exists()) {
                    const allRequests = sentSnapshot.val();
                    // Find requests sent by current user
                    Object.entries(allRequests).forEach(([toUserId, requests]) => {
                        if (requests[currentUserId]) {
                            sentRequestsData[toUserId] = requests[currentUserId];
                        }
                    });
                    // Find requests received by current user
                    if (allRequests[currentUserId]) {
                        receivedRequestsData = allRequests[currentUserId];
                    }
                }
            } catch (error) {
                console.error('Error loading friend requests:', error);
            }
            
            // Use document fragments for better performance
            const friendsFragment = document.createDocumentFragment();
            const availableFragment = document.createDocumentFragment();
                
            Object.entries(allUsers).forEach(([userId, userData]) => {
                if (userId === currentUserId) return; // Skip self
                
                const friendStatus = friendsData[userId]?.status || 'none';
                const avatar = userData.avatar || '../assets/user_male_80px.png';
                const displayName = userData.displayName || userData.name || userData.username || 'Unknown User';
                const username = userData.username || userData.email?.split('@')[0] || 'unknown';
                
                if (friendStatus === 'accepted') {
                    // Add to friends list using fragment
                    hasFriends = true;
                    const friendElement = createFriendElement(userId, userData, avatar, displayName, username);
                    friendsFragment.appendChild(friendElement);
                } else if (friendStatus === 'none' || !friendStatus) {
                    // Add to available users using fragment
                    hasAvailableUsers = true;
                    const requestStatus = getRequestStatus(userId, sentRequestsData, receivedRequestsData);
                    const userElement = createAvailableUserElement(userId, userData, avatar, displayName, username, requestStatus);
                    availableFragment.appendChild(userElement);
                }
            });
            
            // Append fragments (faster than innerHTML +=)
            friendsList.appendChild(friendsFragment);
            availableUsers.appendChild(availableFragment);
            
            // Show/hide messages and hide loading
            updateUIState(hasFriends, hasAvailableUsers);
            
            // Start monitoring user statuses after rendering
            startStatusMonitoring();
        }

        // Helper function to determine request status
        function getRequestStatus(userId, sentRequests, receivedRequests) {
            if (sentRequests[userId] && sentRequests[userId].status === 'pending') {
                return 'sent'; // Current user sent a request to this user
            }
            if (receivedRequests[userId] && receivedRequests[userId].status === 'pending') {
                return 'received'; // This user sent a request to current user
            }
            return 'none'; // No pending requests
        }

        // Helper function to show empty state
        function showEmptyState() {
            document.getElementById('noFriendsMessage').style.display = 'block';
            document.getElementById('noPendingRequests').style.display = 'block';  
            document.getElementById('noAvailableUsers').style.display = 'block';
            document.getElementById('loadingContacts').style.display = 'none';
        }

        // Load friend requests
        async function loadFriendRequests() {
            try {
                const requestsRef = ref(db, `friendRequests/${currentUserId}`);
                const snapshot = await get(requestsRef);
                
                const pendingContainer = document.getElementById('pendingRequests');
                const noPendingMessage = document.getElementById('noPendingRequests');
                
                pendingContainer.innerHTML = '';
                
                if (!snapshot.exists()) {
                    noPendingMessage.style.display = 'block';
                    return;
                }
                
                const requests = snapshot.val();
                let hasPendingRequests = false;
                
                Object.entries(requests).forEach(([requesterId, requestData]) => {
                    if (requestData.status === 'pending') {
                        hasPendingRequests = true;
                        const requestElement = createFriendRequestElement(requesterId, requestData);
                        pendingContainer.appendChild(requestElement);
                    }
                });
                
                noPendingMessage.style.display = hasPendingRequests ? 'none' : 'block';
                
            } catch (error) {
                console.error('Error loading friend requests:', error);
                document.getElementById('noPendingRequests').style.display = 'block';
            }
        }

        // Create friend request element
        function createFriendRequestElement(requesterId, requestData) {
            const div = document.createElement('div');
            div.className = 'card mb-2 border-warning';
            div.innerHTML = `
                <div class="card-body p-3">
                    <div class="d-flex align-items-center gap-3">
                        <img src="${getOptimizedAvatar(requestData.fromAvatar)}" 
                             alt="User" width="40" height="40" 
                             class="rounded-circle border border-warning"
                             onerror="this.onerror=null;this.src='../assets/user_male_80px.png';">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${requestData.fromName || 'Unknown User'}</h6>
                            <small class="text-muted">Wants to be your buddy</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-success btn-sm flex-fill" onclick="acceptFriendRequest('${requesterId}', '${requestData.fromName || 'User'}')">
                            <i class="bi bi-check-circle me-1"></i>Accept
                        </button>
                        <button class="btn btn-outline-secondary btn-sm flex-fill" onclick="declineFriendRequest('${requesterId}', '${requestData.fromName || 'User'}')">
                            <i class="bi bi-x-circle me-1"></i>Decline
                        </button>
                    </div>
                </div>
            `;
            return div;
        }

        // Helper function to update UI state
        function updateUIState(hasFriends, hasAvailableUsers) {
            document.getElementById('noFriendsMessage').style.display = hasFriends ? 'none' : 'block';
            document.getElementById('noAvailableUsers').style.display = hasAvailableUsers ? 'none' : 'block';
            document.getElementById('loadingContacts').style.display = 'none';
        }

        // Start monitoring user statuses
        function startStatusMonitoring() {
            // Get all user IDs that need monitoring
            const contactElements = document.querySelectorAll('[data-contact-id]');
            const modalElements = document.querySelectorAll('[data-modal-user-id]');
            
            const userIds = new Set();
            contactElements.forEach(el => userIds.add(el.getAttribute('data-contact-id')));
            modalElements.forEach(el => userIds.add(el.getAttribute('data-modal-user-id')));
            
            // Monitor each user's status
            userIds.forEach(userId => {
                if (userId && userId !== currentUserId) {
                    const userStatusRef = ref(db, `users/${userId}/status`);
                    onValue(userStatusRef, (snapshot) => {
                        const status = snapshot.val();
                        updateStatusUI(userId, status);
                    });
                }
            });
        }

        // Helper function to optimize and handle avatar URLs
        function getOptimizedAvatar(avatar) {
            if (!avatar || avatar === '') return '../assets/user_male_80px.png';
            
            // If it's a Google avatar, optimize the URL
            if (avatar.includes('googleusercontent.com')) {
                let optimizedUrl = avatar.replace(/=s\d+(-c)?/, '=s200-c');
                optimizedUrl = optimizedUrl.replace(/^http:/, 'https:');
                return optimizedUrl;
            }
            
            return avatar;
        }

        // Optimized element creation functions
        function createFriendElement(userId, userData, avatar, displayName, username) {
            const div = document.createElement('div');
            div.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 mb-2';
            div.setAttribute('data-contact-id', userId);
            div.style.cursor = 'pointer';
            div.style.borderRadius = '1rem';
            div.style.background = '#fff';
            div.style.boxShadow = '0 1px 4px #0001';
            div.onclick = () => openChat(userId, displayName, avatar);
            
            const optimizedAvatar = getOptimizedAvatar(avatar);
            
            div.innerHTML = `
                <div class="position-relative">
                    <img src="${optimizedAvatar}" class="rounded-circle border border-primary" width="50" height="50" alt="${displayName}"
                         onerror="this.onerror=null; this.src='../assets/user_male_80px.png';">
                    <span class="status-indicator status-offline position-absolute firebase-status" 
                          style="bottom: 2px; right: 2px;" title="Loading..."></span>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h6 class="mb-0">${displayName}</h6>
                        <small class="last-seen-time firebase-last-seen">Loading...</small>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted">@${username}</small>
                        <span class="badge bg-secondary badge-sm firebase-badge">Loading</span>
                    </div>
                    <div class="last-message text-dark-emphasis small mt-1"></div>
                </div>
            `;
            return div;
        }

        function createAvailableUserElement(userId, userData, avatar, displayName, username, requestStatus = 'none') {
            const div = document.createElement('div');
            div.className = 'list-group-item d-flex align-items-center gap-3 mb-2';
            div.setAttribute('data-modal-user-id', userId);
            
            const optimizedAvatar = getOptimizedAvatar(avatar);
            
            // Determine button based on request status
            let buttonHTML = '';
            switch (requestStatus) {
                case 'sent':
                    buttonHTML = `
                        <button class="btn btn-outline-warning btn-sm rounded-pill" disabled>
                            <i class="bi bi-clock me-1"></i>REQUEST SENT
                        </button>
                    `;
                    break;
                case 'received':
                    buttonHTML = `
                        <div class="d-flex gap-1">
                            <button class="btn btn-success btn-sm rounded-pill" onclick="acceptFriendRequest('${userId}', '${displayName}')">
                                <i class="bi bi-check"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-sm rounded-pill" onclick="declineFriendRequest('${userId}', '${displayName}')">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    `;
                    break;
                default:
                    buttonHTML = `
                        <button class="btn btn-primary btn-sm rounded-pill" onclick="addFriend('${userId}', '${displayName}')">
                            ADD BUDDY
                        </button>
                    `;
            }
            
            div.innerHTML = `
                <div class="position-relative">
                    <img src="${optimizedAvatar}" class="rounded-circle border border-primary" width="40" height="40" alt="${displayName}"
                         onerror="this.onerror=null; this.src='../assets/user_male_80px.png';">
                    <span class="status-indicator status-offline position-absolute firebase-status-modal" 
                          style="bottom: 0; right: 0;" title="Loading..."></span>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-0">${displayName}</h6>
                    <small class="text-muted">@${username} • <span class="firebase-status-text">Loading...</span></small>
                </div>
                ${buttonHTML}
            `;
            return div;
        }

        // Legacy functions for compatibility
        function createFriendItem(userId, userData, avatar, displayName, username) {
            return `
                <div class="list-group-item list-group-item-action d-flex align-items-center gap-3 mb-2" 
                     data-contact-id="${userId}" 
                     onclick="openChat('${userId}', '${displayName}', '${avatar}')"
                     style="cursor: pointer; border-radius: 1rem; background: #fff; box-shadow: 0 1px 4px #0001;">
                    <div class="position-relative">
                        <img src="${avatar}" class="rounded-circle border border-primary" width="50" height="50" alt="${displayName}">
                        <span class="status-indicator status-offline position-absolute firebase-status" 
                              style="bottom: 2px; right: 2px;" title="Loading..."></span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="mb-0">${displayName}</h6>
                            <small class="last-seen-time firebase-last-seen">Loading...</small>
                        </div>
                        <div class="d-flex align-items-center justify-content-between">
                            <small class="text-muted">@${username}</small>
                            <span class="badge bg-secondary badge-sm firebase-badge">Loading</span>
                        </div>
                        <div class="last-message text-dark-emphasis small mt-1"></div>
                    </div>
                </div>
            `;
        }

        // Create available user item
        function createAvailableUserItem(userId, userData, avatar, displayName, username) {
            return `
                <div class="list-group-item d-flex align-items-center gap-3 mb-2" data-modal-user-id="${userId}">
                    <div class="position-relative">
                        <img src="${avatar}" class="rounded-circle border border-primary" width="40" height="40" alt="${displayName}">
                        <span class="status-indicator status-offline position-absolute firebase-status-modal" 
                              style="bottom: 0; right: 0;" title="Loading..."></span>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">${displayName}</h6>
                        <small class="text-muted">@${username} • <span class="firebase-status-text">Loading...</span></small>
                    </div>
                    <button class="btn btn-primary btn-sm rounded-pill" onclick="addFriend('${userId}', '${displayName}')">
                        ADD BUDDY
                    </button>
                </div>
            `;
        }

        // Open chat function
        window.openChat = function(userId, displayName, avatar) {
            // Create a form and submit to buddyroom.php
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'buddyroom.php';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'user_id';
            input.value = userId;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        };

        // Send friend request function
        window.addFriend = async function(userId, displayName) {
            try {
                // Check if there's already a relationship
                const existingRequestRef = ref(db, `friendRequests/${userId}/${currentUserId}`);
                const existingSnapshot = await get(existingRequestRef);
                
                if (existingSnapshot.exists()) {
                    showNotification('You already have a pending friend request with this user!', 'warning');
                    return;
                }
                
                // Check if already friends
                const friendsRef = ref(db, `friends/${currentUserId}/${userId}`);
                const friendSnapshot = await get(friendsRef);
                
                if (friendSnapshot.exists()) {
                    showNotification('You are already friends with this user!', 'info');
                    return;
                }
                
                // Send friend request
                const requestRef = ref(db, `friendRequests/${userId}/${currentUserId}`);
                await set(requestRef, {
                    from: currentUserId,
                    to: userId,
                    fromName: currentUserName,
                    fromAvatar: currentUserAvatar,
                    status: 'pending',
                    sentAt: serverTimestamp(),
                    timestamp: Date.now()
                });
                
                // Show success message
                showNotification(`Friend request sent to ${displayName}!`, 'success');
                
                // Reload users list to update button states
                await loadUsers();
                
            } catch (error) {
                console.error('Error sending friend request:', error);
                showNotification('Failed to send friend request. Please try again.', 'error');
            }
        };

        // Accept friend request function
        window.acceptFriendRequest = async function(requesterId, requesterName) {
            try {
                // Create mutual friendship
                const friendsRef = ref(db, `friends/${currentUserId}/${requesterId}`);
                await set(friendsRef, {
                    status: 'accepted',
                    addedAt: serverTimestamp(),
                    timestamp: Date.now()
                });
                
                const reverseFriendsRef = ref(db, `friends/${requesterId}/${currentUserId}`);
                await set(reverseFriendsRef, {
                    status: 'accepted',
                    addedAt: serverTimestamp(),
                    timestamp: Date.now()
                });
                
                // Remove the friend request
                const requestRef = ref(db, `friendRequests/${currentUserId}/${requesterId}`);
                await remove(requestRef);
                
                // Show success message
                showNotification(`You are now friends with ${requesterName}!`, 'success');
                
                // Reload users and friend requests
                await loadUsers();
                await loadFriendRequests();
                
            } catch (error) {
                console.error('Error accepting friend request:', error);
                showNotification('Failed to accept friend request. Please try again.', 'error');
            }
        };

        // Decline friend request function
        window.declineFriendRequest = async function(requesterId, requesterName) {
            try {
                // Remove the friend request
                const requestRef = ref(db, `friendRequests/${currentUserId}/${requesterId}`);
                await remove(requestRef);
                
                // Show message
                showNotification(`Friend request from ${requesterName} declined.`, 'info');
                
                // Reload friend requests
                await loadFriendRequests();
                
            } catch (error) {
                console.error('Error declining friend request:', error);
                showNotification('Failed to decline friend request. Please try again.', 'error');
            }
        };

        // Load users will be called after authentication is confirmed
        // Removed immediate loading to prevent race condition

        // Track user activity
        function trackUserActivity() {
            lastActivity = Date.now();
            isUserActive = true;
        }

        ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'].forEach(event => {
            document.addEventListener(event, trackUserActivity, { passive: true });
        });

        // Get user status
        function getUserStatus() {
            const now = Date.now();
            const inactiveTime = now - lastActivity;
            
            if (!isPageVisible) {
                return 'away';
            }
            
            if (inactiveTime > 300000) { // 5 minutes
                return 'away';
            }
            
            return 'online';
        }

        // Update user status in Firebase
        function updateUserStatus() {
            const status = getUserStatus();
            const userStatusRef = ref(db, `users/${currentUserId}/status`);
            
            set(userStatusRef, {
                online: status !== 'offline',
                status: status,
                lastSeen: serverTimestamp(),
                name: currentUserName,
                avatar: currentUserAvatar,
                timestamp: Date.now()
            }).catch(console.error);
        }

        // Initialize presence system
        updateUserStatus();
        const statusInterval = setInterval(updateUserStatus, 45000);

        // Handle page visibility
        document.addEventListener('visibilitychange', () => {
            isPageVisible = !document.hidden;
            
            if (isPageVisible) {
                trackUserActivity();
                updateUserStatus();
            } else {
                setTimeout(() => {
                    if (!isPageVisible) {
                        const userStatusRef = ref(db, `users/${currentUserId}/status`);
                        set(userStatusRef, {
                            online: true,
                            status: 'away',
                            lastSeen: serverTimestamp(),
                            name: currentUserName,
                            avatar: currentUserAvatar,
                            timestamp: Date.now()
                        }).catch(console.error);
                    }
                }, 30000); // 30 seconds delay
            }
        });

        // Set up disconnect handler
        const disconnectRef = onDisconnect(ref(db, `users/${currentUserId}/status`));
        disconnectRef.set({
            online: false,
            status: 'offline',
            lastSeen: serverTimestamp(),
            name: currentUserName,
            avatar: currentUserAvatar,
            timestamp: Date.now()
        }).catch(console.error);

        // Function to update status UI elements
        function updateStatusUI(userId, status) {
            const isOnline = status && status.online === true;
            const userStatus = status ? (status.status || 'offline') : 'offline';
            
            // Update main contacts list
            const contactElements = document.querySelectorAll(`[data-contact-id="${userId}"]`);
            contactElements.forEach(element => {
                const statusIndicator = element.querySelector('.firebase-status');
                const lastSeenEl = element.querySelector('.firebase-last-seen');
                const badgeEl = element.querySelector('.firebase-badge');
                
                if (statusIndicator) {
                    statusIndicator.className = `status-indicator status-${isOnline ? userStatus : 'offline'} position-absolute firebase-status`;
                    statusIndicator.title = userStatus.charAt(0).toUpperCase() + userStatus.slice(1);
                }
                
                if (lastSeenEl && badgeEl) {
                    let statusText = 'Offline';
                    let badgeClass = 'bg-secondary';
                    
                    if (isOnline) {
                        switch (userStatus) {
                            case 'online':
                                statusText = 'Online';
                                badgeClass = 'bg-success';
                                break;
                            case 'away':
                                statusText = 'Away';
                                badgeClass = 'bg-warning';
                                break;
                            default:
                                statusText = 'Online';
                                badgeClass = 'bg-success';
                        }
                    } else if (status && status.lastSeen) {
                        const now = Date.now();
                        const lastSeenTime = typeof status.lastSeen === 'number' ? status.lastSeen : now;
                        const minutesOffline = Math.floor((now - lastSeenTime) / 60000);
                        
                        if (minutesOffline < 1) {
                            statusText = 'Just now';
                        } else if (minutesOffline < 60) {
                            statusText = `${minutesOffline}m ago`;
                        } else if (minutesOffline < 1440) {
                            const hours = Math.floor(minutesOffline / 60);
                            statusText = `${hours}h ago`;
                        } else {
                            const days = Math.floor(minutesOffline / 1440);
                            statusText = `${days}d ago`;
                        }
                    }
                    
                    lastSeenEl.textContent = statusText;
                    badgeEl.className = `badge ${badgeClass} badge-sm firebase-badge`;
                    badgeEl.textContent = userStatus.charAt(0).toUpperCase() + userStatus.slice(1);
                }
            });
            
            // Update modal elements
            const modalElements = document.querySelectorAll(`[data-modal-user-id="${userId}"]`);
            modalElements.forEach(element => {
                const statusIndicator = element.querySelector('.firebase-status-modal');
                const statusText = element.querySelector('.firebase-status-text');
                
                if (statusIndicator) {
                    statusIndicator.className = `status-indicator status-${isOnline ? userStatus : 'offline'} position-absolute firebase-status-modal`;
                    statusIndicator.title = userStatus.charAt(0).toUpperCase() + userStatus.slice(1);
                }
                
                if (statusText) {
                    statusText.textContent = isOnline ? userStatus.charAt(0).toUpperCase() + userStatus.slice(1) : 'Offline';
                }
            });
        }

        // Status monitoring is now handled by startStatusMonitoring() function

        // Load contacts with Firebase integration (last messages)
        const contacts = Array.from(document.querySelectorAll('[data-contact-id]'));
        const contactData = [];
        let loadedCount = 0;
        const loadingContacts = document.getElementById('loadingContacts');
        if (loadingContacts) loadingContacts.style.display = 'block';

        contacts.forEach(el => {
            const contactId = el.getAttribute('data-contact-id');
            const chatRef = ref(db, `chats/${Math.min(currentUserId, contactId)}_${Math.max(currentUserId, contactId)}`);
            const lastMsgQuery = query(chatRef, limitToLast(1));

            onValue(lastMsgQuery, snapshot => {
                let lastTimestamp = 0;
                const lastMsgDiv = el.querySelector('.last-message');

                if (snapshot.exists()) {
                    snapshot.forEach(childSnap => {
                        const msg = childSnap.val();
                        lastTimestamp = msg.timestamp || 0;
                        lastMsgDiv.textContent = (msg.sender_id == currentUserId ? 'You: ' : '') + msg.message;

                        if (msg.sender_id != currentUserId && !msg.is_read) {
                            lastMsgDiv.classList.add('fw-bold');
                            lastMsgDiv.classList.remove('text-dark-emphasis');
                        } else {
                            lastMsgDiv.classList.remove('fw-bold');
                            lastMsgDiv.classList.add('text-dark-emphasis');
                        }
                    });
                } else {
                    lastMsgDiv.textContent = 'No messages yet';
                    lastMsgDiv.classList.add('text-muted');
                }

                contactData.push({
                    el,
                    lastTimestamp
                });
                loadedCount++;

                if (loadedCount === contacts.length) {
                    contactData.sort((a, b) => b.lastTimestamp - a.lastTimestamp);
                    const contactsList = document.getElementById('contactsList');
                    contactData.forEach(({
                        el
                    }) => contactsList.appendChild(el));
                    if (loadingContacts) loadingContacts.style.display = 'none';
                }
            }, {
                
            });
        });

        // Cleanup on page unload
        function cleanup() {
            clearInterval(statusInterval);
            
            set(userStatusRef, {
                online: false,
                status: 'offline',
                lastSeen: serverTimestamp(),
                name: currentUserName,
                avatar: currentUserAvatar,
                timestamp: Date.now()
            }).catch(() => {});
        }

        window.addEventListener('beforeunload', cleanup);
        window.addEventListener('unload', cleanup);

        // If no contacts loaded, hide loading immediately
        if (contacts.length === 0 && loadingContacts) {
            loadingContacts.style.display = 'none';
        }
    </script>
</body>
</html>