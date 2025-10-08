<?php

/**
 * Template for virtual session pages - updated to follow speaker pattern
 * This template prevents WordPress conflicts by not loading post/comment context
 */

// Get session data from query vars
$session = get_query_var('session_data');
$session_speakers = get_query_var('session_speakers_data');
$session_id = get_query_var('session_id');

if (!$session) {
    wp_die('Session not found', 'Session Not Found', array('response' => 404));
}

// Get site info for HTML structure  
$site_title = get_bloginfo('name');
$site_description = get_bloginfo('description');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="index, follow">

    <title><?php echo esc_html($session->event_name . ' - Session - ' . $site_title); ?></title>
    <meta name="description" content="<?php echo esc_attr('Session details for ' . $session->event_name . ' at ' . $site_title . '. ' . wp_trim_words($session->event_description ?? '', 20)); ?>">

    <!-- Open Graph tags for social sharing -->
    <meta property="og:title" content="<?php echo esc_attr($session->event_name); ?>">
    <meta property="og:description" content="<?php echo esc_attr(wp_trim_words($session->event_description ?? '', 20)); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo esc_url(home_url($_SERVER['REQUEST_URI'])); ?>">

    <!-- Open Graph tags for social sharing -->
    <meta property="og:title" content="<?php echo esc_attr($session->event_name . ' - Session'); ?>">
    <meta property="og:description" content="<?php echo esc_attr('Learn about the session ' . $session->event_name . ' and its speakers.'); ?>">
    <meta property="og:type" content="article">

    <?php
    // Load WordPress head but prevent comment-related scripts
    remove_action('wp_head', 'wp_enqueue_comment_reply');
    wp_head();
    ?>

    <style>
        /* Minimal styling for session pages */
        .session-page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .session-page-header {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        .session-page-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .session-back-link {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #0b3363;
            color: white;
            text-decoration: none;
            border-radius: 0;
            transition: background 0.3s;
        }

        .session-back-link:hover {
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

<body class="session-page session-<?php echo esc_attr($session_id); ?>">

    <div class="session-page-wrapper">

        <div class="session-page-header">
            <a href="javascript:history.back();" class="session-back-link" id="smart-back-link">
                ← <span id="back-text">Back to Sessions</span>
            </a>
            
            <div class="session-content-area">
                <?php
                // Use the existing session display template with new data method
                $public_class = new Sched_Public('sched-conference-plugin', '1.0.0');
                $template_data = array(
                    'session' => $session,
                    'session_speakers' => $session_speakers,
                    'event_color' => $public_class->get_event_type_color($session->event_type)
                );
                
                // Load the session display template
                echo wp_kses_post($public_class->load_template('sched-single-session-display', $template_data));
                ?>
            </div>
        </div>

    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const smartBackLink = document.getElementById('smart-back-link');
        const backText = document.getElementById('back-text');
        
        // Get the referrer URL
        const referrer = document.referrer;
        
        if (referrer) {
            // Check if user came from a sessions page - enhanced for flexible base paths
            if (referrer.includes('sched_sessions') || 
                referrer.match(/\/sessions[\/\?\#]/) || 
                referrer.match(/\/sessions$/) ||
                referrer.includes('/program/sessions') ||
                referrer.includes('/program/schedule/sessions')) {
                
                // Determine which sessions page they came from
                try {
                    const url = new URL(referrer);
                    const params = new URLSearchParams(url.search);
                    
                    let backTextContent = 'Back to ';
                    
                    // Check for filters
                    const eventType = params.get('event_type');
                    const sessionType = params.get('session_type');
                    const date = params.get('date');
                    const page = params.get('paged') || params.get('page');
                    
                    if (eventType || sessionType || date) {
                        backTextContent += 'Filtered Sessions';
                    } else if (page && page !== '1') {
                        backTextContent += `Sessions (Page ${page})`;
                    } else {
                        backTextContent += 'All Sessions';
                    }
                    
                    backText.textContent = backTextContent;
                } catch (e) {
                    // URL parsing failed, use default
                    backText.textContent = 'Back to Sessions';
                }
            }
        }
        
        // Add fallback for browsers that don't support history.back()
        smartBackLink.addEventListener('click', function(e) {
            if (window.history.length <= 1) {
                e.preventDefault();
                // Use a more generic fallback - go to home page
                window.location.href = '/';
            }
        });
    });
    </script>

    <?php
    // Load WordPress footer but prevent comment scripts
    remove_action('wp_footer', 'wp_print_footer_scripts');
    wp_footer();
    ?>

</body>

</html>