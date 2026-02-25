<?php

namespace CreditSystem\Includes\Database\Repositories;

use CreditSystem\Includes\Domain\KycRequest;

class KycRepository extends BaseRepository
{
    protected string $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->db->prefix . 'cs_kyc_requests';
    }

    /**
     * ذخیره درخواست KYC
     */
    public function create(KycRequest $kyc): int
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'KycRepository.php:create','message'=>'Creating KYC request','data'=>['user_id'=>$kyc->getUserId()],'runId'=>'run1','hypothesisId'=>'K']) . "\n", FILE_APPEND);
        // #endregion
        $result = $this->insert(
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
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'KycRepository.php:create','message'=>'KYC creation result','data'=>['result'=>$result,'error'=>$this->lastError()],'runId'=>'run1','hypothesisId'=>'K']) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }

    /**
     * گرفتن درخواست KYC بر اساس user_id
     */
    public function getByUserId(int $userId): ?KycRequest
    {
        $row = $this->getRow(
            "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
            [$userId]
        );

        return $row ? $this->mapRowToDomain((array)$row) : null;
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
        $formats = ['%s'];

        if ($status === 'approved') {
            $data['approved_at'] = current_time('mysql');
            $data['rejection_reason'] = null;
            $formats = ['%s', '%s', '%s'];
        }

        if ($status === 'rejected') {
            $data['rejection_reason'] = $rejectionReason;
            $formats = ['%s', '%s'];
        }

        return parent::update(
            $this->table,
            $data,
            ['id' => $kycId],
            $formats,
            ['%d']
        );
    }

    /**
     * لیست درخواست‌های در انتظار بررسی (برای ادمین)
     */
    public function getPendingList(int $limit = 20, int $offset = 0): array
    {
        $results = $this->getResults(
            "SELECT * FROM {$this->table}
             WHERE status = 'pending'
             ORDER BY submitted_at ASC
             LIMIT %d OFFSET %d",
            [$limit, $offset]
        );

        return array_map([$this, 'mapRowToDomain'], $results);
    }

    /**
     * تبدیل ردیف دیتابیس به Domain Object
     */
    private function mapRowToDomain(array|object $row): KycRequest
    {
        $row = (array) $row;
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