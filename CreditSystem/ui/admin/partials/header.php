<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap cs-admin-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-money-alt" style="margin-right:8px;"></span>
        سیستم اعتبار
    </h1>

    <?php
    // نوتیس‌ها با require_once امن
    if (file_exists(CS_UI_DIR . 'admin/partials/notices.php')) {
        require_once CS_UI_DIR . 'admin/partials/notices.php';
    }
    ?>

    <div class="cs-admin-content">