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
 * Abstract class defining a renderer test. The test is responsible for creating
 * an instance of a renderer and either calling render on a renderable or calling
 * a render_xxx method directly. The render test class contains additional properties
 * so that it can be displayed nicely in the element library admin tool.
 *
 * @package    core
 * @category   output
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core\output;

/**
 * A renderer test class for headings.
 *
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class heading_renderer_sample extends renderer_sample_base {

    /** @var string $text The text of the heading */
    private $text = '';

    /** @var int $level The heading level */
    private $level = 2;

    /**
     * Constructor for this heading test. There should be several tests to show different heading
     * levels.
     */
    public function __construct($name, $docs, $text, $level = 2) {
        $this->name = $name;
        $this->docs = $docs;
        $this->text = $text;
        $this->level = $level;
        $this->category = renderer_sample_base::CATEGORY_ELEMENT;
    }

    /**
     * This method is responsible for running this test. It just calls $OUTPUT->heading().
     *
     * @return string
     */
    public function execute() {
        global $OUTPUT;

        return $OUTPUT->heading($this->text, $this->level);
    }

}
