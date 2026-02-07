<?php
namespace CreditSystem\Database\Repositories;

use CreditSystem\Database\TransactionManager;
use CreditSystem\Domain\CreditAccount;

if (!defined('ABSPATH')) {
    exit;
}

class CreditAccountRepository extends BaseRepository
{
    protected string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'credit_accounts';
    }

    /**
     * create new credit account for users
     */
    public function create(CreditAccount $account): int
    {
        global $wpdb;

        $wpdb->insert(
            $this->table,
            [
                'user_id' => $account->getUserId(),
                'credit_limit' => $account->getCreditLimit(),
                'used_amount' => $account->getUsedAmount(),
                'available_amount' => $account->getAvailableAmount(),
                'installment_count' => $account->getInstallmentCount(),
                'monthly_installment_amount' => $account->getMonthlyInstallmentAmount(),
                'status' => $account->getStatus(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%f', '%f', '%f', '%d', '%f', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    /**
     * find user account by user id
     */
    public function findByUserId(int $userId): ?CreditAccount
    {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE user_id = %d LIMIT 1", $userId),
            ARRAY_A
        );

        return $row ? $this->mapRowToEntity($row) : null;
    }

    /**
     * Update credit accounts and amount
     */
    public function update(CreditAccount $account): bool
    {
        global $wpdb;

        return (bool) $wpdb->update(
            $this->table,
            [
                'used_amount' => $account->getUsedAmount(),
                'available_amount' => $account->getAvailableAmount(),
                'status' => $account->getStatus(),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $account->getId()],
            ['%f', '%f', '%s', '%s'],
            ['%d']
        );
    }
    /**
     * lock credit amount or settled insttalment
     */
    public function lockCredit(int $userId, float $amount): bool
    {
        global $wpdb;

        $account = $this->findByUserId($userId);
        if (!$account || $account->getAvailableAmount() < $amount) {
            return false;
        }

        $account->setUsedAmount($account->getUsedAmount() + $amount);
        $account->setAvailableAmount($account->getAvailableAmount() - $amount);

        return $this->update($account);
    }

    /**
     * Release Credit after expire or settled
     */
    public function releaseCredit(int $userId, float $amount): bool
    {
        global $wpdb;

        $account = $this->findByUserId($userId);
        if (!$account) {
            return false;
        }

        $account->setUsedAmount(max(0, $account->getUsedAmount() - $amount));
        $account->setAvailableAmount($account->getAvailableAmount() + $amount);

        return $this->update($account);
    }

    /**
     * enter the row of amount database in CreditAccount
     */
    protected function mapRowToEntity(array $row): CreditAccount
    {
        $account = new CreditAccount();
        $account->setId((int) $row['id']);
        $account->setUserId((int) $row['user_id']);
        $account->setCreditLimit((float) $row['credit_limit']);
        $account->setUsedAmount((float) $row['used_amount']);
        $account->setAvailableAmount((float) $row['available_amount']);
        $account->setInstallmentCount((int) $row['installment_count']);
        $account->setMonthlyInstallmentAmount((float) $row['monthly_installment_amount']);
        $account->setStatus($row['status']);
        $account->setCreatedAt($row['created_at']);
        $account->setUpdatedAt($row['updated_at'] ?? null);

        return $account;
    }
}