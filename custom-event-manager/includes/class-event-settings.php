<?php
/**
 * Event Settings Page
 *
 * @package Custom_Event_Manager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CEM_Event_Settings' ) ) {
    /**
     * Register and manage plugin settings.
     */
    class CEM_Event_Settings {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
            add_action( 'admin_init', array( $this, 'register_settings' ) );
        }

        /**
         * Add plugin settings page to admin menu.
         */
        public function add_settings_page() {
            add_submenu_page(
                'edit.php?post_type=event',
                'Event Manager Settings',
                'Settings',
                'manage_options',
                'cem-settings',
                array( $this, 'render_settings_page' )
            );
        }

        /**
         * Register plugin settings and fields.
         */
        public function register_settings() {
            register_setting( 'cem_settings_group', 'cem_events_per_page' );
            register_setting( 'cem_settings_group', 'cem_enable_rsvp' );

            add_settings_section(
                'cem_main_section',
                'Event Manager Settings',
                array( $this, 'section_callback' ),
                'cem_settings'
            );

            add_settings_field(
                'cem_events_per_page',
                'Events Per Page',
                array( $this, 'field_events_per_page' ),
                'cem_settings',
                'cem_main_section'
            );

            add_settings_field(
                'cem_enable_rsvp',
                'Enable RSVP',
                array( $this, 'field_enable_rsvp' ),
                'cem_settings',
                'cem_main_section'
            );
        }

        /**
         * Section callback.
         */
        public function section_callback() {
            echo 'Configure general Event Manager settings.';
        }

        /**
         * Events per page field.
         */
        public function field_events_per_page() {
            $value = get_option( 'cem_events_per_page', 10 );
            echo '<input type="number" name="cem_events_per_page" value="' . intval( $value ) . '" />';
        }

        /**
         * Enable RSVP field.
         */
        public function field_enable_rsvp() {
            $value = get_option( 'cem_enable_rsvp', 0 );
            $checked = checked( $value, 1, false );
            echo '<input type="checkbox" name="cem_enable_rsvp" value="1" ' . $checked . ' />';
        }

        /**
         * Render the settings page.
         */
        public function render_settings_page() {
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( 'Unauthorized.' );
            }

            ?>
            <div class="wrap">
                <h1><?php esc_html_e( 'Event Manager Settings', 'custom-event-manager' ); ?></h1>
                <form method="post" action="options.php">
                    <?php
                    settings_fields( 'cem_settings_group' );
                    do_settings_sections( 'cem_settings' );
                    submit_button();
                    ?>
                </form>
            </div>
            <?php
        }
    }
}
