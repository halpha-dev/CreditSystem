<?php
if (!defined('ABSPATH')) {
    exit;
}

class TransactionManager
{
    /**
     * @var wpdb
     */
    protected $wpdb;

    /**
     * if in transacion
     */
    protected bool $inTransaction = false;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
    }

    /**
     * begin transacion database
     */
    public function begin(): void
    {
        if ($this->inTransaction) {
            return;
        }

        // make sure of InnoDB
        $this->wpdb->query('START TRANSACTION');
        $this->inTransaction = true;
    }

    /**
     * finall comit tranaction
     */
    public function commit(): void
    {
        if (!$this->inTransaction) {
            return;
        }

        $this->wpdb->query('COMMIT');
        $this->inTransaction = false;
    }

    /**
     * rolback change of transaction
     */
    public function rollback(): void
    {
        if (!$this->inTransaction) {
            return;
        }

        $this->wpdb->query('ROLLBACK');
        $this->inTransaction = false;
    }

    /**
     * run secure a callback in transaction
     *
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    public function run(callable $callback)
    {
        $this->begin();

        try {
            $result = $callback();

            if ($result === false) {
                throw new Exception('Transaction failed');
            }

            $this->commit();
            return $result;

        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * check db suppurt transaction or not
     */
    public function supportsTransactions(): bool
    {
        $engine = $this->wpdb->get_var(
            "SELECT ENGINE
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             LIMIT 1"
        );

        return strtoupper((string) $engine) === 'INNODB';
    }
}