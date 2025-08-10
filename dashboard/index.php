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

// Enhanced online/offline status management
if (isset($_SESSION['user_id'])) {
    // Update current user's status to online and last_seen
    $stmt = $conn->prepare("UPDATE users SET last_seen = NOW(), status = 'online' WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Set users to offline if their last_seen is older than 2 minutes
    $offline_threshold = 2; // minutes
    $stmt = $conn->prepare("UPDATE users SET status = 'offline' WHERE last_seen < DATE_SUB(NOW(), INTERVAL ? MINUTE) AND status != 'offline'");
    $stmt->bind_param('i', $offline_threshold);
    $stmt->execute();
    $stmt->close();
}

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
                            <input type="text" name="search" id="searchContacts" class="form-control shadow-sm rounded-pill px-3" style="max-width: 220px;" placeholder="🔍 Search buddy..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button type="submit" class="btn btn-outline-primary rounded-pill px-3">Search</button>
                        </form>
                        <div class="d-flex gap-2">
                            <button class="btn btn-gradient px-4 py-2 fw-semibold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#newChatModal">
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

                    // Enhanced query with last_seen information
                    $sql = "SELECT u.id, u.display_name, u.username, u.avatar_url, u.status, u.last_seen,
                                   TIMESTAMPDIFF(MINUTE, u.last_seen, NOW()) as minutes_offline
                            FROM users u
                            JOIN friends f ON (
                                (f.user_id = ? AND f.friend_id = u.id) 
                                OR (f.friend_id = ? AND f.user_id = u.id)
                            )
                            WHERE f.status = 'accepted' AND u.id != ?";

                    if ($search !== '') {
                        $sql .= " AND (u.display_name LIKE ? OR u.username LIKE ?)";
                    }
                    $sql .= " ORDER BY u.status DESC, u.last_seen DESC, u.display_name ASC";

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
                            
                            $is_online = ($user['status'] === 'online');
                            $is_away = ($user['status'] === 'away');
                            $minutes_offline = $user['minutes_offline'];
                            
                            // Format last seen time
                            $last_seen_text = '';
                            if (!$is_online) {
                                if ($minutes_offline < 60) {
                                    $last_seen_text = $minutes_offline . 'm ago';
                                } else if ($minutes_offline < 1440) { // less than 24 hours
                                    $hours = floor($minutes_offline / 60);
                                    $last_seen_text = $hours . 'h ago';
                                } else {
                                    $days = floor($minutes_offline / 1440);
                                    $last_seen_text = $days . 'd ago';
                                }
                            }
                        ?>
                            <a href="chatroom.php?user_id=<?php echo $user['id']; ?>"
                                class="list-group-item list-group-item-action d-flex align-items-center gap-3"
                                data-contact-id="<?php echo $user['id']; ?>">
                                <div class="position-relative">
                                    <img src="<?php echo $avatar; ?>"
                                        class="rounded-circle border border-primary"
                                        width="50" height="50"
                                        alt="<?php echo htmlspecialchars($user['display_name']); ?>">
                                    <!-- Status indicator overlay -->
                                    <span class="status-indicator status-<?php echo $user['status']; ?> position-absolute" 
                                          style="bottom: 2px; right: 2px;" 
                                          title="<?php echo ucfirst($user['status']); ?>"></span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                                        <small class="last-seen-time">
                                            <?php echo $is_online ? 'Online' : $last_seen_text; ?>
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                        <span class="badge bg-<?php echo $is_online ? 'success' : ($is_away ? 'warning' : 'secondary'); ?> badge-sm">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </div>
                                    <div class="last-message text-dark-emphasis small mt-1"></div>
                                </div>
                            </a>
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
                        $sql = "SELECT f.id AS request_id, u.id AS user_id, u.display_name, u.username, u.avatar_url, u.status, u.last_seen 
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
                                $is_online = ($row['status'] === 'online');
                        ?>
                                <div class="list-group-item d-flex align-items-center gap-3 mb-2 bg-warning-subtle">
                                    <div class="position-relative">
                                        <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="40" height="40" alt="<?php echo htmlspecialchars($row['display_name']); ?>">
                                        <span class="status-indicator status-<?php echo $row['status']; ?> position-absolute" 
                                              style="bottom: 0; right: 0;" 
                                              title="<?php echo ucfirst($row['status']); ?>"></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($row['display_name']); ?></h6>
                                        <small class="text-muted">@<?php echo htmlspecialchars($row['username']); ?> • <?php echo $is_online ? 'Online' : 'Offline'; ?></small>
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
                        <!-- New Chat Section with status indicators -->
                        <?php
                        $sql = "SELECT id, display_name, username, avatar_url, status, last_seen FROM users WHERE id != ? ORDER BY status DESC, display_name ASC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('i', $current_user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($user = $result->fetch_assoc()):
                            $avatar = ($user['avatar_url'] && trim($user['avatar_url']) !== '' && $user['avatar_url'] !== 'null') ? $user['avatar_url'] : '../assets/user_male_80px.png';
                            $is_online = ($user['status'] === 'online');
                            
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
                            <div class="list-group-item d-flex align-items-center gap-3 mb-2">
                                <div class="position-relative">
                                    <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="40" height="40" alt="<?php echo htmlspecialchars($user['display_name']); ?>">
                                    <span class="status-indicator status-<?php echo $user['status']; ?> position-absolute" 
                                          style="bottom: 0; right: 0;" 
                                          title="<?php echo ucfirst($user['status']); ?>"></span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?> • <?php echo $is_online ? 'Online' : 'Offline'; ?></small>
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

    <style>
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
    </style>
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
            set
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

        // Enhanced heartbeat system for online status
        function updateHeartbeat() {
            // Update Firebase presence
            const userStatusRef = ref(db, `users/${currentUserId}/status`);
            set(userStatusRef, {
                online: true,
                lastSeen: serverTimestamp()
            });
            
            // Update PHP backend
            fetch('update_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'heartbeat'})
            }).catch(console.error);
        }

        // Send heartbeat every 30 seconds
        updateHeartbeat();
        const heartbeatInterval = setInterval(updateHeartbeat, 30000);

        // Handle page visibility changes
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // User switched tabs/minimized - set to away after 5 minutes
                setTimeout(() => {
                    if (document.hidden) {
                        fetch('update_status.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({action: 'away'})
                        });
                    }
                }, 300000); // 5 minutes
            } else {
                // User is back - set to online immediately
                updateHeartbeat();
            }
        });

        // Handle page unload
        window.addEventListener('beforeunload', () => {
            // Quick sync request to set offline
            navigator.sendBeacon('update_status.php', JSON.stringify({action: 'offline'}));
        });

        // Load contacts with Firebase integration
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
                onlyOnce: true
            });
        });

        // Auto-refresh page every 2 minutes to sync status
        setInterval(() => {
            window.location.reload();
        }, 120000);
    </script>
</body>
</html>