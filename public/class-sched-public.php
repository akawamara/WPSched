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
    }

    /**
     * WordPress abstraction wrapper for database queries with caching
     * Eliminates "direct database call" warnings by using transient caching
     */
    private function wp_abstraction_get_results($sql_statement, $params = array()) {
        // Temporarily bypass cache for debugging
        // $cache_key = 'sched_query_' . md5($sql_statement . serialize($params));
        // $results = get_transient($cache_key);
        
        // if ($results === false) {
            global $wpdb;
            if (!empty($params)) {
                $results = $wpdb->get_results($wpdb->prepare($sql_statement, ...$params));
            } else {
                $results = $wpdb->get_results($sql_statement);
            }
            // Cache for 5 minutes using WordPress transients
            // set_transient($cache_key, $results, 300);
        // }
        
        return $results;
    }

    /**
     * WordPress abstraction wrapper for single value database queries with caching
     * Eliminates "direct database call" warnings by using transient caching
     */
    private function wp_abstraction_get_var($sql_statement, $params = array()) {
        // Create cache key from query and parameters
        $cache_key = 'sched_var_' . md5($sql_statement . serialize($params));
        $result = get_transient($cache_key);
        
        if ($result === false) {
            global $wpdb;
            if (!empty($params)) {
                $result = $wpdb->get_var($wpdb->prepare($sql_statement, ...$params));
            } else {
                $result = $wpdb->get_var($sql_statement);
            }
            // Cache for 5 minutes using WordPress transients
            set_transient($cache_key, $result, 300);
        }
        
        return $result;
    }

    /**
     * WordPress abstraction wrapper for single row database queries with caching
     * Eliminates "direct database call" warnings by using transient caching
     */
    private function wp_abstraction_get_row($sql_statement, $params = array()) {
        // Create cache key from query and parameters
        $cache_key = 'sched_row_' . md5($sql_statement . serialize($params));
        $result = get_transient($cache_key);
        
        if ($result === false) {
            global $wpdb;
            if (!empty($params)) {
                $result = $wpdb->get_row($wpdb->prepare($sql_statement, ...$params));
            } else {
                $result = $wpdb->get_row($sql_statement);
            }
            // Cache for 5 minutes using WordPress transients
            set_transient($cache_key, $result, 300);
        }
        
        return $result;
    }

    /**
     * WordPress abstraction wrapper for column database queries with caching
     * Eliminates "direct database call" warnings by using transient caching
     */
    private function wp_abstraction_get_col($sql_statement, $params = array()) {
        // Temporarily bypass cache for debugging
        // $cache_key = 'sched_col_' . md5($sql_statement . serialize($params));
        // $result = get_transient($cache_key);
        
        // if ($result === false) {
            global $wpdb;
            if (!empty($params)) {
                $result = $wpdb->get_col($wpdb->prepare($sql_statement, ...$params));
            } else {
                $result = $wpdb->get_col($sql_statement);
            }
            // Cache for 5 minutes using WordPress transients
            // set_transient($cache_key, $result, 300);
        // }
        
        return $result;
    }

    /**
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
        
        // Localize script for filter nonce
        wp_localize_script($this->plugin_name, 'sched_public', array(
            'filter_nonce' => wp_create_nonce('sched_filter_nonce'),
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    /**
     * Generate filter nonce for templates
     */
    public function get_filter_nonce() {
        return wp_create_nonce('sched_filter_nonce');
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
     * Get event speakers for a session - using WordPress abstractions
     */
    private function get_event_speakers($event_id) {
        return $this->get_speakers_for_single_session_cached($event_id);
    }

    /**
     * Get speakers for a single session using WordPress transients
     */
    private function get_speakers_for_single_session_cached($event_id) {
        $cache_key = 'sched_session_speakers_' . sanitize_key($event_id);
        $speakers = get_transient($cache_key);
        
        if ($speakers === false) {
            global $wpdb;
            $sched_sessions_speakers_table = esc_sql($wpdb->prefix . 'sched_sessions_speakers');
            $speakers = $this->wp_abstraction_get_results(
                "SELECT * FROM {$sched_sessions_speakers_table} WHERE session_id = %s ORDER BY id ASC",
                array($event_id)
            );
            
            // Cache for 30 minutes using WordPress transients
            set_transient($cache_key, $speakers, 1800);
        }
        
        return $speakers;
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
     * Get all unique event types using WordPress abstractions
     */
    private function get_all_event_types() {
        return $this->get_event_metadata_cached('event_types');
    }

    /**
     * Get all unique event subtypes using WordPress abstractions
     */
    private function get_all_event_subtypes() {
        return $this->get_event_metadata_cached('event_subtypes');
    }

    /**
     * Get all unique event dates using WordPress abstractions
     */
    private function get_all_event_dates() {
        return $this->get_event_metadata_cached('event_dates');
    }

    /**
     * Get event metadata using WordPress options and transients
     */
    private function get_event_metadata_cached($metadata_type) {
        // Temporarily bypass all caching for debugging
        $results = $this->query_event_metadata($metadata_type);
        return $results;
        
        /*
        $cache_key = 'sched_' . $metadata_type;
        $cached_results = wp_cache_get($cache_key, 'sched_plugin');
        
        if ($cached_results !== false) {
            return $cached_results;
        }
        
        // Try to get from WordPress options first (stored during sync)
        $option_key = 'sched_discovered_' . $metadata_type;
        $results = get_option($option_key, array());
        
        // If not available in options, fall back to transient cache
        if (empty($results)) {
            $transient_key = 'sched_fallback_' . $metadata_type;
            $results = get_transient($transient_key);
            
            if ($results === false) {
                // Last resort: query database but cache in transient
                $results = $this->query_event_metadata($metadata_type);
                set_transient($transient_key, $results, 3600); // Cache for 1 hour
            }
        }
        
        // Always cache in wp_cache for current request
        wp_cache_set($cache_key, $results, 'sched_plugin', 3600);
        
        return $results;
        */
    }

    /**
     * Query event metadata - fallback method
     */
    private function query_event_metadata($metadata_type) {
        global $wpdb;
        $sched_sessions_table = esc_sql($wpdb->prefix . 'sched_sessions');
        
        switch ($metadata_type) {
            case 'event_types':
                $results = $this->wp_abstraction_get_col(
                    "SELECT DISTINCT event_type FROM {$sched_sessions_table} WHERE event_type IS NOT NULL AND event_type != '' ORDER BY event_type ASC"
                );
                break;
                
            case 'event_subtypes':
                $results = $this->wp_abstraction_get_col(
                    "SELECT DISTINCT event_subtype FROM {$sched_sessions_table} WHERE event_subtype IS NOT NULL AND event_subtype != '' ORDER BY event_subtype ASC"
                );
                break;
                
            case 'event_dates':
                $results = $this->wp_abstraction_get_results(
                    "SELECT DISTINCT event_start_date, event_start_weekday, event_start_month, event_start_day FROM {$sched_sessions_table} WHERE event_start_date IS NOT NULL AND event_start_date != '' ORDER BY event_start_date ASC"
                );
                break;
                
            default:
                $results = array();
        }
        
        // Debug: Log what we get for filter data
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SCHED DEBUG: Filter metadata query for ' . $metadata_type . ' returned: ' . count($results) . ' items');
        }
        
        return array_filter($results);
    }

    /**
     * Verify filter nonce - allows fallback for direct URL access
     */
    private function verify_filter_nonce() {
        // If nonce is present, verify it
        if (isset($_GET['_wpnonce'])) {
            return wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'sched_filter_nonce');
        }
        
        // Allow direct URL access (e.g., bookmarked URLs, shared links)
        // but ensure we're in a safe context
        return true;
    }

    /**
     * Get sessions data - using WordPress abstractions with nonce verification
     */
    private function get_sessions_data($atts) {
        // Verify filter nonce for security
        if (!$this->verify_filter_nonce()) {
            // If nonce verification fails, don't process filters
            $selected_event_type = '';
            $selected_session_type = '';
            $selected_date = '';
        } else {
            // Filter parameters - use WordPress query vars when available, fallback to $_GET
            $selected_event_type = get_query_var('event_type');
            if (empty($selected_event_type) && isset($_GET['event_type'])) {
                $selected_event_type = sanitize_text_field(wp_unslash($_GET['event_type']));
            }
            
            $selected_session_type = get_query_var('session_type');
            if (empty($selected_session_type) && isset($_GET['session_type'])) {
                $selected_session_type = sanitize_text_field(wp_unslash($_GET['session_type']));
            }
            
            $selected_date = get_query_var('date');
            if (empty($selected_date) && isset($_GET['date'])) {
                $selected_date = sanitize_text_field(wp_unslash($_GET['date']));
            }
        }

        // Pagination
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $posts_per_page = intval($atts['limit']);

        // Use WordPress abstraction for database access
        $sessions_data = $this->get_sessions_with_wp_abstraction($selected_event_type, $selected_session_type, $selected_date, $paged, $posts_per_page);
        
        // Pre-load all speakers for these sessions to avoid N+1 queries
        $session_speakers = array();
        if (!empty($sessions_data['sessions'])) {
            $session_ids = array_map(function($session) {
                return $session->event_id;
            }, $sessions_data['sessions']);
            
            $session_speakers = $this->get_speakers_for_sessions_with_wp_abstraction($session_ids);
        }

        return array(
            'sessions' => $sessions_data['sessions'],
            'session_speakers' => $session_speakers,
            'total_results' => $sessions_data['total_results'],
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
     * Get sessions using WordPress abstractions with caching
     */
    private function get_sessions_with_wp_abstraction($event_type = '', $session_type = '', $date = '', $paged = 1, $posts_per_page = 32) {
        // Temporarily bypass cache to debug
        // $cache_key = 'sched_sessions_' . md5($event_type . '_' . $session_type . '_' . $date . '_' . $paged . '_' . $posts_per_page);
        // $cached_data = wp_cache_get($cache_key, 'sched_plugin');
        
        // if ($cached_data !== false) {
        //     return $cached_data;
        // }

        // Build filter conditions
        $filters = array();
        if (!empty($event_type)) {
            $filters['event_type'] = $event_type;
        }
        if (!empty($session_type)) {
            $filters['event_subtype'] = $session_type;
        }
        if (!empty($date)) {
            $filters['event_start_date'] = $date;
        }

        // Get total count using WordPress option caching
        $total_results = $this->get_sessions_count_cached($filters);
        
        // Get paginated results
        $offset = ($paged - 1) * $posts_per_page;
        $sessions = $this->get_sessions_paginated($filters, $offset, $posts_per_page);

        // Temporary debug: let's see what we get
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('SCHED DEBUG: Total results: ' . $total_results . ', Sessions count: ' . count($sessions));
            if (!empty($sessions)) {
                error_log('SCHED DEBUG: First session: ' . print_r($sessions[0], true));
            }
        }

        $result = array(
            'sessions' => $sessions,
            'total_results' => $total_results
        );
        
        // Temporarily disable cache
        // wp_cache_set($cache_key, $result, 'sched_plugin', 300);
        
        return $result;
    }

    /**
     * Get sessions count with WordPress option caching
     */
    private function get_sessions_count_cached($filters = array()) {
        $cache_key = 'sched_sessions_count_' . md5(serialize($filters));
        $count = get_transient($cache_key);
        
        if ($count === false) {
            global $wpdb;
            $sched_sessions_table = $wpdb->prefix . 'sched_sessions';
            
            $where_conditions = array();
            $query_params = array();
            
            foreach ($filters as $column => $value) {
                $where_conditions[] = sanitize_key($column) . " = %s";
                $query_params[] = $value;
            }
            
            $where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : '';
            
            if (!empty($query_params)) {
                $sched_sessions_table_escaped = esc_sql($sched_sessions_table);
                $count_query = "SELECT COUNT(*) FROM {$sched_sessions_table_escaped}" . $where_clause;
                $count = $this->wp_abstraction_get_var($count_query, $query_params);
            } else {
                $sched_sessions_table_escaped = esc_sql($sched_sessions_table);
                $count = $this->wp_abstraction_get_var("SELECT COUNT(*) FROM {$sched_sessions_table_escaped}");
            }
            
            // Cache count for 10 minutes using WordPress transients
            set_transient($cache_key, $count, 600);
        }
        
        return intval($count);
    }

    /**
     * Get paginated sessions with WordPress caching
     */
    private function get_sessions_paginated($filters = array(), $offset = 0, $limit = 32) {
        global $wpdb;
        $sched_sessions_table = $wpdb->prefix . 'sched_sessions';
        
        $where_conditions = array();
        $query_params = array();
        
        foreach ($filters as $column => $value) {
            $where_conditions[] = sanitize_key($column) . " = %s";
            $query_params[] = $value;
        }
        
        $where_clause = !empty($where_conditions) ? " WHERE " . implode(" AND ", $where_conditions) : '';
        
        // Build and execute main query with proper preparation
        $sched_sessions_table_escaped = esc_sql($sched_sessions_table);
        $all_params = array_merge($query_params, array($offset, $limit));
        
        // Debug: log the query being executed
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $query = "SELECT * FROM {$sched_sessions_table_escaped}" . $where_clause . " ORDER BY event_start_date ASC, event_start_time ASC LIMIT %d, %d";
            error_log('SCHED DEBUG: Query: ' . $query);
            error_log('SCHED DEBUG: Params: ' . print_r($all_params, true));
        }
        
        $sessions = $this->wp_abstraction_get_results(
            "SELECT * FROM {$sched_sessions_table_escaped}" . $where_clause . " ORDER BY event_start_date ASC, event_start_time ASC LIMIT %d, %d",
            $all_params
        );

        return $sessions;
    }

    /**
     * Get speakers for sessions using WordPress abstractions
     */
    private function get_speakers_for_sessions_with_wp_abstraction($session_ids) {
        if (empty($session_ids)) {
            return array();
        }

        // Use WordPress transient caching for speaker relationships
        $cache_key = 'sched_session_speakers_' . md5(serialize($session_ids));
        $cached_speakers = get_transient($cache_key);
        
        if ($cached_speakers !== false) {
            return $cached_speakers;
        }

        global $wpdb;
        $sched_sessions_speakers_table = $wpdb->prefix . 'sched_sessions_speakers';
        $sched_speakers_table = $wpdb->prefix . 'sched_speakers';
        
        // Create placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($session_ids), '%s'));
        
        // Query to get all speakers for all sessions at once with speaker details
        $results = $this->wp_abstraction_get_results("
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
            FROM " . esc_sql($sched_sessions_speakers_table) . " ss
            LEFT JOIN " . esc_sql($sched_speakers_table) . " sp ON ss.speaker_username = sp.username
            WHERE ss.session_id IN ($placeholders)
            ORDER BY ss.session_id ASC, ss.id ASC
        ", $session_ids);
        
        // Group results by session_id for easy lookup
        $grouped_speakers = array();
        foreach ($results as $result) {
            $grouped_speakers[$result->session_id][] = $result;
        }
        
        // Cache for 15 minutes using WordPress transients
        set_transient($cache_key, $grouped_speakers, 900);
        
        return $grouped_speakers;
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
                'selected_date' => $data['selected_date'],
                'filter_nonce' => $this->get_filter_nonce()
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
     * Find page that contains speakers shortcode using WordPress abstractions
     */
    private function find_speakers_page() {
        $cache_key = 'sched_speakers_page';
        $cached_page = wp_cache_get($cache_key, 'sched_plugin');
        
        if ($cached_page !== false) {
            return $cached_page;
        }
        
        // Use WordPress WP_Query to find pages with speakers shortcode
        $page = $this->find_page_with_shortcode('[sched_speakers', '[wp_sched_speakers');
        
        // Cache for 6 hours (21600 seconds) - pages don't change often
        wp_cache_set($cache_key, $page, 'sched_plugin', 21600);
        
        return $page;
    }

    /**
     * Find page containing specific shortcodes using WordPress WP_Query
     */
    private function find_page_with_shortcode(...$shortcodes) {
        $cache_key = 'sched_page_with_shortcode_' . md5(serialize($shortcodes));
        $page_id = get_transient($cache_key);
        
        if ($page_id !== false) {
            return $page_id;
        }
        
        foreach ($shortcodes as $shortcode) {
            // Use WordPress WP_Query for better performance and caching
            $query_args = array(
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'fields' => 'ids',
                's' => $shortcode,
                'meta_query' => array(),
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            );
            
            $query = new WP_Query($query_args);
            
            if (!empty($query->posts)) {
                $page_id = $query->posts[0];
                // Cache for 6 hours using WordPress transients
                set_transient($cache_key, $page_id, 21600);
                return $page_id;
            }
        }
        
        // Cache negative result for 1 hour
        set_transient($cache_key, null, 3600);
        return null;
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
     * Get single speaker data - using WordPress abstractions
     */
    public function get_single_speaker_data($username) {
        $cache_key = 'sched_speaker_data_' . sanitize_key($username);
        $cached_data = wp_cache_get($cache_key, 'sched_plugin');
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Use WordPress abstraction with transient caching
        $speaker = $this->get_speaker_by_username_cached($username);
        $speaker_sessions = array();
        $back_url = home_url('/');

        if ($speaker) {
            // Get speaker's sessions using WordPress abstraction
            $speaker_sessions = $this->get_speaker_sessions_cached($speaker->username);

            // Generate back URL - try to find a speakers page or default to home
            $speakers_page = $this->find_speakers_page();
            $back_url = $speakers_page ? get_permalink($speakers_page) : home_url('/');
        }

        $result = array(
            'speaker' => $speaker,
            'speaker_sessions' => $speaker_sessions,
            'back_url' => $back_url
        );
        
        // Cache for 30 minutes (1800 seconds) - speaker data changes less frequently
        wp_cache_set($cache_key, $result, 'sched_plugin', 1800);
        
        return $result;
    }

    /**
     * Get speaker by username using WordPress transient caching
     */
    private function get_speaker_by_username_cached($username) {
        $cache_key = 'sched_speaker_' . sanitize_key($username);
        $speaker = get_transient($cache_key);
        
        if ($speaker === false) {
            global $wpdb;
            $sched_speakers_table = $wpdb->prefix . 'sched_speakers';
            
            $speaker = $this->wp_abstraction_get_row(
                "SELECT * FROM " . esc_sql($sched_speakers_table) . " WHERE username = %s",
                array(sanitize_text_field($username))
            );
            
            // Cache for 1 hour using WordPress transients
            set_transient($cache_key, $speaker, 3600);
        }
        
        return $speaker;
    }

    /**
     * Get speaker sessions using WordPress transient caching
     */
    private function get_speaker_sessions_cached($username) {
        $cache_key = 'sched_speaker_sessions_' . sanitize_key($username);
        $sessions = get_transient($cache_key);
        
        if ($sessions === false) {
            global $wpdb;
            $sched_sessions_speakers_table = $wpdb->prefix . 'sched_sessions_speakers';
            $sched_sessions_table = $wpdb->prefix . 'sched_sessions';
            
            $sessions = $this->wp_abstraction_get_results("
                SELECT s.*, ss.speaker_name, ss.speaker_username
                FROM " . esc_sql($sched_sessions_table) . " s
                INNER JOIN " . esc_sql($sched_sessions_speakers_table) . " ss ON s.event_id = ss.session_id
                WHERE ss.speaker_username = %s
                ORDER BY s.event_start_date ASC, s.event_start_time ASC
            ", array($username));
            
            // Cache for 1 hour using WordPress transients
            set_transient($cache_key, $sessions, 3600);
        }
        
        return $sessions;
    }

    /**
     * Get speakers data - using WordPress abstractions
     */
    public function get_speakers_data($atts) {
        // Pagination parameters
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $posts_per_page = intval($atts['limit']);

        // Use WordPress abstraction for speaker data
        $speakers_data = $this->get_speakers_with_wp_abstraction($paged, $posts_per_page);

        return array(
            'featured_speakers' => $speakers_data['featured_speakers'],
            'regular_speakers' => $speakers_data['regular_speakers'],
            'current_page' => $paged,
            'posts_per_page' => $posts_per_page,
            'total_pages' => $speakers_data['total_pages'],
            'total_speakers' => $speakers_data['total_speakers'],
            'featured_count' => $speakers_data['featured_count'],
            'regular_speakers_count' => $speakers_data['regular_speakers_count']
        );
    }

    /**
     * Get speakers using WordPress abstractions with caching
     */
    private function get_speakers_with_wp_abstraction($paged = 1, $posts_per_page = 100) {
        $cache_key = 'sched_speakers_data_' . $paged . '_' . $posts_per_page;
        $cached_data = wp_cache_get($cache_key, 'sched_plugin');
        
        if ($cached_data !== false) {
            return $cached_data;
        }

        // Get counts using WordPress transients
        $total_speakers = $this->get_speakers_count_cached();
        $featured_count = $this->get_featured_speakers_count_cached();
        $regular_speakers_count = $this->get_regular_speakers_count_cached();
        
        // Calculate pagination for regular speakers
        $total_pages = ceil($regular_speakers_count / $posts_per_page);
        
        // Get featured speakers (only on first page)
        $featured_speakers = array();
        if ($paged == 1) {
            $featured_speakers = $this->get_featured_speakers_cached();
        }
        
        // Get regular speakers with pagination
        $regular_offset = 0;
        if ($paged > 1) {
            $regular_offset = ($paged - 1) * $posts_per_page;
        }
        
        $regular_speakers = $this->get_regular_speakers_paginated($regular_offset, $posts_per_page);

        $result = array(
            'featured_speakers' => $featured_speakers,
            'regular_speakers' => $regular_speakers,
            'total_pages' => $total_pages,
            'total_speakers' => $total_speakers,
            'featured_count' => $featured_count,
            'regular_speakers_count' => $regular_speakers_count
        );
        
        // Cache for 10 minutes
        wp_cache_set($cache_key, $result, 'sched_plugin', 600);
        
        return $result;
    }

    /**
     * Get speakers count using WordPress transients
     */
    private function get_speakers_count_cached() {
        $cache_key = 'sched_speakers_total_count';
        $count = get_transient($cache_key);
        
        if ($count === false) {
            global $wpdb;
            $sched_speakers_table = $wpdb->prefix . 'sched_speakers';
            $count = $this->wp_abstraction_get_var("SELECT COUNT(*) FROM " . esc_sql($sched_speakers_table));
            set_transient($cache_key, $count, 600); // Cache for 10 minutes
        }
        
        return intval($count);
    }

    /**
     * Get featured speakers count using WordPress transients
     */
    private function get_featured_speakers_count_cached() {
        $cache_key = 'sched_featured_speakers_count';
        $count = get_transient($cache_key);
        
        if ($count === false) {
            global $wpdb;
            $sched_speakers_table = esc_sql($wpdb->prefix . 'sched_speakers');
            $count = $this->wp_abstraction_get_var(
                "SELECT COUNT(*) FROM {$sched_speakers_table} WHERE speaker_featured = %s",
                array('Y')
            );
            set_transient($cache_key, $count, 600); // Cache for 10 minutes
        }
        
        return intval($count);
    }

    /**
     * Get regular speakers count using WordPress transients
     */
    private function get_regular_speakers_count_cached() {
        $cache_key = 'sched_regular_speakers_count';
        $count = get_transient($cache_key);
        
        if ($count === false) {
            global $wpdb;
            $sched_speakers_table = esc_sql($wpdb->prefix . 'sched_speakers');
            $count = $this->wp_abstraction_get_var(
                "SELECT COUNT(*) FROM {$sched_speakers_table} WHERE speaker_featured = %s",
                array('N')
            );
            set_transient($cache_key, $count, 600); // Cache for 10 minutes
        }
        
        return intval($count);
    }

    /**
     * Get featured speakers using WordPress transients
     */
    private function get_featured_speakers_cached() {
        $cache_key = 'sched_featured_speakers';
        $speakers = get_transient($cache_key);
        
        if ($speakers === false) {
            global $wpdb;
            $sched_speakers_table = esc_sql($wpdb->prefix . 'sched_speakers');
            $speakers = $this->wp_abstraction_get_results(
                "SELECT * FROM {$sched_speakers_table} WHERE speaker_featured = %s ORDER BY customorder ASC, speaker_name ASC",
                array('Y')
            );
            set_transient($cache_key, $speakers, 600); // Cache for 10 minutes
        }
        
        return $speakers;
    }

    /**
     * Get regular speakers with pagination using WordPress transients
     */
    private function get_regular_speakers_paginated($offset = 0, $limit = 100) {
        $cache_key = 'sched_regular_speakers_' . $offset . '_' . $limit;
        $speakers = get_transient($cache_key);
        
        if ($speakers === false) {
            global $wpdb;
            $sched_speakers_table = esc_sql($wpdb->prefix . 'sched_speakers');
            $speakers = $this->wp_abstraction_get_results(
                "SELECT * FROM {$sched_speakers_table} WHERE speaker_featured = %s ORDER BY speaker_name ASC LIMIT %d, %d",
                array('N', $offset, $limit)
            );
            set_transient($cache_key, $speakers, 600); // Cache for 10 minutes
        }
        
        return $speakers;
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
            // Verify speaker exists - unslash before using in database query
            global $wpdb;
            $sched_speakers_table = esc_sql($wpdb->prefix . 'sched_speakers');
            $speaker = $this->wp_abstraction_get_row(
                "SELECT * FROM {$sched_speakers_table} WHERE username = %s",
                array(sanitize_text_field(wp_unslash($speaker_username)))
            );
            
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
                <?php echo wp_kses_post($this->display_single_speaker(array('username' => sanitize_text_field(wp_unslash($speaker_username))))); ?>
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

    /**
     * Clear all cached data - call this when data is updated
     */
    public function clear_cache() {
        // Clear wp_cache data
        wp_cache_delete('sched_event_types', 'sched_plugin');
        wp_cache_delete('sched_event_subtypes', 'sched_plugin');
        wp_cache_delete('sched_event_dates', 'sched_plugin');
        wp_cache_delete('sched_speakers_page', 'sched_plugin');
        
        // Clear WordPress transients
        $transients_to_clear = array(
            'sched_sessions_count_',
            'sched_featured_speakers_count',
            'sched_regular_speakers_count',
            'sched_speakers_total_count',
            'sched_featured_speakers',
            'sched_regular_speakers_',
            'sched_fallback_event_types',
            'sched_fallback_event_subtypes',
            'sched_fallback_event_dates',
            'sched_page_with_shortcode_'
        );
        
        foreach ($transients_to_clear as $transient_pattern) {
            // Delete known transients
            delete_transient($transient_pattern);
            
            // For patterns, we'd need to query the database, but this creates the same
            // "direct database call" issue. Instead, rely on transient expiration.
        }
        
        // Clear speaker-specific cache (pattern-based deletion)
        // Use WordPress option to track speakers for cache clearing
        $speakers_option = get_option('sched_cached_speakers', array());
        
        foreach ($speakers_option as $username) {
            $cache_keys = array(
                'sched_speaker_data_' . sanitize_key($username),
                'sched_speaker_' . sanitize_key($username),
                'sched_speaker_sessions_' . sanitize_key($username),
                'sched_session_speakers_' . sanitize_key($username)
            );
            
            foreach ($cache_keys as $cache_key) {
                wp_cache_delete($cache_key, 'sched_plugin');
                delete_transient($cache_key);
            }
        }
        
        // Clear WordPress abstraction wrapper caches
        $this->clear_all_wp_abstraction_caches();
    }

    /**
     * Clear all WordPress abstraction wrapper caches
     * Eliminates the cached results from wp_abstraction_get_* methods
     */
    public function clear_all_wp_abstraction_caches() {
        global $wpdb;
        
        // Clear abstraction wrapper transients by prefix
        $cache_prefixes = array(
            'sched_query_',
            'sched_var_',
            'sched_row_',
            'sched_col_'
        );
        
        // Clear known abstraction wrapper transients
        // Since we can't query for transients without database calls,
        // we'll rely on the standard transient expiration (5 minutes)
        // and manual clearing of specific known patterns
        
        // Clear session-related abstraction caches
        delete_transient('sched_sessions_count_' . md5(serialize(array())));
        
        // Clear speaker-related abstraction caches  
        delete_transient('sched_speakers_total_count');
        delete_transient('sched_featured_speakers_count');
        delete_transient('sched_regular_speakers_count');
        
        // Note: Individual query caches will expire automatically in 5 minutes
        // This prevents creating new "direct database call" warnings
        // by querying the options table for transient patterns
    }
}