<?php
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
use block_oua_forum_recent_posts\api;
use block_oua_forum_recent_posts\external;

/**
 *  Unit tests for recent forum posts block
 *
 * @package    blocks
 * @subpackage oua_forum_recent_posts
 */
class block_oua_forum_recent_posts_testcase extends oua_advanced_testcase {

    protected function setUp() {
        global $CFG;
    }

    /**
     * Indent a created module.  Moodle's generator function doesn't handle indent correctly.
     * @param $cm stdClass The course module to indent.
     */
    private function indent_module($cm) {
        global $DB;
        // Moodle 2.9 ignores indent as an option when creating plugins, we need to update the database.
        $coursemoduledata = $DB->get_record('course_modules', array('id' => $cm->cmid));
        $coursemoduledata->indent = 5;
        $DB->update_record('course_modules', $coursemoduledata);
        rebuild_course_cache($cm->course);
    }

    static function setAdminUser() {
        global $USER;
        parent::setAdminUser();
        // The logged in user needs email, country and city to do certain things.
        $USER->email = 'ben.kelada@open.edu.au';
        $USER->country = 'AU';
        $USER->city = 'Melbourne';
    }

    /**
     * GIVEN We have created a recent post block
     * WHEN we retrieve the output
     * THEN the percentage is 0 and there is no end date.
     *
     * @test
     */
    public function test_forum_recent_post_block_no_errors() {
        global $PAGE;

        self::setAdminUser();
        load_all_capabilities();
        $this->resetAfterTest(true);

        $PAGE->set_url('/blocks/test');

        $block = $this->getDataGenerator()->create_block('oua_forum_recent_posts');
        $block = block_instance('oua_forum_recent_posts', $block);

        $html = $block->get_content()->text;

        $this->assertEmpty($html, "The html output of the message should be in the format expected");
    }

