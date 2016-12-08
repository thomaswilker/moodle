<?php

/**
 * PHPUnit data generator tests
 *
 * @package    block_oua_help_tour
 * @category   phpunit
 * @copyright  2015 Open Universities Australia
 */
global $CFG;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/oua_utility/oua_advanced_testcase.php');
require_once($CFG->libdir . '/externallib.php');
use block_oua_help_tour\external;

/**
 * PHPUnit data generator testcase
 *
 * @package    block_oua_help_tour
 * @category   phpunit
 * @copyright  2016 Open Universities Australia
 */
class block_oua_help_tour_testcase extends oua_advanced_testcase {
    /**
     * Ensure javascript is included on page, when config is set.
     */
    public function test_javascript_inclusion() {
        global $PAGE;

        $this->resetAfterTest(true);

        $block = $this->getDataGenerator()->create_block('oua_help_tour');
        $block = block_instance('oua_help_tour', $block);
        $block->config = new stdClass();
        $block->config->tourinstance = 'help_tour_dashboard';
        $block->refresh_content();
        $reflectedrequires = new ReflectionObject($PAGE->requires);
        $amdjs = $reflectedrequires->getProperty('amdjscode');
        $amdjs->setAccessible(true);
        $requirejs = $amdjs->getValue($PAGE->requires);
        $javascriptfound = false;
        foreach ($requirejs as $jsstring) {
            if (strpos($jsstring, 'block_oua_help_tour') !== false) {
                $javascriptfound = true;
                break;
            }
        }

        $this->assertTrue($javascriptfound);
    }

    /**
     * Test the api function called via javascript correctly saves user preference to DB.
     * @throws coding_exception
     */
    public function test_disable_tour_user_preferences() {
        global $DB, $USER;
        $this->resetAfterTest(true);

        self::setAdminUser(); // Must set user for user prefs to save to DB.
        // Create block instance.
        $block = $this->getDataGenerator()->create_block('oua_help_tour');
        $block = block_instance('oua_help_tour', $block);

        // Configure block instance.
        $block->config = new stdClass();
        $tourname = 'help_tour_dashboard';
        $block->config->tourinstance = $tourname;
        $block->refresh_content();

        $externalapi = new external();
        // Send api request to disable tour
        $externalapi::disable_help_tour($tourname);
        $records = $DB->get_records('user_preferences', array('name' => "block_oua_{$tourname}_disabled"));
        $this->assertCount(1, $records, "Tour should be disabled in user preferences");
        $pref = reset($records);
        $this->assertEquals(1, $pref->value, "Disabled for tour should be set to 1");
        $userprefdisabled = get_user_preferences("block_oua_{$tourname}_disabled", false);
        $this->assertEquals(true, $userprefdisabled,
                            "User preference returned via get_user+preferences should return true (disabled)");

        // Test that saving the config item clears the user preference.
        $data = new stdClass();
        $data->resetthistour = 1;
        $data->tourinstance = $tourname;
        $block->instance_config_save($data);
        $records = $DB->get_records('user_preferences', array('name' => "block_oua_{$tourname}_disabled"));
        $this->assertCount(0, $records, "User Preference for disabling tour should be cleared");
    }
}
