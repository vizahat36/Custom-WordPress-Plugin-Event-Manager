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
            // Register settings with sanitization callbacks.
            register_setting(
                'cem_settings_group',
                'cem_events_per_page',
                array(
                    'sanitize_callback' => array( $this, 'sanitize_events_per_page' ),
                    'type'              => 'integer',
                    'default'           => 10,
                )
            );

            register_setting(
                'cem_settings_group',
                'cem_default_currency',
                array(
                    'sanitize_callback' => 'sanitize_text_field',
                    'type'              => 'string',
                    'default'           => 'USD',
                )
            );

            register_setting(
                'cem_settings_group',
                'cem_enable_rsvp',
                array(
                    'sanitize_callback' => array( $this, 'sanitize_checkbox' ),
                    'type'              => 'boolean',
                    'default'           => 0,
                )
            );

            // Add settings section.
            add_settings_section(
                'cem_main_section',
                'Event Manager Settings',
                array( $this, 'section_callback' ),
                'cem_settings'
            );

            // Register settings fields.
            add_settings_field(
                'cem_events_per_page',
                'Events Per Page',
                array( $this, 'field_events_per_page' ),
                'cem_settings',
                'cem_main_section'
            );

            add_settings_field(
                'cem_default_currency',
                'Default Currency',
                array( $this, 'field_default_currency' ),
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
         * Sanitize events per page value.
         *
         * @param mixed $value Input value.
         * @return int Sanitized integer value.
         */
        public function sanitize_events_per_page( $value ) {
            $value = intval( $value );
            return $value > 0 ? $value : 10;
        }

        /**
         * Sanitize checkbox value.
         *
         * @param mixed $value Input value.
         * @return int 0 or 1.
         */
        public function sanitize_checkbox( $value ) {
            return ( 1 === intval( $value ) ) ? 1 : 0;
        }

        /**
         * Section callback.
         */
        public function section_callback() {
            echo 'Configure general Event Manager settings.';
        }

        /**
         * Events per page field callback.
         */
        public function field_events_per_page() {
            $value = get_option( 'cem_events_per_page', 10 );
            ?>
            <input 
                type="number" 
                id="cem_events_per_page" 
                name="cem_events_per_page" 
                value="<?php echo intval( $value ); ?>" 
                min="1" 
                max="100" 
            />
            <p class="description"><?php esc_html_e( 'Number of events to display per page (1-100).', 'custom-event-manager' ); ?></p>
            <?php
        }

        /**
         * Default currency field callback.
         */
        public function field_default_currency() {
            $value = get_option( 'cem_default_currency', 'USD' );
            $currencies = array( 'USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD' );
            ?>
            <select id="cem_default_currency" name="cem_default_currency">
                <?php
                foreach ( $currencies as $currency ) {
                    $selected = selected( $value, $currency, false );
                    echo '<option value="' . esc_attr( $currency ) . '" ' . $selected . '>' . esc_html( $currency ) . '</option>';
                }
                ?>
            </select>
            <p class="description"><?php esc_html_e( 'Default currency for event pricing.', 'custom-event-manager' ); ?></p>
            <?php
        }

        /**
         * Enable RSVP field callback.
         */
        public function field_enable_rsvp() {
            $value = get_option( 'cem_enable_rsvp', 0 );
            $checked = checked( $value, 1, false );
            ?>
            <input 
                type="checkbox" 
                id="cem_enable_rsvp" 
                name="cem_enable_rsvp" 
                value="1" 
                <?php echo $checked; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> 
            />
            <label for="cem_enable_rsvp"><?php esc_html_e( 'Enable RSVP functionality for events', 'custom-event-manager' ); ?></label>
            <p class="description"><?php esc_html_e( 'Allow visitors to RSVP to events.', 'custom-event-manager' ); ?></p>
            <?php
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
