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
 * This file contains the form add/update a competency framework.
 *
 * @package   tool_lpimportau
 * @copyright 2015 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_lpimportau;

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

use tool_lp\api;
use DOMDocument;
use stdClass;

/**
 * Import Competency framework form.
 *
 * @package   tool_lp
 * @copyright 2015 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class framework_importer {

    /** @var string $error The errors message from reading the xml */
    var $error = '';

    /** @var array $tree The competencies tree */
    var $tree = array();

    public function fail($msg) {
        $this->error = $msg;
        return false;
    }

    /**
     * Constructor - parses the raw xml for sanity.
     */
    public function __construct($xml) {
        $doc = new DOMDocument();
        if (!@$doc->loadXML($xml)) {
            $this->fail(get_string('invalidimportfile', 'tool_lpimportau'));
            return;
        }

        $elements = $doc->getElementsByTagName('Description');
        $records = array();
        foreach ($elements as $element) {
            $record = new stdClass();
            // Get the idnumber.
            $attr = $element->attributes->getNamedItem('about');
            if (!$attr) {
                $this->fail(get_string('invalidimportfile', 'tool_lpimportau'));
                return;
            }
            $parts = explode('/', $attr->nodeValue);
            $record->idnumber = array_pop($parts);

            // Get the shortname and description.
            foreach ($element->childNodes as $child) {
                if ($child->localName == 'description') {
                    $record->description = $child->nodeValue;
                } else if ($child->localName == 'title') {
                    $record->shortname = $child->nodeValue;
                } else if ($child->localName == 'statementNotation') {
                    $record->code = $child->nodeValue;
                } else if ($child->localName == 'isChildOf') {
                    $attr = $child->attributes->getNamedItem('resource');
                    if ($attr) {
                        $parts = explode('/', $attr->nodeValue);
                        $record->parentid = array_pop($parts);
                    }
                }
            }

            if (empty($record->shortname) && !empty($record->code)) {
                $record->shortname = $record->code;
            }
            if (empty($record->shortname) && !empty($record->description)) {
                $record->shortname = $record->description;
            }
            $record->children = array();
            array_push($records, $record);
        }

        // Now rebuild into a tree.
        foreach ($records as $key => $record) {
            if (!empty($record->parentid)) {
                $foundparent = false;
                foreach ($records as $parentkey => $parentrecord) {
                    if ($parentrecord->idnumber == $record->parentid) {
                        array_push($parentrecord->children, $record);
                        $foundparent = true;
                        break;
                    }
                }
                if (!$foundparent) {
                    $record->parentid = '';
                }
            }
        }

        // Remove from top level any nodes with a parent.
        foreach ($records as $key => $record) {
            if (!empty($record->parentid)) {
                unset($records[$key]);
            }
        }

        $this->tree = $records;
    }

    /**
     * @return array of errors from parsing the xml.
     */
    public function get_error() {
        return $this->error;
    }

    public function create_competency($parent, $record, $framework) {
        $competency = new stdClass();
        $competency->competencyframeworkid = $framework->get_id();
        if ($parent) {
            $competency->parentid = $parent->get_id();
        } else {
            $competency->parentid = 0;
        }
        if (!empty($record->code)) {
            $competency->idnumber = trim(clean_param($record->code, PARAM_TEXT));
        } else {
            $competency->idnumber = trim(clean_param($record->idnumber, PARAM_TEXT));
        }
        $competency->shortname = trim(clean_param(shorten_text($record->shortname, 50), PARAM_TEXT));
        if (!empty($record->description)) {
            $competency->description = trim(clean_param($record->description, PARAM_TEXT));
        }

        if (!empty($competency->idnumber) && !empty($competency->shortname)) {
            $parent = api::create_competency($competency);

            foreach ($record->children as $child) {
                $this->create_competency($parent, $child, $framework);
            }
        }
    }

    /**
     * @param \tool_lp\competency_framework
     * @return boolean
     */
    public function import_to_framework($framework) {
        foreach ($this->tree as $record) {
            $this->create_competency(null, $record, $framework);
        }
        return true;
    }
}
