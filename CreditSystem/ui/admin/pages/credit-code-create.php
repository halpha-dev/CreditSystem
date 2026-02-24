<?php
if (!defined('ABSPATH')) {
    exit;
}

// ===== Permission Check =====
$current_user = wp_get_current_user();
$is_admin = in_array('administrator', $current_user->roles);

if (!$is_admin) {
    wp_die('دسترسی غیرمجاز');
}

// ===== Detect Edit Mode =====
$edit_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$editing = $edit_id > 0;

global $wpdb;
$table = $wpdb->prefix . 'credit_codes';

$code_data = [
    'code' => '',
    'type' => 'fixed',
    'value' => 0,
    'usage_limit' => 1,
    'used_count' => 0,
    'start_date' => '',
    'expiry_date' => '',
    'merchant_id' => 0,
    'plan_id' => 0,
    'min_amount' => 0,
    'kyc_required' => 1,
    'penalty_exempt' => 0,
    'status' => 1,
    'notes' => '',
];

if ($editing) {
    $code_data = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id),
        ARRAY_A
    );
}

// ===== Handle Save =====
if (isset($_POST['save_credit_code'])) {

    check_admin_referer('save_credit_code_nonce');

    $data = [
        'code' => sanitize_text_field($_POST['code']),
        'type' => sanitize_text_field($_POST['type']),
        'value' => floatval($_POST['value']),
        'usage_limit' => intval($_POST['usage_limit']),
        'start_date' => sanitize_text_field($_POST['start_date']),
        'expiry_date' => sanitize_text_field($_POST['expiry_date']),
        'merchant_id' => intval($_POST['merchant_id']),
        'plan_id' => intval($_POST['plan_id']),
        'min_amount' => floatval($_POST['min_amount']),
        'kyc_required' => isset($_POST['kyc_required']) ? 1 : 0,
        'penalty_exempt' => isset($_POST['penalty_exempt']) ? 1 : 0,
        'status' => intval($_POST['status']),
        'notes' => sanitize_textarea_field($_POST['notes']),
    ];

    // Vendor restriction
    if ($is_vendor && !$is_admin) {
        $data['merchant_id'] = $current_user->ID;
    }

    if ($editing) {
        $wpdb->update($table, $data, ['id' => $edit_id]);
    } else {
        $data['used_count'] = 0;
        $wpdb->insert($table, $data);
    }

    echo '<div class="updated"><p>کردیت کد با موفقیت ذخیره شد.</p></div>';
}

// ===== Fetch Merchants =====
$merchants = get_users(['role' => 'merchant']);

// ===== Fetch Installment Plans =====
$plans_table = $wpdb->prefix . 'installment_plans';
$plans = $wpdb->get_results("SELECT id, name FROM $plans_table");

?>

<div class="wrap">
<h1><?php echo $editing ? 'ویرایش کردیت کد' : 'ایجاد کردیت کد جدید'; ?></h1>

<form method="post">
<?php wp_nonce_field('save_credit_code_nonce'); ?>

<table class="form-table">

<tr>
<th>کد</th>
<td><input type="text" name="code" required value="<?php echo esc_attr($code_data['code']); ?>" class="regular-text"></td>
</tr>

<tr>
<th>نوع اعتبار</th>
<td>
<select name="type">
    <option value="fixed" <?php selected($code_data['type'], 'fixed'); ?>>مبلغ ثابت</option>
    <option value="percentage" <?php selected($code_data['type'], 'percentage'); ?>>درصدی</option>
</select>
</td>
</tr>

<tr>
<th>مقدار</th>
<td><input type="number" step="0.01" name="value" value="<?php echo esc_attr($code_data['value']); ?>"></td>
</tr>

<tr>
<th>حد استفاده</th>
<td><input type="number" name="usage_limit" value="<?php echo esc_attr($code_data['usage_limit']); ?>"></td>
</tr>

<tr>
<th>حداقل مبلغ خرید</th>
<td><input type="number" step="0.01" name="min_amount" value="<?php echo esc_attr($code_data['min_amount']); ?>"></td>
</tr>

<tr>
<th>تاریخ شروع</th>
<td><input type="date" name="start_date" value="<?php echo esc_attr($code_data['start_date']); ?>"></td>
</tr>

<tr>
<th>تاریخ انقضا</th>
<td><input type="date" name="expiry_date" value="<?php echo esc_attr($code_data['expiry_date']); ?>"></td>
</tr>

<tr>
<th>محدود به فروشنده</th>
<td>
<select name="merchant_id">
<option value="0">همه فروشندگان</option>
<?php foreach ($merchants as $m): ?>
<option value="<?php echo $m->ID; ?>" <?php selected($code_data['merchant_id'], $m->ID); ?>>
<?php echo esc_html($m->display_name); ?>
</option>
<?php endforeach; ?>
</select>
</td>
</tr>

<tr>
<th>محدود به پلن اقساط</th>
<td>
<select name="plan_id">
<option value="0">همه پلن‌ها</option>
<?php foreach ($plans as $plan): ?>
<option value="<?php echo $plan->id; ?>" <?php selected($code_data['plan_id'], $plan->id); ?>>
<?php echo esc_html($plan->name); ?>
</option>
<?php endforeach; ?>
</select>
</td>
</tr>

<tr>
<th>نیازمند KYC تایید شده</th>
<td><input type="checkbox" name="kyc_required" <?php checked($code_data['kyc_required'], 1); ?>></td>
</tr>

<tr>
<th>معاف از جریمه اقساط</th>
<td><input type="checkbox" name="penalty_exempt" <?php checked($code_data['penalty_exempt'], 1); ?>></td>
</tr>

<tr>
<th>وضعیت</th>
<td>
<select name="status">
<option value="1" <?php selected($code_data['status'], 1); ?>>فعال</option>
<option value="0" <?php selected($code_data['status'], 0); ?>>غیرفعال</option>
</select>
</td>
</tr>

<tr>
<th>توضیحات داخلی</th>
<td>
<textarea name="notes" rows="4" class="large-text"><?php echo esc_textarea($code_data['notes']); ?></textarea>
</td>
</tr>

</table>

<p class="submit">
<input type="submit" name="save_credit_code" class="button-primary" value="ذخیره">
<a href="admin.php?page=credit-codes" class="button">بازگشت</a>
</p>

</form>
</div>