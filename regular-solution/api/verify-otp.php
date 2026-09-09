<?php
/**
 * POST { phone, code } -> marks the number verified for this session.
 */

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    rs_fail('Use POST.', 405);
}

$config = rs_config()['otp'];
$phone  = rs_normalise_phone(rs_post('phone'));
$code   = preg_replace('/\D+/', '', rs_post('code')) ?? '';
$otp    = $_SESSION['otp'] ?? null;

if ($phone === '') {
    rs_fail('Enter a valid 10-digit mobile number.');
}
if (!$otp || $otp['phone'] !== $phone) {
    rs_fail('Request a code for this number first.');
}
if (time() > $otp['expires']) {
    unset($_SESSION['otp']);
    rs_fail('That code has expired. Please request a new one.', 410);
}
if ($otp['attempts'] >= $config['max_attempts']) {
    unset($_SESSION['otp']);
    rs_fail('Too many incorrect attempts. Please request a new code.', 429);
}
if ($code === '') {
    rs_fail('Enter the code we sent you.');
}

$_SESSION['otp']['attempts']++;

if (!hash_equals($otp['hash'], rs_hash_code($code, $phone))) {
    $left = $config['max_attempts'] - $_SESSION['otp']['attempts'];
    rs_fail(
        $left > 0
            ? "That code is not correct. {$left} attempt" . ($left === 1 ? '' : 's') . ' left.'
            : 'That code is not correct. Please request a new one.',
        422
    );
}

// Verified. Burn the code so it cannot be replayed.
unset($_SESSION['otp']);
$_SESSION['verified_phone'] = $phone;

rs_json(['ok' => true, 'message' => 'Mobile number verified.']);
