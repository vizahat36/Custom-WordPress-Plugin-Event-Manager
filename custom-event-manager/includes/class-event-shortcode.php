<?php
/**
 * Event Shortcode Handler
 *
 * @package Custom_Event_Manager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CEM_Event_Shortcode' ) ) {
    /**
     * Register and render event shortcodes.
     */
    class CEM_Event_Shortcode {

        /**
         * Constructor.
         */
        public function __construct() {
            add_shortcode( 'event_list', array( $this, 'display_events_list' ) );
            add_shortcode( 'cem_events', array( $this, 'display_events_list' ) ); // Backward compatibility.
            add_shortcode( 'event_single', array( $this, 'display_single_event' ) );
            add_shortcode( 'cem_event_single', array( $this, 'display_single_event' ) ); // Backward compatibility.
        }

        /**
         * Display all events as a list.
         *
         * Shortcode: [event_list posts_per_page="10" category="event-category" orderby="date"]
         *
         * @param array  $atts    Shortcode attributes.
         * @param string $content Shortcode content (unused).
         * @return string HTML output.
         */
        public function display_events_list( $atts, $content = '' ) {
            // Parse shortcode attributes with defaults.
            $atts = shortcode_atts(
                array(
                    'posts_per_page' => intval( get_option( 'cem_events_per_page', 10 ) ),
                    'orderby'        => 'meta_value',
                    'meta_key'       => '_cem_event_date',
                    'order'          => 'ASC',
                    'category'       => '',
                    'paged'          => max( 1, get_query_var( 'paged' ) ),
                ),
                $atts,
                'event_list'
            );

            // Sanitize attributes.
            $posts_per_page = intval( $atts['posts_per_page'] );
            $orderby        = sanitize_text_field( $atts['orderby'] );
            $meta_key       = sanitize_text_field( $atts['meta_key'] );
            $order          = strtoupper( sanitize_text_field( $atts['order'] ) );
            $paged          = intval( $atts['paged'] );

            // Validate order direction.
            $order = in_array( $order, array( 'ASC', 'DESC' ), true ) ? $order : 'ASC';

            // Build WP_Query arguments.
            $query_args = array(
                'post_type'      => 'event',
                'posts_per_page' => $posts_per_page,
                'orderby'        => $orderby,
                'meta_key'       => $meta_key,
                'order'          => $order,
                'paged'          => $paged,
            );

            // Add category filter if provided.
            if ( ! empty( $atts['category'] ) ) {
                $query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
                    array(
                        'taxonomy' => 'event_category',
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field( $atts['category'] ),
                    ),
                );
            }

            // Apply filter for extensibility.
            $query_args = apply_filters( 'cem_event_query_args', $query_args );

            // Execute query (no direct DB access, uses WP_Query API).
            $query = new WP_Query( $query_args );

            // Handle no results case.
            if ( ! $query->have_posts() ) {
                return '<div class="cem-no-events"><p>' . esc_html__( 'No events found.', 'custom-event-manager' ) . '</p></div>';
            }

            // Start output buffering for clean HTML generation.
            ob_start();

            echo '<div class="cem-events-list" data-page="' . esc_attr( $paged ) . '">';

            // Render each event item.
            while ( $query->have_posts() ) {
                $query->the_post();
                $this->render_event_item( get_the_ID() );
            }

            echo '</div>';

            // Render pagination if needed.
            if ( $query->max_num_pages > 1 ) {
                $this->render_pagination( $query );
            }

            // Reset post data.
            wp_reset_postdata();

            // Return buffered output.
            return ob_get_clean();
        }

        /**
         * Render a single event item HTML.
         *
         * @param int $post_id Event post ID.
         */
        private function render_event_item( $post_id ) {
            $event_date     = get_post_meta( $post_id, '_cem_event_date', true );
            $event_time     = get_post_meta( $post_id, '_cem_event_time', true );
            $event_location = get_post_meta( $post_id, '_cem_event_location', true );
            $event_capacity = get_post_meta( $post_id, '_cem_event_capacity', true );

            echo '<div class="cem-event-item" data-event-id="' . esc_attr( $post_id ) . '">';

            // Render event title.
            echo '<h3 class="cem-event-title">' . esc_html( get_the_title() ) . '</h3>';

            // Render event metadata.
            echo '<div class="cem-event-meta">';
            if ( ! empty( $event_date ) ) {
                echo '<p class="cem-event-date"><strong>' . esc_html__( 'Date:', 'custom-event-manager' ) . '</strong> ' . esc_html( wp_date( 'F j, Y', strtotime( $event_date ) ) ) . '</p>';
            }
            if ( ! empty( $event_time ) ) {
                echo '<p class="cem-event-time"><strong>' . esc_html__( 'Time:', 'custom-event-manager' ) . '</strong> ' . esc_html( $event_time ) . '</p>';
            }
            if ( ! empty( $event_location ) ) {
                echo '<p class="cem-event-location"><strong>' . esc_html__( 'Location:', 'custom-event-manager' ) . '</strong> ' . esc_html( $event_location ) . '</p>';
            }
            if ( ! empty( $event_capacity ) && intval( $event_capacity ) > 0 ) {
                echo '<p class="cem-event-capacity"><strong>' . esc_html__( 'Capacity:', 'custom-event-manager' ) . '</strong> ' . esc_html( $event_capacity ) . ' ' . esc_html__( 'attendees', 'custom-event-manager' ) . '</p>';
            }
            echo '</div>';

            // Render event excerpt.
            if ( has_excerpt() ) {
                echo '<div class="cem-event-excerpt">' . wp_kses_post( wpautop( get_the_excerpt() ) ) . '</div>';
            }

            // Render read more link with action hook.
            do_action( 'cem_before_read_more_link', $post_id );
            echo '<a href="' . esc_url( get_permalink() ) . '" class="cem-read-more">' . esc_html__( 'View Details', 'custom-event-manager' ) . '</a>';
            do_action( 'cem_after_read_more_link', $post_id );

            echo '</div>';
        }

        /**
         * Render pagination links.
         *
         * @param WP_Query $query WP_Query object.
         */
        private function render_pagination( $query ) {
            echo '<div class="cem-pagination">';
            echo wp_kses_post(
                paginate_links(
                    array(
                        'base'      => add_query_arg( 'paged', '%#%' ),
                        'format'    => '?paged=%#%',
                        'total'     => $query->max_num_pages,
                        'current'   => max( 1, get_query_var( 'paged' ) ),
                        'prev_text' => esc_html__( '← Previous', 'custom-event-manager' ),
                        'next_text' => esc_html__( 'Next →', 'custom-event-manager' ),
                        'type'      => 'list',
                    )
                )
            );
            echo '</div>';
        }

        /**
         * Display a single event by ID.
         *
         * Shortcode: [event_single id="123"]
         *
         * @param array $atts Shortcode attributes.
         * @return string HTML output.
         */
        public function display_single_event( $atts ) {
            // Parse shortcode attributes.
            $atts = shortcode_atts(
                array(
                    'id' => '',
                ),
                $atts,
                'event_single'
            );

            // Validate event ID.
            if ( empty( $atts['id'] ) ) {
                return '<div class="cem-error"><p>' . esc_html__( 'Event ID is required.', 'custom-event-manager' ) . '</p></div>';
            }

            $post_id = intval( $atts['id'] );
            $post    = get_post( $post_id );

            // Verify post exists and is an event.
            if ( ! $post || 'event' !== $post->post_type ) {
                return '<div class="cem-error"><p>' . esc_html__( 'Event not found.', 'custom-event-manager' ) . '</p></div>';
            }

            // Verify post is published.
            if ( 'publish' !== $post->post_status && ! current_user_can( 'read_post', $post_id ) ) {
                return '<div class="cem-error"><p>' . esc_html__( 'You do not have permission to view this event.', 'custom-event-manager' ) . '</p></div>';
            }

            // Start output buffering.
            ob_start();

            echo '<div class="cem-event-single" data-event-id="' . esc_attr( $post_id ) . '">';

            // Event title.
            echo '<h2 class="cem-event-title">' . esc_html( $post->post_title ) . '</h2>';

            // Event metadata.
            $event_date     = get_post_meta( $post_id, '_cem_event_date', true );
            $event_time     = get_post_meta( $post_id, '_cem_event_time', true );
            $event_location = get_post_meta( $post_id, '_cem_event_location', true );

            if ( ! empty( $event_date ) || ! empty( $event_time ) || ! empty( $event_location ) ) {
                echo '<div class="cem-event-meta">';
                if ( ! empty( $event_date ) ) {
                    echo '<p><strong>' . esc_html__( 'Date:', 'custom-event-manager' ) . '</strong> ' . esc_html( wp_date( 'F j, Y', strtotime( $event_date ) ) ) . '</p>';
                }
                if ( ! empty( $event_time ) ) {
                    echo '<p><strong>' . esc_html__( 'Time:', 'custom-event-manager' ) . '</strong> ' . esc_html( $event_time ) . '</p>';
                }
                if ( ! empty( $event_location ) ) {
                    echo '<p><strong>' . esc_html__( 'Location:', 'custom-event-manager' ) . '</strong> ' . esc_html( $event_location ) . '</p>';
                }
                echo '</div>';
            }

            // Event content.
            echo '<div class="cem-event-content">' . wp_kses_post( wpautop( $post->post_content ) ) . '</div>';

            // Render RSVP form if enabled.
            if ( get_option( 'cem_enable_rsvp' ) ) {
                $this->render_rsvp_form( $post_id );
            }

            // Action hook for extending single event display.
            do_action( 'cem_single_event_footer', $post_id );

            echo '</div>';

            return ob_get_clean();
        }

        /**
         * Render RSVP form for event.
         *
         * @param int $post_id Event post ID.
         */
        private function render_rsvp_form( $post_id ) {
            // Create nonce for AJAX.
            $nonce = wp_create_nonce( 'cem_rsvp_nonce' );

            echo '<div class="cem-rsvp-section">';
            echo '<h3>' . esc_html__( 'RSVP to this Event', 'custom-event-manager' ) . '</h3>';

            // Get event capacity and RSVP count.
            $capacity = intval( get_post_meta( $post_id, '_cem_event_capacity', true ) );
            if ( $capacity > 0 ) {
                // Count RSVPs using helper.
                $rsvp_handler = new CEM_Event_RSVP();
                $rsvp_count = $rsvp_handler->get_event_rsvp_count( $post_id );
                $remaining = $capacity - $rsvp_count;

                if ( $remaining > 0 ) {
                    echo '<p class="cem-capacity-info">' . sprintf(
                        esc_html__( '%d spot(s) remaining', 'custom-event-manager' ),
                        $remaining
                    ) . '</p>';
                } else {
                    echo '<p class="cem-capacity-full">' . esc_html__( 'This event has reached maximum capacity.', 'custom-event-manager' ) . '</p>';
                }
            }

            echo '<form id="cem-rsvp-form" class="cem-rsvp-form" data-event-id="' . esc_attr( $post_id ) . '" data-nonce="' . esc_attr( $nonce ) . '">';
            echo '<div class="cem-form-group">';
            echo '<label for="cem_rsvp_name">' . esc_html__( 'Full Name', 'custom-event-manager' ) . ' <span class="required">*</span></label>';
            echo '<input type="text" id="cem_rsvp_name" name="cem_rsvp_name" required placeholder="' . esc_attr__( 'Your name', 'custom-event-manager' ) . '" />';
            echo '</div>';

            echo '<div class="cem-form-group">';
            echo '<label for="cem_rsvp_email">' . esc_html__( 'Email Address', 'custom-event-manager' ) . ' <span class="required">*</span></label>';
            echo '<input type="email" id="cem_rsvp_email" name="cem_rsvp_email" required placeholder="' . esc_attr__( 'your@email.com', 'custom-event-manager' ) . '" />';
            echo '</div>';

            echo '<div class="cem-form-group cem-button-group">';
            echo '<button type="submit" class="cem-rsvp-button cem-button-primary">' . esc_html__( 'RSVP Now', 'custom-event-manager' ) . '</button>';
            echo '<span class="cem-rsvp-loading" style="display:none;">' . esc_html__( 'Processing...', 'custom-event-manager' ) . '</span>';
            echo '</div>';

            echo '<div class="cem-rsvp-message" style="display:none;"></div>';
            echo '</form>';
            echo '</div>';
        }
    }
}
