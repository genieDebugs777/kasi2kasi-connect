<?php
require_once "auth.php";

function requireRole($roles = []) {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit;
    }

    if (!in_array($_SESSION["role_name"], $roles)) {
        die("Access denied.");
    }
}