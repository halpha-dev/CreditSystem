<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * فقط داخل صفحات افزونه اجرا شود
 */
$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
if (strpos($current_page, 'cs-') !== 0) {
    return;
}

$current_user = wp_get_current_user();
$is_admin = current_user_can('manage_options');
$is_merchant = current_user_can('cs_manage_merchant');

/**
 * Helper for notice output
 */
function cs_admin_notice($message, $type = 'info') {

    $allowed_types = ['success', 'error', 'warning', 'info'];
    if (!in_array($type, $allowed_types)) {
        $type = 'info';
    }

    ?>
    <div class="notice notice-<?php echo esc_attr($type); ?> is-dismissible cs-admin-notice">
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php
}

/**
 * 1. KYC Pending Notice
 */
if ($is_admin || $is_merchant) {

    global $wpdb;
    $kyc_table = $wpdb->prefix . 'cs_kyc';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$kyc_table}'") === $kyc_table) {

        $pending_kyc = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$kyc_table} WHERE status = 'pending'"
        );

        if ($pending_kyc > 0) {
            cs_admin_notice(
                "تعداد {$pending_kyc} درخواست KYC در انتظار بررسی است.",
                'warning'
            );
        }
    }
}

/**
 * 2. Overdue Installments Notice
 */
if ($is_admin) {

    global $wpdb;
    $installments_table = $wpdb->prefix . 'cs_installments';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$installments_table}'") === $installments_table) {

        $overdue_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) 
             FROM {$installments_table} 
             WHERE status = 'unpaid' 
             AND due_date < NOW()"
        );

        if ($overdue_count > 0) {
            cs_admin_notice(
                "تعداد {$overdue_count} قسط سررسید گذشته دارید.",
                'error'
            );
        }
    }
}

/**
 * 3. Credit Codes Near Expiration
 */
if ($is_admin || $is_merchant) {

    global $wpdb;
    $codes_table = $wpdb->prefix . 'cs_credit_codes';

    if ($wpdb->get_var("SHOW TABLES LIKE '{$codes_table}'") === $codes_table) {

        $expiring_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) 
             FROM {$codes_table}
             WHERE status = 'active'
             AND expires_at IS NOT NULL
             AND expires_at <= DATE_ADD(NOW(), INTERVAL 3 DAY)"
        );

        if ($expiring_count > 0) {
            cs_admin_notice(
                "تعداد {$expiring_count} کد اعتباری در حال انقضا طی ۳ روز آینده است.",
                'warning'
            );
        }
    }
}

/**
 * 4. Reminder Cron Check
 */
if ($is_admin) {

    $next_cron = wp_next_scheduled('cs_reminder_cron');

    if (!$next_cron) {
        cs_admin_notice(
            "کرون یادآوری اقساط فعال نیست. بررسی Cron پیشنهاد می‌شود.",
            'error'
        );
    }
}

/**
 * 5. Global System Disabled
 */
$settings = get_option('cs_settings', []);
$system_enabled = isset($settings['enable_credit_system']) 
    ? (bool) $settings['enable_credit_system'] 
    : true;

if (!$system_enabled && $is_admin) {
    cs_admin_notice(
        "سیستم اعتباری در حال حاضر غیرفعال است.",
        'warning'
    );
}

/**
 * Extension hook
 */
do_action('cs_admin_notices');