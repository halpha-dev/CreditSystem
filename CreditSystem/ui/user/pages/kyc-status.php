<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_die('برای مشاهده وضعیت احراز هویت باید وارد حساب کاربری شوید.');
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
   دریافت اطلاعات KYC
================================= */

$kyc_status       = get_user_meta($current_user_id, 'cs_kyc_status', true);
$kyc_note         = get_user_meta($current_user_id, 'cs_kyc_note', true);
$national_id      = get_user_meta($current_user_id, 'cs_kyc_national_id', true);
$phone            = get_user_meta($current_user_id, 'cs_kyc_phone', true);
$address          = get_user_meta($current_user_id, 'cs_kyc_address', true);
$national_card    = get_user_meta($current_user_id, 'cs_kyc_national_card', true);
$selfie           = get_user_meta($current_user_id, 'cs_kyc_selfie', true);
$submitted_at     = get_user_meta($current_user_id, 'cs_kyc_submitted_at', true);

// #region agent log
file_put_contents(
    __DIR__ . '/../../../.cursor/debug.log',
    json_encode([
        'id' => 'log_' . time() . '_' . uniqid(),
        'timestamp' => (int) round(microtime(true) * 1000),
        'location' => 'ui/user/pages/kyc-status.php:24',
        'message' => 'Load KYC status page',
        'data' => [
            'user_id' => $current_user_id,
            'kyc_status' => $kyc_status,
            'has_national_id' => !empty($national_id),
            'has_phone' => !empty($phone),
            'has_address' => !empty($address),
            'has_national_card' => !empty($national_card),
            'has_selfie' => !empty($selfie),
            'submitted_at' => $submitted_at,
        ],
        'runId' => 'pre-fix',
        'hypothesisId' => 'A',
    ]) . "\n",
    FILE_APPEND
);
// #endregion

?>

<div class="cs-card">

    <h2>وضعیت احراز هویت (KYC)</h2>

    <?php if (empty($kyc_status)) : ?>

        <div class="cs-alert cs-alert-warning">
            شما هنوز درخواست احراز هویت ثبت نکرده‌اید.
        </div>

        <a href="<?php echo esc_url(site_url('/user/kyc-submit')); ?>" class="cs-btn cs-btn-primary">
            ثبت درخواست احراز هویت
        </a>

    <?php else : ?>

        <div class="cs-kyc-status-box">

            <?php if ($kyc_status === 'pending') : ?>
                <span class="cs-badge cs-badge-warning">در حال بررسی</span>
            <?php elseif ($kyc_status === 'approved') : ?>
                <span class="cs-badge cs-badge-success">تایید شده</span>
            <?php elseif ($kyc_status === 'rejected') : ?>
                <span class="cs-badge cs-badge-danger">رد شده</span>
            <?php endif; ?>

        </div>

        <?php if ($kyc_status === 'approved') : ?>

            <div class="cs-alert cs-alert-success">
                احراز هویت شما تایید شده است و امکان استفاده از سیستم اقساط و کردیت کد فعال می‌باشد.
            </div>

        <?php elseif ($kyc_status === 'pending') : ?>

            <div class="cs-alert cs-alert-warning">
                درخواست شما در حال بررسی توسط ادمین است.
            </div>

        <?php elseif ($kyc_status === 'rejected') : ?>

            <div class="cs-alert cs-alert-error">
                درخواست شما رد شده است.
                <?php if (!empty($kyc_note)) : ?>
                    <br>توضیح ادمین: <?php echo esc_html($kyc_note); ?>
                <?php endif; ?>
            </div>

            <a href="<?php echo esc_url(site_url('/user/kyc-submit')); ?>" class="cs-btn cs-btn-secondary">
                ارسال مجدد مدارک
            </a>

        <?php endif; ?>

        <hr>

        <div class="cs-kyc-details">

            <h3>اطلاعات ثبت شده</h3>

            <ul class="cs-info-list">
                <li><strong>کد ملی:</strong> <?php echo esc_html($national_id); ?></li>
                <li><strong>شماره موبایل:</strong> <?php echo esc_html($phone); ?></li>
                <li><strong>آدرس:</strong> <?php echo esc_html($address); ?></li>

                <?php if (!empty($submitted_at)) : ?>
                    <li><strong>تاریخ ارسال:</strong> <?php echo esc_html(date('Y/m/d H:i', strtotime($submitted_at))); ?></li>
                <?php endif; ?>
            </ul>

        </div>

        <div class="cs-kyc-documents">

            <h3>مدارک بارگذاری شده</h3>

            <div class="cs-doc-grid">

                <?php if (!empty($national_card)) : ?>
                    <div class="cs-doc-item">
                        <p>تصویر کارت ملی</p>
                        <a href="<?php echo esc_url($national_card); ?>" target="_blank">
                            <img src="<?php echo esc_url($national_card); ?>" alt="کارت ملی">
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($selfie)) : ?>
                    <div class="cs-doc-item">
                        <p>سلفی با کارت ملی</p>
                        <a href="<?php echo esc_url($selfie); ?>" target="_blank">
                            <img src="<?php echo esc_url($selfie); ?>" alt="سلفی">
                        </a>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    <?php endif; ?>

</div>