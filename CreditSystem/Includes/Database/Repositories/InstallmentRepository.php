<?php
namespace CreditSystem\Database\Repositories;

use CreditSystem\Domain\Installment;

if (!defined('ABSPATH')) {
    exit;
}

class InstallmentRepository extends BaseRepository
{
    protected string $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->db->prefix . 'installments';
    }

    /**
     * create new Installment
     */
    public function create(Installment $installment): int
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'InstallmentRepository.php:create','message'=>'Creating installment','data'=>['user_id'=>$installment->getUserId()],'runId'=>'run1','hypothesisId'=>'I']) . "\n", FILE_APPEND);
        // #endregion
        $result = $this->insert(
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
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'InstallmentRepository.php:create','message'=>'Installment creation result','data'=>['result'=>$result,'error'=>$this->lastError()],'runId'=>'run1','hypothesisId'=>'I']) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }

    /**
     * find all installments by userId
     */
    public function findByUserId(int $userId): array
    {
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY due_date ASC",
            [$userId]
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * Alias for findByUserId (for service compatibility)
     */
    public function getByUserId(int $userId): array
    {
        return $this->findByUserId($userId);
    }

    /**
     * find installment by credit account ID
     */
    public function findByCreditAccountId(int $creditAccountId): array
    {
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE credit_account_id = %d ORDER BY due_date ASC",
            [$creditAccountId]
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * Updatre installment status
     */
    public function update(Installment $installment): bool
    {
        return parent::update(
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

    /**
     * find due or overdue for penallty
     */
    public function findDueOrOverdue(): array
    {
        $today = current_time('Y-m-d');

        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE status IN ('unpaid','overdue') AND due_date <= %s",
            [$today]
        );

        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * Get overdue installments (for service compatibility)
     */
    public function getOverdue(): array
    {
        $today = current_time('Y-m-d');
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE status = 'overdue' AND due_date < %s",
            [$today]
        );
        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * Get installments due in N days (for service compatibility)
     */
    public function getDueInDays(int $daysBefore): array
    {
        $targetDate = date('Y-m-d', strtotime("+{$daysBefore} days"));
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} 
             WHERE status = 'unpaid' 
             AND due_date = %s
             ORDER BY due_date ASC",
            [$targetDate]
        );
        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    /**
     * Get installment for update (for service compatibility)
     */
    public function getForUpdate(int $installmentId, int $userId): ?Installment
    {
        $row = $this->getRow(
            "SELECT * FROM {$this->table} WHERE id = %d AND user_id = %d LIMIT 1",
            [$installmentId, $userId]
        );
        return $row ? $this->mapRowToEntity((array)$row) : null;
    }

    /**
     * Mark installment as paid (for service compatibility)
     */
    public function markAsPaid(int $installmentId, int $transactionId): bool
    {
        $row = $this->getRow(
            "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
            [$installmentId]
        );
        if (!$row) {
            return false;
        }
        $installment = $this->mapRowToEntity((array)$row);
        $installment->markAsPaid();
        $installment->setTransactionId($transactionId);
        return $this->update($installment);
    }

    /**
     * Add penalty to installment (for service compatibility)
     */
    public function addPenalty(int $installmentId, float $penaltyAmount): bool
    {
        $row = $this->getRow(
            "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
            [$installmentId]
        );
        if (!$row) {
            return false;
        }
        $installment = $this->mapRowToEntity((array)$row);
        // Add penalty directly (penaltyAmount is already calculated)
        $installment->setPenaltyAmount($installment->getPenaltyAmount() + $penaltyAmount);
        return $this->update($installment);
    }

    /**
     * نگاشت ردیف دیتابیس به موجودیت Installment
     */
    protected function mapRowToEntity(array|object $row): Installment
    {
        $row = (array) $row;
        // Use Installment::fromArray factory method if available, otherwise use setters
        if (method_exists(Installment::class, 'fromArray')) {
            return Installment::fromArray($row);
        }
        
        // Fallback: create using constructor and setters
        $installment = new Installment(
            (int)$row['id'],
            (int)$row['user_id'],
            (int)$row['credit_account_id'],
            (int)$row['transaction_id'],
            (float)$row['base_amount'],
            (float)$row['penalty_amount'],
            $row['due_date'],
            $row['status'],
            $row['paid_at'] ?? null,
            $row['created_at']
        );

        return $installment;
    }