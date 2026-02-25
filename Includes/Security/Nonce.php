<?php
namespace CreditSystem\security;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Nonce
 *
 * مدیریت nonce های امنیتی برای api، فرم‌ها و درخواست‌های حساس
 */
class Nonce
{
    /**
     * پیشوند nonce ها برای جلوگیری از تداخل
     */
    private const PREFIX = 'credit_system_';

    /**
     * مدت اعتبار nonce (ثانیه)
     * پیش‌فرض: 12 ساعت
     */
    private const TTL = 43200;

    /**
     * تولید nonce
     *
     * @param string $action
     * @return string
     */
    public static function create(string $action): string
    {
        $action = self::sanitizeAction($action);

        return wp_create_nonce(self::PREFIX . $action);
    }

    /**
     * بررسی صحت nonce
     *
     * @param string|null $nonce
     * @param string $action
     * @return bool
     */
    public static function verify(?string $nonce, string $action): bool
    {
        if (empty($nonce)) {
            return false;
        }

        $action = self::sanitizeAction($action);

        return wp_verify_nonce($nonce, self::PREFIX . $action) === 1;
    }

    /**
     * بررسی nonce از request (GET, POST, HEADER)
     *
     * @param string $action
     * @param string $key
     * @return bool
     */
    public static function verifyFromRequest(string $action, string $key = '_nonce'): bool
    {
        $nonce = null;

        // POST
        if (isset($_POST[$key])) {
            $nonce = sanitize_text_field($_POST[$key]);
        }

        // GET
        if (!$nonce && isset($_GET[$key])) {
            $nonce = sanitize_text_field($_GET[$key]);
        }

        // Header
        if (
            !$nonce &&
            isset($_SERVER['HTTP_X_WP_NONCE'])
        ) {
            $nonce = sanitize_text_field($_SERVER['HTTP_X_WP_NONCE']);
        }

        return self::verify($nonce, $action);
    }

    /**
     * اجبار به اعتبارسنجی nonce
     * در صورت نامعتبر بودن، پاسخ خطای JSON می‌دهد
     *
     * @param string $action
     * @param string $key
     * @return void
     */
    public static function require(string $action, string $key = '_nonce'): void
    {
        if (!self::verifyFromRequest($action, $key)) {
            self::fail();
        }
    }

    /**
     * پاسخ خطای امنیتی استاندارد
     *
     * @return void
     */
    private static function fail(): void
    {
        wp_send_json([
            'success' => false,
            'error' => 'invalid_nonce',
            'message' => 'درخواست نامعتبر یا منقضی شده است.'
        ], 403);

        exit;
    }

    /**
     * تمیزسازی نام اکشن
     *
     * @param string $action
     * @return string
     */
    private static function sanitizeAction(string $action): string
    {
        return sanitize_key($action);
    }
}