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
 * Abstract class for a renderer_test.
 *
 * @copyright  2014 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class renderer_test_base {

    const CATEGORY_ELEMENT = 1;
    const CATEGORY_COMPONENT = 2;
    const CATEGORY_LAYOUT = 3;

    /** @var int $category The category of this renderer test. */
    protected $category = self::CATEGORY_ELEMENT;

    /** @var string $component - The component that defines this renderer. */
    protected $component = 'core';

    /** @var string name - Short test name (shown in a drop down list) */
    protected $name = '';

    /** @var string docs - Markdown formatted docs for theme designers. */
    protected $docs = '';

    /**
     * This method is responsible for running this test. It may create the renderer / renderable
     * and call render() - or it may call a render_xxx() method directly. The important thing
     * is that all the data required to render this thing should be mocked and passed to the
     * real method/class that is being tested, and that class should not perform any additional
     * logic / DB queries etc. This is important, because this function should be able to work
     * with entirely fake data - without modifying the database etc.
     *
     * @return string
     */
    public abstract function execute();

    /**
     * Get the short name for this test.
     *
     * @return string The short name for this test.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get markdown processed docs for this test.
     *
     * @return string The markdown processed docs.
     */
    public function get_docs() {
        return markdown_to_html($this->docs);
    }

    /**
     * Get the component for this test.
     *
     * @return string The component defining this test.
     */
    public function get_component() {
        return $this->component;
    }

    /**
     * Set the component for this test, set automatically by the finder.
     *
     * @param string $component - The component defining this test.
     */
    public function set_component($component) {
        $this->component = $component;
    }

    /**
     * Get the category for this test.
     *
     * @return int - The test category, one of the renderer_test::CATEGORY_* constants.
     */
    public function get_category() {
        return $this->category;
    }
}
