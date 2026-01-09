<?php
/**
 * Custom Post Type: Events
 *
 * @package Custom_Event_Manager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CEM_Event_Post_Type' ) ) {
    /**
     * Register and manage the 'event' custom post type.
     */
    class CEM_Event_Post_Type {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action( 'init', array( $this, 'register_post_type' ) );
            add_action( 'init', array( $this, 'register_event_taxonomy' ) );
        }

        /**
         * Register the 'event' custom post type.
         */
        public function register_post_type() {
            $labels = array(
                'name'               => 'Events',
                'singular_name'      => 'Event',
                'menu_name'          => 'Events',
                'name_admin_bar'     => 'Event',
                'add_new'            => 'Add New',
                'add_new_item'       => 'Add New Event',
                'new_item'           => 'New Event',
                'edit_item'          => 'Edit Event',
                'view_item'          => 'View Event',
                'all_items'          => 'All Events',
                'search_items'       => 'Search Events',
                'not_found'          => 'No events found.',
                'not_found_in_trash' => 'No events found in Trash.',
            );

            $args = array(
                'labels'              => $labels,
                'description'         => 'Custom Events Post Type',
                'public'              => true,
                'publicly_queryable'  => true,
                'show_ui'             => true,
                'show_in_menu'        => true,
                'show_in_nav_menus'   => true,
                'query_var'           => true,
                'rewrite'             => array( 'slug' => 'event' ),
                'capability_type'     => 'post',
                'has_archive'         => true,
                'hierarchical'        => false,
                'menu_position'       => 20,
                'menu_icon'           => 'dashicons-calendar',
                'supports'            => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields' ),
                'show_in_rest'        => true, // Enable REST API support.
            );

            register_post_type( 'event', $args );
        }

        /**
         * Register event taxonomy (category).
         */
        public function register_event_taxonomy() {
            $labels = array(
                'name'              => 'Event Categories',
                'singular_name'     => 'Event Category',
                'search_items'      => 'Search Event Categories',
                'all_items'         => 'All Event Categories',
                'parent_item'       => 'Parent Event Category',
                'parent_item_colon' => 'Parent Event Category:',
                'edit_item'         => 'Edit Event Category',
                'update_item'       => 'Update Event Category',
                'add_new_item'      => 'Add New Event Category',
                'new_item_name'     => 'New Event Category Name',
                'menu_name'         => 'Event Categories',
            );

            $args = array(
                'hierarchical'      => true,
                'labels'            => $labels,
                'show_ui'           => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => array( 'slug' => 'event-category' ),
                'show_in_rest'      => true,
            );

            register_taxonomy( 'event_category', array( 'event' ), $args );
        }
    }
}
