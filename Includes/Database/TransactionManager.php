<?php
namespace CreditSystem\Database;

if (!defined('ABSPATH')) {
    exit;
}

class TransactionManager
{
    /**
     * @var wpdb
     */
    protected static $wpdb;

    /**
     * if in transacion
     */
    protected static bool $inTransaction = false;

    /**
     * Initialize static wpdb reference
     */
    protected static function init(): void
    {
        if (self::$wpdb === null) {
            global $wpdb;
            self::$wpdb = $wpdb;
        }
    }

    /**
     * begin transacion database
     */
    public static function begin(): void
    {
        self::init();
        if (self::$inTransaction) {
            return;
        }

        // make sure of InnoDB
        self::$wpdb->query('START TRANSACTION');
        self::$inTransaction = true;
    }

    /**
     * finall comit tranaction
     */
    public static function commit(): void
    {
        self::init();
        if (!self::$inTransaction) {
            return;
        }

        self::$wpdb->query('COMMIT');
        self::$inTransaction = false;
    }

    /**
     * rolback change of transaction
     */
    public static function rollback(): void
    {
        self::init();
        if (!self::$inTransaction) {
            return;
        }

        self::$wpdb->query('ROLLBACK');
        self::$inTransaction = false;
    }

    /**
     * run secure a callback in transaction
     *
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    public static function run(callable $callback)
    {
        self::begin();

        try {
            $result = $callback();

            if ($result === false) {
                throw new Exception('Transaction failed');
            }

            self::commit();
            return $result;

        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }
    }

    /**
     * check db suppurt transaction or not
     */
    public static function supportsTransactions(): bool
    {
        self::init();
        $engine = self::$wpdb->get_var(
            "SELECT ENGINE
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
             LIMIT 1"
        );

        return strtoupper((string) $engine) === 'INNODB';
    }
}