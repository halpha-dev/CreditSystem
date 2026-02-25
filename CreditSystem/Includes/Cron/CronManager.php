<?php

namespace CreditSystem\Cron;

use CreditSystem\Cron\ExpireCodes;
use CreditSystem\Cron\ApplyPenalties;
use CreditSystem\Cron\SendReminders;

if (!defined('ABSPATH')) {
    exit;
}

class CronManager
{
    /**
     * Register WordPress cron hooks
     * (Used only as entry points, not relying on wp-cron timing)
     */
    public static function register(): void
    {
        add_action('credit_system_cron_expire_codes', [self::class, 'runExpireCodes']);
        add_action('credit_system_cron_apply_penalties', [self::class, 'runApplyPenalties']);
        add_action('credit_system_cron_send_reminders', [self::class, 'runSendReminders']);
    }

    /**
     * Entry for expiring credit codes
     */
    public static function runExpireCodes(): void
    {
        try {
            (new ExpireCodes())->handle();
        } catch (\Throwable $e) {
            self::logError('ExpireCodes', $e);
        }
    }

    /**
     * Entry for applying installment penalties
     */
    public static function runApplyPenalties(): void
    {
        try {
            (new ApplyPenalties())->handle();
        } catch (\Throwable $e) {
            self::logError('ApplyPenalties', $e);
        }
    }

    /**
     * Entry for sending installment reminders
     */
    public static function runSendReminders(): void
    {
        try {
            (new SendReminders())->handle();
        } catch (\Throwable $e) {
            self::logError('SendReminders', $e);
        }
    }

    /**
     * Simple error logger for cron jobs
     */
    protected static function logError(string $jobName, \Throwable $exception): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(
                sprintf(
                    '[CreditSystem Cron Error] %s | %s | %s',
                    $jobName,
                    $exception->getMessage(),
                    $exception->getTraceAsString()
                )
            );
        }
    }
}

