<?php
/**
 * Event RSVP Handler
 *
 * @package Custom_Event_Manager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CEM_Event_RSVP' ) ) {
    /**
     * Handle event RSVP functionality.
     */
    class CEM_Event_RSVP {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action( 'wp_ajax_cem_rsvp_submit', array( $this, 'handle_rsvp_submission' ) );
            add_action( 'wp_ajax_nopriv_cem_rsvp_submit', array( $this, 'handle_rsvp_submission' ) );
            add_action( 'init', array( $this, 'create_rsvp_post_type' ) );
        }

        /**
         * Register custom post type for RSVP records.
         */
        public function create_rsvp_post_type() {
            // Check if RSVP functionality is enabled.
            if ( ! get_option( 'cem_enable_rsvp' ) ) {
                return;
            }

            $labels = array(
                'name'          => 'Event RSVPs',
                'singular_name' => 'Event RSVP',
                'menu_name'     => 'Event RSVPs',
            );

            $args = array(
                'labels'              => $labels,
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => 'edit.php?post_type=event',
                'supports'            => array( 'title', 'custom-fields' ),
                'capability_type'     => 'post',
                'hierarchical'        => false,
                'menu_position'       => 21,
                'menu_icon'           => 'dashicons-groups',
                'show_in_rest'        => true,
            );

            register_post_type( 'event_rsvp', $args );
        }

        /**
         * Handle RSVP form submission via AJAX.
         */
        public function handle_rsvp_submission() {
            // Verify nonce.
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cem_rsvp_nonce' ) ) {
                wp_send_json_error( array( 'message' => esc_html__( 'Security verification failed.', 'custom-event-manager' ) ) );
            }

            // Check if RSVP is enabled.
            if ( ! get_option( 'cem_enable_rsvp' ) ) {
                wp_send_json_error( array( 'message' => esc_html__( 'RSVP functionality is disabled.', 'custom-event-manager' ) ) );
            }

            // Retrieve and validate input.
            $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
            $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
            $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

            // Validate required fields.
            if ( ! $event_id || ! $name || ! $email ) {
                wp_send_json_error( array( 'message' => esc_html__( 'All fields are required.', 'custom-event-manager' ) ) );
            }

            // Validate event exists and is published.
            $event_post = get_post( $event_id );
            if ( ! $event_post || 'event' !== $event_post->post_type || 'publish' !== $event_post->post_status ) {
                wp_send_json_error( array( 'message' => esc_html__( 'Invalid event.', 'custom-event-manager' ) ) );
            }

            // Validate email format.
            if ( ! is_email( $email ) ) {
                wp_send_json_error( array( 'message' => esc_html__( 'Please enter a valid email address.', 'custom-event-manager' ) ) );
            }

            // Check for duplicate RSVP.
            if ( $this->has_existing_rsvp( $event_id, $email ) ) {
                wp_send_json_error( array( 'message' => esc_html__( 'You have already RSVP\'d to this event.', 'custom-event-manager' ) ) );
            }

            // Check event capacity.
            $capacity = intval( get_post_meta( $event_id, '_cem_event_capacity', true ) );
            if ( $capacity > 0 ) {
                $rsvp_count = $this->get_event_rsvp_count( $event_id );
                if ( $rsvp_count >= $capacity ) {
                    wp_send_json_error( array( 'message' => esc_html__( 'This event has reached maximum capacity.', 'custom-event-manager' ) ) );
                }
            }

            // Create RSVP post.
            $rsvp_id = $this->create_rsvp_record( $event_id, $name, $email );

            if ( $rsvp_id ) {
                wp_send_json_success( array(
                    'message' => esc_html__( 'Thank you for your RSVP!', 'custom-event-manager' ),
                    'rsvp_id' => $rsvp_id,
                ) );
            } else {
                wp_send_json_error( array( 'message' => esc_html__( 'Failed to save RSVP. Please try again.', 'custom-event-manager' ) ) );
            }
        }

        /**
         * Check if email has already RSVP'd to an event.
         *
         * @param int    $event_id Event post ID.
         * @param string $email Email address.
         * @return bool True if RSVP exists, false otherwise.
         */
        private function has_existing_rsvp( $event_id, $email ) {
            $args = array(
                'post_type'      => 'event_rsvp',
                'posts_per_page' => 1,
                'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
                    array(
                        'key'   => '_cem_rsvp_event_id',
                        'value' => $event_id,
                    ),
                    array(
                        'key'   => '_cem_rsvp_email',
                        'value' => $email,
                    ),
                ),
            );

            $query = new WP_Query( $args );
            return $query->have_posts();
        }

        /**
         * Get count of RSVPs for an event.
         *
         * @param int $event_id Event post ID.
         * @return int RSVP count.
         */
        private function get_event_rsvp_count( $event_id ) {
            $args = array(
                'post_type'      => 'event_rsvp',
                'posts_per_page' => -1,
                'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
                    array(
                        'key'   => '_cem_rsvp_event_id',
                        'value' => $event_id,
                    ),
                ),
            );

            $query = new WP_Query( $args );
            return $query->found_posts;
        }

        /**
         * Create an RSVP record as a custom post.
         *
         * @param int    $event_id Event post ID.
         * @param string $name Attendee name.
         * @param string $email Attendee email.
         * @return int|bool Post ID on success, false on failure.
         */
        private function create_rsvp_record( $event_id, $name, $email ) {
            $event_title = get_the_title( $event_id );

            $post_data = array(
                'post_type'    => 'event_rsvp',
                'post_title'   => sprintf( '%s - %s', $event_title, $name ),
                'post_status'  => 'publish',
                'post_parent'  => $event_id,
            );

            $rsvp_id = wp_insert_post( $post_data );

            if ( $rsvp_id ) {
                // Store RSVP metadata.
                update_post_meta( $rsvp_id, '_cem_rsvp_event_id', $event_id );
                update_post_meta( $rsvp_id, '_cem_rsvp_name', $name );
                update_post_meta( $rsvp_id, '_cem_rsvp_email', $email );
                update_post_meta( $rsvp_id, '_cem_rsvp_date', current_time( 'mysql' ) );

                do_action( 'cem_rsvp_created', $rsvp_id, $event_id, $name, $email );
            }

            return $rsvp_id;
        }

        /**
         * Get RSVP list for an event.
         *
         * @param int $event_id Event post ID.
         * @return array Array of RSVP records.
         */
        public function get_event_rsvps( $event_id ) {
            $args = array(
                'post_type'      => 'event_rsvp',
                'posts_per_page' => -1,
                'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
                    array(
                        'key'   => '_cem_rsvp_event_id',
                        'value' => $event_id,
                    ),
                ),
            );

            $query = new WP_Query( $args );
            $rsvps = array();

            while ( $query->have_posts() ) {
                $query->the_post();
                $rsvps[] = array(
                    'id'    => get_the_ID(),
                    'name'  => get_post_meta( get_the_ID(), '_cem_rsvp_name', true ),
                    'email' => get_post_meta( get_the_ID(), '_cem_rsvp_email', true ),
                    'date'  => get_post_meta( get_the_ID(), '_cem_rsvp_date', true ),
                );
            }

            wp_reset_postdata();
            return $rsvps;
        }
    }
}
