<?php
/**
 * Shared helpers for the OTP + form endpoints.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function rs_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/config.php';
    }
    return $config;
}

function rs_storage_dir(): string
{
    $dir = __DIR__ . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    // Keep logs and submissions out of reach over HTTP on Apache.
    $deny = $dir . '/.htaccess';
    if (!file_exists($deny)) {
        @file_put_contents($deny, "Require all denied\n<IfModule !mod_authz_core.c>\n Deny from all\n</IfModule>\n");
    }
    return $dir;
}

function rs_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

function rs_fail(string $message, int $status = 400, array $extra = []): void
{
    rs_json(array_merge(['ok' => false, 'message' => $message], $extra), $status);
}

/** True when the caller expects JSON back rather than a redirect. */
function rs_wants_json(): bool
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    return stripos($accept, 'application/json') !== false || strcasecmp($xhr, 'fetch') === 0;
}

function rs_post(string $key, string $default = ''): string
{
    $raw = $_POST[$key] ?? $default;
    if (!is_string($raw)) {
        return $default;
    }
    return trim($raw);
}

/**
 * Reduce anything the visitor typed to a bare 10-digit Indian mobile number.
 * Accepts "+91 98765 43210", "098765-43210", "9876543210".
 * Returns '' when the result is not a valid mobile number.
 */
function rs_normalise_phone(string $input): string
{
    $digits = preg_replace('/\D+/', '', $input) ?? '';
    $cc     = rs_config()['country_code'];

    if (strlen($digits) > 10 && str_starts_with($digits, $cc)) {
        $digits = substr($digits, strlen($cc));
    }
    if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }

    return preg_match('/^[6-9]\d{9}$/', $digits) === 1 ? $digits : '';
}

/** Codes are never stored in plaintext, only compared as hashes. */
function rs_hash_code(string $code, string $phone): string
{
    return hash('sha256', $code . '|' . $phone . '|' . session_id());
}

function rs_log(string $file, string $line): void
{
    @file_put_contents(
        rs_storage_dir() . '/' . $file,
        '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

/**
 * Deliver the code. Returns [bool $sent, string $error].
 * Add a case here to support another gateway.
 */
function rs_send_sms(string $phone, string $code): array
{
    $config   = rs_config();
    $provider = $config['sms_provider'];
    $cc       = $config['country_code'];
    $text     = "Your {$config['site_name']} verification code is {$code}. It is valid for "
              . (int) ($config['otp']['ttl_seconds'] / 60) . " minutes. Do not share it with anyone.";

    switch ($provider) {
        case 'log':
            rs_log('otp.log', "TEST MODE  phone={$phone}  code={$code}");
            return [true, ''];

        case 'msg91':
            $c = $config['msg91'];
            if ($c['auth_key'] === '') {
                return [false, 'MSG91 auth key is not set in api/config.php.'];
            }
            $url = 'https://control.msg91.com/api/v5/otp?' . http_build_query([
                'template_id' => $c['template_id'],
                'mobile'      => $cc . $phone,
                'authkey'     => $c['auth_key'],
                'otp'         => $code,
                'sender'      => $c['sender_id'],
            ]);
            return rs_http_get($url);

        case 'fast2sms':
            $c = $config['fast2sms'];
            if ($c['api_key'] === '') {
                return [false, 'Fast2SMS API key is not set in api/config.php.'];
            }
            $url = 'https://www.fast2sms.com/dev/bulkV2?' . http_build_query([
                'authorization' => $c['api_key'],
                'route'         => $c['route'],
                'variables_values' => $code,
                'numbers'       => $phone,
            ]);
            return rs_http_get($url);

        case 'twilio':
            $c = $config['twilio'];
            if ($c['sid'] === '' || $c['token'] === '') {
                return [false, 'Twilio credentials are not set in api/config.php.'];
            }
            return rs_http_post(
                "https://api.twilio.com/2010-04-01/Accounts/{$c['sid']}/Messages.json",
                ['To' => '+' . $cc . $phone, 'From' => $c['from'], 'Body' => $text],
                $c['sid'] . ':' . $c['token']
            );

        default:
            return [false, 'Unknown SMS provider "' . $provider . '" in api/config.php.'];
    }
}

function rs_http_get(string $url): array
{
    if (!function_exists('curl_init')) {
        return [false, 'PHP cURL extension is not enabled.'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return [false, 'SMS gateway unreachable: ' . $err];
    }
    if ($code >= 400) {
        rs_log('otp.log', "gateway HTTP {$code}: {$body}");
        return [false, 'SMS gateway rejected the request.'];
    }
    return [true, ''];
}

function rs_http_post(string $url, array $fields, string $basicAuth = ''): array
{
    if (!function_exists('curl_init')) {
        return [false, 'PHP cURL extension is not enabled.'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_TIMEOUT        => 15,
    ]);
    if ($basicAuth !== '') {
        curl_setopt($ch, CURLOPT_USERPWD, $basicAuth);
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return [false, 'SMS gateway unreachable: ' . $err];
    }
    if ($code >= 400) {
        rs_log('otp.log', "gateway HTTP {$code}: {$body}");
        return [false, 'SMS gateway rejected the request.'];
    }
    return [true, ''];
}
