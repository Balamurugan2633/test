<?php
/**
 * Site-wide settings edited in Admin > Settings (contact details, logo, social
 * links, footer, landing page text).
 *
 * HOW IT WORKS
 * The pages stay plain static HTML. Every editable spot carries a marker:
 *   data-site="phone"              -> the element's content is replaced
 *   data-site-href="tel:phone"     -> its href is rebuilt (tel:/mailto:/wa:/url:)
 *   data-site-src="logo"           -> its src is replaced
 * Saving settings rewrites those spots in every page file, so the site keeps
 * working with no PHP at all (e.g. on Netlify, once the files are deployed).
 * To make a new spot editable, add the marker to the HTML.
 */

declare(strict_types=1);

require_once __DIR__ . '/blog-lib.php';

const RS_SITE_ROOT    = __DIR__ . '/..';
const RS_LOGO_DIR     = __DIR__ . '/../images/site';
const RS_LOGO_URL     = 'images/site';
const RS_KEEP_BACKUPS = 10;

/**
 * Editable fields: key => [group, label, type, default, hint].
 * Types: text, textarea, tel, email, url, image.
 */
function rs_setting_fields(): array
{
    return [
        // Contact details (all pages)
        'phone'          => ['contact', 'Main phone number', 'tel', '8925516635', 'Shown in the top bar, footer, contact page and call buttons on every page except the home page.'],
        'phone2'         => ['contact', 'Second phone number', 'tel', '8925516636', 'Shown next to the main number in the footer and on the contact and thank-you pages.'],
        'whatsapp'       => ['contact', 'WhatsApp number', 'tel', '918925516635', 'With country code, e.g. 919876543210. Used by every WhatsApp button.'],
        'email'          => ['contact', 'Email address', 'email', 'info@regularsolution.com', ''],
        'address'        => ['contact', 'Office address', 'textarea', "No.31/12, Avvai Nagar,\nThiruvanmiyur, Chennai – 600041", 'Use a new line where the footer should break the address.'],
        'location_short' => ['contact', 'Short location (top bar)', 'text', 'Thiruvanmiyur, Ch‑41', ''],
        'hours'          => ['contact', 'Opening hours', 'text', 'Mon – Sun, 24 Hhrs', ''],
        'map_query'      => ['contact', 'Map location (contact page)', 'text', 'Thiruvanmiyur, Chennai 600041', 'What to search on Google Maps, e.g. your full address.'],

        // Brand & social
        'logo'      => ['brand', 'Logo', 'image', 'images/logo.png', 'PNG, JPG or WEBP, up to 2 MB. A wide logo looks best (about 420 × 215, transparent PNG).'],
        'facebook'  => ['brand', 'Facebook page URL', 'url', 'https://www.facebook.com/', ''],
        'instagram' => ['brand', 'Instagram URL', 'url', 'https://www.instagram.com/', ''],
        'youtube'   => ['brand', 'YouTube URL', 'url', 'https://www.youtube.com/', ''],

        // Footer (all pages)
        'footer_about' => ['footer', 'Footer "about" text', 'textarea', "Regular Solution is Chennai's single platform for trusted home services. We connect families with verified professionals for AC service, cleaning, electrical, plumbing, painting, shifting and more — at transparent prices, all day, every day.", ''],
        'copyright'    => ['footer', 'Copyright text', 'text', 'Regular Solution. All Rights Reserved.', 'The year is added automatically in front.'],

        // Home / landing page
        'landing_phone'      => ['landing', 'Home page phone number', 'tel', '8925516635', 'The home page (ads landing page) uses its own number for its call buttons and footer.'],
        'lp_announce'        => ['landing', 'Top announcement bar', 'text', 'Moving plans made simpler', ''],
        'lp_eyebrow'         => ['landing', 'Small label above the heading', 'text', 'Regular Solution', ''],
        'lp_hero_highlight'  => ['landing', 'Main heading — highlighted part', 'text', 'Packers and Movers', 'Shown in blue.'],
        'lp_hero_title'      => ['landing', 'Main heading — rest', 'text', '– Safe & Reliable Shifting Services', ''],
        'lp_hero_text'       => ['landing', 'Text under the heading', 'textarea', 'Looking for professional packers and movers? Regular Solution helps you plan home shifting, apartment relocation and office moves with packing, loading and transportation services tailored to your requirements.', ''],
        'lp_cta_title'       => ['landing', 'Bottom call-to-action heading', 'text', 'Planning to Move?', ''],
        'lp_cta_text'        => ['landing', 'Bottom call-to-action text', 'textarea', 'Make your relocation planning easier with a customized estimate from Regular Solution. Tell us where you are moving from, where you are going and what needs to be shifted.', ''],
        'lp_footer_title'    => ['landing', 'Footer heading', 'text', 'Regular Solution – Packers and Movers', ''],
        'lp_footer_tagline'  => ['landing', 'Footer tagline', 'text', 'Moving enquiry and service-provider connection platform.', ''],
        'lp_footer_services' => ['landing', 'Footer services line', 'text', 'Home shifting | Office relocation | Local shifting | Intercity relocation | Car & bike transport | Packing and unpacking', ''],
        'website'            => ['landing', 'Website address', 'url', 'https://regularsolution.in', ''],
    ];
}

