<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['session_id'])) {
    $_SESSION['session_id'] = session_id();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getSessionId() {
    return $_SESSION['session_id'] ?? session_id();
}