<?php
namespace CreditSystem\Includes\Database\Repositories;

use CreditSystem\Domain\Installment;

if (!defined('ABSPATH')) {
    exit;
}

class InstallmentRepository extends BaseRepository
{
    protected string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'installments';
    }

    /**
     * create new Installment
     */
    public function create(Installment $installment): int
    {
        global $wpdb;

        $wpdb->insert(
            $this->table,
            [
                'user_id' => $installment->getUserId(),
                'credit_account_id' => $installment->getCreditAccountId(),
                'transaction_id' => $installment->getTransactionId(),
                'due_date' => $installment->getDueDate(),
                'base_amount' => $installment->getBaseAmount(),
                'penalty_amount' => $installment->getPenaltyAmount(),
                'total_amount' => $installment->getTotalAmount(),
                'status' => $installment->getStatus(),
                'paid_at' => $installment->getPaidAt(),
                'created_at' => current_time('mysql'),
            ],
            ['%d','%d','%d','%s','%f','%f','%f','%s','%s','%s']
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * find all installments by userId
     */
    public function findByUserId(int $userId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY due_date ASC", $userId),
            ARRAY_A
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * find installment by credit account ID
     */
    public function findByCreditAccountId(int $creditAccountId): array
    {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE credit_account_id = %d ORDER BY due_date ASC", $creditAccountId),
            ARRAY_A
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * Updatre installment status
     */
    public function update(Installment $installment): bool
    {
        global $wpdb;

        return (bool) $wpdb->update(
            $this->table,
            [
                'base_amount' => $installment->getBaseAmount(),
                'penalty_amount' => $installment->getPenaltyAmount(),
                'total_amount' => $installment->getTotalAmount(),
                'status' => $installment->getStatus(),
                'paid_at' => $installment->getPaidAt(),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $installment->getId()],
            ['%f','%f','%f','%s','%s','%s'],
            ['%d']
        );
    }

}
/**
     * find due or overdue for penallty
     */
    public function findDueOrOverdue(): array
    {
        global $wpdb;

        $today = current_time('Y-m-d');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE status IN ('unpaid','overdue') AND due_date <= %s",
                $today
            ),
            ARRAY_A
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * نگاشت ردیف دیتابیس به موجودیت Installment
     */
    protected function mapRowToEntity(array $row): Installment
    {
        $installment = new Installment();
        $installment->setId((int)$row['id']);
        $installment->setUserId((int)$row['user_id']);
        $installment->setCreditAccountId((int)$row['credit_account_id']);
        $installment->setTransactionId((int)$row['transaction_id']);
        $installment->setDueDate($row['due_date']);
        $installment->setBaseAmount((float)$row['base_amount']);
        $installment->setPenaltyAmount((float)$row['penalty_amount']);
        $installment->setTotalAmount((float)$row['total_amount']);
        $installment->setStatus($row['status']);
        $installment->setPaidAt($row['paid_at']);
        $installment->setCreatedAt($row['created_at']);
        $installment->setUpdatedAt($row['updated_at'] ?? null);

        return $installment;
    }