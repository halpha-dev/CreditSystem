<?php
use CreditSystem\Models\CreditCode;
use CreditSystem\Models\Merchant;
use CreditSystem\Models\InstallmentPlan;

if (!defined('ABSPATH')) exit;

if (!current_user_can('manage_options')) {
    wp_die('دسترسی غیرمجاز');
}

global $wpdb;

/* ===============================
   پردازش عملیات‌ها
================================ */

if (isset($_POST['create_credit_code'])) {

    check_admin_referer('create_credit_code_nonce');

    $wpdb->insert($wpdb->prefix . 'credit_codes', [
        'code' => sanitize_text_field($_POST['code']),
        'merchant_id' => intval($_POST['merchant_id']),
        'plan_id' => intval($_POST['plan_id']),
        'type' => sanitize_text_field($_POST['type']),
        'discount_type' => sanitize_text_field($_POST['discount_type']),
        'discount_value' => floatval($_POST['discount_value']),
        'max_usage' => intval($_POST['max_usage']),
        'expires_at' => sanitize_text_field($_POST['expires_at']),
        'status' => 'active',
        'created_at' => current_time('mysql')
    ]);

    echo '<div class="updated"><p>کردیت کد ایجاد شد.</p></div>';
}

if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $code = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}credit_codes WHERE id = $id");

    $new_status = $code->status === 'active' ? 'inactive' : 'active';

    $wpdb->update(
        $wpdb->prefix . 'credit_codes',
        ['status' => $new_status],
        ['id' => $id]
    );

    echo '<div class="updated"><p>وضعیت تغییر کرد.</p></div>';
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $wpdb->delete($wpdb->prefix . 'credit_codes', ['id' => $id]);
    echo '<div class="updated"><p>کردیت کد حذف شد.</p></div>';
}

/* ===============================
   دریافت داده‌ها
================================ */

$codes = $wpdb->get_results("
    SELECT c.*, m.name as merchant_name, p.title as plan_title
    FROM {$wpdb->prefix}credit_codes c
    LEFT JOIN {$wpdb->prefix}merchants m ON c.merchant_id = m.id
    LEFT JOIN {$wpdb->prefix}installment_plans p ON c.plan_id = p.id
    ORDER BY c.id DESC
");

$merchants = $wpdb->get_results("SELECT id, name FROM {$wpdb->prefix}merchants");
$plans = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}installment_plans");

?>

<div class="wrap">
    <h1>مدیریت کردیت کدها</h1>

    <!-- فرم ایجاد -->
    <h2>ایجاد کردیت کد جدید</h2>
    <form method="post">
        <?php wp_nonce_field('create_credit_code_nonce'); ?>

        <table class="form-table">
            <tr>
                <th>کد</th>
                <td><input type="text" name="code" required></td>
            </tr>

            <tr>
                <th>فروشنده</th>
                <td>
                    <select name="merchant_id" required>
                        <option value="">انتخاب کنید</option>
                        <?php foreach ($merchants as $m): ?>
                            <option value="<?= $m->id ?>"><?= esc_html($m->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th>پلن اقساط</th>
                <td>
                    <select name="plan_id">
                        <option value="">بدون محدودیت</option>
                        <?php foreach ($plans as $p): ?>
                            <option value="<?= $p->id ?>"><?= esc_html($p->title) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <tr>
                <th>نوع</th>
                <td>
                    <select name="type">
                        <option value="public">عمومی</option>
                        <option value="private">اختصاصی</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>نوع تخفیف</th>
                <td>
                    <select name="discount_type">
                        <option value="percent">درصدی</option>
                        <option value="fixed">مبلغ ثابت</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th>مقدار تخفیف</th>
                <td><input type="number" name="discount_value" required></td>
            </tr>

            <tr>
                <th>حداکثر استفاده</th>
                <td><input type="number" name="max_usage" value="1"></td>
            </tr>

            <tr>
                <th>تاریخ انقضا</th>
                <td><input type="date" name="expires_at"></td>
            </tr>
        </table>

        <p><input type="submit" name="create_credit_code" class="button button-primary" value="ایجاد"></p>
    </form>

    <hr>

    <!-- لیست -->
    <h2>لیست کردیت کدها</h2>

    <table class="widefat striped">
        <thead>
            <tr>
                <th>کد</th>
                <th>فروشنده</th>
                <th>پلن</th>
                <th>تخفیف</th>
                <th>استفاده</th>
                <th>انقضا</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($codes): ?>
            <?php foreach ($codes as $code): ?>
                <tr>
                    <td><strong><?= esc_html($code->code) ?></strong></td>
                    <td><?= esc_html($code->merchant_name ?? '-') ?></td>
                    <td><?= esc_html($code->plan_title ?? 'همه پلن‌ها') ?></td>
                    <td>
                        <?php
                        echo $code->discount_type === 'percent'
                            ? $code->discount_value . '%'
                            : number_format($code->discount_value) . ' تومان';
                        ?>
                    </td>
                    <td><?= intval($code->used_count) ?> / <?= intval($code->max_usage) ?></td>
                    <td><?= esc_html($code->expires_at ?: '-') ?></td>
                    <td>
                        <?php if ($code->status === 'active'): ?>
                            <span style="color:green;">فعال</span>
                        <?php else: ?>
                            <span style="color:red;">غیرفعال</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="?page=credit-codes&toggle=<?= $code->id ?>">تغییر وضعیت</a> |
                        <a href="?page=credit-codes&delete=<?= $code->id ?>" 
                           onclick="return confirm('حذف شود؟')">حذف</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="8">موردی یافت نشد.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

</div>