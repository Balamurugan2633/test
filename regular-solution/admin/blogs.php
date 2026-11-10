<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$admin = rs_require_admin();

// Delete (POST + CSRF only).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    rs_csrf_check();
    $post = rs_post_find((int) ($_POST['id'] ?? 0));
    if ($post) {
        rs_post_delete((int) $post['id']);
        rs_flash('success', '"' . $post['title'] . '" was deleted.');
    } else {
        rs_flash('error', 'That blog no longer exists.');
    }
    rs_redirect('blogs.php');
}

$status = $_GET['status'] ?? '';
$status = in_array($status, ['published', 'draft'], true) ? $status : null;
$search = trim((string) ($_GET['q'] ?? ''));
$posts  = rs_posts($status, $search);
$counts = rs_post_counts();

rs_admin_header('All Blogs', 'blogs', $admin, ['Content' => 'blogs.php']);
?>
<div class="page-head fade-up">
    <div>
        <h1>Blogs</h1>
        <p>Helpful articles bring visitors from Google and build trust. Published posts appear on your Blog page instantly.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-light-x" href="../blog.html" target="_blank" rel="noopener"><i class="bi bi-eye"></i> View blog page</a>
        <a class="btn btn-primary" href="blog-form.php"><i class="bi bi-plus-lg"></i> Write new blog</a>
    </div>
</div>

<div class="card-x fade-up d1">
    <div class="toolbar">
        <nav class="segmented" aria-label="Filter by status">
            <a class="<?= $status === null ? 'active' : '' ?>" href="blogs.php">All <span class="count num"><?= $counts['total'] ?></span></a>
            <a class="<?= $status === 'published' ? 'active' : '' ?>" href="?status=published">Published <span class="count num"><?= $counts['published'] ?></span></a>
            <a class="<?= $status === 'draft' ? 'active' : '' ?>" href="?status=draft">Drafts <span class="count num"><?= $counts['draft'] ?></span></a>
        </nav>
        <form class="search-box" method="get" role="search">
            <i class="bi bi-search"></i>
            <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <input class="form-control form-control-sm" type="search" name="q" value="<?= e($search) ?>" placeholder="Search title or category…" aria-label="Search blogs">
        </form>
    </div>

    <?php if (!$posts): ?>
        <?= $search !== ''
            ? rs_empty('bi-search', 'No matches', 'No blog title or category matches "' . $search . '".', '<a class="btn btn-light-x" href="blogs.php">Clear search</a>')
            : rs_empty('bi-journal-plus', 'Nothing published yet', 'Write your first article — tips, guides and news help customers find you on Google.', '<a class="btn btn-primary" href="blog-form.php"><i class="bi bi-plus-lg"></i> Write new blog</a>') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-x">
                <thead><tr><th>Article</th><th>Category</th><th>Status</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <div class="post-row">
                                <?php if ($post['image']): ?>
                                    <img class="thumb" src="../<?= e($post['image']) ?>" alt="" width="72" height="48" loading="lazy">
                                <?php else: ?>
                                    <span class="thumb"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                                <div class="min-w-0">
                                    <a class="cell-main d-block" href="blog-form.php?id=<?= (int) $post['id'] ?>"><?= e($post['title']) ?></a>
                                    <div class="cell-sub text-truncate" style="max-width:420px"><?= e($post['excerpt']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?= $post['category'] ? '<span class="chip"><i class="bi bi-tag"></i>' . e($post['category']) . '</span>' : '<span class="text-3">—</span>' ?></td>
                        <td><?= rs_post_pill($post['status']) ?></td>
                        <td class="text-nowrap">
                            <div class="text-body"><?= e(rs_format_date($post['published_at'] ?? $post['created_at'])) ?></div>
                            <div class="cell-sub"><?= rs_reading_minutes($post['content']) ?> min read</div>
                        </td>
                        <td class="text-end">
                            <div class="row-actions">
                                <?php if ($post['status'] === 'published'): ?>
                                    <a class="btn btn-sm btn-ghost btn-icon" href="../post.php?slug=<?= urlencode($post['slug']) ?>" target="_blank" rel="noopener" title="View live" aria-label="View live"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-light-x" href="blog-form.php?id=<?= (int) $post['id'] ?>"><i class="bi bi-pencil"></i> Edit</a>
                                <?= rs_confirm_form('blogs.php', ['action' => 'delete', 'id' => $post['id']],
                                    '<button class="btn btn-sm btn-ghost btn-icon text-danger" type="submit" title="Delete" aria-label="Delete ' . e($post['title']) . '"><i class="bi bi-trash3"></i></button>',
                                    '"' . $post['title'] . '" and its cover image will be permanently deleted from your website.', 'Delete this blog?') ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-x-foot small text-3">Showing <?= count($posts) ?> of <?= $counts['total'] ?> blogs</div>
    <?php endif; ?>
</div>
<?php
rs_admin_footer();
