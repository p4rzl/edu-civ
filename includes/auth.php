<?php
declare(strict_types=1);

function isLoggedIn(): bool
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']);
}

function currentUser(): ?array
{
    return isLoggedIn() ? $_SESSION['user'] : null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Devi effettuare il login per continuare.');
        header('Location: login.php');
        exit;
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    $user = currentUser();
    if (!$user || !in_array($user['role'], $roles, true)) {
        http_response_code(403);
        exit('Accesso negato.');
    }
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
