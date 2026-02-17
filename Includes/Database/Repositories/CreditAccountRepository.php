<?php
namespace CreditSystem\Database\Repositories;

use CreditSystem\Includes\Domain\CreditAccount;

if (!defined('ABSPATH')) {
    exit;
}

class CreditAccountRepository extends BaseRepository
{
    protected string $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->db->prefix . 'credit_accounts';
    }

    /**
     * create new credit account for users
     */
    public function create(CreditAccount $account): int
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:create','message'=>'Creating credit account','data'=>['user_id'=>$account->getUserId()],'runId'=>'run1','hypothesisId'=>'D']) . "\n", FILE_APPEND);
        // #endregion
        $result = $this->insert(
            $this->table,
            [
                'user_id' => $account->getUserId(),
                'credit_limit' => $account->getCreditLimit(),
                'available_credit' => $account->getAvailableCredit(),
                'status' => $account->getStatus(),
                'installment_plan_id' => $account->getInstallmentMonths(),
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%f', '%f', '%s', '%d', '%s']
        );
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:create','message'=>'Credit account creation result','data'=>['result'=>$result,'error'=>$this->lastError()],'runId'=>'run1','hypothesisId'=>'D']) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }

    /**
     * find user account by user id
     */
    public function findByUserId(int $userId): ?CreditAccount
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:findByUserId','message'=>'Finding credit account by user ID','data'=>['user_id'=>$userId],'runId'=>'run1','hypothesisId'=>'E']) . "\n", FILE_APPEND);
        // #endregion
        $row = $this->getRow(
            "SELECT * FROM {$this->table} WHERE user_id = %d LIMIT 1",
            [$userId]
        );
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:findByUserId','message'=>'Find result','data'=>['found'=>$row!==null],'runId'=>'run1','hypothesisId'=>'E']) . "\n", FILE_APPEND);
        // #endregion
        return $row ? $this->mapRowToEntity((array)$row) : null;
    }

    /**
     * Update credit accounts and amount
     */
    public function update(CreditAccount $account): bool
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:update','message'=>'Updating credit account','data'=>['account_id'=>$account->getId()],'runId'=>'run1','hypothesisId'=>'F']) . "\n", FILE_APPEND);
        // #endregion
        $data = [
            'available_credit' => $account->getAvailableCredit(),
            'status' => $account->getStatus(),
        ];
        $formats = ['%f', '%s'];
        
        // Add updated_at if column exists (check migration)
        if ($account->getActivatedAt()) {
            $data['activated_at'] = $account->getActivatedAt()->format('Y-m-d H:i:s');
            $formats[] = '%s';
        }
        
        $result = parent::update(
            $this->table,
            $data,
            ['id' => $account->getId()],
            $formats,
            ['%d']
        );
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:update','message'=>'Update result','data'=>['result'=>$result,'error'=>$this->lastError()],'runId'=>'run1','hypothesisId'=>'F']) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }
    /**
     * lock credit amount or settled insttalment
     */
    public function lockCredit(int $userId, float $amount): bool
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:lockCredit','message'=>'Locking credit','data'=>['user_id'=>$userId,'amount'=>$amount],'runId'=>'run1','hypothesisId'=>'G']) . "\n", FILE_APPEND);
        // #endregion
        $account = $this->findByUserId($userId);
        if (!$account || $account->getAvailableCredit() < $amount) {
            // #region agent log
            file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:lockCredit','message'=>'Lock credit failed - insufficient funds or account not found','data'=>['account_found'=>$account!==null,'available'=>$account?->getAvailableCredit()],'runId'=>'run1','hypothesisId'=>'G']) . "\n", FILE_APPEND);
            // #endregion
            return false;
        }

        $account->lockCredit($amount);
        $result = $this->update($account);
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:lockCredit','message'=>'Lock credit result','data'=>['result'=>$result],'runId'=>'run1','hypothesisId'=>'G']) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }

    /**
     * Release Credit after expire or settled
     */
    public function releaseCredit(int $userId, float $amount): bool
    {
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:releaseCredit','message'=>'Releasing credit','data'=>['user_id'=>$userId,'amount'=>$amount],'runId'=>'run1','hypothesisId'=>'H']) . "\n", FILE_APPEND);
        // #endregion
        $account = $this->findByUserId($userId);
        if (!$account) {
            // #region agent log
            file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:releaseCredit','message'=>'Release credit failed - account not found','data'=>[],'runId'=>'run1','hypothesisId'=>'H']) . "\n", FILE_APPEND);
            // #endregion
            return false;
        }

        $account->setUsedAmount(max(0, $account->getUsedAmount() - $amount));
        $account->setAvailableAmount($account->getAvailableAmount() + $amount);

        $result = $this->update($account);
        // #region agent log
        file_put_contents(__DIR__ . '/../../../../.cursor/debug.log', json_encode(['id'=>'log_' . time() . '_' . uniqid(),'timestamp'=>time()*1000,'location'=>'CreditAccountRepository.php:releaseCredit','message'=>'Release credit result','data'=>['result'=>$result],'runId'=>'run1','hypothesisId'=>'H']) . "\n", FILE_APPEND);
        // #endregion
        return $result;
    }

    /**
     * enter the row of amount database in CreditAccount
     */
    protected function mapRowToEntity(array|object $row): CreditAccount
    {
        $row = (array) $row;
        return CreditAccount::fromArray($row);
    }

    /**
     * Alias for findByUserId for service compatibility
     */
    public function getByUserIdForUpdate(int $userId): ?CreditAccount
    {
        return $this->findByUserId($userId);
    }

    /**
     * Lock amount (alias for lockCredit for service compatibility)
     */
    public function lockAmount(int $accountId, float $amount): bool
    {
        $account = $this->findById($accountId);
        if (!$account) {
            return false;
        }
        return $this->lockCredit($account->getUserId(), $amount);
    }

    /**
     * Consume locked amount
     */
    public function consumeLockedAmount(int $accountId, float $amount): bool
    {
        $account = $this->findById($accountId);
        if (!$account) {
            return false;
        }
        $account->consumeLockedCredit($amount);
        return $this->update($account);
    }

    /**
     * Find by ID
     */
    public function findById(int $id): ?CreditAccount
    {
        $row = $this->getRow(
            "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
            [$id]
        );
        return $row ? $this->mapRowToEntity((array)$row) : null;
    }

    /**
     * Release credit after installment payment (for service compatibility)
     */
    public function releaseCreditAfterInstallment(int $accountId, float $amount): bool
    {
        $account = $this->findById($accountId);
        if (!$account) {
            return false;
        }
        // Release locked credit back to available
        $account->releaseLockedCredit($amount);
        return $this->update($account);
    }
}