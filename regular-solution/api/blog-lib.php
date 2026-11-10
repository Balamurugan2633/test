<?php
/**
 * Site data layer: blogs, booking requests, newsletter subscribers, admin.
 *
 * Everything lives in one SQLite file, api/storage/site.sqlite, which the
 * storage .htaccess keeps out of reach over HTTP. No MySQL setup is needed:
 * the file and its tables are created on first use.
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

const RS_UPLOAD_DIR = __DIR__ . '/../images/blog/uploads';
const RS_UPLOAD_URL = 'images/blog/uploads';
const RS_MAX_UPLOAD = 3 * 1024 * 1024; // 3 MB

function rs_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('sqlite:' . rs_storage_dir() . '/site.sqlite', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec('CREATE TABLE IF NOT EXISTS admins (
        id            INTEGER PRIMARY KEY AUTOINCREMENT,
        username      TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        is_default    INTEGER NOT NULL DEFAULT 1,
        last_login_at TEXT
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS login_attempts (
        ip         TEXT NOT NULL,
        attempted  INTEGER NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS posts (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        title        TEXT NOT NULL,
        slug         TEXT NOT NULL UNIQUE,
        category     TEXT NOT NULL DEFAULT \'\',
        excerpt      TEXT NOT NULL DEFAULT \'\',
        content      TEXT NOT NULL DEFAULT \'\',
        image        TEXT NOT NULL DEFAULT \'\',
        status       TEXT NOT NULL DEFAULT \'draft\',
        published_at TEXT,
        created_at   TEXT NOT NULL,
        updated_at   TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS bookings (
        id          INTEGER PRIMARY KEY AUTOINCREMENT,
        received_at TEXT NOT NULL,
        form        TEXT NOT NULL DEFAULT \'\',
        name        TEXT NOT NULL DEFAULT \'\',
        phone       TEXT NOT NULL DEFAULT \'\',
        email       TEXT NOT NULL DEFAULT \'\',
        service     TEXT NOT NULL DEFAULT \'\',
        move_type   TEXT NOT NULL DEFAULT \'\',
        house_type  TEXT NOT NULL DEFAULT \'\',
        move_date   TEXT NOT NULL DEFAULT \'\',
        move_time   TEXT NOT NULL DEFAULT \'\',
        from_area   TEXT NOT NULL DEFAULT \'\',
        to_area     TEXT NOT NULL DEFAULT \'\',
        floor_from  TEXT NOT NULL DEFAULT \'\',
        floor_to    TEXT NOT NULL DEFAULT \'\',
        address     TEXT NOT NULL DEFAULT \'\',
        message     TEXT NOT NULL DEFAULT \'\',
        ip          TEXT NOT NULL DEFAULT \'\',
        status      TEXT NOT NULL DEFAULT \'new\',
        notes       TEXT NOT NULL DEFAULT \'\',
        updated_at  TEXT
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS subscribers (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        email      TEXT NOT NULL UNIQUE,
        page       TEXT NOT NULL DEFAULT \'\',
        ip         TEXT NOT NULL DEFAULT \'\',
        created_at TEXT NOT NULL
    )');

    $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
        key   TEXT PRIMARY KEY,
        value TEXT NOT NULL
    )');

    rs_import_legacy_csv($pdo);

    // First run: create the admin account from config.php.
    if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
        $admin = rs_config()['admin'];
        $stmt  = $pdo->prepare('INSERT INTO admins (username, password_hash, is_default) VALUES (?, ?, 1)');
        $stmt->execute([$admin['username'], password_hash($admin['password'], PASSWORD_DEFAULT)]);
    }

    return $pdo;
}

function rs_now(): string
{
    return date('Y-m-d H:i:s');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* --- Posts ----------------------------------------------------------- */

function rs_slugify(string $text): string
{
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    $slug = trim($slug, '-');
    return $slug !== '' ? substr($slug, 0, 80) : 'post';
}

