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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <title>Gwez - Live-Chat</title>
</head>
<body class="bg-light p-3">
    <?php include_once 'navbar.php';?>
    <div class="container mt-5" style="max-width: 90vw;">
        <div class="row mb-3">
            <div class="col d-flex justify-content-end align-items-center">
                <div class="bg-white shadow-sm rounded-pill px-3 py-2 d-flex align-items-center gap-2" style="min-width: 180px;">
                    <img src="../assets/user_male_80px.png" alt="User" width="32" height="32" class="rounded-circle border border-primary">
                    <span class="fw-semibold text-primary">Welcome, <?php echo htmlspecialchars($_SESSION['display_name'] ?? $_SESSION['username']); ?>!</span>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 w-100" style="max-width: 90vw;">
                <div class="card shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">CONTACTS</h5>
                        <div class="list-group list-group-flush">
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
                                ORDER BY sent_at DESC LIMIT 1) AS last_sender_id
                            FROM users WHERE id != ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('iiiiiii', $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
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
                        ?>
                            <a href="chatroom.php?user_id=<?php echo $user['id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                                <img src="../assets/user_male_80px.png" class="rounded-circle border border-primary" width="50" height="50" alt="<?php echo htmlspecialchars($user['display_name']); ?>">
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
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    // AJAX polling for dashboard contacts
    const contactsList = document.querySelector('.list-group.list-group-flush');
    function renderContacts(users) {
        let html = '';
        const currentUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
        users.forEach(user => {
            html += `<a href="chatroom.php?user_id=${user.id}" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                <img src="../assets/user_male_80px.png" class="rounded-circle border border-primary" width="50" height="50" alt="${user.display_name}">
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
    setInterval(fetchContacts, 2000); // Poll every 2 seconds
    fetchContacts(); // Initial load
    </script>
</body>

</html>