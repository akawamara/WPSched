<?php
/**
 * Speakers Pagination Template
 * 
 * @var int $total_pages - Total number of pages
 * @var int $current_page - Current page number
 * @var int $posts_per_page - Posts per page
 */

if (!isset($total_pages) || $total_pages <= 1) {
    return;
}
?>

<div class="sched-pagination-modern">
    <?php
    echo wp_kses_post(paginate_links(array(
        'base' => add_query_arg('paged','%#%'),
        'format' => '',
        'total' => $total_pages,
        'current' => $current_page,
        'prev_text' => '← Previous',
        'next_text' => 'Next →'
    )));
    ?>
</div>
