<?php
/**
 * POST { phone } -> generates a one-time code, stores its hash in the session
 * and hands it to the configured SMS gateway.
 */

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    rs_fail('Use POST.', 405);
}

$config = rs_config()['otp'];
$phone  = rs_normalise_phone(rs_post('phone'));

if ($phone === '') {
    rs_fail('Enter a valid 10-digit mobile number.');
}

$now  = time();
$otp  = $_SESSION['otp'] ?? null;
$hist = $_SESSION['otp_history'][$phone] ?? [];

// Drop send timestamps older than an hour, then enforce the hourly ceiling.
$hist = array_values(array_filter($hist, static fn($t) => $t > $now - 3600));
if (count($hist) >= $config['max_per_hour']) {
    rs_fail('Too many codes requested for this number. Please try again later.', 429);
}

// Cooldown between consecutive sends to the same number.
if ($otp && $otp['phone'] === $phone) {
    $wait = $otp['sent_at'] + $config['resend_cooldown'] - $now;
    if ($wait > 0) {
        rs_fail("Please wait {$wait}s before requesting another code.", 429, ['retry_after' => $wait]);
    }
}

$code = (string) random_int(
    (int) str_pad('1', $config['length'], '0'),
    (int) str_pad('9', $config['length'], '9')
);

[$sent, $error] = rs_send_sms($phone, $code);
if (!$sent) {
    rs_log('otp.log', "send failed for {$phone}: {$error}");
    rs_fail($error !== '' ? $error : 'Could not send the code right now. Please try again.', 502);
}

$_SESSION['otp'] = [
    'phone'    => $phone,
    'hash'     => rs_hash_code($code, $phone),
    'expires'  => $now + $config['ttl_seconds'],
    'attempts' => 0,
    'sent_at'  => $now,
];
$hist[] = $now;
$_SESSION['otp_history'][$phone] = $hist;

// A fresh code invalidates any previous verification for this number.
unset($_SESSION['verified_phone']);

$response = [
    'ok'       => true,
    'message'  => 'We have sent a 6-digit code to ' . substr($phone, 0, 2) . '••••' . substr($phone, -3) . '.',
    'ttl'      => $config['ttl_seconds'],
    'resend_in'=> $config['resend_cooldown'],
];

// Test mode: no SMS is sent. On a local machine the code is handed back so the
// flow can be completed; on any public host it stays in api/storage/otp.log,
// otherwise test mode left switched on would let anyone verify any number.
if (rs_config()['sms_provider'] === 'log') {
    $response['test_mode'] = true;

    if (rs_is_local()) {
        $response['test_code'] = $code;
        $response['message']   = 'Test mode — no SMS was sent. Your code is ' . $code . '.';
    } else {
        $response['message'] = 'Test mode is still switched on, so no SMS was sent. '
                             . 'Set a real sms_provider in api/config.php before taking the site live.';
    }
}

rs_json($response);
