<?php
defined('SOLICITY_ENTRY') or die('Direct access forbidden.');

function base_url(): string {
    static $base = null;
    if ($base !== null) return $base;
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    foreach (['/app/', '/admin/', '/api/', '/auth/'] as $marker) {
        $pos = strpos($script, $marker);
        if ($pos !== false) { $base = substr($script, 0, $pos); return $base; }
    }
    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    if ($base === '.') $base = '';
    return $base;
}

function asset(string $path): string { return base_url() . '/assets/' . ltrim($path, '/'); }
function url(string $path): string { return base_url() . '/' . ltrim($path, '/'); }

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function money(float $n): string {
    $sign = $n < 0 ? '-' : '';
    return $sign . '$' . number_format(abs($n), 2);
}

function pct(float $n): string { return ($n >= 0 ? '+' : '') . number_format($n, 1) . '%'; }

function redirect(string $path): never { header('Location: ' . url($path)); exit; }

function flash_set(string $type, string $msg): void { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }
function flash_take(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f;
}

function now_iso(): string { return date('Y-m-d H:i:s'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_check(): bool {
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }

function mask_card(string $number): string {
    $digits = preg_replace('/\D/', '', $number);
    return '•••• •••• •••• ' . substr($digits, -4);
}

function mask_account(string $number): string {
    return '••••' . substr($number, -4);
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) $out .= mb_substr($p, 0, 1);
    return mb_strtoupper($out);
}

function icon(string $name): string {
    $icons = [
        'home' => '<path d="M3 11.5 12 4l9 7.5" /><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9" />',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5" /><rect x="14" y="3" width="7" height="7" rx="1.5" /><rect x="3" y="14" width="7" height="7" rx="1.5" /><rect x="14" y="14" width="7" height="7" rx="1.5" />',
        'list' => '<line x1="4" y1="6" x2="20" y2="6" /><line x1="4" y1="12" x2="20" y2="12" /><line x1="4" y1="18" x2="14" y2="18" />',
        'transfer' => '<path d="M7 7h13l-4-4" /><path d="M17 17H4l4 4" />',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2.5" /><line x1="2" y1="10" x2="22" y2="10" />',
        'gear' => '<circle cx="12" cy="12" r="3" /><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.1.31.27.6.51.85" />',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M23 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
        'bank' => '<path d="M3 21h18" /><path d="M3 10h18" /><path d="M5 6l7-4 7 4" /><path d="M4 10v11" /><path d="M20 10v11" /><path d="M8 14v3" /><path d="M12 14v3" /><path d="M16 14v3" />',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" /><polyline points="16 17 21 12 16 7" /><line x1="21" y1="12" x2="9" y2="12" />',
        'bolt' => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2" />',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />',
        'chart' => '<line x1="18" y1="20" x2="18" y2="10" /><line x1="12" y1="20" x2="12" y2="4" /><line x1="6" y1="20" x2="6" y2="14" />',
        'plus' => '<line x1="12" y1="5" x2="12" y2="19" /><line x1="5" y1="12" x2="19" y2="12" />',
        'trash' => '<polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2" />',
        'key' => '<circle cx="8" cy="15" r="4" /><path d="M10.5 12.5 20 3" /><path d="M16 7l3 3" /><path d="M13 4l3 3" />',
    ];
    $body = $icons[$name] ?? $icons['grid'];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' . $body . '</svg>';
}

function time_ago(string $ts): string {
    $diff = time() - strtotime($ts);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j', strtotime($ts));
}
