<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate
 * @since      1.0.0
 *
 * @package    Sched_Conference_Plugin
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Include the activator class to access plugin data lists
require_once plugin_dir_path( __FILE__ ) . 'includes/class-sched-activator.php';

/**
 * The WPSched uninstaller.
 *
 * This class handles the cleanup of all plugin data when the plugin is deleted,
 * but only if the user has enabled the cleanup option in the plugin settings.
 */
class Sched_Uninstaller {

    /**
     * Run the uninstaller.
     *
     * This method is called when the plugin is deleted and handles
     * the cleanup of all plugin data if the option is enabled.
     */
    public static function uninstall() {
        // Check if cleanup on delete is enabled
        $cleanup_enabled = get_option('sched_cleanup_on_delete', 0);
        
        if ($cleanup_enabled) {
            self::cleanup_database();
            self::cleanup_options();
            self::cleanup_transients();
            self::cleanup_scheduled_events();
        }
    }

    /**
     * Clean up custom database tables.
     */
    private static function cleanup_database() {
        global $wpdb;

        // Get list of custom tables from activator
        $tables = Sched_Activator::get_plugin_tables();

        foreach ($tables as $table) {
            // For DROP TABLE statements, we need to escape the table name with esc_sql()
            // since table names cannot be parameterized in prepared statements
            $wpdb->query(
                "DROP TABLE IF EXISTS " . esc_sql($table)
            );
        }
    }

    /**
     * Clean up plugin options.
     */
    private static function cleanup_options() {
        // Get list of plugin options from activator
        $options = Sched_Activator::get_plugin_options();

        foreach ($options as $option) {
            delete_option($option);
        }
    }

    /**
     * Clean up transients.
     */
    private static function cleanup_transients() {
        global $wpdb;

        // Remove all transients that start with 'sched_'
        $options_table = esc_sql($wpdb->options);
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$options_table} WHERE option_name LIKE %s OR option_name LIKE %s",
                '_transient_sched_%',
                '_transient_timeout_sched_%'
            )
        );
    }

    /**
     * Clean up scheduled events (cron jobs).
     */
    private static function cleanup_scheduled_events() {
        // Get list of scheduled hooks from activator
        $hooks = Sched_Activator::get_plugin_hooks();
        
        foreach ($hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }
}

// Run the uninstaller
Sched_Uninstaller::uninstall();