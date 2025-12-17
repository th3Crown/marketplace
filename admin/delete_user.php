<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $userId = intval($_POST['user_id']);
    
    if ($userId === $_SESSION['admin_id']) {
        $error = 'You cannot delete your own admin account!';
    } else {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("DELETE FROM orders WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $stmt = $pdo->prepare("DELETE FROM products WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $pdo->commit();
            
            $success = 'User deleted successfully!';
            
            header("Refresh: 2; url=dashboard.php?tab=users");
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Error deleting user: ' . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }
        
        .container {
            background: rgba(20, 24, 31, 0.8);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(78, 205, 196, 0.2);
        }
        
        .success-message {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #4caf50;
        }
        
        .error-message {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #ff6b6b;
        }
        
        h2 {
            margin-bottom: 20px;
            color: #4ecdc4;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        button, a {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s;
        }
        
        .btn-back {
            background: #4ecdc4;
            color: #052;
        }
        
        .btn-back:hover {
            background: #3bbb9f;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <p style="text-align: center; margin-bottom: 20px;">Redirecting to users list...</p>
            <a href="dashboard.php?tab=users" class="btn-back" style="display: inline-block; width: 100%;">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        <?php elseif ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <a href="dashboard.php?tab=users" class="btn-back" style="display: inline-block; width: 100%;">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        <?php else: ?>
            <h2><i class="fas fa-exclamation-triangle"></i> Processing...</h2>
            <p>Deleting user...</p>
        <?php endif; ?>
    </div>
</body>
</html>
