<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
use local_conversations\api;

class local_conversations_testcase extends oua_advanced_testcase {

    /**
     * Copied from messagelib_test.php (modified to add event and return full message record)
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
     * @return stdClass message record object including
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
        \core\event\message_sent::create_from_ids($userfrom->id, $userto->id, $record->id)->trigger();
        return $record;
    }

    public function test_cache_is_retrieved() {
        $this->resetAfterTest();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();

        // With one message, count shows 1, preview is first message.
        $time = time();
        $m1 = $this->send_fake_message($user2, $user1, "Message1", $time);
        $conversationpreviewcache = \local_conversations\api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(1, $conversationpreviewcache['unread_conversation_count'], 'Should see 1 conversation.');
        $this->assertCount(1, $conversationpreviewcache['unread_conversation_preview'], "Number of preview messages is 1");
        $this->assertEquals($m1->smallmessage, $conversationpreviewcache['unread_conversation_preview'][0]->lastmessagesnippet);

        // Two messages sent at the same time by same user, count should still be 1, preview should be second message.
        $m2 = $this->send_fake_message($user2, $user1, "Message2", $time);
        $conversationpreviewcache = \local_conversations\api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(1, $conversationpreviewcache['unread_conversation_count'], 'Should see still see 1 conversation.');
        $this->assertCount(1, $conversationpreviewcache['unread_conversation_preview'], "Number of preview messages is 1");
        $this->assertEquals($m2->smallmessage, $conversationpreviewcache['unread_conversation_preview'][0]->lastmessagesnippet);

        // Third message, different user, count should be 2, third message should be first.
        $m3 = $this->send_fake_message($user3, $user1, "Message3", ++$time);
        $conversationpreviewcache = \local_conversations\api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(2, $conversationpreviewcache['unread_conversation_count'], 'Should see 2 conversations.');
        $this->assertCount(2, $conversationpreviewcache['unread_conversation_preview'], "Number of preview messages should be 2");
        $this->assertEquals($m3->smallmessage, $conversationpreviewcache['unread_conversation_preview'][0]->lastmessagesnippet);

        // With greater than 5 messages, count should be proper conversation count, conversation preview should be limited to 5.
        $m4 = $this->send_fake_message($user4, $user1, "Message4", ++$time);
        $m5 = $this->send_fake_message($user5, $user1, "Message5", ++$time);
        $m6 = $this->send_fake_message($user6, $user1, "Message6", ++$time);
        $m7 = $this->send_fake_message($user7, $user1, "Message7", ++$time);
        $conversationpreviewcache = \local_conversations\api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(6, $conversationpreviewcache['unread_conversation_count'], 'Should see 6 conversations.');
        $this->assertCount(5, $conversationpreviewcache['unread_conversation_preview'],
                           "Number of preview messages should be only 5");
        $this->assertEquals($m7->smallmessage, $conversationpreviewcache['unread_conversation_preview'][0]->lastmessagesnippet);
        $this->assertEquals($m3->smallmessage, $conversationpreviewcache['unread_conversation_preview'][4]->lastmessagesnippet);

        $this->setUser($user1);

        \local_conversations\api::mark_messages_read_by_id(array($m7->id));
        $conversationpreviewcache = \local_conversations\api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(5, $conversationpreviewcache['unread_conversation_count'],
                            'Regenerate the cache and should still get 1 conversation.');

        \local_conversations\external::mark_messages_read_by_id(array($m1->id, $m2->id));
        $conversationpreviewcache = \local_conversations\api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(4, $conversationpreviewcache['unread_conversation_count'], 'Cache should see no conversation.');
        $allmessages = array($m1, $m2, $m3, $m4, $m5, $m6, $m7);
        foreach ($conversationpreviewcache['users_with_unread'] as $userid) { // Test users with unread return.
            foreach ($allmessages as $msg) {
                if ($msg->useridfrom == $userid) {
                    \local_conversations\external::mark_messages_read_by_id(array($msg->id));
                }
            }
        }
        // This also tests cache clearing observer.
        $conversationpreviewcache = \local_conversations\api::get_cached_unread_conversation_preview($user1);
        $this->assertEquals(0, $conversationpreviewcache['unread_conversation_count'], 'Cache should see no conversation.');
    }

    function test_unread_messages_are_displayed_and_correct_order() {
        $this->markTestIncomplete('Test development deferred');
    }

    function test_unread_messages_are_dismissed() {
        $this->markTestIncomplete('Test development deferred');
    }

    function test_read_messages_do_not_display() {
        $this->markTestIncomplete('Test development deferred');
    }

    function test_read_messages_are_deleted() {
        $this->markTestIncomplete('Test development deferred');
    }

    function test_reply_to_message() {
        $this->markTestIncomplete('Test development deferred');
    }

    function test_delete_message() {
        $this->markTestIncomplete('Test development deferred');
    }

    function test_read_messages() {
        global $DB, $PAGE;
        $this->markTestIncomplete('Read/unread no longer uses renderable or correct api needs fixing.');
        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // User 2 login.
        self::setUser($user2);

        // 2 messages sent to user2 from user1.
        $m1 = $this->send_fake_message($user1, $user2, "Message1");
        $m2 = $this->send_fake_message($user1, $user2, "Message2");

        // Messages seen by user2.
        $messagelist = new \local_conversations\output\renderable($user2->id);
        $renderer = $PAGE->get_renderer('local_conversations');
        $html = $renderer->render($messagelist);

        // There are 2 new messages.
        $needle = <<< DATACOUNT
<div class="panel-group accordion ouamsg" id="message_accordion" role="tablist" data-count="2">
DATACOUNT;
        $this->assertContains($needle, $html, "Expected output with data-count=2, " . $needle);

        // These 2 messages are new because they have the new-message class.
        $needle = <<< NEWMESSAGE
<div class="panel-heading new-message" role="tab" id="message_heading$m1->id" data-messageid="$m1->id">
NEWMESSAGE;
        $this->assertContains($needle, $html, "Expected output with new-message class, " . $needle);

        $needle = <<< NEWMESSAGE
<div class="panel-heading new-message" role="tab" id="message_heading$m2->id" data-messageid="$m2->id">
NEWMESSAGE;
        $this->assertContains($needle, $html, "Expected output with new-message class, " . $needle);

        // User2 read message 1, that is messageid = $m1->id
        // Before read, message with m1->id is present in table message
        $record = $DB->get_record('message', array('id' => $m1->id));
        $this->assertEquals($record->useridfrom, $user1->id, "Expected useridfrom: " . $user1->id);
        $this->assertEquals($record->useridto, $user2->id, "Expected useridto: " . $user2->id);

        // User clicks on message 1 and read it.
        \local_conversations\api::mark_messages_read_by_id(array($m1->id));

        // No message $m1 is found in message.
        $record = $DB->get_record('message', array('id' => $m1->id));
        $this->assertFalse($record, "Expected no record of message 1 found");

        // Found message $m1 in message_read.
        $record = $DB->get_record('message_read', array('useridfrom' => $user1->id, 'useridto' => $user2->id));

        $this->assertEquals('Message1', $record->fullmessage, "Expected message value 'Message1'");
        $this->assertGreaterThan(0, $record->timeread, "Expected time read is greater than 0");

        // User 2 hit refresh button.
        $messagelist = new \local_conversations\output\renderable($user2->id);
        $renderer = $PAGE->get_renderer('local_conversations');
        $html = $renderer->render($messagelist);

        // There is now 1 new message.
        $needle = <<< DATACOUNT
<div class="panel-group accordion ouamsg" id="message_accordion" role="tablist" data-count="1">
DATACOUNT;
        $this->assertContains($needle, $html, "Expected output with data-count=1, " . $needle);

        // And the new message is message 2.
        $needle = <<< NEWMESSAGE
<div class="panel-heading new-message" role="tab" id="message_heading$m2->id" data-messageid="$m2->id">
NEWMESSAGE;
        $this->assertContains($needle, $html, "Expected output with new-message class, " . $needle);
    }

    /**
     * GIVEN user1 sends a message to user2
     *  AND user1 sends another message to user2
     *  AND user3 sends a message to user2
     *  AND user4 sends a notification to user2
     * THEN the header panel counter show only 2 conversation for user2, from user1 and user3.
     */
    public function test_count_conversations() {
        global $DB, $PAGE, $OUTPUT, $SESSION, $CFG;

        $this->resetAfterTest(true);

        require_once($CFG->dirroot . '/theme/bootstrap/renderers.php');
        $CFG->theme = 'ouaclean';

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        // User 2 login.
        self::setUser($user2);

        $contextcourse = context_course::instance(SITEID);
        $doctype = $OUTPUT->doctype();
        $layout = new \theme_ouaclean\output\layout\dashboard_layout($contextcourse, $doctype);
        $PAGE->set_url('/');

        // Ensure that global messaging is enabled.
        $CFG->messaging = 1;

        $renderer = $PAGE->get_renderer('theme_ouaclean', 'core', RENDERER_TARGET_GENERAL);

        // 2 messages sent to user2 from user1.
        $m1 = $this->send_fake_message($user1, $user2, "Message1");
        // Trigger event for sending a message - we need to do this before marking as read!

        $m2 = $this->send_fake_message($user1, $user2, "Message2");

        // User 3 sends a message to user2
        $m3 = $this->send_fake_message($user3, $user2, "Message3");

        // User 4 sends a notification to user2, and should not be counted as a conversation.
        $m4 = new stdClass();
        $m4->useridfrom = $user4->id;
        $m4->useridto = $user2->id;
        $m4->subject = 'Subject2';
        $m4->fullmessage = 'Message2';
        $m4->timeuserfromdeleted = 2;
        $m4->timeusertodeleted = 2;
        $m4->notification = 1;
        $m4->id = $DB->insert_record('message', $m4);

        // There are 3 unread messages, 1 notification, but only 2 conversations.
        $data = $layout->export_for_template($renderer);
        $this->assertValidHtml($data->pagelayout);
        $this->assertEquals(2, $data->unread_conversation_count, 'Expected 2 conversations for user 2');

        // And this count of unread conversations is displayed on the header panel.
        $expected = '<span class="total-count badge">2</span>';
        $this->assertContains($expected, $data->pagelayout,
                              'Expected badge display of 2 conversations, expect html string of ' . $expected);
    }

