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

// Update last_seen and status for the current user on every page load
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET last_seen = NOW(), status = 'online' WHERE id = ?");
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// Set all users to offline if their last_seen is older than 1 minute (run on every dashboard load)

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
    <title>Gwez - Live-Chat</title>
</head>
<style>
    body {
        font-family: 'Segoe UI', 'Arial', sans-serif;
        background: linear-gradient(135deg, #e3f0ff 0%, #f9f9f9 100%);
    }
</style>
<body class="bg-light p-3">
    <?php include_once 'navbar.php';?>
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
                </div>
            </div>
        </div>
            <div class="col-md-8 col-lg-6 w-100" style="max-width: 90vw;">
                <div class="card shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">CONTACTS</h5>
                        <div class="d-flex flex-wrap align-items-center mb-2 justify-content-between">
                            <form method="get" class="d-flex align-items-center gap-2 mb-0" style="max-width: 350px;">
                                <input type="text" name="search" id="searchContacts" class="form-control shadow-sm rounded-pill px-3" style="max-width: 220px;" placeholder="🔍 Search contacts..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                <button type="submit" class="btn btn-outline-primary rounded-pill px-3">Search</button>
                            </form>
                            <div class="d-flex gap-2 flex-wrap flex-md-nowrap w-100 justify-content-md-end justify-content-center mt-2 mt-md-0">
                                <button class="btn btn-gradient px-4 py-2 fw-semibold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#newChatModal">
                                    <i class="bi bi-plus-circle me-1"></i>
                                </button>
                                <!-- Friend Requests button removed -->
                            </div>
                        </div>
    <!-- Friend Requests Modal removed -->
                        </div>
                        <div class="list-group list-group-flush" id="contactsList" style="min-height: 100px;">
                        <?php
                        // CONTACTS LIST: Show only accepted friends, with search filter
                        $current_user_id = $_SESSION['user_id'];
                        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                        $sql = "SELECT u.id, u.display_name, u.username, u.avatar_url, u.status FROM users u
                                JOIN friends f ON (
                                    (f.user_id = ? AND f.friend_id = u.id) OR (f.friend_id = ? AND f.user_id = u.id)
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
                        while ($user = $result->fetch_assoc()):
                            $avatar = ($user['avatar_url'] && trim($user['avatar_url']) !== '' && $user['avatar_url'] !== 'null') ? $user['avatar_url'] : '../assets/user_male_80px.png';
                            $is_online = ($user['status'] === 'online');
                        ?>
                            <a href="chatroom.php?user_id=<?php echo $user['id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                                <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="50" height="50" alt="<?php echo htmlspecialchars($user['display_name']); ?>">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <h6 class="mb-0 me-2"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                                        <span class="badge bg-<?php echo $is_online ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                </div>
                            </a>
                        <?php endwhile; $stmt->close(); ?>
                        </div>
    <!-- Search reload JS removed. Manual search only. -->
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
                <!-- Friend Requests Section (pending requests sent to current user) -->
                <h6 class="mb-3 text-center text-warning"><i class="bi bi-person-check me-2"></i>Pending Friend Requests</h6>
                <?php
                $current_user_id = $_SESSION['user_id'];
                $sql = "SELECT f.id AS request_id, u.id AS user_id, u.display_name, u.username, u.avatar_url FROM friends f JOIN users u ON f.user_id = u.id WHERE f.friend_id = ? AND f.status = 'pending'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $current_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $avatar = ($row['avatar_url'] && trim($row['avatar_url']) !== '' && $row['avatar_url'] !== 'null') ? $row['avatar_url'] : '../assets/user_male_80px.png';
                ?>
                    <div class="list-group-item d-flex align-items-center gap-3 mb-2 bg-warning-subtle">
                        <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="40" height="40" alt="<?php echo htmlspecialchars($row['display_name']); ?>">
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?php echo htmlspecialchars($row['display_name']); ?></h6>
                            <small class="text-muted">@<?php echo htmlspecialchars($row['username']); ?></small>
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
                <!-- New Chat Section (all users except self, with friend request status) -->
                <?php
                $sql = "SELECT id, display_name, username, avatar_url FROM users WHERE id != ? ORDER BY display_name ASC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $current_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($user = $result->fetch_assoc()):
                    $avatar = ($user['avatar_url'] && trim($user['avatar_url']) !== '' && $user['avatar_url'] !== 'null') ? $user['avatar_url'] : '../assets/user_male_80px.png';
                    // Check friend status
                    $status = null;
                    $friend_stmt = $conn->prepare("SELECT status FROM friends WHERE (user_id=? AND friend_id=?) OR (user_id=? AND friend_id=?) LIMIT 1");
                    $friend_stmt->bind_param('iiii', $current_user_id, $user['id'], $user['id'], $current_user_id);
                    $friend_stmt->execute();
                    $friend_stmt->bind_result($friend_status);
                    if ($friend_stmt->fetch()) {
                        $status = $friend_status;
                    }
                    $friend_stmt->close();
                    // If accepted, skip user (already friends)
                    if ($status === 'accepted') continue;
                    // If user already sent you a request, skip (already shown above)
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
                        <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="40" height="40" alt="<?php echo htmlspecialchars($user['display_name']); ?>">
                        <div class="flex-grow-1">
                            <h6 class="mb-0"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                        </div>
                        <?php if ($status === 'pending'): ?>
                            <button class="btn btn-secondary btn-sm rounded-pill" disabled>Pending</button>
                        <?php else: ?>
                            <form method="post" action="friend_action.php" class="ms-2">
                                <input type="hidden" name="friend_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill">ADD</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; $stmt->close(); ?>
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
    .btn-gradient:hover, .btn-gradient:focus {
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
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <!-- JS for New Chat Modal removed to allow PHP rendering of user list with ADD button -->
    <script type="module">
import { initializeApp } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-app.js";
import { getDatabase, ref, query, limitToLast, onValue } from "https://www.gstatic.com/firebasejs/9.23.0/firebase-database.js";

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

const contacts = Array.from(document.querySelectorAll('[data-contact-id]'));
const contactData = [];
let loadedCount = 0;

contacts.forEach(el => {
  const contactId = el.getAttribute('data-contact-id');
  const chatRef = ref(db, `chats/${Math.min(currentUserId, contactId)}_${Math.max(currentUserId, contactId)}`);
  const lastMsgQuery = query(chatRef, limitToLast(1));
  onValue(lastMsgQuery, snapshot => {
    let lastTimestamp = 0;
    snapshot.forEach(childSnap => {
      const msg = childSnap.val();
      lastTimestamp = msg.timestamp || 0;
      const lastMsgDiv = el.querySelector('.last-message');
      if (lastMsgDiv) {
        lastMsgDiv.textContent = (msg.sender_id == currentUserId ? 'You: ' : '') + msg.message;
        if (msg.sender_id != currentUserId && !msg.is_read) {
          lastMsgDiv.classList.add('fw-bold');
          lastMsgDiv.classList.remove('text-dark-emphasis');
        } else {
          lastMsgDiv.classList.remove('fw-bold');
          lastMsgDiv.classList.add('text-dark-emphasis');
        }
      }
    });
    contactData.push({el, lastTimestamp});
    loadedCount++;
    // When all contacts loaded, sort and re-append
    if (loadedCount === contacts.length) {
      contactData.sort((a, b) => b.lastTimestamp - a.lastTimestamp);
      const contactsList = document.getElementById('contactsList');
      contactData.forEach(({el}) => contactsList.appendChild(el));
    }
  });
});
</script>
</body>

</html>