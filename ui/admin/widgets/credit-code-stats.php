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
$is_admin        = current_user_can('manage_options');
$is_merchant     = current_user_can('cs_merchant');

$table_codes = $wpdb->prefix . 'cs_credit_codes';

$where = "1=1";

if ($is_merchant && !$is_admin) {
    $where .= $wpdb->prepare(" AND merchant_id = %d", $current_user_id);
}

/**
 * آمار کلی
 */
$total_codes = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$table_codes}
    WHERE {$where}
");

$active_codes = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$table_codes}
    WHERE {$where}
    AND status = 'active'
");

$inactive_codes = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$table_codes}
    WHERE {$where}
    AND status = 'inactive'
");

$expired_codes = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$table_codes}
    WHERE {$where}
    AND expires_at IS NOT NULL
    AND expires_at < NOW()
");

/**
 * مجموع دفعات استفاده
 */
$total_used_count = (int) $wpdb->get_var("
    SELECT SUM(used_count) FROM {$table_codes}
    WHERE {$where}
");

/**
 * مجموع مبلغ استفاده شده
 */
$total_used_amount = (float) $wpdb->get_var("
    SELECT SUM(amount * used_count) FROM {$table_codes}
    WHERE {$where}
");

?>

<div class="cs-widget cs-widget-credit-stats">

    <div class="cs-widget-header">
        <h3>آمار کردیت کدها</h3>
    </div>

    <div class="cs-widget-body">

        <div class="cs-stats-grid">

            <div class="cs-stat-card">
                <span class="cs-stat-number">
                    <?php echo esc_html($total_codes); ?>
                </span>
                <span class="cs-stat-label">کل کدها</span>
            </div>

            <div class="cs-stat-card cs-success">
                <span class="cs-stat-number">
                    <?php echo esc_html($active_codes); ?>
                </span>
                <span class="cs-stat-label">فعال</span>
            </div>

            <div class="cs-stat-card cs-muted">
                <span class="cs-stat-number">
                    <?php echo esc_html($inactive_codes); ?>
                </span>
                <span class="cs-stat-label">غیرفعال</span>
            </div>

            <div class="cs-stat-card cs-danger">
                <span class="cs-stat-number">
                    <?php echo esc_html($expired_codes); ?>
                </span>
                <span class="cs-stat-label">منقضی شده</span>
            </div>

        </div>

        <hr>

        <div class="cs-credit-usage-summary">

            <div>
                <strong>مجموع دفعات استفاده:</strong>
                <?php echo esc_html($total_used_count ? $total_used_count : 0); ?>
            </div>

            <div>
                <strong>مجموع مبلغ استفاده شده:</strong>
                <?php echo esc_html(number_format($total_used_amount ? $total_used_amount : 0)); ?>
            </div>

        </div>

        <?php if ($is_admin) : ?>
            <div class="cs-widget-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cs-credit-codes')); ?>"
                   class="button button-primary">
                    مدیریت کدها
                </a>
            </div>
        <?php endif; ?>

    </div>

</div>