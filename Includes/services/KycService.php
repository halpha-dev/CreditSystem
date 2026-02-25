<?php

namespace CreditSystem\Includes\services;

use CreditSystem\Includes\domain\KycRequest;
use CreditSystem\Database\Repositories\KycRepository;
use CreditSystem\Database\Repositories\CreditAccountRepository;
use CreditSystem\Database\Repositories\MerchantRepository;
use CreditSystem\Includes\security\AuditLogger;
use CreditSystem\Includes\Exceptions\DomainException;

class KycService
{
    private KycRepository $kycRepository;
    private CreditAccountRepository $creditAccountRepository;
    private MerchantRepository $merchantRepository;
    private AuditLogger $auditLogger;

    public function __construct()
    {
        $this->kycRepository = new KycRepository();
        $this->creditAccountRepository = new CreditAccountRepository();
        $this->merchantRepository = new MerchantRepository();
        $this->auditLogger = new AuditLogger();
    }

    /**
     * ثبت درخواست احراز هویت
     */
    public function createRequest(
        int $userId,
        array $documents,
        ?int $installmentPlanId = null,
        ?int $preferredInstallments = null,
        bool $isMerchant = false,
        ?string $merchantName = null
    ): KycRequest {
        // جلوگیری از ارسال دوباره
        $existing = $this->kycRepository->getByUserId($userId);
        if ($existing && $existing->getStatus() === 'pending') {
            throw new DomainException('درخواست احراز هویت در حال بررسی است');
        }

        $kyc = new KycRequest(
            userId: $userId,
            documents: $documents,
            installmentPlanId: $installmentPlanId,
            preferredInstallments: $preferredInstallments
        );

        if ($isMerchant) {
            if (!$merchantName) {
                throw new DomainException('نام فروشگاه الزامی است');
            }

            $merchant = $this->merchantRepository->create([
                'wp_user_id' => $userId,
                'name' => $merchantName,
                'status' => 'pending',
            ]);

            $kycReflection = new \ReflectionClass($kyc);
            $prop = $kycReflection->getProperty('merchantId');
            $prop->setAccessible(true);
            $prop->setValue($kyc, $merchant->getId());
        }

        $id = $this->kycRepository->create($kyc);

        $this->auditLogger->log(
            'kyc_submitted',
            $userId,
            ['kyc_id' => $id]
        );

        return $this->kycRepository->getByUserId($userId);
    }

    /**
     * گرفتن وضعیت KYC کاربر
     */
    public function getByUserId(int $userId): ?KycRequest
    {
        return $this->kycRepository->getByUserId($userId);
    }

    /**
     * تایید KYC توسط ادمین
     */
    public function approve(int $kycId, int $adminId): void
    {
        $kyc = $this->getByIdOrFail($kycId);

        if ($kyc->getStatus() !== 'pending') {
            throw new DomainException('این درخواست قبلاً بررسی شده است');
        }

        $this->kycRepository->updateStatus($kycId, 'approved');

        // فعال‌سازی حساب اعتباری
        $this->creditAccountRepository->createForUser(
            userId: $kyc->getUserId(),
            installmentPlanId: $kyc->getInstallmentPlanId(),
            preferredInstallments: $kyc->getPreferredInstallments()
        );

        // اگر فروشنده است، فعال شود
        if ($kyc->getMerchantId()) {
            $this->merchantRepository->activate($kyc->getMerchantId());
        }

        $this->auditLogger->log(
            'kyc_approved',
            $adminId,
            [
                'kyc_id' => $kycId,
                'user_id' => $kyc->getUserId(),
            ]
        );
    }

    /**
     * رد KYC توسط ادمین
     */
    public function reject(int $kycId, int $adminId, string $reason): void
    {
        $kyc = $this->getByIdOrFail($kycId);

        if ($kyc->getStatus() !== 'pending') {
            throw new DomainException('این درخواست قبلاً بررسی شده است');
        }

        $this->kycRepository->updateStatus($kycId, 'rejected', $reason);

        $this->auditLogger->log(
            'kyc_rejected',
            $adminId,
            [
                'kyc_id' => $kycId,
                'user_id' => $kyc->getUserId(),
                'reason' => $reason,
            ]
        );
    }

    /**
     * لیست درخواست‌های pending برای ادمین
     */
    public function getPendingList(int $limit = 20, int $offset = 0): array
    {
        return $this->kycRepository->getPendingList($limit, $offset);
    }

    /**
     * اتصال احتمالی به سرویس بیرونی KYC
     * (فعلاً Stub – آماده‌ی توسعه)
     */
    public function verifyWithExternalProvider(KycRequest $kyc): bool
    {
        // اینجا می‌تونی api بانکی، شاهکار، یا هر سرویس دیگه‌ای رو بزنی
        // فعلاً همیشه true برمی‌گردونیم چون دنیا جای قشنگیه
        return true;
    }

    private function getByIdOrFail(int $kycId): KycRequest
    {
        $pendingList = $this->kycRepository->getPendingList(1, 0);
        foreach ($pendingList as $kyc) {
            if ($kyc->getId() === $kycId) {
                return $kyc;
            }
        }

        throw new DomainException('درخواست KYC یافت نشد');
    }
}