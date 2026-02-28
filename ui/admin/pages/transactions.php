<?php
/**
 * Credit System - Transactions Page
 * Standardized & Fixed Version
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی سطح دسترسی
if (!current_user_can('manage_options')) {
    wp_die(__('شما مجوز دسترسی به این صفحه را ندارید.', 'credit-system'));
}

global $wpdb;

/* =============================================
   لود ثابت‌ها (Constants)
   ============================================= */
if (!defined('CS_UI_DIR')) {
    $constants_path = plugin_dir_path(dirname(__FILE__, 3)) . 'config/constants.php';
    if (file_exists($constants_path)) {
        require_once $constants_path;
    }
    define('CS_UI_DIR', plugin_dir_path(dirname(__FILE__, 3)) . 'ui/');
}

// فراخوانی المان‌های رابط کاربری
require_once CS_UI_DIR . 'admin/partials/header.php';
require_once CS_UI_DIR . 'admin/partials/sidebar.php';
require_once CS_UI_DIR . 'admin/partials/notices.php';

/* =========================
   فیلترها و پارامترها
========================= */
$merchant_filter = isset($_GET['merchant_id']) ? intval($_GET['merchant_id']) : null;

/* =========================
   دریافت تراکنش‌ها
========================= */
// استفاده از نام جداول وردپرس در صورت تعریف شدن در Constants
$table_transactions = defined('CS_TABLE_TRANSACTIONS') ? CS_TABLE_TRANSACTIONS : 'transactions';

$sql = "
SELECT t.*,
       u.name AS user_name,
       u.email AS user_email,
       m.name AS merchant_name,
       p.name AS plan_name,
       p.installment_count,
       c.code AS credit_code,
       c.type AS credit_type,
       c.value AS credit_value,
       (SELECT status FROM kyc_requests 
            WHERE user_id = t.user_id 
            ORDER BY id DESC LIMIT 1) AS user_kyc_status,
       (SELECT COUNT(*) FROM installments 
            WHERE transaction_id = t.id) AS total_installments,
       (SELECT COUNT(*) FROM installments 
            WHERE transaction_id = t.id AND status='overdue') AS overdue_installments,
       (SELECT COUNT(*) FROM penalties 
            WHERE installment_id IN 
                (SELECT id FROM installments WHERE transaction_id=t.id)
            AND status='unpaid') AS unpaid_penalties
FROM $table_transactions t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN users m ON t.merchant_id = m.id
LEFT JOIN installment_plans p ON t.plan_id = p.id
LEFT JOIN credit_codes c ON t.credit_code_id = c.id
";

if ($merchant_filter) {
    $sql .= $wpdb->prepare(" WHERE t.merchant_id = %d", $merchant_filter);
}

$sql .= " ORDER BY t.created_at DESC";

// متد صحیح برای دریافت نتایج در وردپرس
$transactions = $wpdb->get_results($sql, ARRAY_A);
?>

<div class="admin-transactions" style="padding: 20px;">
    <h1>مدیریت تراکنش‌ها</h1>

    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;">ID</th>
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
                <tr>
                    <td colspan="13">تراکنشی یافت نشد.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                    <tr>
                        <td>#<?php echo esc_html($tx['id']); ?></td>
                        <td>
                            <strong><?php echo esc_html($tx['user_name']); ?></strong><br>
                            <small style="color: #666;"><?php echo esc_html($tx['user_email']); ?></small>
                        </td>
                        <td>
                            <?php 
                            $kyc_class = ($tx['user_kyc_status'] === 'approved') ? 'success' : (($tx['user_kyc_status'] === 'pending') ? 'warning' : 'danger');
                            ?>
                            <span class="badge badge-<?php echo $kyc_class; ?>">
                                <?php echo esc_html($tx['user_kyc_status'] ?: 'unknown'); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($tx['merchant_name']); ?></td>
                        <td>
                            <?php echo esc_html($tx['plan_name']); ?><br>
                            <small><?php echo intval($tx['installment_count']); ?> قسط</small>
                        </td>
                        <td><?php echo number_format($tx['total_amount']); ?></td>
                        <td><strong><?php echo number_format($tx['final_amount']); ?></strong></td>
                        <td>
                            <?php if (!empty($tx['credit_code'])): ?>
                                <span class="badge success"><?php echo esc_html($tx['credit_code']); ?></span><br>
                                <small><?php echo esc_html($tx['credit_type']); ?>: <?php echo esc_html($tx['credit_value']); ?></small>
                            <?php else: ?>
                                <span class="badge secondary">ندارد</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo intval($tx['total_installments']); ?></td>
                        <td>
                            <span class="badge <?php echo ($tx['overdue_installments'] > 0) ? 'danger' : 'success'; ?>">
                                <?php echo intval($tx['overdue_installments']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?php echo ($tx['unpaid_penalties'] > 0) ? 'danger' : 'success'; ?>">
                                <?php echo intval($tx['unpaid_penalties']); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $status_class = ($tx['status'] === 'completed') ? 'success' : (($tx['status'] === 'pending') ? 'warning' : 'danger');
                            ?>
                            <span class="badge badge-<?php echo $status_class; ?>">
                                <?php echo esc_html($tx['status']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($tx['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php 
// فوتر سیستم
require_once CS_UI_DIR . 'admin/partials/footer.php'; 
?>