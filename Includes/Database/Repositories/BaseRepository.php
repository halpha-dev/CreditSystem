<?php
namespace CreditSystem\Database\Repositories;

use wpdb;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BaseRepository
 *
 * for secure accessebility to wpdb tables
 */
abstract class BaseRepository
{
    protected wpdb $db;

    public function __construct()
    {
        global $wpdb;
        $this->db = $wpdb;
    }

    /**
     * runing null query for force
     */
    protected function query(string $sql): bool|int
    {
        return $this->db->query($sql);
    }

    /**
     * get a var
     */
    protected function getVar(string $sql, array $params = []): mixed
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->get_var($sql);
    }

    /**
     * get a row
     */
    protected function getRow(string $sql, array $params = []): ?object
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        $row = $this->db->get_row($sql);

        return $row ?: null;
    }

    /**
     * get multiplie rows
     */
    protected function getResults(string $sql, array $params = []): array
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->get_results($sql) ?: [];
    }

    /**
     * runing standards insert
     */
    protected function insert(string $table, array $data, array $formats): int|false
    {
        $result = $this->db->insert($table, $data, $formats);

        if ($result === false) {
            return false;
        }

        return (int) $this->db->insert_id;
    }

    /**
     * runing standartd update
     */
    protected function update(
        string $table,
        array $data,
        array $where,
        array $dataFormats,
        array $whereFormats
    ): bool {
        return (bool) $this->db->update(
            $table,
            $data,
            $where,
            $dataFormats,
            $whereFormats
        );
    }

    /**
     * اruning standard delete
     */
    protected function delete(string $table, array $where, array $formats): bool
    {
        return (bool) $this->db->delete($table, $where, $formats);
    }

    /**
     * bd eror logs
     */
    protected function lastError(): string
    {
        return (string) $this->db->last_error;
    }
}