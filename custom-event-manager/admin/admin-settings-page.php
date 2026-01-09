<?php
/**
 * Admin Settings Page (Deprecated - Use class-event-settings.php)
 *
 * @package Custom_Event_Manager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render admin event metabox.
 *
 * @param WP_Post $post Current post object.
 */
function cem_render_event_metabox( $post ) {
    wp_nonce_field( 'cem_event_nonce', 'cem_event_nonce_field' );

    $event_date  = get_post_meta( $post->ID, '_cem_event_date', true );
    $event_time  = get_post_meta( $post->ID, '_cem_event_time', true );
    $event_location = get_post_meta( $post->ID, '_cem_event_location', true );

    ?>
    <div class="cem-metabox">
        <p>
            <label for="cem_event_date"><strong>Event Date:</strong></label><br />
            <input type="date" id="cem_event_date" name="cem_event_date" value="<?php echo esc_attr( $event_date ); ?>" />
        </p>
        <p>
            <label for="cem_event_time"><strong>Event Time:</strong></label><br />
            <input type="time" id="cem_event_time" name="cem_event_time" value="<?php echo esc_attr( $event_time ); ?>" />
        </p>
        <p>
            <label for="cem_event_location"><strong>Event Location:</strong></label><br />
            <input type="text" id="cem_event_location" name="cem_event_location" value="<?php echo esc_attr( $event_location ); ?>" placeholder="Enter location" />
        </p>
    </div>
    <?php
}

/**
 * Save event metabox data.
 *
 * @param int $post_id Post ID.
 */
function cem_save_event_metabox( $post_id ) {
    if ( ! isset( $_POST['cem_event_nonce_field'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cem_event_nonce_field'] ) ), 'cem_event_nonce' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['cem_event_date'] ) ) {
        update_post_meta( $post_id, '_cem_event_date', sanitize_text_field( wp_unslash( $_POST['cem_event_date'] ) ) );
    }

    if ( isset( $_POST['cem_event_time'] ) ) {
        update_post_meta( $post_id, '_cem_event_time', sanitize_text_field( wp_unslash( $_POST['cem_event_time'] ) ) );
    }

    if ( isset( $_POST['cem_event_location'] ) ) {
        update_post_meta( $post_id, '_cem_event_location', sanitize_text_field( wp_unslash( $_POST['cem_event_location'] ) ) );
    }
}
