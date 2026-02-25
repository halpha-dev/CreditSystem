<?php
/**
 * Credit System - Transactions Page
 *
 * نسخه نهایی و کاملاً فیکس‌شده - ۲۵ فوریه ۲۰۲۶
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =============================================
   لود امن constants + $wpdb
   ============================================= */
if (!defined('CS_TABLE_TRANSACTIONS')) {
    $constants_path = plugin_dir_path(dirname(__FILE__, 3)) . 'config/constants.php';
    if (file_exists($constants_path)) {
        require_once $constants_path;
    } else {
        wp_die('Credit System Error: constants.php پیدا نشد!');
    }
}

if (!defined('CS_UI_DIR')) {
    define('CS_UI_DIR', plugin_dir_path(dirname(__FILE__, 3)) . 'ui/');
}

/* Requireهای مشترک (فقط یک بار) */
require_once CS_UI_DIR . 'admin/partials/header.php';
require_once CS_UI_DIR . 'admin/partials/sidebar.php';
require_once CS_UI_DIR . 'admin/partials/notices.php';

if (!current_user_can('manage_options')) {
    wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'credit-system'));
}

global $wpdb;

/* =========================
   فیلتر فروشنده
========================= */
$merchant_filter = isset($_GET['merchant_id']) ? (int)$_GET['merchant_id'] : 0;

/* =========================
   دریافت تراکنش‌ها (با $wpdb + Prepared)
========================= */

$query = "
SELECT t.*,
       u.display_name AS user_name,
       u.user_email AS user_email,
       m.display_name AS merchant_name,
       p.name AS plan_name,
       p.installment_count,
       c.code AS credit_code,
       c.type AS credit_type,
       c.value AS credit_value,
       (SELECT status FROM " . CS_TABLE_KYC_REQUESTS . " 
            WHERE user_id = t.user_id 
            ORDER BY id DESC LIMIT 1) AS user_kyc_status,
       (SELECT COUNT(*) FROM " . CS_TABLE_INSTALLMENTS . " 
            WHERE transaction_id = t.id) AS total_installments,
       (SELECT COUNT(*) FROM " . CS_TABLE_INSTALLMENTS . " 
            WHERE transaction_id = t.id AND status='overdue') AS overdue_installments,
       (SELECT COUNT(*) FROM " . CS_TABLE_PENALTIES . " 
            WHERE installment_id IN 
                (SELECT id FROM " . CS_TABLE_INSTALLMENTS . " WHERE transaction_id = t.id)
            AND status='unpaid') AS unpaid_penalties
FROM " . CS_TABLE_TRANSACTIONS . " t
LEFT JOIN " . $wpdb->users . " u ON t.user_id = u.ID
LEFT JOIN " . $wpdb->users . " m ON t.merchant_id = m.ID
LEFT JOIN " . CS_TABLE_INSTALL_PLANS . " p ON t.plan_id = p.id
LEFT JOIN " . CS_TABLE_CODES . " c ON t.credit_code_id = c.id
";

if ($merchant_filter > 0) {
    $query .= $wpdb->prepare(" WHERE t.merchant_id = %d", $merchant_filter);
}

$query .= " ORDER BY t.created_at DESC LIMIT 100";

$transactions = $wpdb->get_results($query, ARRAY_A);
?>

<div class="admin-transactions wrap">

    <h1 class="wp-heading-inline">مدیریت تراکنش‌ها</h1>

    <table class="wp-list-table widefat fixed striped admin-table">
        <thead>
        <tr>
            <th>شناسه</th>
            <th>کاربر</th>
            <th>KYC</th>
            <th>فروشنده</th>
            <th>پلن</th>
            <th>مبلغ کل</th>
            <th>مبلغ نهایی</th>
            <th>کردیت کد</th>
            <th>اقساط</th>
            <th>معوق</th>
            <th>جریمه</th>
            <th>وضعیت</th>
            <th>تاریخ</th>
        </tr>
        </thead>
        <tbody>

        <?php if (empty($transactions)): ?>
            <tr><td colspan="13">هیچ تراکنشی یافت نشد.</td></tr>
        <?php endif; ?>

        <?php foreach ($transactions as $tx): ?>
            <tr>
                <td>#<?php echo esc_html($tx['id']); ?></td>

                <td>
                    <?php echo esc_html($tx['user_name'] ?? '—'); ?><br>
                    <small><?php echo esc_html($tx['user_email'] ?? '—'); ?></small>
                </td>

                <td>
                    <span class="badge 
                        <?php echo $tx['user_kyc_status'] === 'approved' ? 'success' : ($tx['user_kyc_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                        <?php echo esc_html($tx['user_kyc_status'] ?? '—'); ?>
                    </span>
                </td>

                <td><?php echo esc_html($tx['merchant_name'] ?? '—'); ?></td>

                <td>
                    <?php echo esc_html($tx['plan_name'] ?? '—'); ?><br>
                    <small><?php echo (int)($tx['installment_count'] ?? 0); ?> قسط</small>
                </td>

                <td><?php echo number_format($tx['total_amount'] ?? 0); ?></td>
                <td><?php echo number_format($tx['final_amount'] ?? 0); ?></td>

                <td>
                    <?php if (!empty($tx['credit_code'])): ?>
                        <span class="badge success"><?php echo esc_html($tx['credit_code']); ?></span><br>
                        <small><?php echo esc_html($tx['credit_type'] ?? '') . ' - ' . number_format($tx['credit_value'] ?? 0); ?></small>
                    <?php else: ?>
                        <span class="badge secondary">بدون کد</span>
                    <?php endif; ?>
                </td>

                <td><?php echo (int)($tx['total_installments'] ?? 0); ?></td>
                <td><span class="badge <?php echo ($tx['overdue_installments'] ?? 0) > 0 ? 'danger' : 'success'; ?>">
                    <?php echo (int)($tx['overdue_installments'] ?? 0); ?>
                </span></td>
                <td><span class="badge <?php echo ($tx['unpaid_penalties'] ?? 0) > 0 ? 'danger' : 'success'; ?>">
                    <?php echo (int)($tx['unpaid_penalties'] ?? 0); ?>
                </span></td>

                <td>
                    <span class="badge 
                        <?php echo $tx['status'] === 'completed' ? 'success' : ($tx['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                        <?php echo esc_html($tx['status'] ?? '—'); ?>
                    </span>
                </td>

                <td><?php echo esc_html($tx['created_at'] ?? '—'); ?></td>
            </tr>
        <?php endforeach; ?>

        </tbody>
    </table>

</div>

<?php
// فوتر مشترک
if (file_exists(CS_UI_DIR . 'admin/partials/footer.php')) {
    require_once CS_UI_DIR . 'admin/partials/footer.php';
}
?>