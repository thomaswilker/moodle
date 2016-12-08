<?php
namespace block_oua_course_list_teacher\output;

use context_course;
use completion_info;
use stdClass;
use moodle_url;
use coursecat;
use block_oua_course_list_teacher\oua_grade_report_overview;
use block_oua_course_list_teacher\oua_grade_report_user;

defined('MOODLE_INTERNAL') || die;

class course_list_teacher_renderable implements \renderable, \templatable {
    private $mycoursecompletedlist = array();
    private $mycoursecompletedlisthidden = array();
    private $mycourselist = array();
    private $mycourselisthidden = array();
    /**
     * Value of visible items
     * @var int
     */
    private $courselistlength;

    private $hiddencategoryid = null;

    public function __construct($config, $userid) {
        global $CFG;

        $this->courselistlength = isset($config->defaultcourselistlength) ? $config->defaultcourselistlength : 10;
        $this->hiddencategoryid = isset($config->hiddencategoryid) ? $config->hiddencategoryid : null;

        if (!empty($CFG->navsortmycoursessort)) {
            // sort by admin settings
            $sortorder = 'visible DESC, ' . $CFG->navsortmycoursessort . ' ASC';
        } else {
            // default sort config by oua settings
            $sortorder = 'visible DESC, sortorder ASC';
        }

        $lists = $this->generate_sorted_and_split_lists($sortorder, $userid, $this->courselistlength);
        $this->mycourselist = $lists->mycourselist;
        $this->mycourselisthidden = $lists->mycourselisthidden;
        $this->mycoursecompletedlist = $lists->mycoursecompletedlist;
        $this->mycoursecompletedlisthidden = $lists->mycoursecompletedlisthidden;
    }

    /**
     * Produce lists (current/completed) that are sorted according to options
     * @param string $sortorder
     * @param int $userid
     * @param int $numberofvisibleitems
     */
    private function generate_sorted_and_split_lists($sortorder, $userid, $numberofvisibleitems) {
        $lists = new stdClass();

        /**
         * The complete course list with all attributes
         * This list is already default sorted by whatever settings passed in by the system
         */
        $allunits = $this->get_course_list_teacher($sortorder, $userid);

        $currentunits = $this->extract_list($allunits, 'enddate', time(), '>=');
        // sort currentlist most current first, those with no start date will sink to bottom with 0 as their values
        $currentunits = $this->sort_multi($currentunits, 'startdate', SORT_ASC);
        // split for hidden and visbile
        $lists->mycourselist = array_splice($currentunits, 0, $numberofvisibleitems);
        $lists->mycourselisthidden = $currentunits; // whatever left over

        $completedunits = $this->extract_list($allunits, 'enddate', time(), '<=');
        // sort completed untis most current first, those with no enddate will sink to bottom with 0 as their values
        $completedunits = $this->sort_multi($completedunits, 'enddate', SORT_DESC);
        $lists->mycoursecompletedlist = array_splice($completedunits, 0, $numberofvisibleitems);
        $lists->mycoursecompletedlisthidden = $completedunits; // whatever left over

        return $lists;
    }

