<?php
namespace CreditSystem\Includes\services;

use CreditSystem\Database\Repositories\InstallmentRepository;
use CreditSystem\Database\Repositories\CreditCodeRepository;
use CreditSystem\Includes\security\AuditLogger;

class NotificationService
{
    protected InstallmentRepository $installmentRepository;
    protected CreditCodeRepository $creditCodeRepository;

    public function __construct()
    {
        $this->installmentRepository = new InstallmentRepository();
        $this->creditCodeRepository = new CreditCodeRepository();
    }

    /**
     * instalment Reminder
     * cron-based
     */
    public function sendInstallmentReminders(int $daysBefore): void
    {
        $installments = $this->installmentRepository
            ->getDueInDays($daysBefore);

        foreach ($installments as $installment) {
            $this->send(
                $installment->getUserId(),
                'installment_reminder',
                [
                    'due_date' => $installment->getDueDate(),
                    'amount' => $installment->getTotalAmount()
                ]
            );
        }
    }

    /**
     * Overdue installment notifications
     */
    public function sendOverdueNotifications(): void
    {
        $installments = $this->installmentRepository->getOverdue();

        foreach ($installments as $installment) {
            $this->send(
                $installment->getUserId(),
                'installment_overdue',
                [
                    'due_date' => $installment->getDueDate(),
                    'amount' => $installment->getTotalAmount(),
                    'penalty' => $installment->getPenaltyAmount()
                ]
            );
        }
    }

    /**
     * send notify for issued code
     */
    public function notifyCodeIssued(int $userId, string $code, string $expiresAt): void
    {
        $this->send(
            $userId,
            'credit_code_issued',
            [
                'code' => $code,
                'expires_at' => $expiresAt
            ]
        );
    }

    /**
     * Notyfy for code consumed
     */
    public function notifyCodeConsumed(int $userId, float $amount): void
    {
        $this->send(
            $userId,
            'credit_code_consumed',
            [
                'amount' => $amount
            ]
        );
    }

    /**
     * send notification core
     * (only abstraction)
     */
    protected function send(int $userId, string $type, array $data = []): void
    {
        // SMS، Push، Email 
        // just loging

        AuditLogger::log('notification_sent', [
            'user_id' => $userId,
            'type' => $type,
            'data' => $data,
            'sent_at' => current_time('mysql')
        ]);
    }
}