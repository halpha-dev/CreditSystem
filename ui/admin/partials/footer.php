<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('cs_settings', []);
$system_enabled = isset($settings['enable_credit_system']) ? (bool)$settings['enable_credit_system'] : true;

$plugin_version = defined('CS_VERSION') ? CS_VERSION : '1.0.0';
$current_user = wp_get_current_user();
?>

<footer class="cs-admin-footer">

    <div class="cs-footer-inner">

        <div class="cs-footer-left">
            <strong>Credit System</strong>
            <span class="cs-version">
                نسخه <?php echo esc_html($plugin_version); ?>
            </span>
        </div>

        <div class="cs-footer-center">

            <span class="cs-system-status <?php echo $system_enabled ? 'active' : 'inactive'; ?>">
                وضعیت سیستم:
                <?php echo $system_enabled ? 'فعال' : 'غیرفعال'; ?>
            </span>

            <?php if (current_user_can('manage_options')): ?>
                <span class="cs-footer-separator">|</span>

                <a href="<?php echo esc_url(admin_url('admin.php?page=cs-settings')); ?>">
                    تنظیمات
                </a>
            <?php endif; ?>

        </div>

        <div class="cs-footer-right">
            <span class="cs-user">
                <?php echo esc_html($current_user->display_name); ?>
            </span>
        </div>

    </div>

</footer>

<?php
/**
 * Hook for extending admin footer
 * Allows adding:
 * - Custom JS
 * - Modal containers
 * - Global notifications
 * - Reminder alerts
 */
do_action('cs_admin_footer');
?>

<?php wp_footer(); ?>

</div> <!-- .cs-admin-wrapper -->