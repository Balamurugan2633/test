<?php
/**
 * Public, read-only list of published blogs for blog.html (and the home page).
 * GET api/posts.php?limit=6
 */

declare(strict_types=1);

require_once __DIR__ . '/blog-lib.php';

$limit = max(0, min(50, (int) ($_GET['limit'] ?? 0)));

$posts = array_map(fn (array $p) => [
    'title'    => $p['title'],
    'slug'     => $p['slug'],
    'url'      => 'post.php?slug=' . rawurlencode($p['slug']),
    'category' => $p['category'],
    'excerpt'  => $p['excerpt'],
    'image'    => $p['image'] !== '' ? $p['image'] : 'images/blog/blog-5.jpg',
    'date'     => rs_format_date($p['published_at']),
    'minutes'  => rs_reading_minutes($p['content']),
], rs_posts('published', '', $limit));

header('Cache-Control: public, max-age=60');
rs_json(['ok' => true, 'posts' => $posts]);
