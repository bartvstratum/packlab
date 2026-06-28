<?php

require_once __DIR__ . '/data.php';

function auth_start(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user_id(): ?int
{
    auth_start();
    return isset($_SESSION['uid']) ? (int) $_SESSION['uid'] : null;
}

function login(string $email, string $password): bool
{
    $u = user_by_email($email);
    if (!$u || !user_verify($u, $password)) {
        return false;
    }
    auth_start();
    session_regenerate_id(true);
    $_SESSION['uid'] = (int) $u['id'];
    return true;
}

function logout(): void
{
    auth_start();
    $_SESSION = [];
    session_destroy();
}

function require_login(): int
{
    $id = current_user_id();
    if ($id === null) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    return $id;
}

function csrf_token(): string
{
    auth_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_check(?string $t): bool
{
    auth_start();
    return !empty($_SESSION['csrf']) && is_string($t) && hash_equals($_SESSION['csrf'], $t);
}
