<?php
/**
 * Credit System - Core
 *
 * هسته اصلی پلاگین - Singleton
 * نسخه نهایی و کامل - ۲۵ فوریه ۲۰۲۶
 */

namespace CreditSystem;

if (!defined('ABSPATH')) {
    exit; // امنیت مستقیم
}

class Core
{
    /** @var self */
    private static $instance = null;

    /** @var array سرویس‌ها (اختیاری برای گسترش بعدی) */
    private $services = [];

    /**
     * Singleton
     */
    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init_constants();
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * لود ثابت‌ها + safeguard
     */
    private function init_constants()
    {
        if (!defined('CS_VERSION')) {
            require_once CS_PLUGIN_DIR . 'config/constants.php';
        }

        // Safeguard مسیرهای مهم
        if (!defined('CS_UI_DIR')) {
            define('CS_UI_DIR', CS_PLUGIN_DIR . 'ui/');
        }
        if (!defined('CS_ASSETS_URL')) {
            define('CS_ASSETS_URL', CS_PLUGIN_URL . 'ui/assets/');
        }
    }

    /**
     * لود فایل‌های اصلی
     */
    private function load_dependencies()
    {
        $inc = CS_PLUGIN_DIR . 'includes/';

        // فایل‌های اصلی سیستم
        require_once $inc . 'admin-menu.php';
        require_once $inc . 'user-router.php';
        require_once $inc . 'merchant-router.php';

        // کرون منیجر (اگر وجود دارد)
        if (file_exists($inc . 'cron/CronManager.php')) {
            require_once $inc . 'cron/CronManager.php';
        }

        // سرویس‌های اصلی (اختیاری - بعداً می‌توانی اضافه کنی)
        // مثال:
        // if (class_exists('\\CreditSystem\\Includes\\Services\\CreditService')) {
        //     $this->services['credit'] = new \CreditSystem\Includes\Services\CreditService();
        // }
    }

    /**
     * ثبت تمام هوک‌ها
     */
    private function init_hooks()
    {
        // منوی ادمین (در admin-menu.php هوک خودش را دارد)
        // روترهای کاربر و مرچنت
        add_action('template_redirect', [$this, 'handle_user_merchant_routing']);

        // ثبت شورتکدها
        add_action('init', [$this, 'register_shortcodes']);

        // استایل و اسکریپت ادمین
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // استایل و اسکریپت فرانت (برای شورتکدها)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_front_assets']);
    }

    /**
     * ثبت شورتکدهای داشبورد فرانت‌اند
     */
    public function register_shortcodes()
    {
        add_shortcode('cs_user_dashboard', [$this, 'shortcode_user_dashboard']);
        add_shortcode('cs_merchant_dashboard', [$this, 'shortcode_merchant_dashboard']);
    }

    /**
     * شورتکد داشبورد کاربر اعتباری (Credit User)
     */
    public function shortcode_user_dashboard()
    {
        if (!is_user_logged_in()) {
            return '<div class="cs-notice cs-error">لطفاً ابتدا وارد حساب کاربری خود شوید.</div>';
        }

        $user = wp_get_current_user();
        if (!in_array(CS_ROLE_CREDIT_USER, (array)$user->roles, true)) {
            return '<div class="cs-notice cs-error">شما مجوز دسترسی به داشبورد کاربر اعتباری را ندارید.</div>';
        }

        ob_start();

        // لود استایل کاربر
        wp_enqueue_style('credit-system-user', CS_ASSETS_URL . 'css/user.css', [], CS_VERSION);
        wp_enqueue_script('credit-system-user', CS_ASSETS_URL . 'js/user.js', ['jquery'], CS_VERSION, true);

        $file = CS_UI_DIR . 'user/pages/dashboard.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="cs-notice cs-error">فایل داشبورد کاربر یافت نشد.</div>';
        }

