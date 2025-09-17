<?php

/**
 * The main plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 */
class Sched {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = '1.0.0';
        $this->plugin_name = 'sched-conference-plugin';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        $this->loader = new Sched_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     */
    private function set_locale() {
        $this->loader->add_action('plugins_loaded', $this, 'load_plugin_textdomain');
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'sched-conference-plugin',
            false,
            dirname(plugin_basename(__FILE__)) . '/../languages/'
        );
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Sched_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        $this->loader->add_action('wp_ajax_sched_sync_data', $plugin_admin, 'ajax_sync_data');
        $this->loader->add_action('wp_ajax_sched_test_connection', $plugin_admin, 'ajax_test_connection');
        $this->loader->add_action('wp_ajax_sched_debug_connection', $plugin_admin, 'ajax_debug_connection');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $plugin_public = new Sched_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Schedule automatic sync
        $this->loader->add_action('sched_sync_cron', $plugin_public, 'auto_sync_data');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}