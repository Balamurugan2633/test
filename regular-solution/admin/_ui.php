<?php
/**
 * Small UI building blocks shared by the admin pages.
 */

declare(strict_types=1);

function rs_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = mb_substr($parts[0] ?? '?', 0, 1);
    $last  = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

/** Round avatar with initials; the colour is stable per name. */
function rs_avatar(string $name, string $size = ''): string
{
    $tone = abs(crc32(mb_strtolower(trim($name)))) % 6;
    return '<span class="avatar avatar-t' . $tone . ($size ? ' avatar-' . $size : '') . '" aria-hidden="true">'
         . e(rs_initials($name ?: '?')) . '</span>';
}

function rs_status_pill(string $status): string
{
    $label = RS_BOOKING_STATUSES[$status] ?? ucfirst($status);
    return '<span class="pill pill-' . e($status) . '"><i></i>' . e($label) . '</span>';
}

function rs_post_pill(string $status): string
{
    return '<span class="pill pill-' . ($status === 'published' ? 'completed' : 'muted') . '"><i></i>'
         . ($status === 'published' ? 'Published' : 'Draft') . '</span>';
}

function rs_time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'just now';
    }
    foreach ([86400 * 7 => null, 86400 => 'day', 3600 => 'hour', 60 => 'min'] as $secs => $unit) {
        if ($diff >= $secs) {
            if ($unit === null) {
                return date('d M Y', strtotime($datetime));
            }
            $n = (int) floor($diff / $secs);
            return $n . ' ' . $unit . ($n === 1 ? '' : 's') . ' ago';
        }
    }
    return '';
}

/** Percentage change badge, e.g. +40% vs last week. */
function rs_delta(int $now, int $before): string
{
    if ($before === 0) {
        return $now > 0 ? '<span class="delta up"><i class="bi bi-arrow-up-right"></i> New</span>' : '<span class="delta flat">—</span>';
    }
    $pct = (int) round(($now - $before) / $before * 100);
    $cls = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');
    $ico = $pct > 0 ? 'bi-arrow-up-right' : ($pct < 0 ? 'bi-arrow-down-right' : 'bi-dash');
    return '<span class="delta ' . $cls . '"><i class="bi ' . $ico . '"></i> ' . abs($pct) . '%</span>';
}

/** Tiny inline line chart for a list of numbers. */
function rs_sparkline(array $values): string
{
    $values = array_values($values);
    $n      = count($values);
    if ($n < 2) {
        return '';
    }
    $max    = max(1, max($values));
    $points = [];
    foreach ($values as $i => $v) {
        $points[] = round($i / ($n - 1) * 100, 2) . ',' . round(28 - ($v / $max) * 24, 2);
    }
    $line = implode(' ', $points);
    return '<svg class="spark" viewBox="0 0 100 30" preserveAspectRatio="none" aria-hidden="true">'
         . '<polygon points="0,30 ' . $line . ' 100,30" class="spark-fill"/>'
         . '<polyline points="' . $line . '" class="spark-line" vector-effect="non-scaling-stroke"/></svg>';
}

function rs_empty(string $icon, string $title, string $text, string $action = ''): string
{
    return '<div class="empty"><div class="empty-icon"><i class="bi ' . $icon . '"></i></div>'
         . '<h3>' . e($title) . '</h3><p>' . e($text) . '</p>' . $action . '</div>';
}

/** Hidden inputs + button for a POST action protected by CSRF and a confirm dialog. */
function rs_confirm_form(string $action, array $fields, string $button, string $message, string $title = 'Are you sure?'): string
{
    $html = '<form class="d-inline" method="post" action="' . e($action) . '" data-confirm="' . e($message) . '" data-confirm-title="' . e($title) . '">'
          . rs_csrf_field();
    foreach ($fields as $k => $v) {
        $html .= '<input type="hidden" name="' . e($k) . '" value="' . e((string) $v) . '">';
    }
    return $html . $button . '</form>';
}
