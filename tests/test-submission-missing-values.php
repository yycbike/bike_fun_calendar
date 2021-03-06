<?php
require_once('test-case.php');

class TestSubmissionMissingValues extends BfcTestCase {
    // @@@ Add tests for other fields with missing_value (e.g., Title, Tiny Title, etc.)

    /**
     * Load the event from the database, return the value of the specified field.
     */
    function get_value_from_datbase($id, $field) {
        // Check that it propaged to the datbase as a 1
        global $calevent_table_name;
        global $caldaily_table_name;
        global $wpdb;

        $sql = $wpdb->prepare("SELECT * FROM ${calevent_table_name} WHERE id=%d", $id);
        $results = $wpdb->get_results($sql, ARRAY_A);

        $this->assertEquals(1, count($results));

        return $results[0][$field];
    }

    /**
     * Ensure that the hideemail flag can be set
     */
    function test_hideemail_set() {
        $event = $this->make_valid_submission(array('event_hideemail' => 'Y'));
        $this->assertEquals('1', $this->get_value_from_datbase($event->event_id(), 'hideemail'));

        return $event;
    }

    /**
     * Ensure that the hideemail flag can be cleared. See issue #68.
     *
     * @depends test_hideemail_set
     */
    function test_hideemail_clear($event) {
        $event = $this->update_submission($event, array('event_hideemail' => null));
        $this->assertEquals('0', $this->get_value_from_datbase($event->event_id(), 'hideemail'));
    }
}