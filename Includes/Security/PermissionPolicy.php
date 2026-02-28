<?php
namespace CreditSystem\Includes\security;

use CreditSystem\Includes\Database\Repositories\CreditAccountRepository;
use CreditSystem\Includes\Database\Repositories\MerchantRepository;

class PermissionPolicy
{
    // ۱. تعریف ثابت‌ها (بسیار مهم: چون در متدها از این‌ها استفاده کرده‌اید)
    const ACTION_VIEW_CREDIT = 'view_credit';
    const ACTION_PAY_INSTALLMENT = 'pay_installment';
    const ACTION_REQUEST_CREDIT = 'request_credit';
    const MERCHANT_REDEEM_CODE = 'redeem_credit_code';
    const MERCHANT_WITHDRAW = 'withdraw_balance';

    /** @var CreditAccountRepository */
    private $creditAccountRepo;

    /** @var MerchantRepository */
    private $merchantRepo;

    /** @var AuditLogger */
    private $logger;

    public function __construct(
        $creditAccountRepo,
        $merchantRepo,
        $logger
    ) {
        $this->creditAccountRepo = $creditAccountRepo;
        $this->merchantRepo = $merchantRepo;
        $this->logger = $logger;
    }

    /**
     * بررسی دسترسی کاربر
     */
    public function canUserPerform($userId, $action)
    {
        $account = $this->creditAccountRepo->findByUserId($userId);
        $isActive = ($account !== null && $account->status === 'active');

        // استفاده از switch برای سازگاری با PHP 7.x
        switch ($action) {
            case self::ACTION_VIEW_CREDIT:
            case self::ACTION_PAY_INSTALLMENT:
                $allowed = $isActive;
                break;
            case self::ACTION_REQUEST_CREDIT:
                $allowed = !$isActive;
                break;
            default:
                $allowed = false;
        }

        $this->logger->logPermissionCheck('user', $userId, $action, $allowed);
        return $allowed;
    }

    /**
     * بررسی دسترسی فروشنده
     */
    public function canMerchantPerform($merchantId, $action)
    {
        $merchant = $this->merchantRepo->findById($merchantId);

        if (!$merchant || $merchant->status !== 'active') {
            $this->logger->logPermissionCheck('merchant', $merchantId, $action, false);
            return false;
        }

        $allowedActions = [
            self::MERCHANT_REDEEM_CODE,
            self::MERCHANT_WITHDRAW,
            'view_sales_report'
        ];

        $allowed = in_array($action, $allowedActions, true);
        
        $this->logger->logPermissionCheck('merchant', $merchantId, $action, $allowed);
        return $allowed;
    }

    /**
     * بررسی دسترسی ادمین
     */
    public function canAdminPerform($wpUserId, $action)
    {
        if (user_can($wpUserId, 'manage_options')) {
            return true; 
        }

        if (user_can($wpUserId, 'credit_operator')) {
            $operatorActions = [
                'approve_credit',
                'view_users',
                'adjust_credit'
            ];
            return in_array($action, $operatorActions, true);
        }

        return false;
    }
    /**
 * بررسی دسترسی ادمین و توقف اجرای صفحه در صورت عدم دسترسی
 */
/**
 */
public static function adminOnly() {
    // چون استاتیک است، دیگر به $this دسترسی نداریم
    // پس مستقیماً از تابع وردپرس استفاده می‌کنیم
    if (!current_user_can('manage_options')) {
        wp_die('شما اجازه دسترسی به این بخش را ندارید.');
    }
}
} // <--- پایان واقعی کلاس. تمام متدها باید قبل از این آکولاد باشند.