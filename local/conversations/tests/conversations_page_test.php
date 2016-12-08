<?php
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
use local_conversations\api;
use local_conversations\external;

class local_conversations_page_testcase extends oua_advanced_testcase {
    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Copied from messagelib_test.php
     *
     * Send a fake message.
     *
     * {@link message_send()} does not support transaction, this function will simulate a message
     * sent from a user to another. We should stop using it once {@link message_send()} will support
     * transactions. This is not clean at all, this is just used to add rows to the table.
     *
     * @param stdClass $userfrom user object of the one sending the message.
     * @param stdClass $userto user object of the one receiving the message.
     * @param string $message message to send.
     * @return int the id of the message
     */
    protected function send_fake_message($userfrom, $userto, $message = 'Hello world!', $time = 0) {
        global $DB;

        $record = new stdClass();
        $record->useridfrom = $userfrom->id;
        $record->useridto = $userto->id;
        $record->subject = 'No subject';
        $record->fullmessage = $message;
        $record->smallmessage = $message;
        if ($time == 0) {
            $time = time();
        }
        $record->timecreated = $time;

        $record->id = $DB->insert_record('message', $record);
        \core\event\message_sent::create_from_ids($userfrom->id, $userto->id, $record->id )->trigger();
        return $record;
    }


    /**
     * The Message contact list should be sorted with the most recent conversation at the top
     * The most recent message in the conversation is at the bottom.
     *
     * @throws coding_exception
     */
    function test_unread_messages_are_displayed_and_correct_order() {
        global $PAGE;
        // Set this user as the admin.
        $this->setAdminUser();
        $timenow = time();
        $onehour = 60 * 60;
        $oneday = $onehour *  24;

        // Create a user to add to the admin's contact list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        message_add_contact($user2->id);
        message_add_contact($user3->id);
        message_add_contact($user4->id);
        $timenow = $time1 = $timenow + $onehour;
        $m1 = $this->send_fake_message($user1, $user2, "1 hi to $user2->username from $user1->username", $time1);
        $timenow = $time2 = $timenow + $onehour;
        $m2 = $this->send_fake_message($user1, $user3, "2 hi to $user3->username  from $user1->username ", $time2);

        $this->setUser($user2);
        message_add_contact($user1->id);
        message_add_contact($user3->id);
        $timenow = $time3 = $timenow + $oneday;

        // Never message user 1.
        $timenow = $time4 = $timenow + $oneday;
        $m3 = $this->send_fake_message($user2, $user3, "3 hi to $user3->username  from $user2->username ", $time4);

        $this->setUser($user3);
        message_add_contact($user1->id);
        message_add_contact($user2->id);
        $timenow = $time5 = $timenow + $oneday;
        $m4 = $this->send_fake_message($user3, $user1, "4 hi to $user1->username  from $user3->username ", $time5);
        $timenow = $time6 = $timenow + $oneday;
        $m5 = $this->send_fake_message($user3, $user2, "5 hi to $user2->username  from $user3->username ", $time6);

        $this->setUser($user4);
        message_add_contact($user1->id);
        message_add_contact($user2->id);
        $timenow = $time7 = $timenow + $oneday;
        $m6 = $this->send_fake_message($user4, $user1, "6 hi to $user1->username  from $user4->username ", $time7);
        $timenow = $time8 = $timenow + $oneday;
        $m7 = $this->send_fake_message($user4, $user2, "7 hi to $user2->username  from $user4->username ", $time8);

        $timenow = $time9 = $timenow + $oneday;
        $m8 = $this->send_fake_message($user1, $user4, "8 hi reply 1 to $user4->username  from $user1->username ", $time9);
        $timenow = $time10 = $timenow + $oneday;
        $m9 = $this->send_fake_message($user4, $user1, "9 hi reply 2 to $user1->username  from $user4->username ", $time10);
        $timenow = $time11 = $timenow + $oneday;
        $m10 = $this->send_fake_message($user1, $user4, "10 hi reply 3 to $user4->username  from $user1->username ", $time11);

        $timenow = $time12 = $timenow + $oneday;
        $lastmessage = "11 hi again to $user1->username  from $user3->username ";
        $m11 = $this->send_fake_message($user3, $user1, $lastmessage, $time12);

        $this->setUser($user1);
        $mymessagespage = new \local_conversations\output\my_messages();
        $renderer = $PAGE->get_renderer('local_conversations');
        $html = $renderer->render_my_messages($mymessagespage);

        // Test latest user to send a message is up the top
        // Test latest message from that user is on the bottom
        // Test number of messages
        $this->assertValidHtml($html);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' .$html);
        $selector = new DOMXPath($doc);


        $contactnodelist = $selector->query("//li[contains(concat(' ', normalize-space(@class), ' ') , ' message-contact ')]");
        $this->assertEquals(3, $contactnodelist->length, 'There should be 3 contacts in message contact list');
        $contacthtml = $doc->saveHTML($contactnodelist->item(0));

        $this->assertcontains($user3->firstname, $contacthtml, "First contact should be user3");
        $this->assertcontains($lastmessage, $contacthtml, "message snippet should be last message sent.");

        $parentnodelist = $selector->query("//div[contains(concat(' ', normalize-space(@class), ' ') , ' panel ')]");
        $panelheading = array();
        $result = $selector->query("descendant::*[contains(concat(' ', normalize-space(@class), ' ') , ' panel-heading ')]", $parentnodelist->item(2));
        foreach ($result as $node) {
            $panelheading[] = $doc->saveHTML($node);
        }
        $this->assertContains(fullname($user2), $panelheading[0], 'User 1 should see users 2s name in conversation panel, even though they havent received a message (only sent one)');

        // Get the most recent conversation (it should be between user 1 and user 3)
        $result = $selector->query("descendant::*[contains(concat(' ', normalize-space(@class), ' ') , ' message-time ')]", $parentnodelist->item(0));
        $messagetime = array();
        foreach ($result as $node) {
            $messagetime[] = $doc->saveHTML($node);
        }
        $this->assertCount(3, $messagetime, 'There should be 3 total messages between user 1 and user 3');
        $strftimerecent = get_string('strftimedaydatetime');
        $this->assertContains( userdate($time12, $strftimerecent), $messagetime[2], 'Most recent message should be last');

        $this->setUser($user1);
        $messagepreviewcache = api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(2, $messagepreviewcache['unread_conversation_count'], "There should be 2 unread conversations to user 1");

        // Mark all messages as unread from user 4.
        external::mark_messages_read_by_id(array($m6->id, $m9->id));
        $messagepreviewcache = api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(1, $messagepreviewcache['unread_conversation_count'], "There should be 1  unread message left to user 1");

    }

