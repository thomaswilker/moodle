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
 * select type form element
 *
 * Contains HTML class for a select type element
 *
 * @package   core_form
 * @copyright 2006 Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('HTML/QuickForm/select.php');

/**
 * select type form element
 *
 * HTML class for a select type element
 *
 * @package   core_form
 * @category  form
 * @copyright 2006 Jamie Pratt <me@jamiep.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_select extends HTML_QuickForm_select implements renderable, templatable {
    /** @var string html for help button, if empty then no help */
    var $_helpbutton='';

    /** @var bool if true label will be hidden */
    var $_hiddenLabel=false;

    /**
     * constructor
     *
     * @param string $elementName Select name attribute
     * @param mixed $elementLabel Label(s) for the select
     * @param mixed $options Data to be used to populate options
     * @param mixed $attributes Either a typical HTML attribute string or an associative array
     */
    function MoodleQuickForm_select($elementName=null, $elementLabel=null, $options=null, $attributes=null) {
        parent::HTML_QuickForm_select($elementName, $elementLabel, $options, $attributes);
    }

    /**
     * Sets label to be hidden
     *
     * @param bool $hiddenLabel sets if label should be hidden
     */
    function setHiddenLabel($hiddenLabel){
        $this->_hiddenLabel = $hiddenLabel;
    }

    /**
     * Get the name of the template to use for this form element.
     *
     * @return string
     */
    public function get_template_name() {
        return 'core/form-element-select';
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * 1. No complex types - only stdClass, array, int, string, float, bool
     * 2. Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     * @return stdClass|array
     */
    public function export_for_template(renderer_base $output) {
        $context = new stdClass();
        $context->helpButton = $this->getHelpButton();
        $context->options = array();
        $text = array();
        $value = array();
        foreach ($this->_options as $option) {
            $selected = !empty($this->_values) && in_array($option['attr']['value'], $this->_values);
            $exportedoption = array(
                'value' => $option['attr']['value'],
                'text' => $option['text'],
                'selected' => $selected
            );
            if ($selected) {
                array_push($text, $option['text']);
                array_push($value, $option['attr']['value']);
            }
            array_push($context->options, $exportedoption);
        }
        $context->text = implode(', ', $text);
        $context->value = implode(', ', $value);
        $context->id = $this->getAttribute('id');
        $context->name = $this->getName();
        $context->label = $this->getLabel();
        $context->multiple = $this->getMultiple();
        $context->hiddenLabel = $this->_hiddenLabel;
        $context->size = $this->getSize();
        $context->frozen = $this->_flagFrozen;
        $context->required = $this->getAttribute('required');
        $context->advanced = $this->getAttribute('advanced');
        $context->error = $this->getAttribute('error');

        return $context;
    }

    /**
     * get html for help button
     *
     * @return string html for help button
     */
    function getHelpButton(){
        return $this->_helpbutton;
    }

    /**
     * Removes an OPTION from the SELECT
     *
     * @param string $value Value for the OPTION to remove
     * @return void
     */
    function removeOption($value)
    {
        $key=array_search($value, $this->_values);
        if ($key!==FALSE and $key!==null) {
            unset($this->_values[$key]);
        }
        foreach ($this->_options as $key=>$option){
            if ($option['attr']['value']==$value){
                unset($this->_options[$key]);
                // we must reindex the options because the ugly code in quickforms' select.php expects that keys are 0,1,2,3... !?!?
                $this->_options = array_merge($this->_options);
                return;
            }
        }
    }

    /**
     * Removes all OPTIONs from the SELECT
     */
    function removeOptions()
    {
        $this->_options = array();
    }

    /**
     * We check the options and return only the values that _could_ have been
     * selected. We also return a scalar value if select is not "multiple"
     *
     * @param array $submitValues submitted values
     * @param bool $assoc if true the retured value is associated array
     * @return mixed
     */
    function exportValue(&$submitValues, $assoc = false)
    {
        if (empty($this->_options)) {
            return $this->_prepareValue(null, $assoc);
        }

        $value = $this->_findValue($submitValues);
        if (is_null($value)) {
            $value = $this->getValue();
        }
        $value = (array)$value;

        $cleaned = array();
        foreach ($value as $v) {
            foreach ($this->_options as $option) {
                if ((string)$option['attr']['value'] === (string)$v) {
                    $cleaned[] = (string)$option['attr']['value'];
                    break;
                }
            }
        }

        if (empty($cleaned)) {
            return $this->_prepareValue(null, $assoc);
        }
        if ($this->getMultiple()) {
            return $this->_prepareValue($cleaned, $assoc);
        } else {
            return $this->_prepareValue($cleaned[0], $assoc);
        }
    }
}
