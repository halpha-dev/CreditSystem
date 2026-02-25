<?php

namespace App\cron;

use App\Database\Repositories\CreditCodeRepository;

class ExpireCodes
{
    protected CreditCodeRepository $codeRepository;

    public function __construct()
    {
        $this->codeRepository = new CreditCodeRepository();
    }

    /**
     * اRun cron for expire codes
     */
    public function run(): void
    {
        $now = current_time('mysql');

        $expiredCodes = $this->codeRepository->getExpiredActiveCodes($now);

        if (empty($expiredCodes)) {
            return;
        }

        foreach ($expiredCodes as $code) {
            $this->codeRepository->markAsExpired((int) $code->id);
        }
    }

    /**
     * Rigester cron in WP
     */
    public static function schedule(): void
    {
        if (!wp_next_scheduled('credit_system_expire_codes')) {
            wp_schedule_event(time(), 'minute', 'credit_system_expire_codes');
        }
    }

    /**
     * Link hook
     */
    public static function hook(): void
    {
        add_action('credit_system_expire_codes', function () {
            (new self())->run();
        });
    }

    /**
     * Delete cron After expire code 
     */
    public static function unschedule(): void
    {
        $timestamp = wp_next_scheduled('credit_system_expire_codes');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'credit_system_expire_codes');
        }
    }
}