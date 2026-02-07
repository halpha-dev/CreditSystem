<?php
namespace CreditSystem\Database;

if (!defined('ABSPATH')) {
    exit;
}

class Migrations
{
    protected \wpdb $wpdb;
    protected string $charset_collate;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    public function run(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $this->create_credit_accounts_table();
        $this->create_credit_codes_table();
        $this->create_installments_table();
        $this->create_transactions_table();
        $this->create_merchants_table();
        $this->create_merchant_transactions_table();
    }

    /* ======================================================
     * Credit Accounts
     * ====================================================== */

    protected function create_credit_accounts_table(): void
    {
        $table = $this->wpdb->prefix . 'credit_accounts';

        $sql = "
        CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,

            credit_limit DECIMAL(12,2) NOT NULL,
            used_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            available_amount DECIMAL(12,2) NOT NULL,

            installment_count INT UNSIGNED NOT NULL,
            monthly_installment_amount DECIMAL(12,2) NOT NULL,

            status VARCHAR(20) NOT NULL DEFAULT 'active',

            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,

            PRIMARY KEY (id),
            UNIQUE KEY user_unique (user_id),
            KEY status_idx (status)
        ) $this->charset_collate;
        ";

        dbDelta($sql);
    }

    /* ======================================================
     * Credit Codes (16-digit)
     * ====================================================== */

    protected function create_credit_codes_table(): void
    {
        $table = $this->wpdb->prefix . 'credit_codes';

        $sql = "
        CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            code CHAR(16) NOT NULL,

            user_id BIGINT UNSIGNED NOT NULL,
            merchant_id BIGINT UNSIGNED NOT NULL,

            amount DECIMAL(12,2) NOT NULL,

            status VARCHAR(20) NOT NULL DEFAULT 'unused',
            expires_at DATETIME NOT NULL,

            used_at DATETIME NULL,
            created_at DATETIME NOT NULL,

            PRIMARY KEY (id),
            UNIQUE KEY code_unique (code),
            KEY user_idx (user_id),
            KEY merchant_idx (merchant_id),
            KEY status_idx (status),
            KEY expires_idx (expires_at)
        ) $this->charset_collate;
        ";

        dbDelta($sql);
    }

    /*======================================================
     * Installments
     * ====================================================== */

    protected function create_installments_table(): void
    {
        $table = $this->wpdb->prefix . 'installments';

        $sql = "
        CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            credit_account_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,

            due_date DATE NOT NULL,

            base_amount DECIMAL(12,2) NOT NULL,
            penalty_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(12,2) NOT NULL,

            status VARCHAR(20) NOT NULL DEFAULT 'unpaid',

            paid_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,

            PRIMARY KEY (id),
            KEY account_idx (credit_account_id),
            KEY user_idx (user_id),
            KEY status_idx (status),
            KEY due_date_idx (due_date)
        ) $this->charset_collate;
        ";

        dbDelta($sql);
    }

    /* ======================================================
     * User Transactions (ledger)
     * ====================================================== */

    protected function create_transactions_table(): void
    {
        $table = $this->wpdb->prefix . 'credit_transactions';

        $sql = "
        CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            user_id BIGINT UNSIGNED NOT NULL,
            credit_account_id BIGINT UNSIGNED NOT NULL,

            type VARCHAR(30) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,

            reference_id BIGINT UNSIGNED NULL,
            reference_type VARCHAR(30) NULL,

            created_at DATETIME NOT NULL,

            PRIMARY KEY (id),
            KEY user_idx (user_id),
            KEY account_idx (credit_account_id),
            KEY type_idx (type)
        ) $this->charset_collate;
        ";

        dbDelta($sql);
    }

    /*======================================================
     * Merchants
     * ====================================================== */

    protected function create_merchants_table(): void
    {
        $table = $this->wpdb->prefix . 'merchants';

        $sql = "
        CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,

            name VARCHAR(190) NOT NULL,

            total_sales_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            total_pending_amount DECIMAL(14,2) NOT NULL DEFAULT 0,

            status VARCHAR(20) NOT NULL DEFAULT 'active',

            created_at DATETIME NOT NULL,
            updated_at DATETIME NULL,

            PRIMARY KEY (id),
            UNIQUE KEY user_unique (user_id),
            KEY status_idx (status)
        ) $this->charset_collate;
        ";

        dbDelta($sql);
    }

    /* ======================================================
     * Merchant Transactions (settlement)
     * ====================================================== */

    protected function create_merchant_transactions_table(): void
    {
        $table = $this->wpdb->prefix . 'merchant_transactions';

        $sql = "
        CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,

            merchant_id BIGINT UNSIGNED NOT NULL,
            credit_code_id BIGINT UNSIGNED NOT NULL,

            amount DECIMAL(12,2) NOT NULL,

            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            paid_at DATETIME NULL,

            created_at DATETIME NOT NULL,

            PRIMARY KEY (id),
            KEY merchant_idx (merchant_id),
            KEY code_idx (credit_code_id),
            KEY status_idx (status)
        ) $this->charset_collate;
        ";

        dbDelta($sql);
    }
}

