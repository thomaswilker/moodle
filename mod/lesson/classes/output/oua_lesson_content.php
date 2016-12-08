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
 * Lesson content renderable, surrounds lesson content with mustache template
 *
 * @package    mod_lesson
 * @copyright  2016 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace mod_lesson\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;
use moodle_url;
use context_module;

require_once(__DIR__ . '/../../locallib.php');

/**
 * OUA customised lesson content page.
 *
 * @package    mod_lesson
 * @copyright  2016 Ben Kelada (ben.kelada@open.edu.au)
 */
class oua_lesson_content implements renderable, templatable {
    private $lesson;
    private $oldcontent;

    public function __construct($cm, $lesson, $oldcontent) {
        $this->lesson = $lesson;
        $this->oldcontent = $oldcontent;
        $this->cm = $cm;
        $this->context = context_module::instance($cm->id);
        $this->current_progress = $this->get_progress_bar_percent();
        $this->is_teacher = has_capability('mod/lesson:manage', $this->context);
        $this->show_menu = $lesson->displayleft;
        $this->menu_pages = $this->get_pages_for_menu();
    }

    private function get_pages_for_menu() {

        $pages = $this->lesson->load_all_pages();
        $menupages = array();
        foreach ($pages as $page) {
            if ((int)$page->prevpageid === 0) {
                $pageid = $page->id;
                break;
            }
        }
        $currentpageid = optional_param('pageid', $pageid, PARAM_INT);

        if (!$pageid || !$pages) {
            return null;
        }

        while ($pageid != 0) {
            $page = $pages[$pageid];

            // Only process branch tables with display turned on
            if ($page->displayinmenublock && $page->display) {
                $menupages[] = array('page_title' => $page->title,
                                     'page_title_formatted' => format_string($page->title, true),
                                     'page_id' => $page->id,
                                     'current_page' => $page->id == $currentpageid,
                                     'page_url' => new moodle_url('/mod/lesson/view.php', array('id' => $this->cm->id, 'pageid' => $page->id))
                                     );
            }
            $pageid = $page->nextpageid;
        }
        return $menupages;
    }

    /**
     * This function is a copy of the existing lesson progress bar calculation.
     * The moodle help docs say the progress should only be used for linear lessons
     * not lessons that "Jump around".
     * OUA have decided to disable the progress bar for lessons completely,
     * this function is left here for when they change their minds.
     * @return int
     */

    private function get_progress_bar_percent() {
        global $DB, $USER;

        if (!isset($USER->modattempts[$this->lesson->id]) && $this->lesson->progressbar) {
            // all of the lesson pages
            $pages = $this->lesson->load_all_pages();
            foreach ($pages as $page) {
                if ($page->prevpageid == 0) {
                    $pageid = $page->id;  // find the first page id
                    break;
                }
            }

            // current attempt number
            if (!$ntries = $DB->count_records("lesson_grades", array("lessonid" => $this->lesson->id, "userid" => $USER->id))) {
                $ntries = 0;  // may not be necessary
            }

            $viewedpageids = array();
            if ($attempts = $this->lesson->get_attempts($ntries, false)) {
                foreach ($attempts as $attempt) {
                    $viewedpageids[$attempt->pageid] = $attempt;
                }
            }

            $viewedbranches = array();
            // collect all of the branch tables viewed
            if ($branches = $DB->get_records("lesson_branch",
                                             array("lessonid" => $this->lesson->id, "userid" => $USER->id, "retry" => $ntries),
                                             'timeseen ASC', 'id, pageid')
            ) {
                foreach ($branches as $branch) {
                    $viewedbranches[$branch->pageid] = $branch;
                }
                $viewedpageids = array_merge($viewedpageids, $viewedbranches);
            }

            // Filter out the following pages:
            //      End of Cluster
            //      End of Branch
            //      Pages found inside of Clusters
            // Do not filter out Cluster Page(s) because we count a cluster as one.
            // By keeping the cluster page, we get our 1
            $validpages = array();
            while ($pageid != 0) {
                $pageid = $pages[$pageid]->valid_page_and_view($validpages, $viewedpageids);
            }

            // progress calculation as a percent
            $progress = round(count($viewedpageids) / count($validpages), 2) * 100;
        } else {
            $progress = 100;
        }
        return $progress;
    }

    /**
     * Export data for book view so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = new stdClass();
        $data->oldcontent = $this->oldcontent;
        $data->show_progress_bar = $this->lesson->progressbar;
        $data->show_menu = $this->show_menu;
        $data->show_header = ($data->show_menu || $data->show_progress_bar);
        $data->current_progress = $this->current_progress;
        $data->is_teacher = $this->is_teacher;
        $data->cmid = $this->cm->id;
        $data->menu_pages = $this->menu_pages;
        $data->page_base_url = new moodle_url('/mod/lesson/view.php', array('id' => $this->cm->id ));
        return $data;
    }

}
