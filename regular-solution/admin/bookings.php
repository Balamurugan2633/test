<?php
/**
 * All quote / booking requests sent from the website's forms.
 */
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$admin = rs_require_admin();

$status = $_GET['status'] ?? '';
$status = isset(RS_BOOKING_STATUSES[$status]) ? $status : null;
$search = trim((string) ($_GET['q'] ?? ''));
$back   = 'bookings.php?' . http_build_query(array_filter(['status' => $status, 'q' => $search]));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rs_csrf_check();
    $id     = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $b      = rs_booking_find($id);
    if ($action === 'delete' && $b) {
        rs_booking_delete($id);
        rs_flash('success', 'The request from ' . $b['name'] . ' was deleted.');
    } elseif ($action === 'status' && $b && isset(RS_BOOKING_STATUSES[$_POST['status'] ?? ''])) {
        rs_booking_update($id, $_POST['status'], $b['notes']);
        rs_flash('success', $b['name'] . ' moved to ' . RS_BOOKING_STATUSES[$_POST['status']] . '.');
    }
    rs_redirect($back);
}

$rows   = rs_bookings($status, $search);
$counts = rs_booking_counts();

if (($_GET['export'] ?? '') === 'csv') {
    rs_download_csv(
        'booking-requests-' . date('Y-m-d') . '.csv',
        ['ID', 'Received', 'Status', 'Form', 'Name', 'Phone', 'Email', 'Service', 'Move type', 'House type',
         'Date', 'Time', 'From', 'To', 'Floor from', 'Floor to', 'Address', 'Message', 'Notes'],
        array_map(fn ($b) => [$b['id'], $b['received_at'], RS_BOOKING_STATUSES[$b['status']] ?? $b['status'], $b['form'],
            $b['name'], $b['phone'], $b['email'], $b['service'], $b['move_type'], $b['house_type'], $b['move_date'],
            $b['move_time'], $b['from_area'], $b['to_area'], $b['floor_from'], $b['floor_to'], $b['address'],
            $b['message'], $b['notes']], $rows)
    );
}

rs_admin_header('Booking Requests', 'bookings', $admin, ['Customers' => 'bookings.php']);
$query = fn (array $extra) => '?' . http_build_query(array_filter(array_merge(['status' => $status, 'q' => $search], $extra)));
?>
<div class="page-head fade-up">
    <div>
        <h1>Booking Requests</h1>
        <p>Every quote and contact form submitted on your website. Call new leads fast — speed wins jobs.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-light-x" href="<?= e($query(['export' => 'csv'])) ?>"><i class="bi bi-file-earmark-spreadsheet"></i> Export to Excel</a>
    </div>
</div>

<div class="card-x fade-up d1">
    <div class="toolbar">
        <nav class="segmented" aria-label="Filter by status">
            <a class="<?= $status === null ? 'active' : '' ?>" href="bookings.php<?= $search !== '' ? '?q=' . urlencode($search) : '' ?>">All <span class="count num"><?= $counts['total'] ?></span></a>
            <?php foreach (RS_BOOKING_STATUSES as $key => $label): ?>
                <a class="<?= $status === $key ? 'active' : '' ?>" href="<?= e($query(['status' => $key])) ?>"><?= $label ?> <span class="count num"><?= $counts[$key] ?></span></a>
            <?php endforeach; ?>
        </nav>
        <form class="search-box" method="get" role="search">
            <i class="bi bi-search"></i>
            <?php if ($status): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <input class="form-control form-control-sm" type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, phone, area…" aria-label="Search requests">
        </form>
    </div>

    <?php if (!$rows): ?>
        <?= $search !== ''
            ? rs_empty('bi-search', 'No matches found', 'Nothing matches "' . $search . '". Try a name, phone number or area.', '<a class="btn btn-light-x" href="bookings.php">Clear search</a>')
            : rs_empty('bi-inbox', $status ? 'Nothing here' : 'No requests yet', $status ? 'No requests have this status right now.' : 'Requests appear here the moment a visitor submits a quote form on your website.') ?>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-x">
                <thead>
                <tr><th>Customer</th><th>Service</th><th>Move</th><th>Received</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $b): ?>
                    <tr class="<?= $b['status'] === 'new' ? 'row-new' : '' ?>">
                        <td>
                            <div class="person">
                                <?= rs_avatar($b['name']) ?>
                                <div class="min-w-0">
                                    <a class="cell-main" href="booking.php?id=<?= (int) $b['id'] ?>"><?= e($b['name']) ?></a>
                                    <div class="cell-sub num">+91 <?= e($b['phone']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-body fw-500"><?= e($b['service'] ?: '—') ?></div>
                            <?php if ($b['house_type']): ?><div class="cell-sub"><?= e($b['house_type']) ?></div><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($b['from_area'] || $b['to_area']): ?>
                                <span class="route"><?= e($b['from_area'] ?: '?') ?> <i class="bi bi-arrow-right"></i> <?= e($b['to_area'] ?: '?') ?></span>
                            <?php else: ?><span class="text-3">—</span><?php endif; ?>
                            <?php if ($b['move_date']): ?><div class="cell-sub"><i class="bi bi-calendar3"></i> <?= e(date('d M Y', strtotime($b['move_date']))) ?></div><?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <div class="text-body"><?= e(rs_time_ago($b['received_at'])) ?></div>
                            <div class="cell-sub"><?= e(date('d M, h:i A', strtotime($b['received_at']))) ?></div>
                        </td>
                        <td>
                            <form method="post" class="m-0">
                                <?= rs_csrf_field() ?>
                                <input type="hidden" name="action" value="status">
                                <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                                <select class="pill-select pill-<?= e($b['status']) ?>" name="status" data-autosubmit aria-label="Status for <?= e($b['name']) ?>">
                                    <?php foreach (RS_BOOKING_STATUSES as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $b['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td class="text-end">
                            <div class="row-actions">
                                <a class="btn btn-sm btn-ghost btn-icon" href="tel:+91<?= e($b['phone']) ?>" title="Call" aria-label="Call <?= e($b['name']) ?>"><i class="bi bi-telephone"></i></a>
                                <a class="btn btn-sm btn-ghost btn-icon" href="https://wa.me/91<?= e($b['phone']) ?>" target="_blank" rel="noopener" title="WhatsApp" aria-label="WhatsApp <?= e($b['name']) ?>"><i class="bi bi-whatsapp"></i></a>
                                <a class="btn btn-sm btn-light-x" href="booking.php?id=<?= (int) $b['id'] ?>">Open</a>
                                <?= rs_confirm_form($back, ['action' => 'delete', 'id' => $b['id']],
                                    '<button class="btn btn-sm btn-ghost btn-icon text-danger" type="submit" title="Delete" aria-label="Delete request from ' . e($b['name']) . '"><i class="bi bi-trash3"></i></button>',
                                    'The request from ' . $b['name'] . ' will be permanently deleted.', 'Delete this request?') ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-x-foot small text-3">Showing <?= count($rows) ?> request<?= count($rows) === 1 ? '' : 's' ?></div>
    <?php endif; ?>
</div>
<?php
rs_admin_footer();
