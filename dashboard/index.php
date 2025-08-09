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
        <div class="row mb-3">
            <div class="col d-flex justify-content-end align-items-center">
                <div class="bg-white shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2" style="min-width: 180px;">
                    <img src="<?php echo $current_avatar; ?>" alt="User" width="32" height="32" class="rounded-circle border border-primary">
                    <span class="fw-semibold text-primary">Welcome, <?php echo htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']); ?>!</span>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 w-100" style="max-width: 90vw;">
                <div class="card shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">CONTACTS</h5>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <input type="text" id="searchContacts" class="form-control w-75 shadow-sm rounded-pill px-3" style="max-width: 350px;" placeholder="🔍 Search contacts...">
                            <button class="btn btn-gradient ms-2 px-4 py-2 fw-semibold rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#newChatModal">
                                <i class="bi bi-plus-circle me-1"></i> Start New Chat
                            </button>
                        </div>
                        <div class="list-group list-group-flush" id="contactsList" style="min-height: 100px;">
                        <?php
                        $current_user_id = $_SESSION['user_id'];
                        $sql = "SELECT id, display_name, username, last_seen, status,
                            id, display_name, username, last_seen, status FROM users WHERE id != ?";
                        // Search filter
                        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                        if ($search !== '') {
                            $sql .= " AND (display_name LIKE ? OR username LIKE ?)";
                        }
                        $sql .= " ORDER BY display_name ASC LIMIT 100";
                        $stmt = $conn->prepare($sql);
                        if ($search !== '') {
                            $search_param = "%$search%";
                            $stmt->bind_param('iss', $current_user_id, $search_param, $search_param);
                        } else {
                            $stmt->bind_param('i', $current_user_id);
                        }
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($user = $result->fetch_assoc()):
                            $is_online = (strtotime($user['last_seen']) > (time() - 60)); // 1 minute
                            if (!$is_online && $user['status'] !== 'offline') {
                                $offline_stmt = $conn->prepare("UPDATE users SET status = 'offline' WHERE id = ?");
                                $offline_stmt->bind_param('i', $user['id']);
                                $offline_stmt->execute();
                                $offline_stmt->close();
                                $user['status'] = 'offline';
                            }
                            // Fetch avatar_url from users table for each contact
                            $avatar = '../assets/user_male_80px.png';
                            $avatar_stmt = $conn->prepare('SELECT avatar_url FROM users WHERE id = ?');
                            $avatar_stmt->bind_param('i', $user['id']);
                            $avatar_stmt->execute();
                            $avatar_stmt->bind_result($avatar_url);
                            if ($avatar_stmt->fetch() && $avatar_url && trim($avatar_url) !== '' && $avatar_url !== 'null') {
                                $avatar = htmlspecialchars($avatar_url);
                            }
                            $avatar_stmt->close();
                        ?>
                            <a href="chatroom.php?user_id=<?php echo $user['id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3" data-contact-id="<?php echo $user['id']; ?>">
                                <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="50" height="50" alt="<?php echo htmlspecialchars($user['display_name']); ?>">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="mb-0 me-2"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                                        <span class="badge bg-<?php echo ($user['status'] === 'online') ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                    <div class="text-truncate small last-message text-dark-emphasis"></div>
                                </div>
                            </a>
                        <?php endwhile; $stmt->close(); ?>
                        </div>
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
            <div id="newUsersList"></div>
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
    <script>
    // New Chat Modal logic
    const searchNewUsers = document.getElementById('searchNewUsers');
    const newUsersList = document.getElementById('newUsersList');
    let allNewUsers = [];

    function renderNewUsers(users) {
        allNewUsers = users;
        displayNewUsers(users);
    }
    function displayNewUsers(users) {
        let html = '';
        users.forEach(user => {
            // Use custom avatar if set and not empty/null, else fallback
            const avatar = (user.avatar_url && user.avatar_url.trim() !== '' && user.avatar_url !== 'null') ? user.avatar_url : '../assets/user_male_80px.png';
            html += `<a href="chatroom.php?user_id=${user.id}" class="list-group-item list-group-item-action d-flex align-items-center gap-3 mb-2">
                <img src="${avatar}" class="rounded-circle border border-primary" width="40" height="40" alt="${user.display_name}">
                <div class="flex-grow-1">
                    <h6 class="mb-0">${user.display_name}</h6>
                    <small class="text-muted">@${user.username}</small>
                </div>
            </a>`;
        });
        newUsersList.innerHTML = html || '<div class="text-muted text-center">No users found.</div>';
    }
    function fetchNewUsers(query = '') {
        fetch('getnochat.php?q=' + encodeURIComponent(query))
            .then(res => res.json())
            .then(users => renderNewUsers(users));
    }
    searchNewUsers && searchNewUsers.addEventListener('input', function() {
        fetchNewUsers(this.value.trim());
    });
    document.getElementById('newChatModal').addEventListener('show.bs.modal', function() {
        searchNewUsers.value = '';
        fetchNewUsers();
    });
    </script>
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