<?php

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
use local_conversations\api;

class local_notifications_testcase extends oua_advanced_testcase
{
    protected function send_fake_notification($userfrom, $userto, $message = 'Notification', $time = 0)
    {
        global $DB;

        $record = new stdClass();
        $record->useridfrom = $userfrom->id;
        $record->useridto = $userto->id;
        $record->subject = 'Fake notification subject';
        $record->fullmessagehtml = $message;
        $record->fullmessage = $message;
        $record->smallmessage = $message;
        $record->notification = 1;
        if ($time == 0) {
            $time = time();
        }
        $record->timecreated = $time;

        $record->id = $DB->insert_record('message', $record);
        \core\event\message_sent::create_from_ids($userfrom->id, $userto->id, $record->id )->trigger();
        return $record;
    }


    /**
     * Test for notifications count, ordering, read event, triggered event, and cache reload.
     */
    public function test_notification_correct_cache_generated_and_retrieved()
    {
        $this->resetAfterTest(true);

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $userfrom = core_user::get_noreply_user();
        $user4 = self::getDataGenerator()->create_user();
        $user5 = self::getDataGenerator()->create_user();
        $user6 = self::getDataGenerator()->create_user();

        // send_fake_notification($userfrom, $userto, $message = 'Notification', $time = 0)
        $n1 = $this->send_fake_notification($user2, $user1, 'Notification1');
        $n2 = $this->send_fake_notification($userfrom, $user1, 'Notification2');
        $n3 = $this->send_fake_notification($userfrom, $user1, 'Notification3');
        $n4 = $this->send_fake_notification($user4, $user1, 'Notification4');
        $n5 = $this->send_fake_notification($user5, $user1, 'Notification5');
        $n6 = $this->send_fake_notification($user6, $user1, 'Notification6');

        // User1 logs in.
        self::setUser($user1);

        $cachestore = cache::make('local_conversations', 'unreadmessages');
        $cachekey = $user1->id . '_unreadnotificationspreview';
        $cache = $cachestore->get($cachekey);

        // There is no cache.
        $this->assertTrue($cache === false, 'There is no cache');

        // Cache total count of 6 notifications.
        $notificationscached = api::get_cached_unread_notification_preview($user1);
        $totalcount = $notificationscached['unread_notification_count'];
        $this->assertEquals(6, $totalcount, 'There are 6 total notifications for user1');

        $notificationalerts = $notificationscached['unread_notification_preview'];

        // And there are 5 (arbitrary) preview messages for notifications regardless of total count of notifications.
        $notificationpreviewnumber = api::NOTIFICATIONS_PREVIEW_NUMBER;
        $this->assertEquals($notificationpreviewnumber, count($notificationalerts), 'There are '.$notificationpreviewnumber.' notifications from cached');

        // There are 2 notifications from systemuser, such as connection request.
        $systemuser = 0;
        foreach ($notificationalerts as $notification) {
            if ($notification->realuseridfrom === null) {
                $systemuser++;
            }
        }
        $this->assertEquals(2, $systemuser, 'There are 2 notifications from systemuser');

        // The order of the $notificationalerts should be: n6, n5, n4, n3, n2
        $counter = 6;
        foreach ($notificationalerts as $notificationalert) {
            $this->assertEquals($notificationalert->fullmessage, 'Notification'.$counter, 'Expected Notification'.$counter);
            $counter--;
        }

        $time = time();

        // User1 read the notification 6 and 3.
        message_mark_message_read($n6, $time++);
        message_mark_message_read($n3, $time++);

        // message_read triggered and clear cache, new cache reloaded.
        // There are 4 notifications.
        $notificationscached = api::get_cached_unread_notification_preview($user1);
        $totalcount = $notificationscached['unread_notification_count'];
        $this->assertEquals(4, $totalcount, 'There are 4 total notifications for user1');

        // And the order of the $notificationalerts should be: n5, n4, n2, n1
        $counter = 5;
        $notificationalerts = $notificationscached['unread_notification_preview'];
        foreach ($notificationalerts as $notificationalert) {
            $this->assertEquals($notificationalert->fullmessage, 'Notification'.$counter, 'Expected Notification'.$counter);
            $counter--;
            // Skip 3, the third notification that alread read.
            if ($counter == 3) {
                $counter--;
            }
        }
    }

    /**
     * All notification page should display notifications in correct order.
     *
     * @throws coding_exception
     */
    function test_notifications_are_displayed_in_correct_order() {
        global $PAGE;
        $this->resetAfterTest(true);
        // Set this user as the admin.
        $this->setAdminUser();
        $timenow = time();
        $onehour = 60 * 60;
        $oneday = $onehour *  24;

        $nouser = new stdClass();
        $nouser->id = -10;

        // Create a user to add to the admin's contact list.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();


        $this->setUser($user1);

        $timenow = $time1 = $timenow + $onehour;
        $n1 = $this->send_fake_notification($nouser, $user1, "1 Notification", $time1);
        $n2 = $this->send_fake_notification($nouser, $user2, "2 Notification", $time1);
        $n3 = $this->send_fake_notification($user2, $user1, "3 Notification", $time1);
        $n4 = $this->send_fake_notification($nouser, $user2, "3 Notification", $time1);

        \local_conversations\api::mark_messages_read_by_id(array($n3->id));

        $mynotificationspage = new \local_conversations\output\my_notifications();
        $renderer = $PAGE->get_renderer('local_conversations');
        $html = $renderer->render($mynotificationspage);

        $this->assertValidHtml($html);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="UTF-8">' .$html);
        $selector = new DOMXPath($doc);

        $notifications =  $selector->query("//div[contains(concat(' ', normalize-space(@class), ' ') , ' panel ')]");
        $this->assertEquals(2, $notifications->length, 'There should be 2 notifications');
        $notificationreturn = $doc->saveHTML($notifications->item(0));

        $this->assertcontains($n3->smallmessage, $notificationreturn, "Top notification should be notification 3");
        $this->assertNotContains('unread', $notificationreturn);

        $notificationreturn = $doc->saveHTML($notifications->item(1));

        $this->assertcontains($n1->smallmessage, $notificationreturn, "Second notification should be notification 1");
        $this->assertcontains('unread', $notificationreturn);

    }
}
