<?php
require_once 'session_config.php';

header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'message' => 'Session refreshed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
}
?>
