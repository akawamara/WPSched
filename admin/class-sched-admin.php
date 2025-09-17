<?php

/**
 * The admin-specific functionality of the plugin.
 */
class Sched_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/sched-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/sched-admin.js', array('jquery'), $this->version, false);
        
        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'sched_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sched_nonce')
        ));
    }

    /**
     * Add admin menu page.
     */
    public function add_admin_menu() {
        add_options_page(
            'Sched Conference Settings',
            'Sched Conference',
            'manage_options',
            'sched-conference-plugin',
            array($this, 'create_admin_page')
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting('sched_settings_group', 'sched_api_key');
        register_setting('sched_settings_group', 'sched_conference_url');
        register_setting('sched_settings_group', 'sched_pagination_number');
        register_setting('sched_settings_group', 'sched_auto_sync');
        register_setting('sched_settings_group', 'sched_sync_interval');
        register_setting('sched_settings_group', 'sched_cleanup_on_delete');

        // Register color settings dynamically
        $this->register_color_settings();

        add_settings_section(
            'sched_api_section',
            'API Configuration',
            array($this, 'api_section_callback'),
            'sched-conference-plugin'
        );

        add_settings_section(
            'sched_cleanup_section',
            'Data Management',
            array($this, 'cleanup_section_callback'),
            'sched-conference-plugin'
        );

        add_settings_section(
            'sched_event_colors',
            'Event Type Colors',
            array($this, 'event_colors_section_callback'),
            'sched-conference-plugin'
        );

        add_settings_section(
            'sched_subtype_colors',
            'Event Subtype Colors',
            array($this, 'subtype_colors_section_callback'),
            'sched-conference-plugin'
        );

        add_settings_field(
            'sched_api_key',
            'API Key',
            array($this, 'api_key_callback'),
            'sched-conference-plugin',
            'sched_api_section'
        );

        add_settings_field(
            'sched_conference_url',
            'Conference URL',
            array($this, 'conference_url_callback'),
            'sched-conference-plugin',
            'sched_api_section'
        );

        add_settings_field(
            'sched_pagination_number',
            'Items Per Page',
            array($this, 'pagination_callback'),
            'sched-conference-plugin',
            'sched_api_section'
        );

        add_settings_field(
            'sched_auto_sync',
            'Auto Sync',
            array($this, 'auto_sync_callback'),
            'sched-conference-plugin',
            'sched_api_section'
        );

        add_settings_field(
            'sched_sync_interval',
            'Sync Interval',
            array($this, 'sync_interval_callback'),
            'sched-conference-plugin',
            'sched_api_section'
        );

        add_settings_field(
            'sched_cleanup_on_delete',
            'Remove Data on Plugin Delete',
            array($this, 'cleanup_on_delete_callback'),
            'sched-conference-plugin',
            'sched_cleanup_section'
        );
    }

    /**
     * Create the admin page.
     */
    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>Sched Conference Plugin Settings</h1>
            
            <?php settings_errors(); ?>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('sched_settings_group');
                do_settings_sections('sched-conference-plugin');
                submit_button();
                ?>
            </form>
            
            <hr>
            
            <h2>Sync Data</h2>
            <p>Manually sync sessions and speakers from Sched.com</p>
            
            <div id="sync-status"></div>
            
            <button type="button" id="test-connection" class="button">Test Connection</button>
            <button type="button" id="sync-data" class="button button-primary">Sync Data Now</button>
            
            <p><strong>Last Sync:</strong> <?php echo get_option('sched_last_sync', 'Never'); ?></p>
            
            <hr>
            
            <h2>Shortcodes</h2>
            <p>Use these shortcodes to display sessions and speakers:</p>
            <ul>
                <li><code>[sched_sessions]</code> - Display all sessions</li>
                <li><code>[sched_sessions type="keynote"]</code> - Display sessions by type</li>
                <li><code>[sched_speakers]</code> - Display all speakers</li>
            </ul>
            
            <h3>Available Parameters:</h3>
            <h4>Sessions:</h4>
            <ul>
                <li><code>type</code> - Filter by session type</li>
                <li><code>limit</code> - Number of items to show</li>
                <li><code>pagination</code> - Enable pagination (true/false)</li>
            </ul>
            
            <h4>Speakers:</h4>
            <ul>
                <li><code>limit</code> - Number of items to show</li>
                <li><code>pagination</code> - Enable pagination (true/false)</li>
            </ul>
        </div>
        <?php
    }

    // Callback functions for settings fields
    public function api_section_callback() {
        echo '<p>Enter your Sched.com API credentials and configuration.</p>';
    }

    public function api_key_callback() {
        $value = get_option('sched_api_key', '');
        echo '<input type="password" id="sched_api_key" name="sched_api_key" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your Sched.com API key</p>';
    }

    public function conference_url_callback() {
        $value = get_option('sched_conference_url', '');
        echo '<input type="url" id="sched_conference_url" name="sched_conference_url" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Full URL to your conference (e.g., https://yourconference.sched.com)</p>';
    }

    public function pagination_callback() {
        $value = get_option('sched_pagination_number', 10);
        echo '<input type="number" id="sched_pagination_number" name="sched_pagination_number" value="' . esc_attr($value) . '" min="1" max="100" />';
        echo '<p class="description">Number of items to display per page</p>';
    }

    public function auto_sync_callback() {
        $value = get_option('sched_auto_sync', 1);
        echo '<input type="checkbox" id="sched_auto_sync" name="sched_auto_sync" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="sched_auto_sync">Enable automatic synchronization</label>';
    }

    public function sync_interval_callback() {
        $value = get_option('sched_sync_interval', 'hourly');
        $intervals = array(
            'hourly' => 'Every Hour',
            'twicedaily' => 'Twice Daily',
            'daily' => 'Daily'
        );
        
        echo '<select id="sched_sync_interval" name="sched_sync_interval">';
        foreach ($intervals as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function cleanup_section_callback() {
        echo '<p>Configure data management and cleanup options.</p>';
    }

    public function cleanup_on_delete_callback() {
        $value = get_option('sched_cleanup_on_delete', 0);
        echo '<input type="checkbox" id="sched_cleanup_on_delete" name="sched_cleanup_on_delete" value="1" ' . checked(1, $value, false) . ' />';
        echo '<label for="sched_cleanup_on_delete">Remove all plugin data when plugin is deleted</label>';
        echo '<p class="description"><strong>Warning:</strong> When enabled, deleting this plugin will permanently remove all conference data, sessions, speakers, and plugin settings from your database. This action cannot be undone.</p>';
    }

    /**
     * AJAX handler for testing connection.
     */
    public function ajax_test_connection() {
        check_ajax_referer('sched_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'sched-conference-plugin'));
        }

        $api = new Sched_API();
        $result = $api->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(__('Connection successful!', 'sched-conference-plugin'));
        }
    }

    /**
     * AJAX handler for debugging connection.
     */
    public function ajax_debug_connection() {
        check_ajax_referer('sched_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        $api = new Sched_API();
        $result = $api->test_connection();

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX handler for syncing data.
     */
    public function ajax_sync_data() {
        check_ajax_referer('sched_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }

        $api = new Sched_API();
        
        // Use the new unified sync method
        $result = $api->sync_all_data();

        $messages = array();
        
        if (is_wp_error($result)) {
            $messages[] = 'Sync failed: ' . $result->get_error_message();
        } else if (is_array($result)) {
            $total_sessions = $result['sessions'] ?? 0;
            $total_speakers = $result['speakers'] ?? 0;
            $method = $result['method'] ?? 'standard';
            $errors = $result['errors'] ?? array();
            
            $success_message = "Synced {$total_sessions} sessions and {$total_speakers} speakers";
            if ($method === 'export APIs (read-only optimized)') {
                $success_message .= " (using Sched Export APIs - Session Export & Role Export)";
            } elseif ($method === 'read-only compatible') {
                $success_message .= " (using read-only API endpoints)";
            }
            $success_message .= ".";
            
            $messages[] = $success_message;
            
            if (!empty($errors)) {
                $messages[] = "Warnings: " . implode('; ', $errors);
            }
        } else {
            $messages[] = 'Sync completed but no data returned.';
        }

        wp_send_json_success(implode(' ', $messages));
    }

    /**
     * Register color settings dynamically
     */
    private function register_color_settings() {
        // Get discovered types from last sync
        $event_types = get_option('sched_discovered_event_types', array());
        $event_subtypes = get_option('sched_discovered_event_subtypes', array());

        // Register settings for each event type
        foreach ($event_types as $type) {
            $setting_name = 'sched_color_type_' . sanitize_key($type);
            register_setting('sched_settings_group', $setting_name);
        }

        // Register settings for each event subtype
        foreach ($event_subtypes as $subtype) {
            $setting_name = 'sched_color_subtype_' . sanitize_key($subtype);
            register_setting('sched_settings_group', $setting_name);
        }
    }

    /**
     * Event colors section callback
     */
    public function event_colors_section_callback() {
        echo '<p>Set colors to match your Sched.com event type colors. Colors will be applied to session cards and speakers.</p>';
        echo '<p><strong>Instructions:</strong> Check your Sched.com admin panel for the exact colors used for each event type.</p>';
        
        $event_types = get_option('sched_discovered_event_types', array());
        if (empty($event_types)) {
            echo '<p><em>No event types discovered yet. Please sync data first to see available event types.</em></p>';
            return;
        }

        echo '<div class="sched-color-grid">';
        foreach ($event_types as $type) {
            $setting_name = 'sched_color_type_' . sanitize_key($type);
            $value = get_option($setting_name, '#333333');
            
            echo '<div class="color-setting-row">';
            echo '<label for="' . esc_attr($setting_name) . '">' . esc_html($type) . '</label>';
            echo '<input type="color" id="' . esc_attr($setting_name) . '" name="' . esc_attr($setting_name) . '" value="' . esc_attr($value) . '" class="color-picker" />';
            echo '<input type="text" id="' . esc_attr($setting_name) . '_text" value="' . esc_attr($value) . '" class="color-text" maxlength="7" />';
            echo '<div class="color-preview" style="background-color: ' . esc_attr($value) . '"></div>';
            echo '<p class="description">Match the color used in Sched.com for "' . esc_html($type) . '"</p>';
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Subtype colors section callback
     */
    public function subtype_colors_section_callback() {
        echo '<p>Set colors for event subtypes. These are typically used for accent colors or borders.</p>';
        
        $event_subtypes = get_option('sched_discovered_event_subtypes', array());
        if (empty($event_subtypes)) {
            echo '<p><em>No event subtypes discovered yet. Please sync data first to see available event subtypes.</em></p>';
            return;
        }

        echo '<div class="sched-color-grid">';
        foreach ($event_subtypes as $subtype) {
            $setting_name = 'sched_color_subtype_' . sanitize_key($subtype);
            $value = get_option($setting_name, '#666666');
            
            echo '<div class="color-setting-row">';
            echo '<label for="' . esc_attr($setting_name) . '">' . esc_html($subtype) . '</label>';
            echo '<input type="color" id="' . esc_attr($setting_name) . '" name="' . esc_attr($setting_name) . '" value="' . esc_attr($value) . '" class="color-picker" />';
            echo '<input type="text" id="' . esc_attr($setting_name) . '_text" value="' . esc_attr($value) . '" class="color-text" maxlength="7" />';
            echo '<div class="color-preview" style="background-color: ' . esc_attr($value) . '"></div>';
            echo '<p class="description">Match the color used in Sched.com for "' . esc_html($subtype) . '"</p>';
            echo '</div>';
        }
        echo '</div>';
    }
}