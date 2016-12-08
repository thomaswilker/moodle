<?php

/**
 * PHPUnit data generator tests
 *
 * @package    block_oua_course_progress_bar
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */

defined('MOODLE_INTERNAL') || die();


/**
 * PHPUnit data generator testcase
 *
 * @package    block_oua_course_progress_bar
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */
class block_oua_course_progress_bar_generator_testcase extends advanced_testcase {
    public function test_generator() {
        global $DB;

        $this->resetAfterTest(true);

        $beforeblocks = $DB->count_records('block_instances');

        /** @var block_oua_course_progress_bar $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('block_oua_course_progress_bar');
        $this->assertInstanceOf('block_oua_course_progress_bar_generator', $generator);
        $this->assertEquals('oua_course_progress_bar', $generator->get_blockname());

        $generator->create_instance();
        $generator->create_instance();
        $generator->create_instance();
        $this->assertEquals($beforeblocks+3, $DB->count_records('block_instances'));

    }
}