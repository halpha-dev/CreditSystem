<?php
namespace CreditSystem\Includes\Services;

use CreditSystem\Includes\Database\Repositories\CreditAccountRepository;
use CreditSystem\Includes\Database\TransactionManager;

class CreditService {

    protected CreditAccountRepository $creditAccountRepository;
    protected InstallmentPlanService $installmentPlanService;

    public function __construct() {
        $this->creditAccountRepository = new CreditAccountRepository();
        $this->installmentPlanService  = new InstallmentPlanService();
    }

    public function allocateCredit(
        int $userId,
        float $amount,
        int $installmentMonths,
        string $firstDueDate
    ): void {

        TransactionManager::begin();

        try {

            $account = $this->creditAccountRepository->getOrCreateByUserId($userId);

            $this->creditAccountRepository->increaseBalance(
                $account->getId(),
                $amount
            );

            $this->installmentPlanService->createPlan(
                $account->getId(),
                $userId,
                $amount,
                $installmentMonths,
                $firstDueDate
            );

            TransactionManager::commit();

        } catch (\Throwable $e) {
            TransactionManager::rollback();
            throw $e;
        }
    }
}