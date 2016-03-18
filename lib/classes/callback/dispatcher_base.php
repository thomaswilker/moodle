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
 * Dispatcher class.
 *
 * @package    core
 * @copyright  2014 Petr Skoda {@link http://skodak.org}
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\callback;

defined('MOODLE_INTERNAL') || die();

/**
 * Provide consistent API for inter-component communication.
 *
 * The concepts used here are a "callback" (contains modifiable data).
 * The invoker (The calling code)
 * The receiver (The callback executed in response to the callback)
 *
 * Valid callbacks must be registered in the callbacks array in lib/db/callbacks.php
 * Plugins are forbidden from registering their own callbacks. All inter-plugin
 * communication must go through a core API.
 *
 * Receivers are registered in the receivers array in db/callbacks.php. Any plugin or core may
 * register receivers.
 *
 * @package    core
 * @copyright  2014 Petr Skoda
 * @copyright  2016 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class dispatcher_base {

    /** @var array cache of all receivers */
    protected $allreceivers = null;

    /** @var array cache of all dispatchables */
    protected $alldispatchables = null;

    /** @var bool should we reload receivers after the test? */
    protected $reloadaftertest = false;

    /** @var dispatcher_base Singleton instance per sub-class */
    protected static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return dispatcher_base The instance.
     */
    public static function instance()
    {
        if (!(static::$instance instanceof static)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Private constructor to prevent creating a new instance this class without using instance().
     */
    private function __construct()
    {
    }

    /**
     * Private clone to prevent creating a new instance this class without using instance().
     */
    private function __clone()
    {
    }

    /**
     * Private wakeup to prevent creating a new instance this class without using instance().
     */
    private function __wakeup()
    {
    }

    /**
     * Dispatch to all registered receivers.
     *
     * @param dispatchable $dispatchable
     * @param string $componentname when specified the callback is executed only for specific component or plugin
     * @param bool $throwexceptions if set to false (default) all exceptions during callbacks executions will be
     *      converted to debugging messages and will not prevent further execution of other callbacks
     * @return dispatchable returns the dispatchable instance to allow chaining
     */
    public function dispatch(dispatchable $dispatchable, $componentname = null, $throwexceptions = false) {
        global $CFG;

        if (during_initial_install()) {
            return $dispatchable;
        }
        if ($CFG->debugdeveloper) {
            $this->validate($dispatchable);
        }
        $this->init_all_receivers();

        $key = $dispatchable->get_key();
        if (!isset($this->allreceivers[$key])) {
            return $dispatchable;
        }

        if ($componentname !== null) {
            $componentname = \core_component::normalize_componentname($componentname);
        }

        foreach ($this->allreceivers[$key] as $receiver) {
            if ($componentname !== null && $receiver->component !== $componentname) {
                continue;
            }
            if (isset($receiver->includefile) and file_exists($receiver->includefile)) {
                include_once($receiver->includefile);
            }
            if (is_callable($receiver->callable)) {
                if ($throwexceptions) {
                    call_user_func($receiver->callable, $dispatchable->get_arguments());
                } else {
                    try {
                        call_user_func($receiver->callable, $dispatchable->get_arguments());
                    } catch (\Exception $e) {
                        debugging("Exception encountered in receiver '" . $receiver->callable . "': " .
                            $e->getMessage(), DEBUG_DEVELOPER, $e->getTrace());
                    }
               }
            } else {
                debugging("Cannot dispatch to receiver '" . $receiver->callable . "'");
            }
        }

        return $dispatchable;
    }

    /**
     * Initialise the list of receivers.
     */
    protected function init_all_receivers() {
        global $CFG;

        if (is_array($this->allreceivers)) {
            return;
        }

        $cache = null;
        if (!PHPUNIT_TEST and !during_initial_install()) {
            $cache = \cache::make('core', $this->get_cache_name());
            $cached = $cache->get('all');
            $dispatchables = $cache->get('dispatchables');
            $dirroot = $cache->get('dirroot');
            if ($dirroot === $CFG->dirroot and is_array($cached)) {
                $this->allreceivers = $cached;
                $this->alldispatchables = $dispatchables;
                return;
            }
        }

        $this->allreceivers = array();
        $this->add_component_receivers('core', $CFG->dirroot . '/lib');

        $plugintypes = \core_component::get_plugin_types();
        foreach ($plugintypes as $plugintype => $ignored) {
            $plugins = \core_component::get_plugin_list($plugintype);

            foreach ($plugins as $pluginname => $fulldir) {
                $this->add_component_receivers($plugintype . '_' . $pluginname, $fulldir);
            }
        }

        $this->order_all_receivers();

        if (!PHPUNIT_TEST and !during_initial_install()) {
            $cache->set('all', $this->allreceivers);
            $cache->set('dispatchables', $this->dispatchables);
            $cache->set('dirroot', $CFG->dirroot);
        }
    }

    /**
     * Read receivers from file in db/$registrationfile.php in the component and add them.
     *
     * @param string $componentname
     * @param string $fulldir
     */
    protected function add_component_receivers($componentname, $fulldir) {
        $file = $fulldir . $this->get_registration_file_name();
        if (!file_exists($file)) {
            return;
        }

        $receivername = $this->get_receiver_array_name();
        $dispatchablename = $this->get_dispatchable_array_name();
        $$receivername = null;
        $$dispatchablename = null;
        include($file);

        $receiverarray = $$receivername;
        $dispatchables = $$dispatchablename;

        if (is_array($dispatchables)) {
            // We remember the list of all registrations.
            $this->add_dispatchables($dispatchables, $file, $componentname);
        }

        if (!is_array($receiverarray)) {
            return;
        }

        $this->add_receivers($receiverarray, $file, $componentname);
    }

    /**
     * Add dispatchables (only in debugging mode).
     * @param array $dispatchables (string keys only)
     * @param string $file
     * @param string $componentname
     */
    protected function add_dispatchables(array $dispatchables, $file, $componentname) {
        foreach ($dispatchables as $dispatchablekey) {
            $this->dispatchables[$dispatchablekey] = $componentname;
        }
    }

    /**
     * Add receivers.
     * @param array $receivers
     * @param string $file
     * @param string $componentname
     */
    protected function add_receivers(array $receivers, $file, $componentname) {
        global $CFG;
        foreach ($receivers as $receiver) {
            if (empty($receiver['name']) or !is_string($receiver['name'])) {
                debugging("Invalid 'name' detected in $file receiver definition", DEBUG_DEVELOPER);
                continue;
            }
            if (empty($receiver['callback'])) {
                debugging("Invalid 'callback' detected in $file receiver definition", DEBUG_DEVELOPER);
                continue;
            }
            $o = new \stdClass();
            $o->callable = $receiver['callback'];
            if ($componentname === 'core' && !empty($receiver['component'])) {
                $o->component = $receiver['component'];
            } else {
                $o->component = $componentname;
            }
            if (!isset($receiver['priority'])) {
                $o->priority = 0;
            } else {
                $o->priority = (int)$receiver['priority'];
            }
            if (empty($receiver['includefile'])) {
                $o->includefile = null;
            } else {
                if ($CFG->admin !== 'admin' and strpos($receiver['includefile'], '/admin/') === 0) {
                    $receiver['includefile'] = preg_replace('|^/admin/|', '/' . $CFG->admin . '/', $receiver['includefile']);
                }
                $receiver['includefile'] = $CFG->dirroot . '/' . ltrim($receiver['includefile'], '/');
                if (!file_exists($receiver['includefile'])) {
                    debugging("Invalid 'includefile' detected in $file receiver definition", DEBUG_DEVELOPER);
                    continue;
                }
                $o->includefile = $receiver['includefile'];
            }

            $key = $this->sanitise_key($receiver['name']);
            if (!isset($this->allreceivers[$key])) {
                $this->allreceivers[$key] = [];
            }
            $this->allreceivers[$key][] = $o;
        }
    }

    /**
     * Optionally sanitize the key from the registration file.
     */
    protected function sanitise_key($key) {
        return $key;
    }

    /**
     * Reorder receivers to allow quick lookup of receivers for each dispatchable.
     */
    protected function order_all_receivers() {
        foreach ($this->allreceivers as $key => $receivers) {
            \core_collator::asort_objects_by_property($receivers, 'priority', \core_collator::SORT_NUMERIC);
            $this->allreceivers[$key] = array_reverse($receivers);
        }
    }

    /**
     * Custom validation of the dispatchable. Can trigger debugging messages, but cannot affect
     * the dispatching.
     *
     * This function is only executed in the debugging mode.
     *
     * @param \core\callback\dispatchable $dispatchable
     */
    abstract protected function validate(dispatchable $dispatchable);

    /**
     * Define the name of the cache to store the receivers.
     *
     * @return string The name of the cache.
     */
    abstract protected function get_cache_name();

    /**
     * Define the name of the registration file relative to the component dir.
     *
     * @return string The file name of the registration file relative to the component dir.
     */
    abstract protected function get_registration_file_name();

    /**
     * Define the name of the variable in the registration file containing the list of receivers.
     *
     * @return string The variable name in the registration file containing the list of receivers.
     */
    abstract protected function get_receiver_array_name();

    /**
     * Define the name of the variable in the registration file containing the list of dispatchable things.
     *
     * @return string The variable name in the registration file containing the list of dispatchable things.
     */
    abstract protected function get_dispatchable_array_name();

    /**
     * Replace all standard receivers.
     * @param array $receivers
     * @return array
     *
     * @throws \coding_exception if used outside of unit tests.
     */
    public function phpunit_replace_receivers(array $receivers) {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot override receivers outside of phpunit tests!');
        }

        $this->phpunit_reset();
        $this->allreceivers = array();
        $this->reloadaftertest = true;

        $this->add_receivers($receivers, 'phpunit', 'core_phpunit');
        $this->order_all_receivers();

        return $this->allreceivers;
    }

    /**
     * Check the list of dispatchables to see if we know about this one.
     *
     * @return boolean|string The component that defined the dispatchable or false.
     */
    public function get_dispatchable_component($key) {
        if (isset($this->alldispatchables[$key])) {
            return $alldispatchables[$key];
        }
        return false;
    }

    /**
     * Reset everything if necessary.
     *
     * @throws \coding_Exception if used outside of unit tests.
     */
   public function phpunit_reset() {
        if (!PHPUNIT_TEST) {
            throw new \coding_exception('Cannot reset dispatcher outside of phpunit tests!');
        }
        if (!$this->reloadaftertest) {
            $this->allreceivers = null;
            $this->alldispatchables = null;
        }
        $this->reloadaftertest = false;
    }

}
