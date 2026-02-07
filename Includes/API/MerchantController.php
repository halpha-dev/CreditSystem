<?php

namespace App\API;

use App\Services\CreditService;
use App\Services\InstallmentService;
use App\Services\NotificationService;

class MerchantController
{
    protected CreditService $creditService;
    protected InstallmentService $installmentService;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->creditService = new CreditService();
        $this->installmentService = new InstallmentService();
        $this->notificationService = new NotificationService();
    }

    /**
     * Create Installmentpurchase By Merchent
     */
    public function createInstallmentPurchase(array $request): array
    {
        $merchantId = (int) $request['merchant_id'];
        $userId = (int) $request['user_id'];
        $amount = (float) $request['amount'];
        $months = (int) $request['months'];
        $orderId = $request['order_id'] ?? null;

        if (!in_array($months, [3, 6, 12])) {
            return [
                'success' => false,
                'message' => 'مدت اقساط نامعتبر است',
            ];
        }

        if (!$this->creditService->hasEnoughCredit($userId, $amount)) {
            return [
                'success' => false,
                'message' => 'اعتبار کاربر کافی نیست',
            ];
        }

        $plan = $this->installmentService->createPlan(
            userId: $userId,
            amount: $amount,
            months: $months,
            merchantId: $merchantId,
            orderId: $orderId
        );

        $this->creditService->reserveCredit($userId, $amount);

        $this->notificationService->send(
            $userId,
            'خرید اقساطی شما با موفقیت ثبت شد'
        );

        return [
            'success' => true,
            'data' => [
                'installment_plan_id' => $plan['id'],
                'order_id' => $orderId,
                'amount' => $amount,
                'months' => $months,
            ],
        ];
    }

    /**
     * list of Merchent installments Selles
     */
    public function getMerchantInstallments(int $merchantId): array
    {
        return [
            'success' => true,
            'data' => $this->installmentService->getMerchantPlans($merchantId),
        ];
    }

    /**
     * details of a Installments
     */
    public function getInstallmentDetail(int $merchantId, int $planId): array
    {
        $plan = $this->installmentService->getPlanForMerchant($merchantId, $planId);

        if (!$plan) {
            return [
                'success' => false,
                'message' => 'پلن مورد نظر یافت نشد',
            ];
        }

        return [
            'success' => true,
            'data' => $plan,
        ];
    }
}