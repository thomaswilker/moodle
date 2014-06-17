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
 * Sample executor for element library including function tracing.
 *
 * @package    tool_elementlibrary
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_elementlibrary;
defined('MOODLE_INTERNAL') || die();

/**
 * Sample executor class
 *
 * @package    tool_elementlibrary
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sample_executor {

    /**
     * Execute the renderable_sample in a harness so we can trace the renderables in use.
     *
     * @return array containing 'output' - string generated
     *                          'warnings' - array of warnings
     *                          'renderers' - array of renderers in use (with sub keys for classname and methodname)
     *                          'renderables' - array of renderables in use (with sub key for classname)
     */
    public static function execute_sample(\core\output\renderer_sample_base $sample) {
        global $CFG;

        $output = '';
        $warnings = array();
        $renderers = array();
        $renderables = array();

        $tracestr = '';

        // Start tracing output - this is to get the call stack of renderers used.
        if (function_exists('xdebug_start_trace')) {
            // Use locking to prevent concurrent traces.
            $lockfactory = \core\lock\lock_config::get_lock_factory('tool_elementlibrary');
            $lock = $lockfactory->get_lock('trace', 30);

            // Make a temp dir to store the trace file.
            $tracedir = make_temp_directory('tool_elementlibrary');
            $tracefile = $tracedir . '/trace';

            // Force xdebug settings so we get consistent output.
            ini_set('xdebug.trace_format', 0);
            ini_set('xdebug.show_exception_trace', 0);
            ini_set('xdebug.collect_assignments', 0);
            ini_set('xdebug.collect_includes', 0);
            ini_set('xdebug.collect_params', 0);
            ini_set('xdebug.collect_return', 0);
            ini_set('xdebug.collect_vars', 0);
            ini_set('xdebug.coverage_enable', 0);
            ini_set('xdebug.scream', 0);
            ini_set('xdebug.show_local_vars', 0);
            ini_set('xdebug.show_mem_delta', 0);

            // Start tracing.
            xdebug_start_trace($tracefile);
            $output = $sample->execute();
            xdebug_stop_trace();

            // Parse the trace file to get a list of renderer methods used.
            $tracefile .= '.xt';
            $tracestr = file_get_contents($tracefile);
            remove_dir($tracedir);

            // Release the lock.
            $lock->release();
        } else {
            $warnings[] = get_string('noxdebug', 'tool_elementlibrary');
            $output = $sample->execute();
        }

        if ($tracestr) {
            $lines = explode("\n", $tracestr);
            foreach ($lines as $line) {
                if (strpos($line, 'TRACE') !== false) {
                    continue;
                }
                $line = preg_replace('/ +/', ' ', $line);
                // Discard the first part of the line.
                $parts = explode(' ', $line);
                if (count($parts) < 6) {
                    // Unrecognised line format.
                    continue;
                }
                $callable = $parts[4];
                $location = $parts[5];

                $location = str_replace($CFG->dirroot, '', $location);

                $split = strpos($callable, '::');
                if ($split === false) {
                    $split = strpos($callable, '->');
                }
                if ($split === false) {
                    // Not a class method.
                    continue;
                }
                $classname = substr($callable, 0, $split);
                $methodname = substr($callable, $split + 2);

                if (is_subclass_of($classname, 'renderer_base')) {
                    $renderers[] = array('classname' => $classname, 'methodname' => $methodname, 'location' => $location);
                } else {
                    $implements = class_implements($classname);
                    if (array_search('renderable', $implements) !== false) {
                        $renderables[] = array('classname' => $classname, 'location' => $location);
                    }
                }
            }
        }

        return array('output' => $output,
                     'warnings' => $warnings,
                     'renderers' => $renderers,
                     'renderables' => $renderables);
    }

}
