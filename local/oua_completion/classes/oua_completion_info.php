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
 * Contains classes, functions and constants used to complete vet
 * extention of Recoginition of prior learning for VET courses.
 *
 * @package local
 * @category vet_pepi_webservices
 * @copyright 2013 onwards Russell Smith {@link http://moodle.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_oua_completion;
defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/completionlib.php');

/**
 * Class: oua_utility_completion
 *
 * We use core completion where possible, but we need to alter
 * the completion rules in some cases to allow RPL to be applied
 * as a manual completion type, even though we have manual completion
 * disabled in the front end.
 *
 * @see completion_info
 */
class oua_completion_info extends \completion_info {

    private $course;
    private $cmpercentage = array();
    private $cmoverridepercentage = null;
    private $completioncache = array();

    /**
     * Constructor of custom completion
     *
     * We use a custom version here as the course variables are private to our parent.
     *
     */
    public function __construct($course) {
        $this->course = $course;
        parent::__construct($course);
    }

    /**
     * Updates (if necessary) the completion state of activity $cm for the given
     * user.
     *
     * See core completion info for details of original function.
     * For manual completion, this function is called when completion is toggled
     * with $possibleresult set to the target state.
     *
     * This was last audited for Moodle 2.9.1 for correctness.
     *
     * For automatic completion, this function should be called every time a module
     * does something which might influence a user's completion state. For example,
     * if a forum provides options for marking itself 'completed' once a user makes
     * N posts, this function should be called every time a user makes a new post.
     * [After the post has been saved to the database]. When calling, you do not
     * need to pass in the new completion state. Instead this function carries out
     * completion calculation by checking grades and viewed state itself, and
     * calling the involved module via modulename_get_completion_state() to check
     * module-specific conditions.
     *
     * @param stdClass|cm_info $cm Course-module
     * @param int $possibleresult Expected completion result. If the event that
     *   has just occurred (e.g. add post) can only result in making the activity
     *   complete when it wasn't before, use COMPLETION_COMPLETE. If the event that
     *   has just occurred (e.g. delete post) can only result in making the activity
     *   not complete when it was previously complete, use COMPLETION_INCOMPLETE.
     *   Otherwise use COMPLETION_UNKNOWN. Setting this value to something other than
     *   COMPLETION_UNKNOWN significantly improves performance because it will abandon
     *   processing early if the user's completion state already matches the expected
     *   result. For manual events, COMPLETION_COMPLETE or COMPLETION_INCOMPLETE
     *   must be used; these directly set the specified state.
     * @param int $userid User ID to be updated. Default 0 = current user
     * @return void
     */
    public function set_manual_state($cm, $possibleresult=COMPLETION_UNKNOWN, $userid=0) {
        global $USER, $SESSION, $DB;

        // Do nothing if completion is not enabled for that activity.
        if (!$this->is_enabled($cm)) {
            return;
        }

        // Get current value of completion state and do nothing if it's same as
        // the possible result of this change. If the change is to COMPLETE and the
        // current value is one of the COMPLETE_xx subtypes, force a change in this
        // case as we are might be RPL'ing units.  They must end up in COMPLETION_COMPLETE.
        $current = $this->get_data($cm, false, $userid);
        if ($possibleresult == $current->completionstate) {
            return;
        }

        // For manual tracking we set the result directly.
        switch($possibleresult) {
            case COMPLETION_COMPLETE:
            case COMPLETION_INCOMPLETE:
                $newstate = $possibleresult;
                break;
            default:
                $this->internal_systemerror("Unexpected manual completion state for {$cm->id}: $possibleresult");
        }

        // If changed, update.
        if ($newstate != $current->completionstate) {
            $current->completionstate = $newstate;
            $current->timemodified    = time();
            $this->internal_set_data($cm, $current);
        }
    }

    private function load_overrides($force = false) {
        global $DB;

        if ($force || !isset($this->cmoverridepercentage)) {
            // Load all the custom percentages from the database.
            $this->cmoverridepercentage = $DB->get_records_sql('SELECT ocmc.coursemoduleid, ocmc.progresspercent, ocmc.id
                                                                FROM {oua_course_mod_completion} ocmc
                                                                JOIN {course_modules} cm ON (ocmc.coursemoduleid = cm.id)
                                                                WHERE course = ?', array($this->course->id));
        }
    }

    /**
     * Calculate the assigned percentages for each item in the course.
     *
     * @return bool true for a valid set of percentages, false if it can't calculate them.
     */
    public function calculate_assigned_percentages() {
        $totalweight = 0;
        $hundredthpercentagefromweights = 10000;
        $weightlist = array();

        $modinfo = $this->get_fast_modinfo();

        $this->load_overrides();

        // Custom weighting take precidence.
        foreach ($modinfo->get_cms() as $cm) {
            $weight = 0;
            if ($cm->completion == COMPLETION_TRACKING_NONE) {
                continue;
            }
            if (isset($this->cmoverridepercentage[$cm->id])) {
                // There is a specified override, use that value forcibly.
                $weight = 0;
                $cmpercentage = intval($this->cmoverridepercentage[$cm->id]->progresspercent);
                $hundredthpercentagefromweights -= $cmpercentage;
                $this->cmpercentage[$cm->id] = $cmpercentage;
            } else {
                // All activities have the same weight by default.
                $weight = 1;
            }

            if ($weight == 0) {
                continue;
            }
            $totalweight += $weight;
            $weightlist[$cm->id] = $weight;
        }

        if ($totalweight == 0 && $hundredthpercentagefromweights == 10000) {
            // There are no activities to measure.
            $this->cmpercentage = null;
            return false;
        }

        if ($totalweight == 0 && $hundredthpercentagefromweights != 0) {
            // We are support to be auto calculating weights, but there aren't any activities to assign
            // percentages to.
            $this->cmpercentage = null;
            return false;
        }

        // All activities were assigned a weight and they added to 100.
        if ($totalweight == 0 && $hundredthpercentagefromweights == 0) {
            return true;
        }

        // Overrides cannot add up to more than 100.
        if ($hundredthpercentagefromweights < 0) {
            $this->cmpercentage = null;
            return false;
        }

        // Calculate the percentages for each of the weighted items.
        $hundredthweightpercentage = 0;
        $totalfraction = 0;
        $lastcm = null;

        // Working with float's is very dangerous as they don't always add up as you expect.
        // As completion is a percentage approximation, we are going to allow 2 decimal places of accuracy
        // and complete the calculation in an integer value.  The final cm will be adjusted up to make
        // up the required fraction to make 100%.
        foreach ($weightlist as $cmid => $weight) {
            $percentage = $weight / $totalweight * $hundredthpercentagefromweights;
            $hundredthpercentage = intval(round($percentage, 0));
            $this->cmpercentage[$cmid] = $hundredthpercentage;
            $hundredthweightpercentage += $this->cmpercentage[$cmid];
            $lastcm = $cmid;
        }

        // Now we may have a nasty rounding situation left.
        // eg; 33.33 + 33.33 + 33.33 != 100
        // We need to adjust the last cmid
        // Percentages didn't come out at 100, add it onto the last cm because it's going to be close
        // enough when we are just given an indication of progress.
        $leftoverpercentage = $hundredthpercentagefromweights - $hundredthweightpercentage;

        $this->cmpercentage[$lastcm] += $leftoverpercentage;
        $hundredthweightpercentage += $leftoverpercentage;

        if ($hundredthweightpercentage != $hundredthpercentagefromweights) {
            $this->cmpercentage = null;
            return false;
        }

        return true;
    }

    /**
     * Give the percentage progress this item contributes when complete.
     *
     * @param mixed $cmid id of the course module we want a override percentage for.
     *
     * @return integer The whole percent of the override or null for no override.
     */
    public function get_percentage($cmid) {
        if (empty($this->cmpercentage)) {
            $result = $this->calculate_assigned_percentages();
            // In cases where the percentages can't be calculated, we need fail as results
            // are indeterminite.
            if (!$result) {
                return false;
            }
        }

        if (!isset($this->cmpercentage[$cmid])) {
            return 0;
        }
        return round($this->cmpercentage[$cmid] / 100, 2);
    }

    /**
     * Return the value of an overridden percentage if it's overridden
     *
     * @param integer $cmid The course module id find.
     * @return float The percentage, or null if it's not set.
     */
    public function get_custom_percentage($cmid) {
        $this->load_overrides();

        if (isset($this->cmoverridepercentage[$cmid])) {
            return round($this->cmoverridepercentage[$cmid]->progresspercent / 100, 2);
        }
    }

    /**
     * Set the percentage progress this item contributes when complete.
     *
     * @param mixed $cmid id of the course module we want a override percentage for.
     * @param float $percentage The percentage to for override to. (2 decimals only, eg 50.12).
     *
     * @return boolean Where the percentage could be set and calculated correctly.
     */
    public function set_custom_percentage($cmid, $percentage) {
        $this->load_overrides();

        $modinfo = $this->get_fast_modinfo();
        // Get all course modules so we can determine which topic is next.
        $cm = $modinfo->get_cm($cmid);

        if ($cm->completion == COMPLETION_TRACKING_NONE) {
            unset($this->cmoverridepercentage[$cmid]);
        } else if ($percentage === null) {
            unset($this->cmoverridepercentage[$cmid]);
        } else {
            // Preserve ID's for update purposes when saving.
            if (!isset($this->cmoverridepercentage[$cmid])) {
                $this->cmoverridepercentage[$cmid] = new \stdClass();
                $this->cmoverridepercentage[$cmid]->coursemoduleid = $cmid;
                $this->cmoverridepercentage[$cmid]->id = null;
            }
            $this->cmoverridepercentage[$cmid]->progresspercent = intval(round($percentage, 2) * 100);
        }
        return $this->calculate_assigned_percentages();
    }

    /**
     * Obtains completion data for a particular activity and user (from the
     * session cache if available, or by SQL query)
     *
     * This is an extension to completionlib's get_data.  It's used to cache
     * entire course information for a user that's not the current user
     *
     * Last confirmed as still relevant on 2.9.1 of Moodle.
     *
     * @param stdClass|cm_info $cm Activity; only required field is ->id
     * @param bool $wholecourse If true (default false) then, when necessary to
     *   fill the cache, retrieves information from the entire course not just for
     *   this one activity
     * @param int $userid User ID or 0 (default) for current user
     * @param array $modinfo Supply the value here - this is used for unit
     *   testing and so that it can be called recursively from within
     *   get_fast_modinfo. (Needs only list of all CMs with IDs.)
     *   Otherwise the method calls get_fast_modinfo itself.
     * @return object Completion data (record from course_modules_completion)
     */

    public function get_data($cm, $wholecourse = false, $userid = 0, $modinfo = null) {
        global $USER, $CFG, $DB;

        // Cache in the local space.

        // Handle my own caches and a special one for the case where we cache the whole course that the
        // parent doesn't.

        // If the current user is the user sent or no user, just process the parent data. It will cache as required.
        if ($userid === 0 || $userid == $USER->id) {
            return parent::get_data($cm, $wholecourse, $userid, $modinfo);
        }

        // Otherwise we cache the data for the requested user in the class and return cached results if required.
        if (isset($this->completioncache[$userid][$cm->id])) {
            return $this->completioncache[$userid][$cm->id];
        }

        if ($wholecourse) {
            // Get whole course data for cache.
            $alldatabycmc = $DB->get_records_sql("SELECT cmc.*
                                                    FROM {course_modules} cm
                                              INNER JOIN {course_modules_completion} cmc ON cmc.coursemoduleid=cm.id
                                                   WHERE cm.course = ? AND cmc.userid = ?", array($this->course->id, $userid));

            // Reindex by cmid.
            $alldata = array();
            if ($alldatabycmc) {
                foreach ($alldatabycmc as $data) {
                    $alldata[$data->coursemoduleid] = $data;
                }
            }

            // Get the module info and build up condition info for each one.
            if (empty($modinfo)) {
                $modinfo = $this->get_fast_modinfo($userid);
            }
            foreach ($modinfo->get_cms() as $othercm) {
                if (array_key_exists($othercm->id, $alldata)) {
                    $data = $alldata[$othercm->id];
                } else {
                    // Row not present counts as 'not complete'.
                    $data = new \stdClass;
                    $data->id              = 0;
                    $data->coursemoduleid  = $othercm->id;
                    $data->userid          = $userid;
                    $data->completionstate = 0;
                    $data->viewed          = 0;
                    $data->timemodified    = 0;
                }
                $this->completioncache[$userid][$othercm->id] = $data;
            }

            if (!isset($this->completioncache[$userid][$cm->id])) {
                $this->internal_systemerror("Unexpected error: course-module {$cm->id} could not be found ".
                                            "on course {$this->course->id}");
            }
            return $this->completioncache[$userid][$cm->id];

        }

        // It's not whole course, and it's not already in our cache.  Use the standard completion information.
        return parent::get_data($cm, $wholecourse, $userid, $modinfo);
    }

    /**
     * Return how much progress a particular user has.
     *
     * This progress is based on completion information.  If you qualify to complete
     * the course, you will be granted 100% regardless of your current activity progression.
     * You will then be given a percentage based on what has been calculated for the active set
     * of activitites.
     * @param $userid int The userid of the user to calculate progress for.
     *
     * @return boolean|float false for failure and float for progress
     */
    public function get_user_progress($userid) {

        if (empty($this->cmpercentage)) {
            $worked = $this->calculate_assigned_percentages();
            if (!$worked) {
                return false;
            }
        }

        $percentagecomplete = 0;
        $percentagetoignore = 0;
        $modinfo = $this->get_fast_modinfo($userid);

        foreach ($modinfo->get_cms() as $cm) {
            // Invisible items are not considered when calculating user progress.
            // We only consider visible, those items are hidden from all users.
            // uservisible is likely the result of current availability rules and we consider those as
            // part of the progression calculation.
            if (!$cm->visible && isset($this->cmpercentage[$cm->id])) {
                $percentagetoignore += $this->cmpercentage[$cm->id];
                continue;
            }
            // Collect the data for the course module and always ask for wholecourse to make
            // maximum use of the caching in both this class and the parent class.
            $compinfo = $this->get_data($cm, true, $userid);
            $state = $compinfo->completionstate;

            if (($state == COMPLETION_COMPLETE || $state == COMPLETION_COMPLETE_PASS)
                    && isset($this->cmpercentage[$cm->id])) {
                $percentagecomplete += $this->cmpercentage[$cm->id];
            }
        }

        // Recalculate completed percentage by excluding invisible items.
        // Because we work in 100th's of percent, we use 10000 sa the multipler in all iterim steps.
        $availablepercentagetocomplete = 10000 - $percentagetoignore;
        $actualpercentagecomplete = ($percentagecomplete * 10000) / $availablepercentagetocomplete;

        return $actualpercentagecomplete / 100;
    }

    /**
     * This is a class wrapper function for testing. It just runs the global API.
     *
     * @param integer $userid The user id we are getting the module information for.
     *
     * @return array Array of course module objects.
     */
    protected function get_fast_modinfo($userid = 0) {
        return get_fast_modinfo($this->course, $userid);
    }

    /**
     * Save all override changes into the database.
     *
     */
    public function save_assigned_percentages() {
        global $DB;

        // Delete all items for $this->course->id where id not in (idlist).
        $idlist = array();

        $transaction = $DB->start_delegated_transaction();

        foreach ($this->cmoverridepercentage as $cmid => $override) {
            $override->timemodified = time();
            if (isset($override->id)) {
                // Some variables are loaded as strings, even though they are int's.  Make it all ints.
                $idlist[] = $override->id;
                $DB->update_record('oua_course_mod_completion', $override);
            } else {
                // Insert stuff.
                $override->timecreated = time();
                $newid = $DB->insert_record('oua_course_mod_completion', $override);
                $idlist[] = $newid;
            }
        }

        // The completion table doesn't store course information, so we need course modules.
        $where = "coursemoduleid IN (SELECT id from {course_modules} WHERE course = :course)";

        if (!empty($idlist)) {
            list($insql, $params) = $DB->get_in_or_equal($idlist, SQL_PARAMS_NAMED);
            $where .= " AND NOT (id $insql)";
        } else {
            $params = array();
        }

        $params['course'] = $this->course->id;
        $DB->delete_records_select('oua_course_mod_completion', $where, $params);

        $transaction->allow_commit();
    }
}
