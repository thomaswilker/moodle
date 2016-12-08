<?php

defined('MOODLE_INTERNAL') || die;

global $CFG;

require_once($CFG->dirroot . '/course/format/invisible/lib.php');
require_once($CFG->libdir . '/completionlib.php');

class course_format_invisible_view_url_testcase extends advanced_testcase {

    /**
     * Ensure we return the first visible item when get_view_url() is called with section and firstmodule.
     * GIVEN viewing the dashboard
     *   AND the course has started
     * WHEN the click each week
     * THEN they are sent to the first visible module in that section/week.
     */
    public function test_get_view_url_with_section_after_start() {
        global $CFG;

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible',
            'startdate' => time() - 5000,
            'idnumber' => 7000,
            'enablecompletion' => COMPLETION_ENABLED,
            'numsections' => 5
        ));

        $moduledata = array('completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiongradeitemnumber' => 0, // Null to allow manual completion.
            'completionview' => 1,
            'completionexpected' => 1200,
            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);

        $moduledatasection0 = array_merge($moduledata, array('section' => 0));
        $moduledatasection1 = array_merge($moduledata, array('section' => 1));
        $moduledatasection2 = array_merge($moduledata, array('section' => 2));
        $moduledatasection3 = array_merge($moduledata, array('section' => 3));
        $moduledatasection4 = array_merge($moduledata, array('section' => 4));

        // Initial section has a firstlook and a course forum in it.
        $cmfirstlook = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_instance($record, $moduledatasection0);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection0);
        $this->indent_module($cmexcluded);

        // First section has a forum and 3 pages.
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection1);
        $this->indent_module($cmexcluded);
        $cms11 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);
        $cms12 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);
        $cms13 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);

        // Second section has forum and 2 pages.
        $cms21 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection2);
        $cms22 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection2);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection2);
        $this->indent_module($cmexcluded);

        // Third section has a forum and single page, both excluded
        $cms31 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection3);
        $this->indent_module($cms31);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection3);
        $this->indent_module($cmexcluded);

        // Forth section is empty.

        // Now test the progression of module after with section restriction.
        $invisible = course_get_format($course->id);
        $modinfo = get_fast_modinfo($course->id);

        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $invisible->get_view_url(0, array('firstmodule' => true)));
        $this->assertEquals($modinfo->get_cm($cms11->cmid)->url, $invisible->get_view_url(1, array('firstmodule' => true)));
        $this->assertEquals($modinfo->get_cm($cms21->cmid)->url, $invisible->get_view_url(2, array('firstmodule' => true)));
        $this->assertNull($invisible->get_view_url(3, array('firstmodule' => true)), 'There should be no visible modules in this seciton.');
        $this->assertNull($invisible->get_view_url(4, array('firstmodule' => true)), 'There are no modules in this section.');
    }

    /**
     * Ensure we return the first visible item get_view_url() when section and firstmodule are supplied.
     *
     * GIVEN viewing the dashboard
     *   AND the course hasn't started
     *   AND the is no set preview activity
     * WHEN the click each week
     * THEN they are sent to the first visible module in that section/week.
     *
     * GIVEN viewing the dashboard
     *   AND the course hasn't started
     *   AND the is a set preview activity
     * WHEN the click each week
     * THEN they are sent to the preview module.
     */
    public function test_get_view_url_with_section_before_start() {
        global $CFG;

        $CFG->enablecompletion = COMPLETION_ENABLED;

        $this->resetAfterTest(true);
        $course = $this->getDataGenerator()->create_course(array('format' => 'invisible',
            'startdate' => time() + 5000,
            'idnumber' => 7000,
            'enablecompletion' => COMPLETION_ENABLED,
            'numsections' => 5
        ));

        $moduledata = array('completion' => COMPLETION_TRACKING_AUTOMATIC,
            'completiongradeitemnumber' => 0, // Null to allow manual completion.
            'completionview' => 1,
            'completionexpected' => 1200,
            'completiongradeitemnumber' => null,
        );

        // Setup module settings that make it invisible.
        $moduledatainvisible = $moduledata;
        $moduledatainvisible['visible'] = 0;

        $record = array('course' => $course->id);

        $moduledatasection0 = array_merge($moduledata, array('section' => 0));
        $moduledatasection1 = array_merge($moduledata, array('section' => 1));
        $moduledatasection2 = array_merge($moduledata, array('section' => 2));
        $moduledatasection3 = array_merge($moduledata, array('section' => 3));
        $moduledatasection4 = array_merge($moduledata, array('section' => 4));

        // Initial section has a firstlook and a course forum in it.
        $cmfirstlook = $this->getDataGenerator()->get_plugin_generator('mod_wiki')->create_instance($record, $moduledatasection0);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection0);
        $this->indent_module($cmexcluded);

        // First section has a forum and 3 pages.
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection1);
        $this->indent_module($cmexcluded);
        $cms11 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);
        $cms12 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);
        $cms13 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection1);

        // Second section has forum and 2 pages.
        $cms21 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection2);
        $cms22 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection2);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection2);
        $this->indent_module($cmexcluded);

        // Third section has a forum and single page, both excluded
        $cms31 = $this->getDataGenerator()->get_plugin_generator('mod_page')->create_instance($record, $moduledatasection3);
        $this->indent_module($cms31);
        $cmexcluded = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_instance($record, $moduledatasection3);
        $this->indent_module($cmexcluded);

        // Forth section is empty.

        // Now test the progression of module after with section restriction.
        $invisible = course_get_format($course->id);

        $modinfo = get_fast_modinfo($course->id);

        // Without a preview activity, normal behaviour continues.
        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $invisible->get_view_url(0, array('firstmodule' => true)));
        $this->assertEquals($modinfo->get_cm($cms11->cmid)->url, $invisible->get_view_url(1, array('firstmodule' => true)));
        $this->assertEquals($modinfo->get_cm($cms21->cmid)->url, $invisible->get_view_url(2, array('firstmodule' => true)));
        $this->assertNull($invisible->get_view_url(3, array('firstmodule' => true)), 'There should be no visible modules in this seciton.');
        $this->assertNull($invisible->get_view_url(4, array('firstmodule' => true)), 'There are no modules in this section.');

        // Set a preview activity and all items should be sent to preview activity.
        $invisible->update_course_format_options(array('coursepreviewactivity' => $cmfirstlook->cmid));

        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $invisible->get_view_url(0, array('firstmodule' => true)));
        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $invisible->get_view_url(1, array('firstmodule' => true)));
        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $invisible->get_view_url(2, array('firstmodule' => true)));
        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $invisible->get_view_url(3, array('firstmodule' => true)));
        $this->assertEquals($modinfo->get_cm($cmfirstlook->cmid)->url, $invisible->get_view_url(4, array('firstmodule' => true)));
    }

    /**
     * Indent a created module.  Moodle's generator function doesn't handle indent correctly.
     * @param $cm The course module to indent.
     */
    private function indent_module($cm) {
        global $DB;
        // Moodle 2.9 ignores indent as an option when creating plugins, we need to update the database.
        $coursemoduledata = $DB->get_record('course_modules', array('id' => $cm->cmid));
        $coursemoduledata->indent = 5;
        $DB->update_record('course_modules', $coursemoduledata);
        rebuild_course_cache($cm->course);
    }
}
