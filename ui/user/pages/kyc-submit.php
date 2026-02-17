<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_die('برای ارسال احراز هویت باید وارد حساب کاربری شوید.');
}

$current_user_id = get_current_user_id();
$user            = wp_get_current_user();

/* ===============================
   بررسی نقش کاربر
================================= */

if (in_array('administrator', $user->roles) || in_array('merchant', $user->roles)) {
    wp_die('این بخش فقط برای کاربران عادی فعال است.');
}

/* ===============================
   دریافت وضعیت KYC
================================= */

$kyc_status = get_user_meta($current_user_id, 'cs_kyc_status', true);
$kyc_note   = get_user_meta($current_user_id, 'cs_kyc_note', true);

// #region agent log
file_put_contents(
    __DIR__ . '/../../../.cursor/debug.log',
    json_encode([
        'id' => 'log_' . time() . '_' . uniqid(),
        'timestamp' => (int) round(microtime(true) * 1000),
        'location' => 'ui/user/pages/kyc-submit.php:25',
        'message' => 'View KYC submit page',
        'data' => [
            'user_id' => $current_user_id,
            'kyc_status' => $kyc_status,
        ],
        'runId' => 'pre-fix',
        'hypothesisId' => 'B',
    ]) . "\n",
    FILE_APPEND
);
// #endregion

/* ===============================
   ارسال فرم
================================= */

if (isset($_POST['cs_kyc_submit'])) {

    // #region agent log
    file_put_contents(
        __DIR__ . '/../../../.cursor/debug.log',
        json_encode([
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => (int) round(microtime(true) * 1000),
            'location' => 'ui/user/pages/kyc-submit.php:32',
            'message' => 'KYC submit POST received',
            'data' => [
                'user_id' => $current_user_id,
                'has_files' => [
                    'national_card' => !empty($_FILES['national_card']['name'] ?? ''),
                    'selfie' => !empty($_FILES['selfie']['name'] ?? ''),
                ],
            ],
            'runId' => 'pre-fix',
            'hypothesisId' => 'C',
        ]) . "\n",
        FILE_APPEND
    );
    // #endregion

    if (!isset($_POST['cs_kyc_nonce']) || !wp_verify_nonce($_POST['cs_kyc_nonce'], 'cs_kyc_submit_action')) {
        wp_die('خطای امنیتی.');
    }

    $national_id = sanitize_text_field($_POST['national_id'] ?? '');
    $phone       = sanitize_text_field($_POST['phone'] ?? '');
    $address     = sanitize_textarea_field($_POST['address'] ?? '');

    update_user_meta($current_user_id, 'cs_kyc_national_id', $national_id);
    update_user_meta($current_user_id, 'cs_kyc_phone', $phone);
    update_user_meta($current_user_id, 'cs_kyc_address', $address);

    // #region agent log
    file_put_contents(
        __DIR__ . '/../../../.cursor/debug.log',
        json_encode([
            'id' => 'log_' . time() . '_' . uniqid(),
            'timestamp' => (int) round(microtime(true) * 1000),
            'location' => 'ui/user/pages/kyc-submit.php:48',
            'message' => 'KYC basic fields stored',
            'data' => [
                'user_id' => $current_user_id,
                'national_id_set' => !empty($national_id),
                'phone_set' => !empty($phone),
                'address_set' => !empty($address),
            ],
            'runId' => 'pre-fix',
            'hypothesisId' => 'C',
        ]) . "\n",
        FILE_APPEND
    );
    // #endregion

    /* آپلود کارت ملی */
    if (!empty($_FILES['national_card']['name'] ?? '')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $uploaded = wp_handle_upload($_FILES['national_card'], ['test_form' => false]);

        // #region agent log
        file_put_contents(
            __DIR__ . '/../../../.cursor/debug.log',
            json_encode([
                'id' => 'log_' . time() . '_' . uniqid(),
                'timestamp' => (int) round(microtime(true) * 1000),
                'location' => 'ui/user/pages/kyc-submit.php:64',
                'message' => 'National card upload result',
                'data' => [
                    'has_error' => isset($uploaded['error']),
                ],
                'runId' => 'pre-fix',
                'hypothesisId' => 'D',
            ]) . "\n",
            FILE_APPEND
        );
        // #endregion

        if (!isset($uploaded['error'])) {
            update_user_meta($current_user_id, 'cs_kyc_national_card', $uploaded['url']);
        }
    }

    /* آپلود سلفی */
    if (!empty($_FILES['selfie']['name'] ?? '')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';

        $uploaded = wp_handle_upload($_FILES['selfie'], ['test_form' => false]);

        // #region agent log
        file_put_contents(
            __DIR__ . '/../../../.cursor/debug.log',
            json_encode([
                'id' => 'log_' . time() . '_' . uniqid(),
                'timestamp' => (int) round(microtime(true) * 1000),
                'location' => 'ui/user/pages/kyc-submit.php:84',
                'message' => 'Selfie upload result',
                'data' => [
                    'has_error' => isset($uploaded['error']),
                ],
                'runId' => 'pre-fix',
                'hypothesisId' => 'D',
            ]) . "\n",
            FILE_APPEND
        );
        // #endregion

        if (!isset($uploaded['error'])) {
            update_user_meta($current_user_id, 'cs_kyc_selfie', $uploaded['url']);
        }
    }

    update_user_meta($current_user_id, 'cs_kyc_status', 'pending');

    // NOTE: submitted_at meta is not yet set; logs will confirm this hypothesis.

    echo '<div class="cs-alert cs-alert-success">درخواست احراز هویت شما ثبت شد و در انتظار بررسی است.</div>';

    $kyc_status = 'pending';
}

?>

<div class="cs-card">

    <h2>احراز هویت (KYC)</h2>

    <?php if ($kyc_status === 'approved') : ?>

        <div class="cs-alert cs-alert-success">
            احراز هویت شما تایید شده است.
        </div>

    <?php elseif ($kyc_status === 'pending') : ?>

        <div class="cs-alert cs-alert-warning">
            درخواست شما در حال بررسی است.
        </div>

    <?php elseif ($kyc_status === 'rejected') : ?>

        <div class="cs-alert cs-alert-error">
            درخواست شما رد شده است.
            <?php if (!empty($kyc_note)) : ?>
                <br>توضیح: <?php echo esc_html($kyc_note); ?>
            <?php endif; ?>
        </div>

    <?php endif; ?>

    <?php if ($kyc_status !== 'approved') : ?>

        <form method="post" enctype="multipart/form-data">

            <div class="cs-form-group">
                <label>کد ملی</label>
                <input type="text" name="national_id" required>
            </div>

            <div class="cs-form-group">
                <label>شماره موبایل</label>
                <input type="text" name="phone" required>
            </div>

            <div class="cs-form-group">
                <label>آدرس کامل</label>
                <textarea name="address" rows="3" required></textarea>
            </div>

            <div class="cs-form-group">
                <label>تصویر کارت ملی</label>
                <input type="file" name="national_card" accept="image/*" required>
            </div>

            <div class="cs-form-group">
                <label>تصویر سلفی با کارت ملی</label>
                <input type="file" name="selfie" accept="image/*" required>
            </div>

            <?php wp_nonce_field('cs_kyc_submit_action', 'cs_kyc_nonce'); ?>

            <button type="submit" name="cs_kyc_submit" class="cs-btn cs-btn-primary">
                ارسال درخواست
            </button>

        </form>

    <?php endif; ?>

</div>