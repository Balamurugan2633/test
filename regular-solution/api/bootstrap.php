<?php
/**
 * Shared helpers for the OTP + form endpoints.
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $https, // cookie never travels over plain HTTP on the live site
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

/**
 * True only for a site being served locally (XAMPP, a .test/.local hostname or
 * a loopback client). Test mode hands the OTP straight back to the browser,
 * which is fine on a dev machine but would let anyone verify any number on a
 * public host — so that only ever happens when this returns true.
 */
function rs_is_local(): bool
{
    // Judged on the hostname the visitor typed, deliberately NOT on
    // REMOTE_ADDR: behind a reverse proxy (nginx in front of php-fpm) every
    // real visitor arrives from 127.0.0.1, which would mark production local.
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $host = explode(':', $host)[0];

    return in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
        || str_ends_with($host, '.local')
        || str_ends_with($host, '.test');
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

/**
 * Send an email. Plain text always; pass $html to send a multipart message
 * (HTML with a plain-text fallback). Returns true when it was accepted.
 *
 * With config 'smtp' password set, mail goes through that SMTP server (Gmail)
 * and really comes from smtp.from_email. Without it, PHP mail() is used with a
 * no-reply@<this domain> sender (a Gmail "From" sent by another server is
 * rejected/spammed by Gmail) and replies directed to smtp.from_email.
 * Failures are written to storage/mail.log.
 */
function rs_send_mail(string $to, string $subject, string $text, string $html = '', string $replyTo = ''): bool
{
    $clean = fn (string $v): string => trim(str_replace(["\r", "\n"], '', $v));
    $to    = $clean($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $smtp     = rs_config()['smtp'] ?? [];
    $fromMail = $clean((string) ($smtp['from_email'] ?? ''));
    $fromName = $clean((string) ($smtp['from_name'] ?? (rs_config()['site_name'] ?? 'Regular Solution')));
    $useSmtp  = ($smtp['password'] ?? '') !== '' && filter_var($fromMail, FILTER_VALIDATE_EMAIL);

    if (!$useSmtp) {
        $host = preg_replace('/^www\./i', '', strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? 'localhost')[0]));
        if ($replyTo === '' && $fromMail !== '') {
            $replyTo = $fromMail;
        }
        $fromMail = 'no-reply@' . $host;
    }

    $headers = [
        'Date: ' . date('r'),
        'From: =?UTF-8?B?' . base64_encode($fromName) . '?= <' . $fromMail . '>',
        'MIME-Version: 1.0',
    ];
    $replyTo = $clean($replyTo);
    if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    if ($html === '') {
        $headers[] = 'Content-Type: text/plain; charset=utf-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $body = chunk_split(base64_encode($text));
    } else {
        $boundary  = 'rs_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $body = "--{$boundary}\r\n"
              . "Content-Type: text/plain; charset=utf-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($text))
              . "--{$boundary}\r\n"
              . "Content-Type: text/html; charset=utf-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n\r\n"
              . chunk_split(base64_encode($html))
              . "--{$boundary}--\r\n";
    }
    $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

    if ($useSmtp) {
        [$ok, $err] = rs_smtp_send($smtp, $fromMail, $to, $encSubject, $headers, $body);
        if (!$ok) {
            rs_log('mail.log', "SMTP failed: to={$to} subject={$subject} error={$err}");
        }
        return $ok;
    }

    $ok = @mail($to, $encSubject, $body, implode("\r\n", $headers), '-f' . $fromMail);
    if (!$ok) {
        rs_log('mail.log', "mail() failed: to={$to} subject={$subject}");
    }
    return $ok;
}

/**
 * Minimal SMTP client (implicit SSL on 465, or STARTTLS on 587) with AUTH LOGIN.
 * Returns [bool $sent, string $error].
 */
function rs_smtp_send(array $smtp, string $from, string $to, string $subject, array $headers, string $body): array
{
    $host = (string) ($smtp['host'] ?? 'smtp.gmail.com');
    $port = (int) ($smtp['port'] ?? 465);
    $ssl  = $port === 465;

    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client(($ssl ? 'ssl://' : 'tcp://') . $host . ':' . $port, $errno, $errstr, 15);
    if (!$fp) {
        return [false, "connect: {$errstr} ({$errno})"];
    }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $line, array $expect) use ($fp, $read): array {
        if ($line !== '') {
            fwrite($fp, $line . "\r\n");
        }
        $reply = $read();
        $ok    = in_array((int) substr($reply, 0, 3), $expect, true);
        return [$ok, trim($reply)];
    };

    $ehlo  = 'EHLO ' . (gethostname() ?: 'localhost');
    $steps = [['', [220]], [$ehlo, [250]]];
    if (!$ssl) {
        $steps[] = ['STARTTLS', [220]];
    }
    foreach ($steps as $i => [$line, $expect]) {
        [$ok, $reply] = $cmd($line, $expect);
        if (!$ok) {
            fclose($fp);
            return [false, $reply];
        }
    }
    if (!$ssl) {
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            return [false, 'STARTTLS failed'];
        }
        [$ok, $reply] = $cmd($ehlo, [250]);
        if (!$ok) {
            fclose($fp);
            return [false, $reply];
        }
    }

    // Normalise line endings and dot-stuff the message for the DATA phase.
    $message = implode("\r\n", array_merge(["To: <{$to}>", "Subject: {$subject}"], $headers))
             . "\r\n\r\n" . $body;
    $message = preg_replace("/\r?\n/", "\r\n", $message);
    $message = preg_replace('/^\./m', '..', $message);

    $steps = [
        ['AUTH LOGIN', [334]],
        [base64_encode((string) ($smtp['username'] ?? '')), [334]],
        [base64_encode(str_replace(' ', '', (string) $smtp['password'])), [235]],
        ["MAIL FROM:<{$from}>", [250]],
        ["RCPT TO:<{$to}>", [250, 251]],
        ['DATA', [354]],
        [$message . "\r\n.", [250]],
    ];
    foreach ($steps as [$line, $expect]) {
        [$ok, $reply] = $cmd($line, $expect);
        if (!$ok) {
            fwrite($fp, "QUIT\r\n");
            fclose($fp);
            return [false, $reply];
        }
    }
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return [true, ''];
}
