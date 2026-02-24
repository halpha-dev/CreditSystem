<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'cs_register_admin_menus');

function cs_register_admin_menus() {

    add_menu_page(
        'سیستم اقساط',
        'سیستم اقساط',
        'manage_options',
        'cs-dashboard',
        'cs_render_admin_dashboard',
        'dashicons-money-alt',
        6
    );

    $submenus = [
        'dashboard'          => ['داشبورد', 'cs_render_admin_dashboard'],
        'kyc-list'           => ['احراز هویت', 'cs_render_kyc_list'],
        'installment-plans'  => ['پلن‌های اقساط', 'cs_render_installment_plans'],
        'merchants'          => ['فروشندگان', 'cs_render_merchants'],
        'transactions'       => ['تراکنش‌ها', 'cs_render_transactions'],
        'penalties'          => ['جریمه‌ها', 'cs_render_penalties'],
        'reminders'          => ['یادآوری‌ها', 'cs_render_reminders'],
        'credit-codes'       => ['کردیت کدها', 'cs_render_credit_codes'],
        'settings'           => ['تنظیمات', 'cs_render_settings'],
    ];

    foreach ($submenus as $slug => $data) {
        add_submenu_page(
            'cs-dashboard',
            $data[0],
            $data[0],
            'manage_options',
            'cs-' . $slug,
            $data[1]
        );
    }
}

/* ===============================
   Render Functions
================================= */

function cs_render_admin_dashboard() {
    include CS_UI_PATH . 'admin/pages/dashboard.php';
}

function cs_render_kyc_list() {
    include CS_UI_PATH . 'admin/pages/kyc-list.php';
}

function cs_render_installment_plans() {
    include CS_UI_PATH . 'admin/pages/installment-plans.php';
}

function cs_render_merchants() {
    include CS_UI_PATH . 'admin/pages/merchants.php';
}

function cs_render_transactions() {
    include CS_UI_PATH . 'admin/pages/transactions.php';
}

function cs_render_penalties() {
    include CS_UI_PATH . 'admin/pages/penalties.php';
}

function cs_render_reminders() {
    include CS_UI_PATH . 'admin/pages/reminders.php';
}

function cs_render_credit_codes() {
    include CS_UI_PATH . 'admin/pages/credit-codes.php';
}

function cs_render_settings() {
    include CS_UI_PATH . 'admin/pages/settings.php';
}
