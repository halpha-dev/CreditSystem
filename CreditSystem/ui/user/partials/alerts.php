<?php
if (!defined('ABSPATH')) {
    exit;
}

/* =====================================
   شروع سشن اگر فعال نیست
===================================== */
if (!session_id()) {
    session_start();
}

/* =====================================
   دریافت پیام‌ها
===================================== */

$alerts = [];

/* پیام‌های سشن */
if (!empty($_SESSION['cs_alerts']) && is_array($_SESSION['cs_alerts'])) {
    $alerts = $_SESSION['cs_alerts'];
    unset($_SESSION['cs_alerts']);
}

/* پیام‌های از طریق URL */
if (isset($_GET['cs_success'])) {
    $alerts[] = [
        'type' => 'success',
        'message' => sanitize_text_field($_GET['cs_success'])
    ];
}

if (isset($_GET['cs_error'])) {
    $alerts[] = [
        'type' => 'error',
        'message' => sanitize_text_field($_GET['cs_error'])
    ];
}

if (isset($_GET['cs_warning'])) {
    $alerts[] = [
        'type' => 'warning',
        'message' => sanitize_text_field($_GET['cs_warning'])
    ];
}

if (isset($_GET['cs_info'])) {
    $alerts[] = [
        'type' => 'info',
        'message' => sanitize_text_field($_GET['cs_info'])
    ];
}

/* =====================================
   اگر پیامی نیست، خروج
===================================== */
if (empty($alerts)) {
    return;
}
?>

<div class="cs-alert-wrapper">

    <?php foreach ($alerts as $alert) :

        $type = isset($alert['type']) ? $alert['type'] : 'info';
        $message = isset($alert['message']) ? $alert['message'] : '';

        $class = 'cs-alert-info';

        switch ($type) {
            case 'success':
                $class = 'cs-alert-success';
                break;

            case 'error':
                $class = 'cs-alert-danger';
                break;

            case 'warning':
                $class = 'cs-alert-warning';
                break;

            case 'info':
            default:
                $class = 'cs-alert-info';
                break;
        }

        if (empty($message)) {
            continue;
        }

    ?>

        <div class="cs-alert <?php echo esc_attr($class); ?>">

            <div class="cs-alert-content">
                <?php echo esc_html($message); ?>
            </div>

            <button type="button" class="cs-alert-close" onclick="this.parentElement.remove();">
                ×
            </button>

        </div>

    <?php endforeach; ?>

</div>