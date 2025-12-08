<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

$message = '';
$user = null;
try {
    $stmt = $pdo->prepare('SELECT id, username, email, profile_image FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Profile fetch error: ' . $e->getMessage());
}

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $profile_image = $user['profile_image'] ?? null;

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $fileName = $_FILES['profile_image']['name'];
        $fileTmp = $_FILES['profile_image']['tmp_name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $newName = uniqid("profile_", true) . "." . $ext;
            $uploadPath = __DIR__ . '/images/' . $newName;
            if (move_uploaded_file($fileTmp, $uploadPath)) {
                $profile_image = 'images/' . $newName;
            } else {
                $message = 'Profile image upload failed.';
            }
        } else {
            $message = 'Invalid image file type.';
        }
    }

    if ($message === '' && ($username === '' || $email === '')) {
        $message = 'Please provide both username and email.';
    }

    if ($message === '') {
        try {
            if ($profile_image) {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, profile_image = ? WHERE id = ?');
                $stmt->execute([$username, $email, $profile_image, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
                $stmt->execute([$username, $email, $_SESSION['user_id']]);
            }
            $message = 'Profile updated successfully!';
            $success = true;
            $user['username'] = $username;
            $user['email'] = $email;
            if ($profile_image) {
                $user['profile_image'] = $profile_image;
            }
        } catch (Exception $e) {
            error_log('Profile update error: ' . $e->getMessage());
            $message = 'Could not update profile.';
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'username' => $user['username'] ?? null,
            'email' => $user['email'] ?? null,
            'profile_image' => $user['profile_image'] ?? null,
        ]);
        exit;
    }
}


ob_start();
?>
<form id="profileEditForm" class="edit-profile-form" method="POST" enctype="multipart/form-data">
    <h2 style="margin-top:0; color:#fff;">Edit Profile</h2>
    <?php if ($message): ?>
        <div id="profileEditMessage" style="margin-bottom:12px; color:#4ecdc4; padding:8px; background:rgba(78,205,196,0.1); border-radius:4px;"> <?php echo htmlspecialchars($message); ?> </div>
    <?php else: ?>
        <div id="profileEditMessage" style="margin-bottom:12px; display:none; color:#4ecdc4; padding:8px; background:rgba(78,205,196,0.1); border-radius:4px;"></div>
    <?php endif; ?>

    <?php if (!empty($user['profile_image'])): ?>
        <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image" class="profile-img-preview">
    <?php endif; ?>
        <br>
    <label for="username" style="color:rgba(255,255,255,0.9);">Username: </label>
    <input type="text" name="username" id="profileEditUsername" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
        <br>
    <label for="email" style="color:rgba(255,255,255,0.9);">Email: </label>
    <input type="email" name="email" id="profileEditEmail" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
        <br>
    <label for="profile_image" style="color:rgba(255,255,255,0.9);">Profile Image: </label>
    <input type="file" name="profile_image" id="profileEditImage" accept="image/*">
    <div style="display:flex;gap:8px;margin-top:12px;">
        <button type="submit" class="action-button">Update Profile</button>
        <button type="button" class="action-button" id="profileEditCloseBtn" style="background:rgba(255,255,255,0.06); color:#fff;">Close</button>
    </div>
</form>
<?php
$formHtml = ob_get_clean();

if ($isAjax && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo $formHtml;
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profile - Marketplace</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dash.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .edit-profile-form { max-width: 400px; margin: 32px auto; background: rgba(20,24,31,0.95); padding: 24px; border-radius: 10px; color: #fff; border: 1px solid rgba(78,205,196,0.2); }
        .edit-profile-form h2 { color: #fff; margin: 0 0 16px 0; font-family: 'Poppins', sans-serif; }
        .edit-profile-form label { display: block; margin-bottom: 6px; font-weight: 500; color: rgba(255,255,255,0.9); font-size: 0.95rem; }
        .edit-profile-form input[type="text"], 
        .edit-profile-form input[type="email"], 
        .edit-profile-form input[type="file"] { 
            width: 100%; 
            padding: 10px 12px; 
            border-radius: 6px; 
            border: 1px solid rgba(255,255,255,0.1); 
            margin-bottom: 14px; 
            background: rgba(255,255,255,0.05);
            color: #fff;
            font-family: inherit;
        }
        .edit-profile-form input[type="text"]:focus, 
        .edit-profile-form input[type="email"]:focus,
        .edit-profile-form input[type="file"]:focus {
            outline: none;
            border-color: #4ecdc4;
            background: rgba(255,255,255,0.08);
        }
        .edit-profile-form input[type="file"]::file-selector-button {
            background: #4ecdc4;
            color: #052;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        .edit-profile-form input[type="file"]::file-selector-button:hover {
            background: #44a08d;
        }
        .edit-profile-form .action-button { width: 100%; }
        .profile-img-preview { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; margin-bottom: 12px; }
    </style>
</head>
<body class="dashboard-page">
<div class="main-wrapper">
    <div class="dashboard-layout">
        <?php include __DIR__ . '/layout.php'; ?>
        <main class="main-content">
            <?php echo $formHtml; ?>
        </main>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const closeBtn = document.getElementById('profileEditCloseBtn');
    if (closeBtn) closeBtn.addEventListener('click', function(){ window.location.href = 'profile.php'; });
});
</script>
</body>
</html>
