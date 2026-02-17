<?php

namespace CreditSystem\Includes\Database\Repositories;

use wpdb;

/**
 * Base Repository
 *
 * پایه تمام Repositoryهای سیستم اعتباری
 * شامل متدهای استاندارد CRUD با پشتیبانی از prepared statements
 */
class BaseRepository
{
    /** @var wpdb */
    protected $db;

    /**
     * Constructor with optional dependency injection
     *
     * @param wpdb|null $db
     */
    public function __construct(?wpdb $db = null)
    {
        $this->db = $db ?? $GLOBALS['wpdb'];

        if (!$this->db instanceof wpdb) {
            throw new \RuntimeException('wpdb instance is required for BaseRepository');
        }
    }

    /**
     * اجرای کوئری عمومی (برای DELETE, UPDATE, INSERT بدون بازگشت داده)
     */
    protected function query(string $sql, array $params = []): int|bool
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->query($sql);
    }

    /**
     * دریافت یک مقدار تک (مثل COUNT)
     */
    protected function getVar(string $sql, array $params = []): mixed
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->get_var($sql);
    }

    /**
     * دریافت یک ردیف
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
     * دریافت چندین ردیف
     */
    protected function getResults(string $sql, array $params = []): array
    {
        if (!empty($params)) {
            $sql = $this->db->prepare($sql, ...$params);
        }

        return $this->db->get_results($sql) ?: [];
    }

    /**
     * درج رکورد جدید
     */
    protected function insert(string $table, array $data, array $formats): int|false
    {
        $result = $this->db->insert($table, $data, $formats);

        if ($result === false) {
            // می‌تونی اینجا لاگ کنی اگر خواستی
            // $this->logError();
            return false;
        }

        return (int) $this->db->insert_id;
    }

    /**
     * به‌روزرسانی رکورد
     */
    protected function update(
        string $table,
        array $data,
        array $where,
        array $dataFormats = [],
        array $whereFormats = []
    ): bool {
        $result = $this->db->update($table, $data, $where, $dataFormats, $whereFormats);

        return $result !== false;
    }

    /**
     * حذف رکورد
     */
    protected function delete(string $table, array $where, array $formats = []): bool
    {
        $result = $this->db->delete($table, $where, $formats);

        return $result !== false;
    }

    /**
     * آخرین خطای دیتابیس
     */
    protected function lastError(): string
    {
        return (string) $this->db->last_error;
    }

    /**
     * لاگ کردن خطا (اختیاری - بعداً می‌تونی با AuditLogger وصلش کنی)
     */
    protected function logError(string $context = ''): void
    {
        if (!empty($this->db->last_error)) {
            error_log("[CreditSystem DB Error] {$context}: " . $this->db->last_error);
            // یا file_put_contents اگر خواستی
        }
    }
}