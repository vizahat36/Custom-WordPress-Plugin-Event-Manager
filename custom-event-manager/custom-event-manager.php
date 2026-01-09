<?php
/**
 * Plugin Name: Custom Event Manager
 * Description: Manage events with custom post types, admin settings, and shortcodes.
 * Version: 1.0.0
 * Author: vizahat36
 * Text Domain: custom-event-manager
 * Domain Path: /languages
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants for modular architecture.
define( 'CEM_VERSION', '1.0.0' );
define( 'CEM_PLUGIN_FILE', __FILE__ );
define( 'CEM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CEM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load required classes and functions.
require_once CEM_PLUGIN_DIR . 'includes/class-cem.php';
require_once CEM_PLUGIN_DIR . 'includes/class-event-post-type.php';
require_once CEM_PLUGIN_DIR . 'includes/class-event-metabox.php';
require_once CEM_PLUGIN_DIR . 'includes/class-event-shortcode.php';
require_once CEM_PLUGIN_DIR . 'includes/class-event-settings.php';
require_once CEM_PLUGIN_DIR . 'includes/helpers.php';
require_once CEM_PLUGIN_DIR . 'admin/admin-settings-page.php';

// Initialize plugin.
add_action( 'plugins_loaded', array( 'CEM_Plugin', 'init' ) );

if ( ! class_exists( 'CEM_Plugin' ) ) {
    /**
     * Main plugin class.
     */
    class CEM_Plugin {
        /**
         * Initialize plugin components.
         */
        public static function init() {
            new CEM_Event_Post_Type();
            new CEM_Event_Metabox();
            new CEM_Event_Shortcode();
            new CEM_Event_Settings();

            add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_frontend_assets' ) );
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
        }

        /**
         * Enqueue frontend assets.
         */
        public static function enqueue_frontend_assets() {
            wp_enqueue_style( 'cem-style', CEM_PLUGIN_URL . 'assets/css/event-style.css', array(), CEM_VERSION );
            wp_enqueue_script( 'cem-script', CEM_PLUGIN_URL . 'assets/js/event-script.js', array( 'jquery' ), CEM_VERSION, true );
        }

        /**
         * Enqueue admin assets.
         */
        public static function enqueue_admin_assets() {
            // Load admin styles and scripts on event edit pages.
            $current_screen = get_current_screen();
            if ( 'event' === $current_screen->post_type ) {
                wp_enqueue_style( 'cem-admin-style', CEM_PLUGIN_URL . 'assets/css/event-style.css', array(), CEM_VERSION );
            }
        }
    }
}
