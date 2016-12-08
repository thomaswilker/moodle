<?php

/**
 *  Custom navigation for the VET project
 *
 * @package    block
 * @subpackage oua_navigation
 * @author     Marcus Boon <marcus@catatlyst-au.net>
 */
require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

class block_oua_navigation extends block_base {

    /** @var string The name of the block */
    public $blockname = null;
    public $youtubedetailcache = array();

    /**
     *  Set the initial properties for the block
     */
    public function init() {
        $this->blockname = get_class($this);
        $this->title = get_string('pluginname', $this->blockname);
    }

    public function hide_header() {
        return true;
    }

    /**
     *  Allow multiple instances of this block
     *
     * @return bool false
     */
    public function instance_allow_multiple() {
        return false;
    }

    /**
     *  Set the applicable formats for this block to call
     *
     * @return array
     */
    public function applicable_formats() {
        return array('all' => true);
    }

    /**
     *  Allow the user to configure a block instance
     *
     * @return bool true
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     *  This is important so don't let it be hidden
     *
     * @return bool false
     */
    public function instance_can_be_hidden() {
        return false;
    }

    /**
     *  Find out if an instance can be docked
     *
     * @return bool true or false depending on whether the instance can be docked or not
     */
    public function instance_can_be_docked() {
        return (
            parent::instance_can_be_docked() &&
            (empty($this->config->enabledock) ||
             $this->config->enabledock == 'yes')
        );
    }

