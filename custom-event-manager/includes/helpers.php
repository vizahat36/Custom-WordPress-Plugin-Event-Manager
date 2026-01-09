<?php
/**
 * Helper Functions
 *
 * @package Custom_Event_Manager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get all events.
 *
 * @param array $args Query arguments.
 * @return array Array of event posts.
 */
function cem_get_events( $args = array() ) {
    $defaults = array(
        'post_type'      => 'event',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $args = wp_parse_args( $args, $defaults );
    $query = new WP_Query( $args );

    return $query->posts;
}

/**
 * Get event meta.
 *
 * @param int    $event_id Event post ID.
 * @param string $meta_key Meta key.
 * @param mixed  $default Default value.
 * @return mixed Meta value or default.
 */
function cem_get_event_meta( $event_id, $meta_key, $default = '' ) {
    $value = get_post_meta( $event_id, '_cem_' . $meta_key, true );
    return $value ? $value : $default;
}

/**
 * Update event meta.
 *
 * @param int    $event_id Event post ID.
 * @param string $meta_key Meta key.
 * @param mixed  $meta_value Meta value.
 * @return int|bool Meta ID or false on failure.
 */
function cem_update_event_meta( $event_id, $meta_key, $meta_value ) {
    return update_post_meta( $event_id, '_cem_' . $meta_key, $meta_value );
}
