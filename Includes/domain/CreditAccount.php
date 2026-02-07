<?php
namespace CreditSystem\Domain;

if (!defined('ABSPATH')) {
    exit;
}

class CreditAccount
{
    protected int $id;
    protected int $user_id;

    protected float $credit_limit;
    protected float $used_amount;
    protected float $available_amount;

    protected int $installment_count;
    protected float $monthly_installment_amount;

    protected string $status; 
    // active | overdue | settled | suspended

    protected \DateTime $created_at;
    protected ?\DateTime $updated_at = null;

    public function __construct(
        int $user_id,
        float $credit_limit,
        int $installment_count
    ) {
        if ($installment_count <= 0) {
            throw new \InvalidArgumentException('Installment count must be greater than zero.');
        }

        $this->user_id = $user_id;
        $this->credit_limit = $credit_limit;
        $this->used_amount = 0;
        $this->available_amount = $credit_limit;

        $this->installment_count = $installment_count;
        $this->monthly_installment_amount = round(
            $credit_limit / $installment_count,
            2
        );

        $this->status = 'active';
        $this->created_at = new \DateTime();
    }

    /* =======================
     * Identity
     * ======================= */

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    /* =======================
     * Credit amounts
     * ======================= */

    public function getCreditLimit(): float
    {
        return $this->credit_limit;
    }

    public function getUsedAmount(): float
    {
        return $this->used_amount;
    }

    public function getAvailableAmount(): float
    {
        return $this->available_amount;
    }

    public function canSpend(float $amount): bool
    {
        return $amount > 0 && $amount <= $this->available_amount && $this->status === 'active';
    }

    public function spend(float $amount): void
    {
        if (!$this->canSpend($amount)) {
            throw new \RuntimeException('اعتبار کافی نمی باشد و یا حساب شما غیرفعال شده است.');
        }

        $this->used_amount += $amount;
        $this->available_amount -= $amount;
        $this->touch();
    }

    public function refund(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('اعتباری برای حساب شما یافت نشد.');
        }

        $this->used_amount = max(0, $this->used_amount - $amount);
        $this->available_amount = min(
            $this->credit_limit,
            $this->available_amount + $amount
        );

        $this->touch();
    }
    /* =======================
     * Installments
     * ======================= */

    public function getInstallmentCount(): int
    {
        return $this->installment_count;
    }

    public function getMonthlyInstallmentAmount(): float
    {
        return $this->monthly_installment_amount;
    }

    public function recalculateInstallments(int $new_count): void
    {
        if ($new_count <= 0) {
            throw new \InvalidArgumentException('تعداد قسط ها باید باید از صفر بیشتر باشد.');
        }

        $this->installment_count = $new_count;
        $this->monthly_installment_amount = round(
            $this->credit_limit / $new_count,
            2
        );

        $this->touch();
    }

    /* =======================
     * Status management
     * ======================= */

    public function getStatus(): string
    {
        return $this->status;
    }

    public function markOverdue(): void
    {
        if ($this->status !== 'settled') {
            $this->status = 'overdue';
            $this->touch();
        }
    }

    public function markActive(): void
    {
        if ($this->status !== 'settled') {
            $this->status = 'active';
            $this->touch();
        }
    }

    public function markSettled(): void
    {
        $this->status = 'settled';
        $this->available_amount = 0;
        $this->used_amount = 0;
        $this->touch();
    }

    public function suspend(): void
    {
        $this->status = 'suspended';
        $this->touch();
    }

    /* =======================
     * Timestamps
     * ======================= */

    protected function touch(): void
    {
        $this->updated_at = new \DateTime();
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updated_at;
    }

    /* =======================
     * Serialization
     * ======================= */

    public function toArray(): array
    {
        return [
            'id' => $this->id ?? null,
            'user_id' => $this->user_id,
            'credit_limit' => $this->credit_limit,
            'used_amount' => $this->used_amount,
            'available_amount' => $this->available_amount,
            'installment_count' => $this->installment_count,
            'monthly_installment_amount' => $this->monthly_installment_amount,
            'status' => $this->status,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}