<?php
/**
 * Admin session, CSRF and login throttling. Every admin page includes this.
 */

declare(strict_types=1);

require_once __DIR__ . '/../api/blog-lib.php';

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

const RS_LOCK_WINDOW = 15 * 60;

function rs_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    $idle = rs_config()['admin_idle_minutes'] * 60;
    if (time() - (int) ($_SESSION['admin_seen'] ?? 0) > $idle) {
        rs_admin_logout();
        return null;
    }
    $_SESSION['admin_seen'] = time();

    $stmt = rs_db()->prepare('SELECT id, username, is_default, last_login_at FROM admins WHERE id = ?');
    $stmt->execute([(int) $_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}

/** Call at the top of every protected page. */
function rs_require_admin(): array
{
    $admin = rs_admin();
    if ($admin === null) {
        header('Location: login.php');
        exit;
    }
    return $admin;
}

function rs_admin_logout(): void
{
    unset($_SESSION['admin_id'], $_SESSION['admin_seen'], $_SESSION['csrf']);
    session_regenerate_id(true);
}

/* --- Login throttling ------------------------------------------------ */

function rs_client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
}

function rs_login_locked(): bool
{
    $db = rs_db();
    $db->prepare('DELETE FROM login_attempts WHERE attempted < ?')->execute([time() - RS_LOCK_WINDOW]);
    $stmt = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ?');
    $stmt->execute([rs_client_ip()]);
    return (int) $stmt->fetchColumn() >= rs_config()['admin_max_attempts'];
}

function rs_login_attempt(string $username, string $password): bool
{
    $stmt = rs_db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $row = $stmt->fetch();

    if (!$row || !password_verify($password, $row['password_hash'])) {
        rs_db()->prepare('INSERT INTO login_attempts (ip, attempted) VALUES (?, ?)')->execute([rs_client_ip(), time()]);
        rs_log('admin.log', 'FAILED login user=' . $username . ' ip=' . rs_client_ip());
        return false;
    }

    // A fresh session id on login stops session fixation.
    session_regenerate_id(true);
    $_SESSION['admin_id']   = (int) $row['id'];
    $_SESSION['admin_seen'] = time();

    rs_db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([rs_client_ip()]);
    rs_db()->prepare('UPDATE admins SET last_login_at = ? WHERE id = ?')->execute([rs_now(), $row['id']]);
    rs_log('admin.log', 'login user=' . $username . ' ip=' . rs_client_ip());
    return true;
}

/* --- CSRF ------------------------------------------------------------ */

function rs_csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function rs_csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(rs_csrf_token()) . '">';
}

function rs_csrf_check(): void
{
    $sent = (string) ($_POST['csrf'] ?? '');
    if ($sent === '' || !hash_equals(rs_csrf_token(), $sent)) {
        http_response_code(400);
        exit('Your session has expired. Please go back, refresh the page and try again.');
    }
}

/* --- Flash messages -------------------------------------------------- */

function rs_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function rs_take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function rs_redirect(string $to): void
{
    header('Location: ' . $to);
    exit;
}
