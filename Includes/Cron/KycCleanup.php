<?php

namespace CreditSystem\Includes\cron;

use CreditSystem\Includes\Database\Repositories\KycRepository;
use CreditSystem\Includes\security\AuditLogger;

class KycCleanup
{
    private KycRepository $kycRepository;
    private AuditLogger $auditLogger;

    /**
     * تعداد روزی که بعدش KYC pending پاک میشه
     */
    private int $expirationDays = 30;

    public function __construct()
    {
        $this->kycRepository = new KycRepository();
        $this->auditLogger = new AuditLogger();
    }

    /**
     * اجرای کران
     */
    public function run(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'cs_kyc_requests';

        $thresholdDate = date(
            'Y-m-d H:i:s',
            strtotime("-{$this->expirationDays} days", current_time('timestamp'))
        );

        // فقط pending های قدیمی
        $expiredRequests = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, user_id
                 FROM {$table}
                 WHERE status = 'pending'
                 AND submitted_at < %s",
                $thresholdDate
            ),
            ARRAY_A
        );

        if (empty($expiredRequests)) {
            return;
        }

        foreach ($expiredRequests as $row) {
            $wpdb->delete(
                $table,
                ['id' => (int) $row['id']],
                ['%d']
            );

            $this->auditLogger->log(
                'kyc_expired_deleted',
                0, // system
                [
                    'kyc_id' => (int) $row['id'],
                    'user_id' => (int) $row['user_id'],
                ]
            );
        }
    }
}