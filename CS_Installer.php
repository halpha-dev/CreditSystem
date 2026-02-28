<?php
class CS_Installer {

    public static function activate() {
        if (!defined('CS_PLUGIN_DIR')) {
            require_once dirname(dirname(__FILE__)) . '/config/constants.php';
        }

        self::run_migrations();
        self::create_default_options();
        self::add_default_roles_and_caps();

        flush_rewrite_rules();
    }

    private static function run_migrations() {
        // دقت کنید مسیر فایل Migrations.php دقیق باشد
        $migration_file = plugin_dir_path(__FILE__) . 'Includes/Database/Migrations.php';

        if (file_exists($migration_file)) {
            require_once $migration_file;

            // حذف بک‌اسلش اضافی برای اطمینان از پیدا شدن کلاس
            if (class_exists('CreditSystem\Includes\Database\Migrations')) {
                $migrations = new \CreditSystem\Includes\Database\Migrations();
                $migrations->migrate();
                
                update_option('cs_db_version', defined('CS_DB_VERSION') ? CS_DB_VERSION : '1.0.0');
            }
        }
    }
    // سایر متدها (deactivate و ...) را هم به همین شکل بدون : void قرار دهید
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