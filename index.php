<?php
require_once 'session_config.php';
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['signup'])) {
        $user = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        $acceptTerms = isset($_POST['acceptTerms']);

        if (empty($user) || empty($email) || empty($pass)) {
            $error = 'All fields are required.';
        } elseif (!$acceptTerms) {
            $error = 'You must accept the terms and conditions.';
        } elseif (strlen($pass) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } else {
  
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$user, $email]);
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
               
                $hashedPass = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                if ($stmt->execute([$user, $email, $hashedPass])) {
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['username'] = $user;
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Failed to create account.';
                }
            }
        }
    } elseif (isset($_POST['login'])) {
      
        $user = trim($_POST['username'] ?? '');
        $pass = trim($_POST['password'] ?? '');
        
        error_log("LOGIN ATTEMPT: user=$user, pass=$pass");

        
        if (empty($user) || empty($pass)) {
            $error = 'Username and password are required.';
        } else {
           
            $stmt = $pdo->prepare("SELECT id, username, password FROM admin WHERE username = ?");
            $stmt->execute([$user]);
            $adminData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("ADMIN CHECK: " . json_encode($adminData));
            
            if ($adminData) {
                error_log("ADMIN MATCH CHECK: plain=" . var_export($pass === $adminData['password'], true) . ", verify=" . var_export(password_verify($pass, $adminData['password']), true));
                if ($pass === $adminData['password'] || password_verify($pass, $adminData['password'])) {
                    error_log("ADMIN LOGIN SUCCESS");
                    $_SESSION['admin_id'] = $adminData['id'];
                    $_SESSION['admin_username'] = $adminData['username'];
                    header('Location: admin/dashboard.php');
                    exit;
                }
            }
            
            $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$user, $user]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($userData && password_verify($pass, $userData['password'])) {
                $_SESSION['user_id'] = $userData['id'];
                $_SESSION['username'] = $userData['username'];
                header('Location: dashboard.php');
                exit;
            }
            
            error_log("LOGIN FAILED FOR: $user");
            $error = 'Invalid username or password.';
        }
    }
}

if (isset($error)) {
    echo "<script>alert('$error');</script>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication - Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="theme-toggle">
        <button type="button" class="theme-btn" id="themeToggleBtn" onclick="toggleTheme()">
            <i class="fas fa-moon"></i>
        </button>
    </div>

    <div class="main-wrapper" id="mainContainer">
        <div class="animated-circle"></div>
        
        <div class="content-wrapper">
            <div class="forms-section">
                <form class="auth-form login-form" id="loginForm" method="POST" action="">
                    <input type="hidden" name="login" value="1">
                    <h2 class="form-heading">Sign In</h2>
                    
                    <div class="field-container">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" autocomplete="username">
                        <span></span>
                    </div>

                    <div class="field-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="loginPass" name="password" placeholder="Password" autocomplete="current-password">
                        <i class="far fa-eye eye-icon" onclick="togglePasswordVisibility('loginPass', this)"></i>
                    </div>

                    <a href="#" class="text-link">Forgot your password?</a>

                    <button type="submit" class="action-button">Login</button>
                    
                </form>

                <form class="auth-form signup-form" id="signupForm" method="POST" action="">
                    <input type="hidden" name="signup" value="1">
                    <h2 class="form-heading">Create Account</h2>
                    
                    <div class="field-container">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" autocomplete="username">
                        <span></span>
                    </div>

                    <div class="field-container">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" autocomplete="email">
                        <span></span>
                    </div>

                    <div class="field-container">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="signupPass" name="password" placeholder="Password" autocomplete="new-password">
                        <i class="far fa-eye eye-icon" onclick="togglePasswordVisibility('signupPass', this)"></i>
                    </div>

                    <div class="terms-check">
                        <input type="checkbox" id="acceptTerms" name="acceptTerms">
                        <label for="acceptTerms">I accept the <a href="#">terms and conditions</a></label>
                    </div>

                    <button type="submit" class="action-button">Sign Up</button>

                    
                </form>
            </div>
        </div>

        <div class="side-panels">
            <div class="info-panel left-info">
                <div class="panel-content">
                    <h3>Don't have an account?</h3>
                    <p>Create your account now to browse the marketplace</p>
                    <button class="transparent-btn" onclick="switchToRegister()">Sign Up</button>
                </div>
                
            </div>

            <div class="info-panel right-info">
                <div class="panel-content">
                    <h3>Already have an account?</h3>
                    <p>Sign in to see your notifications and add your products</p>
                    <button class="transparent-btn" onclick="switchToLogin()">Sign In</button>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>