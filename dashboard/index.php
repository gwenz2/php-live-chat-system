<?php
// Firebase Authentication - no more MySQL dependency for auth
require_once '../firebase-auth.php';
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
    
    /* Status indicators removed - clean simple chat interface */
    
    /* Removed last-seen-time - no status tracking */

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
                    <img id="currentUserAvatar" src="../assets/user_male_80px.png" alt="User" width="32" height="32" class="rounded-circle border border-primary">
                    <span id="currentUserName" class="fw-semibold text-primary">Loading...</span>
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
                <div class="list-group list-group-flush position-relative" id="contactsList" style="min-height: 470px;">
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

        // Firebase Data Cache System
        const CACHE_EXPIRY_TIME = 5 * 60 * 1000; // 5 minutes
        
        function setCachedData(key, data) {
            const cacheData = {
                data: data,
                timestamp: Date.now(),
                expiresAt: Date.now() + CACHE_EXPIRY_TIME
            };
            localStorage.setItem(`firebase_cache_${key}`, JSON.stringify(cacheData));
        }
        
        function getCachedData(key) {
            const cached = localStorage.getItem(`firebase_cache_${key}`);
            if (!cached) return null;
            
            const cacheData = JSON.parse(cached);
            if (Date.now() > cacheData.expiresAt) {
                localStorage.removeItem(`firebase_cache_${key}`);
                return null;
            }
            
            return cacheData.data;
        }
        
        function clearUserCache(userId) {
            localStorage.removeItem(`firebase_cache_user_${userId}`);
            localStorage.removeItem(`firebase_cache_friends_${userId}`);
            localStorage.removeItem(`firebase_cache_requests_${userId}`);
        }
        
        function refreshUserData() {
            if (currentUserId) {
                clearUserCache(currentUserId);
                loadUsers();
                loadFriendRequests();
                console.log('User data cache refreshed');
            }
        }

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
        let presenceCheckInterval = null;

        // Get user data from localStorage
        const firebaseUserData = localStorage.getItem('firebaseUser');
        if (firebaseUserData) {
            currentUserData = JSON.parse(firebaseUserData);
            currentUserId = currentUserData.uid;
            currentUserName = currentUserData.displayName || currentUserData.username;
            currentUserAvatar = currentUserData.avatar || '../assets/user_male_80px.png';
        } else {
            // No user data found - redirect immediately to login
            console.log('No user data found in localStorage, redirecting to login');
            window.location.href = '../index.php?msg=' + encodeURIComponent('Please log in to access the dashboard.');
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
        onAuthStateChanged(auth, async (user) => {
            console.log('Auth state changed:', user ? 'User logged in' : 'No user', user);
            
            if (!user) {
                // Check localStorage as fallback
                const storedUser = localStorage.getItem('firebaseUser');
                console.log('No Firebase user, checking localStorage:', storedUser ? 'Found' : 'Not found');
                
                if (!storedUser) {
                    // No authentication found - redirect to login
                    localStorage.removeItem('firebaseUser');
                    console.log('Redirecting to login page');
                    window.location.href = '../index.php?msg=' + encodeURIComponent('Please log in to access the dashboard.');
                    return;
                }
            } else {
                // We have a Firebase user, ensure localStorage is updated
                if (!currentUserData) {
                    // Check cache first
                    let fbUserData = getCachedData(`user_${user.uid}`);
                    let customAvatar = user.photoURL;
                    
                    if (fbUserData) {
                        console.log('Using cached user data');
                        customAvatar = fbUserData.customAvatarUrl || fbUserData.photoURL || user.photoURL;
                    } else {
                        // Get user data from Firebase Database
                        try {
                            console.log('Loading fresh user data from Firebase');
                            const userRef = ref(db, `users/${user.uid}`);
                            const userSnapshot = await get(userRef);
                            
                            if (userSnapshot.exists()) {
                                fbUserData = userSnapshot.val();
                                // Cache the user data
                                setCachedData(`user_${user.uid}`, fbUserData);
                                // Prioritize custom avatar URL
                                customAvatar = fbUserData.customAvatarUrl || fbUserData.photoURL || user.photoURL;
                            }
                        } catch (error) {
                            console.error('Error loading user data:', error);
                        }
                    }
                    
                    const userData = {
                        uid: user.uid,
                        displayName: user.displayName || 'User',
                        username: user.displayName || 'User',
                        email: user.email || '',
                        avatar: customAvatar || '../assets/user_male_80px.png'
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
                    
                    // Load users now that we have authentication (with caching)
                    loadUsers();
                    loadFriendRequests();
                    // Set up real-time avatar monitoring
                    setupAvatarMonitoring();
                    // Initialize enhanced presence system
                    initializePresence();
                    setupDisconnectHandler();
                    startSimplePresenceMonitoring();
                }
            }
        });

        // Also try to load users if we already have currentUserId set
        if (currentUserId) {
            // Small delay to let UI render first
            setTimeout(() => {
                loadUsers();
                loadFriendRequests();
                // Set up real-time avatar monitoring
                setupAvatarMonitoring();
            }, 100);
        }

        // Function to monitor real-time avatar changes
        function setupAvatarMonitoring() {
            if (!currentUserId) return;
            
            const userRef = ref(db, `users/${currentUserId}`);
            onValue(userRef, (snapshot) => {
                if (snapshot.exists()) {
                    const userData = snapshot.val();
                    // Prioritize custom avatar URL over Google photo
                    const newAvatar = userData.customAvatarUrl || userData.photoURL || userData.avatar || '../assets/user_male_80px.png';
                    
                    // Update current user avatar if it has changed
                    if (newAvatar !== currentUserAvatar) {
                        currentUserAvatar = newAvatar;
                        
                        // Update the profile display avatar immediately
                        const avatarElement = document.getElementById('currentUserAvatar');
                        if (avatarElement) {
                            avatarElement.src = getOptimizedAvatar(newAvatar);
                            avatarElement.onerror = function() {
                                this.onerror = null;
                                this.src = '../assets/user_male_80px.png';
                            };
                        }
                        
                        // Update localStorage with new avatar
                        if (currentUserData) {
                            currentUserData.avatar = newAvatar;
                            localStorage.setItem('firebaseUser', JSON.stringify(currentUserData));
                        }
                        
                        console.log('Avatar updated in real-time:', newAvatar);
                    }
                }
            });
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

        // Load friends and available users from Firebase (with enhanced caching)
        async function loadUsers() {
            if (!currentUserId) return;
            
            // Show loading state
            const loadingElement = document.getElementById('loadingContacts');
            if (loadingElement) loadingElement.style.display = 'block';
            
            try {
                // Check enhanced cache first
                const cachedUsers = getCachedData(`friends_${currentUserId}`);
                if (cachedUsers) {
                    console.log('Using cached friends data from localStorage');
                    renderUsers(cachedUsers);
                    if (loadingElement) loadingElement.style.display = 'none';
                    return;
                }
                
                console.log('Loading fresh friends data from Firebase');

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
                const now = Date.now();
                
                // Cache the data both in memory and localStorage
                usersCache = { allUsers, friendsData: userFriendsData };
                lastCacheTime = now;
                setCachedData(`friends_${currentUserId}`, usersCache);
                
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
                // Prioritize custom avatar URL over Google photo
                const avatar = userData.customAvatarUrl || userData.photoURL || userData.avatar || '../assets/user_male_80px.png';
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

        // Load friend requests (with caching)
        async function loadFriendRequests() {
            try {
                // Check cache first
                const cachedRequests = getCachedData(`requests_${currentUserId}`);
                if (cachedRequests) {
                    console.log('Using cached friend requests');
                    renderFriendRequests(cachedRequests);
                    return;
                }
                
                console.log('Loading fresh friend requests from Firebase');
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
                // Cache the requests data
                setCachedData(`requests_${currentUserId}`, requests);
                renderFriendRequests(requests);
                
            } catch (error) {
                console.error('Error loading friend requests:', error);
                document.getElementById('noPendingRequests').style.display = 'block';
            }
        }

        // Render friend requests (separate function for caching)
        function renderFriendRequests(requests) {
            const pendingContainer = document.getElementById('pendingRequests');
            const noPendingMessage = document.getElementById('noPendingRequests');
            
            pendingContainer.innerHTML = '';
            
            if (!requests) {
                noPendingMessage.style.display = 'block';
                return;
            }
            
            let hasPendingRequests = false;
            
            Object.entries(requests).forEach(([requesterId, requestData]) => {
                if (requestData.status === 'pending') {
                    hasPendingRequests = true;
                    const requestElement = createFriendRequestElement(requesterId, requestData);
                    pendingContainer.appendChild(requestElement);
                }
            });
            
            noPendingMessage.style.display = hasPendingRequests ? 'none' : 'block';
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

        // No status monitoring needed - removed status indicators

        // Load last message for a specific friend
        function loadLastMessageForFriend(friendElement, userId, displayName) {
            if (!currentUserId || !userId) return;
            
            const lastMsgDiv = friendElement.querySelector('.last-message');
            // Ensure both IDs are strings for proper comparison
            const currentId = String(currentUserId);
            const otherId = String(userId);
            const chatId = `${currentId < otherId ? currentId : otherId}_${currentId < otherId ? otherId : currentId}`;
            const chatRef = ref(db, `chats/${chatId}`);
            const lastMsgQuery = query(chatRef, limitToLast(1));

            onValue(lastMsgQuery, snapshot => {
                if (snapshot.exists()) {
                    snapshot.forEach(childSnap => {
                        const msg = childSnap.val();
                        
                        // Format message with sender name
                        let messageText = '';
                        if (String(msg.sender_id) === String(currentUserId)) {
                            messageText = `You: ${msg.message}`; // Just show the message without "You:"
                        } else {
                            messageText = `${msg.message}`;
                        }
                        
                        lastMsgDiv.textContent = messageText;

                        // Style based on read status - bold if unread message from other user
                        if (String(msg.sender_id) !== String(currentUserId) && !msg.is_read) {
                            lastMsgDiv.classList.remove('text-muted');
                            lastMsgDiv.classList.add('fw-bold', 'text-dark');
                            
                            // Also make the whole friend item stand out for unread
                            friendElement.style.background = '#f8f9ff';
                            friendElement.style.borderLeft = '4px solid #4f8cff';
                        } else {
                            lastMsgDiv.classList.remove('fw-bold', 'text-dark');
                            lastMsgDiv.classList.add('text-muted');
                            
                            // Remove unread styling
                            friendElement.style.background = '#fff';
                            friendElement.style.borderLeft = 'none';
                        }
                        
                        // Store timestamp for sorting
                        friendElement.setAttribute('data-last-message-time', msg.timestamp || 0);
                    });
                } else {
                    lastMsgDiv.textContent = 'No messages yet';
                    lastMsgDiv.classList.remove('fw-bold', 'text-dark');
                    lastMsgDiv.classList.add('text-muted');
                    friendElement.setAttribute('data-last-message-time', 0);
                }
                
                // Re-sort friends list by last message time
                sortFriendsByLastMessage();
            });
        }
        
        // Sort friends by last message timestamp
        function sortFriendsByLastMessage() {
            const friendsList = document.getElementById('friendsList');
            const friends = Array.from(friendsList.children);
            
            friends.sort((a, b) => {
                const timeA = parseInt(a.getAttribute('data-last-message-time') || 0);
                const timeB = parseInt(b.getAttribute('data-last-message-time') || 0);
                return timeB - timeA; // Most recent first
            });
            
            // Re-append in sorted order
            friends.forEach(friend => friendsList.appendChild(friend));
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
            div.style.minHeight = '80px';
            div.style.padding = '16px';
            div.onclick = () => openChat(userId, displayName, avatar);
            
            const optimizedAvatar = getOptimizedAvatar(avatar);
            
            div.innerHTML = `
                <div>
                    <img src="${optimizedAvatar}" class="rounded-circle border border-primary" width="50" height="50" alt="${displayName}"
                         onerror="this.onerror=null; this.src='../assets/user_male_80px.png';">
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h6 class="mb-0">${displayName}</h6>
                    </div>
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted">@${username}</small>
                    </div>
                    <div class="last-message text-muted small mt-1">Loading messages...</div>
                </div>
            `;
            
            // Load last message for this friend after element is created
            loadLastMessageForFriend(div, userId, displayName);
            
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
                <div>
                    <img src="${optimizedAvatar}" class="rounded-circle border border-primary" width="40" height="40" alt="${displayName}"
                         onerror="this.onerror=null; this.src='../assets/user_male_80px.png';">
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-0">${displayName}</h6>
                    <small class="text-muted">@${username}</small>
                </div>
                ${buttonHTML}
            `;
            return div;
        }

        // Legacy functions removed - no status indicators needed

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

        // Simplified user status update
        function updateUserStatus() {
            if (!currentUserId) return;
            
            const userStatusRef = ref(db, `users/${currentUserId}/status`);
            
            set(userStatusRef, {
                online: true, // Simply online when active
                lastSeen: serverTimestamp(),
                name: currentUserName,
                avatar: currentUserAvatar,
                timestamp: Date.now(),
                heartbeat: Date.now() // Basic heartbeat for activity detection
            }).catch(console.error);
        }

        // Enhanced presence system with grace periods
        let presenceTimeout = null;
        let isInitialized = false;

        function initializePresence() {
            if (isInitialized || !currentUserId) return;
            isInitialized = true;
            
            // Set initial online status
            updateUserStatus(true);
            
            // Regular heartbeat every 30 seconds (more frequent)
            const statusInterval = setInterval(() => {
                updateUserStatus();
            }, 30000);
            
            // Clear any existing timeout
            if (presenceTimeout) {
                clearTimeout(presenceTimeout);
                presenceTimeout = null;
            }
        }

        // Initialize presence system after user is confirmed
        if (currentUserId) {
            initializePresence();
            // Start simple presence monitoring
            startSimplePresenceMonitoring();
        }

        // Handle page visibility with better timing
        document.addEventListener('visibilitychange', () => {
            isPageVisible = !document.hidden;
            
            if (isPageVisible) {
                // Page became visible - immediately set as online
                trackUserActivity();
                updateUserStatus(true);
                
                // Clear any pending away status
                if (presenceTimeout) {
                    clearTimeout(presenceTimeout);
                    presenceTimeout = null;
                }
            } else {
                // Page became hidden - set away after longer delay
                presenceTimeout = setTimeout(() => {
                    if (!isPageVisible && currentUserId) {
                        const userStatusRef = ref(db, `users/${currentUserId}/status`);
                        set(userStatusRef, {
                            online: true, // Still online, just away
                            status: 'away',
                            lastSeen: serverTimestamp(),
                            name: currentUserName,
                            avatar: currentUserAvatar,
                            timestamp: Date.now(),
                            heartbeat: Date.now()
                        }).catch(console.error);
                    }
                }, 60000); // Increased to 60 seconds delay
            }
        });

        // Enhanced disconnect handler with grace period
        function setupDisconnectHandler() {
            if (!currentUserId) return;
            
            const disconnectRef = onDisconnect(ref(db, `users/${currentUserId}/status`));
            // Set offline status only after network disconnect (not immediate page reload)
            disconnectRef.set({
                online: false,
                status: 'offline',
                lastSeen: serverTimestamp(),
                name: currentUserName,
                avatar: currentUserAvatar,
                timestamp: Date.now(),
                heartbeat: Date.now()
            }).catch(console.error);
        }
        
        if (currentUserId) {
            setupDisconnectHandler();
        }

        // Simplified presence tracking - just basic activity monitoring
        const monitoredUsers = new Set();
        
        function startSimplePresenceMonitoring() {
            // Simple presence check every 30 seconds
            presenceCheckInterval = setInterval(() => {
                // Just update our own heartbeat - let others handle their own presence
                if (currentUserId) {
                    updateUserStatus();
                }
            }, 30000);
        }
        
        // No status UI updates needed - removed all status indicators

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

        // Enhanced cleanup on page unload
        function cleanup() {
            // Clear any pending timeouts
            if (presenceTimeout) {
                clearTimeout(presenceTimeout);
            }
            
            // Clear presence monitoring interval
            if (presenceCheckInterval) {
                clearInterval(presenceCheckInterval);
            }
            
            // Don't immediately set offline - let Firebase disconnect handler manage it
            // This prevents showing offline during quick page reloads
            console.log('Page unloading - disconnect handler will manage status');
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