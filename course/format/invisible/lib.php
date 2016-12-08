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

use format_invisible\output\contentfooter;
use format_invisible\output\contentheader;

defined('MOODLE_INTERNAL') || die();



require_once($CFG->dirroot. '/course/format/lib.php');
require_once($CFG->dirroot. '/course/format/topics/lib.php');

// Extend the topics format with the extra restrictions we want for the invisible format.
class format_invisible extends format_topics {

    /**
     * Definitions of the additional options that this course format uses for section
     *
     * See {@link format_base::course_format_options()} for return array definition
     *
     * Additionally section format options may have property 'uniqueid' that identifies
     * the section. This is then used by custom web service calls whenever a student
     * completes a particular section. This should survive a backup and restore
     *
     * @param bool $foreditform
     * @return array
     */
    public function section_format_options($foreditform = false) {
        return array(
            'unitid' => array(
                'type' => PARAM_TEXT,
                'label' => new lang_string('unitid', 'format_invisible'),
                'element_type' => 'text',
                'default' => '',
            ),
            'numdaystocomplete' => array(
                'type' => PARAM_INT,
                'label' => new lang_string('numdays', 'format_invisible'),
                'element_type' => 'text',
                'default' => 0,
            )
        );
    }

    /**
     * Return the course format options
     */
    public function course_format_options($foreditform = false) {
        static $courseformatoptions = false;

        if ($courseformatoptions === false) {
            $courseformatoptions = array(
                'courseenddate' => array(
                    'type' => PARAM_INT,
                    'default' => 0,
                    ),
                'coursepreviewactivity' => array(
                            'type' => PARAM_INT,
                            'default' => 0,
                    ),
                'coursecompleteactivity' => array(
                'type' => PARAM_INT,
                'default' => 0,
                    ),
                'cobrandingname' => array(
                    'type' => PARAM_TEXT,
                    'label' => new lang_string('cobrandingname', 'format_invisible')
                )
            );

        }

        // Only calculate if we are editing the form and we've not already calculated the output.
        if ($foreditform === true && !isset($courseformatoptions['coursecompleteactivity']['element_type'])) {
            $courseformatoptions['courseenddate']['label'] = new lang_string('courseenddate', 'format_invisible');
            $courseformatoptions['courseenddate']['element_type'] = 'date_time_selector';

            $courseformatoptions['coursecompleteactivity']['label'] = new lang_string('coursecompleteactivity', 'format_invisible');
            $courseformatoptions['coursecompleteactivity']['element_type'] = 'select';

            $courseformatoptions['coursepreviewactivity']['label'] = new lang_string('coursepreviewactivity', 'format_invisible');
            $courseformatoptions['coursepreviewactivity']['element_type'] = 'select';
        }

        // The course information is not available until a last minute call to this function.  We need to fill in the data
        // at the last possible moment.  However we only need the data if at one point this was called with foreditform.
        if (!isset($courseformatoptions['coursecompleteactivity']['element_attributes']) && $this->get_course() !== null) {
            $modinfo = get_fast_modinfo($this->get_course(), -1);

            $options = array(null => new lang_string('notused', 'format_invisible'));
            foreach ($modinfo->get_cms() as $cmid => $cm) {
                // Match indents of activities to provide some visual structure to the list.
                $options[$cmid] = str_repeat('&nbsp;&nbsp;', $cm->indent).$cm->name;
            }
            $courseformatoptions['coursecompleteactivity']['element_attributes'] = array($options);
            $courseformatoptions['coursepreviewactivity']['element_attributes'] = array($options);

        }

        return parent::course_format_options($foreditform) + $courseformatoptions;
    }

    public function update_course_format_options($data, $oldcourse = null) {
        // Data may be an object or an array, we force object as the filemanager expected objects.
        $data = (object)$data;

        $options = $this->filemanager_options_cobrandinglogo();
        $context = context_course::instance($this->get_courseid());

        // Course format may only update some elements, only handle the filemanager if it was included in the update.
        if (isset($data->cobrandinglogo_filemanager)) {
            $data = file_postupdate_standard_filemanager($data, 'cobrandinglogo', $options, $context,
                'format_invisible', 'cobrandinglogo', 0);
        }

        return parent::update_course_format_options($data, $oldcourse);
    }

