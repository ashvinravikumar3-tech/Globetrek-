<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
    header('Location: index.php');
    exit;
}
?>