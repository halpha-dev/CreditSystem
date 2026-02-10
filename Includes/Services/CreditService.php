<?php
namespace CreditSystem\Services;

use CreditSystem\Domain\CreditAccount;
use CreditSystem\Security\AuditLogger;
use CreditSystem\Security\PermissionPolicy;
use WP_Error;
use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class CreditService
{
    protected $logger;

    public function __construct()
    {
        $this->logger = new AuditLogger();
    }

    /**
     * ایجاد حساب اعتباری برای کاربر
     */
    public function createAccount(int $userId): CreditAccount|WP_Error
    {
        if (!PermissionPolicy::canManageCredits($userId)) {
            return new WP_Error('permission_denied', 'دسترسی لازم برای ایجاد حساب اعتباری وجود ندارد');
        }

        try {
            $account = CreditAccount::create($userId);

            $this->logger->log('credit_account_created', [
                'user_id' => $userId,
            ]);

            return $account;
        } catch (Exception $e) {
            $this->logger->log('credit_account_create_failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return new WP_Error('credit_create_failed', 'خطا در ایجاد حساب اعتباری');
        }
    }

    /**
     * افزایش اعتبار
     */
    public function increaseCredit(
        int $userId,
        float $amount,
        string $reason = 'manual'
    ): CreditAccount|WP_Error {

        if ($amount <= 0) {
            return new WP_Error('invalid_amount', 'مقدار افزایش اعتبار نامعتبر است');
        }

        if (!PermissionPolicy::canIncreaseCredit()) {
            return new WP_Error('permission_denied', 'اجازه افزایش اعتبار ندارید');
        }

        try {
            $account = CreditAccount::loadByUserId($userId);
            $oldBalance = $account->getBalance();

            $account->increase($amount, $reason);

            $this->logger->log('credit_increased', [
                'user_id'     => $userId,
                'amount'      => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $account->getBalance(),
                'reason'      => $reason,
            ]);

            return $account;
        } catch (Exception $e) {
            $this->logger->log('credit_increase_failed', [
                'user_id' => $userId,
                'amount'  => $amount,
                'error'   => $e->getMessage(),
            ]);

            return new WP_Error('credit_increase_failed', 'افزایش اعتبار انجام نشد');
        }
    }

    /**
     * کاهش اعتبار
     */
    public function decreaseCredit(
        int $userId,
        float $amount,
        string $reason = 'usage'
    ): CreditAccount|WP_Error {

        if ($amount <= 0) {
            return new WP_Error('invalid_amount', 'مقدار کاهش اعتبار نامعتبر است');
        }

        if (!PermissionPolicy::canDecreaseCredit()) {
            return new WP_Error('permission_denied', 'اجازه کاهش اعتبار ندارید');
        }

        try {
            $account = CreditAccount::loadByUserId($userId);

            if ($account->getBalance() < $amount) {
                return new WP_Error('insufficient_credit', 'اعتبار کافی نیست');
            }

            $oldBalance = $account->getBalance();
            $account->decrease($amount, $reason);

            $this->logger->log('credit_decreased', [
                'user_id'     => $userId,
                'amount'      => $amount,
                'old_balance' => $oldBalance,
                'new_balance' => $account->getBalance(),
                'reason'      => $reason,
            ]);

            return $account;
        } catch (Exception $e) {
            $this->logger->log('credit_decrease_failed', [
                'user_id' => $userId,
                'amount'  => $amount,
                'error'   => $e->getMessage(),
            ]);

            return new WP_Error('credit_decrease_failed', 'کاهش اعتبار انجام نشد');
        }
    }

    /**
     * دریافت وضعیت حساب
     */
    public function getAccount(int $userId): CreditAccount|WP_Error
    {
        if (!PermissionPolicy::canViewCredit($userId)) {
            return new WP_Error('permission_denied', 'اجازه مشاهده حساب وجود ندارد');
        }

        try {
            return CreditAccount::loadByUserId($userId);
        } catch (Exception $e) {
            return new WP_Error('credit_not_found', 'حساب اعتباری یافت نشد');
        }
    }

    /**
     * مسدود کردن حساب اعتباری
     */
    public function blockAccount(int $userId, string $reason = ''): bool|WP_Error
    {
        if (!PermissionPolicy::canManageCredits()) {
            return new WP_Error('permission_denied', 'اجازه مسدودسازی حساب وجود ندارد');
        }

        try {
            $account = CreditAccount::loadByUserId($userId);
            $account->block($reason);

            $this->logger->log('credit_account_blocked', [
                'user_id' => $userId,
                'reason'  => $reason,
            ]);

            return true;
        } catch (Exception $e) {
            $this->logger->log('credit_account_block_failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);

            return new WP_Error('credit_block_failed', 'مسدودسازی انجام نشد');
        }
    }

    /**
     * رفع مسدودی حساب
     */
    public function unblockAccount(int $userId): bool|WP_Error
    {
        if (!PermissionPolicy::canManageCredits()) {
            return new WP_Error('permission_denied', 'اجازه رفع مسدودی وجود ندارد');
        }

        try {
            $account = CreditAccount::loadByUserId($userId);
            $account->unblock();

            $this->logger->log('credit_account_unblocked', [
                'user_id' => $userId,
            ]);

            return true;
        } catch (Exception $e) {
            return new WP_Error('credit_unblock_failed', 'رفع مسدودی انجام نشد');
        }
    }
}
