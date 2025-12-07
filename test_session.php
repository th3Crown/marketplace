<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

$debug = [
    'session_user_id' => $_SESSION['user_id'] ?? null,
    'session_username' => $_SESSION['username'] ?? null,
    'session_status' => session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'INACTIVE',
];

try {
    if (isset($_SESSION['user_id'])) {
        $userId = intval($_SESSION['user_id']);
        $stmt = $pdo->prepare('SELECT id, username, email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug['db_lookup_result'] = $user ?: 'NOT FOUND';
    }
    
    $stmt = $pdo->query('SELECT id, username FROM users LIMIT 5');
    $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $debug['all_users'] = $allUsers;
} catch (Exception $e) {
    $debug['error'] = $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($debug, JSON_PRETTY_PRINT);
?>
