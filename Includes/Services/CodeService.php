<?php
namespace CreditSystem\Includes\Services;

use CreditSystem\Includes\Database\Repositories\CreditCodeRepository;
use CreditSystem\Includes\Database\Repositories\CreditAccountRepository;
use CreditSystem\Includes\Database\TransactionManager;
use CreditSystem\Includes\Domain\CreditCode;

class CodeService
{
    protected CreditCodeRepository $codeRepository;
    protected CreditAccountRepository $creditAccountRepository;

    public function __construct()
    {
        $this->codeRepository         = new CreditCodeRepository();
        $this->creditAccountRepository = new CreditAccountRepository();
    }

    /**
     * صدور کد خرید ۱۶ رقمی برای مصرف حضوری
     */
    public function generateCode(
        int $userId,
        int $merchantId,
        float $amount
    ): CreditCode {

        TransactionManager::begin();

        try {
            $account = $this->creditAccountRepository->getByUserIdForUpdate($userId);

            if (!$account) {
                throw new \Exception('Credit account not found.');
            }

            if ($account->getAvailableBalance() < $amount) {
                throw new \Exception('Insufficient credit balance.');
            }

            // قفل کردن اعتبار
            $this->creditAccountRepository->lockAmount(
                $account->getId(),
                $amount
            );

            $codeValue = $this->generateUniqueCode();

            $expiresAt = date(
                'Y-m-d H:i:s',
                strtotime('+15 minutes', current_time('timestamp'))
            );

            $creditCode = new CreditCode(
                null,
                $codeValue,
                $userId,
                $merchantId,
                $amount,
                'unused',
                $expiresAt,
                null,
                current_time('mysql')
            );

            $codeId = $this->codeRepository->insert($creditCode);
            $creditCode->setId($codeId);

            TransactionManager::commit();

            return $creditCode;

        } catch (\Throwable $e) {
            TransactionManager::rollback();
            throw $e;
        }
    }
    /**
     * مصرف کد توسط فروشنده
     */
    public function consumeCode(
        string $codeValue,
        int $merchantId
    ): void {

        TransactionManager::begin();

        try {
            $code = $this->codeRepository->getValidCodeForUpdate(
                $codeValue,
                $merchantId
            );

            if (!$code) {
                throw new \Exception('Invalid or expired code.');
            }

            $account = $this->creditAccountRepository->getByUserIdForUpdate(
                $code->getUserId()
            );

            if (!$account) {
                throw new \Exception('Credit account not found.');
            }

            // کسر نهایی اعتبار
            $this->creditAccountRepository->consumeLockedAmount(
                $account->getId(),
                $code->getAmount()
            );

            // علامت‌گذاری کد به عنوان مصرف‌شده
            $this->codeRepository->markAsUsed(
                $code->getId(),
                current_time('mysql')
            );

            TransactionManager::commit();

        } catch (\Throwable $e) {
            TransactionManager::rollback();
            throw $e;
        }
    }

    /**
     * تولید کد یکتای ۱۶ رقمی
     */
    protected function generateUniqueCode(): string
    {
        do {
         $code = str_pad(
        (string) random_int(0, 9999999999999999),
        16,
        '0',
        STR_PAD_LEFT);  // 16 hex chars
        } while ($this->codeRepository->existsByCode($code));

        return $code;
    }
}