<?php
/**
 * Credit System - Merchant Customers
 *
 * صفحه مشتریان فروشنده - فقط کسانی که از این مرچنت خرید کرده‌اند
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =============================================
   لود امن constants + UI
   ============================================= */
if (!defined('CS_TABLE_TRANSACTIONS')) {
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

/* Requireهای مشترک مرچنت */
require_once CS_UI_DIR . 'merchant/partials/header.php';
require_once CS_UI_DIR . 'merchant/partials/sidebar.php';
require_once CS_UI_DIR . 'merchant/partials/notices.php';

if (!current_user_can(CS_ROLE_MERCHANT) && !current_user_can('manage_options')) {
    wp_die(__('شما مجوز دسترسی به لیست مشتریان را ندارید.', 'credit-system'));
}

global $wpdb;
$current_merchant_id = get_current_user_id();

/* =========================
   فیلترها و جستجو
========================= */
$search     = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$kyc_filter = isset($_GET['kyc']) ? sanitize_key($_GET['kyc']) : '';

/* =========================
   آمار خلاصه مشتریان
========================= */
$total_customers = $wpdb->get_var($wpdb->prepare("
    SELECT COUNT(DISTINCT user_id) 
    FROM " . CS_TABLE_TRANSACTIONS . " 
    WHERE merchant_id = %d", $current_merchant_id));

$total_sales_to_customers = $wpdb->get_var($wpdb->prepare("
    SELECT COALESCE(SUM(final_amount), 0) 
    FROM " . CS_TABLE_TRANSACTIONS . " 
    WHERE merchant_id = %d 
    AND status = '" . CS_TX_STATUS_COMPLETED . "'", $current_merchant_id));

/* =========================
   کوئری اصلی مشتریان (فقط کسانی که خرید کرده‌اند)
========================= */
$query = "
SELECT 
    u.ID AS user_id,
    u.display_name,
    u.user_email,
    COUNT(t.id) AS total_purchases,
    COALESCE(SUM(t.final_amount), 0) AS total_spent,
    MAX(t.created_at) AS last_purchase,
    (
        SELECT status 
        FROM " . CS_TABLE_KYC_REQUESTS . " 
        WHERE user_id = u.ID 
        ORDER BY id DESC LIMIT 1
    ) AS kyc_status
FROM " . $wpdb->users . " u
INNER JOIN " . CS_TABLE_TRANSACTIONS . " t ON t.user_id = u.ID
WHERE t.merchant_id = %d 
  AND t.status = '" . CS_TX_STATUS_COMPLETED . "'
";

$params = [$current_merchant_id];

if ($search) {
    $query .= " AND (u.display_name LIKE %s OR u.user_email LIKE %s)";
    $like = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $like;
    $params[] = $like;
}

if ($kyc_filter) {
    $query .= " AND (
        SELECT status 
        FROM " . CS_TABLE_KYC_REQUESTS . " 
        WHERE user_id = u.ID 
        ORDER BY id DESC LIMIT 1
    ) = %s";
    $params[] = $kyc_filter;
}

$query .= "
GROUP BY u.ID
ORDER BY total_spent DESC, last_purchase DESC
LIMIT 100
";

$customers = $wpdb->get_results($wpdb->prepare($query, $params));
?>

<div class="merchant-customers wrap">

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        مشتریان من
    </h1>

    <!-- آمار خلاصه -->
    <div class="card-grid">
        <div class="card">
            <h3>تعداد مشتریان</h3>
            <p class="big-number"><?php echo number_format($total_customers); ?></p>
        </div>
        <div class="card success">
            <h3>مجموع فروش به مشتریان</h3>
            <p class="big-number"><?php echo number_format($total_sales_to_customers); ?> تومان</p>
        </div>
    </div>

    <!-- فیلترها -->
    <div class="tablenav top">
        <form method="get" style="display:inline-block;">
            <input type="hidden" name="page" value="credit-system-merchant-customers">
            
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" 
                   placeholder="جستجو نام یا ایمیل" class="regular-text">

            <select name="kyc">
                <option value="">همه وضعیت‌های KYC</option>
                <option value="<?php echo CS_KYC_STATUS_APPROVED; ?>" <?php selected($kyc_filter, CS_KYC_STATUS_APPROVED); ?>>تأیید شده</option>
                <option value="<?php echo CS_KYC_STATUS_PENDING; ?>" <?php selected($kyc_filter, CS_KYC_STATUS_PENDING); ?>>در انتظار</option>
                <option value="<?php echo CS_KYC_STATUS_REJECTED; ?>" <?php selected($kyc_filter, CS_KYC_STATUS_REJECTED); ?>>رد شده</option>
            </select>

            <button type="submit" class="button">اعمال فیلتر</button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=credit-system-merchant-customers')); ?>" class="button">پاک کردن فیلتر</a>
        </form>
    </div>

    <!-- جدول مشتریان -->
    <table class="wp-list-table widefat fixed striped admin-table">
        <thead>
            <tr>
                <th>نام مشتری</th>
                <th>ایمیل</th>
                <th>تعداد خرید</th>
                <th>مجموع خرید</th>
                <th>آخرین خرید</th>
                <th>وضعیت KYC</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="7">هیچ مشتری‌ای یافت نشد.</td></tr>
            <?php else: foreach ($customers as $customer): ?>
                <tr>
                    <td><strong><?php echo esc_html($customer->display_name); ?></strong></td>
                    <td><?php echo esc_html($customer->user_email); ?></td>
                    <td><?php echo (int)$customer->total_purchases; ?> بار</td>
                    <td><strong><?php echo number_format($customer->total_spent); ?> تومان</strong></td>
                    <td><?php echo esc_html($customer->last_purchase); ?></td>
                    <td>
                        <?php
                        $kyc = $customer->kyc_status;
                        $class = $kyc === CS_KYC_STATUS_APPROVED ? 'success' : ($kyc === CS_KYC_STATUS_PENDING ? 'warning' : 'danger');
                        ?>
                        <span class="badge <?php echo $class; ?>">
                            <?php echo $kyc ? esc_html(ucfirst($kyc)) : '—'; ?>
                        </span>
                    </td>
                    <td>
                        <a href="#" class="button button-small view-customer-details" 
                           data-user-id="<?php echo (int)$customer->user_id; ?>">
                            جزئیات
                        </a>
                    </td>
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