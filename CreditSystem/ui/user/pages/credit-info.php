<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_die('برای مشاهده اطلاعات اعتبار باید وارد حساب کاربری شوید.');
}

$current_user_id = get_current_user_id();
$user            = wp_get_current_user();

/* ===============================
   محدودیت نقش
================================= */
if (in_array('administrator', $user->roles) || in_array('merchant', $user->roles)) {
    wp_die('این بخش فقط برای کاربران عادی فعال است.');
}

/* ===============================
   بررسی KYC
================================= */
$kyc_status = get_user_meta($current_user_id, 'cs_kyc_status', true);

if ($kyc_status !== 'approved') {
    echo '<div class="cs-card">';
    echo '<div class="cs-alert cs-alert-warning">';
    echo 'برای مشاهده و استفاده از اعتبار، احراز هویت شما باید تایید شود.';
    echo '</div>';
    echo '<a href="' . esc_url(site_url('/user/kyc-status')) . '" class="cs-btn cs-btn-primary">مشاهده وضعیت احراز هویت</a>';
    echo '</div>';
    return;
}

/* ===============================
   اطلاعات اعتبار
================================= */

$credit_limit      = (float) get_user_meta($current_user_id, 'cs_credit_limit', true);
$credit_used       = (float) get_user_meta($current_user_id, 'cs_credit_used', true);
$credit_remaining  = max($credit_limit - $credit_used, 0);

$total_penalties   = (float) get_user_meta($current_user_id, 'cs_total_penalties', true);
$active_plan_id    = get_user_meta($current_user_id, 'cs_active_installment_plan', true);

/* ===============================
   اطلاعات اقساط
================================= */

$installments_paid      = 0;
$installments_remaining = 0;
$total_installments     = 0;

if ($active_plan_id) {

    $installments = get_posts([
        'post_type'  => 'cs_installment',
        'meta_key'   => 'cs_plan_id',
        'meta_value' => $active_plan_id,
        'numberposts'=> -1
    ]);

    foreach ($installments as $inst) {
        $status = get_post_meta($inst->ID, 'cs_installment_status', true);

        $total_installments++;

        if ($status === 'paid') {
            $installments_paid++;
        } else {
            $installments_remaining++;
        }
    }
}

/* ===============================
   کردیت کدهای فعال
================================= */

$credit_codes = get_posts([
    'post_type'  => 'cs_credit_code',
    'meta_key'   => 'cs_user_id',
    'meta_value' => $current_user_id,
    'numberposts'=> -1
]);

?>

<div class="cs-card">

    <h2>اطلاعات اعتبار</h2>

    <div class="cs-credit-grid">

        <div class="cs-credit-box">
            <span>سقف اعتبار</span>
            <strong><?php echo number_format($credit_limit); ?> تومان</strong>
        </div>

        <div class="cs-credit-box">
            <span>اعتبار مصرف‌شده</span>
            <strong><?php echo number_format($credit_used); ?> تومان</strong>
        </div>

        <div class="cs-credit-box">
            <span>اعتبار باقی‌مانده</span>
            <strong><?php echo number_format($credit_remaining); ?> تومان</strong>
        </div>

        <div class="cs-credit-box">
            <span>مجموع جریمه‌ها</span>
            <strong><?php echo number_format($total_penalties); ?> تومان</strong>
        </div>

    </div>

</div>


<?php if ($active_plan_id) : ?>

<div class="cs-card">

    <h2>وضعیت اقساط فعال</h2>

    <div class="cs-installment-summary">

        <div class="cs-installment-box">
            <span>تعداد کل اقساط</span>
            <strong><?php echo esc_html($total_installments); ?></strong>
        </div>

        <div class="cs-installment-box">
            <span>پرداخت‌شده</span>
            <strong><?php echo esc_html($installments_paid); ?></strong>
        </div>

        <div class="cs-installment-box">
            <span>باقی‌مانده</span>
            <strong><?php echo esc_html($installments_remaining); ?></strong>
        </div>

    </div>

    <a href="<?php echo esc_url(site_url('/user/installments')); ?>" class="cs-btn cs-btn-secondary">
        مشاهده جزئیات اقساط
    </a>

</div>

<?php endif; ?>


<?php if (!empty($credit_codes)) : ?>

<div class="cs-card">

    <h2>کردیت‌کدهای شما</h2>

    <table class="cs-table">
        <thead>
            <tr>
                <th>کد</th>
                <th>مبلغ</th>
                <th>وضعیت</th>
                <th>تاریخ ایجاد</th>
            </tr>
        </thead>
        <tbody>

        <?php foreach ($credit_codes as $code) :

            $amount  = get_post_meta($code->ID, 'cs_code_amount', true);
            $status  = get_post_meta($code->ID, 'cs_code_status', true);
        ?>

            <tr>
                <td><?php echo esc_html($code->post_title); ?></td>
                <td><?php echo number_format($amount); ?> تومان</td>
                <td>
                    <?php if ($status === 'active') : ?>
                        <span class="cs-badge cs-badge-success">فعال</span>
                    <?php elseif ($status === 'used') : ?>
                        <span class="cs-badge cs-badge-warning">استفاده شده</span>
                    <?php else : ?>
                        <span class="cs-badge cs-badge-danger">غیرفعال</span>
                    <?php endif; ?>
                </td>
                <td><?php echo esc_html(date('Y/m/d', strtotime($code->post_date))); ?></td>
            </tr>

        <?php endforeach; ?>

        </tbody>
    </table>

</div>

<?php endif; ?>