<?php
namespace CreditSystem\security;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AuditLogger
 *
 * Log security Events 
 */
class AuditLogger
{
    /**
     * Name Of Log Table
     */
    private static function table(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'credit_audit_logs';
    }

    /**
     * 
     *
     * @param string $action
     * @param array $data
     * @param int|null $userId
     * @param string|null $ip
     * @return void
     */
    public static function log(
        string $action,
        array $data = [],
        ?int $userId = null,
        ?string $ip = null
    ): void {
        global $wpdb;

        if (!$userId) {
            $userId = get_current_user_id() ?: null;
        }

        if (!$ip) {
            $ip = self::getIp();
        }

        $wpdb->insert(
            self::table(),
            [
                'action' => sanitize_key($action),
                'data' => wp_json_encode(self::sanitizeData($data)),
                'user_id' => $userId,
                'ip_address' => $ip,
                'created_at' => current_time('mysql', true),
            ],
            [
                '%s',
                '%s',
                '%d',
                '%s',
                '%s',
            ]
        );
    }

    /**
     * Log security Events (Up Levels)
     *
     * @param string $action
     * @param string $message
     * @param array $context
     * @return void
     */
    public static function security(
        string $action,
        string $message,
        array $context = []
    ): void {
        self::log(
            'security_' . $action,
            [
                'message' => $message,
                'context' => $context,
            ]
        );
    }

    /**
     * insert Log for api
     *
     * @param string $endpoint
     * @param string $method
     * @param array $params
     * @param int|null $userId
     * @return void
     */
    public static function api(
        string $endpoint,
        string $method,
        array $params = [],
        ?int $userId = null
    ): void {
        self::log(
            'api_call',
            [
                'endpoint' => $endpoint,
                'method' => strtoupper($method),
                'params' => $params,
            ],
            $userId
        );
    }

    /**
     * Logging of Changes In Installments Or credits Codes
     *
     * @param string $entity
     * @param int $entityId
     * @param string $from
     * @param string $to
     * @return void
     */
    public static function statusChange(
        string $entity,
        int $entityId,
        string $from,
        string $to
    ): void {
        self::log(
            'status_change',
            [
                'entity' => sanitize_key($entity),
                'entity_id' => $entityId,
                'from' => $from,
                'to' => $to,
            ]
        );
    }

    /**
     * Get Ip Of User
     *
     * @return string|null
     */
    private static function getIp(): ?string
    {
        $keys = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                return sanitize_text_field(trim($ip));
            }
        }

        return null;
    }

    /**
     * پاکسازی داده‌های لاگ
     *
     * @param array $data
     * @return array
     */
    private static function sanitizeData(array $data): array
    {
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = sanitize_text_field($value);
            }
        });

        return $data;
    }
}