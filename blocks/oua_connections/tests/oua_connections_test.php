<?php

global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');

/**
 * OUA Connections block tests for my connections/suggested connections
 *
 * @package    block_oua_connections
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */
class block_oua_connections_testcase extends oua_advanced_testcase {
    /**
     * Test custom connected event fires when two users are connected.
     *
     */
    function test_fired_event_is_consumable() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user to add to the admin's contact list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // Capture fired events.
        $sink = $this->redirectEvents();

        $this->setUser($user2);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $messages = $DB->get_records('message');
        $this->assertCount(1, $messages, 'There should be only 1 message (connection request)');

        $this->setUser($user1);
        $success = \block_oua_connections\api::accept_request_connection(key($messages), $user2->id);
        $messages = $DB->get_records('message');
        $this->assertCount(2, $messages, 'There should be 2 messages (you are now connected.. connection request has been deleted)');


        // Get events that have been fired.
        $events = $sink->get_events();
        $this->assertCount(5, $events, 'There should be 5 events - connection1, connection2, connected, deleted, viewed');

        $event = array_pop($events);
        $sink->close();

        $this->assertInstanceOf('\block_oua_connections\event\contact_connected', $events[2]);
        $this->assertEquals($user1->id, $event->userid);
        $this->assertEquals($user2->id, $event->relateduserid);
    }

    function test_block_html_valid() {
        global $DB, $USER;
        $this->resetAfterTest();
        // Set this user as the admin.
        $this->setAdminUser();

        // Create a user to add to the admin's contact list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();


        $this->setUser($user2);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user1);
        $messages = $DB->get_records('message');
        $this->assertCount(1, $messages, 'There should be only 1 message (connection request)');
        $this->setUser($user1);
        $success = \block_oua_connections\api::accept_request_connection(key($messages), $user2->id);

        $block = $this->getDataGenerator()->create_block('oua_connections');
        $block = block_instance('oua_connections', $block);

        $html = $block->get_content()->text;
        $this->assertXpathDomQueryResultLengthEquals(1, "//div[contains(@id, 'myconnections')]//*[contains(@class,'connection-item')]", $html,  "Should have one connection item.\n\n$html\n\n");
        $this->assertValidHtml($html);
    }

    /**
     * Block should only display 4 connections
     * All users page displays all connections
     */
    function test_myconnections_limit() {
        global $PAGE;
        $this->resetAfterTest();
        // Set this user as the admin.
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();
        $user8 = $this->getDataGenerator()->create_user();
        $user9 = $this->getDataGenerator()->create_user();


        $this->setUser($user2);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user3);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user4);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user5);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user6);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user7);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user8);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user9);
        $success = \block_oua_connections\api::request_connection($user1->id);

        $this->setUser($user1);
        $success = \block_oua_connections\api::request_connection($user2->id);
        $success = \block_oua_connections\api::request_connection($user3->id);
        $success = \block_oua_connections\api::request_connection($user4->id);
        $success = \block_oua_connections\api::request_connection($user5->id);
        $success = \block_oua_connections\api::request_connection($user6->id);
        $success = \block_oua_connections\api::request_connection($user7->id);
        $success = \block_oua_connections\api::request_connection($user8->id);
        $success = \block_oua_connections\api::request_connection($user9->id);

        $block = $this->getDataGenerator()->create_block('oua_connections');
        $block = block_instance('oua_connections', $block);

        $html = $block->get_content()->text;
        $this->assertXpathDomQueryResultLengthEquals(4, "//div[contains(@id, 'myconnections')]//*[contains(@class,'connection-item')]", $html,  "Should have four my connection items.\n\n$html\n\n");
        $this->assertValidHtml($html);


        $myconnections = new \block_oua_connections\output\my_connections(0, 'firstname');

        $renderer = $PAGE->get_renderer('block_oua_connections');
        $allconnectionshtml =  $renderer->display_all_connections_page($myconnections->export_for_template($renderer));
        $this->assertXpathDomQueryResultLengthEquals(8, "//div[contains(@id, 'myconnections')]//*[contains(@class,'connection-item')]", $allconnectionshtml,  "Should have all (8) connection items.\n\n$allconnectionshtml\n\n");
        $this->assertValidHtml($allconnectionshtml);

    }


    function test_delete_connection() {
        global $PAGE;
        $this->resetAfterTest();
        // Set this user as the admin.
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();
        $user8 = $this->getDataGenerator()->create_user();
        $user9 = $this->getDataGenerator()->create_user();


        $this->setUser($user2);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user3);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user4);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user5);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user6);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user7);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user8);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user9);
        $success = \block_oua_connections\api::request_connection($user1->id);

        $this->setUser($user1);
        $success = \block_oua_connections\api::request_connection($user2->id);
        $success = \block_oua_connections\api::request_connection($user3->id);
        $success = \block_oua_connections\api::request_connection($user4->id);
        $success = \block_oua_connections\api::request_connection($user5->id);
        $success = \block_oua_connections\api::request_connection($user6->id);
        $success = \block_oua_connections\api::request_connection($user7->id);
        $success = \block_oua_connections\api::request_connection($user8->id);
        $success = \block_oua_connections\api::request_connection($user9->id);

        $myconnections = new \block_oua_connections\output\my_connections(0, 'firstname');

        $renderer = $PAGE->get_renderer('block_oua_connections');
        $allconnectionshtml =  $renderer->display_all_connections_page($myconnections->export_for_template($renderer));
        $this->assertXpathDomQueryResultLengthEquals(8, "//div[contains(@id, 'myconnections')]//*[contains(@class,'connection-item')]", $allconnectionshtml,  "Should have all (8) connection items.\n\n$allconnectionshtml\n\n");
        $this->assertValidHtml($allconnectionshtml);

        $this->setUser($user1);
        $allconnectionshtml = \block_oua_connections\api::delete_connection($user2->id);
        $allconnectionshtml = $allconnectionshtml['allmyconnections'];
        $this->assertXpathDomQueryResultLengthEquals(7, "//div[contains(@id, 'myconnections')]//*[contains(@class,'connection-item')]", $allconnectionshtml,  "Should have all (8) connection items.\n\n$allconnectionshtml\n\n");
        $this->assertValidHtml($allconnectionshtml);

    }
    /**
     * Connection request message sent
     * Accept connection request
     * Test Event connected event was captured (if social activity event is installed)
     * Test myconnections block displays with connections
     */
    function test_suggested_myconnections_active_when_has_connections() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user2);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $messages = $DB->get_records('message');
        $this->assertCount(1, $messages, 'There should be only 1 message (connection request)');

        $this->setUser($user1);
        $success = \block_oua_connections\api::accept_request_connection(key($messages), $user2->id);
        $messages = $DB->get_records('message');
        $this->assertCount(2, $messages, 'There should be 2 messages (you are now connected.. connection request has been deleted)');
        if(is_callable('\block_oua_social_activity\observer::save_connection_event')) {
            // This tests that the event we raised was monitored successfully by the social activity block
            $events = $DB->get_records('oua_social_activity_events');
            $this->assertCount(1, $events);
        }

        $block = $this->getDataGenerator()->create_block('oua_connections');
        $block = block_instance('oua_connections', $block);
        $html = $block->get_content()->text;

        $this->assertXpathDomQueryResultLengthEquals(1, "//div[contains(@id, 'myconnections') and contains(@class,'active')]", $html,  "My connections tab should be active when there are connections.\n\n$html\n\n");
        $this->assertXpathDomQueryResultLengthEquals(0, "//div[contains(@id, 'suggestedconnections') and contains(@class,'active')]", $html,  'Suggested connections tab should NOT be active when there are no connections.'. "\n\n$html\n\n");
    }
    /*
     * user notifications are deleted on ignore
     * user contacts are deleted on ignore
     */
    function test_user_ignore_connection_request() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user to add to the admin's contact list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user2);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $messages = $DB->get_records('message');
        $this->assertCount(1, $messages, 'There should be only 1 message (connection request)');
        $contacts = $DB->get_records('message_contacts');
        $this->assertCount(1, $contacts, 'There should be 1 contact after a connection request');

        $this->setUser($user1);
        $success = \block_oua_connections\api::ignore_request_connection(key($messages), $user2->id);
        $messages = $DB->get_records('message');
        $this->assertCount(0, $messages, 'There should be no messages as they should be removed on ignore');
        $messages = $DB->get_records('message_read', array('timeusertodeleted' => 0));

        $this->assertCount(0, $messages, 'There should be no read messages as they should be removed on ignore');
        $contacts = $DB->get_records('message_contacts');
        $this->assertCount(0, $contacts, 'There should be no contacts as they should be removed on ignore');


        $block = $this->getDataGenerator()->create_block('oua_connections');
        $block = block_instance('oua_connections', $block);
        $html = $block->get_content()->text;

    }
    function test_user_connection_is_accepted_when_two_users_request_each_other() {
        global $DB;
        $this->resetAfterTest();
        // Set this user as the admin.
        $this->setAdminUser();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        $this->setUser($user2);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $this->setUser($user1);
        $messages = $DB->get_records('message');
        $this->assertCount(1, $messages, 'There should be only 1 message (connection request)');

        $this->setUser($user1);
        $success = \block_oua_connections\api::request_connection($user2->id);

        $block = $this->getDataGenerator()->create_block('oua_connections');
        $block = block_instance('oua_connections', $block);

        $html = $block->get_content()->text;
        $this->assertXpathDomQueryResultLengthEquals(1, "//div[contains(@id, 'myconnections')]//*[contains(@class,'connection-item')]", $html,  "Should have one connection item.\n\n$html\n\n");
        $this->assertValidHtml($html);
    }
    function test_user_notifications_are_delete_on_accept() {
        $this->markTestIncomplete('Test development deferred');
    }
    function test_user_not_deleted_from_other_user_contact_when_ignored_but_blocked() {
        $this->markTestIncomplete('Test development deferred until blocked functionality added');
    }
    function test_suggested_connections_active_when_no_my_connections() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $block = $this->getDataGenerator()->create_block('oua_connections');
        $block = block_instance('oua_connections', $block);

        $html = $block->get_content()->text;
        $this->assertValidHtml($html, true);

        $this->assertXpathDomQueryResultLengthEquals(1, "//div[contains(@id, 'suggestedconnections') and contains(@class,'active')]", $html,  "Suggested connections tab should be active when there are no connections. \n\n$html\n\n");
        $this->assertXpathDomQueryResultLengthEquals(0, "//div[contains(@id, 'myconnections') and contains(@class,'active')]", $html,  "My connections tab should NOT be active when there are connections.\n\n$html\n\n");

    }
    function test_no_unblock_event_when_double_connection() {
        global $USER, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Create a user to add to the admin's contact list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $sink = $this->redirectEvents();
        $this->setUser($user2);
        $success = \block_oua_connections\api::request_connection($user1->id);
        $messages = $DB->get_records('message');
        $this->assertCount(1, $messages, 'There should be only 1 message (connection request)');
        $this->setUser($user1);
        // Capture fired events.


        $success = \block_oua_connections\api::request_connection($user2->id); // Connects using request connection.
        $success = \block_oua_connections\api::accept_request_connection(key($messages), $user2->id); // Connects again by accepting.
        $messages = $DB->get_records('message');
        $this->assertCount(2, $messages, 'There should be only 2 messages (you are now connected.. connection request has been deleted)');

        // Get events that have been fired.
        $events = $sink->get_events();
        $this->assertCount(5, $events, 'There should be 5 events - connection1, connection2, connected, deleted, viewed.');

        $sink->close();

        $this->assertInstanceOf('\block_oua_connections\event\contact_connected', $events[2]);
   }
}
