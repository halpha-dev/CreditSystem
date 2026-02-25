<?php
/**
 * Credit System - Merchant Dashboard
 *
 * داشبورد کامل فروشنده - نسخه نهایی و هماهنگ با ساختار افزونه
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =============================================
   لود امن constants + UI
   ============================================= */
if (!defined('CS_TABLE_CODES')) {
    $constants_path = plugin_dir_path(dirname(__FILE__, 4)) . 'config/constants.php';
    if (file_exists($constants_path)) {
        require_once $constants_path;
    } else {
        wp_die('Credit System Error: constants.php پیدا نشد!');
    }
}

if (!defined('CS_UI_DIR')) {
    define('CS_UI_DIR', plugin_dir_path(dirname(__FILE__, 4)) . 'ui/');
}

/* Requireهای مشترک */
require_once CS_UI_DIR . 'merchant/partials/header.php';
require_once CS_UI_DIR . 'merchant/partials/sidebar.php';
require_once CS_UI_DIR . 'admin/partials/notices.php';   // نوتیس‌ها مشترک هستند

if (!current_user_can('manage_options') && !current_user_can(CS_ROLE_MERCHANT)) {
    wp_die(__('شما مجوز دسترسی به داشبورد فروشنده را ندارید.', 'credit-system'));
}

global $wpdb;
$current_merchant_id = get_current_user_id(); // فرض بر این که مرچنت با کاربر وردپرس لینک است

