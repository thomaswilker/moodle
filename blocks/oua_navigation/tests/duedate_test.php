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
 * Unit tests for (some of) mod/quiz/editlib.php.
 *
 * @package    block_oua_navigation
 * @category   phpunit
 * @copyright  2014 Russell Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/blocks/moodleblock.class.php');
require_once($CFG->dirroot.'/blocks/oua_navigation/block_oua_navigation.php');

/**
 * Unit tests for due dates on the navigation block.
 *
 * @copyright  2014 Russell Smith
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_oua_navigation_duedates_testcase extends advanced_testcase {

    static function setAdminUser() {
        global $USER;

        parent::setAdminUser();

        $USER->email    = 'admin@example.com';
        $USER->country  = 'AU';
        $USER->city     = 'Sydney';
    }

    public function test_nonquiz() {
        $navigation = new block_oua_navigation();
        $cm = new stdClass();
        $cm->instance = 0;
        $cm->modname = 'page';
        $this->assertEmpty($navigation->get_due_date_display($cm, 0), 'Non-quizzes always return empty string');
    }


    public function test_normaldates() {
        global $SITE;

        $this->resetAfterTest(true);
        self::setAdminUser();

        $quizdata['timeclose'] = 10000;
        $quizdata['course'] = $SITE->id;
        $duestring =  userdate($quizdata['timeclose'], "%d %B");
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance($quizdata);
        $user = $this->getDataGenerator()->create_user();

        $navigation = new block_oua_navigation();

        $cm = new stdClass();
        $cm->instance = $quiz->id;
        $cm->modname = 'quiz';
        self::setUser($user->id);
        $this->assertEquals($duestring, $navigation->get_due_date_display($cm, $user->id));

    }

    public function test_overrides() {
        global $DB, $SITE;

        $this->resetAfterTest(true);
        self::setAdminUser();

        $quizdata['timeclose'] = 10000;
        $quizdata['course'] = $SITE->id;
        $duestring = userdate($quizdata['timeclose'], "%d %B");
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance($quizdata);
        $user = $this->getDataGenerator()->create_user();

        $navigation = new block_oua_navigation();

        $cm = new stdClass();
        $cm->instance = $quiz->id;
        $cm->modname = 'quiz';
        self::setUser($user->id);
        $this->assertEquals($duestring, $navigation->get_due_date_display($cm, $user->id));

        // Insert override for the quiz item.
        $DB->insert_record('quiz_overrides', array('quiz' => $quiz->id, 'userid' => $user->id, 'timeclose' => 11000));

        // Due date should be the new one.
        $duestring = userdate(11000, "%d %B");
        self::setUser($user->id);
        $this->assertEquals($duestring, $navigation->get_due_date_display($cm, $user->id));
    }
}
