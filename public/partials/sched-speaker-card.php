<?php
/**
 * Individual Speaker Card Template
 * 
 * @var object $speaker - Speaker data object
 * @var bool $is_featured - Whether this is a featured speaker
 */

if (!isset($speaker)) {
    return;
}
?>

<div class="sched-speaker-card<?php echo $is_featured ? esc_attr(' featured') : ''; ?>">
    <div class="speaker-card-header">
        <div class="speaker-avatar-container">
            <a href="<?php echo esc_url($plugin_instance->get_speaker_page_url($speaker->username)); ?>" title="<?php echo esc_attr($speaker->speaker_name); ?>">
                <?php if ($speaker->speaker_avatar): ?>
                    <img class="speaker-avatar-modern" src="<?php echo esc_url($speaker->speaker_avatar); ?>" alt="avatar for <?php echo esc_attr($speaker->speaker_name); ?>" />
                <?php else: ?>
                    <div class="speaker-avatar-placeholder"><?php echo esc_html($plugin_instance->get_initials($speaker->speaker_name)); ?></div>
                <?php endif; ?>
            </a>
        </div>
        <?php if ($is_featured): ?>
        <div class="speaker-featured-badge">Featured</div>
        <?php endif; ?>
    </div>
    <div class="speaker-card-content">
        <h2 class="speaker-name-modern">
            <a href="<?php echo esc_url($plugin_instance->get_speaker_page_url($speaker->username)); ?>" title="View their profile and schedule">
                <?php echo esc_html($speaker->speaker_name); ?>
            </a>
        </h2>
        <?php if ($speaker->speaker_position): ?>
        <h4 class="speaker-position"><?php echo esc_html($speaker->speaker_position); ?></h4>
        <?php endif; ?>
        <?php if ($speaker->speaker_company): ?>
        <h4 class="speaker-company">
            <span><?php echo esc_html($speaker->speaker_company); ?></span>
        </h4>
        <?php endif; ?>
    </div>
</div>