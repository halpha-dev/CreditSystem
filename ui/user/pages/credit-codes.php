<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_die('برای مشاهده کردیت‌کدها باید وارد حساب کاربری شوید.');
}

$current_user_id = get_current_user_id();
$user            = wp_get_current_user();

/* ===============================
   محدودیت نقش
================================= */
if (in_array('administrator', $user->roles) || in_array('merchant', $user->roles)) {
    wp_die('این بخش فقط برای کاربران عادی فعال است.');
}

/* ===============================
   بررسی KYC
================================= */
$kyc_status = get_user_meta($current_user_id, 'cs_kyc_status', true);

if ($kyc_status !== 'approved') {
    echo '<div class="cs-card">';
    echo '<div class="cs-alert cs-alert-warning">';
    echo 'برای استفاده از کردیت‌کد، احراز هویت شما باید تایید شده باشد.';
    echo '</div>';
    echo '<a href="' . esc_url(site_url('/user/kyc-status')) . '" class="cs-btn cs-btn-primary">مشاهده وضعیت احراز هویت</a>';
    echo '</div>';
    return;
}

/* ===============================
   دریافت کردیت‌کدهای کاربر
================================= */

$credit_codes = get_posts([
    'post_type'   => 'cs_credit_code',
    'meta_key'    => 'cs_user_id',
    'meta_value'  => $current_user_id,
    'numberposts' => -1,
    'orderby'     => 'date',
    'order'       => 'DESC'
]);

?>

<div class="cs-card">

    <h2>کردیت‌کدهای من</h2>

    <?php if (empty($credit_codes)) : ?>

        <div class="cs-alert cs-alert-info">
            شما هنوز هیچ کردیت‌کدی دریافت نکرده‌اید.
        </div>

    <?php else : ?>

        <table class="cs-table">
            <thead>
                <tr>
                    <th>کد</th>
                    <th>مبلغ</th>
                    <th>تاریخ ایجاد</th>
                    <th>تاریخ انقضا</th>
                    <th>وضعیت</th>
                    <th>جزئیات</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($credit_codes as $code) :

                $amount     = (float) get_post_meta($code->ID, 'cs_code_amount', true);
                $status     = get_post_meta($code->ID, 'cs_code_status', true);
                $expires_at = get_post_meta($code->ID, 'cs_code_expires_at', true);
                $used_at    = get_post_meta($code->ID, 'cs_code_used_at', true);
                $merchant   = get_post_meta($code->ID, 'cs_merchant_id', true);

                ?>

                <tr>
                    <td><strong><?php echo esc_html($code->post_title); ?></strong></td>

                    <td><?php echo number_format($amount); ?> تومان</td>

                    <td><?php echo esc_html(date('Y/m/d', strtotime($code->post_date))); ?></td>

                    <td>
                        <?php 
                        if (!empty($expires_at)) {
                            echo esc_html(date('Y/m/d', strtotime($expires_at)));
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>

                    <td>
                        <?php if ($status === 'active') : ?>
                            <span class="cs-badge cs-badge-success">فعال</span>
                        <?php elseif ($status === 'used') : ?>
                            <span class="cs-badge cs-badge-warning">استفاده شده</span>
                        <?php elseif ($status === 'expired') : ?>
                            <span class="cs-badge cs-badge-danger">منقضی شده</span>
                        <?php elseif ($status === 'cancelled') : ?>
                            <span class="cs-badge cs-badge-danger">لغو شده</span>
                        <?php else : ?>
                            <span class="cs-badge">نامشخص</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <button class="cs-btn cs-btn-secondary cs-toggle-details" data-id="<?php echo esc_attr($code->ID); ?>">
                            مشاهده
                        </button>
                    </td>
                </tr>

                <tr class="cs-code-details" id="code-<?php echo esc_attr($code->ID); ?>" style="display:none;">
                    <td colspan="6">

                        <div class="cs-code-detail-box">

                            <p><strong>شناسه:</strong> <?php echo esc_html($code->ID); ?></p>

                            <?php if (!empty($merchant)) : ?>
                                <p><strong>فروشنده:</strong>
                                    <?php 
                                    $merchant_user = get_user_by('id', $merchant);
                                    echo esc_html($merchant_user ? $merchant_user->display_name : '-');
                                    ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($status === 'used' && !empty($used_at)) : ?>
                                <p><strong>تاریخ استفاده:</strong>
                                    <?php echo esc_html(date('Y/m/d H:i', strtotime($used_at))); ?>
                                </p>
                            <?php endif; ?>

                        </div>

                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.cs-toggle-details');

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const row = document.getElementById('code-' + id);

            if (row.style.display === 'none') {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    });
});
</script>
