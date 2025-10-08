<?php
if (!function_exists('get_option')) {
    if (defined('ABSPATH')) {
        require_once(ABSPATH . 'wp-load.php');
    }
}
if (!function_exists('is_wp_error')) {
    require_once(ABSPATH . 'wp-includes/functions.php');
}

class Sched_API {

    private $api_key;
    private $conference_url;
    private $base_url;
    private $rate_limit_max = 25;
    private $rate_limit_window = 60; // 1 minute in seconds

    public function __construct() {
        $this->api_key = get_option('sched_api_key', '');
        $this->conference_url = get_option('sched_conference_url', '');
        
        // Check if API settings have changed and clear tables if needed
        $this->check_and_handle_config_changes();
        
        if ($this->conference_url) {
            // Extract subdomain from URL like https://gijc25.sched.com
            $parsed = wp_parse_url($this->conference_url);
            $host_parts = explode('.', $parsed['host']);
            $subdomain = $host_parts[0];
            $this->base_url = "https://{$subdomain}.sched.com/api";
        }
    }

    /**
     * Check rate limit and enforce delay if necessary
     */
    private function check_rate_limit() {
        $current_count = get_transient('sched_api_calls_count');
        $last_reset = get_transient('sched_api_calls_reset');
        
        // Initialize if not set
        if ($current_count === false) {
            set_transient('sched_api_calls_count', 0, $this->rate_limit_window);
            set_transient('sched_api_calls_reset', time(), $this->rate_limit_window);
            return true;
        }
        
        // Reset counter if window has passed
        if ($last_reset && (time() - $last_reset) >= $this->rate_limit_window) {
            delete_transient('sched_api_calls_count');
            delete_transient('sched_api_calls_reset');
            set_transient('sched_api_calls_count', 0, $this->rate_limit_window);
            set_transient('sched_api_calls_reset', time(), $this->rate_limit_window);
            return true;
        }
        
        // Check if we're at the limit
        if ($current_count >= $this->rate_limit_max) {
            $time_remaining = $this->rate_limit_window - (time() - $last_reset);
            if ($time_remaining > 0) {
                // Wait for the remaining time
                sleep($time_remaining);
                // Reset counters after waiting
                delete_transient('sched_api_calls_count');
                delete_transient('sched_api_calls_reset');
                set_transient('sched_api_calls_count', 0, $this->rate_limit_window);
                set_transient('sched_api_calls_reset', time(), $this->rate_limit_window);
            }
        }
        
        return true;
    }

    /**
     * Increment rate limit counter
     */
    private function increment_rate_limit() {
        $current_count = get_transient('sched_api_calls_count');
        if ($current_count === false) {
            $current_count = 0;
        }
        set_transient('sched_api_calls_count', $current_count + 1, $this->rate_limit_window);
    }

    /**
     * Make rate-limited API request
     */
    private function make_api_request($url, $args = array()) {
        // Check rate limit before making request
        $this->check_rate_limit();
        
        // Make the request
        $response = wp_remote_request($url, $args);
        
        // Increment counter after successful call
        $this->increment_rate_limit();
        
        return $response;
    }

    /**
     * Check if API configuration has changed and clear tables if needed
     */
    private function check_and_handle_config_changes() {
        // Get previously stored config
        $prev_api_key = get_option('sched_prev_api_key', '');
        $prev_conference_url = get_option('sched_prev_conference_url', '');
        
        // Check if this is the first time or if config has changed
        $config_changed = false;
        
        if (empty($prev_api_key) && empty($prev_conference_url)) {
            // First time setup - store current values but don't clear tables
            update_option('sched_prev_api_key', $this->api_key);
            update_option('sched_prev_conference_url', $this->conference_url);
            return;
        }
        
        if ($prev_api_key !== $this->api_key || $prev_conference_url !== $this->conference_url) {
            $config_changed = true;
        }
        
        if ($config_changed) {
            // Clear all tables since we're switching conferences/API keys
            $this->clear_all_tables();
            
            // Update stored config
            update_option('sched_prev_api_key', $this->api_key);
            update_option('sched_prev_conference_url', $this->conference_url);
            
            // Clear sync timestamps since data is now stale
            delete_option('sched_last_sync_timestamp');
            delete_option('sched_last_sync');
        }
    }

