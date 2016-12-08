<?php
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
require_once($CFG->dirroot.'/user/lib.php');

class contact_user_profile_testcase extends oua_advanced_testcase {
    function test_user_can_see_profile_when_in_contact_list() {

        $this->resetAfterTest();

        $systemcontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();


        // Create a role that will have the capability of view user details
        $viewprofileroleid = create_role('View Profile role', 'messagecontactprofileviewer', 'Profile viewer description');
        // Give the role the capability to view users profiles, this role will be assigned to a users personal context.
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $viewprofileroleid, $systemcontext);
        set_role_contextlevels($viewprofileroleid, array(CONTEXT_USER => CONTEXT_USER));
        set_config('viewprofilecontactrole', $viewprofileroleid, 'block_oua_connections');


        // User 8 cant see user 3's profile.
        $this->setUser($user2);
        $this->assertFalse(user_can_view_profile($user3));

        // User 1 cant see user 3's profile
        $this->setUser($user1);
        $this->assertFalse(user_can_view_profile($user3));
        // User 1 cant see user 8's profile.
        $this->assertFalse(user_can_view_profile($user2));

        // User 3 adds User 1 to contact list.
        $this->setUser($user3);
        message_add_contact($user1->id); // Event should assign role required.

        // User 1 Can now see user 3's profile.
        $this->setUser($user1);
        $this->assertTrue(user_can_view_profile($user3), 'User is part of contact list and cant view profile.');

        // User 1 still cant see user 8's profile.
        $this->assertFalse(user_can_view_profile($user2));

        // User 8 still cant see user 3's profile.
        $this->setUser($user2);
        $this->assertFalse(user_can_view_profile($user3));

        // User 3 adds User 1 to contact list.
        $this->setUser($user3);
        message_remove_contact($user1->id); // Event should remove role required.

        $this->setUser($user1);
        $this->assertFalse(user_can_view_profile($user3)); // User 1 should not longer be able to see user 3's profile
    }
    function test_user_contact_role_change_assigns_previous_connections() {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/oua_connections/lib.php');
        $this->resetAfterTest();

        $systemcontext = \context_system::instance();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        // User 2 cant see user 3's profile.
        $this->setUser($user2);
        $this->assertFalse(user_can_view_profile($user3));

        // User 1 cant see user 3's profile
        $this->setUser($user1);
        $this->assertFalse(user_can_view_profile($user3));

        // User 1 cant see user 2's profile.
        $this->assertFalse(user_can_view_profile($user2));

        // User 3 adds User 1 to contact list.
        $this->setUser($user3);
        message_add_contact($user1->id); // Event can't assign role required. as it hasnt been created yet.

        // User 1 STILL Cant see user 3's profile.
        $this->setUser($user1);
        message_add_contact($user2->id);
        $this->assertFalse(user_can_view_profile($user3));

        // User 1 still cant see user 2's profile.
        $this->assertFalse(user_can_view_profile($user2));


        $this->setUser($user2);
        message_add_contact($user1->id); // User 1 and user 2 are now "connected" they are in each others contact lists.
        // They should be able to see each others profiles, but the role hasnt been created yet.
        $this->assertFalse(user_can_view_profile($user1));
        $this->setUser($user1);
        $this->assertFalse(user_can_view_profile($user2));
        $this->assertFalse(user_can_view_profile($user3));


        // Create a role that will have the capability of view user details
        $viewprofileroleid = create_role('View Profile role', 'messagecontactprofileviewer', 'Profile viewer description');
        // Give the role the capability to view users profiles, this role will be assigned to a users personal context.
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $viewprofileroleid, $systemcontext);
        set_role_contextlevels($viewprofileroleid, array(CONTEXT_USER => CONTEXT_USER));
        set_config('viewprofilecontactrole', $viewprofileroleid, 'block_oua_connections');
        block_oua_connections_updatedcallback('');

        // After role assign, users who are "connected" should be able to see each others profile.
        $this->setUser($user2);
        $this->assertTrue(user_can_view_profile($user1));

        $this->setUser($user1);
        $this->assertTrue(user_can_view_profile($user2));

        // User 1 CAN NOW see user 3's profile as previously user 3 had added user 1 to their profile.
        $this->assertTrue(user_can_view_profile($user3), 'User is part of contact list and cant view profile.');

        $this->setUser($user3);
        $this->assertFalse(user_can_view_profile($user1), "User 1 should still not be able to see user 3's profile");
        message_remove_contact($user1->id); // Event should remove role required.

        $this->setUser($user1);
        $this->assertFalse(user_can_view_profile($user3)); // User 1 should not longer be able to see user 3's profile
    }
}