        return ob_get_clean();
    }

    /**
     * شورتکد داشبورد مرچنت (فروشنده)
     */
    public function shortcode_merchant_dashboard()
    {
        if (!is_user_logged_in()) {
            return '<div class="cs-notice cs-error">لطفاً ابتدا وارد حساب کاربری خود شوید.</div>';
        }

        $user = wp_get_current_user();
        if (!in_array(CS_ROLE_MERCHANT, (array)$user->roles, true)) {
            return '<div class="cs-notice cs-error">شما مجوز دسترسی به داشبورد مرچنت را ندارید.</div>';
        }

        ob_start();

        // لود استایل مرچنت
        wp_enqueue_style('credit-system-merchant', CS_ASSETS_URL . 'css/merchant.css', [], CS_VERSION);
        wp_enqueue_script('credit-system-merchant', CS_ASSETS_URL . 'js/merchant.js', ['jquery'], CS_VERSION, true);

        $file = CS_UI_DIR . 'merchant/pages/dashboard.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="cs-notice cs-error">فایل داشبورد مرچنت هنوز ساخته نشده است.</div>';
        }

        return ob_get_clean();
    }

    /**
     * مدیریت روترهای کاربر و مرچنت (برای permalinkهای خاص)
     */
    public function handle_user_merchant_routing()
    {
        if (is_admin()) {
            return;
        }

        $uri = $_SERVER['REQUEST_URI'] ?? '';

        if (strpos($uri, '/credit-user') !== false || isset($_GET['cs_user'])) {
            include CS_PLUGIN_DIR . 'includes/user-router.php';
            exit;
        }

        if (strpos($uri, '/credit-merchant') !== false || isset($_GET['cs_merchant'])) {
            include CS_PLUGIN_DIR . 'includes/merchant-router.php';
            exit;
        }
    }

    /**
     * استایل و اسکریپت ادمین
     */
    public function enqueue_admin_assets($hook)
    {
        if (strpos($hook, 'credit-system') === false) {
            return;
        }

        wp_enqueue_style('credit-system-admin', CS_ASSETS_URL . 'css/admin.css', [], CS_VERSION);
        wp_enqueue_script('credit-system-admin', CS_ASSETS_URL . 'js/admin.js', ['jquery'], CS_VERSION, true);
    }

    /**
     * استایل و اسکریپت فرانت (برای صفحات شورتکد)
     */
    public function enqueue_front_assets()
    {
        if (is_admin()) {
            return;
        }

        // استایل پایه مشترک
        wp_enqueue_style('credit-system-base', CS_ASSETS_URL . 'css/credit-code.css', [], CS_VERSION);
    }

    /**
     * رندر صفحات ادمین (استفاده شده در admin-menu.php)
     */
    public static function render_admin_page()
    {
        $page      = isset($_GET['page']) ? sanitize_key($_GET['page']) : 'credit-system-dashboard';
        $page_slug = str_replace('credit-system-', '', $page);
        if (empty($page_slug) || $page_slug === 'credit-system') {
            $page_slug = 'dashboard';
        }

        $map = [
            'dashboard'           => 'dashboard.php',
            'kyc-list'            => 'kyc-list.php',
            'kyc-details'         => 'kyc-details.php',
            'installment-plans'   => 'installment-plans.php',
            'merchants'           => 'merchants.php',
            'transactions'        => 'transactions.php',
            'penalties'           => 'penalties.php',
            'reminders'           => 'reminders.php',
            'credit-codes'        => 'credit-codes.php',
            'credit-code-create'  => 'credit-code-create.php',
            'settings'            => 'settings.php',
        ];

        $file_name = $map[$page_slug] ?? 'dashboard.php';
        $file_path = CS_UI_DIR . 'admin/pages/' . $file_name;

        // partials مشترک
        include_once CS_UI_DIR . 'admin/partials/header.php';
        include_once CS_UI_DIR . 'admin/partials/notices.php';
        include_once CS_UI_DIR . 'admin/partials/sidebar.php';

        if (file_exists($file_path)) {
            include $file_path;
        } else {
            echo '<div class="wrap"><h1>صفحه یافت نشد</h1><p>فایل: ' . esc_html($file_name) . '</p></div>';
        }

        include_once CS_UI_DIR . 'admin/partials/footer.php';
    }
}

// سازگاری کامل با Credit-system.php
if (!class_exists('CS_Core')) {
    class_alias('CreditSystem\\Core', 'CS_Core');
}