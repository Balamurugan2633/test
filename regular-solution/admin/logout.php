<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';

// POST only, with a CSRF token, so a link on another site cannot log you out.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rs_csrf_check();
    rs_admin_logout();
}

rs_redirect('login.php');
