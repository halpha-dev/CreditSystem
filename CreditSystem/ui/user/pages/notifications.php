<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    wp_die('برای مشاهده اعلان‌ها باید وارد حساب کاربری شوید.');
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
            برای مشاهده اعلان‌ها، احراز هویت شما باید تایید شود.
        </div>
        <a href="<?php echo esc_url(site_url('/account?tab=kyc-status')); ?>" class="cs-btn cs-btn-primary">
            مشاهده وضعیت احراز هویت
        </a>
    </div>
    <?php
    return;
}

/* ===============================
   علامت‌گذاری به عنوان خوانده شده
================================= */

if (isset($_GET['mark_read'])) {

    $notification_id = intval($_GET['mark_read']);

    if (get_post_type($notification_id) === 'cs_notification') {

        $owner = get_post_meta($notification_id, 'cs_user_id', true);

        if ((int)$owner === (int)$current_user_id) {
            update_post_meta($notification_id, 'cs_is_read', 1);
        }
    }

    wp_redirect(remove_query_arg('mark_read'));
    exit;
}

/* ===============================
   دریافت اعلان‌ها
================================= */

$notifications = get_posts([
    'post_type'      => 'cs_notification',
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

$unread_count = 0;

foreach ($notifications as $note) {
    if (!get_post_meta($note->ID, 'cs_is_read', true)) {
        $unread_count++;
    }
}

?>

<div class="cs-card">

    <h2>اعلان‌ها</h2>

    <?php if ($unread_count > 0) : ?>
        <div class="cs-alert cs-alert-info">
            <?php echo $unread_count; ?> اعلان خوانده نشده دارید.
        </div>
    <?php endif; ?>

    <?php if (empty($notifications)) : ?>

        <div class="cs-alert cs-alert-info">
            هنوز هیچ اعلانی برای شما ثبت نشده است.
        </div>

    <?php else : ?>

        <ul class="cs-notification-list">

        <?php foreach ($notifications as $note) :

            $type     = get_post_meta($note->ID, 'cs_notification_type', true);
            $is_read  = get_post_meta($note->ID, 'cs_is_read', true);
            $date     = get_the_date('Y/m/d H:i', $note);

            $type_label = 'سیستمی';
            $badge_class = 'cs-badge';

            switch ($type) {
                case 'reminder':
                    $type_label  = 'یادآوری قسط';
                    $badge_class = 'cs-badge-warning';
                    break;

                case 'penalty':
                    $type_label  = 'جریمه';
                    $badge_class = 'cs-badge-danger';
                    break;

                case 'credit':
                    $type_label  = 'اعتبار';
                    $badge_class = 'cs-badge-success';
                    break;
            }

        ?>

            <li class="cs-notification-item <?php echo !$is_read ? 'cs-unread' : ''; ?>">

                <div class="cs-notification-header">
                    <span class="cs-badge <?php echo esc_attr($badge_class); ?>">
                        <?php echo esc_html($type_label); ?>
                    </span>

                    <span class="cs-notification-date">
                        <?php echo esc_html($date); ?>
                    </span>
                </div>

                <div class="cs-notification-content">
                    <?php echo wp_kses_post($note->post_content); ?>
                </div>

                <?php if (!$is_read) : ?>
                    <div class="cs-notification-actions">
                        <a href="<?php echo esc_url(add_query_arg('mark_read', $note->ID)); ?>" class="cs-btn cs-btn-sm">
                            علامت‌گذاری به عنوان خوانده شده
                        </a>
                    </div>
                <?php endif; ?>

            </li>

        <?php endforeach; ?>

        </ul>

    <?php endif; ?>

</div>
