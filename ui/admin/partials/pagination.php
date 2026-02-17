<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Expected variables:
 * $total_items (int)
 * $items_per_page (int)
 * $current_page (int)
 */

if (!isset($total_items, $items_per_page, $current_page)) {
    return;
}

$total_items = (int) $total_items;
$items_per_page = max(1, (int) $items_per_page);
$current_page = max(1, (int) $current_page);

$total_pages = (int) ceil($total_items / $items_per_page);

if ($total_pages <= 1) {
    return;
}

/**
 * فقط داخل صفحات افزونه
 */
$current_admin_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
if (strpos($current_admin_page, 'cs-') !== 0) {
    return;
}

/**
 * ساخت base url با حفظ پارامترهای فیلتر
 */
$query_args = $_GET;
unset($query_args['paged']);

$base_url = add_query_arg(
    array_map('sanitize_text_field', $query_args),
    admin_url('admin.php')
);

/**
 * Range calculation
 */
$range = 2; // تعداد صفحات اطراف صفحه فعلی
$start = max(1, $current_page - $range);
$end = min($total_pages, $current_page + $range);

?>

<div class="cs-pagination-wrapper">
    <div class="cs-pagination-info">
        <?php
        $start_item = (($current_page - 1) * $items_per_page) + 1;
        $end_item = min($total_items, $current_page * $items_per_page);
        ?>
        <span>
            نمایش <?php echo esc_html($start_item); ?>
            تا <?php echo esc_html($end_item); ?>
            از <?php echo esc_html($total_items); ?> مورد
        </span>
    </div>

    <ul class="cs-pagination">

        <?php if ($current_page > 1) : ?>
            <li class="cs-page-item">
                <a class="cs-page-link"
                   href="<?php echo esc_url(add_query_arg('paged', 1, $base_url)); ?>">
                    « اول
                </a>
            </li>

            <li class="cs-page-item">
                <a class="cs-page-link"
                   href="<?php echo esc_url(add_query_arg('paged', $current_page - 1, $base_url)); ?>">
                    ‹ قبلی
                </a>
            </li>
        <?php endif; ?>

        <?php if ($start > 1) : ?>
            <li class="cs-page-item disabled">
                <span>…</span>
            </li>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++) : ?>
            <li class="cs-page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                <?php if ($i === $current_page) : ?>
                    <span class="cs-page-link current">
                        <?php echo esc_html($i); ?>
                    </span>
                <?php else : ?>
                    <a class="cs-page-link"
                       href="<?php echo esc_url(add_query_arg('paged', $i, $base_url)); ?>">
                        <?php echo esc_html($i); ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endfor; ?>

        <?php if ($end < $total_pages) : ?>
            <li class="cs-page-item disabled">
                <span>…</span>
            </li>
        <?php endif; ?>

        <?php if ($current_page < $total_pages) : ?>
            <li class="cs-page-item">
                <a class="cs-page-link"
                   href="<?php echo esc_url(add_query_arg('paged', $current_page + 1, $base_url)); ?>">
                    بعدی ›
                </a>
            </li>

            <li class="cs-page-item">
                <a class="cs-page-link"
                   href="<?php echo esc_url(add_query_arg('paged', $total_pages, $base_url)); ?>">
                    آخر »
                </a>
            </li>
        <?php endif; ?>

    </ul>
</div>