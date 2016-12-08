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
class mod_brightcove_addupdatedelete_testcase extends advanced_testcase {
    public function test_add() {
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

    public function test_update() {
        global $DB, $SITE;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_brightcove');
        $this->assertInstanceOf('mod_brightcove_generator', $generator);
        $this->assertEquals('brightcove', $generator->get_modulename());

        $brightcove1 = $generator->create_instance(array('course' => $SITE->id));
        $brightcove2 = $generator->create_instance(array('course'   => $SITE->id,
                                                         'playerid' => 'example-player-id',
                                                         'videoid'  => 'example-video-id'));
        $this->assertEquals(2, $DB->count_records('brightcove'));
        $instance = $DB->get_record('brightcove', array('id' => $brightcove2->id));
        $this->assertEquals('example-player-id', $instance->playerid);
        $this->assertEquals('example-video-id', $instance->videoid);
        $instance->playerid = 'example2-player-id';
        $instance->videoid = 'example2-video-id';

        // Update instance to behave the way the form does when submitting to this function.
        $instance->instance = $instance->id;

        brightcove_update_instance($instance);
        $instance = $DB->get_record('brightcove', array('id' => $brightcove2->id));
        $this->assertEquals('example2-player-id', $instance->playerid);
        $this->assertEquals('example2-video-id', $instance->videoid);

        $instance = $DB->get_record('brightcove', array('id' => $brightcove1->id));
        $this->assertNotEquals('example2-player-id', $instance->playerid);
        $this->assertNotEquals('example2-video-id', $instance->videoid);
    }

    public function test_delete() {
        global $DB, $SITE;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_brightcove');
        $this->assertInstanceOf('mod_brightcove_generator', $generator);
        $this->assertEquals('brightcove', $generator->get_modulename());

        $brightcove1 = $generator->create_instance(array('course' => $SITE->id));
        $brightcove2 = $generator->create_instance(array('course'   => $SITE->id,
                                                         'playerid' => 'example-player-id',
                                                         'videoid'  => 'example-video-id'));
        $this->assertEquals(2, $DB->count_records('brightcove'));

        brightcove_delete_instance($brightcove1->id);
        $this->assertEquals(1, $DB->count_records('brightcove'));
        $instance = $DB->get_record('brightcove', array('id' => $brightcove2->id));
        $this->assertEquals('example-player-id', $instance->playerid);
        $this->assertEquals('example-video-id', $instance->videoid);
    }

    public function test_activity_duplicates_correctly() {
        global $DB, $SITE;

        $this->resetAfterTest();

        // Ensure we are admin to allow backup and restore capabilities.
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('mod_brightcove');
        $brightcove = $generator->create_instance(array('course'   => $SITE->id,
                                                        'playerid' => 'example-player-id',
                                                        'videoid'  => 'example-video-id'));

        $cm = get_coursemodule_from_id('brightcove', $brightcove->cmid);
        $duplicate = mod_duplicate_activity($SITE, $cm);

        $newcm = get_coursemodule_from_id('brightcove', $duplicate->cmid);

        $newinstance = $DB->get_record('brightcove', array('id' => $newcm->instance));

        $this->assertNotEquals($brightcove->id, $newinstance->id, 'Instance must be copied to a new one.');
        $this->assertNotEquals($brightcove->cmid, $newcm->id, 'Must have created a new course module.');

        // Unset differences and ensure they are the same.
        unset($brightcove->id);
        unset($brightcove->cmid);
        unset($newinstance->id);

        $this->assertEquals($brightcove, $newinstance);
    }
}