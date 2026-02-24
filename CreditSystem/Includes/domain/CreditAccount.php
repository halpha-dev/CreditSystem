<?php

namespace CreditSystem\Includes\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

class CreditAccount
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_BLOCKED = 'blocked';

    public const KYC_PENDING = 'pending';
    public const KYC_APPROVED = 'approved';
    public const KYC_REJECTED = 'rejected';

    private int $id;
    private int $userId;

    private float $creditLimit;
    private float $availableCredit;
    private float $lockedCredit;

    private int $installmentMonths;

    private string $status;
    private string $kycStatus;

    private DateTimeImmutable $createdAt;
    private ?DateTimeImmutable $activatedAt;

    public function __construct(
        int $userId,
        float $creditLimit,
        int $installmentMonths,
        string $kycStatus = self::KYC_PENDING
    ) {
        if ($creditLimit <= 0) {
            throw new InvalidArgumentException('Credit limit must be greater than zero.');
        }

        if (!in_array($installmentMonths, [3, 6, 12], true)) {
            throw new InvalidArgumentException('Invalid installment plan. Allowed: 3, 6, 12 months.');
        }

        $this->userId = $userId;
        $this->creditLimit = $creditLimit;
        $this->availableCredit = $creditLimit;
        $this->lockedCredit = 0.0;
        $this->installmentMonths = $installmentMonths;

        $this->status = self::STATUS_PENDING;
        $this->kycStatus = $kycStatus;

        $this->createdAt = new DateTimeImmutable();
        $this->activatedAt = null;
    }

    /* -----------------------------
     * KYC
     * --------------------------- */

    public function approveKyc(): void
    {
        $this->kycStatus = self::KYC_APPROVED;
    }

    public function rejectKyc(): void
    {
        $this->kycStatus = self::KYC_REJECTED;
        $this->block();
    }

    public function isKycApproved(): bool
    {
        return $this->kycStatus === self::KYC_APPROVED;
    }

    /* -----------------------------
     * Account lifecycle
     * --------------------------- */

    public function activate(): void
    {
        if (!$this->isKycApproved()) {
            throw new InvalidArgumentException('Cannot activate credit account without approved KYC.');
        }

        $this->status = self::STATUS_ACTIVE;
        $this->activatedAt = new DateTimeImmutable();
    }

    public function block(): void
    {
        $this->status = self::STATUS_BLOCKED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /* -----------------------------
     * Credit operations
     * --------------------------- */

    public function canUseCredit(float $amount): bool
    {
        return $this->isActive() && $amount > 0 && $this->availableCredit >= $amount;
    }

    public function lockCredit(float $amount): void
    {
        if (!$this->canUseCredit($amount)) {
            throw new InvalidArgumentException('Insufficient available credit or account inactive.');
        }

        $this->availableCredit -= $amount;
        $this->lockedCredit += $amount;
    }

    public function consumeLockedCredit(float $amount): void
    {
        if ($amount <= 0 || $this->lockedCredit < $amount) {
            throw new InvalidArgumentException('Invalid locked credit consumption.');
        }

        $this->lockedCredit -= $amount;
    }

    public function releaseLockedCredit(float $amount): void
    {
        if ($amount <= 0 || $this->lockedCredit < $amount) {
            throw new InvalidArgumentException('Invalid locked credit release.');
        }

        $this->lockedCredit -= $amount;
        $this->availableCredit += $amount;
    }

    /* -----------------------------
     * Installments
     * --------------------------- */

    public function getInstallmentMonths(): int
    {
        return $this->installmentMonths;
    }

    public function calculateMonthlyInstallment(float $totalAmount): float
    {
        return round($totalAmount / $this->installmentMonths, 2);
    }

    /* -----------------------------
     * Getters
     * --------------------------- */

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCreditLimit(): float
    {
        return $this->creditLimit;
    }

    public function getAvailableCredit(): float
    {
        return $this->availableCredit;
    }

    public function getLockedCredit(): float
    {
        return $this->lockedCredit;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getKycStatus(): string
    {
        return $this->kycStatus;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getActivatedAt(): ?DateTimeImmutable
    {
        return $this->activatedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Alias for getAvailableCredit (for service compatibility)
     */
    public function getAvailableBalance(): float
    {
        return $this->getAvailableCredit();
    }

    /**
     * Factory method to reconstruct from database
     */
    public static function fromArray(array $data): self
    {
        $account = new self(
            (int) $data['user_id'],
            (float) $data['credit_limit'],
            (int) ($data['installment_plan_id'] ?? 3), // Default to 3 months if not set
            $data['kyc_status'] ?? self::KYC_PENDING
        );

        $account->id = (int) $data['id'];
        $account->availableCredit = (float) ($data['available_credit'] ?? $data['credit_limit']);
        $account->lockedCredit = (float) ($data['locked_credit'] ?? 0.0);
        $account->status = $data['status'] ?? self::STATUS_PENDING;
        $account->kycStatus = $data['kyc_status'] ?? self::KYC_PENDING;
        
        if (isset($data['created_at'])) {
            $account->createdAt = new DateTimeImmutable($data['created_at']);
        }
        if (isset($data['activated_at'])) {
            $account->activatedAt = new DateTimeImmutable($data['activated_at']);
        }

        return $account;
    }
}
