<?php

namespace CreditSystem\Includes\api\Admin;

use WP_REST_Request;
use WP_REST_Response;
use CreditSystem\Includes\services\KycService;
use CreditSystem\Includes\security\AuthMiddleware;
use CreditSystem\Includes\Security\PermissionPolicy;

class KycController
{
    private KycService $kycService;

    public function __construct()
    {
        $this->kycService = new KycService();
    }

    public function registerRoutes(): void
    {
        register_rest_route('creditsystem/v1/admin', '/kyc', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'list'],
                'permission_callback' => [$this, 'adminOnly'],
            ],
        ]);

        register_rest_route('creditsystem/v1/admin', '/kyc/(?P<id>\d+)', [
            [
                'methods' => 'GET',
                'callback' => [$this, 'details'],
                'permission_callback' => [$this, 'adminOnly'],
            ],
        ]);

        register_rest_route('creditsystem/v1/admin', '/kyc/(?P<id>\d+)/approve', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'approve'],
                'permission_callback' => [$this, 'adminOnly'],
            ],
        ]);

        register_rest_route('creditsystem/v1/admin', '/kyc/(?P<id>\d+)/reject', [
            [
                'methods' => 'POST',
                'callback' => [$this, 'reject'],
                'permission_callback' => [$this, 'adminOnly'],
            ],
        ]);
    }

    /**
     * لیست درخواست‌های pending
     */
    public function list(WP_REST_Request $request): WP_REST_Response
    {
        $limit = (int) ($request->get_param('limit') ?? 20);
        $offset = (int) ($request->get_param('offset') ?? 0);

        $requests = $this->kycService->getPendingList($limit, $offset);

        $data = array_map(function ($kyc) {
            return [
                'id' => $kyc->getId(),
                'user_id' => $kyc->getUserId(),
                'status' => $kyc->getStatus(),
                'installment_plan_id' => $kyc->getInstallmentPlanId(),
                'preferred_installments' => $kyc->getPreferredInstallments(),
                'merchant_id' => $kyc->getMerchantId(),
                'submitted_at' => $kyc->getSubmittedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $requests);

        return new WP_REST_Response([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * مشاهده جزئیات یک درخواست KYC
     */
    public function details(WP_REST_Request $request): WP_REST_Response
    {
        $kycId = (int) $request->get_param('id');

        $kyc = $this->kycService->getByIdOrFail($kycId);

        return new WP_REST_Response([
            'success' => true,
            'data' => [
                'id' => $kyc->getId(),
                'user_id' => $kyc->getUserId(),
                'status' => $kyc->getStatus(),
                'documents' => $kyc->getDocuments(),
                'installment_plan_id' => $kyc->getInstallmentPlanId(),
                'preferred_installments' => $kyc->getPreferredInstallments(),
                'merchant_id' => $kyc->getMerchantId(),
                'submitted_at' => $kyc->getSubmittedAt()?->format('Y-m-d H:i:s'),
                'approved_at' => $kyc->getApprovedAt()?->format('Y-m-d H:i:s'),
                'rejection_reason' => $kyc->getRejectionReason(),
            ],
        ]);
    }

    /**
     * تایید KYC
     */
    public function approve(WP_REST_Request $request): WP_REST_Response
    {
        $kycId = (int) $request->get_param('id');
        $adminId = get_current_user_id();

        $this->kycService->approve($kycId, $adminId);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'درخواست احراز هویت تایید شد',
        ]);
    }

    /**
     * رد KYC
     */
    public function reject(WP_REST_Request $request): WP_REST_Response
    {
        $kycId = (int) $request->get_param('id');
        $reason = (string) $request->get_param('reason');
        $adminId = get_current_user_id();

        if (empty($reason)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'دلیل رد الزامی است',
            ], 400);
        }

        $this->kycService->reject($kycId, $adminId, $reason);

        return new WP_REST_Response([
            'success' => true,
            'message' => 'درخواست احراز هویت رد شد',
        ]);
    }

    /**
     * فقط ادمین
     */
    public function adminOnly(): bool
    {
        AuthMiddleware::check();
        return PermissionPolicy::adminOnly();
    }
}