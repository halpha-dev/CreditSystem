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
// Autoloader 
// --------------------------------------------------
spl_autoload_register(function ($class) {
    if (strpos($class, 'CS_') !== 0) return;

    $path = CS_PLUGIN_DIR . 'includes/' . strtolower(str_replace('CS_', '', $class)) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

// --------------------------------------------------
// Activation / Deactivation
// --------------------------------------------------
register_activation_hook(__FILE__, ['CS_Installer', 'activate']);
register_deactivation_hook(__FILE__, ['CS_Installer', 'deactivate']);
d
// --------------------------------------------------
// Bootstrap Core
// --------------------------------------------------
add_action('plugins_loaded', function () {

    // for translate
    load_plugin_textdomain('credit-system', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // core
    CS_Core::instance();
});

// --------------------------------------------------
// Helper (for fast accessibility)
// --------------------------------------------------
function cs() {
    return CS_Core::instance();
}
