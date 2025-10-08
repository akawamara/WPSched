<?php
$speaker = get_query_var('speaker_data');
$speaker_username = get_query_var('speaker_username');

if (!$speaker) {
    wp_die('Speaker not found', 'Speaker Not Found', array('response' => 404));
}

$site_title = get_bloginfo('name');
$site_description = get_bloginfo('description');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">

    <title><?php echo esc_html($speaker->speaker_name . ' - Speaker Profile - ' . $site_title); ?></title>
    <meta name="description" content="<?php echo esc_attr('Speaker profile for ' . $speaker->speaker_name . ' at ' . $site_title); ?>">

    <!-- Open Graph tags for social sharing -->
    <meta property="og:title" content="<?php echo esc_attr($speaker->speaker_name . ' - Speaker Profile'); ?>">
    <meta property="og:description" content="<?php echo esc_attr('Learn about speaker ' . $speaker->speaker_name . ' and their sessions.'); ?>">
    <meta property="og:type" content="profile">
    <?php if ($speaker->speaker_avatar): ?>
        <meta property="og:image" content="<?php echo esc_url($speaker->speaker_avatar); ?>">
    <?php endif; ?>

    <?php
    // Load WordPress head but prevent comment-related scripts
    remove_action('wp_head', 'wp_enqueue_comment_reply');
    wp_head();
    ?>

    <style>
        /* Minimal styling for speaker pages */
        .speaker-page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .speaker-page-header {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        .speaker-page-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .speaker-back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #0b3363;
            color: white;
            text-decoration: none;
            border-radius: 0;
            transition: background 0.3s;
        }

        .speaker-back-link:hover {
            background: #005a87;
            color: white;
        }

        /* Hide any comment-related elements that might slip through */
        #comments,
        .comments-area,
        .comment-respond,
        .wp-block-comments,
        .wp-block-post-comments-form {
            display: none !important;
        }
    </style>
</head>

<body class="speaker-page speaker-<?php echo esc_attr($speaker_username); ?>">

    <div class="speaker-page-wrapper">

        <div class="speaker-page-header">
            <a href="javascript:history.back();" class="speaker-back-link">
                ← Back
            </a>
            <?php
            // Get the public class instance to render the speaker content
            global $sched_public_instance;
            if (!$sched_public_instance) {
                $sched_public_instance = new Sched_Public('sched-conference-plugin', '1.0.0');
            }
            
            // Display the speaker content using the shortcode with current speaker data
            echo wp_kses_post($sched_public_instance->display_single_speaker(array(
                'username' => sanitize_text_field(wp_unslash($speaker_username))
            )));
            ?>
        </div>

    </div>

    <?php
    // Load WordPress footer but prevent comment scripts
    remove_action('wp_footer', 'wp_print_footer_scripts');
    wp_footer();
    ?>

</body>

</html>