<?php

namespace CreditSystem\Includes\Database;

class Migrations
{
    private $wpdb;
    private $prefix;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        // پیشوند جداول: مثلا wp_cs_
        $this->prefix = $wpdb->prefix . 'cs_';
    }

    public function migrate()
    {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $this->createUsersTable();
        $this->createMerchantsTable(); // مرچنت باید قبل از کدهای اعتباری ساخته شود
        $this->createCreditAccountsTable();
        $this->createInstallmentPlansTable();
        $this->createInstallmentsTable();
        $this->createCreditCodesTable();
        $this->createTransactionsTable();
        $this->createInstallmentPenaltiesTable();
        $this->createInstallmentRemindersTable();
    }

    private function createUsersTable()
    {
        $table = $this->prefix . 'users';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT(20) UNSIGNED NOT NULL,
            kyc_status ENUM('pending','approved','rejected') DEFAULT 'pending',
            kyc_submitted_at DATETIME DEFAULT NULL,
            kyc_approved_at DATETIME DEFAULT NULL,
            kyc_documents TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_id (wp_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        dbDelta($sql);
    }

    private function createMerchantsTable()
    {
        $table = $this->prefix . 'merchants';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            wp_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            total_sales DECIMAL(15,2) DEFAULT 0,
            received_amount DECIMAL(15,2) DEFAULT 0,
            pending_amount DECIMAL(15,2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY wp_user_id (wp_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        dbDelta($sql);
    }

    private function createCreditAccountsTable()
    {
        $table = $this->prefix . 'credit_accounts';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            credit_limit DECIMAL(15,2) NOT NULL DEFAULT 0,
            available_credit DECIMAL(15,2) NOT NULL DEFAULT 0,
            status ENUM('pending','active','blocked') DEFAULT 'pending',
            installment_plan_id BIGINT(20) UNSIGNED DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        dbDelta($sql);
    }

    private function createInstallmentPlansTable()
    {
        $table = $this->prefix . 'installment_plans';
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            months INT(11) NOT NULL,
            interest_rate DECIMAL(10,2) DEFAULT 0,
            penalty_rate DECIMAL(10,2) DEFAULT 0,
            reminder_days INT(11) DEFAULT 3,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        dbDelta($sql);
    }

    // سایر متدها را هم به همین سبک (حذف :void و استفاده از dbDelta) اصلاح کنید
}