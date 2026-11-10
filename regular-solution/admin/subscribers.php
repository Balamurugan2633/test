<?php
/**
 * Emails collected by the newsletter box on every page.
 */
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$admin  = rs_require_admin();
$search = trim((string) ($_GET['q'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    rs_csrf_check();
    rs_db()->prepare('DELETE FROM subscribers WHERE id = ?')->execute([(int) ($_POST['id'] ?? 0)]);
    rs_flash('success', 'Subscriber removed from the list.');
    rs_redirect('subscribers.php');
}

$rows = rs_subscribers($search);

if (($_GET['export'] ?? '') === 'csv') {
    rs_download_csv('subscribers-' . date('Y-m-d') . '.csv', ['Email', 'Subscribed', 'Page'],
        array_map(fn ($s) => [$s['email'], $s['created_at'], $s['page']], $rows));
}

$total    = rs_subscriber_count();
$thisWeek = rs_count_between('subscribers', 'created_at', date('Y-m-d', strtotime('-6 days')), date('Y-m-d'));
$month    = rs_count_between('subscribers', 'created_at', date('Y-m-01'), date('Y-m-d'));

rs_admin_header('Subscribers', 'subscribers', $admin, ['Customers' => 'bookings.php']);
?>
<div class="page-head fade-up">
    <div>
        <h1>Subscribers</h1>
        <p>People who joined your newsletter from the "Follow Us" box on the website.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-light-x" href="?<?= e(http_build_query(array_filter(['q' => $search, 'export' => 'csv']))) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Export to Excel</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php foreach ([['t-green', 'bi-people', 'Total subscribers', $total], ['t-blue', 'bi-calendar-week', 'Joined this week', $thisWeek], ['t-violet', 'bi-calendar-month', 'Joined this month', $month]] as $i => [$tone, $icon, $label, $value]): ?>
        <div class="col-md-4 fade-up d<?= $i + 1 ?>">
            <div class="card-x kpi">
                <div class="kpi-top"><span class="kpi-label"><?= $label ?></span><span class="kpi-icon <?= $tone ?>"><i class="bi <?= $icon ?>"></i></span></div>
                <div class="kpi-value num"><?= $value ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card-x fade-up d2">
    <div class="toolbar">
        <h2 class="h6 fw-bold m-0"><?= count($rows) ?> <?= $search !== '' ? 'matching' : '' ?> subscriber<?= count($rows) === 1 ? '' : 's' ?></h2>
        <form class="search-box" method="get" role="search">
            <i class="bi bi-search"></i>
            <input class="form-control form-control-sm" type="search" name="q" value="<?= e($search) ?>" placeholder="Search email…" aria-label="Search subscribers">
        </form>
    </div>

    <?php if (!$rows): ?>
        <?= $search !== ''
            ? rs_empty('bi-search', 'No matches', 'No subscriber email contains "' . $search . '".', '<a class="btn btn-light-x" href="subscribers.php">Clear search</a>')
            : rs_empty('bi-envelope-heart', 'No subscribers yet', 'Emails entered in the "Follow Us" box on any page of your website will appear here.') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-x">
                <thead><tr><th>Subscriber</th><th>Signed up on page</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $s): ?>
                    <tr>
                        <td>
                            <div class="person">
                                <?= rs_avatar($s['email'], 'sm') ?>
                                <a class="cell-main" href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a>
                            </div>
                        </td>
                        <td><?= $s['page'] ? '<span class="chip">' . e($s['page']) . '</span>' : '<span class="text-3">—</span>' ?></td>
                        <td class="text-nowrap"><div class="text-body"><?= e(rs_time_ago($s['created_at'])) ?></div><div class="cell-sub"><?= e(date('d M Y', strtotime($s['created_at']))) ?></div></td>
                        <td class="text-end">
                            <div class="row-actions">
                                <a class="btn btn-sm btn-ghost btn-icon" href="mailto:<?= e($s['email']) ?>" title="Send email" aria-label="Email <?= e($s['email']) ?>"><i class="bi bi-envelope"></i></a>
                                <?= rs_confirm_form('subscribers.php', ['action' => 'delete', 'id' => $s['id']],
                                    '<button class="btn btn-sm btn-ghost btn-icon text-danger" type="submit" title="Remove" aria-label="Remove ' . e($s['email']) . '"><i class="bi bi-trash3"></i></button>',
                                    $s['email'] . ' will be removed from your subscriber list.', 'Remove subscriber?') ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php
rs_admin_footer();
