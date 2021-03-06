<?php

#
# Functions for shortcodes.
#
# Reference: http://codex.wordpress.org/Shortcode_API

# Return the dates a calendar covers
function bfc_get_cal_dates($atts) {
    if ($atts['for'] == 'palooza') {
        // Start and End can be specified to show archived Paloozas
        $start = isset($atts['start']) ? $atts['start'] : get_option('bfc_festival_start_date');
        $end   = isset($atts['end'])   ? $atts['end']   : get_option('bfc_festival_end_date');

        $startdate = strtotime($start);
        $enddate   = strtotime($end);
    }
    else if ($atts['for'] == 'current') {
        // Start and end can be specified for debugging.
        if (isset($atts['start'])) {
            $startdate = strtotime($atts['start']);
        }
        else {
            // Choose the starting date.  This is always the Sunday at or before
            // today.  We'll move forward from there.
            $now = getdate();
            $noon = $now[0] + (12 - $now["hours"]) * 3600; // approximately noon today
            $startdate = $noon - 86400 * $now["wday"];
        }

        if (isset($atts['end'])) {
            $enddate = strtotime($atts['end']);
        }
        else {
            $numweeks = 3;
            $enddate = $startdate + ($numweeks * 7 - 1) * 86400;
        }
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
    }
	else if ($atts['for'] == 'day') {
		global $wp_query;
		
		$now = getdate(current_time('timestamp'));
				
		$year = isset($wp_query->query_vars['calyear']) ?
        $wp_query->query_vars['calyear'] :
        $now["year"];
		
        $month = isset($wp_query->query_vars['calmonth']) ?
        $wp_query->query_vars['calmonth'] :
        $now["mon"];
				
		$date = isset($wp_query->query_vars['caldate']) ?
		$wp_query->query_vars['caldate'] :
		$now["mday"];
				
		$startdate = mktime(0, 0, 0, $month, $date, $year);
		$enddate = $startdate;
	}
    else {
        die("Bad value of for: " . $atts['for']);
    }

    return array($startdate, $enddate);
}

