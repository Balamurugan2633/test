<?php
/**
 * Thank-you email sent to a visitor after they submit a quote / booking form
 * with an email address. Edit the wording here.
 */

declare(strict_types=1);

require_once __DIR__ . '/site-settings.php';

/**
 * Build the email for one submission.
 * Returns [string $subject, string $plainText, string $html].
 */
function rs_thank_you_mail(array $r): array
{
    try {
        $s = rs_settings();
    } catch (Throwable $e) {
        $s = array_map(fn ($f) => $f[3], rs_setting_fields());
    }

    // The home page (packers & movers) form shows its own phone number.
    $isHome   = stripos($r['form'] ?? '', 'home page') !== false;
    $phone    = $isHome ? ($s['landing_phone'] ?: $s['phone']) : $s['phone'];
    $whatsapp = preg_replace('/\D/', '', (string) ($s['whatsapp'] ?? ''));
    $site     = rs_config()['site_name'] ?? 'Regular Solution';
    $first    = preg_split('/\s+/', trim($r['name']))[0] ?: 'there';
    $siteUrl  = rs_config()['site_url'] ?? 'https://regularsolution.in/';

    // Only the details the visitor actually filled in.
    $fields = [
        'Service'        => $r['service'] ?? '',
        'Move type'      => $r['move_type'] ?? '',
        'House size'     => $r['house_type'] ?? '',
        'Moving from'    => $r['from'] ?? '',
        'Moving to'      => $r['to'] ?? '',
        'Preferred date' => $r['date'] ?? '',
        'Preferred time' => $r['time'] ?? '',
        'Phone'          => $r['phone'] ?? '',
    ];
    $fields = array_filter($fields, fn ($v) => trim((string) $v) !== '');

    $subject = "Thank you, {$first} — we've received your request | {$site}";

    // ---- Plain text ------------------------------------------------------
    $text  = "Hi {$first},\n\n";
    $text .= "Thank you for contacting {$site}. We have received your request and our team will call you shortly to confirm the details and share your quote.\n\n";
    if ($fields) {
        $text .= "Your request:\n";
        foreach ($fields as $k => $v) {
            $text .= "  {$k}: {$v}\n";
        }
        $text .= "\n";
    }
    $text .= "Need help sooner? Call us on {$phone}.\n\n";
    $text .= "Regards,\nTeam {$site}\n{$siteUrl}\n";

    // ---- HTML ------------------------------------------------------------
    $h = fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $rows = '';
    foreach ($fields as $k => $v) {
        $rows .= '<tr><td style="padding:8px 0;color:#5b6478;font-size:14px;width:40%;">' . $h($k) . '</td>'
               . '<td style="padding:8px 0;color:#16294d;font-size:14px;font-weight:600;">' . $h($v) . '</td></tr>';
    }
    $details = $rows === '' ? '' :
        '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:8px 0 24px;border-top:1px solid #e4e9f3;border-bottom:1px solid #e4e9f3;">' . $rows . '</table>';

    $waBtn = $whatsapp === '' ? '' :
        '<a href="https://wa.me/' . $h($whatsapp) . '" style="display:inline-block;margin:4px;padding:12px 22px;background:#25D366;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;">WhatsApp us</a>';

    $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f4f7fc;font-family:Poppins,Segoe UI,Arial,sans-serif;">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fc;padding:24px 12px;"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e4e9f3;">'
        // Header band
        . '<tr><td style="background:#16294d;padding:22px 28px;color:#ffffff;font-size:20px;font-weight:700;">' . $h($site)
        . '<div style="font-size:13px;font-weight:400;color:rgba(255,255,255,.8);margin-top:4px;">Moving plans made simpler</div></td></tr>'
        // Body
        . '<tr><td style="padding:28px;">'
        . '<h1 style="margin:0 0 12px;font-size:22px;color:#16294d;">Thank you, ' . $h($first) . '!</h1>'
        . '<p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#374151;">We have received your request. Our team will call you shortly to confirm the details and share your quote.</p>'
        . ($details !== '' ? '<p style="margin:0;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#1D4ED8;">Your request</p>' . $details : '')
        . '<p style="margin:0 0 16px;font-size:15px;line-height:1.6;color:#374151;">Need help sooner? Reach us directly:</p>'
        . '<div style="text-align:center;margin-bottom:8px;">'
        . '<a href="tel:' . $h(preg_replace('/\D/', '', $phone)) . '" style="display:inline-block;margin:4px;padding:12px 22px;background:#F2760A;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;font-size:14px;">Call ' . $h($phone) . '</a>'
        . $waBtn
        . '</div>'
        . '</td></tr>'
        // Footer
        . '<tr><td style="background:#f4f7fc;padding:18px 28px;font-size:12px;line-height:1.6;color:#5b6478;text-align:center;">'
        . 'You are receiving this email because you submitted a request on <a href="' . $h($siteUrl) . '" style="color:#1D4ED8;">' . $h($site) . '</a>.'
        . '</td></tr>'
        . '</table></td></tr></table></body></html>';

    return [$subject, $text, $html];
}
