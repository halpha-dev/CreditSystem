<?php
/**
 * Plugin Name: Credit System Core
 * Description: سیستم اختصاصی اعتبار، چوب خط
 * Version: 1.0.0
 * Author: hadi sajjadi
 */

if (!defined('ABSPATH')) exit;

// --------------------------------------------------
// Constants
// --------------------------------------------------
define('CS_VERSION', '1.0.0');
define('CS_PLUGIN_FILE', __FILE__);
define('CS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CS_PLUGIN_URL', plugin_dir_url(__FILE__));

// --------------------------------------------------
// Autoloader جدید با پشتیبانی Namespace (prefix: CreditSystem\)
// --------------------------------------------------
spl_autoload_register(function ($class) {
    $prefix   = 'CreditSystem\\';
    $base_dir = CS_PLUGIN_DIR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// --------------------------------------------------

// --------------------------------------------------
// Activation / Deactivation
// --------------------------------------------------
register_activation_hook(__FILE__, ['CS_Installer', 'activate']);
register_deactivation_hook(__FILE__, ['CS_Installer', 'deactivate']);

//register Menu 

define('CS_UI_PATH', plugin_dir_path(__FILE__) . 'ui/');

require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/user-router.php';
require_once plugin_dir_path(__FILE__) . 'includes/merchant-router.php';


// --------------------------------------------------
// Bootstrap Core
// --------------------------------------------------
add_action('plugins_loaded', function () {

    // for translate
    load_plugin_textdomain('credit-system', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // core
    if (class_exists('CreditSystem\\Core') || class_exists('CS_Core')) {
        CS_Core::instance();   // یا نسخه namespaced اگر ساختی
    }
});

// --------------------------------------------------
// Helper
// --------------------------------------------------
function cs() {
    return CS_Core::instance();
}