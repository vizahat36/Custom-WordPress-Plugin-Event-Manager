/**
 * Custom Event Manager - Frontend JavaScript
 */

(function( $ ) {
    'use strict';

    $(document).ready(function() {
        // Initialize event listeners
        initEventFilters();
        initEventModals();
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

})( jQuery );
