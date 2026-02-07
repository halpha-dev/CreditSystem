<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once DIR . '/BaseRepository.php';

class CreditCodeRepository extends BaseRepository
{
    protected $table = 'credit_codes';

    /**
     * ganerate 16 digit code
     */
    public function create(array $data): int
    {
        $this->wpdb->insert(
            $this->table(),
            [
                'code'        => $data['code'],
                'user_id'     => $data['user_id'],
                'merchant_id' => $data['merchant_id'],
                'amount'      => $data['amount'],
                'expires_at'  => $data['expires_at'],
                'status'      => 'unused',
                'created_at'  => current_time('mysql'),
            ],
            ['%s', '%d', '%d', '%f', '%s', '%s', '%s']
        );

        return (int) $this->wpdb->insert_id;
    }

    /**
     * get valid code for shoping
     */
    public function getValidCode(string $code, int $merchant_id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table()}
                 WHERE code = %s
                   AND merchant_id = %d
                   AND status = 'unused'
                   AND expires_at >= %s
                 LIMIT 1",
                $code,
                $merchant_id,
                current_time('mysql')
            )
        );
    }

    /**
     * mark code as used code
     * (atomic – just one time)
     */
    public function markAsUsed(int $code_id): bool
    {
        $result = $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table()}
                 SET status = 'used',
                     used_at = %s
                 WHERE id = %d
                   AND status = 'unused'
                   AND expires_at >= %s",
                current_time('mysql'),
                $code_id,
                current_time('mysql')
            )
        );

        return $result === 1;
    }

    /**
     * expire old and unused codes
     * (for Cron)
     */
    public function expireOldCodes(): int
    {
        return (int) $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->table()}
                 SET status = 'expired'
                 WHERE status = 'unused'
                   AND expires_at < %s",
                current_time('mysql')
            )
        );
    }

    /**
     * check codes not duplicate
     */
    public function exists(string $code): bool
    {
        $count = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(1) FROM {$this->table()} WHERE code = %s",
                $code
            )
        );

        return ((int) $count) > 0;
    }

    /**
     * get active code by user
     */
    public function getActiveByUser(int $user_id): array
    {
        return $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->table()}
                 WHERE user_id = %d
                   AND status = 'unused'
                   AND expires_at >= %s
                 ORDER BY created_at DESC",
                $user_id,
                current_time('mysql')
            )
        );
    }
}
