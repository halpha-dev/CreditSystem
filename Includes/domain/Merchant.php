<?php
namespace CreditSystem\Domain;

if (!defined('ABSPATH')) {
    exit;
}

class Merchant
{
    protected ?int $id = null;
    protected ?string $name = null;
    protected string $status = 'active'; // active | inactive
    protected array $used_codes = []; // لیست کدهای مصرف شده
    protected float $total_received = 0.0; // مبالغی که از پلتفرم دریافت شده
    protected float $total_pending = 0.0;  // مبالغی که باید به فروشگاه پرداخت شود
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $allowed = ['active', 'inactive'];
        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Invalid merchant status: $status");
        }
        $this->status = $status;
        return $this;
    }

    public function getUsedCodes(): array
    {
        return $this->used_codes;
    }

    public function setUsedCodes(array $codes): self
    {
        $this->used_codes = $codes;
        return $this;
    }

    public function addUsedCode(string $code): self
    {
        if (!in_array($code, $this->used_codes)) {
            $this->used_codes[] = $code;
        }
        return $this;
    }

    public function getTotalReceived(): float
    {
        return $this->total_received;
    }

    public function setTotalReceived(float $amount): self
    {
        $this->total_received = $amount;
        return $this;
    }

    public function addReceivedAmount(float $amount): self
    {
        $this->total_received += $amount;
        return $this;
    }

    public function getTotalPending(): float
    {
        return $this->total_pending;
    }

    public function setTotalPending(float $amount): self
    {
        $this->total_pending = $amount;
        return $this;
    }

    public function addPendingAmount(float $amount): self
    {
        $this->total_pending += $amount;
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