// Print either an overview calendar or event listings.
// (The code for either is largely the same, so there's
// one function for both.)
//
// Parameters:
// $type -- What to print. Either 'overview' or 'listings'
// $atts -- The attributes passed in to the shortcode handler.
function bfc_overview_or_event_listings($type, $atts) {
    if (!isset($atts['for'])) {
        die("Did an overview calendar or event listing without specifying 'for'");
    }
	
	$compact = FALSE;

    if ($atts['for'] == 'palooza') {
        $caltype = 'palooza';
    }
    else if ($atts['for'] == 'current') {
        $caltype = 'cal';
    }
    else if ($atts['for'] == 'month') {
        $caltype = 'cal';
    }
	else if ($atts['for'] == 'day') {
		$caltype = 'cal';
		$compact = TRUE;
	}
    else {
        die("Bad value of for: " . $atts['for']);
    }

    list($startdate, $enddate) = bfc_get_cal_dates($atts);

    // WordPress wants shortcodes to return their content as a string,
    // not output it with print statements. But all of the existing code
    // uses print statements, and changing it to use strings would be a
    // hassle. Fortunately, PHP's output buffering (OB) functions can capture
    // print statements into a string.
    ob_start();
    ob_implicit_flush(0);
    
    if ($type == 'overview') {
        overview_calendar($startdate,
                          $enddate,
                          $caltype,
                          TRUE); // preload all days
                          
        add_action('wp_footer', 'load_overview_calendar_javascript');                                         
    }
    else if ($type == 'listings') {
        event_listings($startdate,
                       $enddate,
					   $compact); #TRUE - tiny event listing, FALSE - full event listing
    }
    else if ($type == 'date-selector') {
        print "<div id=date-selector>";
        print "<div id=date-selector-calendar-container>";

        print "<div id=date-selector-calendar>";
        bfc_date_selector_calendar($startdate, $enddate);
        if ($atts['for'] !== 'palooza') {
            print bfc_cal_date_navigation_shortcode($atts);
        }
        print "</div>"; // #date-selector-calendar

        print "</div>"; // #date-selector-calendar-container

        bfc_date_selector_listings($startdate, $enddate);

        print "<div class=clear></div>";
        print "</div>"; // #date-selector

        add_action('wp_footer', 'load_date_selector_javascript');
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
add_shortcode('bfc_overview_calendar', 'bfc_overview_calendar_shortcode');
function bfc_overview_calendar_shortcode($atts) {
    return bfc_overview_or_event_listings('overview', $atts);
}

#
# Print the event listings. 
add_shortcode('bfc_event_listings', 'bfc_event_listings_shortcode');
function bfc_event_listings_shortcode($atts) {
    return bfc_overview_or_event_listings('listings', $atts);
}

add_shortcode('bfc_date_selector', 'bfc_date_selector_shortcode');
function bfc_date_selector_shortcode($atts) {
    return bfc_overview_or_event_listings('date-selector', $atts);
}


#
# Print the button to navigate to the previous set of dates
# in the calendar. 
add_shortcode('bfc_cal_date_navigation', 'bfc_cal_date_navigation_shortcode');
function bfc_cal_date_navigation_shortcode($atts) {
    if ($atts['for'] == 'current') {
        list($startdate, $enddate) = bfc_get_cal_dates($atts);
        
        if (date("F", $startdate) != date("F", $enddate)) {
            // start & end dates are on different months

            $prev_url = bfc_get_month_cal_url(date("m", $startdate), date("Y", $startdate));
            $next_url = bfc_get_month_cal_url(date("m", $enddate), date("Y", $enddate));

            $prev_url = esc_url($prev_url);
            $next_url = esc_url($next_url);

            $prev_month_name = date("F", $startdate);
            $next_month_name = date("F", $enddate);

            return "<div class='cal-links'>
                    <div class='cal-link previous'>
                    <a href='${prev_url}'>&larr; All of ${prev_month_name}</a>
                    </div>

                    <div class='cal-link next'>
                    <a href='${next_url}'>All of ${next_month_name} &rarr;</a>
                    </div>

                    </div>
                    ";
        }
        else {
            // Start and end dates are on the same month
            $prev_month = $startdate - (86400 * 28);
            $next_month = $enddate   + (86400 * 28);

            $prev_url = bfc_get_month_cal_url(date("m", $prev_month), date("Y", $prev_month));
            $next_url = bfc_get_month_cal_url(date("m", $next_month), date("Y", $next_month));
            $curr_url = bfc_get_month_cal_url(date("m", $startdate),  date("Y", $startdate));

            $prev_url = esc_url($prev_url);
            $next_url = esc_url($next_url);
            $curr_url = esc_url($curr_url);

            $prev_month_name = date("F", $prev_month);
            $next_month_name = date("F", $next_month);
            $curr_month_name = date("F", $startdate);

            return "<div class='cal-links'>

                    <div class='cal-link previous'>
                    <a href='${prev_url}'>&larr; ${prev_month_name}</a>
                    </div>

                    <div class='cal-link next'>
                    <a href='${next_url}'>${next_month_name} &rarr;</a>
                    </div>

                    <div class='cal-link current'>
                    <a href='${curr_url}'>All of ${curr_month_name}</a>
                    </div>

                    </div>
                    ";
        }
    }
    else if ($atts['for'] == 'month') {
        list($startdate, $enddate) = bfc_get_cal_dates($atts);

        $prev_month = mktime(0, 0, 0,
                             date('m', $startdate) - 1,
                             1,
                             date('Y', $startdate));
        $next_month = mktime(0, 0, 0,
                             date('m', $startdate) + 1,
                             1,
                             date('Y', $startdate));

        $prev_url = bfc_get_month_cal_url(date("m", $prev_month), date("Y", $prev_month));
        $next_url = bfc_get_month_cal_url(date("m", $next_month), date("Y", $next_month));

        $prev_url = esc_url($prev_url);
        $next_url = esc_url($next_url);

        $prev_month_name = date("F", $prev_month);
        $next_month_name = date("F", $next_month);

        return "<div class='cal-links'>

                <div class='cal-link previous'>
                <a href='${prev_url}'>&larr; ${prev_month_name}</a>
                </div>

                <div class='cal-link next'>
                <a href='${next_url}'>${next_month_name} &rarr;</a>
                </div>

                </div>
                ";
    }
    else if ($atts['for'] == 'day') {
		list($startdate, $enddate) = bfc_get_cal_dates($atts);

        $prev_day = mktime(0, 0, 0,
                             date('m', $startdate),
                             date('j', $startdate) - 1,
                             date('Y', $startdate));
        $next_day = mktime(0, 0, 0,
                             date('m', $startdate),
                             date('j', $startdate) + 1,
                             date('Y', $startdate));
							 
		$prev_url = bfc_get_day_cal_url(date("m", $prev_day), date("Y", $prev_day), date("j", $prev_day));
        $next_url = bfc_get_day_cal_url(date("m", $next_day), date("Y", $next_day), date("j", $next_day));

        $prev_url = esc_url($prev_url);
        $next_url = esc_url($next_url);
		
		$prev_day_name = "Previous Day";
		$next_day_name = "Next Day";
		
		$now = getdate(current_time('timestamp'));

		if(($now["year"] == date('Y', $startdate)) 
				&& ($now["mon"] == date('m', $startdate)) 
				&& ($now["mday"] == date('j', $startdate))) {
			$prev_day_name = "Yesterday";
			$next_day_name = "Tomorrow";
		}
		
		
		
		return "<div class='cal-links'>

                <div class='cal-link previous'>
                <a href='${prev_url}'>&larr; ${prev_day_name}</a>
                </div>

                <div class='cal-link next'>
                <a href='${next_url}'>${next_day_name} &rarr;</a>
                </div>

                </div>
                ";
		
	}
	else {
        die("Can't have a previous date for: " . $atts['for']);
    }
}

# A shorttag that returns the number of events. It's meant to show how many
# events are in a palooza (current or archived).
add_shortcode('bfc_cal_count', 'bfc_cal_count_shortcode');
function bfc_cal_count_shortcode($atts) {
    # Start and End can be specified to show archived Paloozas.
    #
    # bfc_get_cal_dates() returns times as integers and we want strings,
    # so don't bother calling it.
    $start = isset($atts['start']) ? $atts['start'] : get_option('bfc_festival_start_date');
    $end   = isset($atts['end'])   ? $atts['end']   : get_option('bfc_festival_end_date');

    global $wpdb;
    global $caldaily_table_name;
    global $calevent_table_name;
    # The old code checks the review flag here, but we haven't turned that on yet.
    $sql = $wpdb->prepare("SELECT count(${caldaily_table_name}.id) " .
                          "FROM ${caldaily_table_name}, ${calevent_table_name} " .
                          "WHERE ${calevent_table_name}.id = ${caldaily_table_name}.id AND " .
                          "      eventdate >= %s   AND " .
                          "      eventdate <= %s   AND " .
                          "      eventstatus <> \"E\"  AND " .
                          "      eventstatus <> \"C\"  AND " .
                          "      eventstatus <> \"S\" ",
                          $start, $end);
                          
    $count = $wpdb->get_var($sql);
    return $count;
}

function bfc_get_month_cal_url($month = null, $year = null) {
    $edit_page_title = 'Monthly Calendar';
    $edit_page = get_page_by_title($edit_page_title);
    $url = get_permalink($edit_page->ID); 

    // If the URL already has a query string, add additional queries with
    // '&'. Otherwise, initiate a query with '?'
    $has_query = (parse_url($url, PHP_URL_QUERY) !== null);
    
    if ($month !== null) {
        $url .= sprintf('%scalmonth=%s',
                        $has_query ? '&' : '?',
                        $month);
        $has_query = true;
    }

    if ($year !== null) {
        $url .= sprintf('%scalyear=%s',
                        $has_query ? '&' : '?',
                        $year);
        $has_query = true;
    }
	
    return $url;
}

function bfc_get_day_cal_url($month = null, $year = null, $day = null) {
	$edit_page_title = 'Today\'s Rides';
    $edit_page = get_page_by_title($edit_page_title);
    $url = get_permalink($edit_page->ID); 

    // If the URL already has a query string, add additional queries with
    // '&'. Otherwise, initiate a query with '?'
    $has_query = (parse_url($url, PHP_URL_QUERY) !== null);
    
    if ($month !== null) {
        $url .= sprintf('%scalmonth=%s',
                        $has_query ? '&' : '?',
                        $month);
        $has_query = true;
    }

    if ($year !== null) {
        $url .= sprintf('%scalyear=%s',
                        $has_query ? '&' : '?',
                        $year);
        $has_query = true;
    }
	
	if ($day !== null) {
		$url .= sprintf('%scaldate=%s',
                        $has_query ? '&' : '?',
                        $day);
        $has_query = true;
	}

    return $url;
}

#
# Print the event sumission form (or the results)
add_shortcode('bfc_event_submission', 'bfc_event_submission_shortcode');
function bfc_event_submission_shortcode($atts) {
    $event_submission = bfc_event_submission();
    
    if ($event_submission->page_to_show() == "edit-event") {
        // We have to load the javascript in the footer, because by now
        // the header has already been output and it's too late for that.
        add_action('wp_footer', 'load_event_submission_form_javascript');
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

/**
 * Load the JavaScript code that the event submission page needs.
 *
 * This is designed to be run in the wp_footer action.
 */
function load_event_submission_form_javascript() {
    wp_print_styles('bfc-jquery-ui-style');
    wp_print_scripts('bfc-event-submission');                       
}

function load_overview_calendar_javascript() {
    wp_print_scripts('bfc-overview-calendar');
}

function load_date_selector_javascript() {
    wp_print_scripts('bfc-date-selector');
    wp_print_scripts('bfc-overview-calendar');    
}

/**
 * Print the days in the festival (e.g., 'June 3 to 18, 2010').
 */
add_shortcode('bfc_festival_dates', 'bfc_festival_dates_shortcode');
function bfc_festival_dates_shortcode($atts) {
    $start = get_option('bfc_festival_start_date');
    $end   = get_option('bfc_festival_end_date');

    $startdate = strtotime($start);
    $enddate   = strtotime($end);

    $start_month = date('F', $startdate);
    $start_day   = date('j', $startdate);
    $start_year  = date('Y', $startdate);

    $end_month = date('F', $enddate);
    $end_day   = date('j', $enddate);
    $end_year  = date('Y', $enddate);

    $same_month = ($start_month == $end_month);
    if ($same_month) {
        return sprintf('%s %s to %s, %s',
                       $start_month, $start_day, $end_day, $start_year);
    }
    else {
        return sprintf('%s %s to %s %s, %s',
                       $start_month, $start_day, $end_month, $end_day, $start_year);
    }
}

/**
 * Print the number of days in the festival
 */
add_shortcode('bfc_festival_count_days', 'bfc_festival_count_days_shortcode');
function bfc_festival_count_days_shortcode($atts) {
    $start = new DateTime(get_option('bfc_festival_start_date'));
    $end = new DateTime(get_option('bfc_festival_end_date'));

    $interval = $start->diff($end);

    return $interval->d;
}

global $overview_cal_padding_before;
global $overview_cal_padding_after;
$overview_cal_padding_before = '';
$overview_cal_padding_after = '';
add_shortcode('bfc_overview_cal_padding', 'bfc_overview_cal_padding_shortcode');
function bfc_overview_cal_padding_shortcode($atts, $content) {
    global $overview_cal_padding_before;
    global $overview_cal_padding_after;

    if ($atts['for'] == 'before') {
        $overview_cal_padding_before = $content;
    }
    else if ($atts['for'] == 'after') {
        $overview_cal_padding_after = $content;
    }
    else {
        die();
    }
}
