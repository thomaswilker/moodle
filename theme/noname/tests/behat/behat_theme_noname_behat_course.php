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
 * Behat course-related steps definitions overrides.
 *
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../course/tests/behat/behat_course.php');

use Behat\Gherkin\Node\TableNode as TableNode,
    Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Exception\DriverException as DriverException,
    Behat\Mink\Exception\ElementNotFoundException as ElementNotFoundException;

/**
 * Course-related steps definitions overrides.
 *
 * @copyright  2016 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_noname_behat_course extends behat_course {

    public function i_open_actions_menu($activityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        // If it is already opened we do nothing.
        $activitynode = $this->get_activity_node($activityname);

        // Find the menu.
        $menunode = $activitynode->find('css', 'a[data-toggle=dropdown]');
        $expanded = $menunode->getAttribute('aria-expanded');
        if ($expanded == 'true') {
            return;
        }

        $this->execute('behat_course::i_click_on_in_the_activity',
            array("a[data-toggle='dropdown']", "css_element", $this->escape($activityname))
        );

    }

    public function i_close_actions_menu($activityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        // If it is already closed we do nothing.
        $activitynode = $this->get_activity_node($activityname);
        // Find the menu.
        $menunode = $activitynode->find('css', 'a[data-toggle=dropdown]');
        $expanded = $menunode->getAttribute('aria-expanded');
        if ($expanded != 'true') {
            return;
        }

        $this->execute('behat_course::i_click_on_in_the_activity',
            array("a[data-toggle='dropdown']", "css_element", $this->escape($activityname))
        );
    }

    public function actions_menu_should_be_open($activityname) {

        if (!$this->running_javascript()) {
            throw new DriverException('Activities actions menu not available when Javascript is disabled');
        }

        $activitynode = $this->get_activity_node($activityname);
        // Find the menu.
        $menunode = $activitynode->find('css', 'a[data-toggle=dropdown]');
        $expanded = $menunode->getAttribute('aria-expanded');
        if ($expanded != 'true') {
            throw new ExpectationException(sprintf("The action menu for '%s' is not open", $activityname), $this->getSession());
        }
    }

    public function i_add_to_section($activity, $section) {

        if ($this->getSession()->getPage()->find('css', 'body#page-site-index') && (int)$section <= 1) {
            // We are on the frontpage.
            if ($section) {
                // Section 1 represents the contents on the frontpage.
                $sectionxpath = "//body[@id='page-site-index']/descendant::div[contains(concat(' ',normalize-space(@class),' '),' sitetopic ')]";
            } else {
                // Section 0 represents "Site main menu" block.
                $sectionxpath = "//*[contains(concat(' ',normalize-space(@class),' '),' block_site_main_menu ')]";
            }
        } else {
            // We are inside the course.
            $sectionxpath = "//li[@id='section-" . $section . "']";
        }

        $activityliteral = behat_context_helper::escape(ucfirst($activity));

        if ($this->running_javascript()) {

            // Clicks add activity or resource section link.
            $sectionxpath = $sectionxpath . "/descendant::div[contains(concat(' ', normalize-space(@class) , ' '), ' section-modchooser ')]/span/a";
            $sectionnode = $this->find('xpath', $sectionxpath);
            $sectionnode->click();

            // Clicks the selected activity if it exists.
            $activityxpath = "//div[@id='chooseform']/descendant::label" .
                "/descendant::span[contains(concat(' ', normalize-space(@class), ' '), ' typename ')]" .
                "[normalize-space(.)=$activityliteral]" .
                "/parent::label/child::input";
            $activitynode = $this->find('xpath', $activityxpath);
            $activitynode->doubleClick();

        } else {
            // Without Javascript.

            // Selecting the option from the select box which contains the option.
            $selectxpath = $sectionxpath . "/descendant::div[contains(concat(' ', normalize-space(@class), ' '), ' section_add_menus ')]" .
                "/descendant::select[option[normalize-space(.)=$activityliteral]]";
            $selectnode = $this->find('xpath', $selectxpath);
            $selectnode->selectOption($activity);

            // Go button.
            $gobuttonxpath = $selectxpath . "/ancestor::form/descendant::input[@type='submit']";
            $gobutton = $this->find('xpath', $gobuttonxpath);
            $gobutton->click();
        }

    }

}
