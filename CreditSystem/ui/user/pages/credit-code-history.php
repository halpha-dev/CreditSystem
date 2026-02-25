<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_die('برای مشاهده تاریخچه باید وارد حساب کاربری شوید.');
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
    echo 'برای مشاهده تاریخچه اعتبار، احراز هویت شما باید تایید شود.';
    echo '</div>';
    echo '<a href="' . esc_url(site_url('/account?tab=kyc-status')) . '" class="cs-btn cs-btn-primary">مشاهده وضعیت احراز هویت</a>';
    echo '</div>';
    return;
}

/* ===============================
   دریافت کدهای استفاده‌شده
================================= */

$used_codes = get_posts([
    'post_type'   => 'cs_credit_code',
    'numberposts' => -1,
    'orderby'     => 'meta_value',
    'meta_key'    => 'cs_code_used_at',
    'order'       => 'DESC',
    'meta_query'  => [
        [
            'key'     => 'cs_user_id',
            'value'   => $current_user_id,
            'compare' => '='
        ],
        [
            'key'     => 'cs_code_status',
            'value'   => 'used',
            'compare' => '='
        ]
    ]
]);

?>

<div class="cs-card">

    <h2>تاریخچه استفاده از کردیت‌کد</h2>

    <?php if (empty($used_codes)) : ?>

        <div class="cs-alert cs-alert-info">
            هنوز از هیچ کردیت‌کدی استفاده نکرده‌اید.
        </div>

    <?php else : ?>

        <table class="cs-table">
            <thead>
                <tr>
                    <th>کد</th>
                    <th>مبلغ</th>
                    <th>فروشنده</th>
                    <th>تاریخ استفاده</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($used_codes as $code) :

                $amount     = (float) get_post_meta($code->ID, 'cs_code_amount', true);
                $used_at    = get_post_meta($code->ID, 'cs_code_used_at', true);
                $merchant   = get_post_meta($code->ID, 'cs_merchant_id', true);

                $merchant_name = '-';
                if (!empty($merchant)) {
                    $merchant_user = get_user_by('id', $merchant);
                    if ($merchant_user) {
                        $merchant_name = $merchant_user->display_name;
                    }
                }

            ?>

                <tr>
                    <td><strong><?php echo esc_html($code->post_title); ?></strong></td>

                    <td><?php echo number_format($amount); ?> تومان</td>

                    <td><?php echo esc_html($merchant_name); ?></td>

                    <td>
                        <?php
                        if (!empty($used_at)) {
                            echo esc_html(date('Y/m/d H:i', strtotime($used_at)));
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>

                    <td>
                        <span class="cs-badge cs-badge-warning">
                            استفاده شده
                        </span>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

    <?php endif; ?>

</div>
