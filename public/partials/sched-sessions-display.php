<?php
/**
 * Sessions Display Template
 * 
 * @var array $sessions - Session results
 * @var bool $show_filter - Whether to show filter
 * @var bool $show_pagination - Whether to show pagination
 * @var array $filter_data - Filter data for template
 * @var array $pagination_data - Pagination data for template
 */

if (!isset($sessions)) {
    return;
}
?>

<div class="sched-modern-container">
    <div class="sched-header">
        <?php if ($show_filter && !empty($filter_data)): ?>
            <?php echo $plugin_instance->load_template('sched-sessions-filter', $filter_data); ?>
        <?php endif; ?>
    </div>
    
    <div class="sched-sessions-grid">
        <?php if (!empty($sessions)): ?>
            <?php 
            $current_date = null;
            foreach ($sessions as $session): 
                // Get speakers from pre-loaded data instead of individual queries
                $speakers = isset($session_speakers[$session->event_id]) ? $session_speakers[$session->event_id] : array();
                $event_color = $plugin_instance->get_event_type_color($session->event_type);
                
                // Show date header if it's a new date
                if ($session->event_start_date !== $current_date):
                    if ($current_date !== null): ?>
                        </div> <!-- Close previous day's sessions div -->
                        </div> <!-- Close previous date section div -->
                    <?php endif; ?>
                    
                    <div class="sched-date-section">
                        <div class="sched-date-header">
                            <h3 class="sched-date-title">
                                <span class="day-name"><?php echo esc_html($session->event_start_weekday); ?></span>
                                <span class="date-info"><?php echo esc_html($session->event_start_month . ' ' . $session->event_start_day); ?></span>
                            </h3>
                        </div>
                        <div class="sched-day-sessions">
                    
                    <?php $current_date = $session->event_start_date; ?>
                <?php endif; ?>
                
                <?php 
                // Include individual session card
                echo $plugin_instance->load_template('sched-session-card', array(
                    'session' => $session,
                    'speakers' => $speakers,
                    'event_color' => $event_color
                ));
                ?>
            <?php endforeach; ?>
            
            <?php if ($current_date !== null): ?>
                </div> <!-- Close last day's sessions -->
                </div> <!-- Close last date section -->
            <?php endif; ?>
            
            <?php if ($show_pagination && !empty($pagination_data)): ?>
                <?php echo $plugin_instance->load_template('sched-sessions-pagination', $pagination_data); ?>
            <?php endif; ?>
            
        <?php else: ?>
            <?php echo $plugin_instance->load_template('sched-no-sessions'); ?>
        <?php endif; ?>
    </div>
</div>