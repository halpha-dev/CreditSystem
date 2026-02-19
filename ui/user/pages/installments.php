<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_die('برای مشاهده اقساط باید وارد حساب کاربری شوید.');
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
    echo 'برای مشاهده اقساط، احراز هویت شما باید تایید شود.';
    echo '</div>';
    echo '<a href="' . esc_url(site_url('/account?tab=kyc-status')) . '" class="cs-btn cs-btn-primary">مشاهده وضعیت احراز هویت</a>';
    echo '</div>';
    return;
}

/* ===============================
   دریافت اقساط کاربر
================================= */

$installments = get_posts([
    'post_type'   => 'cs_installment',
    'numberposts' => -1,
    'orderby'     => 'meta_value',
    'meta_key'    => 'cs_due_date',
    'order'       => 'ASC',
    'meta_query'  => [
        [
            'key'     => 'cs_user_id',
            'value'   => $current_user_id,
            'compare' => '='
        ]
    ]
]);

/* ===============================
   محاسبه خلاصه وضعیت
================================= */

$total_amount   = 0;
$total_paid     = 0;
$total_overdue  = 0;

foreach ($installments as $inst) {
    $amount = (float) get_post_meta($inst->ID, 'cs_installment_amount', true);
    $status = get_post_meta($inst->ID, 'cs_status', true);

    $total_amount += $amount;

    if ($status === 'paid') {
        $total_paid += $amount;
    }

    if ($status === 'overdue') {
        $total_overdue += $amount;
    }
}

?>

<div class="cs-card">

    <h2>اقساط من</h2>

    <div class="cs-summary-grid">

        <div class="cs-summary-box">
            <span>مجموع اقساط</span>
            <strong><?php echo number_format($total_amount); ?> تومان</strong>
        </div>

        <div class="cs-summary-box">
            <span>پرداخت شده</span>
            <strong class="cs-text-success"><?php echo number_format($total_paid); ?> تومان</strong>
        </div>

        <div class="cs-summary-box">
            <span>معوقه</span>
            <strong class="cs-text-danger"><?php echo number_format($total_overdue); ?> تومان</strong>
        </div>

    </div>

    <?php if (empty($installments)) : ?>

        <div class="cs-alert cs-alert-info">
            شما در حال حاضر هیچ قسطی ندارید.
        </div>

    <?php else : ?>

        <table class="cs-table">
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>مبلغ</th>
                    <th>سررسید</th>
                    <th>جریمه</th>
                    <th>تاریخ پرداخت</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($installments as $inst) :

                $amount      = (float) get_post_meta($inst->ID, 'cs_installment_amount', true);
                $due_date    = get_post_meta($inst->ID, 'cs_due_date', true);
                $status      = get_post_meta($inst->ID, 'cs_status', true);
                $paid_at     = get_post_meta($inst->ID, 'cs_paid_at', true);
                $penalty     = (float) get_post_meta($inst->ID, 'cs_penalty_amount', true);

            ?>

                <tr>
                    <td><?php echo esc_html($inst->ID); ?></td>

                    <td><?php echo number_format($amount); ?> تومان</td>

                    <td>
                        <?php
                        if (!empty($due_date)) {
                            echo esc_html(date('Y/m/d', strtotime($due_date)));
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>

                    <td>
                        <?php
                        if ($penalty > 0) {
                            echo '<span class="cs-text-danger">' . number_format($penalty) . ' تومان</span>';
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>

                    <td>
                        <?php
                        if ($status === 'paid' && !empty($paid_at)) {
                            echo esc_html(date('Y/m/d H:i', strtotime($paid_at)));
                        } else {
                            echo '-';
                        }
                        ?>
                    </td>

                    <td>
                        <?php if ($status === 'paid') : ?>
                            <span class="cs-badge cs-badge-success">پرداخت شده</span>

                        <?php elseif ($status === 'pending') : ?>
                            <span class="cs-badge cs-badge-warning">در انتظار پرداخت</span>

                        <?php elseif ($status === 'overdue') : ?>
                            <span class="cs-badge cs-badge-danger">معوقه</span>

                        <?php else : ?>
                            <span class="cs-badge">نامشخص</span>
                        <?php endif; ?>
                    </td>
                </tr>

            <?php endforeach; ?>

            </tbody>
        </table>

    <?php endif; ?>

</div>
