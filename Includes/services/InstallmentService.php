<?php
namespace CreditSystem\Includes\services;

use CreditSystem\Database\Repositories\InstallmentRepository;
use CreditSystem\Database\Repositories\CreditAccountRepository;
use CreditSystem\Database\Repositories\TransactionRepository;
use CreditSystem\Database\TransactionManager;
use CreditSystem\domain\Installment;

class InstallmentService
{
    protected InstallmentRepository $installmentRepository;
    protected CreditAccountRepository $creditAccountRepository;
    protected TransactionRepository $transactionRepository;

    public function __construct()
    {
        $this->installmentRepository     = new InstallmentRepository();
        $this->creditAccountRepository   = new CreditAccountRepository();
        $this->transactionRepository     = new TransactionRepository();
    }

    /**
     * User installment list
     */
    public function getUserInstallments(int $userId): array
    {
        return $this->installmentRepository->getByUserId($userId);
    }

    /**
     * Pay Installment
     */
    public function payInstallment(int $installmentId, int $userId): void
    {
        TransactionManager::begin();

        try {
            $installment = $this->installmentRepository
                ->getForUpdate($installmentId, $userId);

            if (!$installment) {
                throw new \Exception('Installment not found.');
            }

            if ($installment->isPaid()) {
                // idempotent behavior
                TransactionManager::commit();
                return;
            }

            if (!$installment->isPayable()) {
                throw new \Exception('Installment is not payable yet.');
            }

            $totalAmount = $installment->getTotalAmount();

            // mark Installment as paid
            $transactionId = $this->transactionRepository->insert([
                'user_id'     => $userId,
                'amount'      => $totalAmount,
                'type'        => 'installment_payment',
                'status'      => 'success',
                'created_at'  => current_time('mysql')
            ]);

            // Mark Installment as paid
            $this->installmentRepository->markAsPaid(
                $installment->getId(),
                $transactionId
            );

            // release credit after Installment 
            $this->creditAccountRepository->releaseCreditAfterInstallment(
                $installment->getCreditAccountId(),
                $installment->getBaseAmount()
            );

            TransactionManager::commit();

        } catch (\Throwable $e) {
            TransactionManager::rollback();
            throw $e;
        }
    }

    /**
     * Daily Penalty Rate 
     * (for cron)
     */
    public function applyDailyPenalties(float $dailyPenaltyRate): void
    {
        $overdueInstallments = $this->installmentRepository->getOverdue();

        foreach ($overdueInstallments as $installment) {

            $dailyPenalty = $installment->getBaseAmount() * $dailyPenaltyRate;

            $this->installmentRepository->addPenalty(
                $installment->getId(),
                $dailyPenalty
            );
        }
    }

    /**
     * installment reminder (for cron)
     */
    public function getInstallmentsForReminder(int $daysBefore): array
    {
        return $this->installmentRepository
            ->getDueInDays($daysBefore);
    }
}