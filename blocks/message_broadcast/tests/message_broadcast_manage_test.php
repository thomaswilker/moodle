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
 *  Unit tests for managing messages for the message broadcast block
 *
 * @package    blocks
 * @subpackage message_broadcast
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');

class block_message_broadcast_manage_testcase extends oua_advanced_testcase {

    protected function setUp() {
        global $CFG;

        require_once($CFG->dirroot . '/blocks/message_broadcast/classes/form.php');
        require_once($CFG->dirroot . '/blocks/message_broadcast/classes/manage.php');
    }

    static function setAdminUser() {
        global $USER;
        parent::setAdminUser();
        $USER->email = 'ben.kelada@open.edu.au';
        $USER->country = 'AU';
        $USER->city = 'Melbourne';
    }

    /**
     * GIVEN we have no messages
     * WHEN we create a new systemwide broadcast message
     * THEN it appears in the database as expected
     *  AND it appears in any given course context
     *
     * @test
     */
    public function test_message_create_systemwide() {
        global $DB;

        self::setAdminUser();
        $this->resetAfterTest(true);

        $mangemessages = new \block_message_broadcast\manage();
        // Now we add a message with that data.
        $now = time();
        $startdate = $now;
        $enddate = $now;
        $adjustedstartdate = make_timestamp(date('Y', $startdate), date('m', $startdate), date('d', $startdate), 0, 0, 0);
        $adjustedenddate = make_timestamp(date('Y', $enddate), date('m', $enddate), date('d', $enddate), 0, 0, 0);

        // Let's create fake submission data that the form might send.
        $data = new stdClass();
        $data->width = 1;
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.';
        $data->uid = 1;
        $data->courseids = array(0);
        $data->startdate = $startdate;
        $data->enddate = $enddate;

        list($message1id, $messagecontexts) = $mangemessages->save_message($data, $now);

        // Let's get our message.
        $message = $DB->get_record('message_broadcast', array());

        $expected = new stdClass();
        $expected->id = $message1id;
        $expected->width = '1';
        $expected->priority = '2';
        $expected->dismissible = '1';
        $expected->headingicon = '1';
        $expected->headingtitle = "This is a message.";
        $expected->messagebody = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.";
        $expected->targetinterface = '0';
        $expected->startdate = $adjustedstartdate;
        $expected->enddate = $adjustedenddate;
        $expected->timecreated = $now;
        $expected->lasteditdate = $now;
        $expected->userid = '1';

        $this->assertEquals($expected, $message, "The message entered into the database should match the values provided.");

        // Let's add a second one to be certain it works.
        $data = new stdClass();
        $data->width = 1; // This is either 1,2 or 3 for columns.
        $data->headingtitle = 'Test message the second.';
        $data->messagebody = 'Stuff goes here.';
        $data->uid = 1;
        $data->courseids = array(0);

        // Now we add a message with that data.
        $now = time() - 20;
        $startdate = $now;
        $enddate = $now + 1;

        $data->startdate = $startdate;
        $data->enddate = $enddate;

        list($message2id, $messagecontexts) = $mangemessages->save_message($data, $now);

        // Let's get our messages.
        $messages = $DB->get_records('message_broadcast', array());

        // Most of the values are the same, so let's save some time.
        $second = clone $expected;
        $second->id = $message2id;
        $second->width = '1';
        $second->headingtitle = 'Test message the second.';
        $second->messagebody = 'Stuff goes here.';
        $second->startdate = $adjustedstartdate;
        $second->enddate = $adjustedenddate;
        $second->timecreated = $now;
        $second->lasteditdate = $now;

        $expectedmessages = array($message1id => $expected, $message2id => $second);

        $this->assertEquals($expectedmessages, $messages, 'List of messages should be the same as have been created.');
    }

