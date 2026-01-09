<?php
/**
 * Event Metabox Handler
 *
 * @package Custom_Event_Manager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CEM_Event_Metabox' ) ) {
    /**
     * Register and handle event metabox for meta fields.
     */
    class CEM_Event_Metabox {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action( 'add_meta_boxes_event', array( $this, 'register_metabox' ) );
            add_action( 'save_post_event', array( $this, 'save_event_meta' ), 10, 1 );
        }

        /**
         * Register event metabox on edit screen.
         */
        public function register_metabox() {
            add_meta_box(
                'cem_event_details',
                'Event Details',
                array( $this, 'render_metabox' ),
                'event',
                'normal',
                'high'
            );
        }

        /**
         * Render the event details metabox HTML.
         *
         * @param WP_Post $post Current post object.
         */
        public function render_metabox( $post ) {
            // Security: Verify nonce.
            wp_nonce_field( 'cem_event_meta_nonce', 'cem_event_meta_nonce_field' );

            // Retrieve existing meta values with fallback to empty strings.
            $event_date     = get_post_meta( $post->ID, '_cem_event_date', true );
            $event_time     = get_post_meta( $post->ID, '_cem_event_time', true );
            $event_location = get_post_meta( $post->ID, '_cem_event_location', true );
            $event_capacity = get_post_meta( $post->ID, '_cem_event_capacity', true );

            ?>
            <div class="cem-metabox-wrapper">
                <div class="cem-metabox-field">
                    <label for="cem_event_date">
                        <strong><?php esc_html_e( 'Event Date', 'custom-event-manager' ); ?></strong>
                    </label>
                    <input 
                        type="date" 
                        id="cem_event_date" 
                        name="cem_event_date" 
                        value="<?php echo esc_attr( $event_date ); ?>" 
                        required 
                    />
                    <p class="description"><?php esc_html_e( 'Select the date when the event will take place.', 'custom-event-manager' ); ?></p>
                </div>

                <div class="cem-metabox-field">
                    <label for="cem_event_time">
                        <strong><?php esc_html_e( 'Event Time', 'custom-event-manager' ); ?></strong>
                    </label>
                    <input 
                        type="time" 
                        id="cem_event_time" 
                        name="cem_event_time" 
                        value="<?php echo esc_attr( $event_time ); ?>" 
                    />
                    <p class="description"><?php esc_html_e( 'Enter the start time of the event.', 'custom-event-manager' ); ?></p>
                </div>

                <div class="cem-metabox-field">
                    <label for="cem_event_location">
                        <strong><?php esc_html_e( 'Event Location', 'custom-event-manager' ); ?></strong>
                    </label>
                    <input 
                        type="text" 
                        id="cem_event_location" 
                        name="cem_event_location" 
                        value="<?php echo esc_attr( $event_location ); ?>" 
                        placeholder="<?php esc_attr_e( 'e.g., Convention Center, Hotel Ballroom', 'custom-event-manager' ); ?>" 
                        maxlength="255"
                        required 
                    />
                    <p class="description"><?php esc_html_e( 'Specify where the event will be held.', 'custom-event-manager' ); ?></p>
                </div>

                <div class="cem-metabox-field">
                    <label for="cem_event_capacity">
                        <strong><?php esc_html_e( 'Event Capacity', 'custom-event-manager' ); ?></strong>
                    </label>
                    <input 
                        type="number" 
                        id="cem_event_capacity" 
                        name="cem_event_capacity" 
                        value="<?php echo intval( $event_capacity ); ?>" 
                        placeholder="<?php esc_attr_e( '0 for unlimited', 'custom-event-manager' ); ?>"
                        min="0" 
                    />
                    <p class="description"><?php esc_html_e( 'Maximum number of attendees. Leave 0 for unlimited.', 'custom-event-manager' ); ?></p>
                </div>
            </div>
            <?php
        }

        /**
         * Save event meta fields on post save.
         *
         * @param int $post_id Post ID being saved.
         */
        public function save_event_meta( $post_id ) {
            // Security: Verify nonce existence and validity.
            if ( ! isset( $_POST['cem_event_meta_nonce_field'] ) || 
                 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cem_event_meta_nonce_field'] ) ), 'cem_event_meta_nonce' ) ) {
                return;
            }

            // Security: Skip during autosave.
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            // Security: Verify user capability to edit this post.
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
            }

            // Sanitize and save event date.
            if ( isset( $_POST['cem_event_date'] ) ) {
                $event_date = sanitize_text_field( wp_unslash( $_POST['cem_event_date'] ) );
                // Validate ISO date format (YYYY-MM-DD).
                if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $event_date ) ) {
                    update_post_meta( $post_id, '_cem_event_date', $event_date );
                }
            }

            // Sanitize and save event time.
            if ( isset( $_POST['cem_event_time'] ) ) {
                $event_time = sanitize_text_field( wp_unslash( $_POST['cem_event_time'] ) );
                // Validate time format (HH:MM).
                if ( preg_match( '/^\d{2}:\d{2}$/', $event_time ) ) {
                    update_post_meta( $post_id, '_cem_event_time', $event_time );
                }
            }

            // Sanitize and save event location.
            if ( isset( $_POST['cem_event_location'] ) ) {
                $event_location = sanitize_text_field( wp_unslash( $_POST['cem_event_location'] ) );
                update_post_meta( $post_id, '_cem_event_location', $event_location );
            }

            // Sanitize and save event capacity.
            if ( isset( $_POST['cem_event_capacity'] ) ) {
                $event_capacity = intval( sanitize_text_field( wp_unslash( $_POST['cem_event_capacity'] ) ) );
                update_post_meta( $post_id, '_cem_event_capacity', $event_capacity );
            }
        }
    }
}
