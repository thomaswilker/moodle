<?php
namespace block_oua_course_list;
use context_course;
use context_user;
use grade_item;
use grade_grade;
use grade_report_user;
require_once($CFG->dirroot . '/grade/report/user/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
/**
 * Class providing an API for the overview report building and displaying.
 *
 * This function is a copy of grade_report_overview->fill_table() function
 * It should produce the same output as the grade overview report
 * It needs to extend grade_report overview to access the private method "blank_hidden_total_and_adjust_bounds"
 * There is a test that verifies the output produces the same grade iunc ase core changes underneath us.
 *
 * THIS FUNCTION IS SUPER SLOW FOR MULTIPLE COURSES AT ONCE
 * Its good to cache the output.
 *
 * @uses grade_report
 * @package gradereport_overview
*/
class oua_grade_report_user extends grade_report_user {
    /**
     * Get the course final grade using grade reports private functions.
     *
     * @param $course
     * @return float|null|string
     * @throws \coding_exception
     */
    public function get_oua_table_data() {
       $this->fill_table();

        foreach($this->tabledata as $tablerow) {
            if(isset($tablerow['grade'])) {
                // This row is a grade item
                $flatdata[] = array(
                    'itemname' => $tablerow['itemname']['content'],
                    'grade' => $tablerow['grade']['content']
                );
            }
        }
        return $flatdata;
    }

}