    /**
     * GIVEN we have messages in the systemwide and course contexts
     * WHEN A student who is not enrolled in a course
     *  AND is viewing messages from that course context (i.e. the self enrolment page)
     * THEN They should not see any broadcast messages.
     *
     * GIVEN the we have messages in the sytemwide and course contexts
     *  WHEN the student is enrolled in the course
     *  THEN they should see messages for that course and systemwide
     */
    public function test_messages_permission() {
        global $DB, $PAGE;
        $course1 = self::getDataGenerator()->create_course();
        $PAGE->set_url('/blocks/test');
        $PAGE->set_course($course1);

        $this->resetAfterTest(true);
        self::setAdminUser();
        $user1 = self::getDataGenerator()->create_user();

        $mangemessages = new \block_message_broadcast\manage();

        // Let's create fake submission data that the form might send.
        $data = new stdClass();
        $data->width = 1;
        $data->headingtitle = 'This is a sitewide message.';
        $data->messagebody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.';
        $data->uid = 1;
        $data->courseids = array(0);
        // Now we add a message with that data.
        $now = time();

        list($message1id, $messagecontexts) = $mangemessages->save_message($data, $now);

        $unreadmessages = $mangemessages->get_unread_messages($user1->id, array(context_system::instance()->id));
        $this->assertCount(1, $unreadmessages);

        // Let's create fake submission data that the form might send.
        $data = new stdClass();
        $data->width = 1;
        $data->headingtitle = 'This is a course message.';
        $data->messagebody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.';
        $data->uid = 1;
        $data->courseids = array($course1->id);
        // Now we add a message with that data.
        $now = time();
        list($message2id, $messagecontexts) = $mangemessages->save_message($data, $now);

        $this->setUser($user1);
        $allcontexts = context_course::instance($course1->id)->get_parent_context_ids(true);
        $unreadmessages = $mangemessages->get_unread_messages($user1->id, $allcontexts);
        $this->assertCount(2, $unreadmessages);

        $block = $this->getDataGenerator()->create_block('message_broadcast');
        $block = block_instance('message_broadcast', $block, $PAGE);
        $html = $block->get_content()->text;
        $this->assertEmpty($html);

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id, $studentrole->id);

        $block2 = $this->getDataGenerator()->create_block('message_broadcast');
        $block2 = block_instance('message_broadcast', $block2, $PAGE);

        $html2 = $block2->get_content()->text;

