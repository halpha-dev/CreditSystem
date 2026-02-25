<?php
namespace CreditSystem\Database\Repositories;

use CreditSystem\Domain\CreditCode;

if (!defined('ABSPATH')) {
    exit;
}

class CreditCodeRepository extends BaseRepository
{
    protected string $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->db->prefix . 'credit_codes';
    }

    /**
     * ganerate 16 digit code
     */
    public function create(array $data): int
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditCodeRepository.php:create','message'=>'Creating credit code','data'=>['user_id'=>$data['user_id']??null,'merchant_id'=>$data['merchant_id']??null],'runId'=>'run1','hypothesisId'=>'A']) . "\n", FILE_APPEND);
        // #endregion
        $result = parent::insert(
            $this->table,
            [
                'code'        => $data['code'],
                'user_id'     => $data['user_id'],
                'merchant_id' => $data['merchant_id'],
                'amount'      => $data['amount'],
                'expires_at'  => $data['expires_at'],
                'status'      => 'unused',
                'created_at'  => current_time('mysql'),
            ],
            ['%s', '%d', '%d', '%f', '%s', '%s', '%s']
        );
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditCodeRepository.php:create','message'=>'Credit code creation result','data'=>['result'=>$result,'error'=>$this->lastError()],'runId'=>'run1','hypothesisId'=>'A']) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }

    /**
     * get valid code for shoping
     */
    public function getValidCode(string $code, int $merchant_id)
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditCodeRepository.php:getValidCode','message'=>'Getting valid code','data'=>['code'=>$code,'merchant_id'=>$merchant_id],'runId'=>'run1','hypothesisId'=>'B']) . "\n", FILE_APPEND);
        // #endregion
        return $this->getRow(
            "SELECT * FROM {$this->table}
             WHERE code = %s
               AND merchant_id = %d
               AND status = 'unused'
               AND expires_at >= %s
             LIMIT 1",
            [$code, $merchant_id, current_time('mysql')]
        );
    }

    /**
     * mark code as used code
     * (atomic – just one time)
     */
    public function markAsUsed(int $code_id): bool
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditCodeRepository.php:markAsUsed','message'=>'Marking code as used','data'=>['code_id'=>$code_id],'runId'=>'run1','hypothesisId'=>'C']) . "\n", FILE_APPEND);
        // #endregion
        $result = $this->query(
            $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'used',
                     used_at = %s
                 WHERE id = %d
                   AND status = 'unused'
                   AND expires_at >= %s",
                current_time('mysql'),
                $code_id,
                current_time('mysql')
            )
        );
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditCodeRepository.php:markAsUsed','message'=>'Mark as used result','data'=>['result'=>$result,'error'=>$this->lastError()],'runId'=>'run1','hypothesisId'=>'C']) . "\n", FILE_APPEND);
        // #endregion
        return $result === 1;
    }

    /**
     * expire old and unused codes
     * (for cron)
     */
    public function expireOldCodes(): int
    {
        return (int) $this->query(
            $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = 'expired'
                 WHERE status = 'unused'
                   AND expires_at < %s",
                current_time('mysql')
            )
        );
    }

    /**
     * check codes not duplicate
     */
    public function exists(string $code): bool
    {
        $count = $this->getVar(
            "SELECT COUNT(1) FROM {$this->table} WHERE code = %s",
            [$code]
        );

        return ((int) $count) > 0;
    }

    /**
     * get active code by user
     */
    public function getActiveByUser(int $user_id): array
    {
        return $this->getResults(
            "SELECT * FROM {$this->table}
             WHERE user_id = %d
               AND status = 'unused'
               AND expires_at >= %s
             ORDER BY created_at DESC",
            [$user_id, current_time('mysql')]
        );
    }

    /**
     * Insert CreditCode domain object (for service compatibility)
     */
    public function insert(CreditCode $code): int
    {
        return $this->create([
            'code' => $code->getCode(),
            'user_id' => $code->getUserId(),
            'merchant_id' => $code->getMerchantId(),
            'amount' => $code->getAmount(),
            'expires_at' => $code->getExpiresAt(),
        ]);
    }

    /**
     * Alias for exists (for service compatibility)
     */
    public function existsByCode(string $code): bool
    {
        return $this->exists($code);
    }

    /**
     * Get valid code for update (for service compatibility)
     */
    public function getValidCodeForUpdate(string $code, int $merchantId): ?CreditCode
    {
        $row = $this->getValidCode($code, $merchantId);
        if (!$row) {
            return null;
        }
        return CreditCode::fromArray((array)$row);
    }

    /**
     * Mark as used with timestamp (overload for service compatibility)
     */
    public function markAsUsed(int $codeId, ?string $usedAt = null): bool
    {
        if ($usedAt === null) {
            $usedAt = current_time('mysql');
        }
        return parent::update(
            $this->table,
            [
                'status' => 'used',
                'used_at' => $usedAt,
            ],
            ['id' => $codeId],
            ['%s', '%s'],
            ['%d']
        );
    }
}
