<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class Sched_Activator {

    /**
     * Create necessary database tables and set default options.
     */
    public static function activate() {
        global $wpdb;
        
        $table_sessions = $wpdb->prefix . 'sched_sessions';
        $table_speakers = $wpdb->prefix . 'sched_speakers';
        $table_sessions_speakers = $wpdb->prefix . 'sched_sessions_speakers';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Sessions table based on wp-sched.php schema
        $sql_sessions = "CREATE TABLE $table_sessions (
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
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Speakers table based on wp-sched.php schema
        $sql_speakers = "CREATE TABLE $table_speakers (
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
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Sessions-Speakers pivot table
        $sql_sessions_speakers = "CREATE TABLE $table_sessions_speakers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            speaker_username varchar(255) NOT NULL,
            speaker_name varchar(255) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_sessions);
        dbDelta($sql_speakers);
        dbDelta($sql_sessions_speakers);
        
        // Set default options
        add_option('sched_api_key', '');
        add_option('sched_conference_url', '');
        add_option('sched_pagination_number', 32);
        add_option('sched_last_sync', '');
        add_option('sched_last_sync_timestamp', 0);
        add_option('sched_auto_sync', 0);
        add_option('sched_sync_interval', 'hourly');
        
        // Schedule automatic sync if enabled
        if (get_option('sched_auto_sync')) {
            wp_schedule_event(time(), get_option('sched_sync_interval', 'hourly'), 'sched_sync_cron');
        }
        
        // Add rewrite rules for speaker virtual pages
        add_rewrite_rule('^speakers/([^/]+)/?$', 'index.php?speaker_username=$matches[1]', 'top');
        add_rewrite_tag('%speaker_username%', '([^&]+)');
        
        // Flush rewrite rules to ensure virtual pages work properly
        flush_rewrite_rules();
    }
    
    /**
     * Clean up on plugin deactivation.
     */
    public static function deactivate() {
        // Clear scheduled sync
        wp_clear_scheduled_hook('sched_sync_cron');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Get list of custom database tables created by the plugin.
     *
     * @return array List of table names
     */
    public static function get_plugin_tables() {
        global $wpdb;
        
        return array(
            $wpdb->prefix . 'sched_sessions',
            $wpdb->prefix . 'sched_speakers',
            $wpdb->prefix . 'sched_sessions_speakers'
        );
    }

    /**
     * Get list of plugin options.
     *
     * @return array List of option names
     */
    public static function get_plugin_options() {
        $base_options = array(
            'sched_api_key',
            'sched_conference_url',
            'sched_pagination_number',
            'sched_auto_sync',
            'sched_sync_interval',
            'sched_cleanup_on_delete',
            'sched_last_sync',
            'sched_last_sync_timestamp',
            'sched_db_version',
            'sched_plugin_version',
            'sched_discovered_event_types',
            'sched_discovered_event_subtypes'
        );
        
        // Get dynamic color options
        $color_options = self::get_color_options();
        
        return array_merge($base_options, $color_options);
    }

    /**
     * Get all color-related options that have been saved.
     *
     * @return array List of color option names
     */
    private static function get_color_options() {
        global $wpdb;
        
        $color_options = array();
        
        // Get all options that start with 'sched_color_'
        $results = $wpdb->get_col(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'sched_color_%'"
        );
        
        if ($results) {
            $color_options = array_merge($color_options, $results);
        }
        
        return $color_options;
    }

    /**
     * Get list of scheduled hooks created by the plugin.
     *
     * @return array List of hook names
     */
    public static function get_plugin_hooks() {
        return array(
            'sched_sync_cron',
            'sched_auto_sync_event',
            'sched_cleanup_event'
        );
    }
}