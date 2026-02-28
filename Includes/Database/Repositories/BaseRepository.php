<?php

namespace CreditSystem\Includes\Database\Repositories;

use wpdb;

/**
 * Base Repository
 */
class BaseRepository
{
    /** @var wpdb */
    protected $db;

    /**
     * @param wpdb|null $db
     */
    public function __construct($db = null) // حذف ?wpdb برای سازگاری
    {
        $this->db = $db ?: $GLOBALS['wpdb'];

        if (!$this->db instanceof wpdb) {
            throw new \RuntimeException('wpdb instance is required for BaseRepository');
        }
    }

    /**
     * اجرای کوئری عمومی
     */
    protected function query($sql, array $params = []) 
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->query($sql);
    }

    /**
     * دریافت یک مقدار تک
     */
    protected function getVar($sql, array $params = [])
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->get_var($sql);
    }

    /**
     * دریافت یک ردیف
     */
    protected function getRow($sql, array $params = [])
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        $row = $this->db->get_row($sql);

        return $row ?: null;
    }

    /**
     * دریافت چندین ردیف
     */
    protected function getResults($sql, array $params = [])
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->get_results($sql) ?: [];
    }

    /**
     * درج رکورد جدید
     */
    protected function insert($table, array $data, array $formats)
    {
        $result = $this->db->insert($table, $data, $formats);

        if ($result === false) {
            return false;
        }

        return (int) $this->db->insert_id;
    }

    /**
     * به‌روزرسانی رکورد
     */
    protected function update(
        $table,
        array $data,
        array $where,
        array $dataFormats = [],
        array $whereFormats = []
    ) {
        $result = $this->db->update($table, $data, $where, $dataFormats, $whereFormats);

        return $result !== false;
    }

    /**
     * حذف رکورد
     */
    protected function delete($table, array $where, array $formats = [])
    {
        $result = $this->db->delete($table, $where, $formats);

        return $result !== false;
    }

    /**
     * آخرین خطای دیتابیس
     */
    protected function lastError()
    {
        return (string) $this->db->last_error;
    }

    /**
     * لاگ کردن خطا
     */
    protected function logError($context = '')
    {
        if (!empty($this->db->last_error)) {
            error_log("[CreditSystem DB Error] {$context}: " . $this->db->last_error);
        }
    }
}