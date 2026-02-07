<?php
namespace CreditSystem\Includes\Services;

use CreditSystem\Includes\Domain\InstallmentPlan;
use CreditSystem\Includes\Domain\Installment;
use CreditSystem\Includes\Database\Repositories\InstallmentRepository;

class InstallmentPlanService {

    protected InstallmentRepository $installmentRepository;

    protected array $allowedPlans = [6, 9];

    public function __construct() {
        $this->installmentRepository = new InstallmentRepository();
    }

    public function createPlan(
        int $creditAccountId,
        int $userId,
        float $totalAmount,
        int $months,
        string $startDate
    ): InstallmentPlan {

        if (!in_array($months, $this->allowedPlans, true)) {
            throw new \InvalidArgumentException('Invalid installment plan selected.');
        }

        $monthlyAmount = round($totalAmount / $months, 2);

        $plan = new InstallmentPlan(
            null,
            $creditAccountId,
            $userId,
            $months,
            $totalAmount,
            $monthlyAmount,
            'active',
            current_time('mysql')
        );

        $planId = $this->installmentRepository->insertPlan($plan);
        $plan->setId($planId);

        $this->generateInstallments($plan, $startDate);

        return $plan;
    }

    protected function generateInstallments(InstallmentPlan $plan, string $startDate): void {

        $dueDate = new \DateTime($startDate);

        for ($i = 1; $i <= $plan->getMonths(); $i++) {

            $installment = new Installment(
                null,
                $plan->getId(),
                $plan->getUserId(),
                $i,
                $dueDate->format('Y-m-d'),
                $plan->getMonthlyAmount(),
                0,
                'unpaid',
                null,
                current_time('mysql')
            );

            $this->installmentRepository->insertInstallment($installment);

            $dueDate->modify('+1 month');
        }
    }
}