function rs_settings(): array
{
    $defaults = array_map(fn ($f) => $f[3], rs_setting_fields());
    $saved    = rs_db()->query("SELECT value FROM settings WHERE key = 'site'")->fetchColumn();
    $saved    = $saved ? (json_decode($saved, true) ?: []) : [];
    return array_merge($defaults, array_intersect_key($saved, $defaults));
}

/** Check and tidy submitted values. Returns [array $clean, array $errors]. */
function rs_settings_validate(array $input, array $current): array
{
    $clean  = $current;
    $errors = [];

    foreach (rs_setting_fields() as $key => [, $label, $type]) {
        if ($type === 'image') {
            continue;
        }
        $value = trim(str_replace("\r\n", "\n", (string) ($input[$key] ?? '')));
        if ($type !== 'textarea') {
            $value = preg_replace('/\s+/', ' ', $value);
        }

        if ($value === '') {
            $errors[$key] = $label . ' cannot be empty.';
        } elseif (mb_strlen($value) > ($type === 'textarea' ? 1000 : 200)) {
            $errors[$key] = $label . ' is too long.';
        } elseif ($type === 'tel' && !preg_match('/^\+?[\d\s-]{8,16}$/', $value)) {
            $errors[$key] = 'Enter a valid phone number (digits only, 8–15 long).';
        } elseif ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$key] = 'Enter a valid email address.';
        } elseif ($type === 'url' && (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('~^https?://~i', $value))) {
            $errors[$key] = 'Enter a full link starting with https://';
        }
        $clean[$key] = $value;
    }

    return [$clean, $errors];
}

function rs_settings_store(array $values): void
{
    rs_db()->prepare("INSERT INTO settings (key, value) VALUES ('site', ?)
                      ON CONFLICT(key) DO UPDATE SET value = excluded.value")
        ->execute([json_encode($values, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
}

/* --- Logo upload ------------------------------------------------------ */

/** Returns [string $path, string $error]; $path is '' when no file was chosen. */
function rs_logo_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['', ''];
    }
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2 * 1024 * 1024) {
        return ['', 'The logo must be an image of 2 MB or less.'];
    }
    $info    = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_WEBP => 'webp'];
    if ($info === false || !isset($allowed[$info[2]])) {
        return ['', 'Please upload a PNG, JPG or WEBP logo.'];
    }
    if (!is_dir(RS_LOGO_DIR)) {
        @mkdir(RS_LOGO_DIR, 0775, true);
    }
    $name = 'logo-' . date('Ymd') . '-' . bin2hex(random_bytes(4)) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], RS_LOGO_DIR . '/' . $name)) {
        return ['', 'The logo could not be saved on the server.'];
    }
    return [RS_LOGO_URL . '/' . $name, ''];
}

function rs_logo_delete(string $path): void
{
    if (str_starts_with($path, RS_LOGO_URL . '/') && is_file(RS_SITE_ROOT . '/' . $path)) {
        @unlink(RS_SITE_ROOT . '/' . $path);
    }
}

/* --- Publishing into the page files ----------------------------------- */

/** @return string[] absolute paths of every page that carries markers */
function rs_site_pages(): array
{
    $files = array_merge(glob(RS_SITE_ROOT . '/*.html') ?: [], glob(RS_SITE_ROOT . '/*.php') ?: []);
    return array_values(array_filter($files, fn ($f) => str_contains((string) file_get_contents($f), 'data-site')));
}

function rs_digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

/** Inner HTML for each data-site="..." key. */
function rs_marker_html(array $s): array
{
    $lines = array_values(array_filter(array_map('trim', explode("\n", $s['address']))));
    return [
        'phone'              => e($s['phone']),
        'phone2'             => e($s['phone2']),
        'landing_phone'      => e($s['landing_phone']),
        'email'              => e($s['email']),
        'address'            => implode('<br>', array_map('e', $lines)),
        'address_line'       => e(implode(' ', $lines)),
        'location_short'     => e($s['location_short']),
        'hours'              => e($s['hours']),
        'footer_about'       => nl2br(e($s['footer_about']), false),
        'copyright'          => e($s['copyright']),
        'lp_announce'        => e($s['lp_announce']),
        'lp_eyebrow'         => e($s['lp_eyebrow']),
        'lp_hero_title'      => '<span class="hl">' . e($s['lp_hero_highlight']) . '</span> ' . e($s['lp_hero_title']),
        'lp_hero_text'       => nl2br(e($s['lp_hero_text']), false),
        'lp_cta_title'       => e($s['lp_cta_title']),
        'lp_cta_text'        => nl2br(e($s['lp_cta_text']), false),
        'lp_footer_title'    => e($s['lp_footer_title']),
        'lp_footer_tagline'  => e($s['lp_footer_tagline']),
        'lp_footer_services' => e($s['lp_footer_services']),
        'website'            => e($s['website']),
    ];
}

