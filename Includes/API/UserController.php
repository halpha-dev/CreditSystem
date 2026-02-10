<?php

namespace CreditSystem\Includes\API;

use WP_REST_Request;
use WP_REST_Response;
use CreditSystem\Includes\Security\AuthMiddleware;
use CreditSystem\Includes\Security\PermissionPolicy;
use CreditSystem\Includes\Services\CreditService;
use CreditSystem\Includes\Services\KycService;
use CreditSystem\Includes\Services\InstallmentService;
use CreditSystem\Includes\Services\TransactionService;

class UserController
{
    private CreditService $creditService;
    private KycService $kycService;
    private InstallmentService $installmentService;
    private TransactionService $transactionService;

    public function __construct()
    {
        $this->creditService = new CreditService();
        $this->kycService = new KycService();
        $this->installmentService = new InstallmentService();
        $this->transactionService = new TransactionService();
    }

    public function registerRoutes(): void
    {
        register_rest_route('creditsystem/v1', '/user/kyc', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'submitKyc'],
                'permission_callback' => [AuthMiddleware::class, 'check'],
            ],
            [
                'methods' => 'GET',
                'callback' => [$this, 'getKycStatus'],
                'permission_callback' => [AuthMiddleware::class, 'check'],
            ],
        ]);

        register_rest_route('creditsystem/v1', '/user/credit', [
            'methods' => 'GET',
            'callback' => [$this, 'getCreditInfo'],
            'permission_callback' => [AuthMiddleware::class, 'check'],
        ]);

        register_rest_route('creditsystem/v1', '/user/installments', [
            'methods' => 'GET',
            'callback' => [$this, 'getInstallments'],
            'permission_callback' => [AuthMiddleware::class, 'check'],
        ]);

        register_rest_route('creditsystem/v1', '/user/transactions', [
            'methods' => 'GET',
            'callback' => [$this, 'getTransactions'],
            'permission_callback' => [AuthMiddleware::class, 'check'],
        ]);
    }

    /**
     * ارسال اطلاعات احراز هویت (KYC)
     */
    public function submitKyc(WP_REST_Request $request): WP_REST_Response
    {
        $userId = get_current_user_id();

        PermissionPolicy::userOnly($userId);

        $documents = (array) $request->get_param('documents');
        $installmentPlanId = $request->get_param('installment_plan_id');
        $preferredInstallments = $request->get_param('preferred_installments');
        $isMerchant = (bool) $request->get_param('is_merchant');
        $merchantName = $request->get_param('merchant_name');

        if (empty($documents)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'مدارک احراز هویت ارسال نشده است'
            ], 400);
        }

        $kycRequest = $this->kycService->createRequest(
            userId: $userId,
            documents: $documents,
            installmentPlanId: $installmentPlanId,
            preferredInstallments: $preferredInstallments,
            isMerchant: $isMerchant,
            merchantName: $merchantName
        );

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'kyc_id' => $kycRequest->getId(),
                'status' => $kycRequest->getStatus(),
            ]
        ], 201);
    }

    /**
     * مشاهده وضعیت احراز هویت
     */
    public function getKycStatus(): WP_REST_Response
    {
        $userId = get_current_user_id();

        PermissionPolicy::userOnly($userId);

        $kyc = $this->kycService->getByUserId($userId);

        if (!$kyc) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'درخواست احراز هویت یافت نشد'
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'status' => $kyc->getStatus(),
                'submitted_at' => $kyc->getSubmittedAt()?->format('Y-m-d H:i:s'),
                'approved_at' => $kyc->getApprovedAt()?->format('Y-m-d H:i:s'),
                'rejection_reason' => $kyc->getRejectionReason(),
                'installment_plan_id' => $kyc->getInstallmentPlanId(),
                'preferred_installments' => $kyc->getPreferredInstallments(),
            ]
        ]);
    }

    /**
     * اطلاعات حساب اعتباری کاربر
     */
    public function getCreditInfo(): WP_REST_Response
    {
        $userId = get_current_user_id();

        PermissionPolicy::userOnly($userId);

        $credit = $this->creditService->getByUserId($userId);

        return new WP_REST_Response([
            'success' => true,
            'data' => $credit
        ]);
    }

    /**
     * اقساط + جریمه + یادآوری‌ها
     */
    public function getInstallments(): WP_REST_Response
    {
        $userId = get_current_user_id();

        PermissionPolicy::userOnly($userId);

        $installments = $this->installmentService->getUserInstallmentsWithPenalties($userId);

        return new WP_REST_Response([
            'success' => true,
            'data' => $installments
        ]);
    }

    /**
     * تراکنش‌ها (کاربر یا فروشنده)
     */
    public function getTransactions(): WP_REST_Response
    {
        $userId = get_current_user_id();

        PermissionPolicy::userOnly($userId);

        $transactions = $this->transactionService->getByUser($userId);

        return new WP_REST_Response([
            'success' => true,
            'data' => $transactions
        ]);
    }
}