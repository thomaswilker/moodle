<?php
/**
 * Adds a help tour where the block is added.
 *
 * @package    block_oua_help_tour
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */

use block_oua_help_tour\output\oua_help_tour;

require_once($CFG->dirroot . '/blocks/oua_help_tour/lib.php');

class block_oua_help_tour extends block_base {

    public function init() {
        $this->title = get_string('oua_help_tour', 'block_oua_help_tour');
    }

    public function instance_allow_multiple() {
        return true;
    }

    /**
     * Custom for each tour instance
     * Allows to reset users tour, but dont save the setting
     *
     * @param $data
     * @param bool $nolongerused
     */
    public function instance_config_save($data, $nolongerused = false) {
        global $DB;
        if (isset($data->resetthistour) && $data->resetthistour == 1) {
            unset($data->resetthistour); // This value isn't stored, if it is set, always force it to 0.
            if (isset($data->tourinstance) && !empty($data->tourinstance)) {
                // Reset was 1, tour instance is set, update the value for all users.
                // Reset the user preference for all users
                // Get Users whose preferences will change.
                $users = $DB->get_records('user_preferences', array('name' => "block_oua_{$data->tourinstance}_disabled"));
                // Delete preferences.
                $DB->delete_records('user_preferences', array('name' => "block_oua_{$data->tourinstance}_disabled"));
                foreach ($users as $user) {
                    mark_user_preferences_changed($user->id);
                }
            }
        }
        parent::instance_config_save($data, $nolongerused);
    }

    public function get_content() {
        global $PAGE, $SESSION;

        if ($this->content !== null) {
            return $this->content;
        }
        if (empty($this->config)) {
            $this->config = new stdClass();
        }

        $this->content = new stdClass;
        $this->content->header = '';
        $this->content->text = '';
        $this->content->footer = '';

        if (empty($this->config->tourinstance)) {
            // If not configured or empty, then just return.
            $this->title .= " - " . get_string('not_configured', 'block_oua_help_tour');
            return $this->content;
        }

        $tourname = $this->config->tourinstance;
        $this->title .= " ({$tourname})";
        $forceenabletour = optional_param('tour', false, PARAM_BOOL);
        if ($forceenabletour) {
            // Global force enable for all blocks.
            $tourdisabled = false;
            set_user_preference("block_oua_{$tourname}_disabled", false);
            unset($SESSION->{"block_oua_{$tourname}_disabled"});
        } else {
            $tourdisabled = get_user_preferences("block_oua_{$tourname}_disabled", false);
        }

        if ($tourdisabled || isset($SESSION->{"block_oua_{$tourname}_disabled"})) {
            // User has disabled this tour.
            return $this->content;
        }
        $params = array();
        $PAGE->requires->js_call_amd("block_oua_help_tour/{$tourname}", 'initialise', $params);

        return $this->content;
    }

    function hide_header() {
        if ($this->page->user_is_editing()) {
            return false;
        }
        return true;
    }

}
