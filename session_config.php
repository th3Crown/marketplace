<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 172800);
    ini_set('session.cookie_lifetime', 172800);
    
    session_start();
    
    if (!isset($_SESSION['_fingerprint'])) {
        $_SESSION['_fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
        $_SESSION['_created'] = time();
    } else {
        $current_fingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
        if ($_SESSION['_fingerprint'] !== $current_fingerprint) {
            session_destroy();
            header('Location: index.php');
            exit;
        }
    }
}
?>
