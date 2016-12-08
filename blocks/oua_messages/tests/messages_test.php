<?php

/**
 * PHPUnit data generator tests
 *
 * @package    block_oua_messages
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit data generator testcase
 *
 * @package    block_oua_messages
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */
class block_oua_messages_testcase extends advanced_testcase {

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


    function test_read_messages()
    {
        global $DB, $PAGE;

        $this->resetAfterTest(true);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();

        // User 2 login.
        self::setUser($user2);

        // 2 messages sent to user2 from user1.
        $m1 = new stdClass();
        $m1->useridfrom = $user1->id;
        $m1->useridto = $user2->id;
        $m1->subject = 'Subject1';
        $m1->fullmessage = 'Message1';
        $m1->timeuserfromdeleted = 1;
        $m1->timeusertodeleted = 1;
        $m1->notification = 0;
        $m1->id = $DB->insert_record('message', $m1);

        $m2 = new stdClass();
        $m2->useridfrom = $user1->id;
        $m2->useridto = $user2->id;
        $m2->subject = 'Subject2';
        $m2->fullmessage = 'Message2';
        $m2->timeuserfromdeleted = 2;
        $m2->timeusertodeleted = 2;
        $m2->notification = 0;
        $m2->id = $DB->insert_record('message', $m2);

        // Messages seen by user2.
        $messagelist = new \block_oua_messages\output\renderable($user2->id);
        $renderer = $PAGE->get_renderer('block_oua_messages');
        $html = $renderer->render($messagelist);

        // There are 2 new messages.
        $needle = <<< DATACOUNT
<div class="panel-group accordion ouamsg" id="message_accordion" role="tablist" data-count="2">
DATACOUNT;
        $this->assertContains($needle, $html, "Expected output with data-count=2, ".$needle);

        // These 2 messages are new because they have the new-message class.
        $needle = <<< NEWMESSAGE
<div class="panel-heading new-message" role="tab" id="message_heading$m1->id" data-messageid="$m1->id">
NEWMESSAGE;
        $this->assertContains($needle, $html, "Expected output with new-message class, ". $needle);

        $needle = <<< NEWMESSAGE
<div class="panel-heading new-message" role="tab" id="message_heading$m2->id" data-messageid="$m2->id">
NEWMESSAGE;
        $this->assertContains($needle, $html, "Expected output with new-message class, ". $needle);

        // User2 read message 1, that is messageid = $m1->id
        // Before read, message with m1->id is present in table message
        $record = $DB->get_record('message', array('id'=>$m1->id));
        $this->assertEquals($record->useridfrom, $user1->id, "Expected useridfrom: ".$user1->id);
        $this->assertEquals($record->useridto, $user2->id, "Expected useridto: ".$user2->id);

        // User clicks on message 1 and read it.
        \block_oua_messages\api::mark_message_read($m1->id);

        // No message $m1 is found in message.
        $record = $DB->get_record('message', array('id'=>$m1->id));
        $this->assertFalse($record, "Expected no record of message 1 found");

        // Found message $m1 in message_read.
        $record = $DB->get_record('message_read', array('useridfrom' => $user1->id, 'useridto' => $user2->id));

        $this->assertEquals('Subject1', $record->subject, "Expected subject value 'Subject1'");
        $this->assertEquals('Message1', $record->fullmessage, "Expected message value 'Message1'");
        $this->assertGreaterThan(0, $record->timeread, "Expected time read is greater than 0");

        // User 2 hit refresh button.
        $messagelist = new \block_oua_messages\output\renderable($user2->id);
        $renderer = $PAGE->get_renderer('block_oua_messages');
        $html = $renderer->render($messagelist);

        // There is now 1 new message.
        $needle = <<< DATACOUNT
<div class="panel-group accordion ouamsg" id="message_accordion" role="tablist" data-count="1">
DATACOUNT;
        $this->assertContains($needle, $html, "Expected output with data-count=1, ".$needle);

        // And the new message is message 2.
        $needle = <<< NEWMESSAGE
<div class="panel-heading new-message" role="tab" id="message_heading$m2->id" data-messageid="$m2->id">
NEWMESSAGE;
        $this->assertContains($needle, $html, "Expected output with new-message class, ". $needle);
    }
}