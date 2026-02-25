<?php
/**
 * Credit System - Constants
 *
 * تمام ثابت‌های جهانی پلاگین
 * نسخه نهایی و تمیز - ۲۴ فوریه ۲۰۲۶
 */

if (!defined('ABSPATH')) {
    exit; // امنیت مستقیم
}

/* =============================================
   نسخه و دیتابیس
   ============================================= */
define('CS_VERSION', '1.0.0');
define('CS_DB_VERSION', '1.0.0');

/* =============================================
   مسیرها و URLها (کاملاً case-sensitive-safe)
   ============================================= */
define('CS_PLUGIN_FILE', __FILE__);                    // مهم: از __FILE__ استفاده نمی‌کنیم، بلکه در Credit-system.php تعریف می‌شود
// نکته: CS_PLUGIN_DIR و CS_PLUGIN_URL در Credit-system.php تعریف می‌شوند

define('CS_INCLUDES_DIR', CS_PLUGIN_DIR . 'Includes/');   // I بزرگ - درست
define('CS_CONFIG_DIR',   CS_PLUGIN_DIR . 'config/');
define('CS_UI_DIR',       CS_PLUGIN_DIR . 'ui/');         // مسیر اصلی UI (ادمین + کاربر)
define('CS_UI_URL',       CS_PLUGIN_URL . 'ui/');

define('CS_ASSETS_DIR',   CS_UI_DIR . 'assets/');
define('CS_ASSETS_URL',   CS_UI_URL . 'assets/');

define('CS_ADMIN_DIR',    CS_UI_DIR . 'admin/');
define('CS_USER_DIR',     CS_UI_DIR . 'user/');

define('CS_LANGUAGES_DIR', CS_PLUGIN_DIR . 'languages/');

/* =============================================
   جدول‌های دیتابیس (همه ۹ جدول فعلی)
   ============================================= */
define('CS_DB_PREFIX', $GLOBALS['wpdb']->prefix . 'cs_');

define('CS_TABLE_CREDITS',          CS_DB_PREFIX . 'credits');
define('CS_TABLE_CODES',            CS_DB_PREFIX . 'codes');
define('CS_TABLE_TRANSACTIONS',     CS_DB_PREFIX . 'transactions');
define('CS_TABLE_MERCHANTS',        CS_DB_PREFIX . 'merchants');
define('CS_TABLE_INSTALLMENTS',     CS_DB_PREFIX . 'installments');      // اضافه شد
define('CS_TABLE_INSTALL_PLANS',    CS_DB_PREFIX . 'install_plans');     // اضافه شد
define('CS_TABLE_KYC_REQUESTS',     CS_DB_PREFIX . 'kyc_requests');      // اضافه شد
define('CS_TABLE_PENALTIES',        CS_DB_PREFIX . 'penalties');         // اضافه شد
define('CS_TABLE_REMINDERS',        CS_DB_PREFIX . 'reminders');         // اضافه شد

/* =============================================
   قوانین اعتبار و کد
   ============================================= */
define('CS_CREDIT_CURRENCY',          'IRR');

define('CS_MIN_CREDIT_AMOUNT',        100000);      // حداقل ۱۰۰ هزار تومان
define('CS_MAX_CREDIT_AMOUNT',        1000000000); // حداکثر یک میلیارد

define('CS_CODE_LENGTH',              16);
define('CS_CODE_EXPIRY_MINUTES',      15);
define('CS_MAX_ACTIVE_CODES_PER_USER', 3);

/* =============================================
   امنیت
   ============================================= */
define('CS_CODE_HASH_ALGO',     'sha256');
define('CS_NONCE_ACTION',       'cs_secure_action');
define('CS_RATE_LIMIT_WINDOW',  60);   // ثانیه
define('CS_RATE_LIMIT_MAX',     30);   // درخواست در هر پنجره

/* =============================================
   وضعیت‌ها (Enums)
   ============================================= */
define('CS_CODE_STATUS_UNUSED',    'unused');
define('CS_CODE_STATUS_USED',      'used');
define('CS_CODE_STATUS_EXPIRED',   'expired');
define('CS_CODE_STATUS_CANCELLED', 'cancelled');

define('CS_TX_STATUS_PENDING',    'pending');
define('CS_TX_STATUS_COMPLETED',  'completed');
define('CS_TX_STATUS_FAILED',     'failed');

define('CS_KYC_STATUS_PENDING',   'pending');
define('CS_KYC_STATUS_APPROVED',  'approved');
define('CS_KYC_STATUS_REJECTED',  'rejected');

/* =============================================
   نقش‌ها و قابلیت‌ها
   ============================================= */
define('CS_ROLE_MERCHANT',     'cs_merchant');
define('CS_ROLE_CREDIT_USER',  'cs_credit_user');   // نقش جدید کاربر اعتباری

/* =============================================
   لاگ و دیباگ
   ============================================= */
define('CS_LOG_ENABLED', true);
define('CS_LOG_LEVEL',   'warning'); // debug | info | warning | error

/* =============================================
   تنظیمات پیش‌فرض (قابل تغییر از تنظیمات ادمین)
   ============================================= */
define('CS_DEFAULT_PENALTY_RATE', 0.05);     // ۵٪ روزانه
define('CS_REMINDER_DAYS_BEFORE', 3);