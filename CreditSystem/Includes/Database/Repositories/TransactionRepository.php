<?php
namespace CreditSystem\Database\Repositories;

use CreditSystem\Domain\Transaction;

if (!defined('ABSPATH')) {
    exit;
}

class TransactionRepository extends BaseRepository
{
    protected string $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->db->prefix . 'transactions';
    }

    /**
     * Creat new transaction
     */
    public function create(Transaction $transaction): int
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'TransactionRepository.php:create','message'=>'Creating transaction','data'=>['user_id'=>$transaction->getUserId(),'amount'=>$transaction->getAmount()],'runId'=>'run1','hypothesisId'=>'J']) . "\n", FILE_APPEND);
        // #endregion
        $result = $this->insert(
            $this->table,
            [
                'user_id' => $transaction->getUserId(),
                'credit_account_id' => $transaction->getCreditAccountId(),
                'merchant_id' => $transaction->getMerchantId(),
                'amount' => $transaction->getAmount(),
                'type' => $transaction->getType(), // purchase | installment_payment
                'status' => $transaction->getStatus(), // success | failed
                'created_at' => current_time('mysql'),
            ],
            ['%d','%d','%d','%f','%s','%s','%s']
        );
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'TransactionRepository.php:create','message'=>'Transaction creation result','data'=>['result'=>$result,'error'=>$this->lastError()],'runId'=>'run1','hypothesisId'=>'J']) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }

    /**
     * Find transaction by User Id
     */
    public function findByUserId(int $userId): array
    {
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY created_at DESC",
            [$userId]
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * find transaction by merchant Id
     */
    public function findByMerchantId(int $merchantId): array
    {
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE merchant_id = %d ORDER BY created_at DESC",
            [$merchantId]
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }
    /**
     * update transaction status
     */
    public function update(Transaction $transaction): bool
    {
        return parent::update(
            $this->table,
            [
                'amount' => $transaction->getAmount(),
                'status' => $transaction->getStatus(),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $transaction->getId()],
            ['%f','%s','%s'],
            ['%d']
        );
    }

    /**
     * Insert transaction from array (for service compatibility)
     */
    public function insert(array $data): int
    {
        $transaction = new Transaction();
        $transaction->setUserId($data['user_id'] ?? 0);
        $transaction->setCreditAccountId($data['credit_account_id'] ?? null);
        $transaction->setMerchantId($data['merchant_id'] ?? null);
        $transaction->setAmount($data['amount'] ?? 0.0);
        $transaction->setType($data['type'] ?? 'purchase');
        $transaction->setStatus($data['status'] ?? 'success');
        $transaction->setCreatedAt($data['created_at'] ?? current_time('mysql'));

        return $this->create($transaction);
    }

    /**
     * نگاشت ردیف دیتابیس به موجودیت Transaction
     */
    protected function mapRowToEntity(array|object $row): Transaction
    {
        $row = (array) $row;
        $transaction = new Transaction();
        $transaction->setId((int)$row['id']);
        $transaction->setUserId((int)$row['user_id']);
        $transaction->setCreditAccountId((int)$row['credit_account_id']);
        $transaction->setMerchantId((int)$row['merchant_id']);
        $transaction->setAmount((float)$row['amount']);
        $transaction->setType($row['type']);
        $transaction->setStatus($row['status']);
        $transaction->setCreatedAt($row['created_at']);
        $transaction->setUpdatedAt($row['updated_at'] ?? null);

        return $transaction;
    }
}