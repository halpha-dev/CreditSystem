<?php
use CreditSystem\Core\Auth;

if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_roles = (array) $current_user->roles;

$is_admin = current_user_can('manage_options');
$is_merchant = in_array('cs_merchant', $user_roles);
$is_customer = in_array('cs_customer', $user_roles);

$settings = get_option('cs_settings', []);
$system_enabled = isset($settings['enable_credit_system']) ? (bool)$settings['enable_credit_system'] : true;

$current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
?>

<div class="cs-admin-header">

    <div class="cs-header-top">

        <div class="cs-logo">
            <h1>Credit System</h1>
        </div>

        <div class="cs-user-info">
            <span class="cs-username">
                <?php echo esc_html($current_user->display_name); ?>
            </span>

            <span class="cs-role">
                <?php
                if ($is_admin) {
                    echo 'ادمین';
                } elseif ($is_merchant) {
                    echo 'فروشنده';
                } else {
                    echo 'کاربر';
                }
                ?>
            </span>

            <a href="<?php echo esc_url(wp_logout_url()); ?>" class="cs-logout">
                خروج
            </a>
        </div>

    </div>

    <?php if (!$system_enabled): ?>
        <div class="cs-system-warning">
            سیستم اعتباری غیرفعال است.
        </div>
    <?php endif; ?>

    <nav class="cs-admin-nav">
        <ul>

            <?php if ($is_admin): ?>
                <li class="<?php echo $current_page === 'cs-dashboard' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-dashboard'); ?>">
                        داشبورد
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-kyc' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-kyc'); ?>">
                        مدیریت KYC
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-installment-plans' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-installment-plans'); ?>">
                        پلن‌های اقساط
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-credit-codes' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-credit-codes'); ?>">
                        کردیت کدها
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-reminders' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-reminders'); ?>">
                        یادآوری و جریمه
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-settings' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-settings'); ?>">
                        تنظیمات
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($is_merchant): ?>
                <li class="<?php echo $current_page === 'cs-merchant-dashboard' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-merchant-dashboard'); ?>">
                        داشبورد فروشنده
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-merchant-plans' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-merchant-plans'); ?>">
                        پلن‌های من
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-merchant-installments' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-merchant-installments'); ?>">
                        اقساط مشتریان
                    </a>
                </li>
            <?php endif; ?>

            <?php if ($is_customer): ?>
                <li class="<?php echo $current_page === 'cs-my-credit' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-my-credit'); ?>">
                        اعتبار من
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-my-installments' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-my-installments'); ?>">
                        اقساط من
                    </a>
                </li>

                <li class="<?php echo $current_page === 'cs-my-kyc' ? 'active' : ''; ?>">
                    <a href="<?php echo admin_url('admin.php?page=cs-my-kyc'); ?>">
                        وضعیت احراز هویت
                    </a>
                </li>
            <?php endif; ?>

        </ul>
    </nav>

</div>
