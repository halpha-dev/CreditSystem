<?php
namespace CreditSystem\Domain;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CreditCode
 *
 * مدل دامنه کد اعتباری یک‌بارمصرف
 */
class CreditCode
{
    private int $id;
    private string $code;

    private int $userId;
    private int $merchantId;

    private float $amount;

    private string $status; // unused | used | expired

    private string $expiresAt;
    private ?string $usedAt;
    private string $createdAt;

    public function __construct(
        int $id,
        string $code,
        int $userId,
        int $merchantId,
        float $amount,
        string $status,
        string $expiresAt,
        ?string $usedAt,
        string $createdAt
    ) {
        $this->id         = $id;
        $this->code       = $code;
        $this->userId     = $userId;
        $this->merchantId = $merchantId;
        $this->amount     = $amount;
        $this->status     = $status;
        $this->expiresAt  = $expiresAt;
        $this->usedAt     = $usedAt;
        $this->createdAt  = $createdAt;
    }

    /* ========================
     * Getters
     * ====================== */

    public function getId(): int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getMerchantId(): int
    {
        return $this->merchantId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getExpiresAt(): string
    {
        return $this->expiresAt;
    }

    public function getUsedAt(): ?string
    {
        return $this->usedAt;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
/* ========================
     * Domain Logic
     * ====================== */

    public function isExpired(): bool
    {
        return strtotime($this->expiresAt) < time();
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function isUsable(): bool
    {
        return $this->status === 'unused' && !$this->isExpired();
    }

    public function markAsUsed(): void
    {
        if (!$this->isUsable()) {
            throw new \RuntimeException('این کد قابل استفاده نمی باشد.');
        }

        $this->status = 'used';
        $this->usedAt = current_time('mysql');
    }

    public function markAsExpired(): void
    {
        if ($this->status === 'used') {
            return;
        }

        $this->status = 'expired';
    }

    /* ========================
     * Factory
     * ====================== */

    public static function fromArray(array $data): self
    {
        return new self(
            (int) $data['id'],
            (string) $data['credit_code'],
            (int) $data['user_id'],
            (int) $data['merchant_id'],
            (float) $data['amount'],
            (string) $data['status'],
            (string) $data['expires_at'],
            $data['used_at'] ?? null,
            (string) $data['created_at']
        );
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'credit_code' => $this->code,
            'user_id'     => $this->userId,
            'merchant_id' => $this->merchantId,
            'amount'      => $this->amount,
            'status'      => $this->status,
            'expires_at'  => $this->expiresAt,
            'used_at'     => $this->usedAt,
            'created_at'  => $this->createdAt,
        ];
    }
}