    public function create_edit_form_elements(&$mform, $forsection = false) {
        global $course;

        $elements = parent::create_edit_form_elements($mform, $forsection);

        if (!$forsection) {
            $options = $this->filemanager_options_cobrandinglogo();

            $logotext = new lang_string('cobrandinglogotitle', 'format_invisible');

            if (isset($course->id) && $course->id > 0) {
                $coursecontext = context_course::instance($course->id);
            } else {
                // Use null as the context as the course has not been created yet.
                $course = new stdClass();
                $coursecontext = null;
            }
            file_prepare_standard_filemanager($course, 'cobrandinglogo', $options, $coursecontext, 'format_invisible', 'cobrandinglogo', 0);

            $elements[] = $mform->addElement('filemanager', 'cobrandinglogo_filemanager', $logotext, null, $options);
            $mform->setDefault('cobrandinglogo_filemanager', $course->cobrandinglogo_filemanager);
        }
        return $elements;
    }

    protected function filemanager_options_cobrandinglogo() {
        return array(
            'maxfiles' => 1,
            'subdirs' => 0,
            'accepted_types' =>  '*'
        );
    }

    /**
     * Allows course format to execute code on moodle_page::set_cm()
     *
     * This function is executed before the output starts.
     *
     * You can configure the course to force users to a specific module prior to course start.
     * See course_format_options for the details of choosing a preview activity.
     *
     * @param moodle_page $page instance of page calling set_cm
     */
    public function page_set_cm(moodle_page $page) {
        $pagecm = $page->cm;

        $previewcmid = $this->get_course()->coursepreviewactivity;

        // page_set_course will take care of all redirects for cases where the course has started.
        // Allow viewing of any activity set as preview
        // if no preview activity is set, we use the same rules as if the course had started and redirect
        // to the preview activity.
        // AJAX_SCRIPT could be the inline forums, let it through.
        if (!AJAX_SCRIPT && !$this->course_started() && $previewcmid != 0 && $pagecm->id != $previewcmid
                && !$this->can_view_course($page->course->id)) {
            $url = $this->get_view_url(null);
            redirect($url, '', 0);
       }
    }

    /**
     * Allows course format to execute code on moodle_page::set_course()
     *
     * This function is executed before the output starts.
     *
     * If everything is configured correctly, user is redirected from the
     * default course view page to the activity view page.
     *
     * @param moodle_page $page instance of page calling set_course
     */
    public function page_set_course(moodle_page $page) {
        global $ME, $PAGE;

        $oncourseviewpage = $PAGE == $page && $page->has_set_url() &&
            $page->url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE);

        // If there is an AJAX SCRIPT, it's loading the navigation branch, let it continue per normal.
        // page_set_cm also does some redirections prior to course start.
        // If we aren't viewing the course page, we must be viewing one of the module pages and they will call set_cm.
        if (AJAX_SCRIPT || !$oncourseviewpage || $this->can_view_course($page->course->id)) {
            parent::page_set_course($page);
            return;
        }

        // Pass in module after if it's been made available to the page.
        $moduleafter = optional_param('moduleafter', 0, PARAM_INT);
        $options = array();
        if ($moduleafter !== 0) {
            $options['moduleafter'] = $moduleafter;
        }

        // The user has requested the course view page, we will redirect them to another
        // url if get_view_url returns a different url that isn't /course/view.php.
        $url = $this->get_view_url(null, $options);

        if ($url->compare(new moodle_url('/course/view.php'), URL_MATCH_BASE) ||
                $url->compare(new moodle_url($ME), URL_MATCH_EXACT)) {
            parent::page_set_course($page);
            return;
        }

