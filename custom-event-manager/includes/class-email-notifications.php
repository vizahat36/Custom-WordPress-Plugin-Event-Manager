<?php
/**
 * Email Notification Handler
 *
 * @package Custom_Event_Manager
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'CEM_Email_Notifications' ) ) {
    /**
     * Handle email notifications for RSVP submissions.
     */
    class CEM_Email_Notifications {

        /**
         * Constructor.
         */
        public function __construct() {
            add_action( 'cem_rsvp_created', array( $this, 'send_rsvp_emails' ), 10, 4 );
        }

        /**
         * Send RSVP confirmation and admin notification emails.
         *
         * @param int    $rsvp_id   RSVP post ID.
         * @param int    $event_id  Event post ID.
         * @param string $name      Attendee name.
         * @param string $email     Attendee email.
         */
        public function send_rsvp_emails( $rsvp_id, $event_id, $name, $email ) {
            // Get event details.
            $event_title    = get_the_title( $event_id );
            $event_date     = get_post_meta( $event_id, '_cem_event_date', true );
            $event_time     = get_post_meta( $event_id, '_cem_event_time', true );
            $event_location = get_post_meta( $event_id, '_cem_event_location', true );
            $event_url      = get_permalink( $event_id );

            // Format date for email.
            $formatted_date = ! empty( $event_date ) ? wp_date( 'F j, Y', strtotime( $event_date ) ) : '';

            // Send confirmation email to attendee.
            $this->send_confirmation_email( $email, $name, $event_title, $formatted_date, $event_time, $event_location, $event_url );

            // Send notification email to admin.
            $this->send_admin_notification( $event_id, $name, $email, $event_title, $formatted_date, $event_time, $event_location );
        }

        /**
         * Send confirmation email to attendee.
         *
         * @param string $to        Attendee email address.
         * @param string $name      Attendee name.
         * @param string $event     Event title.
         * @param string $date      Event date (formatted).
         * @param string $time      Event time.
         * @param string $location  Event location.
         * @param string $event_url Event URL.
         * @return bool True on success, false on failure.
         */
        private function send_confirmation_email( $to, $name, $event, $date, $time, $location, $event_url ) {
            // Build email subject.
            $subject = sprintf(
                /* translators: %s: Event title */
                __( 'RSVP Confirmation: %s', 'custom-event-manager' ),
                $event
            );

            // Allow filtering of subject.
            $subject = apply_filters( 'cem_rsvp_confirmation_subject', $subject, $event, $name );

            // Build email body.
            $message = sprintf(
                /* translators: 1: Attendee name, 2: Event title */
                __( 'Hi %1$s,', 'custom-event-manager' ) . "\r\n\r\n" .
                __( 'Thank you for your RSVP! You have successfully registered for:', 'custom-event-manager' ) . "\r\n\r\n" .
                __( 'Event: %2$s', 'custom-event-manager' ) . "\r\n",
                esc_html( $name ),
                esc_html( $event )
            );

            // Add event details.
            if ( ! empty( $date ) ) {
                $message .= sprintf(
                    __( 'Date: %s', 'custom-event-manager' ) . "\r\n",
                    esc_html( $date )
                );
            }

            if ( ! empty( $time ) ) {
                $message .= sprintf(
                    __( 'Time: %s', 'custom-event-manager' ) . "\r\n",
                    esc_html( $time )
                );
            }

            if ( ! empty( $location ) ) {
                $message .= sprintf(
                    __( 'Location: %s', 'custom-event-manager' ) . "\r\n",
                    esc_html( $location )
                );
            }

            $message .= "\r\n" . sprintf(
                __( 'View event details: %s', 'custom-event-manager' ) . "\r\n\r\n",
                esc_url( $event_url )
            );

            $message .= __( 'We look forward to seeing you there!', 'custom-event-manager' ) . "\r\n\r\n";
            $message .= sprintf(
                __( 'Best regards,', 'custom-event-manager' ) . "\r\n" .
                __( '%s', 'custom-event-manager' ),
                esc_html( get_bloginfo( 'name' ) )
            );

            // Allow filtering of message body.
            $message = apply_filters( 'cem_rsvp_confirmation_message', $message, $event, $name, $date, $time, $location );

            // Set email headers.
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                sprintf( 'From: %s <%s>', get_bloginfo( 'name' ), get_option( 'admin_email' ) ),
            );

            // Allow filtering of headers.
            $headers = apply_filters( 'cem_rsvp_confirmation_headers', $headers );

            // Send email.
            $sent = wp_mail( sanitize_email( $to ), $subject, $message, $headers );

            // Log if failed.
            if ( ! $sent ) {
                error_log( sprintf( 'CEM: Failed to send RSVP confirmation email to %s for event %s', $to, $event ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }

            do_action( 'cem_after_confirmation_email', $sent, $to, $event );

            return $sent;
        }

        /**
         * Send notification email to admin.
         *
         * @param int    $event_id  Event post ID.
         * @param string $name      Attendee name.
         * @param string $email     Attendee email.
         * @param string $event     Event title.
         * @param string $date      Event date (formatted).
         * @param string $time      Event time.
         * @param string $location  Event location.
         * @return bool True on success, false on failure.
         */
        private function send_admin_notification( $event_id, $name, $email, $event, $date, $time, $location ) {
            // Get admin email.
            $admin_email = get_option( 'admin_email' );

            // Build email subject.
            $subject = sprintf(
                /* translators: 1: Event title, 2: Attendee name */
                __( 'New RSVP: %1$s - %2$s', 'custom-event-manager' ),
                $event,
                $name
            );

            // Allow filtering of subject.
            $subject = apply_filters( 'cem_admin_notification_subject', $subject, $event, $name, $email );

            // Build email body.
            $message = sprintf(
                __( 'A new RSVP has been submitted for your event:', 'custom-event-manager' ) . "\r\n\r\n" .
                __( 'Event: %s', 'custom-event-manager' ) . "\r\n",
                esc_html( $event )
            );

            // Add event details.
            if ( ! empty( $date ) ) {
                $message .= sprintf(
                    __( 'Date: %s', 'custom-event-manager' ) . "\r\n",
                    esc_html( $date )
                );
            }

            if ( ! empty( $time ) ) {
                $message .= sprintf(
                    __( 'Time: %s', 'custom-event-manager' ) . "\r\n",
                    esc_html( $time )
                );
            }

            if ( ! empty( $location ) ) {
                $message .= sprintf(
                    __( 'Location: %s', 'custom-event-manager' ) . "\r\n",
                    esc_html( $location )
                );
            }

            $message .= "\r\n" . __( 'Attendee Details:', 'custom-event-manager' ) . "\r\n";
            $message .= sprintf(
                __( 'Name: %s', 'custom-event-manager' ) . "\r\n",
                esc_html( $name )
            );
            $message .= sprintf(
                __( 'Email: %s', 'custom-event-manager' ) . "\r\n\r\n",
                esc_html( $email )
            );

            $message .= sprintf(
                __( 'View event RSVPs: %s', 'custom-event-manager' ),
                esc_url( admin_url( 'edit.php?post_type=event_rsvp&event_id=' . $event_id ) )
            );

            // Allow filtering of message body.
            $message = apply_filters( 'cem_admin_notification_message', $message, $event, $name, $email, $date, $time, $location );

            // Set email headers.
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                sprintf( 'Reply-To: %s <%s>', $name, $email ),
            );

            // Allow filtering of headers.
            $headers = apply_filters( 'cem_admin_notification_headers', $headers );

            // Send email.
            $sent = wp_mail( sanitize_email( $admin_email ), $subject, $message, $headers );

            // Log if failed.
            if ( ! $sent ) {
                error_log( sprintf( 'CEM: Failed to send admin notification email for event %s', $event ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }

            do_action( 'cem_after_admin_notification', $sent, $admin_email, $event );

            return $sent;
        }
    }
}
