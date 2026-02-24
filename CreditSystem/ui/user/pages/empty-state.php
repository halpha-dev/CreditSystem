<?php
if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| پارامترهای قابل تنظیم
|--------------------------------------------------------------------------
| $args = [
|   'icon'        => '📄',
|   'title'       => 'عنوان',
|   'description' => 'توضیح کوتاه',
|   'button_text' => 'متن دکمه',
|   'button_url'  => 'لینک',
| ]
*/

$defaults = [
    'icon'        => '📭',
    'title'       => 'موردی یافت نشد',
    'description' => 'در حال حاضر اطلاعاتی برای نمایش وجود ندارد.',
    'button_text' => '',
    'button_url'  => '',
];

$args = isset($args) && is_array($args) ? wp_parse_args($args, $defaults) : $defaults;

$icon        = $args['icon'];
$title       = $args['title'];
$description = $args['description'];
$button_text = $args['button_text'];
$button_url  = $args['button_url'];
?>

<div class="cs-empty-state">

    <div class="cs-empty-icon">
        <?php echo esc_html($icon); ?>
    </div>

    <h3 class="cs-empty-title">
        <?php echo esc_html($title); ?>
    </h3>

    <p class="cs-empty-description">
        <?php echo esc_html($description); ?>
    </p>

    <?php if (!empty($button_text) && !empty($button_url)) : ?>
        <div class="cs-empty-action">
            <a href="<?php echo esc_url($button_url); ?>" class="cs-btn cs-btn-primary">
                <?php echo esc_html($button_text); ?>
            </a>
        </div>
    <?php endif; ?>

</div>