/* =========================
   آمار سریع مرچنت
========================= */
$total_sales_today     = $wpdb->get_var($wpdb->prepare("
    SELECT COALESCE(SUM(final_amount), 0) 
    FROM " . CS_TABLE_TRANSACTIONS . " 
    WHERE merchant_id = %d 
    AND DATE(created_at) = CURDATE() 
    AND status = '" . CS_TX_STATUS_COMPLETED . "'", $current_merchant_id));

$total_sales_all       = $wpdb->get_var($wpdb->prepare("
    SELECT COALESCE(SUM(final_amount), 0) 
    FROM " . CS_TABLE_TRANSACTIONS . " 
    WHERE merchant_id = %d 
    AND status = '" . CS_TX_STATUS_COMPLETED . "'", $current_merchant_id));

$pending_confirm_codes = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*) 
    FROM " . CS_TABLE_CODES . " 
    WHERE merchant_id = %d 
    AND status = 'used' 
    AND confirmed_at IS NULL", $current_merchant_id));

$confirmed_codes       = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(*) 
    FROM " . CS_TABLE_CODES . " 
    WHERE merchant_id = %d 
    AND status = 'used' 
    AND confirmed_at IS NOT NULL", $current_merchant_id));
?>

<div class="merchant-dashboard wrap">

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-store"></span>
        داشبورد فروشنده
    </h1>

    <!-- آمار سریع -->
    <div class="card-grid">
        <div class="card">
            <h3>فروش امروز</h3>
            <p class="big-number"><?php echo number_format($total_sales_today); ?> تومان</p>
        </div>
        <div class="card">
            <h3>کل فروش</h3>
            <p class="big-number"><?php echo number_format($total_sales_all); ?> تومان</p>
        </div>
        <div class="card warning">
            <h3>کدهای منتظر تأیید</h3>
            <p class="big-number"><?php echo $pending_confirm_codes; ?></p>
        </div>
        <div class="card success">
            <h3>کدهای تأیید شده</h3>
            <p class="big-number"><?php echo $confirmed_codes; ?></p>
        </div>
    </div>

    <!-- بخش ۱: تأیید کدهای خرید -->
    <h2>تأیید کدهای خرید</h2>
    <?php
    $pending_codes = $wpdb->get_results($wpdb->prepare("
        SELECT c.*, u.display_name AS customer_name, t.final_amount 
        FROM " . CS_TABLE_CODES . " c
        LEFT JOIN " . $wpdb->users . " u ON c.user_id = u.ID
        LEFT JOIN " . CS_TABLE_TRANSACTIONS . " t ON c.transaction_id = t.id
        WHERE c.merchant_id = %d 
        AND c.status = 'used' 
        AND c.confirmed_at IS NULL
        ORDER BY c.used_at DESC LIMIT 20", $current_merchant_id));
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>کد</th>
                <th>مشتری</th>
                <th>مبلغ</th>
                <th>زمان استفاده</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($pending_codes)): ?>
                <tr><td colspan="5">کد خرید منتظری وجود ندارد.</td></tr>
            <?php else: foreach ($pending_codes as $code): ?>
                <tr>
                    <td><strong><?php echo esc_html($code->code); ?></strong></td>
                    <td><?php echo esc_html($code->customer_name ?? '—'); ?></td>
                    <td><?php echo number_format($code->final_amount ?? 0); ?> تومان</td>
                    <td><?php echo esc_html($code->used_at); ?></td>
                    <td>
                        <button class="button button-primary confirm-code" data-code-id="<?php echo $code->id; ?>">تأیید</button>
                        <button class="button button-link-delete reject-code" data-code-id="<?php echo $code->id; ?>">رد</button>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- بخش ۲: تاریخچه فروش (کدهای تأیید شده) -->
    <h2>تاریخچه فروش</h2>
    <?php
    $confirmed_sales = $wpdb->get_results($wpdb->prepare("
        SELECT c.*, u.display_name AS customer_name, t.final_amount 
        FROM " . CS_TABLE_CODES . " c
        LEFT JOIN " . $wpdb->users . " u ON c.user_id = u.ID
        LEFT JOIN " . CS_TABLE_TRANSACTIONS . " t ON c.transaction_id = t.id
        WHERE c.merchant_id = %d 
        AND c.status = 'used' 
        AND c.confirmed_at IS NOT NULL
        ORDER BY c.confirmed_at DESC LIMIT 15", $current_merchant_id));
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>کد</th>
                <th>مشتری</th>
                <th>مبلغ</th>
                <th>زمان تأیید</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($confirmed_sales)): ?>
                <tr><td colspan="4">هنوز فروش تأیید شده‌ای ندارید.</td></tr>
            <?php else: foreach ($confirmed_sales as $sale): ?>
                <tr>
                    <td><strong><?php echo esc_html($sale->code); ?></strong></td>
                    <td><?php echo esc_html($sale->customer_name ?? '—'); ?></td>
                    <td><?php echo number_format($sale->final_amount ?? 0); ?> تومان</td>
                    <td><?php echo esc_html($sale->confirmed_at); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <!-- بخش ۳: تاریخچه واریزی‌ها (تراکنش‌های پلتفرم به مرچنت) -->
    <h2>تاریخچه واریزی‌ها</h2>
    <?php
    $settlements = $wpdb->get_results($wpdb->prepare("
        SELECT * FROM " . CS_TABLE_TRANSACTIONS . "
        WHERE merchant_id = %d 
        AND type = 'settlement' 
        ORDER BY created_at DESC LIMIT 10", $current_merchant_id));
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>شناسه واریزی</th>
                <th>مبلغ واریزی</th>
                <th>وضعیت</th>
                <th>تاریخ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($settlements)): ?>
                <tr><td colspan="4">هنوز واریزی‌ای انجام نشده است.</td></tr>
            <?php else: foreach ($settlements as $sett): ?>
                <tr>
                    <td>#<?php echo esc_html($sett->id); ?></td>
                    <td><?php echo number_format($sett->amount); ?> تومان</td>
                    <td><span class="badge success">واریز شده</span></td>
                    <td><?php echo esc_html($sett->created_at); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

</div>

<?php
// فوتر مرچنت
if (file_exists(CS_UI_DIR . 'merchant/partials/footer.php')) {
    require_once CS_UI_DIR . 'merchant/partials/footer.php';
}
?>