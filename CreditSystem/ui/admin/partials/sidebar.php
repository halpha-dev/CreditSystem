<?php
if (!defined('ABSPATH')) {
    exit;
}

$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
$current_user = wp_get_current_user();

$is_admin = current_user_can('manage_options');
$is_merchant = current_user_can('cs_manage_merchant');
$is_user = current_user_can('read');

/**
 * Helper to check active menu
 */
function cs_is_active($slug, $current_page) {
    return $slug === $current_page ? 'active' : '';
}
?>

<aside class="cs-admin-sidebar">