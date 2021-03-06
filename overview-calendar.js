// The popup library comes from here:
// http://www.ericmmartin.com/projects/simplemodal/

// Use the same set of options twice: Once when loading the
// spinner and once when loading the real content.
var BfcPopupOptions = {
    // Click the overlay to close
    'overlayClose': true,

    // Opacity of the overlay (in percent)
    'opacity': 20,
	
    'minWidth': 800,
    'maxWidth': 800
};

function update_popup(popup_content_html) {
    // Close the popup with the spinner
    jQuery.modal.close();

    // There are two ways of opening a modal window. 
    // 1. jQuery(content).modal(options);
    // 2. jQuery.modal(content, options);
    //
    // If you use way #1 here, the popup is too small and the
    // content gets clipped.
    jQuery.modal(popup_content_html, BfcPopupOptions);
    jQuery.modal.setContainerDimensions();

    // Attach click-to-email actions
    descramble_emails();

    // Attach popup to previous & next buttons
    jQuery('.event-navigation a').each(function(index, element) {
        element = jQuery(element);
        element.click(function(e) {
            // Show a pop-up if there's enough width, otherwise just 
            // follow the link.
            if (jQuery(window).width() > 800) {
                jQuery.modal.close();
                launch_popup(element);
                return false;
            }
        });
    });
}

function launch_popup(element) {
    var ajax_params = {
        'action': 'event-popup',
        'id': element.attr('data-id'),
        'date': element.attr('data-date')
    };

    // For now, put up a spinner popup
    var initialPopup = jQuery('<div id=cal-popup><img id=spinner></div>');
    initialPopup.find('#spinner').attr('src', BikeFunAjax.spinnerURL);
    initialPopup.modal(BfcPopupOptions);

    // Get the results back as text, so we don't have
    // to worry about validating them as XML
    jQuery.post(
        BikeFunAjax.ajaxURL,
        ajax_params,
        update_popup, 
        'text');
}

jQuery(document).ready(function() {
    jQuery('.event-title a').each(function(index, element) {
        element = jQuery(element);
        element.click(function(e) {
            if (jQuery(window).width() > 800) {
                launch_popup(element)
                
                // Within a jQuery handler, 'return false' prevents
                // propagation and stops the event from bubbling up.
                return false;
            }
        });
    });
});


/* Scroll the date selector calendar alongside the event listings.
 *
 */
(function($) {
    $(document).ready(function() {
        var calendar = $('#date-selector-calendar');

        // Waypoint at the top of the event listings
        var topOptions = {
            // none
        };
        var topHandler = function(event, direction) {
            calendar.toggleClass('sticky', direction === 'down');
            event.stopPropagation();
        };
        $('.date-selector-listings .top-of-listings').waypoint(topHandler, topOptions);

        // Waypoint at the bottom of the event listings
        var bottomOptions = {
            offset: function() {
              return calendar.outerHeight();
            }
        };
        var bottomHandler = function(event, direction) {
            calendar.toggleClass('bottom', direction === 'down');
            event.stopPropagation();
        };
        $('.date-selector-listings .bottom-of-listings').waypoint(bottomHandler, bottomOptions);
    });
})(jQuery);


