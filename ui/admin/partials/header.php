<?php
/**
 * Credit System - Admin Header
 *
 * هدر مشترک همه صفحات ادمین + لود امن constants
 */

if (!defined('ABSPATH')) {
    exit;
}

/* =============================================
   لود امن constants (مهم‌ترین فیکس)
   ============================================= */
if (!defined('CS_TABLE_CREDITS')) {
    $constants_path = plugin_dir_path(dirname(__FILE__, 3)) . 'config/constants.php';
    if (file_exists($constants_path)) {
        require_once $constants_path;
    } else {
        wp_die('Credit System Error: فایل constants.php پیدا نشد!');
    }
}

if (!defined('CS_UI_DIR')) {
    define('CS_UI_DIR', plugin_dir_path(dirname(__FILE__, 3)) . 'ui/');
}
?>

<div class="wrap cs-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-money-alt" style="margin-right: 10px;"></span>
        سیستم اعتبار
    </h1>

    <?php
    // نمایش نوتیس‌ها
    if (file_exists(CS_UI_DIR . 'admin/partials/notices.php')) {
        require_once CS_UI_DIR . 'admin/partials/notices.php';
    }
    ?>

    <div class="cs-admin-content">