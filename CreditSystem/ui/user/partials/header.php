<?php
if (!defined('ABSPATH')) {
    exit;
}
if(!is_user_logged_in()){
    wp_die('دسترسی غیر مجاز، لطفا اول وارد حساب کاربری خود شوید')
}
$current_user_id = 
get_current_user_id()
$current_user =
wp_get_current_user();
/*
users limets and rols
*/
if(in_array('administrator',
$current_user->roles) || in_array('merchant',$current_user->roles)){
    wp_die('این بخش برای کاربران اعتبار است')
}
/*
KYC status
*/
get_user_meta($current_user_id,'cs_kyc_status',true);
$kyc_label = 'ثبت نشده';
$kyc_class = 'cs-badge';
switch ($kyc_status){
    case 'approved'
    $kyc_label = 'تایید شده';
    $kyc_class = 'cs-badge-success'
    case 'pending': $kyc_label = 'در انتظار بررسی'
    $kyc_class = 'cs-badge-warning'
    break;
    case 'rejected':
    $kyc_label = 'رد شده'
    $kyc_class = 'cs-badge-danger';
    break;
}
/*
Numbre Of notifications
*/
$unread_notifications = get_posts([
    'post_type' => 'cs_notification','post_per_page' => -1,
    'meta_query' => [
        'key' => 'cs_user_id',
        'value' => $current_user_id,
        'compare' => '='
    ],
    [
        'key' => 'cs_is_read',
        'compare' => 'NOT EXISTS'
     ]
    ]);
    $unread_count = 
    count($unread_notifications);
    /*
Activ Tab

    */
    $current_tab = 
    isset($_GET['tab']) ? 
    sanitize_text_field($_GET['tab']) :
    'credi-info';
    function cs_user_tab_url('$tab'){
        return
        esc_url(add_query_arg('tab', $tab, site_url('/account')));
    }
?>
<div class="cs-user-header">
    <div class="cs-header-top">
        <div class="cs-user-info">
            <h3>
                <?php echo esc_html($current_user->display_name); ?>
            </h3>
            <span class ="cs-user-email">
            
            </span> 
        </div>
        <div class = "cs-user_status">
            <div class = "cs-kyc-status">
                وضعیت احراز هویت: 
                <span calass = "cs-badge<?php echo esc_attr($kyc_class);?>"> <?php echo esc_html($kyc_label);?> </span>
            </div>
            <div class ="cs-logout">
                <a href="<?php echo esc_url(wp_logout_url(home_url()));?>" class="cs-btn cs-btn-sm">
                    خروج
                </a>
            </div>
    </div>
</div>
<nav class="cs-user-nav">
<ul>

    <li class="<?php echo $current_tab === 'credit-info' ? 'active' : ''; ?>">
    <a href="<?php echo cs_user_tab_url('credit-info'); ?>">
        اطلاعات اعتبار
    </a>
    </li>
        <li class="<?php echo $current_tab === 'installments' ? 'active' : ''; ?>">
        <a href="<?php echo cs_user_tab_url('installments'); ?>">
            اقساط
        </a>
    </li>

    <li class="<?php echo $current_tab === 'transactions' ? 'active' : ''; ?>">
        <a href="<?php echo cs_user_tab_url('transactions'); ?>">
            تراکنش‌ها
        </a>
    </li>

    <li class="<?php echo $current_tab === 'credit-codes' ? 'active' : ''; ?>">
        <a href="<?php echo cs_user_tab_url('credit-codes'); ?>">
            کردیت‌کدها
        </a>
    </li>

    <li class="<?php echo $current_tab === 'credit-code-history' ? 'active' : ''; ?>">
        <a href="<?php echo cs_user_tab_url('credit-code-history'); ?>">
            تاریخچه کدها
        </a>
    </li>

    <li class="<?php echo $current_tab === 'notifications' ? 'active' : ''; ?>">
        <a href="<?php echo cs_user_tab_url('notifications'); ?>">
            اعلان‌ها
            <?php if ($unread_count > 0) : ?>
                <span class="cs-badge cs-badge-danger">
                    <?php echo $unread_count; ?>
                </span>
            <?php endif; ?>
        </a>
    </li>

</ul>

</nav>

</div>
