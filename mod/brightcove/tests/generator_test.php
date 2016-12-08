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
 * PHPUnit data generator tests
 *
 * @package    mod_brightcove
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * PHPUnit data generator testcase
 *
 * @package    mod_brightcove
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_brightcove_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB, $SITE;

        $this->resetAfterTest(true);

        $this->assertEquals(0, $DB->count_records('brightcove'));

        /** @var mod_brightcove_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_brightcove');
        $this->assertInstanceOf('mod_brightcove_generator', $generator);
        $this->assertEquals('brightcove', $generator->get_modulename());

        $generator->create_instance(array('course' => $SITE->id));
        $generator->create_instance(array('course' => $SITE->id));
        $brightcove = $generator->create_instance(array('course'   => $SITE->id,
                                                        'playerid' => 'example-player-id',
                                                        'videoid'  => 'example-video-id'));
        $this->assertEquals(3, $DB->count_records('brightcove'));

        $cm = get_coursemodule_from_instance('brightcove', $brightcove->id);
        $this->assertEquals($brightcove->id, $cm->instance);
        $this->assertEquals('brightcove', $cm->modname);
        $this->assertEquals($SITE->id, $cm->course);

        $instance = $DB->get_record('brightcove', array('id' => $cm->instance));
        $this->assertEquals('example-player-id', $instance->playerid);
        $this->assertEquals('example-video-id', $instance->videoid);

        $context = context_module::instance($cm->id);
        $this->assertEquals($brightcove->cmid, $context->instanceid);
    }
}
