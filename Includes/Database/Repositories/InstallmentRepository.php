<?php
namespace CreditSystem\Includes\Database\Repositories;

use CreditSystem\Domain\Installment;

if (!defined('ABSPATH')) {
    exit;
}

class InstallmentRepository extends BaseRepository
{
    /** @var string */
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->db->prefix . 'installments';
    }

    /**
     * ایجاد قسط جدید
     */
    public function create($installment)
    {
        return $this->insert(
            $this->table,
            [
                'user_id'           => $installment->getUserId(),
                'credit_account_id' => $installment->getCreditAccountId(),
                'transaction_id'    => $installment->getTransactionId(),
                'due_date'          => $installment->getDueDate(),
                'base_amount'       => $installment->getBaseAmount(),
                'penalty_amount'    => $installment->getPenaltyAmount(),
                'total_amount'      => $installment->getTotalAmount(),
                'status'            => $installment->getStatus(),
                'paid_at'           => $installment->getPaidAt(),
                'created_at'        => current_time('mysql'),
            ],
            ['%d','%d','%d','%s','%f','%f','%f','%s','%s','%s']
        );
    }

    public function findByUserId($userId)
    {
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE user_id = %d ORDER BY due_date ASC",
            [(int)$userId]
        );
        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function getByUserId($userId)
    {
        return $this->findByUserId($userId);
    }

    public function findByCreditAccountId($creditAccountId)
    {
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE credit_account_id = %d ORDER BY due_date ASC",
            [(int)$creditAccountId]
        );
        return array_map([$this, 'mapRowToEntity'], $rows);
    }

public function updateEntity($installment) 
{
    return parent::update(
        $this->table,
        [
            'base_amount'    => $installment->getBaseAmount(),
            'penalty_amount' => $installment->getPenaltyAmount(),
            'total_amount'   => $installment->getTotalAmount(),
            'status'         => $installment->getStatus(),
            'paid_at'        => $installment->getPaidAt(),
            'updated_at'     => current_time('mysql'),
        ],
        ['id' => $installment->getId()],
        ['%f','%f','%f','%s','%s','%s'],
        ['%d']
    );
}

    public function findDueOrOverdue()
    {
        $today = current_time('Y-m-d');
        $rows = $this->getResults(
            "SELECT * FROM {$this->table} WHERE status IN ('unpaid','overdue') AND due_date <= %s",
            [$today]
        );
        return array_map([$this, 'mapRowToEntity'], $rows);
    }

    public function getForUpdate($installmentId, $userId)
    {
        $row = $this->getRow(
            "SELECT * FROM {$this->table} WHERE id = %d AND user_id = %d LIMIT 1",
            [(int)$installmentId, (int)$userId]
        );
        return $row ? $this->mapRowToEntity($row) : null;
    }

    protected function mapRowToEntity($row)
    {
        $row = (array) $row;
        
        // اگر متد factory در کلاس Domain وجود دارد از آن استفاده کن
        if (method_exists(Installment::class, 'fromArray')) {
            return Installment::fromArray($row);
        }
        
        // در غیر این صورت به صورت دستی بساز
        return new Installment(
            (int)$row['id'],
            (int)$row['user_id'],
            (int)$row['credit_account_id'],
            (int)($row['transaction_id'] ?? 0),
            (float)$row['base_amount'],
            (float)$row['penalty_amount'],
            $row['due_date'],
            $row['status'],
            $row['paid_at'] ?? null,
            $row['created_at']
        );
    }
    public function findAll() {
    $rows = $this->getResults("SELECT * FROM {$this->table} ORDER BY created_at DESC");
    return array_map([$this, 'mapRowToEntity'], $rows);
    }
    /**
 * ذخیره پلن جدید در دیتابیس
 */
public function insertPlan($data)
{
    // نام جدول پلن‌ها (مطمئن شوید این جدول در دیتابیس ساخته شده است)
    $table_name = $this->db->prefix . 'installment_plans';

    return $this->insert(
        $table_name,
        [
            'title'         => $data['title'],
            'months'        => (int)$data['months'],
            'interest_rate' => (float)$data['interest_rate'],
            'penalty_rate'  => (float)$data['penalty_rate'],
            'reminder_days' => (int)$data['reminder_days'],
            'is_active'     => (int)$data['is_active'],
            'created_at'    => $data['created_at']
        ],
        ['%s', '%d', '%f', '%f', '%d', '%d', '%s'] // فرمت ستون‌ها
    );
}
} // <--- تمام متدها باید قبل از این آکولاد نهایی باشند