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
            add_shortcode( 'cem_events', array( $this, 'display_events' ) );
            add_shortcode( 'cem_event_single', array( $this, 'display_single_event' ) );
        }

        /**
         * Display all events as a list.
         *
         * @param array $atts Shortcode attributes.
         * @return string HTML output.
         */
        public function display_events( $atts ) {
            $atts = shortcode_atts(
                array(
                    'posts_per_page' => 10,
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ),
                $atts,
                'cem_events'
            );

            $args = array(
                'post_type'      => 'event',
                'posts_per_page' => (int) $atts['posts_per_page'],
                'orderby'        => $atts['orderby'],
                'order'          => $atts['order'],
            );

            $query = new WP_Query( $args );

            if ( ! $query->have_posts() ) {
                return '<p>No events found.</p>';
            }

            ob_start();
            echo '<div class="cem-events-list">';

            while ( $query->have_posts() ) {
                $query->the_post();
                $event_date = get_post_meta( get_the_ID(), '_cem_event_date', true );
                echo '<div class="cem-event-item">';
                echo '<h3>' . esc_html( get_the_title() ) . '</h3>';
                if ( $event_date ) {
                    echo '<p><strong>Date:</strong> ' . esc_html( $event_date ) . '</p>';
                }
                echo '<p>' . wp_kses_post( get_the_excerpt() ) . '</p>';
                echo '<a href="' . esc_url( get_permalink() ) . '" class="cem-read-more">Read More</a>';
                echo '</div>';
            }

            echo '</div>';
            wp_reset_postdata();

            return ob_get_clean();
        }

        /**
         * Display a single event by ID.
         *
         * @param array $atts Shortcode attributes.
         * @return string HTML output.
         */
        public function display_single_event( $atts ) {
            $atts = shortcode_atts(
                array(
                    'id' => '',
                ),
                $atts,
                'cem_event_single'
            );

            if ( ! $atts['id'] ) {
                return '<p>Event ID not provided.</p>';
            }

            $post = get_post( (int) $atts['id'] );

            if ( ! $post || 'event' !== $post->post_type ) {
                return '<p>Event not found.</p>';
            }

            ob_start();
            echo '<div class="cem-event-single">';
            echo '<h2>' . esc_html( $post->post_title ) . '</h2>';
            echo wp_kses_post( wpautop( $post->post_content ) );
            echo '</div>';

            return ob_get_clean();
        }
    }
}