        $this->assertXPathGetNodesWithClassesCount(2, 'message-notification', $html2, 'Wrong number of messages');
    }

    /**
     * GIVEN we have no messages
     * WHEN we create a new coursewide broadcast message sent to two courses
     * THEN it appears in the database with the correct contextid
     *
     * @test
     */
    public function test_message_create_coursewide() {
        global $DB;
        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);

        // Enable course completion.
        set_config('enablecompletion', 1);

        $category = self::getDataGenerator()->create_category(array('name' => 'Template'));
        $course = $this->getDataGenerator()->create_course(array('category' => $category->id));
        $course2 = $this->getDataGenerator()->create_course(array('category' => $category->id));
        $coursecontext1 = context_course::instance($course->id);
        $coursecontext2 = context_course::instance($course2->id);
        $mangemessages = new \block_message_broadcast\manage();

        // Let's create fake submission data that the form might send.
        $data = new stdClass();
        $data->width = 1;
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.';
        $data->uid = 1;
        $data->courseids = array($course->id, $course2->id);
        // Now we add a message with that data.
        $now = time();

        list($message1id, $message1contexts) = $mangemessages->save_message($data, $now);

        // Let's get our message.
        $messagecontext = $DB->get_records('message_broadcast_context', array());

        $expected = new stdClass();
        $expected->id = $message1contexts[0];
        $expected->messagebroadcastid = $message1id;
        $expected->contextid = $coursecontext1->id;

        $expected2 = new stdClass();
        $expected2->id = $message1contexts[1];
        $expected2->messagebroadcastid = $message1id;
        $expected2->contextid = $coursecontext2->id;

        $expectedcontexts = array($message1contexts[0] => $expected, $message1contexts[1] => $expected2);

        $this->assertEquals($expectedcontexts, $messagecontext,
                            "The message context entered into the database should match the value of our test course");
    }

    /**
     * GIVEN we have two system wide broadcast messages
     * WHEN we retrieve the manage messages table html
     * THEN it matches the expected format for sitewide
     * AND it matches the expected format for course only messages
     *
     * @test
     */
    public function test_manage_message_table() {
        global $PAGE;
        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);
        // Enable course completion.
        set_config('enablecompletion', 1);

        $category = self::getDataGenerator()->create_category(array('name' => 'Template'));
        $course = $this->getDataGenerator()->create_course(array('category' => $category->id));
        $course2 = $this->getDataGenerator()->create_course(array('category' => $category->id));

        $mangemessages = new \block_message_broadcast\manage();
        $now = time();

        // Let's create some messages.
        $data = new stdClass();
        $data->width = 2; // This is either 1,2 or 3 for columns.
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.';
        $data->uid = 1;
        $data->startdate = $now - 30 * DAYSECS;
        $data->enddate = $now + 10 * DAYSECS;
        $data->courseids = array(0);

        // Build the dates that will appear in the output table.
        $stardate1 = userdate($data->startdate, get_string('messagedateformat', 'block_message_broadcast'));
        $enddate1 = userdate($data->enddate, get_string('messagedateformat', 'block_message_broadcast'));

        // Now we add a message with that data.

        list($message1id, $messagecontexts) = $mangemessages->save_message($data, $now);

        // Create second coursewide message.
        $data = new stdClass();
        $data->width = 1; // This is either 1,2 or 3 for columns.
        $data->headingtitle = 'Test message the second.';
        $data->messagebody = 'Stuff goes here.';
        $data->uid = 1;
        $data->startdate = $now - 20 * DAYSECS;
        $data->enddate = 0;
        $data->courseids = array(0);

        // Build the dates that will appear in the output table.
        $stardate2 = userdate($data->startdate, get_string('messagedateformat', 'block_message_broadcast'));
        // Use the generic string for no end date.
        $enddate2 = get_string('messagedatewhennodate', 'block_message_broadcast');

        // Now we add the second message with that data.
        list($message2id, $messagecontexts) = $mangemessages->save_message($data, $now + 300);

        // Third coursewide message
        $data = new stdClass();
        $data->width = 1;
        $data->headingtitle = 'This is a coursewide message.';
        $data->messagebody = 'This is only sent to my course.';
        $data->uid = 1;
        $data->startdate = $now - 10 * DAYSECS;
        $data->enddate = $now;
        $data->courseids = array($course->id, $course2->id);

        // Build the dates that will appear in the output table.
        $stardate3 = userdate($data->startdate, get_string('messagedateformat', 'block_message_broadcast'));
        $enddate3 = userdate($data->enddate, get_string('messagedateformat', 'block_message_broadcast'));

        // Now we add the coursewide message
        list($message3id, $messagecontexts) = $mangemessages->save_message($data, $now + 600);
        $messages = $mangemessages->get_messages(null);
        $output = $PAGE->get_renderer('block_message_broadcast');
        // Now we create the output table for our messages.
        $managemessagepage = new \block_message_broadcast\output\manage_messages_page();

        $table = $output->render($managemessagepage);

        $newmessage = get_string('newmessage', 'block_message_broadcast');
        $tablemsgtitle = get_string('tablemsgtitle', 'block_message_broadcast');
        $tablemsgcontext = get_string('tablemsgcontext', 'block_message_broadcast');

        $startdateheader = get_string('tablestartdate', 'block_message_broadcast');
        $endtdateheader = get_string('tableenddate', 'block_message_broadcast');

        $expected = <<<MULTI
<a class="button primary_btn" href="http://www.example.com/moodle/blocks/message_broadcast/newmessage.php">$newmessage</a>
<br/>
<br/>

