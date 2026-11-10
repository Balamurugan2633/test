<?php
/**
 * Newsletter sign-up from the "Follow Us" box on every page.
 * POST email=... (+ page). Answers JSON.
 */

declare(strict_types=1);

require_once __DIR__ . '/blog-lib.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    rs_fail('Method not allowed.', 405);
}

// Bots fill in every field, including the hidden one.
if (rs_post('company') !== '') {
    rs_json(['ok' => true, 'message' => 'Thanks for subscribing!']);
}

$email = rs_post('email');
if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    rs_fail('Please enter a valid email address.');
}

$page  = substr(rs_post('page'), 0, 120);
$isNew = rs_subscribe($email, $page);

rs_json([
    'ok'      => true,
    'message' => $isNew
        ? 'Thanks for subscribing! Look out for our service updates.'
        : 'You are already subscribed — thank you!',
]);
