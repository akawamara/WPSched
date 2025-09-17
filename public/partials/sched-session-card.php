<?php

/**
 * Individual Session Card Template
 * 
 * @var object $session - Session data object
 * @var array $speakers - Array of speakers for this session
 * @var string $event_color - Color for the event type
 */

if (!isset($session)) {
    return;
}
?>

<div class="sched-session-card" data-event-type="<?php echo esc_attr(strtolower($session->event_type)); ?>">

    <!-- Session time and type header -->
    <div class="sched-session-meta">
        <div class="session-time-small">
            <span class="time-text"><?php echo esc_html($session->event_start_time); ?></span>
        </div>

        <?php if ($session->event_type): ?>
            <div class="session-type-badge" style="background-color: <?php echo $event_color; ?>">
                <?php echo esc_html($session->event_type); ?>
            </div>
        <?php endif; ?>

        <?php if ($session->pinned == "Y"): ?>
            <div class="session-pinned">Featured</div>
        <?php endif; ?>
    </div>

    <!-- Session title -->
    <div class="sched-session-header" style="border-left: 4px solid <?php echo $event_color; ?>">
        <h4 class="session-title"><?php echo esc_html($session->event_name); ?></h4>
    </div>

    <!-- Session details -->
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

        <!-- Speakers -->
        <?php if ($speakers): ?>
            <div class="session-speakers">
                <div class="speakers-label">Speakers</div>
                <div class="speakers-list">
                    <?php foreach ($speakers as $speaker): ?>
                        <div class="speaker-chip">

                            <a href="<?php echo esc_url(site_url('/speakers/' . urlencode($speaker->speaker_username))); ?>" class="speaker-link" data-speaker="<?php echo esc_attr($speaker->speaker_username); ?>" title="<?php echo esc_attr($speaker->speaker_name); ?>">
                                <?php echo esc_html($speaker->speaker_name); ?>
                            </a>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>