<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', 'cs_merchant_router');

function cs_merchant_router() {

    if (!is_user_logged_in()) {
        return;
    }

    if (!is_page('merchant-panel')) {
        return;
    }

    $user = wp_get_current_user();

    if (!in_array('merchant', $user->roles)) {
        wp_die('دسترسی غیرمجاز.');
    }

    $page = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

    $allowed_pages = [
        'dashboard',
        'sales',
        'settlements',
        'customers',
        'credit-code-apply',
        'reports',
    ];

    if (!in_array($page, $allowed_pages)) {
        $page = 'dashboard';
    }

    include CS_UI_PATH . 'merchant/partials/header.php';
    include CS_UI_PATH . 'merchant/pages/' . $page . '.php';
    include CS_UI_PATH . 'merchant/partials/footer.php';

    exit;
}
