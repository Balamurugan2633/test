<?php
/**
 * ONE-TIME admin password reset for Regular Solution.
 * Upload to public_html (next to index.html), open it once in the browser,
 * and it deletes itself. Do not leave it on the server.
 */
declare(strict_types=1);
require __DIR__ . '/api/blog-lib.php';

$username = 'admin';
$password = 'Admin@1234';

$db   = rs_db(); // creates api/storage/site.sqlite + tables if missing
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $db->prepare('SELECT id FROM admins WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetchColumn()) {
    $db->prepare('UPDATE admins SET password_hash = ?, is_default = 0 WHERE username = ?')->execute([$hash, $username]);
} else {
    $db->prepare('INSERT INTO admins (username, password_hash, is_default) VALUES (?, ?, 0)')->execute([$username, $hash]);
}
$db->exec('DELETE FROM login_attempts'); // clear any lockout

$deleted = @unlink(__FILE__);
header('Content-Type: text/plain; charset=utf-8');
echo "Admin password reset. Username: {$username}\n";
echo $deleted
    ? "This reset file has deleted itself.\n"
    : "WARNING: could not delete this file - delete it now in File Manager!\n";
