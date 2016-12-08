<?php
namespace block_oua_course_list;
use context_course;
use context_user;
use grade_item;
use grade_grade;
use grade_report_overview;
require_once($CFG->dirroot . '/grade/report/overview/lib.php');
/**
 * Class providing an API for the overview report building and displaying.
 *
 * This function is a copy of grade_report_overview->fill_table() function
 * It should produce the same output as the grade overview report
 * It needs to extend grade_report overview to access the private method "blank_hidden_total_and_adjust_bounds"
 * There is a test that verifies the output produces the same grade iunc ase core changes underneath us.
 *
 * @uses grade_report
 * @package gradereport_overview
*/
class oua_grade_report_overview extends grade_report_overview {
    /**
     * Get the course final grade using grade reports private functions.
     *
     * @param $course
     * @return float|null|string
     * @throws \coding_exception
     */
    public function get_course_final_grade($course) {
        global $USER;
        $finalgrade = '';

        if (!$course->showgrades) {
            return $finalgrade;
        }

        $coursecontext = context_course::instance($course->id);

        if (!$course->visible && !has_capability('moodle/course:viewhiddencourses', $coursecontext)) {
            // The course is hidden and the user isn't allowed to see it
            return $finalgrade;
        }

        if (!has_capability('moodle/user:viewuseractivitiesreport',
                            context_user::instance($this->user->id)) && ((!has_capability('moodle/grade:view',
                                                                                          $coursecontext) || $this->user->id != $USER->id) && !has_capability('moodle/grade:viewall',
                                                                                                                                                              $coursecontext))
        ) {
            return $finalgrade;
        }

        $canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);

        // Get course grade_item
        $course_item = grade_item::fetch_course_item($course->id);

        // Get the stored grade
        $course_grade = new grade_grade(array('itemid' => $course_item->id, 'userid' => $this->user->id));
        $course_grade->grade_item =& $course_item;
        $finalgrade = $course_grade->finalgrade;

        if (!$canviewhidden and !is_null($finalgrade)) {
            if ($course_grade->is_hidden()) {
                $finalgrade = null;
            } else {
                $adjustedgrade = $this->blank_hidden_total_and_adjust_bounds($course->id, $course_item, $finalgrade);

                // We temporarily adjust the view of this grade item - because the min and
                // max are affected by the hidden values in the aggregation.
                $finalgrade = $adjustedgrade['grade'];
                $course_item->grademax = $adjustedgrade['grademax'];
                $course_item->grademin = $adjustedgrade['grademin'];
            }
        } else {
            // We must use the specific max/min because it can be different for
            // each grade_grade when items are excluded from sum of grades.
            if (!is_null($finalgrade)) {
                $course_item->grademin = $course_grade->get_grade_min();
                $course_item->grademax = $course_grade->get_grade_max();
            }
        }

        return grade_format_gradevalue($finalgrade, $course_item, true);
    }

}
