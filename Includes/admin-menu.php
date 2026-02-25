<?php
/**
 * Credit System - Admin Menu
 *
 * نسخه نهایی و کاملاً فیکس‌شده - ۲۴ فوریه ۲۰۲۶
 */

if (!defined('ABSPATH')) {
    exit; // امنیت مستقیم
}

/* =============================================
   Safeguard ثابت‌ها (حتی اگر CS_Core دیر لود بشه)
   ============================================= */
if (!defined('CS_UI_DIR')) {
    if (defined('CS_PLUGIN_DIR')) {
        define('CS_UI_DIR', CS_PLUGIN_DIR . 'ui/');
    } else {
        // fallback امن
        define('CS_UI_DIR', plugin_dir_path(dirname(__FILE__)) . 'ui/');
    }
}

if (!defined('CS_UI_DIR') || !is_dir(CS_UI_DIR)) {
    wp_die('Credit System Error: مسیر UI پیدا نشد. لطفاً CS_Core.php و constants.php را چک کنید.');
}

/**
 * ثبت منوی ادمین
 */
function cs_register_admin_menu(): void
{
    $parent_slug = 'credit-system';

    add_menu_page(
        __('سیستم اعتبار', 'credit-system'),
        __('سیستم اعتبار', 'credit-system'),
        'manage_options',
        $parent_slug,
        'cs_render_admin_page',
        'dashicons-money-alt',
        58
    );

    $submenus = [
        ['dashboard',          __('داشبورد', 'credit-system'),          'dashboard.php'],
        ['credit-codes',       __('کدهای اعتبار', 'credit-system'),     'credit-codes.php'],
        ['credit-code-create', __('ایجاد کد جدید', 'credit-system'),    'credit-code-create.php'],
        ['transactions',       __('تراکنش‌ها', 'credit-system'),        'transactions.php'],
        ['merchants',          __('فروشندگان', 'credit-system'),         'merchants.php'],
        ['installment-plans',  __('برنامه‌های قسطی', 'credit-system'), 'installment-plans.php'],
        ['kyc-list',           __('درخواست‌های KYC', 'credit-system'),  'kyc-list.php'],
        ['penalties',          __('جریمه‌ها', 'credit-system'),         'penalties.php'],
        ['reminders',          __('یادآوری‌ها', 'credit-system'),       'reminders.php'],
        ['settings',           __('تنظیمات', 'credit-system'),          'settings.php'],
    ];

    foreach ($submenus as $menu) {
        add_submenu_page(
            $parent_slug,
            $menu[1],
            $menu[1],
            'manage_options',
            $parent_slug . '-' . $menu[0],
            'cs_render_admin_page'
        );
    }
}

/**
 * رندر صفحه ادمین (همه صفحات)
 */
function cs_render_admin_page(): void
{
    $page      = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'credit-system-dashboard';
    $page_slug = str_replace('credit-system-', '', $page);
    
    if (empty($page_slug) || $page_slug === 'credit-system') {
        $page_slug = 'dashboard';
    }

    $page_map = [
        'dashboard'          => 'dashboard.php',
        'credit-codes'       => 'credit-codes.php',
        'credit-code-create' => 'credit-code-create.php',
        'transactions'       => 'transactions.php',
        'merchants'          => 'merchants.php',
        'installment-plans'  => 'installment-plans.php',
        'kyc-list'           => 'kyc-list.php',
        'penalties'          => 'penalties.php',
        'reminders'          => 'reminders.php',
        'settings'           => 'settings.php',
        'kyc-details'        => 'kyc-details.php',
    ];

    $file_name = $page_map[$page_slug] ?? 'dashboard.php';
    $file_path = CS_UI_DIR . 'admin/pages/' . $file_name;

    // هدر و نوتیس و سایدبار مشترک
    $partials = [
        'notices' => CS_UI_DIR . 'admin/partials/notices.php',
        'sidebar' => CS_UI_DIR . 'admin/partials/sidebar.php',
    ];

    foreach ($partials as $name => $path) {
        if (file_exists($path)) {
            include_once $path;
        } else {
            echo "<div class='notice notice-warning'><p>فایل partial {$name} پیدا نشد: <code>" . esc_html(basename($path)) . "</code></p></div>";
        }
    }

    // صفحه اصلی
    if (file_exists($file_path)) {
        include $file_path;
    } else {
        echo '<div class="wrap">';
        echo '<h1 class="wp-heading-inline">خطا</h1>';
        echo '<div class="notice notice-error"><p>';
        echo 'فایل صفحه یافت نشد: <code>' . esc_html($file_name) . '</code><br>';
        echo 'مسیر چک شده: <code>' . esc_html($file_path) . '</code>';
        echo '</p></div>';
        echo '</div>';
    }
}

// ثبت هوک
add_action('admin_menu', 'cs_register_admin_menu');