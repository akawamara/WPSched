<?php
/**
 * Plugin Name: WPSched
 * Description: A WordPress plugin to list conference sessions and speakers using the Sched API.
 * Version: 1.0.0
 * Author: Alan Kawamara
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wpsched
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.8.2
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'SCHED_PLUGIN_VERSION', '1.0.0' );
define( 'SCHED_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SCHED_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SCHED_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include necessary files
require_once SCHED_PLUGIN_DIR . 'includes/class-sched.php';
require_once SCHED_PLUGIN_DIR . 'includes/class-sched-loader.php';
require_once SCHED_PLUGIN_DIR . 'includes/class-sched-api.php';
require_once SCHED_PLUGIN_DIR . 'includes/class-sched-activator.php';
require_once SCHED_PLUGIN_DIR . 'admin/class-sched-admin.php';
require_once SCHED_PLUGIN_DIR . 'public/class-sched-public.php';

// Activation and Deactivation hooks
register_activation_hook( __FILE__, array( 'Sched_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Sched_Activator', 'deactivate' ) );

// Initialize the plugin
function run_sched_conference_plugin() {
    $plugin = new Sched();
    $plugin->run();
}

// Hook to initialize plugin after WordPress loads
add_action( 'plugins_loaded', 'run_sched_conference_plugin' );

// Add settings link on plugin page
add_filter( 'plugin_action_links_' . SCHED_PLUGIN_BASENAME, 'sched_add_settings_link' );

function sched_add_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'options-general.php?page=sched-conference-plugin' ) . '">' . __( 'Settings', 'wpsched' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
?>