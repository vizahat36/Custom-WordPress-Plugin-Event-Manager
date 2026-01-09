/**
 * Custom Event Manager - Frontend JavaScript
 */

(function( $ ) {
    'use strict';

    $(document).ready(function() {
        // Initialize event listeners
        initEventFilters();
        initEventModals();
        initRSVPForm();
    });

    /**
     * Initialize event filtering functionality.
     */
    function initEventFilters() {
        var $filters = $('.cem-filter');

        if ( $filters.length === 0 ) {
            return;
        }

        $filters.on('change', function() {
            var category = $(this).val();
            filterEventsByCategory(category);
        });
    }

    /**
     * Filter events by category.
     *
     * @param {string} category Category slug.
     */
    function filterEventsByCategory(category) {
        var $items = $('.cem-event-item');

        if ( category === '' ) {
            $items.show();
        } else {
            $items.each(function() {
                var $item = $(this);
                var itemCategory = $item.data('category');

                if ( itemCategory === category ) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });
        }
    }

    /**
     * Initialize modal windows for events.
     */
    function initEventModals() {
        var $triggers = $('.cem-modal-trigger');

        $triggers.on('click', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal');
            openModal(modalId);
        });

        $('.cem-modal-close').on('click', function() {
            closeModal();
        });
    }

    /**
     * Open a modal window.
     *
     * @param {string} modalId Modal element ID.
     */
    function openModal(modalId) {
        var $modal = $('#' + modalId);
        if ( $modal.length ) {
            $modal.addClass('open');
        }
    }

    /**
     * Close the current modal window.
     */
    function closeModal() {
        $('.cem-modal.open').removeClass('open');
    }

    /**
     * Initialize RSVP form handling.
     */
    function initRSVPForm() {
        var $form = $('#cem-rsvp-form');

        if ( $form.length === 0 ) {
            return;
        }

        $form.on('submit', function(e) {
            e.preventDefault();
            submitRSVPForm($(this));
        });
    }

    /**
     * Submit RSVP form via AJAX.
     *
     * @param {jQuery} $form The form element.
     */
    function submitRSVPForm($form) {
        var eventId = $form.data('event-id');
        var nonce = $form.data('nonce');
        var name = $form.find('#cem_rsvp_name').val();
        var email = $form.find('#cem_rsvp_email').val();
        var $button = $form.find('.cem-rsvp-button');
        var $loading = $form.find('.cem-rsvp-loading');
        var $message = $form.find('.cem-rsvp-message');

        // Validate inputs.
        if ( !eventId || !nonce || !name || !email ) {
            showRSVPMessage($message, 'error', 'Please fill all required fields.');
            return;
        }

        // Disable button and show loading.
        $button.prop('disabled', true);
        $loading.show();
        $message.hide();

        // Send AJAX request.
        $.ajax({
            type: 'POST',
            url: cemAjax.ajaxurl,
            data: {
                action: 'cem_rsvp_submit',
                event_id: eventId,
                name: name,
                email: email,
                nonce: nonce
            },
            dataType: 'json',
            success: function(response) {
                if ( response.success ) {
                    showRSVPMessage($message, 'success', response.data.message);
                    $form[0].reset();
                } else {
                    showRSVPMessage($message, 'error', response.data.message);
                }
            },
            error: function() {
                showRSVPMessage($message, 'error', 'An error occurred. Please try again.');
            },
            complete: function() {
                // Re-enable button and hide loading.
                $button.prop('disabled', false);
                $loading.hide();
            }
        });
    }

    /**
     * Display RSVP message.
     *
     * @param {jQuery} $message Message container.
     * @param {string} type Message type: 'success' or 'error'.
     * @param {string} text Message text.
     */
    function showRSVPMessage($message, type, text) {
        $message.removeClass('cem-message-success cem-message-error');
        $message.addClass('cem-message-' + type);
        $message.text(text);
        $message.slideDown();

        // Auto-hide success message after 5 seconds.
        if ( type === 'success' ) {
            setTimeout(function() {
                $message.slideUp();
            }, 5000);
        }
    }

})( jQuery );
