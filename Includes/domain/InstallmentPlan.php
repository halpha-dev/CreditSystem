<?php
namespace CreditSystem\domain;

if (!defined('ABSPATH')) {
    exit;
}

class InstallmentPlan
{
    protected ?int $id = null;
    protected int $credit_account_id;
    protected float $total_amount;
    protected int $installment_count;
    protected float $installment_amount;
    protected string $status = 'active'; // active | completed | cancelled
    protected string $start_date;
    protected ?string $created_at = null;

    public function __construct(
        int $creditAccountId,
        float $totalAmount,
        int $installmentCount,
        string $startDate
    ) {
        if ($installmentCount <= 0) {
            throw new \InvalidArgumentException('Installment count must be greater than zero');
        }

        $this->credit_account_id  = $creditAccountId;
        $this->total_amount       = round($totalAmount, 2);
        $this->installment_count  = $installmentCount;
        $this->installment_amount = round($totalAmount / $installmentCount, 2);
        $this->start_date         = $startDate;
        $this->created_at         = current_time('mysql');
    }

    /* -------------------- Getters -------------------- */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreditAccountId(): int
    {
        return $this->credit_account_id;
    }

    public function getTotalAmount(): float
    {
        return $this->total_amount;
    }

    public function getInstallmentCount(): int
    {
        return $this->installment_count;
    }

    public function getInstallmentAmount(): float
    {
        return $this->installment_amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStartDate(): string
    {
        return $this->start_date;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    /* -------------------- State changes -------------------- */

    public function markCompleted(): self
    {
        $this->status = 'completed';
        return $this;
    }

    public function cancel(): self
    {
        $this->status = 'cancelled';
        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}