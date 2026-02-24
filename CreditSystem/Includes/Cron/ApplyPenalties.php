<?php

namespace App\Cron;

use App\Database\Repositories\InstallmentRepository;
use App\Config\Constants;

class ApplyPenalties
{
    protected InstallmentRepository $installmentRepository;

    public function __construct()
    {
        $this->installmentRepository = new InstallmentRepository();
    }

    /**
     * Run the penalty of installment
     */
    public function run(): void
    {
        $today = current_time('Y-m-d');

        $installments = $this->installmentRepository->getInstallmentsForPenalty($today);

        if (empty($installments)) {
            return;
        }

        foreach ($installments as $installment) {
            $this->applyDailyPenalty($installment);
        }
    }

    /**
     * Aplly daily  Penslty
     */
    protected function applyDailyPenalty(object $installment): void
    {
        $baseAmount = (float) $installment->base_amount;
        $currentPenalty = (float) $installment->penalty_amount;

        $dailyRate = Constants::DAILY_PENALTY_RATE; // مثلا 0.005
        $maxRate = Constants::MAX_PENALTY_RATE; // مثلا 0.10

        $dailyPenalty = $baseAmount * $dailyRate;
        $newPenalty = $currentPenalty + $dailyPenalty;

        $maxPenaltyAllowed = $baseAmount * $maxRate;

        if ($newPenalty > $maxPenaltyAllowed) {
            $newPenalty = $maxPenaltyAllowed;
        }

        $this->installmentRepository->updatePenalty(
            (int) $installment->id,
            $newPenalty
        );

        // اگر هنوز overdue نشده، تغییر وضعیت بده
        if ($installment->status !== 'overdue') {
            $this->installmentRepository->markAsOverdue((int) $installment->id);
        }
    }

    /**
     * ثبت کران
     */
    public static function schedule(): void
    {
        if (!wp_next_scheduled('credit_system_apply_penalties')) {
            wp_schedule_event(time(), 'daily', 'credit_system_apply_penalties');
        }
    }

    /**
     * اتصال هوک
     */
    public static function hook(): void
    {
        add_action('credit_system_apply_penalties', function () {
            (new self())->run();
        });
    }

    /**
     * حذف کران
     */
    public static function unschedule(): void
    {
        $timestamp = wp_next_scheduled('credit_system_apply_penalties');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'credit_system_apply_penalties');
        }
    }
}