        redirect($url, '', 0);
    }

    /**
     * Calculate the module that should be displayed to the user.
     *
     * In invisible, the first incomplete module that is valid will be displayed.  You may specify that
     * a current module, and the next visible one will be displayed.  If there is difficulty finding a module
     * the code will make a best attempt at a default module to show something to the user.
     *
     * @param course_modinfo $modinfo Course module information. eg; get_fast_modinfo()
     * @param bool $currentcmid false for default behaviour, course module id for next visible module in the course
     * @param bool $providebestattempt provide a default module if one can't be found.
     * @param bool $restricttosection Results should be limited to the section $currentcmid is in. $currentcmid is required if true.
     * @param string $type Which module you want back, 'next', 'prev', 'both'.
     * @throws coding_exception When an invalid parameter combination is sent.
     * @return null|cm_info|array null for no answer, 'next','prev' send the module, 'both' is array($prev,$next)
     */
    public function module_to_display($modinfo, $currentcmid = false, $providebestattempt = true,
                                      $restricttosection = false, $type = 'next') {
        $course = $this->get_course();
        $completioninfo = new completion_info($course);
        $previewactivity = null;
        $cmaftercompletion = null;
        $lastcoursecm = null;
        $completedatleastoneitem = false;
        $foundcurrentcmid = false;
        $previousactivity = null;
        $nextactivity = null;
        $currentcm = null;

        if ($restricttosection) {
            if ($currentcmid == false) {
                throw new coding_exception('If using $restricttosection, you must send a $currentcmid.');
            }
            $currentcm = get_coursemodule_from_id(false, $currentcmid, $course->id);
        }

        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->id == $currentcmid) {
                $foundcurrentcmid = true;
            }
            $allowedasnextorpreviousmodule = ($restricttosection === false || $cm->section == $currentcm->section);

            /* Is the module visible at all to the user? */

            if (!$this->module_is_visible($cm)) {
                continue;
            }

            // Only skip completion removal if we aren't looking for a specific module.
            if ($completioninfo->is_enabled($cm) == COMPLETION_DISABLED && $currentcmid === false) {
                continue;
            }

            /* Start calculating whether to redirect to this module */

            // Update the last visitable course module for this user.
            $lastcoursecm = $cm;

            // We may be asked for the previous activity, so track that while we look though the list.
            if ($currentcmid !== false && !$foundcurrentcmid && $cm->id != $currentcmid && $allowedasnextorpreviousmodule) {
                $previousactivity = $cm;
            }

            // If we asked for a module after a certain module, send the user there if this is
            // the first compatible module after finding where they are.
            if ($currentcmid !== false && $foundcurrentcmid && $cm->id != $currentcmid && $allowedasnextorpreviousmodule
                    && $nextactivity === null) {
                $nextactivity = $cm;
            }

            $completiondata = $completioninfo->get_data($cm, true, $modinfo->get_user_id());
            // If this item is the first incomplete item, redirect to that.
            // If anything is complete, then there can't be a preview.
            if ($completiondata->completionstate == COMPLETION_INCOMPLETE ||
                    $completiondata->completionstate == COMPLETION_COMPLETE_FAIL) {
                // If we not looking for a specific course module, we can redirect now.
                if ($currentcmid === false) {
                    return $cm;
                }
            } else {
                $completedatleastoneitem = true;
            }

            // Determine whether we have enough information to return to the user.
            if ($type == 'next' && isset($nextactivity)) {
                return $nextactivity;
            } else if ($type == 'previous' && isset($previousactivity)) {
                return $previousactivity;
            } else if ($type == 'both' && isset($nextactivity) && isset($previousactivity)) {
                return array($previousactivity, $nextactivity);
            }
        }

        // We searched through the entire course and didn't find the answer we were looking for.
        // If we don't want to guess what we are doing, return null.  Otherwise apply the best
        // attempt of where to send the user.
        if (!$providebestattempt) {
            return $this->module_return($type, $previousactivity, $nextactivity);
        }

        $previewactivity = $this->cm_before_course_start($modinfo);
        $cmaftercompletion = $this->cm_after_course_completion($modinfo);

        // We haven't finished anything, and could find a start.  Use preview if we had one.
        if ($previewactivity !== null && !$completedatleastoneitem) {
            return $this->module_return($type, $previewactivity, $previewactivity);
        }

        // If we haven't figured out where to redirect them to take them to the course completion.
        if ($cmaftercompletion !== null) {
            return $this->module_return($type, $cmaftercompletion, $cmaftercompletion);
        }

        // If there is the last activity in the course, use that.
        if ($lastcoursecm !== null) {
            return $this->module_return($type, $lastcoursecm, $lastcoursecm);
        }

        // There are no visible course items matching the current set of rules.  We can't send the student to
        // anything, so we will display them whatever is available on the standard course page.
        return $this->module_return($type, null, null);
    }

    /**
     * Determine if a course module should be displayed to the user based on rules for this course format.
     *
     * @param $cm cm_info A course module to test.
     * @return bool true is to be displayed, false otherwise.
     */
    private function module_is_visible($cm) {
        // If the item is not user visible, they will never be able to get there so ignore these modules
        // in all circumstances.
        if (!$cm->uservisible) {
            return false;
        }

        if ($this->module_is_hidden_from_view($cm)) {
            return false;
        }

        // We must be able to view the module type to consider being able to redirect there.
        if (!$cm->has_view()) {
            return false;
        }
        return true;
    }

    /**
     * Exclude modules that are indented so we can easily exclude items from the next/previous links.
     * They are also excluded from other locations based on this hidden criteria.
     * eg; recent forum posts block and oua navigation block.
     *
     * sytles.css has visual indicators for the selection made here, review that CSS if you are making changes.
     *
     * @param $cm cm_info The module to determine if it's hidden from view.
     * @return bool true for hidden, false otherwise.
     */
    public function module_is_hidden_from_view($cm) {
        if ($cm->indent >= 5) {
            return true;
        } else {
            return false;
        }
    }

    private function module_return($type, $previousactivity, $nextactivity) {
        // Determine whether we have enough information to return to the user.
        if ($type == 'next') {
            return $nextactivity;
        } else if ($type == 'previous') {
            return $previousactivity;
        } else if ($type == 'both') {
            return array($previousactivity, $nextactivity);
        }
        throw new coding_exception('Type now valid, must be "next","previous","both".');
    }

    /**
     * Return the view course module for this course at this point in time.
     *
     * @param array $options Array of option to process, moduleafter and user are used.
     * @return moodle_url|null url to redirect to, or null when no url was found.
     */
    public function get_view_cm($options = array()) {
        global $USER;

        $course = $this->get_course();

        // Pass in module after if it's been made available to the page.
        $moduleafter = false;
        if (isset($options['moduleafter'])) {
            $moduleafter = $options['moduleafter'];
        }

        // Default to current user, or the specified one in the options.
        if (isset($options['user'])) {
            $user = intval($options['user']);
        } else {
            $user = $USER->id;
        }

        $modinfo = get_fast_modinfo($course->id, $user);

        if (!$this->course_started()) {
            $startcm = $this->cm_before_course_start($modinfo);
            if ($startcm !== null) {
                return $startcm;
            }
        }

        // With completion, the user should have access to the course, so if we are
        // asking for a specific module next, then we don't redirect to the ending module.
        if ($this->course_completed($user) && $moduleafter === false) {
            $endingcm = $this->cm_after_course_completion($modinfo);
            if ($endingcm !== null) {
                return $endingcm;
            }
        }

        $cm = $this->module_to_display($modinfo, $moduleafter);

        return $cm;

    }

    /**
     * Return the view url for this course at this point in time.
     *
     * @param int|stdClass $section unused section information.
     * @param array $options Array of option to process, moduleafter and user are used.
     * @return moodle_url|null url to redirect to, or null when no url was found.
     */
    public function get_view_url($section, $options = array()) {

        $course = $this->get_course();

        if (($this->can_view_course($course->id) || isset($options['core_get_view_url'])) && !isset($options['get_format_view_url'])) {
            return parent::get_view_url($section, $options);
        }

        if (isset($options['firstmodule']) && $section !== null) {
            // Find the section and
            if (is_object($section)) {
                $sectionno = $section->section;
            } else {
                $sectionno = $section;
            }

            $courseinfo = get_fast_modinfo($course);

            if ($this->course_started() || $this->cm_before_course_start($courseinfo) === null) {
                // If there is a startcm, send all weeks links to that.

                // The requested section has no items and can't be shown.
                if (!isset($courseinfo->sections[$sectionno])) {
                    return null;
                }

                foreach ($courseinfo->sections[$sectionno] as $cmid) {
                    if ($this->module_is_visible($courseinfo->get_cm($cmid))) {
                        return $courseinfo->get_cm($cmid)->url;
                    }
                }
                return null;
            }
            // Intentionally fall through to the the default when the course hasn't started.
        }

        $cm = $this->get_view_cm($options);

        // If we don't find a module, send the user to the default page or topics.
        if ($cm === null) {
            return parent::get_view_url($section, $options);
        }

        return $cm->url;
    }

    /**
     * Determine if the course has started yet.
     *
     * @param null $time Optional fixed time to calculate if the course has started.
     * @return bool true if the course has started.
     */
    protected function course_started($time = null) {
        if ($time === null) {
            $time = time();
        }

        return ($time >= $this->get_course()->startdate);
    }

    /**
     * Determine if the course is completed for the purpose of what module to display.
     *
     * @param integer $userid Which user id to test if they are completed.
     * @param integer $time Optionally inject the time as unixtimestamp to this calculation.
     * @return bool true if the course is completed.
     */
    protected function course_completed($userid, $time = null) {

        if ($time === null) {
            $time = time();
        }

        // If the course has finished, force true, otherwise we decide based on whether the course is complete.
        $options = $this->get_format_options();
        if ($time > $options['courseenddate']) {
            return true;
        }

        return $this->moodle_course_complete($userid);

    }

    /**
     * Determine if the course is complete using moodle's completion logic.
     *
     * @param integer $userid The user to calculate if the course is completed.
     * @return bool true on course complete, false otherwise.
     */
    private function moodle_course_complete($userid) {
        $completioninfo = new completion_info($this->get_course());
        if ($completioninfo->is_course_complete($userid)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Determine the module to display when the course has not yet started.
     *
     * @param course_modinfo $modinfo Course module information. eg; get_fast_modinfo();
     * @return cm_info|null The course module to display, or null when unknown.
     */
    protected function cm_before_course_start($modinfo) {
        $cmid = $this->get_course()->coursepreviewactivity;

        return $this->get_cm_or_null($modinfo, $cmid);
    }

    /**
     * @param course_modinfo $modinfo Course module information. eg; get_fast_modinfo()
     * @return cm_info|null The completed module to display or null when unknown.
     */
    protected function cm_after_course_completion($modinfo) {
        $cmid = $this->get_course()->coursecompleteactivity;

        return $this->get_cm_or_null($modinfo, $cmid);

    }

    /**
     * Get details of a course module based on cmid.  But return null if one isn't found.
     *
     * @param course_modinfo $modinfo Course module information. eg; get_fast_modinfo()
     * @param integer $cmid A course module ID for this course.
     * @return cm_info|null The completed module to display or null when unknown.
     * @throws Exception
     * @throws moodle_exception
     */
    private function get_cm_or_null($modinfo, $cmid) {
        // If there is no preview module, we can't redirect you to it.
        if ($cmid === null) {
            return null;
        }

        // If the course modules change and the completion module is deleted, we behave like there isn't one.
        try {
            $cm = $modinfo->get_cm($cmid);
            if ($cm->uservisible) {
                return $cm;
            }
        } catch (moodle_exception $e) {
            if ($e->errorcode = 'invalidcoursemodule') {
                return null;
            }
            throw $e;
        }

        // We didn't find an appropriate item for the user, return no starting item.
        return null;
    }
    /**
     * Can the user view the normal course display page.
     *
     * @param integer $courseid The course id of the course to test.
     * @return bool true or false depending on privileges.
     */
    private function can_view_course($courseid) {
        $context = context_course::instance($courseid);
        return has_capability('moodle/course:setcurrentsection', $context);
    }

    public function course_content_header() {
        global $PAGE;

        $data = new \stdClass();

        $pos = strpos($this->get_course()->shortname,'-');
        if($pos === false) {
            $data->shortname = $this->get_course()->shortname;
        } else {
            $data->shortname =  substr($this->get_course()->shortname, 0, $pos);
        }

        if (isset($PAGE->cm->id)) {
            $data->activitytype = $PAGE->cm->modfullname;
            $data->activitytitle = $PAGE->cm->name;
            $data->activityicon = $PAGE->cm->get_icon_url();
        }

        $data->courseid = $this->get_courseid();
        $data->fullname = $this->get_course()->fullname;

        $url = $this->get_branding_url();
        $data->cobrandinglogo = $url instanceof moodle_url ? $url->out_as_local_url() : null;
        $data->cobrandingname = $this->get_format_options()['cobrandingname'];

        // Next and previous are only available if the course has started & the activity is visible for viewing
        if (isset($PAGE->cm->id) && $this->course_started() && !$this->module_is_hidden_from_view($PAGE->cm)) {
            $modinfo = get_fast_modinfo($this->get_course());
            list($data->previouscm, $data->nextcm) = $this->module_to_display($modinfo, $PAGE->cm->id, false, false, 'both');
        } else {
            $data->previouscm = null;
            $data->nextcm = null;
        }

        $PAGE->requires->js_call_amd("format_invisible/format_invisible", 'initialise');
        return new contentheader($data);
    }

    /**
     * Get the branding URL for the graphic to be displayed.
     *
     * @return moodle_url|null URL of graphic or null if there isn't one.
     */
    public function get_branding_url() {
        $context = context_course::instance($this->get_courseid());

        $fs = get_file_storage();
        $filelist = $fs->get_area_files($context->id, 'format_invisible', 'cobrandinglogo', 0, "itemid", false);
        foreach ($filelist as $file) {
            return moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
        }
        return null;
    }
}

/**
 * Serves format options attachmented files.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function format_invisible_pluginfile($course,
                           $cm,
                           context $context,
                           $filearea,
                           $args,
                           $forcedownload,
                           array $options=array()) {

    if ($context->contextlevel != CONTEXT_COURSE) {
        return false;
    }

    // If you have the login privilege for this course, then you can view this file.
    // If you don't login, the screen displaying that you are unable to login will display a broken image.
    require_login($course, false, $cm);

    $itemid = (int)array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $relativepath = implode('/', $args);

    $fullpath = "/{$context->id}/format_invisible/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}
