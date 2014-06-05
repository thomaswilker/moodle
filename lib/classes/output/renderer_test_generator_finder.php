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
 * Helper class that will find all the renderer_test_generated classes
 * listed by component.
 *
 * @package    core
 * @category   output
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\output;

/**
 * Implementation of renderer_test_generator_base for core renderers.
 *
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer_test_generator_finder {

    /** @var array $cache Cache of plugins -> renderer_test_generator mappings */
    private $cache = array();

    /**
     * Generate a list of renderer_test_generator classes for every plugin in Moodle and cache it.
     */
    private function fill_cache() {
        if (empty($this->cache)) {
            // Add the core one.
            $this->cache['core'] = new \core\output\renderer_test_generator();

            // Now all the plugins.
            $types = \core_component::get_plugin_types();
            foreach ($types as $type => $path) {
                // Try namespaced version first.
                $plugins = \core_component::get_plugin_list_with_class($type, 'output\\renderer_test_generator');

                foreach ($plugins as $component => $classname) {
                    $generator = new $classname();
                    if ($generator instanceof renderer_test_generator_base) {
                        $this->cache[$component] = $generator;
                    }
                }

                if (empty($plugins)) {
                    // Try non-namespaced version.
                    $plugins = \core_component::get_plugin_list_with_class($type, 'output_renderer_test_generator');

                    foreach ($plugins as $component => $classname) {
                        $generator = new $classname();
                        if ($generator instanceof renderer_test_generator_base) {
                            $this->cache[$component] = new $classname();
                        }
                    }
                }
            }
        }
    }

    /**
     * Generate a list of renderer_test_generator classes.
     *
     * @return renderer_test_generator[] Array of renderer_test_generator classes indexed by component.
     */
    public function find_all_generators() {
        $this->fill_cache();

        return $this->cache;
    }

    /**
     * Generate a list of renderer_test_generator classes.
     *
     * @return renderer_test_generator|bool Renderer generator class for a given plugin (or false).
     */
    public function find_generator($component = 'core') {
        $this->fill_cache();

        if (isset($this->cache[$component])) {
            return $this->cache[$component];
        }

        // No generator for this component.
        return false;
    }
}