    /**
     * @param string $sortorder
     * @return array $mycourselist
     * @throws \coding_exception
     */
    private function get_course_list_teacher($sortorder, $userid) {
        $mycourselist = array();
        if ($courses = enrol_get_my_courses('showgrades', $sortorder)) {
            foreach ($courses as $course) {
                if ($this->hiddencategoryid !== null) {
                    // Hide this course from course lists if it is in the configured hidden category.
                    $category = coursecat::get($course->category);
                    $parentcats = coursecat::get_many($category->get_parents());
                    $allcats = array($category->id => $category) + $parentcats;
                    if (array_key_exists($this->hiddencategoryid, $allcats)) {
                        continue;
                    }
                }

                $coursecontext = context_course::instance($course->id);
                $courseformat = course_get_format($course->id);
                $sections = array();
                foreach ($courseformat->get_sections() as $section) {
                    $sectionno = $section->section;
                    $sectionlist['section'] = $sectionno;
                    $sectionlist['name'] = $courseformat->get_section_name($sectionno);
                    $sectionlist['url'] = $courseformat->get_view_url($sectionno, array('firstmodule' => true));
                    $sections[] = $sectionlist;
                }

                $cobrandinglogo = '';
                $cobrandingname = '';
                if (method_exists($courseformat, 'get_branding_url') && $courseformat->get_branding_url() !== null) {
                    $cobrandinglogo = $courseformat->get_branding_url();
                    $options = $courseformat->get_format_options();
                    $cobrandingname = $options['cobrandingname'];
                }

                $completion = new completion_info($course);
                $coursecomplete = $completion->is_course_complete($userid);

                $nextcmname = false;
                if (is_callable(array($courseformat, 'get_view_cm'))) { // Only custom invisible format returns next/current cm.
                    // Check that next course module is available.
                    $nextcm = $courseformat->get_view_cm();
                    if ($nextcm !== null) {
                        $nextcmname = $nextcm->name;
                    }
                }

                $courseformatoptions = $courseformat->get_format_options();

                $enddate = (isset($courseformatoptions['courseenddate'])) ? $courseformatoptions['courseenddate'] // expect int value
                    : time() + 1000; // no end date, put it in the future.

                $coursetoadd = array("title" => format_string($course->shortname, true, array('context' => $coursecontext)),
                                     "courseid" => $course->id,
                                     "courseviewurl" => $courseformat->get_view_url(0),
                                     "nextactivityname" => $nextcmname,
                                     "coursename" => format_string(get_course_display_name_for_list($course)),
                                     "cobrandinglogo" => $cobrandinglogo, "cobrandingname" => $cobrandingname,
                                     "coursesections" => $sections, "startdate" => $course->startdate, "enddate" => $enddate,
                                     "complete" => (bool)$coursecomplete);

                $coursetoadd['finalgradelink'] = new moodle_url('/grade/report/grader/index.php', array('id' => $course->id));
                $coursetoadd['managemessageslink'] = new moodle_url('/blocks/message_broadcast/managemessages.php',
                                                                    array('courseid' => $course->id));
                $mycourselist[] = $coursetoadd;
            }
        }
        return $mycourselist;
    }

    /**
     * Helper function
     * Extract a sub list from courses where the courses match the attributes
     * @param array $list of courses
     * @param string $field name of the field to filter the array
     * @param mix $value value of the field name used for extracting the element
     * @return array of courses with the matching attributes
     */
    public function extract_list($list, $field, $value, $ops = '==') {

        $extractlist = array();
        foreach ($list as $item) {
            if (isset($item[$field])) {
                $isValue = false;
                switch (trim($ops)) {
                    case '==':
                        $isValue = ($item[$field] == $value);
                        break;
                    case '!=':
                        $isValue = ($item[$field] != $value);
                        break;
                    case '>':
                        $isValue = ($item[$field] > $value);
                        break;
                    case '>=':
                        $isValue = ($item[$field] >= $value);
                        break;
                    case '<':
                        $isValue = ($item[$field] < $value);
                        break;
                    case '<=':
                        $isValue = ($item[$field] <= $value);
                        break;
                    default:
                        $isValue = false;
                        break;
                }
                if ($isValue) {
                    $extractlist[] = $item;
                }
            }
        }
        return $extractlist;
    }

    /**
     * Helper function
     * Sort courses based on field values in courses and order flags
     * Example: $sorted = sort_multi($to_be_sorted, 'enddate', SORT_DESC, 'shortname', SORT_ASC);
     * This will sort the $to_be_sorted by enddate desc, then shortname asc
     * This assumes that there are 'enddate' and 'shortname' keys and values for sorting in the $to_be_sorted
     * @return array $data_sorted
     * @internal param mixed array $data_to_sort [, string $field_name, int sort_order_flags [, ...]]
     */
    public function sort_multi() {

        $args = func_get_args();

        // $data to be sorted
        $data = array_shift($args);
        foreach ($args as $n => $field) {

            // turn this arg into an array of values used for sorted
            if (is_string($field)) {

                // create the array of values for sorting
                $tmp = array();
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }

        // data is the last argument required by array_multisort
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);

        // return $data_sorted by value
        return array_pop($args);
    }

    /**
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(\renderer_base $output) {
        $data = new StdClass();
        $data->courselist = array_values($this->mycourselist);
        $data->courselisthidden = array_values($this->mycourselisthidden);
        $data->courselistcount = count($data->courselist) + count($data->courselisthidden);
        $data->courselistshowmorerequired = count($data->courselisthidden) > 0;

        $data->completedcourselist = array_values($this->mycoursecompletedlist);
        $data->completedcourselisthidden = array_values($this->mycoursecompletedlisthidden);
        $data->completedcourselistcount = count($data->completedcourselist) + count($data->completedcourselisthidden);
        $data->courselistlength = $this->courselistlength;
        $data->completedcourselistshowmorerequired = count($data->completedcourselisthidden) > 0;

        return $data;
    }
}