<div><table class="generaltable">
    <thead>
    <tr>
        <th class="header c0" style="" scope="col">$tablemsgtitle</th>
        <th class="header c1" style="" scope="col">$startdateheader</th>
        <th class="header c1" style="" scope="col">$endtdateheader</th>
        <th class="header c2" style="" scope="col">$tablemsgcontext</th>
        <th class="header c3 lastcol" style="" scope="col">Actions</th>
    </tr>
    </thead>
    <tbody>
        <tr class="">
            <td class="cell c0" style="">Test message the second.</td>
            <td class="cell c1 text-center" style="">$stardate2</td>
            <td class="cell c1 text-center" style="">$enddate2</td>
            <td class="cell c2" style="">System Wide</td>
            <td class="cell c3 lastcol" style="">
                <a class="button" href="http://www.example.com/moodle/blocks/message_broadcast/editmessage.php?id=$message2id">Edit</a>  <a class="button" href="http://www.example.com/moodle/blocks/message_broadcast/deletemessage.php?id=$message2id">Delete</a>
            </td>
        </tr>
        <tr class="">
            <td class="cell c0" style="">This is a message.</td>
            <td class="cell c1 text-center" style="">$stardate1</td>
            <td class="cell c1 text-center" style="">$enddate1</td>
            <td class="cell c2" style="">System Wide</td>
            <td class="cell c3 lastcol" style="">
                <a class="button" href="http://www.example.com/moodle/blocks/message_broadcast/editmessage.php?id=$message1id">Edit</a>  <a class="button" href="http://www.example.com/moodle/blocks/message_broadcast/deletemessage.php?id=$message1id">Delete</a>
            </td>
        </tr>
        <tr class="">
            <td class="cell c0" style="">This is a coursewide message.</td>
            <td class="cell c1 text-center" style="">$stardate3</td>
            <td class="cell c1 text-center" style="">$enddate3</td>
            <td class="cell c2" style="">tc_1,tc_2</td>
            <td class="cell c3 lastcol" style="">
                <a class="button" href="http://www.example.com/moodle/blocks/message_broadcast/editmessage.php?id=$message3id">Edit</a>  <a class="button" href="http://www.example.com/moodle/blocks/message_broadcast/deletemessage.php?id=$message3id">Delete</a>
            </td>
        </tr>
    </tbody>
