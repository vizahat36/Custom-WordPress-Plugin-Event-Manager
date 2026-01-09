<?php
/**
 * REST API Extensions for Advanced Filtering
 *
 * Extends REST API with date range, location, and search filtering.
 *
 * @package Custom_Event_Manager
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add advanced filtering to REST API.
 *
 * Hooks into cem_event_rest_collection_params and cem_event_rest_query_args
 * to extend the REST API with additional filtering capabilities.
 */
class CEM_REST_API_Filters {

    /**
     * Constructor.
     */
    public function __construct() {
        add_filter( 'rest_event_query', array( $this, 'filter_events_query' ), 10, 2 );
    }

    /**
     * Filter events REST API query with advanced parameters.
     *
     * @param array           $args    WP_Query arguments.
     * @param WP_REST_Request $request REST request object.
     * @return array Modified query arguments.
     */
    public function filter_events_query( $args, $request ) {
        $date_from = $request->get_param( 'date_from' );
        $date_to   = $request->get_param( 'date_to' );
        $location  = $request->get_param( 'location' );
        $search    = $request->get_param( 'search' );

        // Add date range filter.
        if ( ! empty( $date_from ) || ! empty( $date_to ) ) {
            if ( ! isset( $args['meta_query'] ) ) {
                $args['meta_query'] = array();
            }

            $date_query = array(
                'key'  => '_cem_event_date',
                'type' => 'DATE',
            );

            if ( ! empty( $date_from ) && ! empty( $date_to ) ) {
                $date_query['value']   = array( $date_from, $date_to );
                $date_query['compare'] = 'BETWEEN';
            } elseif ( ! empty( $date_from ) ) {
                $date_query['value']   = $date_from;
                $date_query['compare'] = '>=';
            } elseif ( ! empty( $date_to ) ) {
                $date_query['value']   = $date_to;
                $date_query['compare'] = '<=';
            }

            $args['meta_query'][] = $date_query;
        }

        // Add location filter.
        if ( ! empty( $location ) ) {
            if ( ! isset( $args['meta_query'] ) ) {
                $args['meta_query'] = array();
            }

            $args['meta_query'][] = array(
                'key'     => '_cem_event_location',
                'value'   => $location,
                'compare' => 'LIKE',
            );
        }

        // Add search filter.
        if ( ! empty( $search ) ) {
            $args['s'] = $search;
        }

        // Set meta_query relation.
        if ( isset( $args['meta_query'] ) && count( $args['meta_query'] ) > 1 ) {
            $args['meta_query']['relation'] = 'AND';
        }

        return $args;
    }
}

// Initialize filters.
new CEM_REST_API_Filters();