/** Make a slug unique by appending -2, -3 ... ($ignoreId = the post being edited). */
function rs_unique_slug(string $slug, int $ignoreId = 0): string
{
    $stmt = rs_db()->prepare('SELECT COUNT(*) FROM posts WHERE slug = ? AND id != ?');
    $base = $slug;
    $n    = 2;
    while (true) {
        $stmt->execute([$slug, $ignoreId]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $n++;
    }
}

function rs_post_find(int $id): ?array
{
    $stmt = rs_db()->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function rs_post_find_published(string $slug): ?array
{
    $stmt = rs_db()->prepare("SELECT * FROM posts WHERE slug = ? AND status = 'published'");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

/** @return array<int, array> */
function rs_posts(?string $status = null, string $search = '', int $limit = 0): array
{
    $sql    = 'SELECT * FROM posts WHERE 1=1';
    $params = [];
    if ($status !== null) {
        $sql      .= ' AND status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $sql      .= ' AND (title LIKE ? OR category LIKE ?)';
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    $sql .= ' ORDER BY COALESCE(published_at, created_at) DESC, id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    $stmt = rs_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function rs_post_counts(): array
{
    $rows = rs_db()->query('SELECT status, COUNT(*) AS n FROM posts GROUP BY status')->fetchAll();
    $out  = ['published' => 0, 'draft' => 0];
    foreach ($rows as $row) {
        $out[$row['status']] = (int) $row['n'];
    }
    $out['total'] = $out['published'] + $out['draft'];
    return $out;
}

/** Insert ($id = 0) or update a post. Returns the post id. */
function rs_post_save(array $data, int $id = 0): int
{
    $db       = rs_db();
    $now      = rs_now();
    $existing = $id > 0 ? rs_post_find($id) : null;

    $publishedAt = $existing['published_at'] ?? null;
    if ($data['status'] === 'published' && $publishedAt === null) {
        $publishedAt = $now;
    }

    if ($existing) {
        $stmt = $db->prepare('UPDATE posts SET title = ?, slug = ?, category = ?, excerpt = ?, content = ?,
                              image = ?, status = ?, published_at = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([$data['title'], $data['slug'], $data['category'], $data['excerpt'], $data['content'],
                        $data['image'], $data['status'], $publishedAt, $now, $id]);
        return $id;
    }

    $stmt = $db->prepare('INSERT INTO posts (title, slug, category, excerpt, content, image, status, published_at, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$data['title'], $data['slug'], $data['category'], $data['excerpt'], $data['content'],
                    $data['image'], $data['status'], $publishedAt, $now, $now]);
    return (int) $db->lastInsertId();
}

function rs_post_delete(int $id): void
{
    $post = rs_post_find($id);
    if (!$post) {
        return;
    }
    rs_db()->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
    rs_delete_upload($post['image']);
}

function rs_reading_minutes(string $content): int
{
    $words = str_word_count(strip_tags($content));
    return max(1, (int) ceil($words / 200));
}

function rs_format_date(?string $date): string
{
    return $date ? date('d F Y', strtotime($date)) : '';
}

/**
 * Turn the plain-text editor format into safe HTML:
 *   blank line   -> new paragraph
 *   "## Heading" -> sub heading
 *   "- item"     -> bullet list
 * Everything is escaped, so no HTML typed into a post can run on the site.
 */
function rs_render_content(string $content): string
{
    $html  = '';
    $para  = [];
    $items = [];

    $flush = function () use (&$html, &$para, &$items) {
        if ($para) {
            $html .= '<p>' . implode("<br>\n", array_map('e', $para)) . "</p>\n";
            $para  = [];
        }
        if ($items) {
            $html .= "<ul>\n<li>" . implode("</li>\n<li>", array_map('e', $items)) . "</li>\n</ul>\n";
            $items = [];
        }
    };

    foreach (preg_split('/\R/', $content) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            $flush();
        } elseif (preg_match('/^#{1,3}\s+(.+)$/', $line, $m)) {
            $flush();
            $html .= '<h2>' . e($m[1]) . "</h2>\n";
        } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
            if ($para) {
                $flush();
            }
            $items[] = $m[1];
        } else {
            if ($items) {
                $flush();
            }
            $para[] = $line;
        }
    }
    $flush();

    return $html;
}

/* --- Booking requests ------------------------------------------------- */

const RS_BOOKING_STATUSES = [
    'new'       => 'New',
    'contacted' => 'Contacted',
    'confirmed' => 'Confirmed',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];

/** Columns a booking accepts, mapped to the form field each one comes from. */
const RS_BOOKING_FIELDS = [
    'received_at' => 'received', 'form' => 'form', 'name' => 'name', 'phone' => 'phone',
    'email' => 'email', 'service' => 'service', 'move_type' => 'move_type',
    'house_type' => 'house_type', 'move_date' => 'date', 'move_time' => 'time',
    'from_area' => 'from', 'to_area' => 'to', 'floor_from' => 'floor_from',
    'floor_to' => 'floor_to', 'address' => 'address', 'message' => 'message', 'ip' => 'ip',
];

/** Save a request from api/submit-form.php (keys as in its $record). Returns the id. */
function rs_booking_create(array $record, ?PDO $db = null): int
{
    $db     = $db ?? rs_db();
    $cols   = array_keys(RS_BOOKING_FIELDS);
    $values = array_map(fn ($src) => (string) ($record[$src] ?? ''), array_values(RS_BOOKING_FIELDS));
    if ($values[0] === '') {
        $values[0] = rs_now();
    }

    $stmt = $db->prepare('INSERT INTO bookings (' . implode(', ', $cols) . ')
                          VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')');
    $stmt->execute($values);
    return (int) $db->lastInsertId();
}

/**
 * Requests saved before the admin panel existed live only in submissions.csv.
 * Copy them in once; the flag stops them coming back after being deleted.
 */
function rs_import_legacy_csv(PDO $db): void
{
    $flag = $db->query("SELECT value FROM settings WHERE key = 'csv_imported'")->fetchColumn();
    if ($flag !== false) {
        return;
    }

    $csv = rs_storage_dir() . '/submissions.csv';
    if (is_file($csv) && ($handle = fopen($csv, 'r'))) {
        $header = fgetcsv($handle);
        while ($header && ($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                rs_booking_create(array_combine($header, $row), $db);
            }
        }
        fclose($handle);
    }

    $db->prepare("INSERT INTO settings (key, value) VALUES ('csv_imported', ?)")->execute([rs_now()]);
}

function rs_booking_find(int $id): ?array
{
    $stmt = rs_db()->prepare('SELECT * FROM bookings WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/** @return array<int, array> */
function rs_bookings(?string $status = null, string $search = '', int $limit = 0): array
{
    $sql    = 'SELECT * FROM bookings WHERE 1=1';
    $params = [];
    if ($status !== null) {
        $sql      .= ' AND status = ?';
        $params[] = $status;
    }
    if ($search !== '') {
        $sql .= ' AND (name LIKE ? OR phone LIKE ? OR email LIKE ? OR service LIKE ? OR from_area LIKE ? OR to_area LIKE ?)';
        array_push($params, ...array_fill(0, 6, '%' . $search . '%'));
    }
    $sql .= ' ORDER BY received_at DESC, id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    $stmt = rs_db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function rs_booking_counts(): array
{
    $out = array_fill_keys(array_keys(RS_BOOKING_STATUSES), 0);
    foreach (rs_db()->query('SELECT status, COUNT(*) AS n FROM bookings GROUP BY status') as $row) {
        $out[$row['status']] = (int) $row['n'];
    }
    $out['total'] = array_sum($out);
    $out['today'] = (int) rs_db()->query("SELECT COUNT(*) FROM bookings WHERE date(received_at) = date('now', 'localtime')")->fetchColumn();
    return $out;
}

/** Requests per day for the last $days days (oldest first): ['Y-m-d' => n]. */
function rs_booking_daily(int $days = 14): array
{
    $out = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $out[date('Y-m-d', strtotime("-{$i} days"))] = 0;
    }
    $stmt = rs_db()->prepare('SELECT date(received_at) AS d, COUNT(*) AS n FROM bookings
                              WHERE date(received_at) >= ? GROUP BY d');
    $stmt->execute([array_key_first($out)]);
    foreach ($stmt as $row) {
        if (isset($out[$row['d']])) {
            $out[$row['d']] = (int) $row['n'];
        }
    }
    return $out;
}

/** Number of rows in $table created between two dates (inclusive). */
function rs_count_between(string $table, string $column, string $from, string $to): int
{
    $stmt = rs_db()->prepare("SELECT COUNT(*) FROM {$table} WHERE date({$column}) BETWEEN ? AND ?");
    $stmt->execute([$from, $to]);
    return (int) $stmt->fetchColumn();
}

/** Most requested services: [['service' => ..., 'n' => ...], ...] */
function rs_top_services(int $limit = 5): array
{
    $stmt = rs_db()->prepare("SELECT service, COUNT(*) AS n FROM bookings WHERE service != ''
                              GROUP BY service ORDER BY n DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function rs_booking_update(int $id, string $status, string $notes): void
{
    rs_db()->prepare('UPDATE bookings SET status = ?, notes = ?, updated_at = ? WHERE id = ?')
        ->execute([$status, $notes, rs_now(), $id]);
}

function rs_booking_delete(int $id): void
{
    rs_db()->prepare('DELETE FROM bookings WHERE id = ?')->execute([$id]);
}

/* --- Newsletter subscribers ------------------------------------------- */

/** Returns false when the address was already subscribed. */
function rs_subscribe(string $email, string $page): bool
{
    $stmt = rs_db()->prepare('INSERT OR IGNORE INTO subscribers (email, page, ip, created_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([strtolower($email), $page, (string) ($_SERVER['REMOTE_ADDR'] ?? ''), rs_now()]);
    return $stmt->rowCount() > 0;
}

/** @return array<int, array> */
function rs_subscribers(string $search = ''): array
{
    $stmt = rs_db()->prepare('SELECT * FROM subscribers WHERE email LIKE ? ORDER BY created_at DESC, id DESC');
    $stmt->execute(['%' . $search . '%']);
    return $stmt->fetchAll();
}

function rs_subscriber_count(): int
{
    return (int) rs_db()->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
}

/** Stream rows as a CSV download (opens cleanly in Excel). */
function rs_download_csv(string $filename, array $header, array $rows): void
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Tamil/₹ text correctly
    fputcsv($out, $header);
    foreach ($rows as $row) {
        // A leading = + - @ would run as a formula in Excel.
        fputcsv($out, array_map(fn ($v) => preg_match('/^[=+\-@]/', (string) $v) ? "'" . $v : $v, $row));
    }
    fclose($out);
    exit;
}

/* --- Image uploads ---------------------------------------------------- */

/**
 * Store an uploaded cover image. Returns [string $path, string $error].
 * $path is '' when no file was chosen.
 */
function rs_handle_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['', ''];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['', 'The image could not be uploaded. Please try again.'];
    }
    if ($file['size'] > RS_MAX_UPLOAD) {
        return ['', 'The image must be 3 MB or smaller.'];
    }

    // Trust the file's real contents, never its name or the browser's claim.
    $info    = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if ($info === false || !isset($allowed[$info[2]])) {
        return ['', 'Please upload a JPG, PNG, WEBP or GIF image.'];
    }

    if (!is_dir(RS_UPLOAD_DIR)) {
        @mkdir(RS_UPLOAD_DIR, 0775, true);
    }
    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $allowed[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], RS_UPLOAD_DIR . '/' . $name)) {
        return ['', 'The image could not be saved on the server.'];
    }

    return [RS_UPLOAD_URL . '/' . $name, ''];
}

/** Remove an uploaded image; built-in site images are never touched. */
function rs_delete_upload(string $path): void
{
    if (!str_starts_with($path, RS_UPLOAD_URL . '/')) {
        return;
    }
    $file = RS_UPLOAD_DIR . '/' . basename($path);
    if (is_file($file)) {
        @unlink($file);
    }
}
