<?php
use CreditSystem\Core\Auth;
use CreditSystem\Core\Capabilities;

if (!defined('ABSPATH')) {
    exit;
}

if (!current_user_can('manage_options')) {
    wp_die('دسترسی غیرمجاز');
}

$option_key = 'cs_settings';

$defaults = [
    // عمومی
    'currency' => 'IRR',
    'enable_credit_system' => 1,

    // KYC
    'kyc_required_for_credit' => 1,
    'kyc_auto_approve' => 0,

    // اقساط
    'min_installment_amount' => 1000000,
    'max_installment_months' => 12,
    'default_interest_rate' => 0,

    // جریمه
    'penalty_enabled' => 1,
    'penalty_type' => 'percentage', // percentage | fixed
    'penalty_value' => 5,
    'penalty_grace_days' => 3,

    // یادآوری
    'reminder_enabled' => 1,
    'reminder_days_before' => 2,
    'reminder_days_after' => 1,

    // فروشندگان
    'merchant_auto_approve' => 0,
    'merchant_can_create_plan' => 0,

    // کردیت کد
    'credit_code_enabled' => 1,
    'credit_code_stackable' => 0,
    'credit_code_affect_penalty' => 0,
];

$settings = get_option($option_key, $defaults);
$settings = wp_parse_args($settings, $defaults);

if (isset($_POST['cs_save_settings'])) {

    check_admin_referer('cs_settings_nonce');

    $settings['currency'] = sanitize_text_field($_POST['currency']);
    $settings['enable_credit_system'] = isset($_POST['enable_credit_system']) ? 1 : 0;

    // KYC
    $settings['kyc_required_for_credit'] = isset($_POST['kyc_required_for_credit']) ? 1 : 0;
    $settings['kyc_auto_approve'] = isset($_POST['kyc_auto_approve']) ? 1 : 0;

    // اقساط
    $settings['min_installment_amount'] = floatval($_POST['min_installment_amount']);
    $settings['max_installment_months'] = intval($_POST['max_installment_months']);
    $settings['default_interest_rate'] = floatval($_POST['default_interest_rate']);

    // جریمه
    $settings['penalty_enabled'] = isset($_POST['penalty_enabled']) ? 1 : 0;
    $settings['penalty_type'] = sanitize_text_field($_POST['penalty_type']);
    $settings['penalty_value'] = floatval($_POST['penalty_value']);
    $settings['penalty_grace_days'] = intval($_POST['penalty_grace_days']);

    // یادآوری
    $settings['reminder_enabled'] = isset($_POST['reminder_enabled']) ? 1 : 0;
    $settings['reminder_days_before'] = intval($_POST['reminder_days_before']);
    $settings['reminder_days_after'] = intval($_POST['reminder_days_after']);

    // فروشندگان
    $settings['merchant_auto_approve'] = isset($_POST['merchant_auto_approve']) ? 1 : 0;
    $settings['merchant_can_create_plan'] = isset($_POST['merchant_can_create_plan']) ? 1 : 0;

    // کردیت کد
    $settings['credit_code_enabled'] = isset($_POST['credit_code_enabled']) ? 1 : 0;
    $settings['credit_code_stackable'] = isset($_POST['credit_code_stackable']) ? 1 : 0;
    $settings['credit_code_affect_penalty'] = isset($_POST['credit_code_affect_penalty']) ? 1 : 0;

    update_option($option_key, $settings);

    echo '<div class="updated"><p>تنظیمات ذخیره شد.</p></div>';
}
?>

