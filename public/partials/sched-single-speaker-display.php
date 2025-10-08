<?php

/**
 * Single speaker display template
 */

if (!$speaker) {
    echo '<div class="sched-speaker-error">Speaker not found.</div>';
    return;
}
?>

<div>
    <!-- Speaker Header -->
    <div class="sched-speaker-header-single">
        <div class="speaker-avatar-large">
            <?php if (!empty($speaker->speaker_avatar)): ?>
                <img src="<?php echo esc_url($speaker->speaker_avatar); ?>"
                    alt="<?php echo esc_attr($speaker->speaker_name); ?>"
                    class="speaker-image-large">
            <?php else: ?>
                <div class="speaker-avatar-placeholder">
                    <span class="avatar-initials">
                        <?php echo esc_html(substr($speaker->speaker_name, 0, 1)); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>

        <div class="speaker-info-large">
            <h1 class="speaker-name-large"><?php echo esc_html($speaker->speaker_name); ?></h1>

            <?php if (!empty($speaker->speaker_position)): ?>
                <div class="speaker-title-large">
                    <?php echo esc_html($speaker->speaker_position); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($speaker->speaker_company)): ?>
                <div class="speaker-company-large"><?php echo esc_html($speaker->speaker_company); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Speaker Bio -->
    <?php if (!empty($speaker->speaker_about)): ?>
        <div class="speaker-bio-section">
            <h3>About</h3>
            <div class="speaker-bio-content">
                <?php echo wp_kses_post($speaker->speaker_about); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Speaker Sessions -->
    <?php if (!empty($speaker_sessions)): ?>
        <div class="speaker-sessions-section">
            <h3>Speaking at <?php echo count($speaker_sessions); ?> session<?php echo count($speaker_sessions) !== 1 ? 's' : ''; ?></h3>
            <div class="speaker-sessions-grid">
                <?php foreach ($speaker_sessions as $session): ?>
                    <div class="speaker-session-card event-type-<?php echo esc_attr(strtolower($session->event_type)); ?>">
                        <div class="session-card-header">

                            <h4 class="session-card-title">
                                <?php echo esc_html($session->event_name); ?>
                            </h4>

                            <div class="session-meta">
                                <?php if (!empty($session->event_type)): ?>
                                    <div class="session-type-badge" style="background-color: <?php echo esc_attr($plugin_instance->get_event_type_color($session->event_type)); ?>">
                                        <?php echo esc_html($session->event_type); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($session->event_start_date) || !empty($session->event_start_time)): ?>
                                    <div class="session-datetime-small">
                                        <?php if (!empty($session->event_start_time)): ?>
                                            <?php echo esc_html($session->event_start_time); ?>
                                            <?php if (!empty($session->event_start_weekday) && !empty($session->event_start_month) && !empty($session->event_start_day)): ?>
                                                on <?php echo esc_html($session->event_start_weekday . ', ' . $session->event_start_month . ' ' . $session->event_start_day); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($session->venue)): ?>
                                    <div class="session-venue-small">
                                        <?php echo esc_html($session->venue); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contact Information -->
    <?php if (!empty($speaker->speaker_url)): ?>
        <div class="speaker-contact-section">
            <h3>Contact</h3>
            <div class="contact-links">
                <a href="<?php echo esc_url($speaker->speaker_url); ?>"
                    target="_blank"
                    rel="noopener"
                    class="contact-link">Website</a>
            </div>
        </div>
    <?php endif; ?>
</div>