</table>
</div>
MULTI;
        $this->assertEquals($expected, $table, 'Table generated should match the expected output - it should display all messages');
    }

    /**
     * GIVEN we have a system wide broadcast message
     * WHEN we edit the message
     * THEN The text changes will be shown in the message in the database
     *  AND the lasteditdate will have changed
     *
     * @test
     */
    public function test_edit_message_submit() {
        global $DB;

        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);
        $mangemessages = new \block_message_broadcast\manage();

        // Let's create a message.
        $data = new stdClass();
        $data->width = 2; // This is either 1,2 or 3 for columns.
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.';
        $data->uid = 1;
        $data->courseids = array(0);

        // Now we add a message with that data.
        $now = time();
        $beforeedittime = make_timestamp(date('Y', $now), date('m', $now), date('d', $now), 0, 0, 0);;
        $mangemessages->save_message($data, $now);

        $message = $DB->get_record('message_broadcast', array());

        // Let's post data for an update to a message
        $data = new stdClass();
        $data->id = $message->id;
        $data->width = 1; // This is either 1,2 or 3 for columns.
        $data->headingtitle = 'This is a message that has been edited.';
        $data->messagebody = 'This is no longer generic latin words.';
        $data->uid = 1;
        $data->courseids = array(0);
        // Save message generated the startdate and enddate.
        $data->startdate = $message->startdate;
        // There wasn't any enddate, so enddate should be 0
        $data->enddate = $message->enddate;
        $this->assertEquals(0, $message->enddate);

        // Some time later we edit the message.
        $sometimelater = $now + 100;

        $mangemessages->edit_message($data, $sometimelater);

        $expected = $message;
        $expected->width = '1';
        $expected->headingtitle = 'This is a message that has been edited.';
        $expected->messagebody = 'This is no longer generic latin words.';
        $expected->userid = '1';
        $expected->lasteditdate = '' . $sometimelater . '';
        // There is no startdate, startdate is set to $beforeedittime.
        $expected->startdate = $beforeedittime;
        // enddate remains is still 0.
        $expected->enddate = '0';

        // Get the edited message
        $message = $DB->get_record('message_broadcast', array());

        $this->assertEquals($expected, $message, "Original message should have been updated to have new data.");
    }

    /**
     * GIVEN we have a system wide broadcast message
     * WHEN we delete the message
     * THEN the message will be removed from the database
     *
     * @test
     */
    public function test_message_delete() {
        global $DB;

        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);
        $mangemessages = new \block_message_broadcast\manage();

        // Let's create a message.
        $data = new stdClass();
        $data->width = 2; // This is either 1,2 or 3 for columns.
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.';
        $data->uid = 1;
        $data->courseids = array(0);

        // Now we add a message with that data.
        $now = time();
        $mangemessages->save_message($data, $now);
        $message = $DB->get_record('message_broadcast', array());
        $mangemessages->delete_message($message->id);
        $message = $DB->get_record('message_broadcast', array());
        $this->assertEquals(false, $message, "Message should have been deleted");
    }

    /**
     * This test cannot accommodate redirect, so simulate file upload and download.
     *
     * Given an announcement, test for file upload associated with the announcement message id.
     * Verify that a record of the attachment is in the files table.
     *
     * Given a file record of the attachment, verify that there is such a file in the storage area.
     * Verify that the attachment has the correct content as uploaded.
     *
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    public function test_upload_download_attachments() {
        global $DB, $CFG;

        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        // Get configuration from the announcement upload form.
        $context = \block_message_broadcast\form::get_context();
        $filearea = \block_message_broadcast\form::ATTACHMENTS_AREA;

        // Attachments are associated with the message_broadcast id.
        $fakemessagebroadcast = (object)array('id' => 123, 'message' => 'Yay!');

        // A dummy attachment for uploading.
        $fs = get_file_storage();
        $dummyattachment = (object)array('contextid' => $context->id, 'component' => 'block_message_broadcast',
                                         'filearea' => $filearea, 'itemid' => $fakemessagebroadcast->id, 'filepath' => '/',
                                         'filename' => 'myannouncement.txt');
        $uploadcontent = 'This is my file content';

        // Store the attachment and create a file record.
        $storedfile = $fs->create_file_from_string($dummyattachment, $uploadcontent);
        $fileid = $storedfile->get_id();

        // Check file record, file stored and a record is generated.
        $filerecord = $DB->get_record('files', array('id' => $fileid));
        $this->assertEquals($dummyattachment->filename, $filerecord->filename, 'File is now uploaded and stored');
        // And reference the correct announcement id.
        $this->assertEquals($fakemessagebroadcast->id, $filerecord->itemid, 'Attachment reference the announcement id');

        // Can't redirect, so simulate file downloading.
        // Given the file record, verify that the stored file is the uploaded file.
        $fs = get_file_storage();
        $file = $fs->get_file($context->id, $filerecord->component, $filerecord->filearea, $filerecord->itemid,
                              $filerecord->filepath, $filerecord->filename);
        $downloadcontent = $file->get_content();

        $this->assertSame($uploadcontent, $downloadcontent, 'File has the same content as uploaded and downloaded');
    }

    /**
     * Create a fake announcement message.
     *
     * @param $data data for message attributes
     * @return object message id and context
     */
    protected function make_fake_message(&$data, $time = null) {
        global $DB;

        // A dummy message.
        $data->width = 2; // This is either 1,2 or 3 for columns.
        $data->headingtitle = 'This is a message.';
        $data->messagebody = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam ornare.';
        // Hardwired the user id that creates the message.
        $data->uid = 1;
        $data->courseids = array(0);

        $mangemessages = new \block_message_broadcast\manage();

        // Create a new message to display.
        list($messageid) = $mangemessages->save_message($data, $time);

        // Message has context.
        $messagebroadcastcontext = $DB->get_record('message_broadcast_context', array('messagebroadcastid' => $messageid));

        return (object)array('context' => $messagebroadcastcontext, 'id' => $messageid);
    }

    /**
     * Scenario: nowtime within date range, show message.
     *
     * GIVEN nowtime, startdate, enddate
     * WHEN startdate < nowtime
     *  AND enddate > nowtime
     * THEN show
     */
    public function test_within_startdate_enddate_show_message() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        $now = time();
        $data = new stdClass();
        // Make start and end dates today.
        $data->startdate = $now;
        $data->enddate = $now;

        $message = $this->make_fake_message($data);

        // Unread messages available for display.
        $mangemessages = new \block_message_broadcast\manage();
        $messages = $mangemessages->get_unread_messages($user->id, array($message->context->contextid));
        $this->assertEquals(1, count($messages), 'We must display the message that is available for today only.');

        $unreadmessage = array_pop($messages);
        $this->assertEquals($message->id, $unreadmessage->id, 'And the message is available');
        $this->assertSame($data->messagebody, $unreadmessage->messagebody, 'And its the same message');
    }

    /**
     * Scenario: Always show when enddate is unset (value 0), test using nowtime into future.
     *
     * GIVEN nowtime, startdate, enddate
     * WHEN startdate < nowtime
     *  AND enddate = 0
     * THEN show
     */
    public function test_no_enddate_always_display_message() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        $now = time();
        $data = new stdClass();
        // startdate in the past.
        $data->startdate = $now;
        // always show regardless nowtime.
        $data->enddate = 0;

        $message = $this->make_fake_message($data);

        // Unread messages available for display.
        $mangemessages = new \block_message_broadcast\manage();
        $messages = $mangemessages->get_unread_messages($user->id, array($message->context->contextid));
        $this->assertEquals(1, count($messages));
        $unreadmessage = array_pop($messages);
        $this->assertEquals($message->id, $unreadmessage->id, 'And the message is available');

        // Unread messages available for display in future time.
        $nowtime = time() + 100;
        $messages = $mangemessages->get_unread_messages($user->id, array($message->context->contextid), $nowtime);
        $this->assertEquals(1, count($messages));
        $unreadmessage = array_pop($messages);

        $this->assertEquals($message->id, $unreadmessage->id, 'And the message is available in future time');
    }

    /**
     * Scenario: Expired when enddate is already past, do not show regardless of startdate.
     *
     * GIVEN nowtime, startdate, enddate
     * WHEN startdate < nowtime
     *  AND enddate < nowtime
     * THEN do not show
     */
    public function test_past_enddate_do_not_display_message() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        $now = time();
        $data = new stdClass();
        $data->startdate = $now;
        // enddate in the past, expired.
        // enddate is automatically extends to end of day so the past need to be greater than full day.
        $data->enddate = $now - DAYSECS - 100;

        $message = $this->make_fake_message($data);

        // There is no message for display since startdate = 0.
        $mangemessages = new \block_message_broadcast\manage();
        $messages = $mangemessages->get_unread_messages($user->id, array($message->context->contextid));
        $this->assertEquals(0, count($messages));
    }

    /**
     * Scenario: No start date, set startdate to nowtime.
     *
     * GIVEN nowtime, startdate, enddate
     * WHEN startdate = 0 (no startdate)
     * THEN set startdate as nowtime
     */
    public function test_no_startdate_display_message() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        $now = time();
        $data = new stdClass();
        $data->startdate = 0;
        // Don't really care.
        $data->enddate = null;

        $message = $this->make_fake_message($data);

        // startdate set to nowtime, enddate is now set to nowtime + 1 day.
        // Make sure readtime is well past the record insert startdate
        $mangemessages = new \block_message_broadcast\manage();
        $messages = $mangemessages->get_unread_messages($user->id, array($message->context->contextid));
        $this->assertEquals(1, count($messages));
        $unreadmessage = array_pop($messages);

        $startofday = make_timestamp(date('Y', $now), date('m', $now), date('d', $now), 0, 0, 0);
        $this->assertEquals($startofday, $unreadmessage->startdate, 'Without a specified start date, we must get the start of today.');
    }

    /**
     * Scenario: Start date in future, do not display regardless of enddate.
     *
     * GIVEN nowtime, startdate, enddate
     * WHEN startdate > nowtime
     *  AND enddate = 0 | enddate > nowtime | enddate < nowtime
     * THEN do not display message regardless of enddate
     */
    public function test_future_startdate_no_display_message() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user->id);

        $mangemessages = new \block_message_broadcast\manage();

        $now = time();
        $data = new stdClass();
        // Future startdate.
        $data->startdate = $now + DAYSECS;
        $data->enddate = 0;

        $message = $this->make_fake_message($data);

        // Make sure readtime is well past the record insert startdate
        $messages = $mangemessages->get_unread_messages($user->id, array($message->context->contextid));
        $this->assertEquals(0, count($messages), 'No message, startdate in future');
    }
}
