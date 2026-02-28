<?php
/**
 * Credit System - Merchant Settlements (تسویه‌حساب‌ها)
 *
 * صفحه کامل مدیریت واریزی‌ها برای فروشنده
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

/* Require های مشترک مرچنت */
require_once CS_UI_DIR . 'merchant/partials/header.php';
require_once CS_UI_DIR . 'merchant/partials/sidebar.php';
require_once CS_UI_DIR . 'merchant/partials/notices.php';   // اگر notices merchant داری، иначе از admin استفاده کن

if (!current_user_can(CS_ROLE_MERCHANT) && !current_user_can('manage_options')) {
    wp_die(__('شما مجوز دسترسی به صفحه تسویه‌حساب‌ها را ندارید.', 'credit-system'));
}

global $wpdb;
$current_merchant_id = get_current_user_id();

/* =========================
   فیلترها
========================= */
$status_filter   = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
$date_from       = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$date_to         = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$search          = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

/* =========================
   آمار خلاصه
========================= */
$balance_available = $wpdb->get_var($wpdb->prepare("
    SELECT COALESCE(SUM(final_amount), 0) 
    FROM " . CS_TABLE_TRANSACTIONS . " 
    WHERE merchant_id = %d 
    AND status = '" . CS_TX_STATUS_COMPLETED . "' 
    AND settlement_status = 'pending'", $current_merchant_id));

$total_settled     = $wpdb->get_var($wpdb->prepare("
    SELECT COALESCE(SUM(amount), 0) 
    FROM " . CS_TABLE_TRANSACTIONS . " 
    WHERE merchant_id = %d 
    AND type = 'settlement' 
    AND status = 'completed'", $current_merchant_id));

/* =========================
   کوئری اصلی تسویه‌حساب‌ها
========================= */
$query = "
SELECT * FROM " . CS_TABLE_TRANSACTIONS . "
WHERE merchant_id = %d 
AND type = 'settlement'
";

$params = [$current_merchant_id];

if ($status_filter) {
    $query .= " AND status = %s";
    $params[] = $status_filter;
}
if ($date_from) {
    $query .= " AND DATE(created_at) >= %s";
    $params[] = $date_from;
}
if ($date_to) {
    $query .= " AND DATE(created_at) <= %s";
    $params[] = $date_to;
}
if ($search) {
    $query .= " AND (id LIKE %s OR notes LIKE %s)";
    $like = '%' . $wpdb->esc_like($search) . '%';
    $params[] = $like;
    $params[] = $like;
}

$query .= " ORDER BY created_at DESC LIMIT 50";

$settlements = $wpdb->get_results($wpdb->prepare($query, $params));
?>

<div class="merchant-settlements wrap">

    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-money"></span>
        تسویه‌حساب‌ها
    </h1>

    <!-- آمار سریع -->
    <div class="card-grid">
        <div class="card">
            <h3>موجودی قابل تسویه</h3>
            <p class="big-number"><?php echo number_format($balance_available); ?> تومان</p>
        </div>
        <div class="card success">
            <h3>کل واریزی انجام شده</h3>
            <p class="big-number"><?php echo number_format($total_settled); ?> تومان</p>
        </div>
    </div>

    <!-- فیلترها -->
    <div class="tablenav top">
        <form method="get" class="alignleft">
            <input type="hidden" name="page" value="credit-system-merchant-settlements">
            <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="جستجو بر اساس شناسه یا توضیحات" class="regular-text">
            
            <select name="status">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>در انتظار</option>
                <option value="completed" <?php selected($status_filter, 'completed'); ?>>واریز شده</option>
                <option value="failed" <?php selected($status_filter, 'failed'); ?>>ناموفق</option>
            </select>

            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
            <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">

            <button type="submit" class="button">فیلتر</button>
            <a href="<?php echo esc_url(admin_url('admin.php?page=credit-system-merchant-settlements')); ?>" class="button">پاک کردن فیلتر</a>
        </form>

        <!-- دکمه درخواست تسویه جدید -->
        <?php if ($balance_available >= CS_MIN_CREDIT_AMOUNT): ?>
            <a href="#" class="button button-primary button-hero alignright request-settlement" data-amount="<?php echo $balance_available; ?>">
                درخواست تسویه جدید (<?php echo number_format($balance_available); ?> تومان)
            </a>
        <?php endif; ?>
    </div>

    <!-- جدول تسویه‌حساب‌ها -->
    <table class="wp-list-table widefat fixed striped admin-table">
        <thead>
            <tr>
                <th>شناسه</th>
                <th>مبلغ واریزی</th>
                <th>وضعیت</th>
                <th>شماره شبا / کارت</th>
                <th>درخواست شده در</th>
                <th>واریز شده در</th>
                <th>توضیحات</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($settlements)): ?>
                <tr><td colspan="7">هیچ تسویه‌حسابی یافت نشد.</td></tr>
            <?php else: foreach ($settlements as $sett): ?>
                <tr>
                    <td>#<?php echo esc_html($sett->id); ?></td>
                    <td><strong><?php echo number_format($sett->amount); ?> تومان</strong></td>
                    <td>
                        <span class="badge 
                            <?php echo $sett->status === 'completed' ? 'success' : ($sett->status === 'pending' ? 'warning' : 'danger'); ?>">
                            <?php echo esc_html(ucfirst($sett->status)); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($sett->bank_account ?? '—'); ?></td>
                    <td><?php echo esc_html($sett->created_at); ?></td>
                    <td><?php echo $sett->processed_at ? esc_html($sett->processed_at) : '—'; ?></td>
                    <td><?php echo esc_html($sett->notes ?? '—'); ?></td>
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