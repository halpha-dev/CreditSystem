<?php
namespace CreditSystem\Domain;

if (!defined('ABSPATH')) {
    exit;
}

class Transaction
{
    protected ?int $id = null;
    protected ?int $user_id = null;
    protected ?int $credit_account_id = null;
    protected ?int $merchant_id = null;
    protected float $amount = 0.0;
    protected string $type = 'purchase'; // purchase | installment_payment
    protected string $status = 'success'; // success | failed
    protected ?string $created_at = null;
    protected ?string $updated_at = null;
    // --- Getters & Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $userId): self
    {
        $this->user_id = $userId;
        return $this;
    }

    public function getCreditAccountId(): ?int
    {
        return $this->credit_account_id;
    }

    public function setCreditAccountId(int $creditAccountId): self
    {
        $this->credit_account_id = $creditAccountId;
        return $this;
    }

    public function getMerchantId(): ?int
    {
        return $this->merchant_id;
    }

    public function setMerchantId(int $merchantId): self
    {
        $this->merchant_id = $merchantId;
        return $this;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $allowed = ['purchase', 'installment_payment'];
        if (!in_array($type, $allowed)) {
            throw new \InvalidArgumentException("نوع تراکنش شما اشتباه است: $type");
        }
        $this->type = $type;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $allowed = ['success', 'failed'];
        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Invalid transaction status: $status");
        }
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function setCreatedAt(string $createdAt): self
    {
        $this->created_at = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?string $updatedAt): self
    {
        $this->updated_at = $updatedAt;
        return $this;
    }
}