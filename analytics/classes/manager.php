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
 * Analytics basic actions manager.
 *
 * @package   core_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_analytics;

defined('MOODLE_INTERNAL') || die();

/**
 * Analytics basic actions manager.
 *
 * @package   core_analytics
 * @copyright 2017 David Monllao {@link http://www.davidmonllao.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {

    /**
     * @var \core_analytics\predictor[]
     */
    protected static $predictionprocessors = null;

    /**
     * @var \core_analytics\local\indicator\base[]
     */
    protected static $allindicators = null;

    /**
     * @var \core_analytics\local\time_splitting\base[]
     */
    protected static $alltimesplittings = null;

    /**
     * Checks that the user can manage models
     *
     * @throws \required_capability_exception
     * @return void
     */
    public static function check_can_manage_models() {
        require_capability('moodle/analytics:managemodels', \context_system::instance());
    }

    /**
     * Checks that the user can list that context insights
     *
     * @throws \required_capability_exception
     * @param \context $context
     * @return void
     */
    public static function check_can_list_insights(\context $context) {
        require_capability('moodle/analytics:listinsights', $context);
    }

    /**
     * Returns all system models that match the provided filters.
     *
     * @param bool $enabled
     * @param bool $trained
     * @param \context $predictioncontext
     * @return \core_analytics\model[]
     */
    public static function get_all_models($enabled = false, $trained = false, $predictioncontext = false) {
        global $DB;

        $params = array();

        $sql = "SELECT DISTINCT am.* FROM {analytics_models} am";
        if ($predictioncontext) {
            $sql .= " JOIN {analytics_predictions} ap ON ap.modelid = am.id AND ap.contextid = :contextid";
            $params['contextid'] = $predictioncontext->id;
        }

        if ($enabled || $trained) {
            $conditions = [];
            if ($enabled) {
                $conditions[] = 'am.enabled = :enabled';
                $params['enabled'] = 1;
            }
            if ($trained) {
                $conditions[] = 'am.trained = :trained';
                $params['trained'] = 1;
            }
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $modelobjs = $DB->get_records_sql($sql, $params);

        $models = array();
        foreach ($modelobjs as $modelobj) {
            $models[$modelobj->id] = new \core_analytics\model($modelobj);
        }
        return $models;
    }

    /**
     * Returns the site selected predictions processor.
     *
     * @param string $predictionclass
     * @param bool $checkisready
     * @return \core_analytics\predictor
     */
    public static function get_predictions_processor($predictionclass = false, $checkisready = true) {

        // We want 0 or 1 so we can use it as an array key for caching.
        $checkisready = intval($checkisready);

        if ($predictionclass === false) {
            $predictionclass = get_config('analytics', 'predictionsprocessor');
        }

        if (empty($predictionclass)) {
            // Use the default one if nothing set.
            $predictionclass = '\mlbackend_php\processor';
        }

        if (!class_exists($predictionclass)) {
            throw new \coding_exception('Invalid predictions processor ' . $predictionclass . '.');
        }

        $interfaces = class_implements($predictionclass);
        if (empty($interfaces['core_analytics\predictor'])) {
            throw new \coding_exception($predictionclass . ' should implement \core_analytics\predictor.');
        }

        // Return it from the cached list.
        if (!isset(self::$predictionprocessors[$checkisready][$predictionclass])) {

            $instance = new $predictionclass();
            if ($checkisready) {
                $isready = $instance->is_ready();
                if ($isready !== true) {
                    throw new \moodle_exception('errorprocessornotready', 'analytics', '', $isready);
                }
            }
            self::$predictionprocessors[$checkisready][$predictionclass] = $instance;
        }

        return self::$predictionprocessors[$checkisready][$predictionclass];
    }

    /**
     * Return all system predictions processors.
     *
     * @return \core_analytics\predictor
     */
    public static function get_all_prediction_processors() {

        $mlbackends = \core_component::get_plugin_list('mlbackend');

        $predictionprocessors = array();
        foreach ($mlbackends as $mlbackend => $unused) {
            $classfullpath = '\\mlbackend_' . $mlbackend . '\\processor';
            $predictionprocessors[$classfullpath] = self::get_predictions_processor($classfullpath, false);
        }
        return $predictionprocessors;
    }

    /**
     * Get all available time splitting methods.
     *
     * @return \core_analytics\time_splitting\base[]
     */
    public static function get_all_time_splittings() {
        if (self::$alltimesplittings !== null) {
            return self::$alltimesplittings;
        }

        $classes = self::get_analytics_classes('time_splitting');

        self::$alltimesplittings = [];
        foreach ($classes as $fullclassname => $classpath) {
            $instance = self::get_time_splitting($fullclassname);
            // We need to check that it is a valid time splitting method, it may be an abstract class.
            if ($instance) {
                self::$alltimesplittings[$instance->get_id()] = $instance;
            }
        }

        return self::$alltimesplittings;
    }

    /**
     * Returns the enabled time splitting methods.
     *
     * @return \core_analytics\local\time_splitting\base[]
     */
    public static function get_enabled_time_splitting_methods() {

        if ($enabledtimesplittings = get_config('analytics', 'timesplittings')) {
            $enabledtimesplittings = array_flip(explode(',', $enabledtimesplittings));
        }

        $timesplittings = self::get_all_time_splittings();
        foreach ($timesplittings as $key => $timesplitting) {

            // We remove the ones that are not enabled. This also respects the default value (all methods enabled).
            if (!empty($enabledtimesplittings) && !isset($enabledtimesplittings[$key])) {
                unset($timesplittings[$key]);
            }
        }
        return $timesplittings;
    }

    /**
     * Returns a time splitting method by its classname.
     *
     * @param string $fullclassname
     * @return \core_analytics\local\time_splitting\base|false False if it is not valid.
     */
    public static function get_time_splitting($fullclassname) {
        if (!self::is_valid($fullclassname, '\core_analytics\local\time_splitting\base')) {
            return false;
        }
        return new $fullclassname();
    }

    /**
     * Return all system indicators.
     *
     * @return \core_analytics\local\indicator\base[]
     */
    public static function get_all_indicators() {
        if (self::$allindicators !== null) {
            return self::$allindicators;
        }

        $classes = self::get_analytics_classes('indicator');

        self::$allindicators = [];
        foreach ($classes as $fullclassname => $classpath) {
            $instance = self::get_indicator($fullclassname);
            if ($instance) {
                // Using get_class as get_component_classes_in_namespace returns double escaped fully qualified class names.
                self::$allindicators[$instance->get_id()] = $instance;
            }
        }

        return self::$allindicators;
    }

    /**
     * Returns the specified target
     *
     * @param mixed $fullclassname
     * @return \core_analytics\local\target\base|false False if it is not valid
     */
    public static function get_target($fullclassname) {
        if (!self::is_valid($fullclassname, 'core_analytics\local\target\base')) {
            return false;
        }
        return new $fullclassname();
    }

    /**
     * Returns an instance of the provided indicator.
     *
     * @param string $fullclassname
     * @return \core_analytics\local\indicator\base|false False if it is not valid.
     */
    public static function get_indicator($fullclassname) {
        if (!self::is_valid($fullclassname, 'core_analytics\local\indicator\base')) {
            return false;
        }
        return new $fullclassname();
    }

    /**
     * Returns whether a time splitting method is valid or not.
     *
     * @param string $fullclassname
     * @param string $baseclass
     * @return bool
     */
    public static function is_valid($fullclassname, $baseclass) {
        if (is_subclass_of($fullclassname, $baseclass)) {
            if ((new \ReflectionClass($fullclassname))->isInstantiable()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the logstore used for analytics.
     *
     * @return \core\log\sql_reader
     */
    public static function get_analytics_logstore() {
        $readers = get_log_manager()->get_readers('core\log\sql_reader');
        $analyticsstore = get_config('analytics', 'logstore');
        if (empty($analyticsstore)) {
            $logstore = reset($readers);
        } else if (!empty($readers[$analyticsstore])) {
            $logstore = $readers[$analyticsstore];
        } else {
            $logstore = reset($readers);
            debugging('The selected log store for analytics is not available anymore. Using "' .
                $logstore->get_name() . '"', DEBUG_DEVELOPER);
        }

        if (!$logstore->is_logging()) {
            debugging('The selected log store for analytics "' . $logstore->get_name() .
                '" is not logging activity logs', DEBUG_DEVELOPER);
        }

        return $logstore;
    }

    /**
     * Returns the models with insights at the provided context.
     *
     * @param \context $context
     * @return \core_analytics\model[]
     */
    public static function get_models_with_insights(\context $context) {

        self::check_can_list_insights($context);

        $models = \core_analytics\manager::get_all_models(true, true, $context);
        foreach ($models as $key => $model) {
            // Check that it not only have predictions but also generates insights from them.
            if (!$model->uses_insights()) {
                unset($models[$key]);
            }
        }
        return $models;
    }

    /**
     * Returns a prediction
     *
     * @param int $predictionid
     * @param bool $requirelogin
     * @return array array($model, $prediction, $context)
     */
    public static function get_prediction($predictionid, $requirelogin = false) {
        global $DB;

        if (!$predictionobj = $DB->get_record('analytics_predictions', array('id' => $predictionid))) {
            throw new \moodle_exception('errorpredictionnotfound', 'report_insights');
        }

        if ($requirelogin) {
            list($context, $course, $cm) = get_context_info_array($predictionobj->contextid);
            require_login($course, false, $cm);
        } else {
            $context = \context::instance_by_id($predictionobj->contextid);
        }

        \core_analytics\manager::check_can_list_insights($context);

        $model = new \core_analytics\model($predictionobj->modelid);
        $sampledata = $model->prediction_sample_data($predictionobj);
        $prediction = new \core_analytics\prediction($predictionobj, $sampledata);

        return array($model, $prediction, $context);
    }

    /**
     * Returns the provided element classes in the site.
     *
     * @param string $element
     * @return string[] Array keys are the FQCN and the values the class path.
     */
    private static function get_analytics_classes($element) {

        // Just in case...
        $element = clean_param($element, PARAM_ALPHANUMEXT);

        $classes = \core_component::get_component_classes_in_namespace('core_analytics', 'local\\' . $element);
        foreach (\core_component::get_plugin_types() as $type => $unusedplugintypepath) {
            foreach (\core_component::get_plugin_list($type) as $pluginname => $unusedpluginpath) {
                $frankenstyle = $type . '_' . $pluginname;
                $classes += \core_component::get_component_classes_in_namespace($frankenstyle, 'analytics\\' . $element);
            }
        }
        return $classes;
    }
}