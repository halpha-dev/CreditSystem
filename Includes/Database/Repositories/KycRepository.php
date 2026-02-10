<?php

namespace CreditSystem\Includes\Database\Repositories;

use CreditSystem\Includes\Domain\KycRequest;
use wpdb;

class KycRepository
{
    private wpdb $db;
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
        $this->table = $wpdb->prefix . 'cs_kyc_requests';
    }

    /**
     * ذخیره درخواست KYC
     */
    public function create(KycRequest $kyc): int
    {
        $this->db->insert(
            $this->table,
            [
                'user_id' => $kyc->getUserId(),
                'documents' => wp_json_encode($kyc->getDocuments()),
                'status' => $kyc->getStatus(),
                'installment_plan_id' => $kyc->getInstallmentPlanId(),
                'preferred_installments' => $kyc->getPreferredInstallments(),
                'merchant_id' => $kyc->getMerchantId(),
                'submitted_at' => current_time('mysql'),
                'approved_at' => null,
                'rejection_reason' => null,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%d',
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );

        return (int) $this->db->insert_id;
    }

    /**
     * گرفتن درخواست KYC بر اساس user_id
     */
    public function getByUserId(int $userId): ?KycRequest
    {
        $row = $this->db->get_row(
            $this->db->prepare(
                "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
                $userId
            ),
            ARRAY_A
        );

        return $row ? $this->mapRowToDomain($row) : null;
    }

    /**
     * تغییر وضعیت KYC (approve / reject)
     */
    public function updateStatus(
        int $kycId,
        string $status,
        ?string $rejectionReason = null
    ): bool {
        $data = [
            'status' => $status,
        ];

        if ($status === 'approved') {
            $data['approved_at'] = current_time('mysql');
            $data['rejection_reason'] = null;
        }

        if ($status === 'rejected') {
            $data['rejection_reason'] = $rejectionReason;
        }

        return (bool) $this->db->update(
            $this->table,
            $data,
            ['id' => $kycId],
            null,
            ['%d']
        );
    }

    /**
     * لیست درخواست‌های در انتظار بررسی (برای ادمین)
     */
    public function getPendingList(int $limit = 20, int $offset = 0): array
    {
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->table}
                 WHERE status = 'pending'
                 ORDER BY submitted_at ASC
                 LIMIT %d OFFSET %d",
                $limit,
                $offset
            ),
            ARRAY_A
        );

        return array_map([$this, 'mapRowToDomain'], $results);
    }

    /**
     * تبدیل ردیف دیتابیس به Domain Object
     */
    private function mapRowToDomain(array $row): KycRequest
    {
        $kyc = new KycRequest(
            userId: (int) $row['user_id'],
            documents: json_decode($row['documents'], true) ?? [],
            installmentPlanId: $row['installment_plan_id'] ? (int) $row['installment_plan_id'] : null,
            preferredInstallments: $row['preferred_installments'] ? (int) $row['preferred_installments'] : null,
            merchantId: $row['merchant_id'] ? (int) $row['merchant_id'] : null
        );

        // مقداردهی دستی فیلدهایی که constructor نباید دست بزنه
        $reflection = new \ReflectionClass($kyc);

        foreach ([
            'id' => (int) $row['id'],
            'status' => $row['status'],
            'submittedAt' => new \DateTime($row['submitted_at']),
            'approvedAt' => $row['approved_at'] ? new \DateTime($row['approved_at']) : null,
            'rejectionReason' => $row['rejection_reason'],
        ] as $property => $value) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            $prop->setValue($kyc, $value);
        }

        return $kyc;
    }
}