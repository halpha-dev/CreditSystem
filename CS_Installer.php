<?php
/**
 * CS_Installer
 *
 * مدیریت فعال‌سازی، غیرفعال‌سازی و مهاجرت دیتابیس پلاگین Credit System
 * نسخه سازگار با ساختار فعلی (۹ جدول + Migrations.php)
 */

class CS_Installer {

    /**
     * فعال‌سازی پلاگین
     */
    public static function activate(): void
    {
        // لود constants اگر هنوز لود نشده
        if (!defined('CS_PLUGIN_DIR')) {
            require_once dirname(__FILE__) . '/config/constants.php';
        }

        self::run_migrations();
        self::create_default_options();
        self::add_default_roles_and_caps();

        // فلاش قوانین permalink (برای روترهای کاربر/مرچنت)
        flush_rewrite_rules();
    }

    /**
     * غیرفعال‌سازی پلاگین
     */
    public static function deactivate(): void
    {
        flush_rewrite_rules();

        // پاک کردن تمام کرون‌جاب‌های سیستم
        $scheduled_hooks = [
            'cs_apply_penalties',
            'cs_expire_codes',
            'cs_send_reminders',
            'cs_kyc_cleanup'
        ];

        foreach ($scheduled_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }

    /**
     * اجرای مهاجرت کامل دیتابیس (۹ جدول)
     */
    private static function run_migrations(): void
    {
        $migration_file = dirname(__FILE__) . '/Includes/Database/Migrations.php';

        if (file_exists($migration_file)) {
            require_once $migration_file;

            if (class_exists('\\CreditSystem\\Includes\\Database\\Migrations')) {
                $migrations = new \CreditSystem\Includes\Database\Migrations();
                $migrations->migrate();

                // ذخیره نسخه دیتابیس
                if (defined('CS_DB_VERSION')) {
                    update_option('cs_db_version', CS_DB_VERSION, false);
                }
            }
        }
    }

    /**
     * ایجاد تنظیمات پیش‌فرض
     */
    private static function create_default_options(): void
    {
        if (!get_option('cs_settings')) {
            update_option('cs_settings', [
                'enable_kyc'              => true,
                'max_credit_per_user'     => 50000000,
                'default_penalty_rate'    => 0.05,     // ۵٪ روزانه
                'reminder_days_before'    => 3,
                'code_expiry_minutes'     => 15,
                'max_active_codes'        => 3,
            ]);
        }

        // گزینه نسخه دیتابیس
        if (!get_option('cs_db_version')) {
            update_option('cs_db_version', CS_DB_VERSION ?? '1.0.0', false);
        }
    }

    /**
     * افزودن نقش‌ها و قابلیت‌های پیش‌فرض
     */
       /**
     * افزودن نقش‌ها و قابلیت‌های پیش‌فرض
     */
    private static function add_default_roles_and_caps(): void
    {
        // ────── نقش Merchant (فروشنده / مرچنت) ──────
        if (!get_role('cs_merchant')) {
            add_role(
                'cs_merchant',
                'مرچنت سیستم اعتبار',
                [
                    'read'                        => true,
                    'manage_credit_codes'         => true,   // مدیریت کدهای اعتبار
                    'view_merchant_transactions'  => true,
                    'manage_own_installments'     => true,
                ]
            );
        }

        // ────── نقش Credit User (کاربر اعتباری معمولی) ──────
        if (!get_role('cs_credit_user')) {
            add_role(
                'cs_credit_user',
                'کاربر اعتباری',
                [
                    'read'                        => true,
                    'view_own_credit'             => true,   // دیدن اعتبار خود
                    'use_credit_codes'            => true,   // استفاده از کدهای شارژ
                    'view_own_transactions'       => true,
                    'view_own_installments'       => true,
                    'submit_kyc'                  => true,   // ارسال مدارک KYC
                ]
            );
        }

        // ────── اضافه کردن قابلیت‌ها به ادمین ──────
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_caps = [
                'manage_credit_system',
                'manage_cs_merchants',
                'manage_cs_kyc',
                'manage_cs_installments',
                'manage_cs_transactions',
                'manage_cs_codes'
            ];
            foreach ($admin_caps as $cap) {
                if (!$admin_role->has_cap($cap)) {
                    $admin_role->add_cap($cap);
                }
            }
        }
    }
}