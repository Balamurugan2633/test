<?php
/**
 * Receives a booking / contact request, refuses it unless the mobile number was
 * verified by OTP in this session, records it, then sends the visitor to the
 * Thank You page.
 */

declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: ../index.html');
    exit;
}

$phone    = rs_normalise_phone(rs_post('phone'));
$verified = $_SESSION['verified_phone'] ?? '';

/** Send the visitor back with an explanation, or answer JSON for fetch callers. */
function rs_reject(string $message, int $status = 400): void
{
    if (rs_wants_json()) {
        rs_fail($message, $status);
    }
    header('Location: ../index.html?error=' . rawurlencode($message) . '#booking');
    exit;
}

// Bots fill in every field, including the hidden one.
if (rs_post('company') !== '') {
    rs_reject('Your request could not be processed.');
}

if ($phone === '') {
    rs_reject('Enter a valid 10-digit mobile number.');
}
if ($verified === '' || !hash_equals($verified, $phone)) {
    rs_reject('Please verify your mobile number with the OTP before submitting.', 403);
}

$name = rs_post('name');
$mail = rs_post('email');
if ($name === '') {
    rs_reject('Please enter your name.');
}
if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
    rs_reject('Please enter a valid email address.');
}

$record = [
    'received'  => date('Y-m-d H:i:s'),
    'form'      => rs_post('form_name', 'Website form'),
    'name'      => $name,
    'phone'     => $phone,
    'email'     => $mail,
    'service'   => rs_post('service'),
    'move_type' => rs_post('move_type'),
    'house_type' => rs_post('house_type'),
    'date'      => rs_post('date'),
    'time'      => rs_post('time'),
    'from'      => rs_post('from'),
    'to'        => rs_post('to'),
    'floor_from' => rs_post('floor_from'),
    'floor_to'  => rs_post('floor_to'),
    'address'   => rs_post('address'),
    'message'   => rs_post('message'),
    'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
];

// Always keep a copy on disk.
$csv    = rs_storage_dir() . '/submissions.csv';
$isNew  = !file_exists($csv);
if ($handle = @fopen($csv, 'a')) {
    if (flock($handle, LOCK_EX)) {
        if ($isNew) {
            fputcsv($handle, array_keys($record));
        }
        fputcsv($handle, array_values($record));
        flock($handle, LOCK_UN);
    }
    fclose($handle);
}

// Optionally email it on as well.
$to = rs_config()['notify_email'];
if ($to !== '') {
    $body = '';
    foreach ($record as $key => $value) {
        $body .= str_pad(ucfirst($key) . ':', 12) . $value . "\n";
    }
    @mail(
        $to,
        'New ' . $record['form'] . ' — ' . $name,
        $body,
        'Content-Type: text/plain; charset=utf-8'
    );
}

// One verification covers one submission.
unset($_SESSION['verified_phone']);
$_SESSION['booking_name'] = $name;

if (rs_wants_json()) {
    rs_json(['ok' => true, 'redirect' => '../thank-you.php']);
}

header('Location: ../thank-you.php');
exit;
