<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

if (!current_user_can('read')) {
    return;
}

/**
 * فقط داخل صفحات افزونه
 */
$current_admin_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
if (strpos($current_admin_page, 'cs-') !== 0) {
    return;
}

$current_user_id = get_current_user_id();
$is_admin = current_user_can('manage_options');
$is_merchant = current_user_can('cs_merchant');

$table_installments = $wpdb->prefix . 'cs_installments';
$table_credits = $wpdb->prefix . 'cs_credits';

/**
 * شرط دسترسی
 */
$where = "1=1";

if ($is_merchant && !$is_admin) {
    $where .= $wpdb->prepare(" AND c.merchant_id = %d", $current_user_id);
}

/**
 * امروز
 */
$today = current_time('Y-m-d');

/**
 * دریافت اقساط سررسید گذشته
 */
$query = $wpdb->prepare("
    SELECT 
        i.id,
        i.amount,
        i.due_date,
        i.penalty_amount,
        c.user_id
    FROM {$table_installments} i
    INNER JOIN {$table_credits} c ON i.credit_id = c.id
    WHERE {$where}
    AND i.status IN ('pending','late')
    AND i.due_date < %s
    ORDER BY i.due_date ASC
    LIMIT 10
", $today);

$overdues = $wpdb->get_results($query);

/**
 * مجموع بدهی معوق
 */
$total_overdue_amount = 0;
$total_penalties = 0;

foreach ($overdues as $row) {
    $total_overdue_amount += (float) $row->amount;
    $total_penalties += (float) $row->penalty_amount;
}

?>

<div class="cs-widget cs-widget-overdue">

    <div class="cs-widget-header">
        <h3>اقساط سررسید گذشته</h3>
    </div>

    <div class="cs-widget-body">

        <?php if (!empty($overdues)) : ?>

            <div class="cs-overdue-summary">
                <div>
                    <strong>جمع بدهی معوق:</strong>
                    <?php echo esc_html(number_format($total_overdue_amount)); ?>
                </div>

                <div class="cs-danger">
                    <strong>جمع جریمه:</strong>
                    <?php echo esc_html(number_format($total_penalties)); ?>
                </div>
            </div>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>کاربر</th>
                        <th>مبلغ</th>
                        <th>سررسید</th>
                        <th>جریمه</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($overdues as $row) : 
                        $user = get_userdata($row->user_id);
                        ?>
                        <tr>
                            <td>
                                <?php echo esc_html($user ? $user->display_name : '—'); ?>
                            </td>
                            <td>
                                <?php echo esc_html(number_format($row->amount)); ?>
                            </td>
                            <td>
                                <span class="cs-danger">
                                    <?php echo esc_html(date_i18n('Y-m-d', strtotime($row->due_date))); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo esc_html(number_format($row->penalty_amount)); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($is_admin) : ?>
                <div class="cs-widget-footer">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=cs-installments&filter=overdue')); ?>"
                       class="button button-primary">
                        مشاهده همه اقساط معوق
                    </a>
                </div>
            <?php endif; ?>

        <?php else : ?>

            <div class="cs-empty-state">
                هیچ قسط معوقی وجود ندارد.
                عجیب است. مردم بالاخره یک جایی دیر می‌کنند.
            </div>

        <?php endif; ?>

    </div>

</div>