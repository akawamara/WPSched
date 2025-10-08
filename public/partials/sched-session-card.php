<?php
if (!isset($session)) {
    return;
}

// Use plugin instance to get dynamic session URL
$base_path = $plugin_instance->get_sessions_base_path();
$session_url = esc_url(site_url('/' . $base_path . '/' . urlencode($session->event_id)));
?>

<div class="sched-session-card" data-event-type="<?php echo esc_attr(strtolower($session->event_type)); ?>">

    <div class="sched-session-meta">
        <div class="session-time-small">
            <span class="time-text"><?php echo esc_html($session->event_start_time); ?></span>
        </div>

        <?php if ($session->event_type): ?>
            <div class="session-type-badge" style="background-color: <?php echo esc_attr($event_color); ?>">
                <?php echo esc_html($session->event_type); ?>
            </div>
        <?php endif; ?>

        <?php if ($session->pinned == "Y"): ?>
            <div class="session-pinned">Featured</div>
        <?php endif; ?>
    </div>

    <div class="sched-session-header" style="border-left: 4px solid <?php echo esc_attr($event_color); ?>">
        <h4 class="session-title">
            <a href="<?php echo esc_url($session_url); ?>" class="session-title-link">
                <?php echo esc_html($session->event_name); ?>
            </a>
        </h4>
    </div>

    <div class="sched-session-content">

        <?php if ($session->venue): ?>
            <div class="session-venue-small">
                <span><?php echo esc_html($session->venue); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($session->event_description): ?>
            <div class="session-description">
                <?php echo wp_kses_post($session->event_description); ?>
            </div>
        <?php endif; ?>

        <?php if ($speakers): ?>
            <div class="session-speakers">
                <div class="speakers-label">Speakers</div>
                <div class="speakers-list">
                    <?php foreach ($speakers as $speaker): ?>
                        <div class="speaker-chip">
                            <a href="<?php echo esc_url($plugin_instance->get_speaker_page_url($speaker->speaker_username)); ?>" class="speaker-link" data-speaker="<?php echo esc_attr($speaker->speaker_username); ?>">
                                <?php echo esc_html($speaker->speaker_name); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

</div>