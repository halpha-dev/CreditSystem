<?php

namespace CreditSystem;

/**
 * CS_Core - هسته اصلی پلاگین
 * تمام فایل‌های جدید (admin-menu, user-router, merchant-router) اینجا مدیریت می‌شوند
 */
class Core
{
    /** @var self */
    private static $instance = null;

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
     * ثابت‌ها + فیکس‌های لازم
     */
    private function init_constants()
    {
        // لود constants.php
        if (!defined('CS_VERSION')) {
            require_once CS_PLUGIN_DIR . 'config/constants.php';
        }

        // ثابت گم‌شده که همه routerها از آن استفاده می‌کنند
        if (!defined('CS_UI_PATH')) {
            define('CS_UI_PATH', CS_PLUGIN_DIR . 'ui/');
        }

        // فیکس case-sensitive برای سرورهای لینوکس (مهم!)
        if (!defined('CS_INCLUDES_DIR_FIXED')) {
            define('CS_INCLUDES_DIR_FIXED', CS_PLUGIN_DIR . 'Includes/');
        }
    }

    /**
     * لود فایل‌های جدید + وابستگی‌ها
     */
    private function load_dependencies()
    {
        $inc = CS_INCLUDES_DIR_FIXED;   // مسیر درست با I بزرگ

        // فایل‌های جدید شما
        require_once $inc . 'admin-menu.php';
        require_once $inc . 'user-router.php';
        require_once $inc . 'merchant-router.php';

        // سرویس‌های دیگر (اگر بعداً اضافه کردی)
        // مثال:
        // if (class_exists('\\CreditSystem\\Includes\\Services\\CreditService')) {
        //     new \CreditSystem\Includes\Services\CreditService();
        // }
    }

    /**
     * ثبت تمام هوک‌ها
     */
    private function init_hooks()
    {
        // روترهای کاربر و مرچنت (چون exit() دارند باید در template_redirect باشند)
        add_action('template_redirect', [$this, 'handle_user_merchant_routing']);

        // اگر بعداً شورت‌کد یا AJAX اضافه کردی اینجا می‌آید
    }

    /**
     * مدیریت روترهای کاربر و مرچنت
     * (فایل‌های router فعلی‌ات کد مستقیم دارند و $user تعریف نشده، پس اینجا فراخوانی می‌کنیم)
     */
    public function handle_user_merchant_routing()
    {
        if (is_admin()) {
            return; // ادمین رو اینجا هندل نکن
        }

        // تشخیص صفحه کاربر (می‌تونی بعداً با permalink یا query param تغییر بدی)
        $is_user_page = isset($_GET['cs_user']) || strpos($_SERVER['REQUEST_URI'], 'credit-user') !== false;
        $is_merchant_page = isset($_GET['cs_merchant']) || strpos($_SERVER['REQUEST_URI'], 'credit-merchant') !== false;

        if ($is_user_page) {
            // فایل user-router.php کد مستقیم دارد → اگر $user تعریف نشده باشه ارور می‌ده
            // فعلاً مستقیم include می‌کنیم (بعداً به function تبدیلش کن)
            include CS_INCLUDES_DIR_FIXED . 'user-router.php';
            exit; // router خودش exit داره ولی برای اطمینان
        }

        if ($is_merchant_page) {
            include CS_INCLUDES_DIR_FIXED . 'merchant-router.php';
            exit;
        }
    }
}

// =============================================
// سازگاری کامل با Credit-system.php فعلی تو
// =============================================
if (!class_exists('CS_Core')) {
    class_alias('CreditSystem\\Core', 'CS_Core');
}