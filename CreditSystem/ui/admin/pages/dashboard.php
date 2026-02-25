<?php
/**
 * Credit System - Admin Dashboard
 *
 * نسخه نهایی و فیکس‌شده - ۲۴ فوریه ۲۰۲۶
 */

if (!defined('ABSPATH')) {
    exit;
}

// Safeguard ثابت‌ها
if (!defined('CS_UI_DIR')) {
    define('CS_UI_DIR', plugin_dir_path(dirname(__FILE__, 3)) . 'ui/');
}

// Requireهای مشترک (با مسیر امن)
require_once CS_UI_DIR . 'admin/partials/header.php';
require_once CS_UI_DIR . 'admin/partials/sidebar.php';
require_once CS_UI_DIR . 'admin/partials/notices.php';

if (!is_admin() || !current_user_can('manage_options')) {
    wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'credit-system'));
}

global $wpdb;

/* =========================
   آمار کلی - با استفاده از ثابت‌های جدول
========================= */

// اعتبار کل کاربران
$total_credit = $wpdb->get_var("
    SELECT SUM(available_credit) 
    FROM " . CS_TABLE_CREDITS . "
");

// تعداد حساب‌های اعتباری فعال
$total_active_accounts = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM " . CS_TABLE_CREDITS . "
    WHERE status = 'active'
");

// کدهای اعتبار
$total_codes = $wpdb->get_var("SELECT COUNT(*) FROM " . CS_TABLE_CODES);
$active_codes = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM " . CS_TABLE_CODES . "
    WHERE status = '" . CS_CODE_STATUS_UNUSED . "' 
    AND expires_at > NOW()
");
$expired_codes = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM " . CS_TABLE_CODES . "
    WHERE expires_at < NOW()
");

// تراکنش‌ها
$total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM " . CS_TABLE_TRANSACTIONS);
$completed_transactions = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM " . CS_TABLE_TRANSACTIONS . "
    WHERE status = '" . CS_TX_STATUS_COMPLETED . "'
");
$total_sales = $wpdb->get_var("
    SELECT COALESCE(SUM(amount), 0) 
    FROM " . CS_TABLE_TRANSACTIONS . "
    WHERE status = '" . CS_TX_STATUS_COMPLETED . "'
");

// مرچنت‌ها
$total_merchants = $wpdb->get_var("SELECT COUNT(*) FROM " . CS_TABLE_MERCHANTS);

// KYC
$kyc_pending   = $wpdb->get_var("SELECT COUNT(*) FROM " . CS_TABLE_KYC_REQUESTS . " WHERE status = '" . CS_KYC_STATUS_PENDING . "'");
$kyc_approved  = $wpdb->get_var("SELECT COUNT(*) FROM " . CS_TABLE_KYC_REQUESTS . " WHERE status = '" . CS_KYC_STATUS_APPROVED . "'");
$kyc_rejected  = $wpdb->get_var("SELECT COUNT(*) FROM " . CS_TABLE_KYC_REQUESTS . " WHERE status = '" . CS_KYC_STATUS_REJECTED . "'");

// اقساط
$total_installments     = $wpdb->get_var("SELECT COUNT(*) FROM " . CS_TABLE_INSTALLMENTS);
$overdue_installments   = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM " . CS_TABLE_INSTALLMENTS . "
    WHERE due_date < NOW() AND status != 'paid'
");
$active_plans = $wpdb->get_var("
    SELECT COUNT(*) 
    FROM " . CS_TABLE_INSTALL_PLANS . "
    WHERE status = 'active'
");
?>

<div class="admin-dashboard wrap">

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-money-alt"></span>
        داشبورد سیستم اعتبار
    </h1>

    <!-- آمار کلی -->
    <div class="card-grid">
        <div class="card">
            <h3>اعتبار کل کاربران</h3>
            <p class="big-number"><?php echo number_format($total_credit ?? 0); ?> <small>تومان</small></p>
        </div>
        <div class="card">
            <h3>حساب‌های فعال</h3>
            <p class="big-number"><?php echo number_format($total_active_accounts ?? 0); ?></p>
        </div>
        <div class="card">
            <h3>مرچنت‌ها</h3>
            <p class="big-number"><?php echo number_format($total_merchants ?? 0); ?></p>
        </div>
    </div>

    <!-- KYC -->
    <h2>KYC درخواست‌ها</h2>
    <div class="card-grid">
        <div class="card warning">
            <h3>در انتظار بررسی</h3>
            <p><?php echo number_format($kyc_pending); ?></p>
        </div>
        <div class="card success">
            <h3>تایید شده</h3>
            <p><?php echo number_format($kyc_approved); ?></p>
        </div>
        <div class="card danger">
            <h3>رد شده</h3>
            <p><?php echo number_format($kyc_rejected); ?></p>
        </div>
    </div>

    <!-- کدهای اعتبار -->
    <h2>کدهای اعتبار</h2>
    <div class="card-grid">
        <div class="card">
            <h3>کل کدها</h3>
            <p><?php echo number_format($total_codes); ?></p>
        </div>
        <div class="card success">
            <h3>فعال</h3>
            <p><?php echo number_format($active_codes); ?></p>
        </div>
        <div class="card danger">
            <h3>منقضی شده</h3>
            <p><?php echo number_format($expired_codes); ?></p>
        </div>
    </div>

    <!-- تراکنش و گردش مالی -->
    <h2>گردش مالی</h2>
    <div class="card-grid">
        <div class="card highlight">
            <h3>تراکنش‌های تکمیل‌شده</h3>
            <p><?php echo number_format($completed_transactions); ?></p>
        </div>
        <div class="card highlight">
            <h3>مجموع فروش</h3>
            <p><?php echo number_format($total_sales); ?> تومان</p>
        </div>
    </div>

    <!-- اقساط -->
    <h2>اقساط</h2>
    <div class="card-grid">
        <div class="card">
            <h3>کل اقساط</h3>
            <p><?php echo number_format($total_installments); ?></p>
        </div>
        <div class="card danger">
            <h3>اقساط معوق</h3>
            <p><?php echo number_format($overdue_installments); ?></p>
        </div>
        <div class="card">
            <h3>پلن‌های فعال</h3>
            <p><?php echo number_format($active_plans); ?></p>
        </div>
    </div>

</div>

<?php
// فوتر مشترک
if (file_exists(CS_UI_DIR . 'admin/partials/footer.php')) {
    require_once CS_UI_DIR . 'admin/partials/footer.php';
}
?>