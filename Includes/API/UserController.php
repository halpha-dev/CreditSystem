<?php

namespace App\API;

use App\Services\CreditService;
use App\Services\InstallmentService;
use App\Services\NotificationService;

class UserController
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
     * get credit
     */
    public function getCredit(int $userId): array
    {
        return [
            'success' => true,
            'data' => $this->creditService->getUserCredit($userId),
        ];
    }

    /**
     * create installment plan
     */
    public function createInstallmentPlan(array $request): array
    {
        $userId = (int) $request['user_id'];
        $amount = (float) $request['amount'];
        $months = (int) $request['months'];

        if (!in_array($months, [ 6, 9])) {
            return [
                'success' => false,
                'message' => 'پلن اقساط نامعتبر است',
            ];
        }

        if (!$this->creditService->hasEnoughCredit($userId, $amount)) {
            return [
                'success' => false,
                'message' => 'اعتبار کافی نیست',
            ];
        }

        $plan = $this->installmentService->createPlan(
            userId: $userId,
            amount: $amount,
            months: $months
        );

        $this->creditService->reserveCredit($userId, $amount);

        $this->notificationService->send(
            $userId,
            'پلن اقساط شما با موفقیت ایجاد شد'
        );

        return [
            'success' => true,
            'data' => $plan,
        ];
    }

    /**
     * دریافت لیست اقساط کاربر
     */
    public function getInstallments(int $userId): array
    {
        return [
            'success' => true,
            'data' => $this->installmentService->getUserInstallments($userId),
        ];
    }

    /**
     * پرداخت قسط
     */
    public function payInstallment(array $request): array
    {
        $userId = (int) $request['user_id'];
        $installmentId = (int) $request['installment_id'];

        $result = $this->installmentService->payInstallment(
            userId: $userId,
            installmentId: $installmentId
        );

        if (!$result['success']) {
            return $result;
        }

        $this->notificationService->send(
            $userId,
            'قسط شما با موفقیت پرداخت شد'
        );

        return [
            'success' => true,
            'message' => 'پرداخت انجام شد',
        ];
    }

    /**
     * due Installments (for User panel)
     */
    public function getDueInstallments(int $userId): array
    {
        return [
            'success' => true,
            'data' => $this->installmentService->getDueInstallments($userId),
        ];
    }
}