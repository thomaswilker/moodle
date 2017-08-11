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
 * Class for exporting users capabilities for a database module instance.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data\external;
defined('MOODLE_INTERNAL') || die();

use core\external\exporter;
use renderer_base;
use core_user;

/**
 * Class for exporting users capabilities for a database module instance.
 *
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capability_exporter extends exporter {

    protected static function define_related() {
        return [
            'context' => 'context'
        ];
    }

    protected static function define_other_properties() {
        return array(
            'canview' => [
                'type' => PARAM_BOOL,
                'description' => 'User can view.',
            ],
            'canexportuserinfo' => [
                'type' => PARAM_BOOL,
                'description' => 'User can export the user info.',
            ],
            'canexportallentries' => [
                'type' => PARAM_BOOL,
                'description' => 'User can export all the entries.',
            ],
            'canexportownentry' => [
                'type' => PARAM_BOOL,
                'description' => 'User can export their own entries.',
            ],
            'canmanageuserpresets' => [
                'type' => PARAM_BOOL,
                'description' => 'User can manage their own presets.',
            ],
            'canviewalluserpresets' => [
                'type' => PARAM_BOOL,
                'description' => 'User can view presets for all users.',
            ],
            'canmanagetemplates' => [
                'type' => PARAM_BOOL,
                'description' => 'User can manage templates.',
            ],
            'canmanagecomments' => [
                'type' => PARAM_BOOL,
                'description' => 'User can manage comments.',
            ],
            'canmanageentries' => [
                'type' => PARAM_BOOL,
                'description' => 'User can manage entries.',
            ],
            'canapprove' => [
                'type' => PARAM_BOOL,
                'description' => 'User can approve entries.',
            ],
            'canviewallratings' => [
                'type' => PARAM_BOOL,
                'description' => 'User can view all ratings.',
            ],
            'canviewanyrating' => [
                'type' => PARAM_BOOL,
                'description' => 'User can view any rating.',
            ],
            'canviewrating' => [
                'type' => PARAM_BOOL,
                'description' => 'User can view ratings.',
            ],
            'canrate' => [
                'type' => PARAM_BOOL,
                'description' => 'User can rate.',
            ],
            'cancomment' => [
                'type' => PARAM_BOOL,
                'description' => 'User can comment.',
            ],
            'canwriteentry' => [
                'type' => PARAM_BOOL,
                'description' => 'User write an entry.',
            ],
            'canviewentry' => [
                'type' => PARAM_BOOL,
                'description' => 'User view an entry.',
            ],
        );
    }

    protected function get_other_values(renderer_base $output) {
        return (array) \mod_data\api::get_capabilities($this->related['context']);
    }
}
