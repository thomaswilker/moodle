<?php

/**
 * PHPUnit data generator tests
 *
 * @package    block_oua_social_activity
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
use block_oua_social_activity\external;

/**
 * PHPUnit data generator testcase
 *
 * @package    block_oua_social_activity
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */
class block_oua_social_activity_testcase extends oua_advanced_testcase {

    function test_block_html_valid() {
        global $USER;
        $this->resetAfterTest();
        // Set this user as the admin.
        $this->setAdminUser();

        global $DB;
        $transact = $DB->start_delegated_transaction();

        $user0 = $this->getDataGenerator()->create_user();
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();
        $user5 = $this->getDataGenerator()->create_user();
        $user6 = $this->getDataGenerator()->create_user();
        $user7 = $this->getDataGenerator()->create_user();
        $user8 = $this->getDataGenerator()->create_user();
        $user9 = $this->getDataGenerator()->create_user();

        $user10 = $this->getDataGenerator()->create_user();



        $this->setUser($user1->id);
        message_add_contact($user9->id); // One way connection user 9 is on user 1's contact list
        $this->setUser($user8->id);
        message_add_contact($user1->id); // One way connection 1 is on user 8's contact list.


        $this->setAdminUser();


        $this->connect_contacts_with_event($user2->id, $user10->id); // Connect User 2 to User 10 (before user 2 is connected to user 1, user 1 should not see this)
        $params = $this->get_connection_string_params($user2, $user10);
        $u2u10connectedstring = get_string('contactconnected', 'block_oua_social_activity', $params);

        $this->connect_contacts_with_event($user1->id, $user2->id);
        $this->connect_contacts_with_event($user1->id, $user3->id);

        $this->connect_contacts_with_event($user2->id, $user3->id); // User 3 connected to user 3, after User 2 is connected to user 1 and user 3 is connected to user 1(user 1 should see this (both User 1 contacts))
        $params = $this->get_connection_string_params($user3, $user2);
        $u2u3connectedstring = get_string('bothcontactsconnected', 'block_oua_social_activity', $params);

        $this->connect_contacts_with_event($user2->id, $user4->id); // User 1 should see this (user 2 is user 1 contact)
        $params = $this->get_connection_string_params($user2, $user4);
        $u2u4connectedstring = get_string('contactconnected', 'block_oua_social_activity', $params);


        $this->connect_contacts_with_event($user2->id, $user5->id); // User 2 is connected to user5, user 1 should see this (user 2 is user 1 contact)
        $params = $this->get_connection_string_params($user2, $user5);
        $u2u5connectedstring = get_string('contactconnected', 'block_oua_social_activity', $params);

        $this->connect_contacts_with_event($user6->id, $user3->id); // user 6 connects to user 3, User 1 should see this (user 3 is user 1 contact)
        $params = $this->get_connection_string_params($user3, $user6);
        $u3u6connectedstring = get_string('contactconnected', 'block_oua_social_activity', $params);

        $this->connect_contacts_with_event($user8->id, $user7->id); // User 1 should NOT see this
        $params = $this->get_connection_string_params($user8, $user7);
        $u8u7connectedstring = get_string('contactconnected', 'block_oua_social_activity', $params);


        $this->connect_contacts_with_event($user9->id, $user7->id); // User 1 should NOT see this.
        $params = $this->get_connection_string_params($user9, $user7);
        $u9u7connectedstring = get_string('contactconnected', 'block_oua_social_activity', $params);


        $this->connect_contacts_with_event($user2->id, $user7->id); // User 1 should see this
        $params = $this->get_connection_string_params($user2, $user7);
        $u2u7connectedstring = get_string('contactconnected', 'block_oua_social_activity', $params);


        // Seeing the social connections is time dependant, the above connections all happened within the same second.
        // Update the database to fix the times, udpates the time in order of creation using the  record id.
        $DB->execute("UPDATE {oua_social_activity_events} SET timecreated = timecreated + id"); // Events all have the same time created, update them for testing.

        $this->setUser($user1->id);
        $block = $this->getDataGenerator()->create_block('oua_social_activity');
        $blockinstance = block_instance('oua_social_activity', $block);
        $blockinstance->config = new stdClass();
        $blockinstance->config->numberofsocialevents = 20;
        $html = $blockinstance->get_content()->text;
        $this->assertValidHtml($html);

        $this->assertContains($u2u3connectedstring, $html, 'user 1 should see special string because user 2 and user 3 are both user 1\'s contacts');
        $this->assertContains($u2u4connectedstring, $html, 'user 1 should see user 2 and user 4 connection');
        $this->assertContains($u2u5connectedstring, $html, 'user 1 should see user 2 and user 5 connection');
        $this->assertContains($u3u6connectedstring, $html, 'user 1 should see user 2 and user 6 connection');
        $this->assertNotContains($u2u10connectedstring, $html, 'User1 should not see connections of user 2 that happened before they were connected');
        $this->assertNotContains($u8u7connectedstring, $html, 'User1 should not see connections of user 8 because it is only a one way connection');
        $this->assertNotContains($u9u7connectedstring, $html, 'User1 should not see connections of user 9 because itis only a one way connection');
        $this->assertContains($u2u7connectedstring, $html, 'User1 should  see connections of user 7 because they are connected to user 2');

    }

    /**
     * Helper function to create parameters to retrieve language string "user 1 connected to user2" for testing.
     *
     * @param $usera user object with user details for user1
     * @param $userb user object with user details for user2
     *
     * @return array parameter array with user details for language string retrieval
     */
    private function get_connection_string_params($usera, $userb) {
        $params = array();
        $params['u1id'] = $usera->id;
        $params['u1fullname'] = fullname($usera);
        $profileurl = new moodle_url('/user/profile.php', array('id' =>  $usera->id));
        $params['u1profileurl'] = $profileurl->out(true);

        $params['u2id'] = $userb->id;
        $params['u2fullname'] = fullname($userb);
        $profileurl = new moodle_url('/user/profile.php', array('id' =>  $userb->id));
        $params['u2profileurl'] = $profileurl->out(true);
        return $params;
    }

    /**
     * Helper function to connect users and trigger the connection event
     * We don't want to be dependant on the oua_connections message api for testing.
     *
     * @param $user1id
     * @param $user2id
     *
     * @throws coding_exception
     */
    private function connect_contacts_with_event($user1id, $user2id) {
            global $USER;
            $currentuserid = $USER->id;
            $this->setUser($user1id);

            // Add users to the admin's contact list.
            message_add_contact($user2id);

            $this->setUser($user2id);
            message_add_contact($user1id);
            // Fake Connected event as we arent relying on oua_connections block event (or using its methods)
            $event = \block_oua_connections\event\contact_connected::create(array(
                                                                                'objectid' => $user2id,
                                                                                'userid' => $user2id,
                                                                                'relateduserid' => $user1id,
                                                                                'context'  => \context_user::instance($user2id)
                                                                            ));
            $event->trigger();
            $this->setUser($currentuserid);
    }

}