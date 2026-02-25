<?php

namespace CreditSystem\Cron;

use CreditSystem\Database\Repositories\InstallmentRepository;
use CreditSystem\Services\NotificationService;
use CreditSystem\Config\Constants;

if (!defined('ABSPATH')) {
    exit;
}

class SendReminders
{
    protected InstallmentRepository $installmentRepository;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->installmentRepository = new InstallmentRepository();
        $this->notificationService   = new NotificationService();
    }

    /**
     * Cron entry point
     */
    public function handle(): void
    {
        $today = current_time('Y-m-d');

        // 1. Reminder before due date
        $this->sendUpcomingDueReminders($today);

        // 2. Reminder on due date
        $this->sendDueTodayReminders($today);

        // 3. Reminder for overdue installments
        $this->sendOverdueReminders($today);
    }

    /**
     * Send reminders X days before due date
     */
    protected function sendUpcomingDueReminders(string $today): void
    {
        $daysBeforeList = Constants::INSTALLMENT_REMINDER_DAYS_BEFORE;

        foreach ($daysBeforeList as $daysBefore) {
            $targetDate = date('Y-m-d', strtotime("+{$daysBefore} days", strtotime($today)));

            $installments = $this->installmentRepository
                ->getUnpaidByDueDate($targetDate);

            foreach ($installments as $installment) {
                $this->notificationService->sendInstallmentReminder(
                    $installment->user_id,
                    [
                        'type'        => 'before_due',
                        'days_before' => $daysBefore,
                        'due_date'    => $installment->due_date,
                        'amount'      => $installment->total_amount,
                    ]
                );
            }
        }
    }

    /**
     * Send reminders on due date
     */
    protected function sendDueTodayReminders(string $today): void
    {
        $installments = $this->installmentRepository
            ->getUnpaidByDueDate($today);

        foreach ($installments as $installment) {
            $this->notificationService->sendInstallmentReminder(
                $installment->user_id,
                [
                    'type'     => 'due_today',
                    'due_date'=> $installment->due_date,
                    'amount'  => $installment->total_amount,
                ]
            );
        }
    }

    /**
     * Send reminders for overdue installments
     */
    protected function sendOverdueReminders(string $today): void
    {
        $intervalDays = Constants::INSTALLMENT_OVERDUE_REMINDER_INTERVAL;

        $installments = $this->installmentRepository
            ->getOverdueInstallments($today);

        foreach ($installments as $installment) {

            // جلوگیری از اسپم روزانه
            if (!$this->shouldSendOverdueReminder($installment->last_reminder_at, $intervalDays)) {
                continue;
            }

            $this->notificationService->sendInstallmentReminder(
                $installment->user_id,
                [
                    'type'          => 'overdue',
                    'due_date'      => $installment->due_date,
                    'amount'        => $installment->total_amount,
                    'penalty'       => $installment->penalty_amount,
                    'days_overdue'  => $installment->days_overdue,
                ]
            );

            $this->installmentRepository->updateLastReminderAt($installment->id);
        }
    }

    /**
     * Check reminder throttling for overdue installments
     */
    protected function shouldSendOverdueReminder(?string $lastReminderAt, int $intervalDays): bool
    {
        if (!$lastReminderAt) {
            return true;
        }

        $nextAllowed = strtotime("+{$intervalDays} days", strtotime($lastReminderAt));

        return time() >= $nextAllowed;
    }
}