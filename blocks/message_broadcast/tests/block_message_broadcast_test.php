<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 *  Unit tests for message broadcast block
 *
 * @package    blocks
 * @subpackage message_broadcast
 */

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

class block_message_broadcast_testcase extends advanced_testcase {

    protected function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/blocks/message_broadcast/classes/form.php');
        require_once($CFG->dirroot . '/blocks/message_broadcast/classes/manage.php');
    }

    static function setAdminUser() {
        global $USER;
        parent::setAdminUser();
        // The logged in user needs email, country and city to do certain things
        $USER->email = 'ben.kelada@open.edu.au';
        $USER->country = 'AU';
        $USER->city = 'Melbourne';
    }

    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        $beforeblocks = $DB->count_records('block_instances');

        $generator = $this->getDataGenerator()->get_plugin_generator('block_message_broadcast');
        $this->assertInstanceOf('block_message_broadcast_generator', $generator);
        $this->assertEquals('message_broadcast', $generator->get_blockname());

        $generator->create_instance();
        $generator->create_instance();
        $generator->create_instance();
        $this->assertEquals($beforeblocks + 3, $DB->count_records('block_instances'));
    }

    /**
     * GIVEN We have created a sitewide broadcast message
     * WHEN we retrieve the message block output
     * THEN the message output will match the given format
     *
     * @test
     */
    public function test_get_message_html() {
        global $PAGE;

        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);

        $PAGE->set_url('/blocks/test');

        // Create a message to output
        $data = new stdClass();
        $data->width = 2;
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'This is its body.';
        $data->uid = 1;
        $data->courseids = array(0);
        // Now we add a message with that data.
        $now = time();
        $mangemessages = new \block_message_broadcast\manage();
        list($messageid, $messagecontexts) = $mangemessages->save_message($data, $now);

        $block = $this->getDataGenerator()->create_block('message_broadcast');
        $block = block_instance('message_broadcast', $block);

        $html = $block->get_content()->text;

        $title = get_string('dismissmessage', 'block_message_broadcast');
        $notice = get_string('notice', 'block_message_broadcast');
        $expected = <<<BLOCK
<div class="broadcastmessagewrapper clearfix">
        <div class="message-notification clearfix box-yellow-rounded" role="alert">
            <div class="wrapper">
                <i class="fa fa-bullhorn">$notice</i>
                <div class="messagetitle">This is a message.</div>
                <a class="dismiss"
                   href="http://www.example.com/moodle/blocks/message_broadcast/managemessages.php?dismissmessage=$messageid" title="$title"><i class="fa fa-times"></i></a>
                <span class="messageid hidden">$messageid</span>
                <div class="messagebody">This is its body.</div>
                <ul class="attachments-list"></ul>
            </div>
        </div>
</div>
BLOCK;

        $this->assertEquals($expected, $html, "The html output of the message should be in the format expected");
    }

    /**
     * GIVEN We have created a coursewide broadcast message
     * WHEN we retrieve the message block output from the correct
     * THEN the message output will match the given format
     * AND when we retrieve message output from another course, no message will be retrieved
     *
     * @test
     */
    public function test_get_message_coursewide_html() {
        global $PAGE;

        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);

        // Enable course completion.
        set_config('enablecompletion', 1);

        $category = self::getDataGenerator()->create_category(array('name' => 'Template'));
        $course1 = $this->getDataGenerator()->create_course(array('category' => $category->id));

        // Create a message to output
        $data = new stdClass();
        $data->width = 2;
        $data->headingtitle = 'This is a sitewide broadcast message';
        $data->messagebody = 'This is its body.';
        $data->uid = 1;
        $data->courseids = array(0);
        // Now we add a message with that data.
        $now = time();
        $mangemessages = new \block_message_broadcast\manage();
        list($messageid1, $messagecontexts) = $mangemessages->save_message($data, $now);

        $PAGE->set_url('/blocks/test');
        $PAGE->set_course($course1);

        // Create a message to output
        $data = new stdClass();
        $data->width = 2;
        $data->headingtitle = 'This is a message for course 1 only.';
        $data->messagebody = 'This is its body.';
        $data->uid = 1;
        $data->courseids = array($course1->id);
        // Now we add a message with that data.
        $mangemessages = new \block_message_broadcast\manage();
        list($messageid2, $messagecontexts) = $mangemessages->save_message($data, $now);

        $block = $this->getDataGenerator()->create_block('message_broadcast');
        $block = block_instance('message_broadcast', $block, $PAGE);

        $html = $block->get_content()->text;

        $title = get_string('dismissmessage', 'block_message_broadcast');
        $notice = get_string('notice', 'block_message_broadcast');
        $expected = <<<BLOCK
