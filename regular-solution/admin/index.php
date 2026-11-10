<?php
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$admin    = rs_require_admin();
$posts    = rs_post_counts();
$bookings = rs_booking_counts();
$daily    = rs_booking_daily(14);
$recentB  = rs_bookings(null, '', 6);
$recentP  = rs_posts(null, '', 4);
$services = rs_top_services(5);
$subs     = rs_subscriber_count();

$today     = date('Y-m-d');
$weekAgo   = date('Y-m-d', strtotime('-6 days'));
$thisWeek  = rs_count_between('bookings', 'received_at', $weekAgo, $today);
$lastWeek  = rs_count_between('bookings', 'received_at', date('Y-m-d', strtotime('-13 days')), date('Y-m-d', strtotime('-7 days')));
$subsWeek  = rs_count_between('subscribers', 'created_at', $weekAgo, $today);
$subsPrev  = rs_count_between('subscribers', 'created_at', date('Y-m-d', strtotime('-13 days')), date('Y-m-d', strtotime('-7 days')));
$handled   = $bookings['total'] > 0 ? (int) round(($bookings['total'] - $bookings['new']) / $bookings['total'] * 100) : 0;
$maxDaily  = max(1, max($daily));
$hour      = (int) date('G');
$greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

rs_admin_header('Dashboard', 'dashboard', $admin);
?>

<section class="hero-card mb-4 fade-up">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <p class="mb-1 small text-uppercase fw-600" style="letter-spacing:.1em;color:#ffd166"><?= e(date('l, d F Y')) ?></p>
            <h1><?= $greeting ?>, <?= e(ucfirst($admin['username'])) ?></h1>
            <p>
                <?php if ($bookings['new'] > 0): ?>
                    You have <strong class="text-white"><?= $bookings['new'] ?> new booking request<?= $bookings['new'] === 1 ? '' : 's' ?></strong> waiting for a call back.
                <?php else: ?>
                    Every booking request has been handled. Nice work!
                <?php endif; ?>
            </p>
        </div>
        <div class="page-actions">
            <a class="btn btn-light-x" href="blog-form.php"><i class="bi bi-pencil-square"></i> Write a blog</a>
            <a class="btn btn-primary" href="bookings.php?status=new"><i class="bi bi-inbox"></i> Review requests</a>
        </div>
    </div>
    <div class="hero-stat">
        <div><strong class="num"><?= $bookings['today'] ?></strong>Requests today</div>
        <div><strong class="num"><?= $thisWeek ?></strong>Last 7 days</div>
        <div><strong class="num"><?= $handled ?>%</strong>Requests handled</div>
        <div><strong class="num"><?= $posts['published'] ?></strong>Live blogs</div>
    </div>
</section>

