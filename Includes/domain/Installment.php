<?php
namespace CreditSystem\domain;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Installment
 *
 * مدل دامنه قسط اعتباری
 */
class Installment
{
    private int $id;
    private int $userId;
    private int $creditAccountId;
    private int $transactionId;

    private float $baseAmount;
    private float $penaltyAmount;
    private float $totalAmount;

    private string $dueDate;
    private string $status; // unpaid | paid | overdue

    private ?string $paidAt;
    private string $createdAt;

    public function __construct(
        int $id,
        int $userId,
        int $creditAccountId,
        int $transactionId,
        float $baseAmount,
        float $penaltyAmount,
        string $dueDate,
        string $status,
        ?string $paidAt,
        string $createdAt
    ) {
        $this->id              = $id;
        $this->userId          = $userId;
        $this->creditAccountId = $creditAccountId;
        $this->transactionId   = $transactionId;

        $this->baseAmount     = $baseAmount;
        $this->penaltyAmount  = $penaltyAmount;
        $this->totalAmount    = $baseAmount + $penaltyAmount;

        $this->dueDate   = $dueDate;
        $this->status    = $status;
        $this->paidAt    = $paidAt;
        $this->createdAt = $createdAt;
    }

    /* ========================
     * Getters
     * ====================== */

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreditAccountId(): int
    {
        return $this->creditAccountId;
    }

    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    public function getBaseAmount(): float
    {
        return $this->baseAmount;
    }

    public function getPenaltyAmount(): float
    {
        return $this->penaltyAmount;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPaidAt(): ?string
    {
        return $this->paidAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
    /* ========================
     * Domain Logic
     * ====================== */

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        return strtotime($this->dueDate) < strtotime(date('Y-m-d'));
    }

    public function canBePaid(): bool
    {
        if ($this->isPaid()) {
            return false;
        }

        return strtotime(date('Y-m-d')) >= strtotime($this->dueDate);
    }

    /**
     * Alias for canBePaid (for service compatibility)
     */
    public function isPayable(): bool
    {
        return $this->canBePaid();
    }

    public function markAsPaid(): void
    {
        if ($this->isPaid()) {
            throw new \RuntimeException('قسط پرداخت شده است.');
        }

        if (!$this->canBePaid()) {
            throw new \RuntimeException('قسط شما هنوز پرداخت نشده است.');
        }

        $this->status = 'paid';
        $this->paidAt = current_time('mysql');
    }

    public function markAsOverdue(): void
    {
        if ($this->isPaid()) {
            return;
        }

        $this->status = 'overdue';
    }

    public function applyDailyPenalty(float $dailyRate): void
    {
        if ($this->isPaid()) {
            return;
        }

        if (!$this->isOverdue()) {
            return;
        }

        $dailyPenalty = $this->baseAmount * $dailyRate;

        $this->penaltyAmount += $dailyPenalty;
        $this->totalAmount   = $this->baseAmount + $this->penaltyAmount;
    }

    /* ========================
     * Factory
     * ====================== */

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            (int) $data['user_id'],
            (int) $data['credit_account_id'],
            (int) $data['transaction_id'],
            (float) $data['base_amount'],
            (float) $data['penalty_amount'],
            (string) $data['due_date'],
            (string) $data['status'],
            $data['paid_at'] ?? null,
            (string) $data['created_at']
        );
    }

    public function toArray(): array
    {
        return [
            'id'                => $this->id,
            'user_id'           => $this->userId,
            'credit_account_id' => $this->creditAccountId,
            'transaction_id'    => $this->transactionId,
            'base_amount'       => $this->baseAmount,
            'penalty_amount'    => $this->penaltyAmount,
            'total_amount'      => $this->totalAmount,
            'due_date'          => $this->dueDate,
            'status'            => $this->status,
            'paid_at'           => $this->paidAt,
            'created_at'        => $this->createdAt,
        ];
    }

    /* ========================
     * Setters (for repository mapping)
     * ====================== */

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function setCreditAccountId(int $creditAccountId): void
    {
        $this->creditAccountId = $creditAccountId;
    }

    public function setTransactionId(int $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function setBaseAmount(float $baseAmount): void
    {
        $this->baseAmount = $baseAmount;
        $this->totalAmount = $this->baseAmount + $this->penaltyAmount;
    }

    public function setPenaltyAmount(float $penaltyAmount): void
    {
        $this->penaltyAmount = $penaltyAmount;
        $this->totalAmount = $this->baseAmount + $this->penaltyAmount;
    }

    public function setDueDate(string $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setPaidAt(?string $paidAt): void
    {
        $this->paidAt = $paidAt;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(?string $updatedAt): void
    {
        // Not stored in domain, but needed for repository compatibility
    }
}