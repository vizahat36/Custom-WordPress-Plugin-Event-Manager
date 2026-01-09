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
            add_shortcode( 'event_filters', array( $this, 'display_event_filters' ) );
        }

        /**
         * Display all events as a list.
         *
         * Shortcode: [event_list posts_per_page="10" category="event-category" orderby="date" date_from="2026-01-01" date_to="2026-12-31" location="New York" search="conference" show_filters="yes"]
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
                    'date_from'      => '',
                    'date_to'        => '',
                    'location'       => '',
                    'search'         => '',
                    'show_filters'   => 'no',
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
            $date_from      = sanitize_text_field( $atts['date_from'] );
            $date_to        = sanitize_text_field( $atts['date_to'] );
            $location       = sanitize_text_field( $atts['location'] );
            $search         = sanitize_text_field( $atts['search'] );
            $show_filters   = sanitize_text_field( $atts['show_filters'] );

            // Check for $_GET parameters (from filter form submission).
            if ( ! empty( $_GET['cem_date_from'] ) ) {
                $date_from = sanitize_text_field( wp_unslash( $_GET['cem_date_from'] ) );
            }
            if ( ! empty( $_GET['cem_date_to'] ) ) {
                $date_to = sanitize_text_field( wp_unslash( $_GET['cem_date_to'] ) );
            }
            if ( ! empty( $_GET['cem_location'] ) ) {
                $location = sanitize_text_field( wp_unslash( $_GET['cem_location'] ) );
            }
            if ( ! empty( $_GET['cem_search'] ) ) {
                $search = sanitize_text_field( wp_unslash( $_GET['cem_search'] ) );
            }
            if ( ! empty( $_GET['cem_paged'] ) ) {
                $paged = intval( $_GET['cem_paged'] );
            }

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

            // Add date range filter.
            if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
                if ( ! isset( $query_args['meta_query'] ) ) {
                    $query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
                }

                $date_query = array(
                    'key'  => '_cem_event_date',
                    'type' => 'DATE',
                );

                if ( ! empty( $date_from ) && ! empty( $date_to ) ) {
                    // Date range.
                    $date_query['value']   = array( $date_from, $date_to );
                    $date_query['compare'] = 'BETWEEN';
                } elseif ( ! empty( $date_from ) ) {
                    // From date onwards.
                    $date_query['value']   = $date_from;
                    $date_query['compare'] = '>=';
                } elseif ( ! empty( $date_to ) ) {
                    // Up to date.
                    $date_query['value']   = $date_to;
                    $date_query['compare'] = '<=';
                }

                $query_args['meta_query'][] = $date_query;
            }

            // Add location filter.
            if ( ! empty( $location ) ) {
                if ( ! isset( $query_args['meta_query'] ) ) {
                    $query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query
                }

                $query_args['meta_query'][] = array(
                    'key'     => '_cem_event_location',
                    'value'   => $location,
                    'compare' => 'LIKE',
                );
            }

            // Add keyword search.
            if ( ! empty( $search ) ) {
                $query_args['s'] = $search;
            }

            // Set meta_query relation if multiple meta queries exist.
            if ( isset( $query_args['meta_query'] ) && count( $query_args['meta_query'] ) > 1 ) {
                $query_args['meta_query']['relation'] = 'AND';
            }

            /**
             * Filter event query arguments.
             *
             * @since 1.0.0
             * @param array $query_args WP_Query arguments.
             */
            $query_args = apply_filters( 'cem_event_query_args', $query_args );
            $query_args = apply_filters( 'cem_event_list_query_args', $query_args, $atts );

            // Execute query (no direct DB access, uses WP_Query API).
            $query = new WP_Query( $query_args );

            // Start output buffering for clean HTML generation.
            ob_start();

            // Render filter form if enabled.
            if ( 'yes' === $show_filters ) {
                $this->render_filter_form( $date_from, $date_to, $location, $search );
            }

            // Handle no results case.
            if ( ! $query->have_posts() ) {
                echo '<div class="cem-no-events"><p>' . esc_html__( 'No events found.', 'custom-event-manager' ) . '</p></div>';
                return ob_get_clean();
            }

            echo '<div class="cem-events-list" data-page="' . esc_attr( $paged ) . '">';

            // Render each event item.
            while ( $query->have_posts() ) {
                $query->the_post();
                $this->render_event_item( get_the_ID() );
            }

            echo '</div>';

            // Render pagination if needed.
            if ( $query->max_num_pages > 1 ) {
                $this->render_pagination( $query, $date_from, $date_to, $location, $search );
            }

            // Reset post data.
            wp_reset_postdata();

            // Return buffered output.
            return ob_get_clean();
        }

        /**
         * Render filter form for events.
         *
         * @since 1.0.0
         * @param string $date_from Current date_from filter value.
         * @param string $date_to   Current date_to filter value.
         * @param string $location  Current location filter value.
         * @param string $search    Current search filter value.
         */
        private function render_filter_form( $date_from = '', $date_to = '', $location = '', $search = '' ) {
            echo '<div class="cem-filter-form">';
            echo '<form method="get" action="" class="cem-filters">';

            // Preserve existing query vars.
            foreach ( $_GET as $key => $value ) {
                if ( ! in_array( $key, array( 'cem_date_from', 'cem_date_to', 'cem_location', 'cem_search', 'cem_paged' ), true ) ) {
                    echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
                }
            }

            echo '<div class="cem-filter-row">';

            // Date from filter.
            echo '<div class="cem-filter-field">';
            echo '<label for="cem_date_from">' . esc_html__( 'From Date:', 'custom-event-manager' ) . '</label>';
            echo '<input type="date" id="cem_date_from" name="cem_date_from" value="' . esc_attr( $date_from ) . '" class="cem-filter-input">';
            echo '</div>';

            // Date to filter.
            echo '<div class="cem-filter-field">';
            echo '<label for="cem_date_to">' . esc_html__( 'To Date:', 'custom-event-manager' ) . '</label>';
            echo '<input type="date" id="cem_date_to" name="cem_date_to" value="' . esc_attr( $date_to ) . '" class="cem-filter-input">';
            echo '</div>';

            // Location filter.
            echo '<div class="cem-filter-field">';
            echo '<label for="cem_location">' . esc_html__( 'Location:', 'custom-event-manager' ) . '</label>';
            echo '<input type="text" id="cem_location" name="cem_location" value="' . esc_attr( $location ) . '" placeholder="' . esc_attr__( 'Enter location', 'custom-event-manager' ) . '" class="cem-filter-input">';
            echo '</div>';

            // Search filter.
            echo '<div class="cem-filter-field">';
            echo '<label for="cem_search">' . esc_html__( 'Search:', 'custom-event-manager' ) . '</label>';
            echo '<input type="text" id="cem_search" name="cem_search" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search events...', 'custom-event-manager' ) . '" class="cem-filter-input">';
            echo '</div>';

            echo '</div>';

            // Filter buttons.
            echo '<div class="cem-filter-actions">';
            echo '<button type="submit" class="cem-filter-submit">' . esc_html__( 'Apply Filters', 'custom-event-manager' ) . '</button>';
            $reset_url = strtok( $_SERVER['REQUEST_URI'], '?' );
            echo '<a href="' . esc_url( $reset_url ) . '" class="cem-filter-reset">' . esc_html__( 'Clear Filters', 'custom-event-manager' ) . '</a>';
            echo '</div>';

            echo '</form>';
            echo '</div>';

            /**
             * Hook after filter form.
             *
             * @since 1.0.0
             */
            do_action( 'cem_after_filter_form' );
        }

        /**
         * Display standalone filter form shortcode.
         *
         * Shortcode: [event_filters]
         *
         * @since 1.0.0
         * @param array $atts Shortcode attributes.
         * @return string HTML output.
         */
        public function display_event_filters( $atts ) {
            $atts = shortcode_atts(
                array(
                    'date_from' => '',
                    'date_to'   => '',
                    'location'  => '',
                    'search'    => '',
                ),
                $atts,
                'event_filters'
            );

            // Check for $_GET parameters.
            $date_from = ! empty( $_GET['cem_date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['cem_date_from'] ) ) : $atts['date_from'];
            $date_to   = ! empty( $_GET['cem_date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['cem_date_to'] ) ) : $atts['date_to'];
            $location  = ! empty( $_GET['cem_location'] ) ? sanitize_text_field( wp_unslash( $_GET['cem_location'] ) ) : $atts['location'];
            $search    = ! empty( $_GET['cem_search'] ) ? sanitize_text_field( wp_unslash( $_GET['cem_search'] ) ) : $atts['search'];

            ob_start();
            $this->render_filter_form( $date_from, $date_to, $location, $search );
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
         * @param WP_Query $query     WP_Query object.
         * @param string   $date_from Current date_from filter.
         * @param string   $date_to   Current date_to filter.
         * @param string   $location  Current location filter.
         * @param string   $search    Current search filter.
         */
        private function render_pagination( $query, $date_from = '', $date_to = '', $location = '', $search = '' ) {
            // Build query string for pagination with filters.
            $query_string = array();

            if ( ! empty( $date_from ) ) {
                $query_string['cem_date_from'] = $date_from;
            }
            if ( ! empty( $date_to ) ) {
                $query_string['cem_date_to'] = $date_to;
            }
            if ( ! empty( $location ) ) {
                $query_string['cem_location'] = $location;
            }
            if ( ! empty( $search ) ) {
                $query_string['cem_search'] = $search;
            }

            // Build base URL.
            $base = ! empty( $query_string ) ? add_query_arg( $query_string, get_permalink() ) : get_permalink();
            $base = add_query_arg( 'cem_paged', '%#%', $base );

            $current_page = max( 1, ! empty( $_GET['cem_paged'] ) ? intval( $_GET['cem_paged'] ) : get_query_var( 'paged' ) );

            echo '<div class="cem-pagination">';
            echo wp_kses_post(
                paginate_links(
                    array(
                        'base'      => $base,
                        'format'    => '?cem_paged=%#%',
                        'total'     => $query->max_num_pages,
                        'current'   => $current_page,
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