<div class="broadcastmessagewrapper clearfix">
        <div class="message-notification clearfix box-yellow-rounded" role="alert">
            <div class="wrapper">
                <i class="fa fa-bullhorn">$notice</i>
                <div class="messagetitle">This is a message for course 1 only.</div>
                <a class="dismiss"
                   href="http://www.example.com/moodle/blocks/message_broadcast/managemessages.php?dismissmessage=$messageid2" title="$title"><i class="fa fa-times"></i></a>
                <span class="messageid hidden">$messageid2</span>
                <div class="messagebody">This is its body.</div>
                <ul class="attachments-list"></ul>
            </div>
        </div>
        <div class="message-notification clearfix box-yellow-rounded" role="alert">
            <div class="wrapper">
                <i class="fa fa-bullhorn">$notice</i>
                <div class="messagetitle">This is a sitewide broadcast message</div>
                <a class="dismiss"
                   href="http://www.example.com/moodle/blocks/message_broadcast/managemessages.php?dismissmessage=$messageid1" title="$title"><i class="fa fa-times"></i></a>
                <span class="messageid hidden">$messageid1</span>
                <div class="messagebody">This is its body.</div>
                <ul class="attachments-list"></ul>
            </div>
        </div>
</div>
BLOCK;


        $this->assertEquals($expected, $html, "The html output of the message should be the messages for course 1");

        $course2 = $this->getDataGenerator()->create_course(array('category' => $category->id));
        $PAGE->set_context(context_course::instance($course2->id));
        $block2 = $this->getDataGenerator()->create_block('message_broadcast');
        $block2 = block_instance('message_broadcast', $block2, $PAGE);

        $html = $block2->get_content()->text;

        $expected = <<<BLOCK
<div class="broadcastmessagewrapper clearfix">
        <div class="message-notification clearfix box-yellow-rounded" role="alert">
            <div class="wrapper">
                <i class="fa fa-bullhorn">$notice</i>
                <div class="messagetitle">This is a sitewide broadcast message</div>
                <a class="dismiss"
                   href="http://www.example.com/moodle/blocks/message_broadcast/managemessages.php?dismissmessage=$messageid1" title="$title"><i class="fa fa-times"></i></a>
                <span class="messageid hidden">$messageid1</span>
                <div class="messagebody">This is its body.</div>
                <ul class="attachments-list"></ul>
            </div>
        </div>
</div>
BLOCK;

        $this->assertEquals($expected, $html, "The html output of the message should only contain the sitewide message (course 2 context)");
    }

    /**
     * GIVEN We have a system wide message
     * WHEN we mark the message as read
     * THEN the message should appear in the message_broadcast_read table
     *
     * @test
     */
    public function test_message_mark_read() {
        global $DB;

        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);

        $user = new stdClass();
        $user->id = 1;

        // Create a message to output
        $data = new stdClass();
        $data->width = 2;
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'This is its body.';
        $data->uid = 1;
        $data->courseids = array(0);

        // Now we add a message with that data.
        $now = time();
        $mangemessages = new \block_message_broadcast\manage();
        list($messageid, $messagecontexts) = $mangemessages->save_message($data);

        $message = $DB->get_record('message_broadcast', array());

        $mangemessages->mark_read($user, $message->id, $now + 10);

        $read = $DB->get_record('message_broadcast_read', array());

        $expected = new stdClass();
        $expected->id = $read->id;
        $expected->messagebroadcastid = $messageid;
        $expected->useridto = "1";
        $expected->timeread = "" . $now + 10 . "";

        $this->assertEquals($expected, $read, "The message should have been marked as read for the user.");
    }

    /**
     * GIVEN we have have a sitewide broadcast message
     * WHEN we mark it as read as a user
     * THEN it will not appear in the unread messages for the user
     *
     * @test
     */
    public function test_get_unread_messages() {
        global $DB;

        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);

        $user = new stdClass();
        $user->id = 1;

        // Create a message to output
        $data = new stdClass();
        $data->width = 2;
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'This is its body.';
        $data->uid = 1;
        $data->courseids = array(0);

        // Now we add a message with that data.
        $now = time();
        $mangemessages = new \block_message_broadcast\manage();
        $mangemessages->save_message($data, $now);

        $block = $this->getDataGenerator()->create_block('message_broadcast');
        $block = block_instance('message_broadcast', $block);

        $messages = $DB->get_records('message_broadcast', array());
        $unreadmessages = $mangemessages->get_unread_messages($user->id, $block->context->get_parent_context_ids(true));

        // There are no attachments, remove this attribute.
        foreach ($unreadmessages as &$unreadmessage) {
            $this->assertEmpty($unreadmessage->attachments, 'Should have no attachment');
            unset($unreadmessage->attachments);
        }

        $this->assertEquals($messages, $unreadmessages, "All messages should still exist as none have been read.");

        $message = array_shift($messages);
        $mangemessages->mark_read($user, $message->id);
        $unread = $mangemessages->get_unread_messages($user->id, $block->context->get_parent_context_ids(true));

        $expected = array();

        $this->assertEquals($expected, $unread, "There should be no unread messages.");
    }
}