    function test_contact_search_correct_results() {

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $this->setUser($user1);
        message_add_contact($user2->id);

        $this->setUser($user2);
        message_add_contact($user1->id); // User 1 and user 2 are now "connected" user 1 should be returned from user1's list

        $searchresult = api::search_connected_and_messaged_users($user2->id);
        $this->assertCount(1, $searchresult);
        $this->assertArrayHasKey($user1->id, $searchresult);

        $searchresult = api::search_connected_and_messaged_users($user2->id, "xxx");
        $this->assertCount(0, $searchresult);

        $searchresult = api::search_connected_and_messaged_users($user2->id, $user1->firstname);
        $this->assertCount(1, $searchresult);
        $this->assertArrayHasKey($user1->id, $searchresult);


        // user2 receives a message from user 3, user 3 appears in results even though we are not "connected"
        $timenow = time();
        $m1 = $this->send_fake_message($user3, $user2, "hi to $user2->username  from $user3->username ", ++$timenow);

        $searchresult = api::search_connected_and_messaged_users($user2->id);
        $this->assertCount(2, $searchresult);
        $this->assertArrayHasKey($user3->id, $searchresult);

        // Current requirements state that if i have sent a message to a user but am not "connected" they wont appear in contact search
        $m2 =$this->send_fake_message($user2, $user4, "hi to $user4->username  from $user2->username ", ++$timenow);
        $searchresult = api::search_connected_and_messaged_users($user2->id);
        $this->assertCount(2, $searchresult);
        $this->assertArrayNotHasKey($user4->id, $searchresult);

        $m3 = $this->send_fake_message($user1, $user2, "hi to $user2->username  from $user1->username ", ++$timenow);

        $searchresult = api::search_connected_and_messaged_users($user2->id);
        $this->assertCount(2, $searchresult, "user 1 should appear in result only once");
        
        // User3 is not a contact, when we have deleted their received conversation, they should not appear in search.
        $this->setUser($user2);
        $searchresult = api::search_connected_and_messaged_users($user2->id);
        $this->assertCount(2, $searchresult);
        $this->assertArrayHasKey($user3->id, $searchresult);

        api::mark_messages_read_by_id($m1->id);
        api::delete_conversation($user2->id, $user3->id);
        $searchresult = api::search_connected_and_messaged_users($user2->id);
        $this->assertCount(1, $searchresult, 'User3 is not a contact, when we have deleted their received conversation, they should not appear in search.');


    }
}
