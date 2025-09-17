<?php

/**
 * The public-facing functionality of the plugin based on wp-sched.php.
 */
class Sched_Public {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Add virtual page hooks for speaker profiles
        add_action('init', array($this, 'add_speaker_rewrite_rules'));
        add_action('template_redirect', array($this, 'handle_speaker_virtual_pages'));
        add_filter('template_include', array($this, 'load_speaker_template'));
    }    /**
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/sched-public.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/sched-public.js', array('jquery'), $this->version, false);
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode('sched_sessions', array($this, 'display_sessions'));
        add_shortcode('sched_speakers', array($this, 'display_speakers'));
        add_shortcode('sched_single_speaker', array($this, 'display_single_speaker'));
        
        // Also register the wp-sched compatible shortcodes
        add_shortcode('wp_sched_sessions', array($this, 'display_sessions'));
        add_shortcode('wp_sched_speakers', array($this, 'display_speakers'));
        add_shortcode('wp_sched_single_speaker', array($this, 'display_single_speaker'));
    }

    /**
     * Get event speakers for a session - based on wp-sched.php
     */
    private function get_event_speakers($event_id) {
        global $wpdb;
        $sched_sessions_speakers_table = $wpdb->prefix . 'sched_sessions_speakers';
        $query = "SELECT * FROM $sched_sessions_speakers_table WHERE session_id = %s ORDER BY id ASC";
        $results = $wpdb->get_results($wpdb->prepare($query, $event_id));
        return $results;
    }

    /**
     * Optimized: Get speakers for multiple sessions in one query to avoid N+1 problem
     */
    private function get_speakers_for_sessions($session_ids) {
        if (empty($session_ids)) {
            return array();
        }

        global $wpdb;
        $sched_sessions_speakers_table = $wpdb->prefix . 'sched_sessions_speakers';
        $sched_speakers_table = $wpdb->prefix . 'sched_speakers';
        
        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($session_ids), '%s'));
        
        // Query to get all speakers for all sessions at once with speaker details
        $query = "
            SELECT 
                ss.session_id,
                ss.speaker_username,
                ss.speaker_name,
                sp.speaker_company,
                sp.speaker_position,
                sp.speaker_location,
                sp.speaker_about,
                sp.speaker_url,
                sp.speaker_avatar,
                sp.speaker_featured
            FROM $sched_sessions_speakers_table ss
            LEFT JOIN $sched_speakers_table sp ON ss.speaker_username = sp.username
            WHERE ss.session_id IN ($placeholders)
            ORDER BY ss.session_id ASC, ss.id ASC
        ";
        
        $results = $wpdb->get_results($wpdb->prepare($query, ...$session_ids));
        
        // Group results by session_id for easy lookup
        $grouped_speakers = array();
        foreach ($results as $result) {
            $grouped_speakers[$result->session_id][] = $result;
        }
        
        return $grouped_speakers;
    }

    /**
     * Generate initials from speaker name - based on wp-sched.php
     */
    private function get_initials($name) {
        $words = explode(" ", $name);
        $initials = null;
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= $w[0];
            }
        }
        return $initials;
    }

    /**
     * Load template with theme override support and proper context
     */
    private function load_template($template_name, $data = array()) {
        // Allow themes to override plugin templates
        $template_path = locate_template(array(
            "sched/{$template_name}.php",
            "sched-{$template_name}.php"
        ));
        
        if (!$template_path) {
            $template_path = plugin_dir_path(__FILE__) . "partials/{$template_name}.php";
        }
        
        if (file_exists($template_path)) {
            // Pass plugin instance to templates for accessing methods
            $data['plugin_instance'] = $this;
            extract($data);
            ob_start();
            include $template_path;
            return ob_get_clean();
        }
        
        return '';
    }

    /**
     * Get color for event type from admin settings
     */
    public function get_event_type_color($event_type) {
        if (empty($event_type)) {
            return '#95a5a6'; // Default gray
        }
        
        $setting_name = 'sched_color_type_' . sanitize_key($event_type);
        $color = get_option($setting_name, '');
        
        // Return stored color or fallback
        if (!empty($color)) {
            return $color;
        }
        
        // Fallback to existing default colors if not set
        $colors = array(
            'panel' => '#1f2937',
            'workshop' => '#374151', 
            'keynote' => '#f59e0b',
            'training' => '#4b5563',
            'networking' => '#065f46',
            'presentation' => '#6b7280',
            'discussion' => '#1e40af',
            'interview' => '#7c2d12',
            'masterclass' => '#166534',
            'demo' => '#7c3aed',
            'roundtable' => '#581c87',
            'q&a' => '#0891b2',
            'social' => '#dc2626',
            'meeting' => '#0d9488',
            'break' => '#9ca3af',
            'lunch' => '#ea580c',
            'dinner' => '#be123c',
            'coffee' => '#a3a3a3'
        );
        
        $type_key = strtolower(trim($event_type));
        return isset($colors[$type_key]) ? $colors[$type_key] : '#6b7280';
    }

    /**
     * Get all unique event types for filtering
     */
    private function get_all_event_types() {
        global $wpdb;
        $sched_sessions_table = $wpdb->prefix . 'sched_sessions';
        $query = "SELECT DISTINCT event_type FROM $sched_sessions_table WHERE event_type IS NOT NULL AND event_type != '' ORDER BY event_type ASC";
        $results = $wpdb->get_col($query);
        return array_filter($results);
    }

    /**
     * Get all unique event subtypes for Session Type filtering
     */
    private function get_all_event_subtypes() {
        global $wpdb;
        $sched_sessions_table = $wpdb->prefix . 'sched_sessions';
        $query = "SELECT DISTINCT event_subtype FROM $sched_sessions_table WHERE event_subtype IS NOT NULL AND event_subtype != '' ORDER BY event_subtype ASC";
        $results = $wpdb->get_col($query);
        return array_filter($results);
    }

    /**
     * Get all unique event dates for Date filtering
     */
    private function get_all_event_dates() {
        global $wpdb;
        $sched_sessions_table = $wpdb->prefix . 'sched_sessions';
        $query = "SELECT DISTINCT event_start_date, event_start_weekday, event_start_month, event_start_day FROM $sched_sessions_table WHERE event_start_date IS NOT NULL AND event_start_date != '' ORDER BY event_start_date ASC";
        $results = $wpdb->get_results($query);
        return $results;
    }

    /**
     * Render advanced filter toggle with collapsible sections
     */
    private function render_advanced_filter($selected_event_type = '', $selected_session_type = '', $selected_date = '') {
        $event_types = $this->get_all_event_types();
        $session_types = $this->get_all_event_subtypes();
        $event_dates = $this->get_all_event_dates();
        
        if (empty($event_types) && empty($session_types) && empty($event_dates)) {
            return '';
        }

        $active_filters = 0;
        if (!empty($selected_event_type)) $active_filters++;
        if (!empty($selected_session_type)) $active_filters++;
        if (!empty($selected_date)) $active_filters++;

        $html = '<div class="sched-filter-toggle-container">';
        
        // Filter Toggle Button
        $html .= '<button class="sched-filter-toggle-btn" id="sched-filter-toggle">';
        $html .= '<div class="filter-left">';
        $html .= '<span class="filter-icon">🔍</span>';
        $html .= '<span class="filter-text">Filters</span>';
        $html .= '<span class="filter-badge" id="filter-active-badge"' . ($active_filters == 0 ? ' style="display:none;"' : '') . '>' . $active_filters . '</span>';
        $html .= '</div>';
        $html .= '<span class="toggle-arrow">▼</span>';
        $html .= '</button>';
        
        // Collapsible Filter Panel
        $html .= '<div class="sched-filter-panel" id="sched-filter-panel">';
        $html .= '<div class="filter-panel-content">';
        
        // Event Type Filter
        if (!empty($event_types)) {
            $html .= '<div class="filter-group">';
            $html .= '<label class="filter-group-label">Session Track</label>';
            $html .= '<select id="event-type-filter" class="filter-select">';
            $html .= '<option value="">All Events</option>';
            
            foreach ($event_types as $type) {
                $selected = ($selected_event_type === $type) ? ' selected' : '';
                $html .= '<option value="' . esc_attr($type) . '"' . $selected . '>' . esc_html($type) . '</option>';
            }
            
            $html .= '</select>';
            $html .= '</div>';
        }
        
        // Session Type Filter (Event SubType)
        if (!empty($session_types)) {
            $html .= '<div class="filter-group">';
            $html .= '<label class="filter-group-label">Session Type</label>';
            $html .= '<select id="session-type-filter" class="filter-select">';
            $html .= '<option value="">All Session Types</option>';
            
            foreach ($session_types as $subtype) {
                $selected = ($selected_session_type === $subtype) ? ' selected' : '';
                $html .= '<option value="' . esc_attr($subtype) . '"' . $selected . '>' . esc_html($subtype) . '</option>';
            }
            
            $html .= '</select>';
            $html .= '</div>';
        }

        // Date Filter
        if (!empty($event_dates)) {
            $html .= '<div class="filter-group">';
            $html .= '<label class="filter-group-label">Date</label>';
            $html .= '<select id="date-filter" class="filter-select">';
            $html .= '<option value="">All Dates</option>';
            
            foreach ($event_dates as $date_obj) {
                $selected = ($selected_date === $date_obj->event_start_date) ? ' selected' : '';
                $display_text = $date_obj->event_start_weekday . ', ' . $date_obj->event_start_month . ' ' . $date_obj->event_start_day;
                $html .= '<option value="' . esc_attr($date_obj->event_start_date) . '"' . $selected . '>' . esc_html($display_text) . '</option>';
            }
            
            $html .= '</select>';
            $html .= '</div>';
        }
        
        // Clear Filters Button
        $html .= '<div class="filter-actions">';
        $html .= '<button class="clear-filters-btn" id="clear-all-filters">Clear All Filters</button>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Get sessions data - pure business logic with optimized speaker loading
     */
    private function get_sessions_data($atts) {
        // Filter parameters
        $selected_event_type = isset($_GET['event_type']) ? sanitize_text_field($_GET['event_type']) : '';
        $selected_session_type = isset($_GET['session_type']) ? sanitize_text_field($_GET['session_type']) : '';
        $selected_date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : '';

        // Pagination
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $posts_per_page = intval($atts['limit']);

        // Database queries
        global $wpdb;
        $sched_sessions_table = $wpdb->prefix . 'sched_sessions';
        
        // Build where conditions
        $where_conditions = array();
        if (!empty($selected_event_type)) {
            $where_conditions[] = $wpdb->prepare("event_type = %s", $selected_event_type);
        }
        if (!empty($selected_session_type)) {
            $where_conditions[] = $wpdb->prepare("event_subtype = %s", $selected_session_type);
        }
        if (!empty($selected_date)) {
            $where_conditions[] = $wpdb->prepare("event_start_date = %s", $selected_date);
        }
        
        $where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : '';
        
        // Get results
        $total_results = $wpdb->get_var("SELECT COUNT(*) FROM $sched_sessions_table" . $where_clause);
        
        // Sanitize pagination parameters
        $offset = absint(($paged - 1) * $posts_per_page);
        $limit = absint($posts_per_page);
        
        $query = "SELECT * FROM $sched_sessions_table" . $where_clause . " ORDER BY event_start_date ASC, event_start_time ASC";
        $query .= $wpdb->prepare(" LIMIT %d, %d", $offset, $limit);
        $sessions = $wpdb->get_results($query);

        // Pre-load all speakers for these sessions to avoid N+1 queries
        $session_speakers = array();
        if (!empty($sessions)) {
            $session_ids = array_map(function($session) {
                return $session->event_id;
            }, $sessions);
            
            $session_speakers = $this->get_speakers_for_sessions($session_ids);
        }

        return array(
            'sessions' => $sessions,
            'session_speakers' => $session_speakers,
            'total_results' => $total_results,
            'current_page' => $paged,
            'posts_per_page' => $posts_per_page,
            'selected_event_type' => $selected_event_type,
            'selected_session_type' => $selected_session_type,
            'selected_date' => $selected_date,
            'show_filter' => $atts['show_filter'],
            'show_pagination' => $atts['pagination']
        );
    }

    /**
     * Display sessions shortcode - clean separation of concerns
     */
    public function display_sessions($atts = array()) {
        // 1. Parse attributes and sanitize input
        $atts = shortcode_atts(array(
            'limit' => get_option('sched_pagination_number', 32),
            'pagination' => 'true',
            'show_filter' => 'true'
        ), $atts, 'sched_sessions');

        // 2. Get data (business logic)
        $data = $this->get_sessions_data($atts);
        
        // 3. Prepare template data
        $template_data = array(
            'sessions' => $data['sessions'],
            'session_speakers' => $data['session_speakers'],
            'show_filter' => ($atts['show_filter'] === 'true'),
            'show_pagination' => ($atts['pagination'] === 'true'),
            'filter_data' => array(
                'event_types' => $this->get_all_event_types(),
                'session_types' => $this->get_all_event_subtypes(),
                'event_dates' => $this->get_all_event_dates(),
                'selected_event_type' => $data['selected_event_type'],
                'selected_session_type' => $data['selected_session_type'],
                'selected_date' => $data['selected_date']
            ),
            'pagination_data' => array(
                'total_results' => $data['total_results'],
                'posts_per_page' => $data['posts_per_page'],
                'current_page' => $data['current_page']
            )
        );
        
        // 4. Pass to template (presentation)
        return $this->load_template('sched-sessions-display', $template_data);
    }

    /**
     * Find page that contains speakers shortcode
     */
    private function find_speakers_page() {
        global $wpdb;
        
        // Look for pages containing the speakers shortcode
        $page = $wpdb->get_var(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_content LIKE '%[sched_speakers%' 
             AND post_status = 'publish' 
             AND post_type = 'page'
             LIMIT 1"
        );
        
        if (!$page) {
            // Try alternative shortcode
            $page = $wpdb->get_var(
                "SELECT ID FROM {$wpdb->posts} 
                 WHERE post_content LIKE '%[wp_sched_speakers%' 
                 AND post_status = 'publish' 
                 AND post_type = 'page'
                 LIMIT 1"
            );
        }
        
        return $page;
    }

    /**
     * Display single speaker page
     */
    /**
     * Display single speaker shortcode - clean separation of concerns
     */
    public function display_single_speaker($atts = array()) {
        // 1. Parse attributes and sanitize input
        $atts = shortcode_atts(array(
            'username' => '',
            'back_url' => ''
        ), $atts, 'sched_single_speaker');

        if (empty($atts['username'])) {
            return '<div class="sched-speaker-error">
                        <h3>Speaker username required</h3>
                        <p>Please specify a speaker username: [sched_single_speaker username="speaker-username"]</p>
                    </div>';
        }

        // 2. Get data (business logic)
        $data = $this->get_single_speaker_data($atts['username']);
        
        if (!$data['speaker']) {
            return '<div class="sched-speaker-not-found">
                        <h3>Speaker not found</h3>
                        <p>The requested speaker could not be found.</p>
                        <a href="javascript:history.back()" class="sched-back-btn">← Back to Speakers</a>
                    </div>';
        }

        // 3. Prepare template data
        $template_data = array(
            'speaker' => $data['speaker'],
            'speaker_sessions' => $data['speaker_sessions'],
            'back_url' => !empty($atts['back_url']) ? $atts['back_url'] : $data['back_url']
        );
        
        // 4. Pass to template (presentation)
        return $this->load_template('sched-single-speaker-display', $template_data);
    }

    /**
     * Get single speaker data - business logic separation
     */
    public function get_single_speaker_data($username) {
        global $wpdb;
        $sched_speakers_table = $wpdb->prefix . 'sched_speakers';
        
        // Get speaker data
        $speaker = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $sched_speakers_table WHERE username = %s",
            sanitize_text_field($username)
        ));

        $speaker_sessions = array();
        $back_url = home_url('/');

        if ($speaker) {
            // Get speaker's sessions
            $sched_sessions_speakers_table = $wpdb->prefix . 'sched_sessions_speakers';
            $sched_sessions_table = $wpdb->prefix . 'sched_sessions';
            
            $speaker_sessions = $wpdb->get_results($wpdb->prepare("
                SELECT s.*, ss.speaker_name, ss.speaker_username
                FROM $sched_sessions_table s
                INNER JOIN $sched_sessions_speakers_table ss ON s.event_id = ss.session_id
                WHERE ss.speaker_username = %s
                ORDER BY s.event_start_date ASC, s.event_start_time ASC
            ", $speaker->username));

            // Generate back URL - try to find a speakers page or default to home
            $speakers_page = $this->find_speakers_page();
            $back_url = $speakers_page ? get_permalink($speakers_page) : home_url('/');
        }

        return array(
            'speaker' => $speaker,
            'speaker_sessions' => $speaker_sessions,
            'back_url' => $back_url
        );
    }

    /**
     * Get speakers data - business logic separation
     */
    public function get_speakers_data($atts) {
        // Pagination parameters
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $posts_per_page = intval($atts['limit']);

        // Query the custom table data
        global $wpdb;
        $sched_speakers_table = $wpdb->prefix . 'sched_speakers';
        
        // Get total count of ALL speakers for pagination
        $total_speakers = $wpdb->get_var("SELECT COUNT(*) FROM $sched_speakers_table");
        
        // Get total count of featured speakers (these always show on first page)
        $featured_count = $wpdb->get_var("SELECT COUNT(*) FROM $sched_speakers_table WHERE speaker_featured = 'Y'");
        
        // Calculate offset for regular speakers pagination
        $regular_offset = 0;
        if ($paged > 1) {
            // On pages after the first, we don't show featured speakers again
            $regular_offset = absint(($paged - 1) * $posts_per_page);
        } else {
            // On first page, featured speakers are shown separately, so regular speakers start normally
            $regular_offset = 0;
        }
        
        // Query regular speakers with proper pagination
        $query = $wpdb->prepare(
            "SELECT * FROM $sched_speakers_table WHERE speaker_featured = 'N' ORDER BY speaker_name ASC LIMIT %d, %d",
            $regular_offset,
            absint($posts_per_page)
        );
        $results = $wpdb->get_results($query);

        // Query featured speakers (only on first page)
        $results_featured = array();
        if ($paged == 1) {
            $query_featured = "SELECT * FROM $sched_speakers_table WHERE speaker_featured = 'Y' ORDER BY customorder ASC, speaker_name ASC";
            $results_featured = $wpdb->get_results($query_featured);
        }

        // Calculate pagination data for regular speakers only
        // (featured speakers always show on page 1 and don't count toward pagination)
        $regular_speakers_count = $wpdb->get_var("SELECT COUNT(*) FROM $sched_speakers_table WHERE speaker_featured = 'N'");
        $total_pages = ceil($regular_speakers_count / $posts_per_page);

        return array(
            'featured_speakers' => $results_featured,
            'regular_speakers' => $results,
            'current_page' => $paged,
            'posts_per_page' => $posts_per_page,
            'total_pages' => $total_pages,
            'total_speakers' => $total_speakers,
            'featured_count' => $featured_count,
            'regular_speakers_count' => $regular_speakers_count
        );
    }

    /**
     * Display speakers shortcode - clean separation of concerns
     */
    public function display_speakers($atts = array()) {
        // 1. Parse attributes and sanitize input
        $atts = shortcode_atts(array(
            'limit' => get_option('sched_pagination_number', 100),
            'pagination' => 'true'
        ), $atts, 'sched_speakers');

        // 2. Get data (business logic)
        $data = $this->get_speakers_data($atts);
        
        // 3. Prepare template data
        $template_data = array(
            'featured_speakers' => $data['featured_speakers'],
            'regular_speakers' => $data['regular_speakers'],
            'show_pagination' => ($atts['pagination'] === 'true'),
            'current_page' => $data['current_page'],
            'pagination_data' => array(
                'total_pages' => $data['total_pages'],
                'current_page' => $data['current_page'],
                'posts_per_page' => $data['posts_per_page']
            )
        );
        
        // 4. Pass to template (presentation)
        return $this->load_template('sched-speakers-display', $template_data);
    }

    /**
     * Automatic sync function called by cron.
     */
    public function auto_sync_data() {
        if (!get_option('sched_auto_sync')) {
            return;
        }
        
        $api = new Sched_API();
        $api->sync_all_data();
    }

    /**
     * Add rewrite rules for speaker virtual pages
     */
    public function add_speaker_rewrite_rules() {
        add_rewrite_rule('^speakers/([^/]+)/?$', 'index.php?speaker_username=$matches[1]', 'top');
        add_rewrite_tag('%speaker_username%', '([^&]+)');
    }

    /**
     * Handle speaker virtual pages - prevent WordPress conflicts
     */
    public function handle_speaker_virtual_pages() {
        $speaker_username = get_query_var('speaker_username');
        
        if (!empty($speaker_username)) {
            // Verify speaker exists
            global $wpdb;
            $sched_speakers_table = $wpdb->prefix . 'sched_speakers';
            $speaker = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $sched_speakers_table WHERE username = %s",
                $speaker_username
            ));
            
            if (!$speaker) {
                // Speaker not found, show 404
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return;
            }
            
            // Set up virtual page properly to prevent WordPress conflicts
            global $wp_query;
            $wp_query->is_404 = false;
            $wp_query->is_page = false;  // Don't pretend to be a regular page
            $wp_query->is_singular = false;  // Don't trigger singular page logic
            $wp_query->is_home = false;
            $wp_query->is_front_page = false;
            
            // Set custom query var for template system
            $wp_query->set('is_speaker_page', true);
            $wp_query->set('speaker_data', $speaker);
            
            // Completely disable comments system for speaker pages
            add_filter('comments_open', '__return_false', 999);
            add_filter('pings_open', '__return_false', 999);
            add_filter('comments_array', '__return_empty_array', 999);
            
            // Remove comment-related actions that could cause conflicts
            remove_action('wp_head', 'wp_enqueue_comment_reply');
            remove_action('wp_footer', 'wp_print_footer_scripts');
            
            // Filter out any comment blocks that might try to render
            add_filter('render_block', array($this, 'filter_comment_blocks'), 10, 2);
            
            // Set proper HTTP headers
            status_header(200);
        }
    }

    /**
     * Filter out comment blocks on speaker pages
     */
    public function filter_comment_blocks($block_content, $block) {
        if (get_query_var('is_speaker_page')) {
            // Remove any comment-related blocks
            if (isset($block['blockName']) && 
                (strpos($block['blockName'], 'core/comment') === 0 || 
                 $block['blockName'] === 'core/comments' ||
                 $block['blockName'] === 'core/post-comments-form')) {
                return '';
            }
        }
        return $block_content;
    }

    /**
     * Load custom template for speaker pages
     */
    public function load_speaker_template($template) {
        if (get_query_var('is_speaker_page')) {
            // Use a simple template that doesn't trigger WordPress post context
            $custom_template = plugin_dir_path(__FILE__) . 'partials/speaker-page-template.php';
            if (file_exists($custom_template)) {
                return $custom_template;
            }
            
            // Fallback: create minimal template inline
            $this->render_speaker_page_inline();
            exit;
        }
        
        return $template;
    }

    /**
     * Render speaker page inline if no template file exists
     */
    private function render_speaker_page_inline() {
        $speaker = get_query_var('speaker_data');
        $speaker_username = get_query_var('speaker_username');
        
        if (!$speaker) {
            wp_die('Speaker not found', 'Speaker Not Found', array('response' => 404));
        }
        
        // Get site title and theme info for basic HTML structure
        $site_title = get_bloginfo('name');
        $theme_url = get_template_directory_uri();
        
        // Render minimal HTML page
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html($speaker->speaker_name . ' - ' . $site_title); ?></title>
            <?php wp_head(); ?>
        </head>
        <body class="speaker-page">
            <div class="speaker-page-container">
                <?php echo $this->display_single_speaker(array('username' => $speaker_username)); ?>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Get color for event subtype from admin settings
     */
    public function get_event_subtype_color($event_subtype) {
        if (empty($event_subtype)) {
            return '#666666'; // Default dark gray
        }
        
        $setting_name = 'sched_color_subtype_' . sanitize_key($event_subtype);
        $color = get_option($setting_name, '');
        
        return !empty($color) ? $color : '#666666';
    }
}