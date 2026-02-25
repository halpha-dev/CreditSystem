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

$table_credits = $wpdb->prefix . 'cs_credits';
$table_installments = $wpdb->prefix . 'cs_installments';

/**
 * شرط دسترسی
 */
$where = "1=1";

if ($is_merchant && !$is_admin) {
    $where .= $wpdb->prepare(" AND merchant_id = %d", $current_user_id);
}

/**
 * آمار کردیت
 */
$total_issued = (float) $wpdb->get_var("
    SELECT COALESCE(SUM(total_amount),0)
    FROM {$table_credits}
    WHERE {$where}
");

$total_used = (float) $wpdb->get_var("
    SELECT COALESCE(SUM(used_amount),0)
    FROM {$table_credits}
    WHERE {$where}
");

$total_remaining = (float) $wpdb->get_var("
    SELECT COALESCE(SUM(remaining_amount),0)
    FROM {$table_credits}
    WHERE {$where}
");

$active_credits = (int) $wpdb->get_var("
    SELECT COUNT(*)
    FROM {$table_credits}
    WHERE {$where}
    AND status = 'active'
");

/**
 * آمار اقساط و جریمه
 */
$total_pending_installments = (float) $wpdb->get_var("
    SELECT COALESCE(SUM(i.amount),0)
    FROM {$table_installments} i
    INNER JOIN {$table_credits} c ON i.credit_id = c.id
    WHERE {$where}
    AND i.status = 'pending'
");

$total_late_penalties = (float) $wpdb->get_var("
    SELECT COALESCE(SUM(i.penalty_amount),0)
    FROM {$table_installments} i
    INNER JOIN {$table_credits} c ON i.credit_id = c.id
    WHERE {$where}
    AND i.status = 'late'
");

?>

<div class="cs-widget cs-widget-credit-summary">

    <div class="cs-widget-header">
        <h3>خلاصه وضعیت کردیت</h3>
    </div>

    <div class="cs-widget-body">

        <div class="cs-stats-grid">

            <div class="cs-stat-card primary">
                <div class="cs-stat-number">
                    <?php echo esc_html(number_format($total_issued)); ?>
                </div>
                <div class="cs-stat-label">
                    مجموع اعتبار صادر شده
                </div>
            </div>

            <div class="cs-stat-card info">
                <div class="cs-stat-number">
                    <?php echo esc_html(number_format($total_used)); ?>
                </div>
                <div class="cs-stat-label">
                    مصرف‌شده
                </div>
            </div>

            <div class="cs-stat-card success">
                <div class="cs-stat-number">
                    <?php echo esc_html(number_format($total_remaining)); ?>
                </div>
                <div class="cs-stat-label">
                    مانده اعتبار
                </div>
            </div>

            <div class="cs-stat-card warning">
                <div class="cs-stat-number">
                    <?php echo esc_html($active_credits); ?>
                </div>
                <div class="cs-stat-label">
                    کردیت فعال
                </div>
            </div>

        </div>

        <hr>

        <div class="cs-installment-summary">

            <div class="cs-installment-item">
                <span class="label">اقساط در انتظار پرداخت:</span>
                <strong><?php echo esc_html(number_format($total_pending_installments)); ?></strong>
            </div>

            <div class="cs-installment-item">
                <span class="label">جمع جریمه‌های دیرکرد:</span>
                <strong class="cs-danger">
                    <?php echo esc_html(number_format($total_late_penalties)); ?>
                </strong>
            </div>

        </div>

        <?php if ($is_admin) : ?>
            <div class="cs-widget-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cs-credits')); ?>"
                   class="button button-primary">
                    مدیریت کردیت‌ها
                </a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=cs-installments')); ?>"
                   class="button">
                    مشاهده اقساط
                </a>
            </div>
        <?php endif; ?>

    </div>

</div>