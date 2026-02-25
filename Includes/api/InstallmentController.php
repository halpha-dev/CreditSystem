<?php

namespace App\api;

use App\services\InstallmentService;
use App\services\NotificationService;

class InstallmentController
{
    protected InstallmentService $installmentService;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->installmentService = new InstallmentService();
        $this->notificationService = new NotificationService();
    }

    /**
     * List Of User Installments Plan
     */
    public function getUserInstallments(int $userId): array
    {
        return [
            'success' => true,
            'data' => $this->installmentService->getUserPlans($userId),
        ];
    }

    /**
     * details of a Instaliment plan 
     */
    public function getInstallmentDetail(int $userId, int $planId): array
    {
        $plan = $this->installmentService->getPlanForUser($userId, $planId);

        if (!$plan) {
            return [
                'success' => false,
                'message' => 'پلن اقساطی یافت نشد',
            ];
        }

        return [
            'success' => true,
            'data' => $plan,
        ];
    }

    /**
     * Pay a Installment
     */
    public function payInstallment(array $request): array
    {
        $userId = (int) $request['user_id'];
        $installmentId = (int) $request['installment_id'];
        $amount = (float) $request['amount'];

        $result = $this->installmentService->payInstallment(
            userId: $userId,
            installmentId: $installmentId,
            amount: $amount
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
            'message' => 'پرداخت قسط با موفقیت انجام شد',
            'data' => $result['data'],
        ];
    }

    /**
     * List of User due Installment
     */
    public function getDueInstallments(int $userId): array
    {
        return [
            'success' => true,
            'data' => $this->installmentService->getDueInstallments($userId),
        ];
    }
}