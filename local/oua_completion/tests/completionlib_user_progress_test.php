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
 * Completion tests
 *
 * @package    local_oua_completion\\oua_completion_info
 * @category   phpunit
 * @copyright  2013 Russell Smith, Catalyst IT.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/oua_completion/classes/oua_completion_info.php');


class ouacompletioninfo_userprogress_testcase extends basic_testcase {

    protected $realdb;
    protected $realcfg;
    protected $realsession;
    protected $realuser;

    protected $modinfo; // Mocked modinfo data
    protected $modinfomap; // Parameters to be called for get_data mock calls.
    protected $complete; // Store a completion state of completed items.
    protected $incomplete; // Store a completion state of incompleted items.

    protected function setUp() {
        global $DB, $CFG, $SESSION, $USER;
        parent::setUp();

        $this->realdb = $DB;
        $this->realcfg = $CFG;
        $this->realsession = $SESSION;
        $this->prevuser = $USER;

        $DB = $this->getMock(get_class($DB));
        $CFG = clone($this->realcfg);
        $CFG->prefix = 'test_';
        $CFG->enablecompletion = COMPLETION_ENABLED;
        $SESSION = new stdClass();
        $USER = (object)array('id' => 314159);

        // Mock out get_data to ensure it returns the expected results when calculating our users progression.
        // This requires completion states to be included.
        $this->complete = new stdClass();
        $this->complete->completionstate = COMPLETION_COMPLETE;
        $this->incomplete = new stdClass();
        $this->incomplete->completionstate = COMPLETION_INCOMPLETE;

    }

    protected function tearDown() {
        global $DB, $CFG, $SESSION, $USER;
        $DB = $this->realdb;
        $CFG = $this->realcfg;
        $SESSION = $this->realsession;
        $USER = $this->prevuser;

        parent::tearDown();
    }

    protected function setup_user_progress() {
        global $DB;

        // Call constructor with a course object that has an id in it.  We only need a minimal
        // information about a course as information used is mocked out.
        $coursestub = new stdClass();
        $coursestub->id = 0;

        $ouacompletion = $this->getMock('local_oua_completion\\oua_completion_info', array('get_data', 'get_fast_modinfo'), array($coursestub), '', true);

        // Database calls as in load_overrides should return empty.
        $DB->expects($this->any())
           ->method('get_recordset_sql')
           ->will($this->returnValue(new ouacompletion_user_progress_test_fake_recordset(array())));

        $modinfo = $this->getMock('course_modinfo', array(), array(), '', false);
        $modinfo->expects($this->any())
                ->method('get_cms')
                ->will($this->returnValue($this->modinfodata));

        // Setup get_fast_modinfo to be mocked out of the way by returning our preconfigured set of get_cms data.
        $ouacompletion->expects($this->any())
                      ->method('get_fast_modinfo')
                      ->will($this->returnValue($modinfo));

        return $ouacompletion;
    }

    /**
     * Function to handle the get_data responses based on input arguements.
     *
     * @return mixed Returns the last value of the relevant $this->modinfomap array element.
     */
    public function get_data_provider() {
        $args = func_get_args();
        foreach ($this->modinfomap as $tuple) {
            $match = true;
            $response = array_pop($tuple);
            foreach ($tuple as $key => $param) {
                if (!($args[$key] == $param)) {
                    $match = false;
                    break;
                }
            }

            // If all the values match, return the response.
            if ($match) {
                return $response;
            }
        }
        // If we didn't fina a match, we aren't running the test properly.
        $this->fail('There was an unhandled call to get_data\'s mock. Data sent: '.var_export($args)."\n");
    }

    /**
     * Test 0 percent returned when course not completed. Include an untracked item.
     *
     * @covers local_oua_completion\\oua_completion_info::get_user_progress()
     */
    public function test_get_user_progress_course_0() {
        global $DB;

        // Mock course_modinfo and return it from any calls to get_fast_modinfo in the mock.
        $this->modinfodata = array((object)array('id' => 1000, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_NONE, 'visible' => true),
                                   (object)array('id' => 1004, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1008, 'modname' => 'mailassess', 'completion' => COMPLETION_TRACKING_NONE, 'visible' => true),
                                   (object)array('id' => 1012, 'modname' => 'mailassess', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true));

        $ouacompletion = $this->setup_user_progress();

        $this->modinfomap = array(array($this->modinfodata[0], true, 1, $this->complete),
                                  array($this->modinfodata[1], true, 1, $this->incomplete),
                                  array($this->modinfodata[2], true, 1, $this->complete),
                                  array($this->modinfodata[3], true, 1, $this->incomplete));

        $ouacompletion->expects($this->any())
                      ->method('get_data')
                      ->will($this->returnCallback(array($this, 'get_data_provider')));

        $this->assertEquals(0, $ouacompletion->get_user_progress(1));
    }

