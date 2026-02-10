<?php

namespace CreditSystem\Includes\Domain;

use CreditSystem\Includes\Database\Repositories\CreditAccountRepository;
use CreditSystem\Includes\Database\Repositories\InstallmentRepository;
use CreditSystem\Includes\Database\Repositories\TransactionRepository;
use CreditSystem\Includes\Database\Repositories\MerchantRepository;

class KycRequest
{
    private int $id;
    private int $userId;
    private array $documents; // فایل‌ها و مدارک KYC
    private string $status; // pending | approved | rejected
    private ?\DateTime $submittedAt;
    private ?\DateTime $approvedAt;
    private ?string $rejectionReason;
    private ?int $installmentPlanId; // انتخاب پلن اقساط
    private ?int $preferredInstallments; // تعداد قسط انتخابی کاربر
    private ?int $merchantId; // برای کاربر فروشنده، مربوط به فروشگاه

    public function __construct(
        int $userId,
        array $documents,
        ?int $installmentPlanId = null,
        ?int $preferredInstallments = null,
        ?int $merchantId = null
    ) {
        $this->userId = $userId;
        $this->documents = $documents;
        $this->status = 'pending';
        $this->submittedAt = new \DateTime();
        $this->approvedAt = null;
        $this->rejectionReason = null;
        $this->installmentPlanId = $installmentPlanId;
        $this->preferredInstallments = $preferredInstallments;
        $this->merchantId = $merchantId;
    }

    // ---------- Getter و Setter ها ----------
    public function getId(): int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getSubmittedAt(): ?\DateTime
    {
        return $this->submittedAt;
    }

    public function getApprovedAt(): ?\DateTime
    {
        return $this->approvedAt;
    }

    public function getRejectionReason(): ?string
    {
        return $this->rejectionReason;
    }

    public function getInstallmentPlanId(): ?int
    {
        return $this->installmentPlanId;
    }

    public function getPreferredInstallments(): ?int
    {
        return $this->preferredInstallments;
    }

    public function getMerchantId(): ?int
    {
        return $this->merchantId;
    }

    public function setStatus(string $status): void
    {
        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            throw new \InvalidArgumentException("Invalid KYC status");
        }
        $this->status = $status;
        if ($status === 'approved') {
            $this->approvedAt = new \DateTime();
        }
    }

    public function setRejectionReason(?string $reason): void
    {
        $this->rejectionReason = $reason;
    }

    // ---------- عملیات مرتبط با سیستم ----------

    /**
     * تبدیل KYC به حساب اعتباری فعال
     */
    public function approve(CreditAccountRepository $creditRepo): CreditAccount
    {
        $this->setStatus('approved');

        $creditAccount = new CreditAccount(
            $this->userId,
            0.0, // اعتبار اولیه
            0.0, // available_credit
            $this->installmentPlanId
        );

        // ذخیره در دیتابیس
        $creditRepo->create($creditAccount);

        return $creditAccount;
    }

    /**
     * رد کردن درخواست KYC
     */
    public function reject(string $reason): void
    {
        $this->setStatus('rejected');
        $this->setRejectionReason($reason);
    }

    /**
     * اگر کاربر فروشنده است، فروشگاه ایجاد یا بروزرسانی شود
     */
    public function setupMerchant(MerchantRepository $merchantRepo, string $merchantName): void
    {
        if (!$this->merchantId) {
            $merchant = $merchantRepo->create([
                'wp_user_id' => $this->userId,
                'name' => $merchantName,
                'status' => 'active'
            ]);
            $this->merchantId = $merchant->getId();
        }
    }

    /**
     * بازیابی تراکنش‌های کاربر
     */
    public function getTransactions(TransactionRepository $transactionRepo): array
    {
        return $transactionRepo->getByUserId($this->userId);
    }

    /**
     * بازیابی اقساط کاربر
     */
    public function getInstallments(InstallmentRepository $installmentRepo): array
    {
        return $installmentRepo->getByUserId($this->userId);
    }
}