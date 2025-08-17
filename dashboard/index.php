<?php
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?msg=' . urlencode('Please log in to access the dashboard.'));
    exit;
}

// REMOVE: All PHP status management - Firebase handles this now

// Fetch current user's avatar_url for welcome message
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
    <div class="container mt-5" style="max-width: 90vw;">
        <?php if (isset($_GET['msg']) && $_GET['msg']): ?>
            <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
                <?php echo htmlspecialchars($_GET['msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <div class="row mb-3">
            <div class="col d-flex justify-content-end align-items-center">
                <div class="bg-white shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2" style="min-width: 50px;">
                    <img src="<?php echo $current_avatar; ?>" alt="User" width="32" height="32" class="rounded-circle border border-primary">
                    <span class="fw-semibold text-primary"><?php echo htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']); ?></span>
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
                    <?php
                    $current_user_id = $_SESSION['user_id'];
                    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

                    // First: check if there are any friends or requests at all
                    $check_sql = "SELECT COUNT(*) as total 
                        FROM friends 
                        WHERE (user_id = ? OR friend_id = ?)";
                    $check_stmt = $conn->prepare($check_sql);
                    $check_stmt->bind_param('ii', $current_user_id, $current_user_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    $total_connections = $check_result->fetch_assoc()['total'];
                    $check_stmt->close();

                    // SIMPLIFIED query - remove PHP status fields, Firebase will handle status
                    $sql = "SELECT u.id, u.display_name, u.username, u.avatar_url
                            FROM users u
                            JOIN friends f ON (
                                (f.user_id = ? AND f.friend_id = u.id) 
                                OR (f.friend_id = ? AND f.user_id = u.id)
                            )
                            WHERE f.status = 'accepted' AND u.id != ?";

                    if ($search !== '') {
                        $sql .= " AND (u.display_name LIKE ? OR u.username LIKE ?)";
                    }
                    $sql .= " ORDER BY u.display_name ASC";

                    $stmt = $conn->prepare($sql);
                    if ($search !== '') {
                        $search_param = "%$search%";
                        $stmt->bind_param('iiiss', $current_user_id, $current_user_id, $current_user_id, $search_param, $search_param);
                    } else {
                        $stmt->bind_param('iii', $current_user_id, $current_user_id, $current_user_id);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 0) {
                        if ($total_connections == 0): ?>
                            <div class="text-center text-muted py-4">
                                <p>You don't have any buddies yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <p>No accepted buddies yet. Check your pending requests!</p>
                            </div>
                        <?php endif;
                    } else {
                        while ($user = $result->fetch_assoc()):
                            $avatar = ($user['avatar_url'] && trim($user['avatar_url']) !== '' && $user['avatar_url'] !== 'null')
                                ? $user['avatar_url']
                                : '../assets/user_male_80px.png';
                        ?>
<form id="chat-form-<?php echo $user['id']; ?>" action="buddyroom.php" method="POST" style="margin:0; padding:0;">
    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
    <a href="#" 
       onclick="event.preventDefault(); document.getElementById('chat-form-<?php echo $user['id']; ?>').submit();" 
       class="list-group-item list-group-item-action d-flex align-items-center gap-3"
       data-contact-id="<?php echo $user['id']; ?>">

        <div class="position-relative">
            <img src="<?php echo $avatar; ?>"
                class="rounded-circle border border-primary"
                width="50" height="50"
                alt="<?php echo htmlspecialchars($user['display_name']); ?>">
            <!-- Status indicator -->
            <span class="status-indicator status-offline position-absolute firebase-status" 
                  style="bottom: 2px; right: 2px;" 
                  title="Loading..."></span>
        </div>

        <div class="flex-grow-1">
            <div class="d-flex align-items-center justify-content-between mb-1">
                <h6 class="mb-0"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                <small class="last-seen-time firebase-last-seen">
                    Loading...
                </small>
            </div>
            <div class="d-flex align-items-center justify-content-between">
                <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                <span class="badge bg-secondary badge-sm firebase-badge">
                    Loading
                </span>
            </div>
            <div class="last-message text-dark-emphasis small mt-1"></div>
        </div>
    </a>
</form>

                    <?php endwhile;
                    }
                    $stmt->close();
                    ?>
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
                        <?php
                        $current_user_id = $_SESSION['user_id'];
                        $sql = "SELECT f.id AS request_id, u.id AS user_id, u.display_name, u.username, u.avatar_url
                                FROM friends f 
                                JOIN users u ON f.user_id = u.id 
                                WHERE f.friend_id = ? AND f.status = 'pending'";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('i', $current_user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0):
                            while ($row = $result->fetch_assoc()):
                                $avatar = ($row['avatar_url'] && trim($row['avatar_url']) !== '' && $row['avatar_url'] !== 'null') ? $row['avatar_url'] : '../assets/user_male_80px.png';
                        ?>
                                <div class="list-group-item d-flex align-items-center gap-3 mb-2 bg-warning-subtle" data-modal-user-id="<?php echo $row['user_id']; ?>">
                                    <div class="position-relative">
                                        <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="40" height="40" alt="<?php echo htmlspecialchars($row['display_name']); ?>">
                                        <span class="status-indicator status-offline position-absolute firebase-status-modal" 
                                              style="bottom: 0; right: 0;" 
                                              title="Loading..."></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($row['display_name']); ?></h6>
                                        <small class="text-muted">@<?php echo htmlspecialchars($row['username']); ?> • <span class="firebase-status-text">Loading...</span></small>
                                    </div>
                                    <form method="post" action="friend_action.php" class="ms-2">
                                        <input type="hidden" name="request_id" value="<?php echo $row['request_id']; ?>">
                                        <button type="submit" name="action" value="accept" class="btn btn-success btn-sm rounded-pill me-1">Accept</button>
                                        <button type="submit" name="action" value="decline" class="btn btn-danger btn-sm rounded-pill">Decline</button>
                                    </form>
                                </div>
                        <?php endwhile;
                        else:
                            echo '<div class="text-muted text-center">No pending requests.</div>';
                        endif;
                        $stmt->close();
                        ?>
                        <hr>
                        <!-- New Chat Section with Firebase status -->
                        <?php
                        $sql = "SELECT id, display_name, username, avatar_url FROM users WHERE id != ? ORDER BY display_name ASC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('i', $current_user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($user = $result->fetch_assoc()):
                            $avatar = ($user['avatar_url'] && trim($user['avatar_url']) !== '' && $user['avatar_url'] !== 'null') ? $user['avatar_url'] : '../assets/user_male_80px.png';
                            
                            // Check buddy status
                            $status = null;
                            $friend_stmt = $conn->prepare("SELECT status FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?) LIMIT 1");
                            $friend_stmt->bind_param('iiii', $current_user_id, $user['id'], $user['id'], $current_user_id);
                            $friend_stmt->execute();
                            $friend_stmt->bind_result($friend_status);
                            if ($friend_stmt->fetch()) {
                                $status = $friend_status;
                            }
                            $friend_stmt->close();
                            
                            // Skip if already buddies
                            if ($status === 'accepted') continue;
                            
                            // Skip if user already sent you a buddy request
                            $pending_to_me_stmt = $conn->prepare("SELECT id FROM friends WHERE user_id=? AND friend_id=? AND status='pending' LIMIT 1");
                            $pending_to_me_stmt->bind_param('ii', $user['id'], $current_user_id);
                            $pending_to_me_stmt->execute();
                            $pending_to_me_stmt->store_result();
                            if ($pending_to_me_stmt->num_rows > 0) {
                                $pending_to_me_stmt->close();
                                continue;
                            }
                            $pending_to_me_stmt->close();
                        ?>
                            <div class="list-group-item d-flex align-items-center gap-3 mb-2" data-modal-user-id="<?php echo $user['id']; ?>">
                                <div class="position-relative">
                                    <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="40" height="40" alt="<?php echo htmlspecialchars($user['display_name']); ?>">
                                    <span class="status-indicator status-offline position-absolute firebase-status-modal" 
                                          style="bottom: 0; right: 0;" 
                                          title="Loading..."></span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?> • <span class="firebase-status-text">Loading...</span></small>
                                </div>
                                <?php if ($status === 'pending'): ?>
                                    <button class="btn btn-secondary btn-sm rounded-pill" disabled>Pending</button>
                                <?php else: ?>
                                    <form method="post" action="friend_action.php" class="ms-2">
                                        <input type="hidden" name="friend_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill">ADD BUDDY</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endwhile;
                        $stmt->close(); ?>
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

        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        const currentUserName = <?php echo json_encode($_SESSION['display_name'] ?? $_SESSION['username']); ?>;
        const currentUserAvatar = <?php echo json_encode($current_avatar); ?>;

        let lastActivity = Date.now();
        let isUserActive = true;
        let isPageVisible = !document.hidden;

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

        // Monitor all users' status
        const allUserIds = [
            ...Array.from(document.querySelectorAll('[data-contact-id]')).map(el => el.getAttribute('data-contact-id')),
            ...Array.from(document.querySelectorAll('[data-modal-user-id]')).map(el => el.getAttribute('data-modal-user-id'))
        ];
        
        const uniqueUserIds = [...new Set(allUserIds)];
        
        uniqueUserIds.forEach(userId => {
            if (userId && userId !== currentUserId.toString()) {
                const userStatusRef = ref(db, `users/${userId}/status`);
                onValue(userStatusRef, (snapshot) => {
                    const status = snapshot.val();
                    updateStatusUI(userId, status);
                });
            }
        });

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