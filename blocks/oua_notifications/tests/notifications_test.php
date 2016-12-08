<?php

/**
 * PHPUnit data generator tests
 *
 * @package    block_oua_notifications
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
use block_oua_notifications\external as notification_external;
use block_oua_notifications\api as notification_api;

/**
 * PHPUnit data generator testcase
 *
 * @package    block_oua_notifications
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */
class block_oua_notifications_testcase extends oua_advanced_testcase {
    private $user1;
    private $user2;
    private $message1;
    private $message2;
    private $message3;

    /**
     * GIVEN there is user1.
     *  AND there is user2.
     *  AND there is systemuser.
     * WHEN user2 sends user1 an expiring notification.
     *  AND systemuser sends user1 a badgecreatornotice.
     *  AND systemuser sends user1 a request connection notice.
     * THEN user1 has 3 new notifications
     *
     * @throws coding_exception
     */
    function setUp() {
        $this->resetAfterTest(true);

        $this->user1 = self::getDataGenerator()->create_user();
        $this->user2 = self::getDataGenerator()->create_user();
        $userfrom = core_user::get_noreply_user();

        $course = $this->getDataGenerator()->create_course();
        $this->message1 = new stdClass();
        $this->message1->notification = 1;
        $this->message1->component = 'enrol_manual';
        $this->message1->name = 'expiry_notification';
        $this->message1->userfrom = $this->user2;
        $this->message1->userto = $this->user1;
        $this->message1->subject = 'Enrolment expired';
        $this->message1->fullmessage = 'Enrolment expired blah blah blah';
        $this->message1->fullmessageformat = FORMAT_MARKDOWN;
        $this->message1->fullmessagehtml = markdown_to_html($this->message1->fullmessage);
        $this->message1->smallmessage = $this->message1->subject;
        $this->message1->contexturlname = $course->fullname;
        $this->message1->contexturl = (string)new moodle_url('/course/view.php', array('id' => $course->id));
        message_send($this->message1);

        $this->message2 = new stdClass();
        $this->message2->component = 'moodle';
        $this->message2->name = 'badgecreatornotice';
        $this->message2->userfrom = $userfrom;
        $this->message2->userto = $this->user1;
        $this->message2->notification = 1;
        $this->message2->subject = 'New badge';
        $this->message2->fullmessage = format_text_email($this->message2->subject, FORMAT_HTML);
        $this->message2->fullmessageformat = FORMAT_PLAIN;
        $this->message2->fullmessagehtml = $this->message2->subject;
        $this->message2->smallmessage = $this->message2->subject;
        message_send($this->message2);

        $messagebodydata = new stdClass;
        $messagebodydata->userfrom = fullname($userfrom);
        $messagebodydata->userto = fullname($this->user1);

        $subject = 'connection request test';
        $plainbody = 'connection request plain body';
        $htmlbody = 'connection request html body';
        $smallbody = 'connection request small body';

        // Build data to create a message1 with notification tag.
        $this->message3 = new \core\message\message();
        $this->message3->component = 'block_oua_connections';
        $this->message3->name = 'acceptrequest';
        $this->message3->userfrom = $userfrom;
        $this->message3->userto = $this->user1;
        $this->message3->subject = $subject;
        $this->message3->fullmessagehtml = $plainbody;
        $this->message3->fullmessageformat = FORMAT_HTML;
        $this->message3->fullmessage = $htmlbody;
        $this->message3->smallmessage = $smallbody;
        $this->message3->notification = 1;

        message_send($this->message3);
    }

    function generate_block_contents($blockuser) {
        $this->setUser($blockuser);
        $block = $this->getDataGenerator()->create_block('oua_notifications');
        $block = block_instance('oua_notifications', $block);

        $html = $block->get_content()->text;

        return $html;
    }

    function test_unread_notifications_are_displayed_and_correct_order() {
        $html = $this->generate_block_contents($this->user1);
        $this->assertValidHtml($html, true);

        $expected = array($this->message3->subject, $this->message2->subject, $this->message1->subject);
        $this->assertXPathGetNodesWithClassesEquals($expected, 'subject', $html, 'Expected Dom Elements not found in correct order');
    }

    /*
     * GIVEN we are viewing the notification block
     *  WHEN we have two notifications that are not notification requests
     *  THEN dismiss all should be displayed
     */
    function test_dismiss_all_displayed_when_more_than_two_notifications() {
        // more than two notifications that are NOT connection requests
        // Dismiss all should be displayed
        $this->setUser($this->user1);
        $html = $this->generate_block_contents($this->user1);
        $this->assertValidHtml($html, true);

        $this->assertXpathDomQueryResultLengthEquals(1, "//a[contains(concat(' ', normalize-space(@class), ' ') , ' dismissall ')]",
                                                     $html, 'Expected dismiss all');
    }
    /*
     * GIVEN we are viewing the notification block with three notifications
     *  WHEN we delete one of the notificatinos
     *  THEN the block should regenerate with only 2 notifications
     */
    function test_notifications_are_dismissed() {
        $this->setUser($this->user1);
        $html = $this->generate_block_contents($this->user1);

        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $selector = new DOMXPath($doc);
        $notificationidquery = "//div[contains(concat(' ', normalize-space(@class), ' '), ' new-message ')]";
        $result = $selector->query($notificationidquery);

        foreach ($result as $key => $value) {
            $values[] = $value->getAttribute('data-notificationid');
        }
        $notificationidtodelete[] = array_pop($values);
        notification_external::delete_notifications($notificationidtodelete);

        $html = $this->generate_block_contents($this->user1);
        $this->assertXpathDomQueryResultLengthEquals(2, $notificationidquery, $html, 'Expected 2 new notifications remain after dismissed 1 notification');
    }

    function test_read_connection_requests_are_deleted_on_ignore() {
        $this->markTestIncomplete('Test development deferred');
    }


    function test_notifications_count_display_correctly()
    {
        $this->setUser($this->user1);

        $notificationscount = notification_api::get_cached_count_unread_notifications($this->user1);
        $this->assertEquals(3, $notificationscount, 'Expected 3 new notifications');

        $html = $this->generate_block_contents($this->user1);
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $selector = new DOMXPath($doc);

        $countinhtml = '//input[@id="notification_count"][@value="3"]';
        $result = $selector->query($countinhtml);
        $this->assertEquals(1, $result->length, 'Notifications count display of value 3');
    }

}
