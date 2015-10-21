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

$PAGE->set_url('/test.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_context(context_system::instance());

require_login();

$PAGE->set_heading('Test');
$PAGE->set_title('Form hacks');

echo $OUTPUT->header();

$form = new form();
$fieldset = $form->add_fieldset('general', 'General');
$row = $fieldset->add_row();
$text = $row->add_element(new text());
$text->set_name('nameoffield');
$text->set_label('Label for field');
$text->set_placeholder('Placeholder text...');
echo json_encode($form->export_for_template($OUTPUT));

echo $OUTPUT->render($form);

echo $OUTPUT->footer();
