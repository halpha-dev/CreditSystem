<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('template_redirect', 'cs_user_router');

function cs_user_router() {

    if (!is_user_logged_in()) {
        return;
    }

    if (!is_page('account')) {
        return;
    }

    $user = wp_get_current_user();

    if (in_array('merchant', $user->roles) || in_array('administrator', $user->roles)) {
        return;
    }

    $page = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';

    $allowed_pages = [
        'dashboard',
        'kyc-submit',
        'kyc-status',
        'credit-info',
        'credit-codes',
        'credit-code-history',
        'installments',
        'transactions',
        'notifications',
    ];

    if (!in_array($page, $allowed_pages)) {
        $page = 'dashboard';
    }

    include CS_UI_PATH . 'user/partials/header.php';
    include CS_UI_PATH . 'user/pages/' . $page . '.php';
    include CS_UI_PATH . 'user/partials/footer.php';

    exit;
}
