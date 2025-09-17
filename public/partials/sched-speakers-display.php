<?php
/**
 * Main Speakers Display Template
 * 
 * @var array $featured_speakers - Featured speakers data
 * @var array $regular_speakers - Regular speakers data  
 * @var bool $show_pagination - Whether to show pagination
 * @var int $current_page - Current page number
 * @var array $pagination_data - Pagination configuration
 */

if (!isset($featured_speakers) && !isset($regular_speakers)) {
    echo $plugin_instance->load_template('sched-no-speakers');
    return;
}
?>

<div class="sched-modern-container">
    <div class="sched-speakers-grid">
        
        <?php if ($current_page == 1 && !empty($featured_speakers)): ?>
        <!-- Featured Speakers Section -->
        <div class="sched-featured-section">
            <div class="sched-featured-header">
                <h3 class="sched-section-title">
                    <span>Featured Speakers</span>
                </h3>
            </div>
            <div class="sched-featured-speakers">
                <?php foreach ($featured_speakers as $speaker): ?>
                    <?php echo $plugin_instance->load_template('sched-speaker-card', array(
                        'speaker' => $speaker,
                        'is_featured' => true
                    )); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($regular_speakers) || ($current_page == 1 && !empty($featured_speakers))): ?>
        <!-- Regular Speakers Section -->
        <div class="sched-speakers-section">
            <div class="sched-speakers-header">
                <h3 class="sched-section-title">
                    <?php if ($current_page == 1 && !empty($featured_speakers)): ?>
                        <span>All Speakers</span>
                    <?php else: ?>
                        <span>Speakers</span>
                    <?php endif; ?>
                </h3>
            </div>
            
            <?php if (!empty($regular_speakers)): ?>
            <div class="sched-all-speakers">
                <?php foreach ($regular_speakers as $speaker): ?>
                    <?php echo $plugin_instance->load_template('sched-speaker-card', array(
                        'speaker' => $speaker,
                        'is_featured' => false
                    )); ?>
                <?php endforeach; ?>
            </div>
            <?php elseif ($current_page == 1 && !empty($featured_speakers)): ?>
            <!-- Only featured speakers exist -->
            <div class="sched-regular-speakers-empty">
                <p>Additional speakers will be added soon.</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($show_pagination && !empty($pagination_data) && $pagination_data['total_pages'] > 1): ?>
            <?php echo $plugin_instance->load_template('sched-speakers-pagination', $pagination_data); ?>
        <?php endif; ?>
        
        <?php else: ?>
            <?php echo $plugin_instance->load_template('sched-no-speakers'); ?>
        <?php endif; ?>
        
    </div>
</div>