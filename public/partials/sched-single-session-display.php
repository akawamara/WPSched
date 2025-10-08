<?php
/**
 * Single session display template
 * 
 * @var object $session - Session data object
 * @var array $session_speakers - Array of speakers for this session
 * @var string $event_color - Color for the event type
 */

if (!$session) {
    echo '<div class="sched-session-error">Session not found.</div>';
    return;
}

$event_color = $plugin_instance->get_event_type_color($session->event_type);
?>

<div class="sched-single-session-container">
    <!-- Session Header -->
    <div class="sched-session-header-single">
        <div class="session-type-large">
            <?php if (!empty($session->event_type)): ?>
                <span class="session-type-badge" style="background-color: <?php echo esc_attr($event_color); ?>;">
                    <?php echo esc_html($session->event_type); ?>
                </span>
            <?php endif; ?>
            
            <?php if ($session->pinned == "Y"): ?>
                <span class="session-featured-badge">Featured</span>
            <?php endif; ?>
        </div>
        
        <div class="session-info-large">
            <h1 class="session-name-large"><?php echo esc_html($session->event_name); ?></h1>
            
            <?php if (!empty($session->event_start_time)): ?>
                <div class="session-time-large">
                    <?php echo esc_html($session->event_start_time); ?>
                    <?php if (!empty($session->event_start_weekday) && !empty($session->event_start_month) && !empty($session->event_start_day)): ?>
                        on <?php echo esc_html($session->event_start_weekday . ', ' . $session->event_start_month . ' ' . $session->event_start_day); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($session->venue)): ?>
                <div class="session-venue-large"><?php echo esc_html($session->venue); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Session Description -->
    <?php if (!empty($session->event_description)): ?>
        <div class="session-description-section">
            <h3>About This Session</h3>
            <div class="session-description-content">
                <?php echo wp_kses_post($session->event_description); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Session Speakers -->
    <?php if (!empty($session_speakers)): ?>
        <div class="session-speakers-section">
            <h3>
                <?php if (count($session_speakers) === 1): ?>
                    Speaker
                <?php else: ?>
                    Speakers
                <?php endif; ?>
            </h3>
            <div class="session-speakers-grid">
                <?php foreach ($session_speakers as $speaker): ?>
                    <div class="session-speaker-card">
                        <div class="session-speaker-header">
                            <h4 class="session-speaker-name">
                                <a href="<?php echo esc_url($plugin_instance->get_speaker_page_url($speaker->speaker_username)); ?>">
                                    <?php echo esc_html($speaker->speaker_name); ?>
                                </a>
                            </h4>
                            
                            <div class="session-speaker-meta">
                                <?php if (!empty($speaker->speaker_position)): ?>
                                    <div class="speaker-position">
                                        <?php echo esc_html($speaker->speaker_position); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($speaker->speaker_company)): ?>
                                    <div class="speaker-company-left">
                                        <?php echo esc_html($speaker->speaker_company); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>