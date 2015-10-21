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

require('config.php');

use core\form\form;
use core\form\element\text;
use core\form\condition\notempty;
use core\form\trigger\disable;
use core\form\rule;

$PAGE->set_url('/test.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());

require_login();

$PAGE->set_heading('Test');
$PAGE->set_title('Form hacks');

echo $OUTPUT->header();

$form = new form();
$element = $form->add_fieldset('general', 'General')
                ->add_row()
                    ->add_element(new text())
                        ->set_name('nameoffield')
                        ->set_error('You stuffed it up!')
                        ->set_label('Label for field')
                        ->set_placeholder('Placeholder text...');

$condition = new notempty($element);
$trigger = new disable();
$rule = new rule();
$rule->add_condition($condition);
$rule->set_trigger($trigger);
$element->add_rule($rule);

echo json_encode($form->export_for_template($OUTPUT));

echo $OUTPUT->render($form);

echo $OUTPUT->footer();
