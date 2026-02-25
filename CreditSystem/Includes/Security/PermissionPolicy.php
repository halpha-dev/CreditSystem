<?php
namespace CreditSystem\Includes\Security;

use CreditSystem\Includes\Database\Repositories\CreditAccountRepository;
use CreditSystem\Includes\Database\Repositories\MerchantRepository;
use CreditSystem\Includes\Security\AuditLogger;

/**
 * Class PermissionPolicy
 * 
 * مدیریت دسترسی کاربران و فروشندگان به بخش‌ها و اکشن‌های مختلف سیستم.
 * بررسی می‌کند آیا کاربر یا فروشنده اجازه انجام عملیات خاص را دارد یا خیر.
 */
class PermissionPolicy
{
    private CreditAccountRepository $creditAccountRepo;
    private MerchantRepository $merchantRepo;
    private AuditLogger $logger;

    public function __construct(
        CreditAccountRepository $creditAccountRepo,
        MerchantRepository $merchantRepo,
        AuditLogger $logger
    ) {
        $this->creditAccountRepo = $creditAccountRepo;
        $this->merchantRepo = $merchantRepo;
        $this->logger = $logger;
    }

    /**
     * بررسی دسترسی کاربر به عملیات خاص
     *
     * @param int $userId
     * @param string $action
     * @return bool
     */
    public function canUserPerform(int $userId, string $action): bool
    {
        // گرفتن اطلاعات اعتبار کاربر
        $creditAccount = $this->creditAccountRepo->findByUserId($userId);

        switch ($action) {
            case 'view_credit':
                $allowed = $creditAccount !== null && $creditAccount->status === 'active';
                break;

            case 'pay_installment':
                $allowed = $creditAccount !== null && $creditAccount->status === 'active';
                break;

            case 'request_credit':
                $allowed = $creditAccount === null || $creditAccount->status !== 'active';
                break;

            default:
                $allowed = false;
        }

        $this->logger->logPermissionCheck('user', $userId, $action, $allowed);
        return $allowed;
    }

    /**
     * بررسی دسترسی فروشنده به عملیات خاص
     *
     * @param int $merchantId
     * @param string $action
     * @return bool
     */
    public function canMerchantPerform(int $merchantId, string $action): bool
    {
        $merchant = $this->merchantRepo->findById($merchantId);

        if (!$merchant || $merchant->status !== 'active') {
            $this->logger->logPermissionCheck('merchant', $merchantId, $action, false);
            return false;
        }

        switch ($action) {
            case 'redeem_credit_code':
            case 'view_sales_report':
            case 'withdraw_balance':
                $allowed = true;
                break;

            default:
                $allowed = false;
        }

        $this->logger->logPermissionCheck('merchant', $merchantId, $action, $allowed);
        return $allowed;
    }

    /**
     * بررسی دسترسی admin یا operator به سیستم
     *
     * @param string $role
     * @param string $action
     * @return bool
     */
    public function canAdminPerform(string $role, string $action): bool
    {
        // roles: admin, credit_operator
        if ($role === 'admin') {
            return true; // دسترسی کامل
        }

        if ($role === 'credit_operator') {
            $allowedActions = [
                'approve_credit',
                'view_users',
                'adjust_credit',
                'override_installments'
            ];
            return in_array($action, $allowedActions, true);
        }

        return false;
    }
}