/** Attribute value for data-site-href="kind:key" / data-site-src="key". */
function rs_marker_attr(string $spec, array $s, string $old): ?string
{
    [$kind, $key] = str_contains($spec, ':') ? explode(':', $spec, 2) : ['src', $spec];

    if ($kind === 'src' && $key === 'map') {
        return 'https://maps.google.com/maps?q=' . rawurlencode($s['map_query']) . '&t=&z=14&ie=UTF8&iwloc=&output=embed';
    }
    if (!isset($s[$key])) {
        return null;
    }
    $value = $s[$key];

    switch ($kind) {
        case 'tel':
            return 'tel:' . rs_digits($value);
        case 'mailto':
            return 'mailto:' . $value;
        case 'wa':
            $d = rs_digits($value);
            return 'https://wa.me/' . (strlen($d) === 10 ? '91' . $d : $d);
        case 'url':
            return $value;
        case 'src':
            // Pages served from the site root (404.html) use an absolute path.
            return (str_starts_with($old, '/') ? '/' : '') . ltrim($value, '/');
    }
    return null;
}

/** Rewrite one page's markup. Returns the new HTML and how many spots changed. */
function rs_apply_settings(string $html, array $s, array $previous): array
{
    $inner   = rs_marker_html($s);
    $changed = 0;

    $html = preg_replace_callback(
        '~(<([a-z][a-z0-9]*)\b[^>]*?\sdata-site="([a-z_]+)"[^>]*>)(.*?)(</\2>)~is',
        function ($m) use ($inner, &$changed) {
            if (!isset($inner[$m[3]]) || $m[4] === $inner[$m[3]]) {
                return $m[0];
            }
            $changed++;
            return $m[1] . $inner[$m[3]] . $m[5];
        },
        $html
    );

    $html = preg_replace_callback(
        '~<[a-z][a-z0-9]*\b[^>]*\sdata-site-(href|src)="([^"]+)"[^>]*>~i',
        function ($m) use ($s, &$changed) {
            $attr = strtolower($m[1]);
            if (!preg_match('~\s' . $attr . '="([^"]*)"~i', $m[0], $old)) {
                return $m[0];
            }
            $new = rs_marker_attr($m[2], $s, html_entity_decode($old[1], ENT_QUOTES, 'UTF-8'));
            if ($new === null || e($new) === $old[1]) {
                return $m[0];
            }
            $changed++;
            return str_replace($old[0], ' ' . $attr . '="' . e($new) . '"', $m[0]);
        },
        $html
    );

    // Page titles / meta descriptions mention the phone number and email as plain text.
    $headEnd = stripos($html, '</head>');
    if ($headEnd !== false) {
        $head = substr($html, 0, $headEnd);
        foreach (['phone', 'email'] as $key) {
            if (($previous[$key] ?? '') !== '' && $previous[$key] !== $s[$key]) {
                $head = str_replace(e($previous[$key]), e($s[$key]), $head, $n);
                $changed += $n;
            }
        }
        $html = $head . substr($html, $headEnd);
    }

    return [$html, $changed];
}

/**
 * Write the settings into every page. Returns [int $pagesChanged, string[] $problems].
 * A copy of each page is kept in api/storage/backups/ before it is overwritten.
 */
function rs_publish_settings(array $s, array $previous): array
{
    $backupDir = rs_storage_dir() . '/backups/' . date('Ymd-His');
    $pages     = 0;
    $problems  = [];

    foreach (rs_site_pages() as $file) {
        $html = (string) file_get_contents($file);
        [$new, $changed] = rs_apply_settings($html, $s, $previous);
        if ($changed === 0 || $new === $html) {
            continue;
        }
        if (!is_writable($file)) {
            $problems[] = basename($file) . ' is not writable by the web server.';
            continue;
        }
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0775, true);
        }
        @copy($file, $backupDir . '/' . basename($file));
        if (file_put_contents($file, $new, LOCK_EX) === false) {
            $problems[] = basename($file) . ' could not be saved.';
            continue;
        }
        $pages++;
    }

    rs_prune_backups();
    return [$pages, $problems];
}

function rs_prune_backups(): void
{
    $dirs = glob(rs_storage_dir() . '/backups/*', GLOB_ONLYDIR) ?: [];
    rsort($dirs);
    foreach (array_slice($dirs, RS_KEEP_BACKUPS) as $dir) {
        array_map('unlink', glob($dir . '/*') ?: []);
        @rmdir($dir);
    }
}