    public function test_conversations_not_display_when_config_messaging_disable() {
        global $DB, $PAGE, $OUTPUT, $SESSION, $CFG;

        $this->resetAfterTest(true);

        require_once($CFG->dirroot . '/theme/bootstrap/renderers.php');
        $CFG->theme = 'ouaclean';

        $contextcourse = context_course::instance(SITEID);
        $doctype = $OUTPUT->doctype();
        $layout = new \theme_ouaclean\output\layout\dashboard_layout($contextcourse, $doctype);
        $PAGE->set_url('/');

        // But messaging configuration is disabled, don't show anything.
        $CFG->messaging = 0;
        $renderer = $PAGE->get_renderer('theme_ouaclean', 'core', RENDERER_TARGET_GENERAL);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // User 2 login.
        self::setUser($user2);

        $m1 = $this->send_fake_message($user1, $user2, "Message1");

        // Value of unread_conversations_count is not set for user2.
        $data = $layout->export_for_template($renderer);
        $this->assertFalse(isset($data->unread_conversation_count), 'Expected no count value set for conversations');

        // And this count of unread conversations is NOT displayed on the header panel.
        $expected = '<span class="badge">2</span><i class="fa fa-envelope-o">';
        $this->assertNotContains($expected, $data->pagelayout, 'Expected no badge display of html string ' . $expected);
    }

