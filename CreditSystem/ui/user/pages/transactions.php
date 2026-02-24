<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_die('برای مشاهده تراکنش‌ها باید وارد حساب کاربری شوید.');
}

$current_user_id = get_current_user_id();
$current_user    = wp_get_current_user();

/* ===============================
   محدودیت نقش
================================= */
if (in_array('administrator', $current_user->roles) || in_array('merchant', $current_user->roles)) {
    wp_die('دسترسی غیرمجاز.');
}

/* ===============================
   بررسی KYC
================================= */
$kyc_status = get_user_meta($current_user_id, 'cs_kyc_status', true);

if ($kyc_status !== 'approved') {
    ?>
    <div class="cs-card">
        <div class="cs-alert cs-alert-warning">
            برای مشاهده تراکنش‌ها، احراز هویت شما باید تایید شود.
        </div>
        <a href="<?php echo esc_url(site_url('/account?tab=kyc-status')); ?>" class="cs-btn cs-btn-primary">
            مشاهده وضعیت احراز هویت
        </a>
    </div>
    <?php
    return;
}

/* ===============================
   دریافت تراکنش‌های کاربر
================================= */

$transactions = get_posts([
    'post_type'      => 'cs_transaction',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
        [
            'key'     => 'cs_user_id',
            'value'   => $current_user_id,
            'compare' => '='
        ]
    ]
]);

/* ===============================
   خلاصه آماری
================================= */

$total_in  = 0;
$total_out = 0;

foreach ($transactions as $trx) {

    $amount = (float) get_post_meta($trx->ID, 'cs_transaction_amount', true);
    $type   = get_post_meta($trx->ID, 'cs_transaction_type', true);
    $status = get_post_meta($trx->ID, 'cs_status', true);

    if ($status !== 'completed') {
        continue;
    }

    if (in_array($type, ['credit', 'refund'])) {
        $total_in += $amount;
    } else {
        $total_out += $amount;
    }
}

?>

<div class="cs-card">

    <h2>تراکنش‌های من</h2>

    <div class="cs-summary-grid">

        <div class="cs-summary-box">
            <span>ورودی‌ها</span>
            <strong class="cs-text-success"><?php echo number_format($total_in); ?> تومان</strong>
        </div>

        <div class="cs-summary-box">
            <span>خروجی‌ها</span>
            <strong class="cs-text-danger"><?php echo number_format($total_out); ?> تومان</strong>
        </div>

    </div>

    <?php if (empty($transactions)) : ?>

        <div class="cs-alert cs-alert-info">
            هنوز هیچ تراکنشی برای شما ثبت نشده است.
        </div>

    <?php else : ?>

        <table class="cs-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نوع</th>
                    <th>مبلغ</th>
                    <th>درگاه</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($transactions as $trx) :

                $amount     = (float) get_post_meta($trx->ID, 'cs_transaction_amount', true);
                $type       = get_post_meta($trx->ID, 'cs_transaction_type', true);
                $status     = get_post_meta($trx->ID, 'cs_status', true);
                $gateway    = get_post_meta($trx->ID, 'cs_gateway', true);
                $paid_at    = get_post_meta($trx->ID, 'cs_paid_at', true);

                $type_label = 'نامشخص';

                switch ($type) {
                    case 'credit':
                        $type_label = 'شارژ اعتبار';
                        break;
                    case 'installment':
                        $type_label = 'پرداخت قسط';
                        break;
                    case 'penalty':
                        $type_label = 'پرداخت جریمه';
                        break;
                    case 'refund':
                        $type_label = 'بازگشت وجه';
                        break;
                }

            ?>

                <tr>

                    <td><?php echo esc_html($trx->ID); ?></td>

                    <td><?php echo esc_html($type_label); ?></td>

                    <td><?php echo number_format($amount); ?> تومان</td>

                    <td><?php echo esc_html($gateway ? $gateway : '-'); ?></td>

                    <td>
                        <?php
                        if (!empty($paid_at)) {
                            echo esc_html(date('Y/m/d H:i', strtotime($paid_at)));
                        } else {
                            echo esc_html(get_the_date('Y/m/d H:i', $trx));
                        }
                        ?>
                    </td>

                    <td>
                        <?php if ($status === 'completed') : ?>
                            <span class="cs-badge cs-badge-success">موفق</span>
                        <?php elseif ($status === 'pending') : ?>
                            <span class="cs-badge cs-badge-warning">در انتظار</span>
                        <?php elseif ($status === 'failed') : ?>
                            <span class="cs-badge cs-badge-danger">ناموفق</span>
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
