<?php
namespace CreditSystem\Database\Repositories;

use CreditSystem\Database\Repositories\BaseRepository;

if (!defined('ABSPATH')) {
    exit;
}

class MerchantRepository extends BaseRepository
{
    protected string $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = $this->db->prefix . 'cs_merchant';
    }

    /**
     * |create new merchant
     */
    public function create(array $data): int|false
    {
        $defaults = [
            'user_id'     => null,
            'title'       => '',
            'status'      => 'active', // active | inactive | suspended
            'created_at'  => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $this->db->insert(
            $this->table,
            [
                'user_id'    => (int) $data['user_id'],
                'title'      => sanitize_text_field($data['title']),
                'status'     => sanitize_text_field($data['status']),
                'created_at' => $data['created_at'],
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            return false;
        }

        return (int) $this->db->insert_id;
    }

    /**
     * find merchants by ID
     */
    public function find(int $id): ?object
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
            $id
        );

        $row = $this->db->get_row($sql);

        return $row ?: null;
    }

    /**
     * find merchants by user_id
     */
    public function findByUserId(int $userId): ?object
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = %d LIMIT 1",
            $userId
        );

        $row = $this->db->get_row($sql);

        return $row ?: null;
    }

    /**
     * merchants list
     */
    public function getAll(int $limit = 50, int $offset = 0): array
    {
        $sql = $this->db->prepare(
            "SELECT * FROM {$this->table}
             ORDER BY id DESC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        );

        return $this->db->get_results($sql) ?: [];
    }

    /**
     * change merchants status
     */
    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->db->update(
            $this->table,
            ['status' => sanitize_text_field($status)],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * delete merchants
     */
    public function delete(int $id): bool
    {
        return (bool) $this->db->delete(
            $this->table,
            ['id' => $id],
            ['%d']
        );
    }
}