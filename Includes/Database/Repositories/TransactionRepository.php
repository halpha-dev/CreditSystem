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
        global $wpdb;
        $this->table = $wpdb->prefix . 'transactions';
    }

    /**
     * Creat new transaction
     */
    public function create(Transaction $transaction): int
    {
        global $wpdb;

        $wpdb->insert(
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

        return (int) $wpdb->insert_id;
    }

    /**
     * Find transaction by User Id
     */
    public function findByUserId(int $userId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY created_at DESC",
                $userId
            ),
            ARRAY_A
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * find transaction by merchant Id
     */
    public function findByMerchantId(int $merchantId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE merchant_id = %d ORDER BY created_at DESC",
                $merchantId
            ),
            ARRAY_A
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }
    /**
     * update transaction status
     */
    public function update(Transaction $transaction): bool
    {
        global $wpdb;

        return (bool) $wpdb->update(
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
     * نگاشت ردیف دیتابیس به موجودیت Transaction
     */
    protected function mapRowToEntity(array $row): Transaction
    {
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