<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('BASE_PATH')) {
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    $currentDir = realpath(__DIR__);
    $docRoot = str_replace('\\', '/', $docRoot);
    $currentDir = str_replace('\\', '/', $currentDir);

    $basePath = '';
    if (strpos($currentDir, $docRoot) === 0) {
        $basePath = substr($currentDir, strlen($docRoot));
    }
    $basePath = '/' . ltrim($basePath, '/');
    define('BASE_PATH', rtrim($basePath, '/'));
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_PATH . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (!isAdmin()) {
        header('Location: ' . BASE_PATH . '/index.php');
        exit;
    }
}

function csrf() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}