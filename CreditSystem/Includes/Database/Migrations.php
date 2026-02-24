<?php

namespace CreditSystem\Includes\Database;

use CreditSystem\Database\TransactionManager;

class Migrations
{
    private \wpdb $wpdb;
    private string $prefix;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->prefix = $wpdb->prefix . 'cs_'; // CreditSystem prefix
    }

    public function migrate(): void
    {
        $this->createUsersTable();
        $this->createCreditAccountsTable();
        $this->createInstallmentPlansTable();
        $this->createInstallmentsTable();
        $this->createCreditCodesTable();
        $this->createMerchantsTable();
        $this->createTransactionsTable();
        $this->createInstallmentPenaltiesTable();
        $this->createInstallmentRemindersTable();
    }

//User Tables

    private function createUsersTable(): void
    {
        $table = $this->prefix . 'users';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                wp_user_id BIGINT(20) UNSIGNED NOT NULL,
                kyc_status ENUM('pending','approved','rejected') DEFAULT 'pending',
                kyc_submitted_at DATETIME DEFAULT NULL,
                kyc_approved_at DATETIME DEFAULT NULL,
                kyc_documents JSON DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id),
                UNIQUE KEY(wp_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
// Credit Accuont Table
    private function createCreditAccountsTable(): void
    {
        $table = $this->prefix . 'credit_accounts';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                credit_limit DECIMAL(15,2) NOT NULL DEFAULT 0,
                available_credit DECIMAL(15,2) NOT NULL DEFAULT 0,
                status ENUM('pending','active','blocked') DEFAULT 'pending',
                installment_plan_id BIGINT(20) UNSIGNED DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id),
                KEY(user_id),
                FOREIGN KEY(user_id) REFERENCES {$this->prefix}users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
//Intallment Plan Table 
    private function createInstallmentPlansTable(): void
    {
        $table = $this->prefix . 'installment_plans';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                months INT(2) NOT NULL,
                description VARCHAR(255) DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
// Installment Plan Table 
    private function createInstallmentsTable(): void
    {
        $table = $this->prefix . 'installments';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                credit_account_id BIGINT(20) UNSIGNED NOT NULL,
                installment_plan_id BIGINT(20) UNSIGNED DEFAULT NULL,
                transaction_id BIGINT(20) UNSIGNED DEFAULT NULL,
                due_date DATE NOT NULL,
                base_amount DECIMAL(15,2) NOT NULL,
                penalty_amount DECIMAL(15,2) DEFAULT 0,
                total_amount DECIMAL(15,2) AS (base_amount + penalty_amount) STORED,
                status ENUM('unpaid','paid','overdue') DEFAULT 'unpaid',
                paid_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id),
                KEY(credit_account_id),
                FOREIGN KEY(credit_account_id) REFERENCES {$this->prefix}credit_accounts(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
//Credit Codes Table
    private function createCreditCodesTable(): void
    {
        $table = $this->prefix . 'credit_codes';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                merchant_id BIGINT(20) UNSIGNED NOT NULL,
                code CHAR(16) NOT NULL,
                amount DECIMAL(15,2) NOT NULL,
                expires_at DATETIME NOT NULL,
                status ENUM('unused','used','expired') DEFAULT 'unused',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id),
                UNIQUE KEY(code),
                KEY(user_id),
                KEY(merchant_id),
                FOREIGN KEY(user_id) REFERENCES {$this->prefix}users(id) ON DELETE CASCADE,
                FOREIGN KEY(merchant_id) REFERENCES {$this->prefix}merchants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
//Create Merchants Table
    private function createMerchantsTable(): void
    {
        $table = $this->prefix . 'merchants';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                wp_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                status ENUM('active','inactive') DEFAULT 'active',
                total_sales DECIMAL(15,2) DEFAULT 0,
                received_amount DECIMAL(15,2) DEFAULT 0,
                pending_amount DECIMAL(15,2) DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id),
                UNIQUE KEY(wp_user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
//Create Tranaction Table 
    private function createTransactionsTable(): void
    {
        $table = $this->prefix . 'transactions';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                wallet_id BIGINT(20) UNSIGNED DEFAULT NULL,
                credit_account_id BIGINT(20) UNSIGNED DEFAULT NULL,
                merchant_id BIGINT(20) UNSIGNED DEFAULT NULL,
                amount DECIMAL(15,2) NOT NULL,
                type ENUM('purchase','installment_payment') NOT NULL,
                status ENUM('success','failed') DEFAULT 'success',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY(id),
                KEY(wallet_id),
                KEY(credit_account_id),
                KEY(merchant_id),
                FOREIGN KEY(credit_account_id) REFERENCES {$this->prefix}credit_accounts(id) ON DELETE CASCADE,
                FOREIGN KEY(merchant_id) REFERENCES {$this->prefix}merchants(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
//Create Installment Penallties
    private function createInstallmentPenaltiesTable(): void
    {
        $table = $this->prefix . 'installment_penalties';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                installment_id BIGINT(20) UNSIGNED NOT NULL,
                daily_rate DECIMAL(5,4) NOT NULL,
                accumulated DECIMAL(15,2) DEFAULT 0,
                last_applied DATETIME DEFAULT NULL,
                PRIMARY KEY(id),
                KEY(installment_id),
                FOREIGN KEY(installment_id) REFERENCES {$this->prefix}installments(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
//Create Installment Remninder table
    private function createInstallmentRemindersTable(): void
    {
        $table = $this->prefix . 'installment_reminders';
        $this->wpdb->query("
            CREATE TABLE IF NOT EXISTS {$table} (
                id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                installment_id BIGINT(20) UNSIGNED NOT NULL,
                remind_at DATETIME NOT NULL,
                sent_at DATETIME DEFAULT NULL,
                channel ENUM('sms','email','push') NOT NULL,
                PRIMARY KEY(id),
                KEY(installment_id),
                FOREIGN KEY(installment_id) REFERENCES {$this->prefix}installments(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }
}