    /**
     * Test 50 percent returned when course is 1/2 completed.
     *
     * @covers local_oua_completion\\oua_completion_info::get_user_progress()
     */
    public function test_get_user_progress_course_50() {
        global $DB;

        // Mock course_modinfo and return it from any calls to get_fast_modinfo in the mock.
        $this->modinfodata = array((object)array('id' => 1000, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1004, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1008, 'modname' => 'mailassess', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1012, 'modname' => 'mailassess', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true));

        $ouacompletion = $this->setup_user_progress();

        $this->modinfomap = array(array($this->modinfodata[0], true, 1, $this->complete),
                                  array($this->modinfodata[1], true, 1, $this->incomplete),
                                  array($this->modinfodata[2], true, 1, $this->complete),
                                  array($this->modinfodata[3], true, 1, $this->incomplete));

        $ouacompletion->expects($this->any())
                      ->method('get_data')
                      ->will($this->returnCallback(array($this, 'get_data_provider')));

        $this->assertEquals(50, $ouacompletion->get_user_progress(1));
    }

    /**
     * Test 75 percent returned when course is 3/4 completed.
     *
     * @covers local_oua_completion\\oua_completion_info::get_user_progress()
     */
    public function test_get_user_progress_course_75() {
        global $DB;

        // Mock course_modinfo and return it from any calls to get_fast_modinfo in the mock.
        $this->modinfodata = array((object)array('id' => 1000, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1004, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1008, 'modname' => 'mailassess', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1012, 'modname' => 'mailassess', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true));

        $ouacompletion = $this->setup_user_progress();

        $this->modinfomap = array(array($this->modinfodata[0], true, 1, $this->complete),
                                  array($this->modinfodata[1], true, 1, $this->complete),
                                  array($this->modinfodata[2], true, 1, $this->complete),
                                  array($this->modinfodata[3], true, 1, $this->incomplete));

        $ouacompletion->expects($this->any())
                      ->method('get_data')
                      ->will($this->returnCallback(array($this, 'get_data_provider')));

        $this->assertEquals(75, $ouacompletion->get_user_progress(1));
    }

    /**
     * Test 100 percent returned when course has all activities complete, even without course completion.
     *
     * @covers local_oua_completion\\oua_completion_info::get_user_progress()
     */
    public function test_get_user_progress_course_100() {
        global $DB;

        // Mock course_modinfo and return it from any calls to get_fast_modinfo in the mock.
        $this->modinfodata = array((object)array('id' => 1000, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1004, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1008, 'modname' => 'mailassess', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1012, 'modname' => 'mailassess', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true));

        $ouacompletion = $this->setup_user_progress();

        $this->modinfomap = array(array($this->modinfodata[0], true, 1, $this->complete),
                                  array($this->modinfodata[1], true, 1, $this->complete),
                                  array($this->modinfodata[2], true, 1, $this->complete),
                                  array($this->modinfodata[3], true, 1, $this->complete));

        $ouacompletion->expects($this->any())
                      ->method('get_data')
                      ->will($this->returnCallback(array($this, 'get_data_provider')));

        $this->assertEquals(100, $ouacompletion->get_user_progress(1));
    }

    /**
     * Test 100 percent returned when course has all VISIBLE activities complete.
     *
     * @covers local_oua_completion\\oua_completion_info::get_user_progress()
     */
    public function test_get_user_progress_course_125_visible() {
        global $DB;

        // Mock course_modinfo and return it from any calls to get_fast_modinfo in the mock.
        $this->modinfodata = array((object)array('id' => 1000, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => false),
                                   (object)array('id' => 1004, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1002, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1001, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1005, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1007, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1008, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1009, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1010, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => true),
                                   (object)array('id' => 1003, 'modname' => 'quiz', 'completion' => COMPLETION_TRACKING_AUTOMATIC, 'visible' => false));

        $ouacompletion = $this->setup_user_progress();

        // We are 12.5% complete here.
        $this->modinfomap = array(array($this->modinfodata[0], true, 1, $this->incomplete),
                                  array($this->modinfodata[1], true, 1, $this->incomplete),
                                  array($this->modinfodata[2], true, 1, $this->incomplete),
                                  array($this->modinfodata[3], true, 1, $this->complete),
                                  array($this->modinfodata[4], true, 1, $this->incomplete),
                                  array($this->modinfodata[5], true, 1, $this->incomplete),
                                  array($this->modinfodata[6], true, 1, $this->incomplete),
                                  array($this->modinfodata[7], true, 1, $this->incomplete),
                                  array($this->modinfodata[8], true, 1, $this->incomplete),
                                  array($this->modinfodata[9], true, 1, $this->complete));

        $ouacompletion->expects($this->any())
                      ->method('get_data')
                      ->will($this->returnCallback(array($this, 'get_data_provider')));

        $this->assertEquals(12.5, $ouacompletion->get_user_progress(1));
    }
}


class ouacompletion_user_progress_test_fake_recordset implements Iterator {
    var $closed;
    var $values, $index;

    function __construct($values) {
        $this->values = $values;
        $this->index = 0;
    }

    function current() {
        return $this->values[$this->index];
    }

    function key() {
        return $this->values[$this->index];
    }

    function next() {
        $this->index++;
    }

    function rewind() {
        $this->index = 0;
    }

    function valid() {
        return count($this->values) > $this->index;
    }

    function close() {
        $this->closed = true;
    }

    function was_closed() {
        return $this->closed;
    }
}
