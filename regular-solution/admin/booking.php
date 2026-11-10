<?php
/**
 * One booking request: full details, status and private notes.
 */
declare(strict_types=1);

require_once __DIR__ . '/_auth.php';
require_once __DIR__ . '/_layout.php';

$admin   = rs_require_admin();
$id      = (int) ($_GET['id'] ?? 0);
$booking = rs_booking_find($id);
if (!$booking) {
    rs_flash('error', 'That booking request no longer exists.');
    rs_redirect('bookings.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    rs_csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        rs_booking_delete($id);
        rs_flash('success', 'The request from ' . $booking['name'] . ' was deleted.');
        rs_redirect('bookings.php');
    }
    $status = isset(RS_BOOKING_STATUSES[$_POST['status'] ?? '']) ? $_POST['status'] : $booking['status'];
    rs_booking_update($id, $status, mb_substr(trim((string) ($_POST['notes'] ?? '')), 0, 2000));
    rs_flash('success', 'Request updated — status is now ' . RS_BOOKING_STATUSES[$status] . '.');
    rs_redirect('booking.php?id=' . $id);
}

rs_admin_header($booking['name'], 'bookings', $admin, ['Booking Requests' => 'bookings.php']);

$tiles = [
    ['bi-briefcase', 'Service', $booking['service']],
    ['bi-truck', 'Move type', $booking['move_type']],
    ['bi-house-door', 'House type', $booking['house_type']],
    ['bi-calendar-event', 'Preferred date', $booking['move_date'] ? date('D, d M Y', strtotime($booking['move_date'])) : ''],
    ['bi-clock', 'Preferred time', $booking['move_time']],
    ['bi-building-up', 'Floor (from)', $booking['floor_from']],
    ['bi-building-down', 'Floor (to)', $booking['floor_to']],
    ['bi-geo-alt', 'Address', $booking['address']],
    ['bi-envelope', 'Email', $booking['email']],
];
$tiles  = array_filter($tiles, fn ($t) => $t[2] !== '');
$waText = rawurlencode('Hello ' . $booking['name'] . ', this is Regular Solution regarding your ' . ($booking['service'] ?: 'service') . ' request.');
$icons  = ['new' => 'bi-stars', 'contacted' => 'bi-telephone-outbound', 'confirmed' => 'bi-calendar-check', 'completed' => 'bi-check2-circle', 'cancelled' => 'bi-x-circle'];
$hints  = ['new' => 'Not contacted yet', 'contacted' => 'Spoke to the customer', 'confirmed' => 'Job is booked', 'completed' => 'Job finished', 'cancelled' => 'Customer dropped out'];
?>
<a class="btn btn-sm btn-ghost mb-3 px-2" href="bookings.php"><i class="bi bi-arrow-left"></i> All requests</a>

<div class="card-x mb-4 fade-up">
    <div class="card-x-body">
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
            <div class="profile">
                <?= rs_avatar($booking['name'], 'lg') ?>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h1><?= e($booking['name']) ?></h1><?= rs_status_pill($booking['status']) ?>
                    </div>
                    <div class="text-2 small">
                        <span class="num"><i class="bi bi-telephone"></i> +91 <?= e($booking['phone']) ?></span>
                        <span class="mx-2 text-3">•</span>Request #<?= $id ?>
                        <span class="mx-2 text-3">•</span><?= e(rs_time_ago($booking['received_at'])) ?>
                    </div>
                </div>
            </div>
            <div class="page-actions">
                <a class="btn btn-primary" href="tel:+91<?= e($booking['phone']) ?>"><i class="bi bi-telephone-fill"></i> Call now</a>
                <a class="btn btn-whatsapp" href="https://wa.me/91<?= e($booking['phone']) ?>?text=<?= $waText ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                <?php if ($booking['email']): ?>
                    <a class="btn btn-light-x" href="mailto:<?= e($booking['email']) ?>"><i class="bi bi-envelope"></i> Email</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <?php if ($booking['from_area'] || $booking['to_area']): ?>
            <div class="journey mb-4 fade-up d1">
                <div class="pt"><small>Moving from</small><strong><?= e($booking['from_area'] ?: 'Not given') ?></strong></div>
                <div class="line"><i class="bi bi-truck"></i></div>
                <div class="pt text-lg-end"><small>Moving to</small><strong><?= e($booking['to_area'] ?: 'Not given') ?></strong></div>
            </div>
        <?php endif; ?>

        <div class="card-x mb-4 fade-up d2">
            <div class="card-x-head"><div><h2>Request details</h2><p>What the customer told us on the form</p></div></div>
            <div class="card-x-body">
                <?php if ($tiles): ?>
                    <div class="info-grid">
                        <?php foreach ($tiles as [$icon, $label, $value]): ?>
                            <div class="info-tile">
                                <span class="ic"><i class="bi <?= $icon ?>"></i></span>
                                <div class="min-w-0"><small><?= $label ?></small><strong><?= e($value) ?></strong></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-3 mb-0">Only a name and phone number were provided.</p>
                <?php endif; ?>

                <?php if ($booking['message']): ?>
                    <h3 class="h6 fw-bold mt-4 mb-2">Customer message</h3>
                    <div class="quote"><?= e($booking['message']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="sticky-side">
            <form class="card-x mb-4 fade-up d2" method="post">
                <?= rs_csrf_field() ?>
                <div class="card-x-head"><div><h2>Update progress</h2><p>Keep your team on the same page</p></div></div>
                <div class="card-x-body">
                    <div class="choice-grid mb-3" role="radiogroup" aria-label="Status">
                        <?php foreach (RS_BOOKING_STATUSES as $key => $label): ?>
                            <div class="choice">
                                <input type="radio" name="status" id="st-<?= $key ?>" value="<?= $key ?>" <?= $booking['status'] === $key ? 'checked' : '' ?>>
                                <label for="st-<?= $key ?>">
                                    <span class="ic"><i class="bi <?= $icons[$key] ?>"></i></span>
                                    <span><strong><?= $label ?></strong><small><?= $hints[$key] ?></small></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <label class="form-label" for="notes">Private notes <span class="opt">(only admins see this)</span></label>
                    <textarea class="form-control mb-3" id="notes" name="notes" rows="4" maxlength="2000" placeholder="e.g. Called on 12 Nov, quoted ₹8,500 — customer will confirm by Friday"><?= e($booking['notes']) ?></textarea>
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-check2"></i> Save changes</button>
                </div>
            </form>

            <div class="card-x fade-up d3">
                <div class="card-x-head"><h2>Activity</h2></div>
                <div class="card-x-body">
                    <ul class="timeline">
                        <li><strong>Request received</strong><?= e(date('d M Y, h:i A', strtotime($booking['received_at']))) ?><br><span class="text-3">via <?= e($booking['form'] ?: 'website form') ?></span></li>
                        <?php if ($booking['updated_at']): ?>
                            <li><strong>Last updated</strong><?= e(date('d M Y, h:i A', strtotime($booking['updated_at']))) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-x-foot d-flex justify-content-between align-items-center">
                    <span class="small text-3">Danger zone</span>
                    <?= rs_confirm_form('booking.php?id=' . $id, ['action' => 'delete'],
                        '<button class="btn btn-sm btn-soft-danger" type="submit"><i class="bi bi-trash3"></i> Delete request</button>',
                        'The request from ' . $booking['name'] . ' will be permanently deleted. This cannot be undone.', 'Delete this request?') ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
rs_admin_footer();
