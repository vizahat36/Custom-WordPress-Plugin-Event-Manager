<?php
/**
 * REST API Handler for Events
 *
 * Exposes events via WordPress REST API for headless/API-first applications.
 * Provides GET endpoints for listing events and retrieving single event details.
 *
 * @package Custom_Event_Manager
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * CEM_Event_REST_API class
 *
 * Registers and handles REST API endpoints for events.
 *
 * Endpoints:
 * - GET /wp-json/cem/v1/events - List all events with pagination
 * - GET /wp-json/cem/v1/events/{id} - Get single event by ID
 *
 * @since 1.0.0
 */
class CEM_Event_REST_API {

    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'cem/v1';

    /**
     * Constructor.
     *
     * Hooks into rest_api_init to register routes.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     *
     * Registers two endpoints:
     * 1. GET /wp-json/cem/v1/events - List events
     * 2. GET /wp-json/cem/v1/events/{id} - Single event
     *
     * @since 1.0.0
     */
    public function register_routes() {
        // Register route for listing events.
        register_rest_route(
            $this->namespace,
            '/events',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_events' ),
                'permission_callback' => array( $this, 'get_events_permissions_check' ),
                'args'                => $this->get_collection_params(),
            )
        );

        // Register route for single event.
        register_rest_route(
            $this->namespace,
            '/events/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_event' ),
                'permission_callback' => array( $this, 'get_event_permissions_check' ),
                'args'                => array(
                    'id' => array(
                        'description'       => __( 'Unique identifier for the event.', 'custom-event-manager' ),
                        'type'              => 'integer',
                        'required'          => true,
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param ) && $param > 0;
                        },
                    ),
                ),
            )
        );
    }

    /**
     * Permission callback for listing events.
     *
     * Events are public, so always return true.
     * Can be extended for authentication if needed.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return bool True if permission granted.
     */
    public function get_events_permissions_check( $request ) {
        // Events are publicly accessible.
        // For private events, check: current_user_can( 'read' )
        return true;
    }

    /**
     * Permission callback for single event.
     *
     * Events are public, so always return true.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return bool True if permission granted.
     */
    public function get_event_permissions_check( $request ) {
        // Events are publicly accessible.
        return true;
    }

    /**
     * Get collection parameters for events endpoint.
     *
     * Defines query parameters for filtering and pagination.
     *
     * @since 1.0.0
     * @return array Query parameters schema.
     */
    private function get_collection_params() {
        return array(
            'page'     => array(
                'description'       => __( 'Current page of the collection.', 'custom-event-manager' ),
                'type'              => 'integer',
                'default'           => 1,
                'minimum'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => __( 'Maximum number of items to return.', 'custom-event-manager' ),
                'type'              => 'integer',
                'default'           => 10,
                'minimum'           => 1,
                'maximum'           => 100,
                'sanitize_callback' => 'absint',
            ),
            'orderby'  => array(
                'description'       => __( 'Sort collection by event attribute.', 'custom-event-manager' ),
                'type'              => 'string',
                'default'           => 'date',
                'enum'              => array( 'date', 'title', 'event_date' ),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'order'    => array(
                'description'       => __( 'Order sort attribute ascending or descending.', 'custom-event-manager' ),
                'type'              => 'string',
                'default'           => 'DESC',
                'enum'              => array( 'ASC', 'DESC' ),
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'category' => array(
                'description'       => __( 'Filter by event category slug.', 'custom-event-manager' ),
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    /**
     * Get events collection.
     *
     * Retrieves list of events with pagination support.
     * Uses WP_Query for safe database queries.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_events( $request ) {
        // Sanitize and retrieve parameters.
        $page     = $request->get_param( 'page' );
        $per_page = $request->get_param( 'per_page' );
        $orderby  = $request->get_param( 'orderby' );
        $order    = $request->get_param( 'order' );
        $category = $request->get_param( 'category' );

        // Build WP_Query arguments.
        $args = array(
            'post_type'      => 'event',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
            'orderby'        => 'date' === $orderby ? 'date' : ( 'title' === $orderby ? 'title' : 'meta_value' ),
            'order'          => strtoupper( $order ),
        );

        // Handle event_date ordering.
        if ( 'event_date' === $orderby ) {
            $args['meta_key'] = '_cem_event_date';
            $args['orderby']  = 'meta_value';
        }

        // Handle category filtering.
        if ( ! empty( $category ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'event_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        // Execute query.
        $query = new WP_Query( $args );

        // Build response data.
        $events = array();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $events[] = $this->prepare_event_for_response( get_post() );
            }
            wp_reset_postdata();
        }

        // Create response with pagination headers.
        $response = rest_ensure_response( $events );

        // Add pagination headers.
        $total_events = $query->found_posts;
        $total_pages  = $query->max_num_pages;

        $response->header( 'X-WP-Total', $total_events );
        $response->header( 'X-WP-TotalPages', $total_pages );

        // Add pagination links.
        $base_url = rest_url( sprintf( '%s/%s', $this->namespace, 'events' ) );
        $links    = array();

        if ( $page > 1 ) {
            $prev_page     = $page - 1;
            $prev_link     = add_query_arg( array( 'page' => $prev_page ), $base_url );
            $links['prev'] = array(
                'href' => $prev_link,
            );
        }

        if ( $page < $total_pages ) {
            $next_page     = $page + 1;
            $next_link     = add_query_arg( array( 'page' => $next_page ), $base_url );
            $links['next'] = array(
                'href' => $next_link,
            );
        }

        if ( ! empty( $links ) ) {
            $response->add_links( $links );
        }

        return $response;
    }

    /**
     * Get single event by ID.
     *
     * Retrieves detailed information for a specific event.
     *
     * @since 1.0.0
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object or error.
     */
    public function get_event( $request ) {
        // Sanitize event ID.
        $event_id = absint( $request->get_param( 'id' ) );

        // Verify event exists and is published.
        $event = get_post( $event_id );

        if ( ! $event || 'event' !== $event->post_type ) {
            return new WP_Error(
                'cem_event_not_found',
                __( 'Event not found.', 'custom-event-manager' ),
                array( 'status' => 404 )
            );
        }

        if ( 'publish' !== $event->post_status ) {
            return new WP_Error(
                'cem_event_not_published',
                __( 'Event is not published.', 'custom-event-manager' ),
                array( 'status' => 403 )
            );
        }

        // Prepare and return event data.
        $event_data = $this->prepare_event_for_response( $event );

        return rest_ensure_response( $event_data );
    }

    /**
     * Prepare event data for REST response.
     *
     * Formats event post data into structured JSON response.
     * Includes event meta fields and RSVP count.
     *
     * @since 1.0.0
     * @param WP_Post $event Event post object.
     * @return array Formatted event data.
     */
    private function prepare_event_for_response( $event ) {
        // Get event meta data with escaping.
        $event_date     = get_post_meta( $event->ID, '_cem_event_date', true );
        $event_time     = get_post_meta( $event->ID, '_cem_event_time', true );
        $event_location = get_post_meta( $event->ID, '_cem_event_location', true );
        $event_capacity = get_post_meta( $event->ID, '_cem_event_capacity', true );

        // Get RSVP count.
        $rsvp_count = $this->get_event_rsvp_count( $event->ID );

        // Get event categories.
        $categories = get_the_terms( $event->ID, 'event_category' );
        $category_data = array();
        if ( $categories && ! is_wp_error( $categories ) ) {
            foreach ( $categories as $category ) {
                $category_data[] = array(
                    'id'   => $category->term_id,
                    'name' => esc_html( $category->name ),
                    'slug' => esc_attr( $category->slug ),
                );
            }
        }

        // Format event data.
        $event_data = array(
            'id'            => $event->ID,
            'title'         => array(
                'rendered' => esc_html( get_the_title( $event->ID ) ),
                'raw'      => $event->post_title,
            ),
            'content'       => array(
                'rendered' => apply_filters( 'the_content', $event->post_content ),
                'raw'      => $event->post_content,
            ),
            'excerpt'       => array(
                'rendered' => esc_html( get_the_excerpt( $event->ID ) ),
                'raw'      => $event->post_excerpt,
            ),
            'date'          => esc_html( $event_date ),
            'time'          => esc_html( $event_time ),
            'location'      => esc_html( $event_location ),
            'capacity'      => absint( $event_capacity ),
            'rsvp_count'    => $rsvp_count,
            'rsvp_available' => $this->get_available_rsvp_slots( $event->ID ),
            'categories'    => $category_data,
            'link'          => esc_url( get_permalink( $event->ID ) ),
            'featured_image' => $this->get_featured_image_data( $event->ID ),
            'author'        => array(
                'id'   => $event->post_author,
                'name' => esc_html( get_the_author_meta( 'display_name', $event->post_author ) ),
            ),
            'published_date' => esc_html( $event->post_date ),
            'modified_date'  => esc_html( $event->post_modified ),
        );

        /**
         * Filter event REST response data.
         *
         * Allows developers to modify the event data returned by the REST API.
         *
         * @since 1.0.0
         * @param array   $event_data Formatted event data.
         * @param WP_Post $event      Event post object.
         */
        return apply_filters( 'cem_rest_prepare_event', $event_data, $event );
    }

    /**
     * Get RSVP count for an event.
     *
     * Counts the number of RSVPs submitted for a specific event.
     *
     * @since 1.0.0
     * @param int $event_id Event post ID.
     * @return int Number of RSVPs.
     */
    private function get_event_rsvp_count( $event_id ) {
        $args = array(
            'post_type'      => 'event_rsvp',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_cem_rsvp_event_id',
                    'value'   => absint( $event_id ),
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
            'fields'         => 'ids', // Only retrieve IDs for performance.
        );

        $query = new WP_Query( $args );
        return $query->found_posts;
    }

    /**
     * Get available RSVP slots for an event.
     *
     * Calculates remaining capacity based on event capacity and current RSVPs.
     *
     * @since 1.0.0
     * @param int $event_id Event post ID.
     * @return int|string Available slots or 'unlimited'.
     */
    private function get_available_rsvp_slots( $event_id ) {
        $capacity   = get_post_meta( $event_id, '_cem_event_capacity', true );
        $rsvp_count = $this->get_event_rsvp_count( $event_id );

        // 0 or empty capacity means unlimited.
        if ( empty( $capacity ) || 0 === absint( $capacity ) ) {
            return 'unlimited';
        }

        $available = absint( $capacity ) - $rsvp_count;
        return max( 0, $available );
    }

    /**
     * Get featured image data for an event.
     *
     * Returns featured image URLs in multiple sizes.
     *
     * @since 1.0.0
     * @param int $event_id Event post ID.
     * @return array|null Featured image data or null if not set.
     */
    private function get_featured_image_data( $event_id ) {
        $thumbnail_id = get_post_thumbnail_id( $event_id );

        if ( ! $thumbnail_id ) {
            return null;
        }

        $image_sizes = array( 'thumbnail', 'medium', 'large', 'full' );
        $image_data  = array(
            'id'  => $thumbnail_id,
            'alt' => esc_attr( get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ) ),
        );

        foreach ( $image_sizes as $size ) {
            $image_url = wp_get_attachment_image_src( $thumbnail_id, $size );
            if ( $image_url ) {
                $image_data[ $size ] = array(
                    'url'    => esc_url( $image_url[0] ),
                    'width'  => $image_url[1],
                    'height' => $image_url[2],
                );
            }
        }

        return $image_data;
    }
}
