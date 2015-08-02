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
 * Mustache helper that will add JS to the end of the page.
 *
 * @package    core
 * @category   output
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core\output;

/**
 * Add more support for simple boolean logic.
 *
 * @copyright  2015 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      3.0
 */
class mustache_conditional_helper {

    /**
     * Are these 2 things equal?
     *
     * @param string $text The script content of the section.
     * @param \Mustache_LambdaHelper $helper Used to render the content of this block.
     */
    public function eq($text, \Mustache_LambdaHelper $helper) {
        $arguments = $helper->getArguments();
        $arg1 = array_shift($arguments);
        $arg2 = array_shift($arguments);
    
        if ($arg1 == $arg2) {
            return $helper->render($text);
        }
        return '';
    }

    /**
     * Are these 2 things not equal?
     *
     * @param string $text The script content of the section.
     * @param \Mustache_LambdaHelper $helper Used to render the content of this block.
     */
    public function neq($text, \Mustache_LambdaHelper $helper) {
        $arguments = $helper->getArguments();
        $arg1 = array_shift($arguments);
        $arg2 = array_shift($arguments);
    
        if ($arg1 != $arg2) {
            return $helper->render($text);
        }
        return '';
    }

    /**
     * Parse 2 arguments into 2 floats and notify if there was an error.
     *
     * @param string $arg1 The first argument to parse as a float.
     * @param string $arg2 The second argument to parse as a float.
     * @return array($arg1, $arg2, $success)
     */
    private function parseFloatArguments($arg1, $arg2) {
        $success = true;
        if (is_numeric($arg1)) {
            $arg1 = floatval($arg1);
        } else {
            $success = false;
        }
        if (is_numeric($arg2)) {
            $arg2 = floatval($arg2);
        } else {
            $success = false;
        }
        
        return array($arg1, $arg2, $success);
    }
    
    /**
     * Is a less than b ?
     *
     * @param string $text The script content of the section.
     * @param \Mustache_LambdaHelper $helper Used to render the content of this block.
     */
    public function lt($text, \Mustache_LambdaHelper $helper) {
        $arguments = $helper->getArguments();
        $arg1 = array_shift($arguments);
        $arg2 = array_shift($arguments);
        list($arg1, $arg2, $success) = $this->parseFloatArguments($arg1, $arg2);
        if (!$success) {
            return '';
        }
    
        if ($arg1 < $arg2) {
            return $helper->render($text);
        }
        return '';
    }

    /**
     * Is a greater than b ?
     *
     * @param string $text The script content of the section.
     * @param \Mustache_LambdaHelper $helper Used to render the content of this block.
     */
    public function gt($text, \Mustache_LambdaHelper $helper) {
        $arguments = $helper->getArguments();
        $arg1 = array_shift($arguments);
        $arg2 = array_shift($arguments);
        list($arg1, $arg2, $success) = $this->parseFloatArguments($arg1, $arg2);
        if (!$success) {
            return '';
        }
    
        if ($arg1 > $arg2) {
            return $helper->render($text);
        }
        return '';
    }

    /**
     * Is a less than or equal to b ?
     *
     * @param string $text The script content of the section.
     * @param \Mustache_LambdaHelper $helper Used to render the content of this block.
     */
    public function lte($text, \Mustache_LambdaHelper $helper) {
        $arguments = $helper->getArguments();
        $arg1 = array_shift($arguments);
        $arg2 = array_shift($arguments);
        list($arg1, $arg2, $success) = $this->parseFloatArguments($arg1, $arg2);
        if (!$success) {
            return '';
        }
    
        if ($arg1 <= $arg2) {
            return $helper->render($text);
        }
        return '';
    }

    /**
     * Is a greater than or equal to b ?
     *
     * @param string $text The script content of the section.
     * @param \Mustache_LambdaHelper $helper Used to render the content of this block.
     */
    public function gte($text, \Mustache_LambdaHelper $helper) {
        $arguments = $helper->getArguments();
        $arg1 = array_shift($arguments);
        $arg2 = array_shift($arguments);
        list($arg1, $arg2, $success) = $this->parseFloatArguments($arg1, $arg2);
        if (!$success) {
            return '';
        }
    
        if ($arg1 >= $arg2) {
            return $helper->render($text);
        }
        return '';
    }

    /**
     * Is a and b true ?
     *
     * @param string $text The script content of the section.
     * @param \Mustache_LambdaHelper $helper Used to render the content of this block.
     */
    public function andLogic($text, \Mustache_LambdaHelper $helper) {
        $arguments = $helper->getArguments();
        $arg1 = array_shift($arguments);
        $arg2 = array_shift($arguments);
    
        if ($arg1 && $arg2) {
            return $helper->render($text);
        }
        return '';
    }

    /**
     * Is a or b true ?
     *
     * @param string $text The script content of the section.
     * @param \Mustache_LambdaHelper $helper Used to render the content of this block.
     */
    public function orLogic($text, \Mustache_LambdaHelper $helper) {
        $arguments = $helper->getArguments();
        $arg1 = array_shift($arguments);
        $arg2 = array_shift($arguments);
    
        if ($arg1 || $arg2) {
            return $helper->render($text);
        }
        return '';
    }
}
