<?php
#
# Functions for shortcodes.
#
# Reference: http://codex.wordpress.org/Shortcode_API



# Print either an overview calendar or event listings.
# (The code for either is largely the same, so there's
# one function for both.)
#
# Parameters:
# $type -- What to print. Either 'overview' or 'listings'
# $atts -- The attributes passed in to the shortcode handler.
function bfc_overview_or_event_listings($type, $atts) {
    if (!isset($atts['for'])) {
        die("Did an overview calendar or event listing without specifying 'for'");
    }

    if ($atts['for'] == 'palooza') {
        $startdate = strtotime(PSTART);
        $enddate   = strtotime(PEND);
        $caltype = 'palooza';
    }
    else if ($atts['for'] == 'month') {
        global $wp_query;

        $now = getdate();
        
        $year = isset($wp_query->query_vars['calyear']) ?
            $wp_query->query_vars['calyear'] :
            $now["year"];

        $month = isset($wp_query->query_vars['calmonth']) ?
            $wp_query->query_vars['calmonth'] :
            $now["mon"];

        $year = intval($year);
        $month = intval($month);

        $startdate = mktime(0, 0, 0, #h:m:s
                            $month,
                            1,
                            $year);
        # End the calendar at the end of this month.
        # The 0th next month is the last day of this month.
        # PHP also does the right thing at the end of year.
        $enddate   = mktime(0,0,0, # h:m:s
                            $month + 1,  
                            0,
                            $year);

        $caltype = 'cal';
    }
    else {
        die("Bad value of for: " . $atts['for']);
    }

    # WordPress wants shortcodes to return their content as a string,
    # not output it with print statements. But all of the existing code
    # uses print statements, and changing it to use strings would be a
    # hassle. Fortunately, PHP's output buffering (OB) functions can capture
    # print statements into a string.
    ob_start();
    ob_implicit_flush(0);
    
    if ($type == 'overview') {
        overview_calendar($startdate,
                          $enddate,
                          $caltype,
                          TRUE); # preload all days
    }
    else if ($type == 'listings') {
        event_listings($startdate,
                       $enddate,
                       TRUE, #preload
                       FALSE,   # For printer?
                       TRUE);  # Include images?
    }
    else {
        die("Bad value of type: " . $type);
    }

    $calendar_contents = ob_get_contents();
    ob_end_clean();

    return $calendar_contents;
}

#
# Print the overview calendar that goes on a page.
#
# This assumes you'll also put an event listing on the same page.
function bfc_overview_calendar_tag($atts) {
    return bfc_overview_or_event_listings('overview', $atts);
}
add_shortcode('bfc_overview_calendar', 'bfc_overview_calendar_tag');

#
# Print the event listings. 
function bfc_event_listings_tag($atts) {
    return bfc_overview_or_event_listings('listings', $atts);
}
add_shortcode('bfc_event_listings', 'bfc_event_listings_tag');

#
# Print the event sumission form (or the results)
function bfc_event_submission_tag($atts) {
    $event_submission = new BfcEventSubmission();

    if ($event_submission->page_to_show() == "edit-event") {
        # We have to load the javascript in the footer, because by now
        # the header has already been output and it's too late for that.
        add_action('wp_footer', 'bfc_load_event_submission_form_javascript');
        bfc_print_event_submission_form($event_submission);
    }
    else if ($event_submission->page_to_show() == "event-updated") {
        bfc_print_event_submission_result($event_submission);
    }
    else if ($event_submission->page_to_show() == "event-deleted") {
        bfc_print_event_deletion_result($event_submission);
    }
    else {
        die();
    }
}
add_shortcode('bfc_event_submission', 'bfc_event_submission_tag');


#
# Load the JavaScript code that the event submission page needs.
#
# This is designed to be run in the wp_footer action.
function bfc_load_event_submission_form_javascript() {
    # @@@ Evan isn't sure how to do this without hard-coding
    # 'bikefuncal' into the URL.
    $js_url = plugins_url('bikefuncal/calform.js');

    wp_register_script('calform', $js_url, array('jquery'));
    wp_localize_script('calform',
                       'BikeFunAjax',
                       array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

    wp_print_scripts('calform');                       
    
}
