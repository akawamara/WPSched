<?php
/**
 * Sessions Pagination Template
 * 
 * @var int $total_results
 * @var int $posts_per_page
 * @var int $current_page
 */

if (!isset($total_results) || !isset($posts_per_page) || !isset($current_page)) {
    return;
}

$total_pages = ceil($total_results / $posts_per_page);

if ($total_pages <= 1) {
    return;
}
?>

<div class="sched-pagination-modern">
    <?php
    echo wp_kses_post(paginate_links(array(
        'base' => add_query_arg('paged','%#%'),
        'format' => '',
        'prev_text' => esc_html__('&laquo;', 'wpsched'),
        'next_text' => esc_html__('&raquo;', 'wpsched'),
        'total' => $total_pages,
        'current' => $current_page
    )));
    ?>
</div>
