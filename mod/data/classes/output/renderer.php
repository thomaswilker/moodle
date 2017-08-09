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
 * Database renderer.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_data\output;

use plugin_renderer_base;
use html_writer;
use html_table;

defined('MOODLE_INTERNAL') || die();

/**
 * Database renderer.
 *
 * @package    mod_data
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    public function render_page(page $page) {
        $context = $page->export_for_template($this);
        return $this->render_from_template('mod_data/page', $context);
    }

    public function import_setting_mappings($datamodule, \data_preset_importer $importer) {

        $strblank = get_string('blank', 'data');
        $strcontinue = get_string('continue');
        $strwarning = get_string('mappingwarning', 'data');
        $strfieldmappings = get_string('fieldmappings', 'data');
        $strnew = get_string('new');

        $params = $importer->get_preset_settings();
        $settings = $params->settings;
        $newfields = $params->importfields;
        $currentfields = $params->currentfields;

        $html  = html_writer::start_tag('div', array('class'=>'presetmapping'));
        $html .= html_writer::start_tag('form', array('method'=>'post', 'action'=>''));
        $html .= html_writer::start_tag('div');
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'finishimport'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'d', 'value'=>$datamodule->id));

        if ($importer instanceof data_preset_existing_importer) {
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'fullname', 'value'=>$importer->get_userid().'/'.$importer->get_directory()));
        } else {
            $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'directory', 'value'=>$importer->get_directory()));
        }

        if (!empty($newfields)) {
            $html .= $this->output->heading_with_help($strfieldmappings, 'fieldmappings', 'data', '', '', 3);

            $table = new html_table();
            $table->data = array();

            foreach ($newfields as $nid => $newfield) {
                $row = array();
                $row[0] = html_writer::tag('label', $newfield->name, array('for'=>'id_'.$newfield->name));
                $attrs = array('name' => 'field_' . $nid, 'id' => 'id_' . $newfield->name, 'class' => 'custom-select');
                $row[1] = html_writer::start_tag('select', $attrs);

                $selected = false;
                foreach ($currentfields as $cid => $currentfield) {
                    if ($currentfield->type != $newfield->type) {
                        continue;
                    }
                    if ($currentfield->name == $newfield->name) {
                        $row[1] .= html_writer::tag('option', get_string('mapexistingfield', 'data', $currentfield->name), array('value'=>$cid, 'selected'=>'selected'));
                        $selected=true;
                    } else {
                        $row[1] .= html_writer::tag('option', get_string('mapexistingfield', 'data', $currentfield->name), array('value'=>$cid));
                    }
                }

                if ($selected) {
                    $row[1] .= html_writer::tag('option', get_string('mapnewfield', 'data'), array('value'=>'-1'));
                } else {
                    $row[1] .= html_writer::tag('option', get_string('mapnewfield', 'data'), array('value'=>'-1', 'selected'=>'selected'));
                }

                $row[1] .= html_writer::end_tag('select');
                $table->data[] = $row;
            }
            $html .= html_writer::table($table);
            $html .= html_writer::tag('p', $strwarning);
        } else {
            $html .= $this->output->notification(get_string('nodefinedfields', 'data'));
        }

        $html .= html_writer::start_tag('div', array('class'=>'overwritesettings'));
        $html .= html_writer::tag('label', get_string('overwritesettings', 'data'), array('for' => 'overwritesettings'));
        $attrs = array('type' => 'checkbox', 'name' => 'overwritesettings', 'id' => 'overwritesettings', 'class' => 'm-l-1');
        $html .= html_writer::empty_tag('input', $attrs);
        $html .= html_writer::end_tag('div');
        $html .= html_writer::empty_tag('input', array('type' => 'submit', 'class' => 'btn btn-primary', 'value' => $strcontinue));

        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div');

        return $html;
    }

}
