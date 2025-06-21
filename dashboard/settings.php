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

// Fetch current user data
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT display_name, avatar_url, username FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
}

// Handle profile update
$success = false;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = trim($_POST['display_name'] ?? $user['display_name']);
    $avatar_url = trim($_POST['avatar_url'] ?? '');
    $uploaded_avatar = $user['avatar_url'] ?? '';
    $new_username = trim($_POST['username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    // Handle file upload and process image
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['avatar_file']['tmp_name'];
        $fileExt = strtolower(pathinfo($_FILES['avatar_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileExt, $allowed)) {
            $newName = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.jpg';
            $dest = '../assets/images/' . $newName;
            $srcImg = null;
            if ($fileExt === 'jpg' || $fileExt === 'jpeg') {
                $srcImg = imagecreatefromjpeg($fileTmp);
            } elseif ($fileExt === 'png') {
                $srcImg = imagecreatefrompng($fileTmp);
            } elseif ($fileExt === 'gif') {
                $srcImg = imagecreatefromgif($fileTmp);
            } elseif ($fileExt === 'webp') {
                $srcImg = imagecreatefromwebp($fileTmp);
            }
            if ($srcImg) {
                $dstImg = imagecreatetruecolor(200, 200);
                $width = imagesx($srcImg);
                $height = imagesy($srcImg);
                $minDim = min($width, $height);
                $srcX = ($width - $minDim) / 2;
                $srcY = ($height - $minDim) / 2;
                imagecopyresampled($dstImg, $srcImg, 0, 0, $srcX, $srcY, 200, 200, $minDim, $minDim);
                imagejpeg($dstImg, $dest, 90);
                imagedestroy($srcImg);
                imagedestroy($dstImg);
                $uploaded_avatar = $dest;
            } else {
                $error = 'Failed to process image.';
            }
        } else {
            $error = 'Invalid image type. Allowed: jpg, jpeg, png, gif, webp.';
        }
    } else if ($avatar_url !== '') {
        $uploaded_avatar = $avatar_url;
    }
    // Username validation and update
    if ($new_username && $new_username !== $user['username']) {
        if (!preg_match('/^[a-z0-9]{3,}$/', $new_username)) {
            $error = 'Username must be at least 3 characters, lowercase letters and numbers only.';
        } else {
            $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $stmt->bind_param('si', $new_username, $_SESSION['user_id']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = 'Username already taken.';
            }
            $stmt->close();
        }
    }
    // Password update
    if (!$error && $new_password) {
        if (strlen($new_password) < 8) {
            $error = 'New password must be at least 8 characters.';
        } else {
            $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $stmt->bind_result($db_password);
            $stmt->fetch();
            $stmt->close();
            if (!password_verify($current_password, $db_password)) {
                $error = 'Current password is incorrect.';
            }
        }
    }
    if (!$error) {
        // Build update query
        $fields = ['display_name = ?', 'avatar_url = ?'];
        $params = [$display_name, $uploaded_avatar];
        $types = 'ss';
        if ($new_username && $new_username !== $user['username']) {
            $fields[] = 'username = ?';
            $params[] = $new_username;
            $types .= 's';
        }
        if ($new_password) {
            $fields[] = 'password = ?';
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            $types .= 's';
        }
        $params[] = $_SESSION['user_id'];
        $types .= 'i';
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
        $success = true;
        $_SESSION['display_name'] = $display_name;
        if ($new_username && $new_username !== $user['username']) {
            $user['username'] = $new_username;
        }
        $user['display_name'] = $display_name;
        $user['avatar_url'] = $uploaded_avatar;
    }
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
<body class="p-3">
    <?php include_once 'navbar.php'; ?>
    <div class="container mt-5" style="max-width: 90vw;">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 w-100" style="max-width: 90vw;">
                <div class="card shadow-sm rounded-4">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Profile Settings</h5>
                        <?php if ($success): ?>
                            <div class="alert alert-success">Profile updated successfully!</div>
                        <?php elseif ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <form method="POST" class="mb-3" enctype="multipart/form-data">
                            <div class="mb-3 text-center">
                                <img src="<?php echo htmlspecialchars($user['avatar_url'] ?? '../assets/user_male_80px.png'); ?>" class="rounded-circle border border-primary" width="80" height="80" alt="Avatar">
                            </div>
                            <div class="mb-3">
                                <label for="display_name" class="form-label">Display Name</label>
                                <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>">
                                <div class="form-text">Lowercase letters and numbers, at least 3 characters. Leave blank to keep current.</div>
                            </div>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="off">
                                <div class="form-text">Required only to change password.</div>
                            </div>
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="off">
                                <div class="form-text">At least 8 characters. Leave blank to keep current.</div>
                            </div>
                            <div class="mb-3">
                                <label for="avatar_file" class="form-label">Upload Avatar Image</label>
                                <input type="file" class="form-control" id="avatar_file" name="avatar_file" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                        </form>
                        <div class="text-center text-muted small">Username: <strong><?php echo htmlspecialchars($user['username'] ?? ''); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>