    /**
     *
     * GIVEN user1 sends 2 messages to user2
     *  AND user3 sends 1 message to user2
     *  AND user2 replies to user3
     *
     * WHEN user2 logs in
     *  AND user2 deletes conversation with user1
     *  THEN all messages in conversation with user1 are deleted
     *
     * WHEN user1 logs in
     *  THEN all messages with user2 are available
     *
     * WHEN user3 logs in
     *  AND user3 deletes all messages in conversation with user2
     *  THEN all messages in conversation with user2 are deleted
     *
     * WHEN user2 logs in
     *  THEN all messages in conversation with user3 are still available
     *
     */
    public function test_conversation_delete() {
        global $DB;
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        // Time is used for sorting, no need to consider correct time sequence.
        $time = time();
        $m1 = $this->send_fake_message($user1, $user2, "Message1 u1->u2", $time);
        $m2 = $this->send_fake_message($user1, $user2, "Message2 u1->u2", $time);
        $m3 = $this->send_fake_message($user3, $user2, "Message3 u3->u2", $time);
        $m4 = $this->send_fake_message($user2, $user3, "Message4 u2->u3", $time);
        $m5 = $this->send_fake_message($user2, $user1, "Message5 u1->u2", $time);
        $m6 = $this->send_fake_message($user2, $user4, "Message6 u2->u4", $time);
        $m7 = $this->send_fake_message($user4, $user2, "Message7 u4->u2", $time);

        $n1 = $this->send_fake_message($user1, $user2, "Notification 1 u1->u2", $time);
        $n2 = $this->send_fake_message($user1, $user2, "Notification 2 u1->u2", $time);
        $n2->id = message_mark_message_read($n2, $time);

        // Create a notification from user 1 to user 2.
        $updatenotification = new stdClass();
        $updatenotification->id = $n1->id;
        $updatenotification->notification = 1;
        $DB->update_record('message', $updatenotification);

        $updatenotification->id = $n2->id;
        $updatenotification->notification = 1;
        $DB->update_record('message_read', $updatenotification);

        // user2 logs in.
        self::setUser($user2);

        // user2 sees 3 messages ( 2 received/1 sent to user 1)
        $messages = api::get_user_conversations($user2->id, $user1->id);
        $this->assertEquals(3, count($messages), 'user2 sees 2 messages from user1');

        // User 2 marks messages as read received from user 1
        message_mark_message_read($m1, $time);
        message_mark_message_read($m2, $time);

        $u2u4messages = api::get_user_conversations($user2->id, $user4->id);
        $this->assertEquals(2, count($u2u4messages), 'Mark messages read had no impact on user 3 conversation');

        // And delete all messages in user1 conversation.
        api::delete_conversation($user2->id, $user1->id);
        $u2u1messages = api::get_user_conversations($user2->id, $user1->id);
        $this->assertEquals(0, count($u2u1messages), 'There is no conversation for user2 to/from user1');

        $u2u4messages = api::get_user_conversations($user2->id, $user4->id);
        $this->assertEquals(2, count($u2u4messages), 'Mark messages read had no impact on user 3 conversation');

        api::delete_conversation($user2->id, $user4->id);
        $u2u1messages = api::get_user_conversations($user2->id, $user4->id);
        $this->assertEquals(1, count($u2u1messages), 'Delete had no impact on user2 -> user 3 messages as they were not read');

        // User 2 marks messages as read received from user 4
        message_mark_message_read($m7, $time);
        api::delete_conversation($user2->id, $user4->id);

        $u2u4messages = api::get_user_conversations($user2->id, $user4->id);
        $this->assertEquals(0, count($u2u4messages), 'Mark messages read had no impact on user 3 conversation');

        // user1 logs in.
        self::setUser($user1);

        // user1 should still see 2 messages sent to user2. These messages are marked as read by user1 when user1 sent messages.
        $messages = api::get_user_conversations($user1->id, $user2->id);
        $this->assertEquals(3, count($messages),
                            'user1 logs in and still see 2 messages sent to user2 after user 2 deleted the conversation');

        // user3 logs in.
        self::setUser($user3);
        message_mark_message_read($m3, $time);
        // user 3 sees 2 messages, one to user2 and 1 from user2.
        $messages = api::get_user_conversations($user3->id, $user2->id);
        $this->assertEquals(2, count($messages), 'user3 sees 2 messages, 1 sent to user2 and 1 replied from user2');

        // Verify that these messages are to/from user2 and user3.
        // And 1 read because user3 sent it, and 1 is unread because user3 received it.
        $assertcount = 0;
        foreach ($messages as $message) {
            // This message is from user3 to user2, message is mark as read because user3 sent it.
            if ($message->smallmessage == $m3->smallmessage) {
                $this->assertEquals($user3->id, $message->useridfrom, 'user3 sends Message3');
                $this->assertEquals($user2->id, $message->useridto, 'user2 receives Message3');
                $this->assertEquals(0, $message->unread, 'user3 sent message and it has a status of unread = 0');
                $assertcount += 3;
                // This message is the reply of user2 to user3, message is mark as unread because user3 hasn't seen it.
            } elseif ($message->smallmessage == $m4->smallmessage) {
                $this->assertEquals($user2->id, $message->useridfrom, 'user2 sends Message4 as a reply');
                $this->assertEquals($user3->id, $message->useridto, 'user3 receives Message4');
                $this->assertEquals(1, $message->unread, 'user3 received a message and it has a status of unread = 1');
                $assertcount += 3;
            }
        }
        $this->assertEquals(6, $assertcount, '6 asserted tested to verify Message3|4 to/from user2 and user3');

        // Unread message does not get deleted, user3 has not seen the message.
        api::delete_conversation($user3->id, $user2->id);
        $messages = api::get_user_conversations($user3->id, $user2->id);
        $this->assertEquals(1, count($messages));

        // user3 now read the message.
        message_mark_message_read($m4, $time);

        api::delete_conversation($user3->id, $user2->id);
        $messages = api::get_user_conversations($user3->id, $user2->id);

        // All messages are now deleted.
        $this->assertEquals(0, count($messages));

        // Ensure notification hasnt been deleted or marked as read.
        $notification = $DB->get_records('message', array('id' => $n1->id));
        $this->assertCount(1, $notification);
        $this->assertEquals(0, $notification[$n1->id]->timeusertodeleted);
        // Ensure notification hasnt been deleted
        $notification = $DB->get_records('message_read', array('id' => $n2->id));
        $this->assertCount(1, $notification);
        $this->assertEquals(0, $notification[$n2->id]->timeusertodeleted);
    }

}