    /**
     * Clear all plugin tables when config changes
     */
    private function clear_all_tables() {
        global $wpdb;
        
        $sessions_table = esc_sql($wpdb->prefix . 'sched_sessions');
        $speakers_table = esc_sql($wpdb->prefix . 'sched_speakers');
        $sessions_speakers_table = esc_sql($wpdb->prefix . 'sched_sessions_speakers');
        
        // Only truncate if tables exist
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'sched_sessions')) == $wpdb->prefix . 'sched_sessions') {
            $wpdb->query("DELETE FROM {$sessions_table}");
        }
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'sched_speakers')) == $wpdb->prefix . 'sched_speakers') {
            $wpdb->query("DELETE FROM {$speakers_table}");
        }
        
        if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->prefix . 'sched_sessions_speakers')) == $wpdb->prefix . 'sched_sessions_speakers') {
            $wpdb->query("DELETE FROM {$sessions_speakers_table}");
        }
    }

    /**
     * Clear all plugin caches after sync
     */
    private function clear_all_plugin_caches() {
        global $wpdb;
        
        // Clear all transient caches (5-minute query caches)
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_sched_%' OR option_name LIKE '_transient_timeout_sched_%'");
        
        // Clear object cache
        wp_cache_flush_group('sched_plugin');
        
        // Clear specific options cache that might contain stale filter data
        delete_option('sched_discovered_event_types');
        delete_option('sched_discovered_event_subtypes');
        delete_option('sched_discovered_event_dates');
        
        // Clear WordPress object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
    }

    /**
     * Test connection to Sched API
     */
    public function test_connection() {
        if (empty($this->api_key) || empty($this->base_url)) {
            return new WP_Error('missing_config', 'API key or conference URL not configured.');
        }

        // Test with session/export endpoint using POST method
        $test_url = $this->base_url . '/session/export';
        $test_args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'WP Sched',
            ),
        );
        
        $request_url = $test_url . '?api_key=' . urlencode($this->api_key) . '&format=json&limit=1';
        $test_response = $this->make_api_request($request_url, $test_args);
        
        if (is_wp_error($test_response)) {
            return $test_response;
        }

        $response_code = wp_remote_retrieve_response_code($test_response);
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'API returned status code: ' . $response_code);
        }

        return true;
    }

    /**
     * Sync all data - create tables and import sessions and speakers
     */
    public function sync_all_data() {
        // Create/truncate tables first
        $this->create_custom_tables();
        
        $sessions_count = 0;
        $speakers_count = 0;
        $errors = array();
        
        // Get and insert sessions
        $sessions_result = $this->get_and_insert_sessions();
        if (is_wp_error($sessions_result)) {
            $errors[] = 'Sessions: ' . $sessions_result->get_error_message();
        } else {
            $sessions_count = $sessions_result;
        }
        
        // Get and insert speakers
        $speakers_result = $this->get_and_insert_speakers();
        if (is_wp_error($speakers_result)) {
            $errors[] = 'Speakers: ' . $speakers_result->get_error_message();
        } else {
            $speakers_count = $speakers_result;
        }
        
        // Update sync timestamp
        update_option('sched_last_sync_timestamp', time());
        update_option('sched_last_sync', current_time('mysql'));
        
        // ADDED: Clear all caches after successful sync
        $this->clear_all_plugin_caches();
        
        if (!empty($errors) && $sessions_count === 0 && $speakers_count === 0) {
            return new WP_Error('sync_failed', implode('; ', $errors));
        }
        
        return array(
            'sessions' => $sessions_count,
            'speakers' => $speakers_count,
            'method' => 'wp-sched compatible',
            'errors' => $errors
        );
    }

    /**
     * Create custom tables based on wp-sched.php approach
     */
    private function create_custom_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sessions_table_name = $wpdb->prefix . 'sched_sessions';
        $speakers_table_name = $wpdb->prefix . 'sched_speakers';
        $sessions_speakers_table_name = $wpdb->prefix . 'sched_sessions_speakers';

        // Create sessions table or truncate if exists
        if($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $sessions_table_name)) != $sessions_table_name) {
            $sessions_table_escaped = esc_sql($sessions_table_name);
            $sessions_sql = "CREATE TABLE {$sessions_table_escaped} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                event_key varchar(255) NULL,
                event_active varchar(255) NULL,
                pinned varchar(255) NULL,
                event_name varchar(255) NULL,
                event_start varchar(255) NULL,
                event_end varchar(255) NULL,
                event_type varchar(255) NULL,
                event_subtype varchar(255) NULL,
                event_description text NULL,
                goers varchar(255) NULL,
                seats varchar(255) NULL,
                invite_only varchar(255) NULL,
                venue varchar(255) NULL,
                event_address varchar(255) NULL,
                event_id varchar(255) NULL,
                event_start_year varchar(255) NULL,
                event_start_month varchar(255) NULL,
                event_start_month_short varchar(255) NULL,
                event_start_day varchar(255) NULL,
                event_start_weekday varchar(255) NULL,
                event_start_weekday_short varchar(255) NULL,
                event_start_time varchar(255) NULL,
                event_end_year varchar(255) NULL,
                event_end_month varchar(255) NULL,
                event_end_month_short varchar(255) NULL,
                event_end_day varchar(255) NULL,
                event_end_weekday varchar(255) NULL,
                event_end_weekday_short varchar(255) NULL,
                event_end_time varchar(255) NULL,
                event_start_date varchar(255) NULL,
                event_start_datetime varchar(255) NULL,
                event_end_date varchar(255) NULL,
                event_end_datetime varchar(255) NULL,
                event_start_time_ts varchar(255) NULL,
                event_type_sort varchar(255) NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sessions_sql);
        } else {
            $sessions_table_escaped = esc_sql($sessions_table_name);
            $wpdb->query("DELETE FROM {$sessions_table_escaped}");
        }

        // Create speakers table or truncate if exists
        if($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $speakers_table_name)) != $speakers_table_name) {
            $speakers_table_escaped = esc_sql($speakers_table_name);
            $speakers_sql = "CREATE TABLE {$speakers_table_escaped} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                customorder varchar(255) NULL,
                username varchar(255) NOT NULL,
                speaker_name varchar(255) NULL,
                speaker_company varchar(255) NULL,
                speaker_position varchar(255) NULL,
                speaker_location text NULL,
                speaker_about text NULL,
                speaker_url varchar(255) NULL,
                speaker_avatar varchar(255) NULL,
                speaker_featured varchar(255) NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($speakers_sql);
        } else {
            $speakers_table_escaped = esc_sql($speakers_table_name);
            $wpdb->query("DELETE FROM {$speakers_table_escaped}");
        }

        // Create sessions-speakers pivot table or truncate if exists
        if($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $sessions_speakers_table_name)) != $sessions_speakers_table_name) {
            $sessions_speakers_table_escaped = esc_sql($sessions_speakers_table_name);
            $sessions_speakers_sql = "CREATE TABLE {$sessions_speakers_table_escaped} (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                session_id varchar(255) NOT NULL,
                speaker_username varchar(255) NOT NULL,
                speaker_name varchar(255) NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sessions_speakers_sql);
        } else {
            $sessions_speakers_table_escaped = esc_sql($sessions_speakers_table_name);
            $wpdb->query("DELETE FROM {$sessions_speakers_table_escaped}");
        }
    }

    /**
     * Get sessions from API and insert into database
     */
    private function get_and_insert_sessions() {
        if (empty($this->api_key) || empty($this->base_url)) {
            return new WP_Error('missing_config', 'API key or conference URL not configured.');
        }

        $sessions_url = $this->base_url . '/session/export?api_key=' . urlencode($this->api_key) . '&format=json&strip_html=N';
        
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'WP Sched',
            ),
        );

        $sessions_response = $this->make_api_request($sessions_url, $args);
        
        if (is_wp_error($sessions_response)) {
            return $sessions_response;
        }

        $response_code = wp_remote_retrieve_response_code($sessions_response);
        if ($response_code !== 200) {
            return new WP_Error('api_error', 'Sessions API returned status code: ' . $response_code);
        }

        $sessions_body = wp_remote_retrieve_body($sessions_response);
        $sessions = json_decode($sessions_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Invalid JSON response from Sessions API.');
        }

        return $this->insert_session_data($sessions);
    }

    /**
     * Insert session data - based on wp-sched.php function
     */
    private function insert_session_data($sessions) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sched_sessions';
        $count = 0;

        foreach ($sessions as $session) {
            if ($session['active'] == "Y") {
                $wpdb->insert(
                    $table_name,
                    array(
                        'event_key' => array_key_exists('event_key', $session) ? $session['event_key'] : '',
                        'event_active' => array_key_exists('active', $session) ? $session['active'] : '',
                        'pinned' => array_key_exists('pinned', $session) ? $session['pinned'] : '',
                        'event_name' => array_key_exists('name', $session) ? $session['name'] : '',
                        'event_start' => array_key_exists('event_start', $session) ? $session['event_start'] : '',
                        'event_end' => array_key_exists('event_end', $session) ? $session['event_end'] : '',
                        'event_type' => array_key_exists('event_type', $session) ? $session['event_type'] : '',
                        'event_subtype' => array_key_exists('event_subtype', $session) ? $session['event_subtype'] : '',
                        'event_description' => array_key_exists('description', $session) ? $session['description'] : '',
                        'goers' => array_key_exists('goers', $session) ? $session['goers'] : '',
                        'seats' => array_key_exists('seats', $session) ? $session['seats'] : '',
                        'invite_only' => array_key_exists('invite_only', $session) ? $session['invite_only'] : '',
                        'venue' => array_key_exists('venue', $session) ? $session['venue'] : '',
                        'event_address' => array_key_exists('address', $session) ? $session['address'] : '',
                        'event_id' => array_key_exists('id', $session) ? $session['id'] : '',
                        'event_start_year' => array_key_exists('event_start_year', $session) ? $session['event_start_year'] : '',
                        'event_start_month' => array_key_exists('event_start_month', $session) ? $session['event_start_month'] : '',
                        'event_start_month_short' => array_key_exists('event_start_month_short', $session) ? $session['event_start_month_short'] : '',
                        'event_start_day' => array_key_exists('event_start_day', $session) ? $session['event_start_day'] : '',
                        'event_start_weekday' => array_key_exists('event_start_weekday', $session) ? $session['event_start_weekday'] : '',
                        'event_start_weekday_short' => array_key_exists('event_start_weekday_short', $session) ? $session['event_start_weekday_short'] : '',
                        'event_start_time' => array_key_exists('event_start_time', $session) ? $session['event_start_time'] : '',
                        'event_end_year' => array_key_exists('event_end_year', $session) ? $session['event_end_year'] : '',
                        'event_end_month' => array_key_exists('event_end_month', $session) ? $session['event_end_month'] : '',
                        'event_end_month_short' => array_key_exists('event_end_month_short', $session) ? $session['event_end_month_short'] : '',
                        'event_end_day' => array_key_exists('event_end_day', $session) ? $session['event_end_day'] : '',
                        'event_end_weekday' => array_key_exists('event_end_weekday', $session) ? $session['event_end_weekday'] : '',
                        'event_end_weekday_short' => array_key_exists('event_end_weekday_short', $session) ? $session['event_end_weekday_short'] : '',
                        'event_end_time' => array_key_exists('event_end_time', $session) ? $session['event_end_time'] : '',
                        'event_start_date' => array_key_exists('start_date', $session) ? $session['start_date'] : '',
                        'event_start_datetime' => array_key_exists('start_date', $session) ? $session['start_date'] . ' ' . $session['start_time'] : '',
                        'event_end_date' => array_key_exists('end_date', $session) ? $session['end_date'] : '',
                        'event_end_datetime' => array_key_exists('end_date', $session) ? $session['end_date'] . ' ' . $session['end_time'] : '',
                        'event_start_time_ts' => array_key_exists('start_time_ts', $session) ? $session['start_time_ts'] : '',
                        'event_type_sort' => array_key_exists('event_type_sort', $session) ? $session['event_type_sort'] : '',
                    )
                );

                $count++;

                // Handle speakers for this session
                if (array_key_exists('speakers', $session)) {
                    foreach ($session['speakers'] as $speaker) {
                        $wpdb->insert(
                            $wpdb->prefix . 'sched_sessions_speakers',
                            array(
                                'session_id' => $session['id'],
                                'speaker_username' => $speaker['username'],
                                'speaker_name' => $speaker['name'],
                            )
                        );
                    }
                }
            }
        }

        // Collect unique event types and subtypes for color settings
        $this->collect_event_types($sessions);

        return $count;
    }

    /**
     * Collect unique event types and subtypes during sync
     */
    private function collect_event_types($sessions) {
        $event_types = array();
        $event_subtypes = array();
        
        foreach ($sessions as $session) {
            if ($session['active'] == "Y") {
                if (!empty($session['event_type'])) {
                    $event_types[] = $session['event_type'];
                }
                if (!empty($session['event_subtype'])) {
                    $event_subtypes[] = $session['event_subtype'];
                }
            }
        }
        
        // Store unique discovered types for admin settings
        update_option('sched_discovered_event_types', array_unique($event_types));
        update_option('sched_discovered_event_subtypes', array_unique($event_subtypes));
    }

    /**
     * Get speakers from API and insert into database
     */
    private function get_and_insert_speakers() {
        if (empty($this->api_key) || empty($this->base_url)) {
            return new WP_Error('missing_config', 'API key or conference URL not configured.');
        }

        $featured_speakers_url = $this->base_url . '/role/export?api_key=' . urlencode($this->api_key) . '&role=speaker&format=json&featured=y&strip_html=Y&fields=customorder,username,name,company,position,location,about,url,avatar,sessions';
        $all_speakers_url = $this->base_url . '/role/export?api_key=' . urlencode($this->api_key) . '&role=speaker&format=json&strip_html=Y&fields=customorder,username,name,company,position,location,about,url,avatar,sessions';
        
        $args = array(
            'method' => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'User-Agent' => 'WP Sched',
            ),
        );

        // Get featured speakers
        $featured_response = $this->make_api_request($featured_speakers_url, $args);
        $all_response = $this->make_api_request($all_speakers_url, $args);
        
        if (is_wp_error($featured_response) || is_wp_error($all_response)) {
            return new WP_Error('api_error', 'Error fetching speaker data from API');
        }

        $featured_body = wp_remote_retrieve_body($featured_response);
        $all_body = wp_remote_retrieve_body($all_response);
        
        $featured_speakers = json_decode($featured_body, true);
        $all_speakers = json_decode($all_body, true);

        $count = 0;
        
        // FIXED: Detect if featured API returned all speakers (Sched.com behavior when no speakers are marked as featured)
        $actual_featured_speakers = array();
        if ($featured_speakers && $all_speakers) {
            if (count($featured_speakers) === count($all_speakers)) {
                // Featured API returned same count as all speakers - means no speakers are actually featured
                $actual_featured_speakers = array();
            } else {
                // There are actual featured speakers (different counts)
                $actual_featured_speakers = $featured_speakers;
            }
        }
        
        // Insert actual featured speakers only if there are any
        if (!empty($actual_featured_speakers)) {
            $count += $this->insert_featured_speakers_data($actual_featured_speakers);
        }
        
        // Insert all speakers as regular speakers
        if ($all_speakers) {
            $count += $this->insert_speakers_data($all_speakers);
        }

        return $count;
    }

    /**
     * Insert featured speakers data
     */
    private function insert_featured_speakers_data($speakers) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sched_speakers';
        $count = 0;
        
        foreach ($speakers as $speaker) {
            $wpdb->insert(
                $table_name,
                array(
                    'customorder' => array_key_exists('customorder', $speaker) ? $speaker['customorder'] : '',
                    'username' => array_key_exists('username', $speaker) ? $speaker['username'] : '',
                    'speaker_name' => array_key_exists('name', $speaker) ? $speaker['name'] : '',
                    'speaker_company' => array_key_exists('company', $speaker) ? $speaker['company'] : '',
                    'speaker_position' => array_key_exists('position', $speaker) ? $speaker['position'] : '',
                    'speaker_location' => array_key_exists('location', $speaker) ? $speaker['location'] : '',
                    'speaker_about' => array_key_exists('about', $speaker) ? $speaker['about'] : '',
                    'speaker_url' => array_key_exists('url', $speaker) ? $speaker['url'] : '',
                    'speaker_avatar' => array_key_exists('avatar', $speaker) ? $speaker['avatar'] : '',
                    'speaker_featured' => 'Y',
                )
            );
            $count++;
        }
        
        return $count;
    }

    /**
     * Insert speakers data
     */
    private function insert_speakers_data($speakers) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sched_speakers';
        $count = 0;
        
        foreach ($speakers as $speaker) {
            // Check if user doesn't exist in table and insert
            if (!$this->check_user_exists($speaker['username'])) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'customorder' => array_key_exists('customorder', $speaker) ? $speaker['customorder'] : '',
                        'username' => array_key_exists('username', $speaker) ? $speaker['username'] : '',
                        'speaker_name' => array_key_exists('name', $speaker) ? $speaker['name'] : '',
                        'speaker_company' => array_key_exists('company', $speaker) ? $speaker['company'] : '',
                        'speaker_position' => array_key_exists('position', $speaker) ? $speaker['position'] : '',
                        'speaker_location' => array_key_exists('location', $speaker) ? $speaker['location'] : '',
                        'speaker_about' => array_key_exists('about', $speaker) ? $speaker['about'] : '',
                        'speaker_url' => array_key_exists('url', $speaker) ? $speaker['url'] : '',
                        'speaker_avatar' => array_key_exists('avatar', $speaker) ? $speaker['avatar'] : '',
                        'speaker_featured' => 'N',
                    )
                );
                $count++;
            }
        }
        
        return $count;
    }

    /**
     * Check if user exists in speakers table
     */
    private function check_user_exists($username) {
        global $wpdb;
        $table_name = esc_sql($wpdb->prefix . 'sched_speakers');
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE username = %s",
            $username
        ));
        return !empty($results);
    }

    /**
     * Legacy methods for compatibility
     */
    public function sync_sessions() {
        $result = $this->sync_all_data();
        if (is_wp_error($result)) {
            return $result;
        }
        return $result['sessions'] ?? 0;
    }

    public function sync_speakers() {
        $result = $this->sync_all_data();
        if (is_wp_error($result)) {
            return $result;
        }
        return $result['speakers'] ?? 0;
    }
}
