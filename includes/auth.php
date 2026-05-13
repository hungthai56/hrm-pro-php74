<?php
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        redirect(url('login'));
    }
}

function login_attempt(string $email, string $password): bool
{
    $stmt = db()->prepare("SELECT u.*, r.role_name FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.email = ? AND u.is_active = 1 LIMIT 1");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'role_name' => $user['role_name'] ?? 'user',
    ];

    $stmt = db()->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();
    return true;
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}