    /**
     *  Get the contents of the block
     *
     * @return object $this->content
     */
    public function get_content() {
        global $DB, $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        $course = $this->page->course;

        $this->content = new \stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $modinfo = get_fast_modinfo($course);

        $completioninfo = new completion_info($course);
        $coursecontent = new \stdClass();

        // Unless the course is complete, it's always considered in-progress as we have looked at it.
        if ($completioninfo->is_course_complete($USER->id)) {
            $coursecontent->course_completion_status_class = 'complete';
        } else {
            $coursecontent->course_completion_status_class = 'inprogress';
        }

        $subjectcompletion = null;

        // Preload youtube durations to save DB time
        $dbman = $DB->get_manager();
        if ($dbman->table_exists('youtube')) {
            $this->youtubedetailcache = $DB->get_records_sql(
                "SELECT m.id, yt.duration
                       FROM {course_modules} m
                       JOIN {modules} md ON md.id = m.module
                       JOIN {youtube} yt ON yt.id = m.instance
                      WHERE m.course = :course
                        AND md.name = 'youtube'",
                array('course' => $this->page->course->id)
            );
        }
        $courseforums = forum_get_readable_forums($USER->id, $course->id); // Get all courseforums to save time.

        $coursecontent->sections_for_display = array();
        foreach ($modinfo->get_section_info_all() as $sectionnum => $section) {
            $sectionfordisplay = array();
            $sectionfordisplay['sectionnum'] = $sectionnum;
            $sectionfordisplay['active'] = false;
            $sectionfordisplay['sectioncssclasses'] = array();
            if (!$section->uservisible || $sectionnum > 100) {
                // Too many sections:  processing this many, may take too long and make the nav too big.
                // This was imported from previous platform.
                continue;
            }

            $modulecount = 0;
            $completecount = 0;
            $inprogresscount = 0;
            $assessmentcount = 0;
            $assessmentcompletecount = 0;

            $sectionname = get_section_name($course, $section);

            $sectionfordisplay['section_title'] = $sectionname;
            $sectionfordisplay['sectioncomplete'] = true;
            $sectionfordisplay['modules'] = array();

            $firsthiddenforum = $this->get_first_hidden_forum($courseforums, $section);

            if ($firsthiddenforum !== null) {

                $modulefordisplay = array();
                $modulefordisplay['modurl'] = new moodle_url('/mod/forum/viewinline.php', array('id' => $firsthiddenforum->cm->id));
                if ($this->page && $this->page->cm && $this->page->cm->id && ($this->page->cm->id == $firsthiddenforum->cm->id)) {
                    // This is the active page, set classes accordingly for template use.
                    $modulefordisplay['active'] = true;
                    $modulefordisplay['modulecssclasses'][] = 'active';
                    $sectionfordisplay['active'] = true;
                    $sectionfordisplay['sectioncssclasses'][] = 'active';
                }
                $modulefordisplay['modulecssclasses'][] = $firsthiddenforum->cm->modname;
                $modulefordisplay['modulecssclasses'][] = 'sectiondiscussion';
                $modulefordisplay['modname'] = $firsthiddenforum->cm->name;
                $modulefordisplay['modfullname'] = $firsthiddenforum->cm->modfullname;
                $modulefordisplay['indent'] = 0;
                $modulefordisplay['activityiconurl'] = $firsthiddenforum->cm->get_icon_url();
                $sectionfordisplay['modules'][] = $modulefordisplay;
            }

            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$sectionnum] as $modnumber) {
                    $modulefordisplay = array();
                    $mod = $modinfo->cms[$modnumber];

                    $modulefordisplay['modulecssclasses'] = array($mod->modname);
                    $modulefordisplay['modcompletionstateclass'] = '';

                    // If there is a unitintro module, flag it to set the title link later.
                    if ($mod->modname == 'unitintro' && preg_match("/$sname/", $mod->name)) {
                        $unitintro = $mod;
                    }
                    // Hasview, isvisible, indent > 2.
                    if (!$this->module_is_included($mod)) {
                        continue;
                    }
                    $modulecount++;
                    if ($completioninfo->is_enabled($mod) != COMPLETION_TRACKING_NONE) {
                        // Completion is enabled for this activity.

                        $completion = $completioninfo->get_data($mod);
                        $modcompletionstate = $this->get_completion_progress_css_class($completion);
                        $modulefordisplay['modcompletionstateclass'] = $modcompletionstate;

                        if ($mod->completiongradeitemnumber == 0) { // Assessments are anything with a grade.
                            // Quick decision for section completion.
                            // A section is complete when all activities that use a grade completion
                            // have been completed.
                            $assessmentcount++;
                            if ($modcompletionstate == 'complete') {
                                $assessmentcompletecount++;
                            }
                        }

                        if ($modcompletionstate == 'complete') {
                            // Grade completion enabled, and item is complete
                            $completecount++;
                        } else if ($modcompletionstate == 'inprogress') {
                            $inprogresscount++;
                        }
                    }
                    $modulefordisplay['modname'] = format_string($mod->name);
                    if ($mod->modname == 'youtube' && isset($this->youtubedetailcache[$mod->id])) {
                        // Add duration for youtube videos
                        $duration = $this->youtubedetailcache[$mod->id]->duration;
                        if ($duration) {
                            $modulefordisplay['youtubeduration'] = sprintf(
                                '%d:%02d',
                                floor($duration / 60),
                                $duration % 60
                            );
                        }
                    }
                    $modulefordisplay['duedate'] = $this->get_due_date_display($mod, $USER->id);
                    $modulefordisplay['indent'] = $mod->indent;

                    if ($this->page && $this->page->cm && $this->page->cm->id && ($this->page->cm->id == $modnumber)) {
                        // This is the active page, set classes accordingly for template use.
                        $modulefordisplay['active'] = true;
                        $modulefordisplay['modulecssclasses'][] = 'active';
                        $sectionfordisplay['active'] = true;
                        $sectionfordisplay['sectioncssclasses'][] = 'active';
                    }
                    $modulefordisplay['modurl'] = $mod->url;
                    $modulefordisplay['modname'] = $mod->name;
                    $modulefordisplay['modfullname'] = $mod->modfullname;
                    $modulefordisplay['activityiconurl'] = $mod->get_icon_url();
                    $sectionfordisplay['modules'][] = $modulefordisplay;
                }
            }

            $unitcompletestatus = $this->get_section_completion_css_class($assessmentcount, $assessmentcompletecount,
                $modulecount, $completecount, $inprogresscount);
            $sectionfordisplay['sectioncssclasses'][] = $unitcompletestatus;

            $coursecontent->sections_for_display[] = $sectionfordisplay;
        }

        $renderer = $this->page->get_renderer('block_oua_navigation');
        $this->content->text = $renderer->display_course_navigation($coursecontent);

        return $this->content;
    }

    protected function module_is_included($cm) {
        if (!$cm->has_view() || !$cm->uservisible) {
            return false;
        }

        return !($this->module_is_hidden($cm));
    }
    /**
     * Determine if this forum is on that should be included in the list of invisible forums.
     *
     * Unless the course format used supports the module_is_visible function, then all forums
     * will be visible and included
     */
    protected function module_is_hidden($cm) {
        // The course format needs to tell us if it's hidden or not.
        $courseformat = course_get_format($this->page->course);
        $cancall = is_callable(array($courseformat, 'module_is_hidden_from_view'));

        if (!$cancall) {
            return false;
        }

        return $courseformat->module_is_hidden_from_view($cm);
    }


    protected function get_first_hidden_forum($courseforums, $section) {

        $firsthiddenforum = null;

        foreach ($courseforums as $cmforum) {
            if ($cmforum->cm->section == $section->id && $cmforum->cm->uservisible && $this->module_is_hidden($cmforum->cm)) {
                $firsthiddenforum = $cmforum;
                break;
            }
        }
        return $firsthiddenforum;
    }

    /**
     *  Returns the attributes to set for this block
     *
     *  This function returns an array of HTML attributes for this block including
     *   the defaults
     *  {@link block_tree::html_attributes()} is used to get the default arguments
     *  and then we check whether the user has enabled hover expansion and add the
     *  appropriate hover class if it has.
     *
     * @return array An array of HTML attributes
     */
    public function html_attributes() {
        $attributes = parent::html_attributes();
        $attributes['class'] .= ' block_js_expansion block_navigation';

        return $attributes;
    }

    /**
     * @param $sectionnum int The logical section number inside the course.
     *
     * @return bool Whether to display the unit prefix for this particular section.
     */
    public function display_unit_prefix($sectionnum) {
        // Get the section format options.  If unitid is empty, we don't display the Unit part.
        $unitid = $this->get_unitid($sectionnum);
        if (!empty($unitid)
                && (!isset($this->config->unit_intro_prefix) || $this->config->unit_intro_prefix == 'show')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $sectionnum int The logical section number inside the course.
     *
     * @return string|null Return the unitid or null if there isn't one for the format.
     */
    protected function get_unitid($sectionnum) {
        $formatoptions = course_get_format($this->page->course->id)->get_format_options($sectionnum);
        if (isset($formatoptions['unitid'])) {
            return $formatoptions['unitid'];
        } else {
            return null;
        }
    }

    /**
     * Return the progress status for a particular item
     *
     * @param object $completiondata The completion data returned from completions get_data
     *
     * @return string complete,inprogress,incomplete
     */
    public function get_completion_progress_css_class($completiondata) {
        if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
            // If the item is completion_complete or completion_complete_pass, we are complete.
            return 'complete';
        } else if (($completiondata->viewed == COMPLETION_VIEWED &&
                $completiondata->completionstate == COMPLETION_INCOMPLETE) ||
                $completiondata->completionstate == COMPLETION_COMPLETE_FAIL) {
            // When we have viewed the item, but not completed, we are in progress.
            return 'inprogress';
        } else {
            // Unless the above conditions are met, we consider the activity incomplete.
            return 'incomplete';
        }
    }

    /**
     * Return the due date for this element if required.
     *
     * @param object  $cm Course Module to generate due date for.
     * @param integer $userid The ID of the user to generate dates for.
     *
     * @return string Text (due: date) or empty string for no date.
     */
    public function get_due_date_display($cm, $userid) {
        global $CFG;
        $duedate = '';
        switch ($cm->modname) {
            case 'quiz':
                require_once($CFG->dirroot . '/mod/quiz/locallib.php');
                $quiz = quiz::create($cm->instance, $userid);
                $timeclose = $quiz->get_quiz()->timeclose;
                if ($timeclose) {
                    $duedate = userdate($timeclose, "%d %B");
                }
              break;
            default:
               break;
        }
        return $duedate;
    }

    /**
     * Return the completion status of the unit.
     *
     * @param integer $assessmentcount Number of assessment modules in the unit.
     * @param integer $assessmentcompletecount The number of assessments the user has completed.
     * @param integer $modulecount Number of modules in the unit.
     * @param integer $completecount Number of modules the user has completed.
     * @param integer $inprogresscount Number of modules in progress.
     *
     * @return string Text status or empty string for no status.
     */
    public function get_section_completion_css_class($assessmentcount, $assessmentcompletecount, $modulecount,
        $completecount, $inprogresscount) {
        // A section is complete if all assessments (e.g. all items using grade for completion) are complete.
        // If there aren't assessments, then it's complete when all items marked with completion are complete.
        // Mark as inprogress when we have at least one item in progress and none of above are met.
        // it is incomplete i.e. not started, no class otherwise.
        // These requirements are not solid and need to be confirmed.
        if ($assessmentcount == $assessmentcompletecount && $assessmentcount > 0) {
            $sectioncompletestatus = 'complete';
        } else if ($modulecount == $completecount && $completecount > 0) {
            $sectioncompletestatus = 'complete';
        } else if ($completecount < $modulecount && ($completecount + $inprogresscount) > 0) {
            $sectioncompletestatus = 'inprogress';
        } else {
            $sectioncompletestatus = '';
        }

        return $sectioncompletestatus;
    }
}