<div class="row g-4 mb-4">
    <?php
    $kpis = [
        ['bookings.php?status=new', 't-amber', 'bi-bell', 'New requests', $bookings['new'], '<span>Waiting for first contact</span>', ''],
        ['bookings.php', 't-blue', 'bi-inbox', 'Requests this week', $thisWeek, rs_delta($thisWeek, $lastWeek) . '<span>vs previous 7 days</span>', rs_sparkline($daily)],
        ['subscribers.php', 't-green', 'bi-envelope-paper', 'Subscribers', $subs, rs_delta($subsWeek, $subsPrev) . '<span>' . $subsWeek . ' joined this week</span>', ''],
        ['blogs.php', 't-violet', 'bi-journal-richtext', 'Blog posts', $posts['total'], '<span>' . $posts['published'] . ' published · ' . $posts['draft'] . ' draft' . ($posts['draft'] === 1 ? '' : 's') . '</span>', ''],
    ];
    foreach ($kpis as $i => [$href, $tone, $icon, $label, $value, $foot, $spark]): ?>
        <div class="col-sm-6 col-xl-3 fade-up d<?= $i + 1 ?>">
            <a class="card-x kpi" href="<?= $href ?>">
                <div class="kpi-top"><span class="kpi-label"><?= e($label) ?></span><span class="kpi-icon <?= $tone ?>"><i class="bi <?= $icon ?>"></i></span></div>
                <div class="kpi-row"><div class="kpi-value num"><?= $value ?></div><?= $spark ?></div>
                <div class="kpi-foot"><?= $foot ?></div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8 fade-up d2">
        <div class="card-x h-100">
            <div class="card-x-head">
                <div><h2>Booking requests</h2><p>Last 14 days · <?= array_sum($daily) ?> total</p></div>
                <span class="chip"><i class="bi bi-circle-fill" style="color:var(--accent);font-size:.5rem"></i> Today</span>
            </div>
            <div class="card-x-body">
                <div class="bars" role="img" aria-label="Bar chart of booking requests per day for the last 14 days">
                    <?php foreach ($daily as $day => $n):
                        $h = $n === 0 ? 0 : max(6, (int) round($n / $maxDaily * 100)); ?>
                        <div class="bar<?= $n === 0 ? ' zero' : '' ?><?= $day === $today ? ' today' : '' ?>" style="--h: <?= $h ?>%">
                            <span style="animation-delay: <?= array_search($day, array_keys($daily)) * 30 ?>ms"></span>
                            <div class="tip"><?= $n ?> request<?= $n === 1 ? '' : 's' ?> · <?= e(date('d M', strtotime($day))) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="bar-labels">
                    <?php foreach (array_keys($daily) as $day): ?>
                        <span><?= $day === $today ? 'Today' : e(date('d', strtotime($day))) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4 fade-up d3">
        <div class="card-x h-100">
            <div class="card-x-head"><div><h2>Pipeline</h2><p>Where every request stands</p></div></div>
            <div class="card-x-body">
                <?php if ($bookings['total'] === 0): ?>
                    <?= rs_empty('bi-pie-chart', 'No data yet', 'Your pipeline fills up as requests come in.') ?>
                <?php else: ?>
                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="kpi-value num"><?= $bookings['total'] ?></span><span class="text-3">total requests</span>
                    </div>
                    <div class="stack" aria-hidden="true">
                        <?php foreach (RS_BOOKING_STATUSES as $key => $label): if (!$bookings[$key]) continue; ?>
                            <span class="c-<?= $key ?>" style="width: <?= $bookings[$key] / $bookings['total'] * 100 ?>%"></span>
                        <?php endforeach; ?>
                    </div>
                    <ul class="legend">
                        <?php foreach (RS_BOOKING_STATUSES as $key => $label): ?>
                            <li>
                                <span class="sw c-<?= $key ?>"></span>
                                <a class="text-2" href="bookings.php?status=<?= $key ?>"><?= $label ?></a>
                                <span class="val num"><?= $bookings[$key] ?></span>
                                <span class="pct num"><?= round($bookings[$key] / $bookings['total'] * 100) ?>%</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-8 fade-up d3">
        <div class="card-x mb-4">
            <div class="card-x-head">
                <div><h2>Latest booking requests</h2><p>Newest enquiries from your website forms</p></div>
                <a class="btn btn-sm btn-light-x" href="bookings.php">View all <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php if (!$recentB): ?>
                <?= rs_empty('bi-inbox', 'No requests yet', 'Every quote form submitted on your website will land here instantly.') ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-x">
                        <thead><tr><th>Customer</th><th>Service</th><th>Received</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($recentB as $b): ?>
                            <tr class="<?= $b['status'] === 'new' ? 'row-new' : '' ?>">
                                <td>
                                    <div class="person">
                                        <?= rs_avatar($b['name']) ?>
                                        <div class="min-w-0">
                                            <a class="cell-main" href="booking.php?id=<?= (int) $b['id'] ?>"><?= e($b['name']) ?></a>
                                            <div class="cell-sub num"><?= e($b['phone']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="cell-main fw-normal"><?= e($b['service'] ?: '—') ?></div>
                                    <?php if ($b['from_area'] || $b['to_area']): ?>
                                        <div class="cell-sub"><?= e($b['from_area'] ?: '?') ?> → <?= e($b['to_area'] ?: '?') ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap" title="<?= e(date('d M Y, h:i A', strtotime($b['received_at']))) ?>"><?= e(rs_time_ago($b['received_at'])) ?></td>
                                <td><?= rs_status_pill($b['status']) ?></td>
                                <td class="text-end">
                                    <div class="row-actions">
                                        <a class="btn btn-sm btn-ghost btn-icon" href="tel:+91<?= e($b['phone']) ?>" title="Call" aria-label="Call <?= e($b['name']) ?>"><i class="bi bi-telephone"></i></a>
                                        <a class="btn btn-sm btn-light-x" href="booking.php?id=<?= (int) $b['id'] ?>">Open</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card-x">
            <div class="card-x-head">
                <div><h2>Recent blogs</h2><p>Your latest posts and drafts</p></div>
                <a class="btn btn-sm btn-light-x" href="blogs.php">Manage blogs <i class="bi bi-arrow-right"></i></a>
            </div>
            <?php if (!$recentP): ?>
                <?= rs_empty('bi-journal-plus', 'Start your blog', 'Helpful articles bring visitors from Google. Publish your first one today.', '<a class="btn btn-primary" href="blog-form.php"><i class="bi bi-plus-lg"></i> Write first blog</a>') ?>
            <?php else: ?>
                <ul class="list-x">
                    <?php foreach ($recentP as $p): ?>
                        <li>
                            <?php if ($p['image']): ?>
                                <img class="thumb" src="../<?= e($p['image']) ?>" alt="" width="72" height="48">
                            <?php else: ?>
                                <span class="thumb"><i class="bi bi-image"></i></span>
                            <?php endif; ?>
                            <div class="flex-grow-1 min-w-0">
                                <a class="cell-main d-block text-truncate" href="blog-form.php?id=<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a>
                                <div class="cell-sub">Updated <?= e(rs_time_ago($p['updated_at'])) ?><?= $p['category'] ? ' · ' . e($p['category']) : '' ?></div>
                            </div>
                            <?= rs_post_pill($p['status']) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4 fade-up d4">
        <div class="card-x mb-4">
            <div class="card-x-head"><h2>Quick actions</h2></div>
            <div class="card-x-body">
                <div class="quick">
                    <a href="blog-form.php"><i class="bi bi-pencil-square"></i>Write a blog</a>
                    <a href="bookings.php?status=new"><i class="bi bi-telephone-outbound"></i>Call new leads</a>
                    <a href="settings.php?tab=contact"><i class="bi bi-telephone"></i>Edit contact info</a>
                    <a href="settings.php?tab=brand"><i class="bi bi-image"></i>Change logo</a>
                    <a href="bookings.php?export=csv"><i class="bi bi-file-earmark-spreadsheet"></i>Export leads</a>
                    <a href="../index.html" target="_blank" rel="noopener"><i class="bi bi-globe2"></i>View website</a>
                </div>
            </div>
        </div>

        <div class="card-x">
            <div class="card-x-head"><div><h2>Top services</h2><p>Most requested by customers</p></div></div>
            <div class="card-x-body">
                <?php if (!$services): ?>
                    <div class="empty empty-sm"><div class="empty-icon"><i class="bi bi-bar-chart"></i></div><p class="mb-0">No service data yet.</p></div>
                <?php else:
                    $top = max(1, (int) $services[0]['n']);
                    foreach ($services as $s): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1 small">
                                <span class="fw-600"><?= e($s['service']) ?></span><span class="text-3 num"><?= (int) $s['n'] ?></span>
                            </div>
                            <div class="meter"><span style="width: <?= (int) $s['n'] / $top * 100 ?>%"></span></div>
                        </div>
                    <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
rs_admin_footer();