    public function test_forum_recent_post_block_shows_no_forum_posts_when_not_hidden() {
        global $PAGE;

        $this->resetAfterTest();
        self::setAdminUser();

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));

        $courseformat = course_get_format($course);

        $record = array('course' => $course->id);

        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record);
        $PAGE->set_cm(get_coursemodule_from_id('forum', $forum->cmid));

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = 2;
        $record->forum = $forum->id;
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $block = $this->getDataGenerator()->create_block('oua_forum_recent_posts');
        $block = block_instance('oua_forum_recent_posts', $block);

        $html = $block->get_content()->text;

        $this->assertEmpty($html, 'No posts should be returned as the item is not hidden.');
    }

    /**
     * Tests creating forum discussions, including pinning them
     * uses our custom api's and tests the caching is operating correctly
     * Fakes the ajax requests with api calls as we no longer generate the block on block output
     * block is generated with ajax.
     *
     * @throws coding_exception
     */
    public function test_forum_recent_post_block_shows_recent_forum_discussions() {
        global $DB, $PAGE, $USER;

        $this->resetAfterTest();

        self::setAdminUser();
        $PAGE->set_url('/');
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));

        $record = array('course' => $course->id);

        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record);
        $this->indent_module($forum);
        $PAGE->set_cm(get_coursemodule_from_id('forum', $forum->cmid));

        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $user1->id;
        $record->forum = $forum->id;
        $nowtime = time();
        $record->timemodified = $nowtime++;
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $record->userid = $user2->id;
        $record->timemodified = $nowtime++;
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $block = $this->getDataGenerator()->create_block('oua_forum_recent_posts');
        $block = block_instance('oua_forum_recent_posts', $block);

        $adhoccachename = "adhoc/block_oua_forum_recent_posts_user_forum_discussion_with_post_cache_{$forum->id}";
        $stats = \cache_helper::get_stats();
        $this->assertArrayNotHasKey($adhoccachename, $stats);
        $html = $this->emulate_ajax_discussion_load($forum, 0, 4);

        // Test cache was set.
        $stats = \cache_helper::get_stats();
        $this->assertArrayHasKey($adhoccachename, $stats);
        $this->assertEquals(1, $stats[$adhoccachename]['stores']['cachestore_file']['sets']);

        $this->assertXPathGetNodesWithClassesCount(2, 'discussion-topic', $html,
                                                   'There must be 2 visible discussions.' . "\n\n$html\n\n");

        // Test cache works.
        $cache = api::make_forum_discussion_cache($forum->id);

        $cachekey = $USER->id . "_" . 0 . "_" . 4 . '_' . 'timemodified' . '_' . 'DESC';
        $discussionscache = $cache->get($cachekey);
        $this->assertCount(2, $discussionscache); // Test that cache was retrieves the right value.

        $html = $this->emulate_ajax_discussion_load($forum, 0, 4); // This hit should load from cache.

        $stats = \cache_helper::get_stats();
        $this->assertEquals(2, $stats[$adhoccachename]['stores']['cachestore_file']['hits']);

        $record->pinned = 1;
        $record->userid = $user1->id;
        $nowtime = $nowtime + 120;
        $record->timemodified = $nowtime++;
        $discussion3 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $record->discussion = $discussion3->id;
        $record->parent = $discussion3->firstpost;
        $record->userid = $user2->id;
        $nowtime = $nowtime + 120;
        $record->modified = $nowtime++;
        $discussion3reply1 = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        $record->parent = $discussion3reply1->id;
        $record->userid = $user3->id;
        $nowtime = $nowtime + 120;
        $record->modified = $nowtime++;
        $discussion3reply2 = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        $reply3subject = "REPLY3: " . $discussion3reply2->subject;
        $reply3message = "REPLY3: " . $discussion3reply2->message;
        $ajaxreply3result = external::oua_forum_add_discussion_post($discussion3->firstpost, $reply3subject, $reply3message);
        // update modified time on post added by webservice
        $nowtime = $nowtime + 120;
        $nowtime = $nowtime++;
        $DB->set_field('forum_posts', 'modified', $nowtime, array('id' => $ajaxreply3result['postid']));
        $DB->set_field('forum_posts', 'created', $nowtime, array('id' => $ajaxreply3result['postid']));
        $DB->set_field('forum_discussions', 'timemodified', $nowtime, array('id' => $discussion3->id));

        $record->pinned = 1;
        $record->userid = $user2->id;
        $record->timemodified = $nowtime++;
        $discussion4 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        $record->pinned = 0;
        $record->userid = $user3->id;
        $record->timemodified = $nowtime++;
        $discussion5 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);
        $record->userid = $user4->id;
        $nowtime = $nowtime + 120;
        $record->timemodified = $nowtime++;
        $discussion6 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $block->refresh_content();
        $html = $block->get_content()->text;
        $this->assertValidHtml($html);
        // Change number loaded to avoid cache.
        $numdiscussions = 4;
        $cache->purge();
        $html = $this->emulate_ajax_discussion_load($forum, 0, $numdiscussions, true);
        $this->assertXPathGetNodesWithClassesCount($numdiscussions, 'discussion-topic', $html,
                                                   "There must only be $numdiscussions visible discussions." . "\n\n$html\n\n");

        $this->assertContains($discussion6->name, $html);
        $this->assertContains($discussion5->name, $html);
        $this->assertContains($discussion4->name, $html);
        $this->assertContains($discussion3->name, $html);
        $this->assertNotContains($discussion2->name, $html);
        $this->assertNotContains($discussion1->name, $html);

        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $selector = new DOMXPath($doc);
        $result = $selector->query("//*[contains(concat(' ', normalize-space(@class), ' ') , ' discussion-topic ')]");

        foreach ($result as $node) {
            $values[] = $doc->saveHTML($node);
        }
        // Test order of discussions is correct, tests that pinned discussions are at top.
        $this->assertContains($discussion4->name, $values[0]);
        $this->assertContains($discussion3->name, $values[1]);
        $this->assertContains($discussion6->name, $values[2]);
        $this->assertContains($discussion5->name, $values[3]);

        $this->assertNotContains($discussion1->name, $html);
        $this->assertContains('<span class="col-md-2 replies">3</span>', $values[1]);
        $result = $selector->query("//*[contains(concat(' ', normalize-space(@class), ' ') , ' discussion-post-replies ')]");
        $discussioncontentvalues = array();
        foreach ($result as $node) {
            $discussioncontentvalues[] = $doc->saveHTML($node);
        }

        // This gets the reply count from discussion 3, this is subject to change.
        $this->assertContains($discussion3reply1->message, $discussioncontentvalues[1]);
        $this->assertContains($discussion3reply2->message, $discussioncontentvalues[1]);
        $this->assertContains($reply3message, $discussioncontentvalues[1]);

        // Delete a post.
        external::oua_forum_delete_discussion_post($discussion5->firstpost);
        $cache->purge();

        $html = $this->emulate_ajax_discussion_load($forum, 0, $numdiscussions, true);

        $this->assertXPathGetNodesWithClassesCount($numdiscussions, 'discussion-topic', $html,
                                                   "There must only be $numdiscussions visible discussions." . "\n\n$html\n\n");
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $selector = new DOMXPath($doc);
        $result = $selector->query("//*[contains(concat(' ', normalize-space(@class), ' ') , ' discussion-topic ')]");
        $values = array();
        foreach ($result as $node) {
            $values[] = $doc->saveHTML($node);
        }

        // Test order of discussions is correct after DELETE.
        // Tests that pinned discussions are at top.
        $this->assertContains($discussion4->name, $values[0]);
        $this->assertContains($discussion3->name, $values[1]);
        $this->assertContains($discussion6->name, $values[2]);
        $this->assertContains($discussion2->name, $values[3]);

        // Test post times are correct
        // Get discussion modified time for discussion 3
        $discussion3posttimequery = "//div[@data-discussionid='" . $discussion3->id . "']//div//span[contains(concat(' ', normalize-space(@class), ' ') , ' time ')]//text()";
        $result = $selector->query($discussion3posttimequery);
        $discussiontime = array();
        foreach ($result as $node) {
            $discussiontime[] = $doc->saveHTML($node);
        }
        // Get post times for all replies in discussion 3
        $discussion3replytimes = "//div[@data-discussionid='" . $discussion3->id . "']//div[contains(concat(' ', normalize-space(@class), ' ') , ' discussion-post-replies ')]//div[contains(concat(' ', normalize-space(@class), ' ') , ' post-header ')]//p//span//text()";
        $result = $selector->query($discussion3replytimes);
        $posttimes = array();
        foreach ($result as $node) {
            $posttimes[] = $doc->saveHTML($node);
        }
        $this->assertNotEquals($discussiontime[0], $posttimes[0],
                               "First post in discussion 3 should not match discussion modified time. HTML: " . $html);
        $this->assertNotEquals($discussiontime[0], $posttimes[1],
                               "Second post in discussion 3 should not match discussion modified time.  HTML: " . $html);
        $this->assertEquals($discussiontime[0], $posttimes[2],
                            "Third post in discussion 3  should  match discussion modified time. HTML:" . $html);
    }

    public function test_forum_recent_post_block_shows_when_no_discussions() {
        global $PAGE;

        $this->resetAfterTest();
        $PAGE->set_url('/');
        self::setAdminUser();

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));

        $courseformat = course_get_format($course);

        $course = array('course' => $course->id);

        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($course);
        $page = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($course);
        $this->indent_module($forum);
        $PAGE->set_cm(get_coursemodule_from_id('page', $page->cmid));

        $block = $this->getDataGenerator()->create_block('oua_forum_recent_posts');
        $block = block_instance('oua_forum_recent_posts', $block);

        $html = $block->get_content()->text;
        $this->assertContains(get_string('addnewdiscussion', 'block_oua_forum_recent_posts'), $html,
                              'New disucssion button should still be displayed when there are no posts');
    }

    public function test_forum_recent_post_block_contains_only_posts_from_this_section() {
        $this->markTestIncomplete('Test development has been deferred until we have completed functionality.');
    }

    public function test_forum_recent_post_block_contains_only_has_max_post_size() {
        $this->markTestIncomplete('Test development has been deferred until we have completed functionality.');
    }

    public function test_block_returns_correct_student_count() {
        global $DB, $PAGE;
        $this->resetAfterTest();
        $PAGE->set_url('/');
        self::setAdminUser();
        $c1 = $this->getDataGenerator()->create_course(array('format' => 'invisible'));
        $c1ctx = context_course::instance($c1->id);
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $u3 = $this->getDataGenerator()->create_user();
        $u4 = $this->getDataGenerator()->create_user();
        $u5 = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($u1->id, $c1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($u2->id, $c1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($u3->id, $c1->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($u4->id, $c1->id, $teacherrole->id);

        $course = array('course' => $c1->id);

        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($course);
        $page = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($course);
        $this->indent_module($forum);
        $PAGE->set_cm(get_coursemodule_from_id('page', $page->cmid));
        $course = new stdClass();
        $course->course = $c1->id;
        $course->userid = 2;
        $course->forum = $forum->id;
        $nowtime = time();
        $course->timemodified = $nowtime++;
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($course);
        $course->timemodified = $nowtime++;
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($course);

        $block = $this->getDataGenerator()->create_block('oua_forum_recent_posts');
        $block = block_instance('oua_forum_recent_posts', $block);

        $html = $block->get_content()->text;
        $this->assertEquals(3, $block->get_enrolled_students_count($c1ctx));
        $this->assertContains(get_string('peopleincourse', 'block_oua_forum_recent_posts',
                                         $block->get_enrolled_students_count($c1ctx)), $html);

        $this->getDataGenerator()->enrol_user($u5->id, $c1->id, $teacherrole->id); // Enrol user to trigger cache invalidation.
        $cache = cache::make('block_oua_forum_recent_posts', 'student_count');
        $count = $cache->get($c1ctx->id);
        $this->assertEquals(null, $count); // Test that cache was invalidated on enrolment.
    }

    private function emulate_ajax_discussion_load($forum, $page = -1, $numposts = 5, $purgecache = false) {
        global $PAGE;
        if ($purgecache) {
            $cache = api::make_forum_discussion_cache($forum->id);
            $cache->purge();
        }
        $forumcm = get_fast_modinfo($forum->course, 0)->instances['forum'][$forum->id];

        // Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
        $modcontext = \context_module::instance($forumcm->id);
        $discussions = api::get_oua_forum_discussions_with_posts_paginated($forum, 'timemodified', 'DESC', $page, $numposts);

        $renderer = $PAGE->get_renderer('block_oua_forum_recent_posts');
        $html = $renderer->render_from_template('block_oua_forum_recent_posts/discussions', array('discussions' => $discussions));
        return $html;
    }

    private function check_moderate_links($html, $myforums, $assertvalue) {
        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $sel = new DOMXPath($doc);

        $foundcount = 0;
        $moderatetext = get_string('moderatediscussion', 'block_oua_forum_recent_posts');
        foreach ($myforums as $adiscussion) {

            $discussionid = $adiscussion->id;
            $firstpostid = $adiscussion->firstpost;

            $query = "//div[@data-discussionid='" . $discussionid . "']//a[contains(@href, '/mod/forum/discuss.php?d=" . $discussionid . "')][text()[contains(.,'" . $moderatetext . "')]]";

            $result = $sel->query($query);

            $error = array();
            $error['message'] = $assertvalue ? 'User:moderator' : 'User:student';
            $error['query'] = $query;
            $error['discussion'] = $adiscussion;
            $error['html'] = $html;

            $this->assertEquals($assertvalue, $result->length, print_r($error, true));
            $foundcount += $assertvalue;
        }

        // get moderate links from anywhere
        $query = "//a[contains(@href, '/mod/forum/discuss.php?d=')][text()[contains(.,'" . $moderatetext . "')]]";
        $result = $sel->query($query);

        // match exactly despite looking everywhere for other instances
        $this->assertEquals($foundcount, $result->length);

        // if administrator login, then the moderate links should also match the number of discussions
        if ($assertvalue) {
            $this->assertEquals(count($myforums), $result->length);
        }
    }

    /**
     * Test for moderate link on the first topic is available when a moderator login
     * and none for student
     * @throws coding_exception
     */
    public function test_forum_recent_post_moderate() {
        global $PAGE, $DB;

        $this->resetAfterTest();

        self::setAdminUser();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);

        $record = array('course' => $course->id);

        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record);
        $this->indent_module($forum);
        $PAGE->set_cm(get_coursemodule_from_id('forum', $forum->cmid));

        $record = new stdClass();
        $record->course = $course->id;

        // create forum
        $record->forum = $forum->id;
        $nowtime = time();
        $numdiscussions = 10;

        // create block
        $block = $this->getDataGenerator()->create_block('oua_forum_recent_posts');
        $block = block_instance('oua_forum_recent_posts', $block);

        // discussion 1 by user 1
        $record->userid = $user1->id;
        $record->timemodified = $nowtime++;
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // discussion 2 by user 2
        $record->userid = $user2->id;
        $record->timemodified = $nowtime++;
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // discussion 3 by user 1
        $record->userid = $user1->id;
        $record->timemodified = $nowtime++;
        $discussion3 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // user 2 reply to discussion 3
        $record->discussion = $discussion3->id;
        $record->parent = $discussion3->firstpost;
        $record->userid = $user2->id;
        $record->modified = $nowtime++;
        $discussion3reply1 = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // user 3 reply to discussion 3 of user 2 post
        $record->parent = $discussion3reply1->id;
        $record->userid = $user3->id;
        $record->modified = $nowtime++;
        $discussion3reply2 = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // ajax create a reply to discussion 3 first topic using external API, modify subject and message
        $reply3subject = "REPLY3: " . $discussion3reply2->subject;
        $reply3message = "REPLY3: " . $discussion3reply2->message;
        $ajaxreply3result = external::oua_forum_add_discussion_post($discussion3->firstpost, $reply3subject, $reply3message);
        $nowtime = $nowtime + 240;
        // update modified time on post added by webservice
        $DB->set_field('forum_posts', 'modified', $nowtime, array('id' => $ajaxreply3result['postid']));
        $DB->set_field('forum_posts', 'created', $nowtime, array('id' => $ajaxreply3result['postid']));
        $DB->set_field('forum_discussions', 'timemodified', $nowtime, array('id' => $discussion3->id));

        // discussion 4
        $record->userid = $user2->id;
        $record->timemodified = $nowtime++;
        $discussion4 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // discussion 5
        $record->userid = $user3->id;
        $record->timemodified = $nowtime++;
        $discussion5 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // discussion 6
        $record->userid = $user4->id;
        $record->timemodified = $nowtime++;
        $discussion6 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $myforums = array();
        $myforums[] = $discussion1;
        $myforums[] = $discussion2;
        $myforums[] = $discussion3;
        $myforums[] = $discussion4;
        $myforums[] = $discussion5;
        $myforums[] = $discussion6;

        // I am an adminstrator, for each discussion in $myforums I will have exactly 1 moderate link on the first topic
        // and the number of moderate link is equal to the number of discussions
        // and there is no moderate links elsewhere
        $html = $this->emulate_ajax_discussion_load($forum, 0, $numdiscussions, true); // load as an admin
        $this->check_moderate_links($html, $myforums, 1);

        // Now I am a student, there is no moderate link anywhere
        $this->setUser($student);
        $html = $this->emulate_ajax_discussion_load($forum, 0, $numdiscussions, true); // load as a student
        $this->check_moderate_links($html, $myforums, 0);
    }

    /**
     * student post reply-1, there is a delete link
     * student post reply-2 to reply-1 -- there is delete link in reply-2 BUT NOT in reply-1
     *
     * @throws coding_exception
     */
    public function test_forum_delete_with_reply() {
        global $PAGE, $DB;

        $this->resetAfterTest();

        self::setAdminUser();

        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $user4 = self::getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id, $studentrole->id);

        $record = array('course' => $course->id);

        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record);
        $this->indent_module($forum);
        $PAGE->set_cm(get_coursemodule_from_id('forum', $forum->cmid));

        $record = new stdClass();
        $record->course = $course->id;

        // create forum
        $record->forum = $forum->id;
        $nowtime = time();
        $numdiscussions = 10;

        // create block
        $block = $this->getDataGenerator()->create_block('oua_forum_recent_posts');
        $block = block_instance('oua_forum_recent_posts', $block);

        $this->setUser($user1);

        // discussion 1 by user 1
        $record->userid = $user1->id;
        $record->timemodified = $nowtime++;
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // discussion 2 by user 2
        $record->userid = $user2->id;
        $record->timemodified = $nowtime++;
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // discussion 3 by user 1
        $record->userid = $user1->id;
        $record->timemodified = $nowtime++;
        $discussion3 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // user 3 reply to discussion 3
        $record->discussion = $discussion3->id;
        $record->parent = $discussion3->firstpost;
        $record->userid = $user3->id; // ====> user 3 post
        $record->modified = $nowtime++;
        $discussion3reply1 = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // user 3 reply to discussion 3 of user 3 post
        $record->parent = $discussion3reply1->id;
        $record->userid = $user3->id; // === now user 3 post again, the delete link is available
        $record->modified = $nowtime++;
        $discussion3reply2 = self::getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);
        $nowtime++;

        // ajax create a reply to discussion 3 first topic using external API, modify subject and message
        // this one is interesting, it is user 1 posting since user 1 is login and also replying to first post
        $reply3subject = "REPLY3: " . $discussion3reply2->subject;
        $reply3message = "REPLY3: " . $discussion3reply2->message;
        $ajaxreply3result = external::oua_forum_add_discussion_post($discussion3->firstpost, $reply3subject, $reply3message);

        // update modified time on post added by webservice
        $DB->set_field('forum_posts', 'modified', $nowtime, array('id' => $ajaxreply3result['postid']));
        $DB->set_field('forum_posts', 'created', $nowtime, array('id' => $ajaxreply3result['postid']));
        $DB->set_field('forum_discussions', 'timemodified', $nowtime, array('id' => $discussion3->id));

        // discussion 4
        $record->userid = $user2->id;
        $record->timemodified = $nowtime++;
        $discussion4 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // == USER 1 started discussion1, then simulated ajax posting to discussion 3
        // user 1 started discussion 1
        $this->setUser($user1);
        $html = $this->emulate_ajax_discussion_load($forum, 0, $numdiscussions);

        $query = "//div[@data-postid='" . $discussion1->firstpost . "']//div[@class='deletepost']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html, 'User 1: expect 1 delete link in discussion1');

        $postid = $ajaxreply3result['postid'];
        $query = "//div[@data-postid='" . $postid . "']//div[@class='deletepost']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html, 'User 1: expect 1 delete link ajax replied to discussion 3');

        $query = "*//div[@class='deletepost']";
        $this->assertXpathDomQueryResultLengthEquals(2, $query, $html, 'User 1: expected no other delete link in DOM');

        // === USER 3 post again in discussion3
        $this->setUser($user3);
        $html = $this->emulate_ajax_discussion_load($forum, 0, $numdiscussions);

        $query = "//div[@data-postid='" . $discussion3reply2->id . "']//div[@class='deletepost']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html,
                                                     'User 3: expected 1 delete link in the reply to discussion3');

        $query = "*//div[@class='deletepost']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html, 'User 3: expected no other delete link in DOM');

        // === USER 2 discusssion 2 and 4
        $this->setUser($user2);
        $html = $this->emulate_ajax_discussion_load($forum, 0, $numdiscussions);

        $query = "//div[@data-postid='" . $discussion2->firstpost . "']//div[@class='deletepost']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html,
                                                     'User 2: expected 1 delete link in the reply to discussion2');

        $query = "//div[@data-postid='" . $discussion4->firstpost . "']//div[@class='deletepost']";
        $this->assertXpathDomQueryResultLengthEquals(1, $query, $html,
                                                     'User 2: expected 1 delete link in the reply to discussion4');

        $query = "*//div[@class='deletepost']";
        $this->assertXpathDomQueryResultLengthEquals(2, $query, $html, 'User 2: expected no other delete link in DOM');
    }

    public function test_file_attachment_inline() {
        $this->markTestIncomplete('Have not yet written test to upload files by ajax');
    }

    /**
     * Test post message include max allowable edit time for student
     * Test post message without edit time limit for admin
     */
    public function test_forum_post_message() {
        global $PAGE, $DB, $CFG, $USER;

        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $studentrole->id);

        $record = array('course' => $course->id);

        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record);
        $forum->context = context_module::instance($forum->cmid);

        $PAGE->set_cm(get_coursemodule_from_id('forum', $forum->cmid));
        $renderer = $PAGE->get_renderer('block_oua_forum_recent_posts');
        $PAGE->set_url('/');

        $this->setUser($user1);

        $record = new stdClass();
        $record->course = $course->id;

        // create forum
        $record->forum = $forum->id;
        $nowtime = time();

        // discussion 1 by user 1
        $record->userid = $user1->id;
        $record->timemodified = $nowtime++;
        $discussion1 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $canmanageforum = has_capability('mod/forum:pindiscussions', $forum->context) || has_capability('mod/forum:editanypost',
                                                                                                        $forum->context);

        $html = $renderer->display_forum_posts($forum, // discussion
                                               $discussion1, 10, // dummy student post count
                                               100, // display all posts don't worry about hidden
                                               $canmanageforum //$canmanageforum - student
        );

        $alloweditminute = round($CFG->maxeditingtime / 60);
        $message = get_string('postsuccesswithedittime', 'block_oua_forum_recent_posts', $alloweditminute);
        $expect = '<div class="discussion-reply alert alert-success " style="display:none" role="alert"> ' . $message . '</div>';
        $this->assertContains($expect, $html,
                              'Expect "The post was submitted successfully and you have {$a} mins to delete the post" where {$a} refers to configure delete time');

        self::setAdminUser();

        // discussion 2 by user 2 - admin
        $record->userid = $USER->id;
        $record->timemodified = $nowtime++;
        $discussion2 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $canmanageforum = has_capability('mod/forum:pindiscussions', $forum->context) || has_capability('mod/forum:editanypost',
                                                                                                        $forum->context);

        $html = $renderer->display_forum_posts($forum, // discussion
                                               $discussion2, 10, // dummy post count
                                               100, // display all posts don't worry about hidden
                                               $canmanageforum //$canmanageforum - student
        );

        $message = get_string('postsuccess', 'block_oua_forum_recent_posts');
        $expect = '<div class="discussion-reply alert alert-success " style="display:none" role="alert"> ' . $message . '</div>';
        $this->assertContains($expect, $html, 'Expect "The post was submitted successfully" with no reference to editing time');
    }

    public function test_forum_add_discussion_subject_length_limit() {
        global $PAGE, $DB, $CFG;

        require_once($CFG->dirroot . '/lib/externallib.php');

        $this->resetAfterTest();

        $user1 = self::getDataGenerator()->create_user();

        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible'));
        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $studentrole->id);

        $record = array('course' => $course->id);

        $forum = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record);
        $forum->context = context_module::instance($forum->cmid);

        $PAGE->set_cm(get_coursemodule_from_id('forum', $forum->cmid));
        $renderer = $PAGE->get_renderer('block_oua_forum_recent_posts');
        $PAGE->set_url('/');

        $this->setUser($user1);

        $record = new stdClass();
        $record->course = $course->id;

        // create forum
        $record->forum = $forum->id;
        $nowtime = time();

        // discussion 1 by user 1
        $record->userid = $user1->id;
        $record->timemodified = $nowtime++;

        // simulate ajax add new discussion with long subject line
        $s1 = "0123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789";
        $discussion1subject = $s1 . $s1 . $s1; // 300 chars
        $discussion1message = "message";

        $ajaxnewdiscussion = external::oua_forum_add_discussion($forum->id, $discussion1subject, $discussion1message);

        $expectedwarning = get_string('validationsubjectlength', 'block_oua_forum_recent_posts');
        $resultwarning = '';

        $this->assertTrue(isset($ajaxnewdiscussion['warnings']), 'Expect warnings result');
        $warnings = $ajaxnewdiscussion['warnings'];

        $issubjectwarning = false;
        foreach ($warnings as $alert) {
            if (isset($alert['subject'])) {
                $resultwarning = $alert['subject'];
                $issubjectwarning = true;
            }
        }
        $this->assertTrue($issubjectwarning, 'Expect there is a "subject" warning');
        $this->assertEquals($expectedwarning, $resultwarning,
                            'Expect warning message "' . $expectedwarning . '" and new discussion adding aborted without throwing database error exception');
    }
}
