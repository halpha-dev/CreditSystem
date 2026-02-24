<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

    </div> <!-- پایان cs-user-content -->
</div> <!-- پایان cs-user-wrapper -->

<footer class="cs-user-footer">

    <div class="cs-footer-inner">

        <div class="cs-footer-left">
            © <?php echo date('Y'); ?>
            <?php echo esc_html(get_bloginfo('name')); ?>
        </div>

        <div class="cs-footer-right">
            <span>سیستم مدیریت اعتبار و اقساط</span>
        </div>

    </div>

</footer>

<?php
/* ===============================
   اسکریپت‌های مخصوص پنل کاربر
================================= */

wp_enqueue_script(
    'cs-user-js',
    plugin_dir_url(dirname(__DIR__)) . 'assets/js/user.js',
    ['jquery'],
    '1.0.0',
    true
);

/* در صورت نیاز به اسکریپت اعتبار */
wp_enqueue_script(
    'cs-credit-code-js',
    plugin_dir_url(dirname(__DIR__)) . 'assets/js/credit-code.js',
    ['jquery'],
    '1.0.0',
    true
);

wp_footer();
?>