<div class="wrap">
    <h1>تنظیمات سیستم اعتباری</h1>

    <form method="post">
        <?php wp_nonce_field('cs_settings_nonce'); ?>

        <h2>تنظیمات عمومی</h2>
        <table class="form-table">
            <tr>
                <th>فعال بودن سیستم اعتباری</th>
                <td>
                    <input type="checkbox" name="enable_credit_system" <?php checked($settings['enable_credit_system'],1); ?>>
                </td>
            </tr>
            <tr>
                <th>واحد پول</th>
                <td>
                    <input type="text" name="currency" value="<?php echo esc_attr($settings['currency']); ?>">
                </td>
            </tr>
        </table>

        <h2>تنظیمات KYC</h2>
        <table class="form-table">
            <tr>
                <th>الزام KYC برای استفاده از اعتبار</th>
                <td>
                    <input type="checkbox" name="kyc_required_for_credit" <?php checked($settings['kyc_required_for_credit'],1); ?>>
                </td>
            </tr>
            <tr>
                <th>تایید خودکار KYC</th>
                <td>
                    <input type="checkbox" name="kyc_auto_approve" <?php checked($settings['kyc_auto_approve'],1); ?>>
                </td>
            </tr>
        </table>

        <h2>تنظیمات اقساط و پلن‌ها</h2>
        <table class="form-table">
            <tr>
                <th>حداقل مبلغ خرید اقساطی</th>
                <td>
                    <input type="number" name="min_installment_amount" value="<?php echo esc_attr($settings['min_installment_amount']); ?>">
                </td>
            </tr>
            <tr>
                <th>حداکثر تعداد ماه اقساط</th>
                <td>
                    <input type="number" name="max_installment_months" value="<?php echo esc_attr($settings['max_installment_months']); ?>">
                </td>
            </tr>
            <tr>
                <th>نرخ سود پیش‌فرض (%)</th>
                <td>
                    <input type="number" step="0.01" name="default_interest_rate" value="<?php echo esc_attr($settings['default_interest_rate']); ?>">
                </td>
            </tr>
        </table>

        <h2>تنظیمات جریمه</h2>
        <table class="form-table">
            <tr>
                <th>فعال بودن جریمه دیرکرد</th>
                <td>
                    <input type="checkbox" name="penalty_enabled" <?php checked($settings['penalty_enabled'],1); ?>>
                </td>
            </tr>
            <tr>
                <th>نوع جریمه</th>
                <td>
                    <select name="penalty_type">
                        <option value="percentage" <?php selected($settings['penalty_type'],'percentage'); ?>>درصدی</option>
                        <option value="fixed" <?php selected($settings['penalty_type'],'fixed'); ?>>مبلغ ثابت</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th>مقدار جریمه</th>
                <td>
                    <input type="number" step="0.01" name="penalty_value" value="<?php echo esc_attr($settings['penalty_value']); ?>">
                </td>
            </tr>
            <tr>
                <th>مهلت قبل از اعمال جریمه (روز)</th>
                <td>
                    <input type="number" name="penalty_grace_days" value="<?php echo esc_attr($settings['penalty_grace_days']); ?>">
                </td>
            </tr>
        </table>

        <h2>تنظیمات یادآوری اقساط</h2>
        <table class="form-table">
            <tr>
                <th>فعال بودن یادآوری</th>
                <td>
                    <input type="checkbox" name="reminder_enabled" <?php checked($settings['reminder_enabled'],1); ?>>
                </td>
            </tr>
            <tr>
                <th>چند روز قبل از سررسید</th>
                <td>
                    <input type="number" name="reminder_days_before" value="<?php echo esc_attr($settings['reminder_days_before']); ?>">
                </td>
            </tr>
            <tr>
                <th>چند روز بعد از سررسید</th>
                <td>
                    <input type="number" name="reminder_days_after" value="<?php echo esc_attr($settings['reminder_days_after']); ?>">
                </td>
            </tr>
        </table>

        <h2>تنظیمات فروشندگان</h2>
        <table class="form-table">
            <tr>
                <th>تایید خودکار فروشنده</th>
                <td>
                    <input type="checkbox" name="merchant_auto_approve" <?php checked($settings['merchant_auto_approve'],1); ?>>
                </td>
            </tr>
            <tr>
                <th>امکان ساخت پلن توسط فروشنده</th>
                <td>
                    <input type="checkbox" name="merchant_can_create_plan" <?php checked($settings['merchant_can_create_plan'],1); ?>>
                </td>
            </tr>
        </table>

        <h2>تنظیمات Credit Code</h2>
        <table class="form-table">
            <tr>
                <th>فعال بودن کد تخفیف اعتباری</th>
                <td>
                    <input type="checkbox" name="credit_code_enabled" <?php checked($settings['credit_code_enabled'],1); ?>>
                </td>
            </tr>
            <tr>
                <th>امکان استفاده همزمان چند کد</th>
                <td>
                    <input type="checkbox" name="credit_code_stackable" <?php checked($settings['credit_code_stackable'],1); ?>>
                </td>
            </tr>
            <tr>
                <th>کسر جریمه از مبلغ بعد از اعمال کد</th>
                <td>
                    <input type="checkbox" name="credit_code_affect_penalty" <?php checked($settings['credit_code_affect_penalty'],1); ?>>
                </td>
            </tr>
        </table>

        <p class="submit">
            <button type="submit" name="cs_save_settings" class="button button-primary">
                ذخیره تنظیمات
            </button>
        </p>

    </form>
</div>