<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function currentUserName() {
    return $_SESSION['name'] ?? 'Guest';
}

function currentUserRole() {
    return $_SESSION['role_name'] ?? 'User';
}
?>