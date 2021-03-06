<?php

# Print the results of submitting an event
# 
# $event_submission -- A BfcEventSubmission object.
function bfc_print_event_submission_result($event_submission) {

$edit_url = bfc_get_edit_url_for_event($event_submission->event_id(),
                                       $event_submission->editcode());

$permalink_url = get_permalink($event_submission->wordpress_id());                                       
?>


<div class="event-updated">

<h3>Event Saved!</h3>
<p>
Your changes have been saved.
</p>

<p>
 To make changes to your event, go here:
<br>

<a href="<?php print esc_url($edit_url); ?>">
<?php print $edit_url; ?>
</a>

</p>


<p>
 To share your event with friends, send them here:
<br>
<a href="<?php print esc_url($permalink_url); ?>">
<?php print $permalink_url;  ?>
</a>
</p>

<?php
if ($event_submission->current_action() == 'update') {
    $event_submission->print_changes();
}    

$exceptions = $event_submission->get_exceptions();
if (count($exceptions) > 0) {
    print "<p>";
    print "Here are the exceptions to this event:";
    print "</p>";

    print "<ul>";

    foreach ($exceptions as $exception) {
        print "<li>";

        $edit_url = bfc_get_edit_url_for_event($exception['exceptionid']);
        $edit_url = esc_url($edit_url);
        print "<a href='${edit_url}'>";
        print date("l, F j", strtotime($exception['sqldate']));
        print "</a>";

        print "</li>";
    }
}

?>

</div>


<?php
}
?>
