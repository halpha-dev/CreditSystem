<?php
/**
 * Credit System - Merchants Management
 */

if (!defined('ABSPATH')) exit;

// بررسی سطح دسترسی
if (!current_user_can('manage_options')) {
    wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'credit-system'));
}

global $wpdb;

/* =========================
   پرداختن به درخواست‌های POST
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // بررسی اعتبار امنیتی (Nonce)
    if (!isset($_POST['cs_merchant_nonce']) || !wp_verify_nonce($_POST['cs_merchant_nonce'], 'cs_merchant_action')) {
        wp_die('خطای امنیتی: درخواست شما نامعتبر است.');
    }

    $merchant_id = isset($_POST['merchant_id']) ? intval($_POST['merchant_id']) : 0;

    if ($merchant_id > 0) {
        if (isset($_POST['toggle_status'])) {
            // تغییر وضعیت بین فعال و غیرفعال
            $wpdb->query($wpdb->prepare("
                UPDATE {$wpdb->prefix}users 
                SET status = IF(status='active', 'inactive', 'active') 
                WHERE ID = %d", 
            $merchant_id));
        }

        if (isset($_POST['force_kyc_reset'])) {
            // ریست کردن وضعیت KYC به در حال بررسی
            $wpdb->update(
                'kyc_requests',
                array('status' => 'pending'),
                array('user_id' => $merchant_id),
                array('%s'),
                array('%d')
            );
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>تغییرات با موفقیت اعمال شد.</p></div>';
    }
}

/* =========================
   دریافت لیست فروشندگان
========================= */
$sql = "
    SELECT u.ID as id, u.display_name as name, u.user_email as email, u.status,
           (SELECT status FROM kyc_requests WHERE user_id = u.ID ORDER BY id DESC LIMIT 1) AS kyc_status,
           (SELECT COUNT(*) FROM transactions WHERE merchant_id = u.ID) AS total_transactions,
           (SELECT SUM(total_amount) FROM transactions WHERE merchant_id = u.ID AND status='completed') AS total_sales,
           (SELECT COUNT(*) FROM installments WHERE merchant_id = u.ID AND status='overdue') AS overdue_installments,
           (SELECT COUNT(*) FROM penalties WHERE merchant_id = u.ID AND status='unpaid') AS unpaid_penalties,
           (SELECT COUNT(*) FROM credit_codes WHERE merchant_id = u.ID) AS credit_codes_count
    FROM {$wpdb->prefix}users u
    INNER JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id
    WHERE um.meta_key = '{$wpdb->prefix}capabilities' AND um.meta_value LIKE '%cs_merchant%'
    ORDER BY u.user_registered DESC
";

$merchants = $wpdb->get_results($sql, ARRAY_A);

// لود هدر و سایدبار (با فرض وجود در ساختار شما)
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';
?>

<div class="wrap admin-merchants">
    <h1 class="wp-heading-inline">مدیریت فروشندگان</h1>
    <hr class="wp-header-end">

    <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
            <th>نام فروشنده</th>
            <th>ایمیل</th>
            <th>وضعیت حساب</th>
            <th>وضعیت KYC</th>
            <th>تراکنش‌ها</th>
            <th>فروش کل</th>
            <th>اقساط معوق</th>
            <th>جریمه‌ها</th>
            <th>کد اعتبار</th>
            <th>عملیات</th>
        </tr>
        </thead>

        <tbody>
        <?php if (empty($merchants)): ?>
            <tr>
                <td colspan="10">هیچ فروشنده‌ای با نقش cs_merchant یافت نشد.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($merchants as $merchant): ?>
                <tr>
                    <td><strong><?php echo esc_html($merchant['name']); ?></strong></td>
                    <td><?php echo esc_html($merchant['email']); ?></td>
                    <td>
                        <span class="badge <?php echo $merchant['status'] === 'active' ? 'success' : 'danger'; ?>">
                            <?php echo $merchant['status'] === 'active' ? 'فعال' : 'غیرفعال'; ?>
                        </span>
                    </td>
                    <td>
                        <?php $kyc = $merchant['kyc_status'] ?: 'none'; ?>
                        <span class="badge badge-<?php echo esc_attr($kyc); ?>">
                            <?php echo esc_html($kyc); ?>
                        </span>
                    </td>
                    <td><?php echo intval($merchant['total_transactions']); ?></td>
                    <td><?php echo number_format((float)$merchant['total_sales']); ?> <small>تومان</small></td>
                    <td>
                        <span class="badge <?php echo $merchant['overdue_installments'] > 0 ? 'danger' : 'success'; ?>">
                            <?php echo intval($merchant['overdue_installments']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?php echo $merchant['unpaid_penalties'] > 0 ? 'danger' : 'success'; ?>">
                            <?php echo intval($merchant['unpaid_penalties']); ?>
                        </span>
                    </td>
                    <td><?php echo intval($merchant['credit_codes_count']); ?></td>
                    <td>
                        <form method="post" style="display:inline-block;">
                            <?php wp_nonce_field('cs_merchant_action', 'cs_merchant_nonce'); ?>
                            <input type="hidden" name="merchant_id" value="<?php echo $merchant['id']; ?>">
                            
                            <button type="submit" name="toggle_status" class="button button-small">
                                تغییر وضعیت
                            </button>
                            
                            <button type="submit" name="force_kyc_reset" class="button button-small" onclick="return confirm('آیا از ریست کردن KYC اطمینان دارید؟')">
                                ریست KYC
                            </button>
                        </form>
                        
                        <a href="transactions.php?merchant_id=<?php echo $merchant['id']; ?>" class="button button-small">
                            مشاهده تراکنش‌ها
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>