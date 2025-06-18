<?php
require_once '../db.php';
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php?msg=' . urlencode('Please log in to access the dashboard.'));
    exit;
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
                        $sql = "SELECT id, display_name, username FROM users WHERE id != ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param('i', $current_user_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        while ($user = $result->fetch_assoc()):
                        ?>
                            <a href="chatroom.php?user_id=<?php echo $user['id']; ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3">
                                <img src="../assets/user_male_80px.png" class="rounded-circle border border-primary" width="50" height="50" alt="<?php echo htmlspecialchars($user['display_name']); ?>">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <h6 class="mb-0 me-2"><?php echo htmlspecialchars($user['display_name']); ?></h6>
                                        <span class="badge bg-secondary">User</span>
                                    </div>
                                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
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
</body>

</html>