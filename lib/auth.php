<?php
defined('SOLICITY_ENTRY') or die('Direct access forbidden.');

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    return $u ?: null;
}

function current_admin(): ?array {
    if (empty($_SESSION['admin_id'])) return null;
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    $a = $stmt->fetch();
    return $a ?: null;
}

function user_accounts(string $userId): array {
    $stmt = db()->prepare('SELECT * FROM accounts WHERE user_id = ? ORDER BY type');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function account_card(string $accountId): ?array {
    $stmt = db()->prepare('SELECT * FROM cards WHERE account_id = ? LIMIT 1');
    $stmt->execute([$accountId]);
    $c = $stmt->fetch();
    return $c ?: null;
}

function require_customer(): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    if ($u['status'] === 'frozen') { flash_set('error', 'Your account is currently frozen. Contact support.'); }
    return $u;
}

function require_admin(): array {
    $a = current_admin();
    if (!$a) redirect('admin/index.php');
    return $a;
}

function login_customer(string $id): void { $_SESSION['user_id'] = $id; }
function login_admin(string $id): void { $_SESSION['admin_id'] = $id; }
function logout_all(): void { unset($_SESSION['user_id'], $_SESSION['admin_id']); }
function verify_password(string $plain, string $hash): bool { return password_verify($plain, $hash); }
