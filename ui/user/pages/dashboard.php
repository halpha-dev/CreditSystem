<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url());
    exit;
}

global $wpdb;

$current_user_id = get_current_user_id();
$current_user = wp_get_current_user();

$is_admin = current_user_can('manage_options');
$is_merchant = current_user_can('cs_merchant');
$is_user = (!$is_admin && !$is_merchant);

/**
 * جداول
 */
$table_kyc = $wpdb->prefix . 'cs_kyc';
$table_credits = $wpdb->prefix . 'cs_credits';
$table_installments = $wpdb->prefix . 'cs_installments';
$table_codes = $wpdb->prefix . 'cs_credit_codes';

/**
 * وضعیت KYC
 */
$kyc_status = $wpdb->get_var($wpdb->prepare(
    "SELECT status FROM {$table_kyc} WHERE user_id = %d",
    $current_user_id
));

/**
 * اعتبارات کاربر
 */
$user_credits = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_credits}
     WHERE user_id = %d
     ORDER BY created_at DESC",
    $current_user_id
));

$total_credit_amount = 0;
$total_remaining = 0;

foreach ($user_credits as $credit) {
    $total_credit_amount += (float) $credit->amount;
    $total_remaining += (float) $credit->remaining_amount;
}

/**
 * اقساط معوق
 */
$today = current_time('Y-m-d');

$overdue_installments = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_installments}
     WHERE user_id = %d
     AND status IN ('pending','late')
     AND due_date < %s",
    $current_user_id,
    $today
));

$total_overdue = 0;

foreach ($overdue_installments as $row) {
    $total_overdue += (float) $row->amount + (float) $row->penalty_amount;
}

/**
 * کدهای اعتباری قابل استفاده
 */
$available_codes = $wpdb->get_results("
    SELECT * FROM {$table_codes}
    WHERE status = 'active'
    AND (expires_at IS NULL OR expires_at > NOW())
    AND (usage_limit IS NULL OR used_count < usage_limit)
    LIMIT 5
");
?>

<div class="cs-dashboard">

    <h2>داشبورد مالی</h2>

    <!-- وضعیت KYC -->
    <div class="cs-card">
        <h3>وضعیت احراز هویت (KYC)</h3>

        <?php if ($kyc_status === 'approved') : ?>
            <span class="cs-badge cs-success">تأیید شده</span>
        <?php elseif ($kyc_status === 'pending') : ?>
            <span class="cs-badge cs-warning">در حال بررسی</span>
        <?php else : ?>
            <span class="cs-badge cs-danger">تکمیل نشده</span>
            <a href="<?php echo esc_url(site_url('/kyc')); ?>" class="button">
                تکمیل احراز هویت
            </a>
        <?php endif; ?>
    </div>

    <!-- خلاصه اعتبار -->
    <div class="cs-card">
        <h3>خلاصه اعتبار</h3>

        <div class="cs-stats-grid">
            <div>
                <strong>مجموع اعتبار:</strong>
                <?php echo esc_html(number_format($total_credit_amount)); ?>
            </div>
            <div>
                <strong>باقیمانده:</strong>
                <?php echo esc_html(number_format($total_remaining)); ?>
            </div>
        </div>

        <?php if ($total_overdue > 0) : ?>
            <div class="cs-alert cs-danger">
                شما دارای اقساط معوق به مبلغ
                <?php echo esc_html(number_format($total_overdue)); ?>
                هستید.
            </div>
        <?php endif; ?>
    </div>

    <!-- لیست اقساط -->
    <div class="cs-card">
        <h3>اقساط آینده و معوق</h3>

        <?php
        $installments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_installments}
             WHERE user_id = %d
             ORDER BY due_date ASC
             LIMIT 10",
            $current_user_id
        ));
        ?>

        <?php if ($installments) : ?>
            <table class="cs-table">
                <thead>
                    <tr>
                        <th>مبلغ</th>
                        <th>سررسید</th>
                        <th>وضعیت</th>
                        <th>جریمه</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($installments as $ins) : ?>
                    <tr>
                        <td><?php echo esc_html(number_format($ins->amount)); ?></td>
                        <td><?php echo esc_html(date_i18n('Y-m-d', strtotime($ins->due_date))); ?></td>
                        <td>
                            <?php
                            $status_label = $ins->status;
                            if ($ins->status === 'late') {
                                $status_label = 'معوق';
                            } elseif ($ins->status === 'paid') {
                                $status_label = 'پرداخت شده';
                            } else {
                                $status_label = 'در انتظار';
                            }
                            echo esc_html($status_label);
                            ?>
                        </td>
                        <td><?php echo esc_html(number_format($ins->penalty_amount)); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>هیچ قسطی ثبت نشده است.</p>
        <?php endif; ?>
    </div>

    <!-- کردیت کدها -->
    <div class="cs-card">
        <h3>کردیت کدهای فعال</h3>

        <?php if ($available_codes) : ?>
            <ul class="cs-code-list">
                <?php foreach ($available_codes as $code) : ?>
                    <li>
                        <strong><?php echo esc