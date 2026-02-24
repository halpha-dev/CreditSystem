<?php
// we whell be free from this world.


if (!defined('ABSPATH')) {
exit;
}


/**
* Plugin Versioning
*/
define('CS_VERSION', '1.0.0');
define('CS_DB_VERSION', '1.0.0');


/**
* Paths & URLs
*/
define('CS_PLUGIN_FILE', dirname(__DIR__) . '/credit-system.php');
define('CS_PLUGIN_DIR', plugin_dir_path(CS_PLUGIN_FILE));
define('CS_PLUGIN_URL', plugin_dir_url(CS_PLUGIN_FILE));
define('CS_CONFIG_DIR', CS_PLUGIN_DIR . 'config/');
define('CS_INCLUDES_DIR', CS_PLUGIN_DIR . 'includes/');
define('CS_ASSETS_DIR', CS_PLUGIN_DIR . 'assets/');
define('CS_TEMPLATES_DIR', CS_PLUGIN_DIR . 'templates/');


/**
* Database
*/
global $wpdb;
define('CS_DB_PREFIX', $wpdb->prefix . 'cs_');


define('CS_TABLE_CREDITS', CS_DB_PREFIX . 'credits');
define('CS_TABLE_CODES', CS_DB_PREFIX . 'codes');
define('CS_TABLE_TRANSACTIONS', CS_DB_PREFIX . 'transactions');
define('CS_TABLE_MERCHANTS', CS_DB_PREFIX . 'merchants');
define('CS_TABLE_INSTALL_LOG', CS_DB_PREFIX . 'install_log');


/**
* Credit & Code Rules
*/
define('CS_CODE_LENGTH', 16); // 16-digit unique code
define('CS_CODE_EXPIRY_MINUTES', 15); // Code validity window
define('CS_MAX_ACTIVE_CODES_PER_USER', 3); // Anti-abuse


define('CS_CREDIT_CURRENCY', 'IRR'); // Rial, change if needed


define('CS_MIN_CREDIT_AMOUNT', 100000); // Minimum credit amount
define('CS_MAX_CREDIT_AMOUNT', 1000000000); // Safety ceiling


/**
* Security
*/
define('CS_CODE_HASH_ALGO', 'sha256');
define('CS_NONCE_ACTION', 'cs_secure_action');
define('CS_RATE_LIMIT_WINDOW', 60); // seconds
define('CS_RATE_LIMIT_MAX', 30); // requests per window


/**
* Status Enums
*/
define('CS_CODE_STATUS_UNUSED', 'unused');
define('CS_CODE_STATUS_USED', 'used');
define('CS_CODE_STATUS_EXPIRED', 'expired');
define('CS_CODE_STATUS_CANCELLED', 'cancelled');


define('CS_TX_STATUS_PENDING', 'pending');
define('CS_TX_STATUS_COMPLETED', 'completed');
define('CS_TX_STATUS_FAILED', 'failed');


/**
* Roles & Capabilities
*/
define('CS_ROLE_MERCHANT', 'cs_merchant');
define('CS_ROLE_CREDIT_ADMIN', 'cs_credit_admin');


/**
* Logging & Debug & info
*/
define('CS_LOG_ENABLED', true);
define('CS_LOG_LEVEL', 'warning'); // debug | info | warning | error