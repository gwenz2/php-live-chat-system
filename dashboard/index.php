<?php
require_once '../db.php';
require_once 'update_status.php';

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
require_once 'update_status.php';

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
    <div class="container mt-4" style="max-width: 90vw;">
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
                            (SELECT message_text FROM messages
                                WHERE (sender_id = users.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = users.id)
                                ORDER BY sent_at DESC LIMIT 1) AS last_message,
                            (SELECT is_read FROM messages
                                WHERE (sender_id = users.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = users.id)
                                ORDER BY sent_at DESC LIMIT 1) AS last_is_read,
                            (SELECT sender_id FROM messages
                                WHERE (sender_id = users.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = users.id)
                                ORDER BY sent_at DESC LIMIT 1) AS last_sender_id,
                            (SELECT sent_at FROM messages
                                WHERE (sender_id = users.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = users.id)
                                ORDER BY sent_at DESC LIMIT 1) AS last_message_time
                            FROM users WHERE id != ?
                            AND (
                                (SELECT COUNT(*) FROM messages WHERE (sender_id = users.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = users.id)) > 0
                            )
                            ORDER BY last_message_time IS NULL, last_message_time DESC";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('iiiiiiiiiii', $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($user = $result->fetch_assoc()):
                            $is_online = (strtotime($user['last_seen']) > (time() - 60)); // 1 minute
                            // If user is not online, update their status to offline in DB if needed
                            if (!$is_online && $user['status'] !== 'offline') {
                                $offline_stmt = $conn->prepare("UPDATE users SET status = 'offline' WHERE id = ?");
                                $offline_stmt->bind_param('i', $user['id']);
                                $offline_stmt->execute();
                                $offline_stmt->close();
                                $user['status'] = 'offline';
                            }
                            //$avatar = $user['avatar_url'] ? htmlspecialchars($user['avatar_url']) : '../assets/user_male_80px.png';
                        ?>
                            <a href="chatroom.php?user_id=<?php echo $user['id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                                <img src="<?php echo $avatar; ?>" class="rounded-circle border border-primary" width="50" height="50" alt="<?php echo htmlspecialchars($user['display_name']); ?>">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="mb-0 me-2"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                                        <span class="badge bg-<?php echo ($user['status'] === 'online') ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                    <?php if ($user['last_message']): ?>
                                        <div class="text-truncate small <?php echo ($user['last_is_read'] == 0) ? 'fw-bold' : 'text-dark-emphasis'; ?>">
                                            <?php if ($user['last_sender_id'] == $current_user_id): ?>
                                                <span class="text-primary">You: </span>
                                            <?php endif; ?>
                                             <?php echo htmlspecialchars($user['last_message']); ?>
                                        </div>
                                    <?php endif; ?>
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
    // AJAX polling for dashboard contacts
    const contactsList = document.getElementById('contactsList');
    const searchInput = document.getElementById('searchContacts');
    let allContacts = [];

    function renderContacts(users) {
        allContacts = users;
        displayContacts(users);
    }

    function displayContacts(users) {
        let html = '';
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        users.forEach(user => {
            const avatar = user.avatar_url ? user.avatar_url : '../assets/user_male_80px.png';
            html += `<a href="chatroom.php?user_id=${user.id}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                <img src="${avatar}" class="rounded-circle border border-primary" width="50" height="50" alt="${user.display_name}">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <h6 class="mb-0 me-2">${user.display_name}</h6>
                        <span class="badge bg-${user.status === 'online' ? 'success' : 'secondary'}">${user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : ''}</span>
                    </div>
                    <small class="text-muted">@${user.username}</small>
                    ${user.last_message ? `<div class="text-truncate small ${user.last_is_read == 0 ? 'fw-bold' : 'text-dark-emphasis'}">${user.last_sender_id == currentUserId ? '<span class=\'text-primary\'>You: </span>' : ''}${user.last_message}</div>` : ''}
                </div>
            </a>`;
        });
        contactsList.innerHTML = html;
    }

    function fetchContacts() {
        fetch('fetch_dashboard.php')
            .then(res => res.json())
            .then(users => renderContacts(users));
    }

    searchInput.addEventListener('input', function() {
        const query = this.value.trim().toLowerCase();
        if (!query) {
            displayContacts(allContacts);
            return;
        }
        const filtered = allContacts.filter(user =>
            user.display_name.toLowerCase().includes(query) ||
            user.username.toLowerCase().includes(query)
        );
        displayContacts(filtered);
    });

    setInterval(fetchContacts, 2000); // Poll every 2 seconds
    fetchContacts(); // Initial load

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
            const avatar = user.avatar_url ? user.avatar_url : '../assets/user_male_80px.png';
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
        fetch('fetch_all_users.php?q=' + encodeURIComponent(query))
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
</body>

</html>