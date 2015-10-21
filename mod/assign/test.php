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
 * TESTING PAGE for autocomplete field.
 */

require('../../config.php');

use core\form\form;
use core\form\element\text;
use core\form\condition\isnotempty;
use core\form\condition\isempty;
use core\form\trigger\disable;
use core\form\trigger\invalidate;
use core\form\rule;

$id = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_login($course);
$PAGE->set_url('/mod/assign/test.php', array('id' => $id));
$PAGE->set_pagelayout('incourse');

require_login();

$PAGE->set_heading('Test');
$PAGE->set_title('Form hacks');

echo $OUTPUT->header();

$form = new form();
$element = $form->add_fieldset('general', 'General')
                ->add_row()
                    ->add_element(new text())
                        ->set_name('nameoffield')
                        ->set_label('Label for field')
                        ->set_placeholder('Placeholder text...');

$condition = new isnotempty($element);
$trigger = new disable();
$rule = new rule();
$rule->add_condition($condition);
$rule->set_trigger($trigger);
$element->add_rule($rule);

$condition = new isempty($element);
$trigger = new invalidate('You need a value dummy!');
$rule = new rule();
$rule->add_condition($condition);
$rule->set_trigger($trigger);
$element->add_rule($rule);

echo '<pre>';
echo json_encode($form->export_for_template($OUTPUT), JSON_PRETTY_PRINT);
echo '</pre>';

echo $OUTPUT->render($form);

echo $OUTPUT->footer();
