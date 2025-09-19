<?php
/**
 * Sessions Filter Template
 * 
 * @var array $event_types
 * @var array $session_types  
 * @var array $event_dates
 * @var string $selected_event_type
 * @var string $selected_session_type
 * @var string $selected_date
 */

// Check if variables exist
if (!isset($event_types)) $event_types = array();
if (!isset($session_types)) $session_types = array();
if (!isset($event_dates)) $event_dates = array();

if (empty($event_types) && empty($session_types) && empty($event_dates)) {
    return;
}

$active_filters = 0;
if (!empty($selected_event_type)) $active_filters++;
if (!empty($selected_session_type)) $active_filters++;
if (!empty($selected_date)) $active_filters++;
?>

<div class="sched-filter-toggle-container">
    <!-- Filter Toggle Button -->
    <button class="sched-filter-toggle-btn" id="sched-filter-toggle">
        <div class="filter-left">
            <span class="filter-icon">🔍</span>
            <span class="filter-text">Filters</span>
            <span class="filter-badge" id="filter-active-badge"<?php echo ($active_filters == 0 ? ' style="display:none;"' : ''); ?>><?php echo esc_html($active_filters); ?></span>
        </div>
        <span class="toggle-arrow">▼</span>
    </button>
    
    <!-- Collapsible Filter Panel -->
    <div class="sched-filter-panel" id="sched-filter-panel">
        <div class="filter-panel-content">
            
            <?php if (!empty($event_types)): ?>
            <!-- Event Type Filter -->
            <div class="filter-group">
                <label class="filter-group-label">Session Type</label>
                <select id="event-type-filter" class="filter-select">
                    <option value="">All Events</option>
                    <?php foreach ($event_types as $type): ?>
                        <option value="<?php echo esc_attr($type); ?>"<?php echo ($selected_event_type === $type) ? ' selected' : ''; ?>><?php echo esc_html($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($session_types)): ?>
            <!-- Session Type Filter (Event SubType) -->
            <div class="filter-group">
                <label class="filter-group-label">Session Type</label>
                <select id="session-type-filter" class="filter-select">
                    <option value="">All Session Types</option>
                    <?php foreach ($session_types as $subtype): ?>
                        <option value="<?php echo esc_attr($subtype); ?>"<?php echo ($selected_session_type === $subtype) ? ' selected' : ''; ?>><?php echo esc_html($subtype); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($event_dates)): ?>
            <!-- Date Filter -->
            <div class="filter-group">
                <label class="filter-group-label">Date</label>
                <select id="date-filter" class="filter-select">
                    <option value="">All Dates</option>
                    <?php foreach ($event_dates as $date_obj): ?>
                        <?php 
                        $display_text = $date_obj->event_start_weekday . ', ' . $date_obj->event_start_month . ' ' . $date_obj->event_start_day;
                        $selected = ($selected_date === $date_obj->event_start_date) ? ' selected' : '';
                        ?>
                        <option value="<?php echo esc_attr($date_obj->event_start_date); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($display_text); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Clear Filters Button -->
            <div class="filter-actions">
                <button class="clear-filters-btn" id="clear-all-filters">Clear All Filters</button>
            </div>
            
        </div>
    </div>
</div>