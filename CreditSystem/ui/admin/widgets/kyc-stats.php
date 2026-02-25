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

/**
 * جدول KYC
 */
$table_kyc = $wpdb->prefix . 'cs_kyc';

/**
 * شرط دسترسی
 */
$where = "1=1";

if ($is_merchant && !$is_admin) {
    $where .= $wpdb->prepare(" AND merchant_id = %d", $current_user_id);
}

/**
 * آمار کلی
 */
$total_kyc = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$table_kyc}
    WHERE {$where}
");

$pending_kyc = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$table_kyc}
    WHERE {$where}
    AND status = 'pending'
");

$approved_kyc = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$table_kyc}
    WHERE {$where}
    AND status = 'approved'
");

$rejected_kyc = (int) $wpdb->get_var("
    SELECT COUNT(*) FROM {$table_kyc}
    WHERE {$where}
    AND status = 'rejected'
");

/**
 * درصد تایید
 */
$approval_rate = $total_kyc > 0
    ? round(($approved_kyc / $total_kyc) * 100)
    : 0;
?>

<div class="cs-widget cs-widget-kyc">
    <div class="cs-widget-header">
        <h3>آمار احراز هویت (KYC)</h3>
    </div>

    <div class="cs-widget-body">

        <div class="cs-stats-grid">

            <div class="cs-stat-card total">
                <div class="cs-stat-number">
                    <?php echo esc_html($total_kyc); ?>
                </div>
                <div class="cs-stat-label">
                    کل درخواست‌ها
                </div>
            </div>

            <div class="cs-stat-card pending">
                <div class="cs-stat-number">
                    <?php echo esc_html($pending_kyc); ?>
                </div>
                <div class="cs-stat-label">
                    در انتظار بررسی
                </div>
            </div>

            <div class="cs-stat-card approved">
                <div class="cs-stat-number">
                    <?php echo esc_html($approved_kyc); ?>
                </div>
                <div class="cs-stat-label">
                    تایید شده
                </div>
            </div>

            <div class="cs-stat-card rejected">
                <div class="cs-stat-number">
                    <?php echo esc_html($rejected_kyc); ?>
                </div>
                <div class="cs-stat-label">
                    رد شده
                </div>
            </div>

        </div>

        <div class="cs-approval-rate">
            <div class="cs-rate-label">
                نرخ تایید:
                <strong><?php echo esc_html($approval_rate); ?>%</strong>
            </div>

            <div class="cs-rate-bar">
                <div class="cs-rate-fill"
                     style="width: <?php echo esc_attr($approval_rate); ?>%;">
                </div>
            </div>
        </div>

        <?php if ($is_admin) : ?>
            <div class="cs-widget-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cs-kyc-list')); ?>"
                   class="button button-primary">
                    مشاهده لیست KYC
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>