<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

function isLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

function login(string $email, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT id, nombre, password FROM admins WHERE email = ? AND activo = 1 LIMIT 1');
    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']     = $admin['id'];
        $_SESSION['admin_nombre'] = $admin['nombre'];
        return true;
    }
    return false;
}

function logout(): void {
    $_SESSION = [];
    session_destroy();
    header('Location: ' . ADMIN_URL . '/login.php');
    exit;
}

function currentAdmin(): string {
    return $_SESSION['admin_nombre'] ?? 'Admin';
}
