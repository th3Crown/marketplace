<?php
session_start();
require_once __DIR__ . '/db.php';

echo "<pre style='background:#222; color:#0f0; padding:20px; font-family:monospace;'>";

echo "=== SESSION DEBUG ===\n";
echo "SESSION['user_id']: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "SESSION['username']: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE') . "\n\n";

echo "=== DATABASE USERS ===\n";
try {
    $stmt = $pdo->prepare('SELECT id, username, email FROM users LIMIT 10');
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($users)) {
        echo "NO USERS FOUND IN DATABASE!\n";
    } else {
        foreach ($users as $user) {
            echo "ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}\n";
        }
    }
} catch (Exception $e) {
    echo "ERROR QUERYING USERS: " . $e->getMessage() . "\n";
}

echo "\n=== ATTEMPTING TO FETCH CURRENT USER ===\n";
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "SUCCESS! Found user: " . json_encode($user) . "\n";
        } else {
            echo "FAILED: User ID {$_SESSION['user_id']} not found in database\n";
        }
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "No session user_id set\n";
}

echo "</pre>";
?>
