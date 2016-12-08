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
 * Book view renderable, containing data for book view page.
 *
 * @package    mod_book
 * @copyright  2016 Ben Kelada (ben.kelada@open.edu.au)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_book\output;

use renderable;
use templatable;
use renderer_base;
use stdClass;
use moodle_url;
use context_module;

require_once(__DIR__ . '/../../locallib.php');

/**
 * Book view renderable, containing data for book view page.
 *
 * @package    mod_book
 * @copyright  2016 Ben Kelada (ben.kelada@open.edu.au)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class book_view_page implements renderable, templatable {
    private $book;
    private $allchapters;
    private $edit;
    private $toc;
    private $move_url_base;
    private $edit_url_base;
    private $delete_url_base;
    private $show_url_base;
    private $visiblechapters;

    public function __construct($cm, $book, $edit) {
        global $USER;

        $this->book = $book;
        $this->edit = $edit;

        $cmcontext = context_module::instance($cm->id);
        $this->toc = array();
        $this->allchapters = book_preload_chapters($book);
        $this->move_url_base = new moodle_url('move.php', array('id' => $cm->id, 'sesskey' => $USER->sesskey));
        $this->edit_url_base = new moodle_url('edit.php', array('cmid' => $cm->id, 'sesskey' => $USER->sesskey));
        $this->delete_url_base = new moodle_url('delete.php', array('id' => $cm->id, 'sesskey' => $USER->sesskey));
        $this->show_url_base = new moodle_url('show.php', array('id' => $cm->id, 'sesskey' => $USER->sesskey));
        $this->prepare_visible_chapters_for_display($cmcontext);
    }

    protected function prepare_visible_chapters_for_display($cmcontext) {
        $totalch = 0;
        $tempchnum = 0;
        $subchnum = 0;
        $currentchnum = 'x';
        $tempsubnum = 'x';
        $previousid = null;
        $displaypagenum = 0;

        foreach ($this->allchapters as $id => $chapter) {
            if ($this->edit || $chapter->hidden == false) {
                $totalch++;
                $displaypagenum++;
                $chaptertext = file_rewrite_pluginfile_urls($chapter->content, 'pluginfile.php', $cmcontext->id, 'mod_book',
                                                            'chapter', $chapter->id);
                $chapter->chaptertextformatted = format_text($chaptertext, $chapter->contentformat,
                                                             array('noclean' => true, 'overflowdiv' => true,
                                                                   'context' => $cmcontext));
                if (!$chapter->subchapter) {
                    $tempchnum++;
                    if ($subchnum != 0) {
                        // Helper for table of contents indentation.
                        $lastchapter = end($this->visiblechapters);
                        $lastchapterkey = key($this->visiblechapters);
                        if ($lastchapter->parent) {
                            // If the last chapter was a sub chapter.
                            $this->visiblechapters[$lastchapterkey]->last_child = true;
                        }
                    }
                    if ($tempchnum == 1) {
                        // First element.
                        $chapter->first_item = true;
                    }
                    // Normal chapter, reset subchapter to 0.
                    $subchnum = 0;
                    $currentchnum = $chapter->hidden ? 'x' : $tempchnum;
                    $chapter->toc_number = $tempchnum;
                } else {
                    $subchnum++;
                    $tempsubnum = $chapter->hidden ? 'x' : $subchnum;
                    $chapter->toc_number = "{$currentchnum}";
                    $chapter->sub_toc_number = "{$currentchnum}.{$tempsubnum}";

                    if ($subchnum == 1) {
                        // Helper for table of contents indentation.
                        $chapter->firstsub = true;
                        $lastchapter = end($this->visiblechapters);
                        $lastchapterkey = key($this->visiblechapters);
                        $this->visiblechapters[$lastchapterkey]->has_children = true;
                    }
                }
                if ($this->book->numbering !== BOOK_NUM_NUMBERS) {
                    $chapter->toc_number = '';
                    $chapter->sub_toc_number = '';
                }
                if (!$this->book->customtitles) {
                    if (!$chapter->subchapter) {
                        $chapter->title_formatted = trim(\format_string($chapter->title, true, array('context' => $cmcontext)));
                    } else {
                        $parentid = $this->allchapters[$chapter->id]->parent;
                        $chapter->subtitle_formatted = trim(\format_string($chapter->title, true, array('context' => $cmcontext)));
                        $chapter->title_formatted = trim(\format_string($this->allchapters[$parentid]->title, true,
                                                                        array('context' => $cmcontext)));
                    }
                }
                $chapter->toc_title = trim(\format_string($chapter->title, true, array('context' => $cmcontext)));
                $chapter->displaypagenum = $displaypagenum;
                $this->visiblechapters[] = $chapter;
            }
        }
        if (!empty($this->visiblechapters)) {
            $lastchapter = end($this->visiblechapters);
            $lastchapterkey = key($this->visiblechapters);
            $this->visiblechapters[$lastchapterkey]->last_item = true;
            if ($lastchapter->parent) {
                // Helper for table of contents.
                // If the last chapter was a sub chapter.
                $this->visiblechapters[$lastchapterkey]->last_child = true;
            }
        }
    }

    public function get_book_toc_css_class($style) {
        $class = '';
        switch($style) {
            case BOOK_NUM_NONE:
                $class .= ' book_toc_none';
                break;
            case BOOK_NUM_NUMBERS:
                $class .= ' book_toc_numbered';
                break;
            case BOOK_NUM_BULLETS:
                $class .= ' book_toc_bullets';
                break;
            case BOOK_NUM_INDENTED:
                $class .= ' book_toc_indented';
                break;

        }
        return $class;
    }
    public function get_page_number_for_chapterx($chapterid) {
        $pagenum = 0;
        foreach ($this->visiblechapters as $chapter) {
            if ($chapter->id == $chapterid) {
                $pagenum = $chapter->displaypagenum;
                break;
            }
        }
        return $pagenum;
    }

    /**
     * Export data for book view so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = new stdClass();
        $data->edit = $this->edit;
        $data->visiblechapters = $this->visiblechapters;
        $data->total_pages = count($this->visiblechapters);
        $data->move_url = $this->move_url_base;
        $data->edit_url = $this->edit_url_base;
        $data->delete_url = $this->delete_url_base;
        $data->show_url = $this->show_url_base;
        $data->book_name = $this->book->name;
        $data->bookid = $this->book->id;
        $data->toc_class = $this->get_book_toc_css_class($this->book->numbering);
        return $data;
    }

    public function get_chapters() {
        return $this->visiblechapters;
    }
}
