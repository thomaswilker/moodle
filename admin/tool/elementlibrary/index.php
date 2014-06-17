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
 * Element library entry page
 *
 * @package    tool_elementlibrary
 * @copyright  2014 Damyon Wiese <damyon@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

$paramcategory = optional_param('category', 0, PARAM_INT);
$paramcomponent = optional_param('component', '', PARAM_COMPONENT);
$paramswitchdir = optional_param('switchdir', false, PARAM_BOOL);

if ($paramswitchdir) {
    $CFG->switchlangdir = true;
}
$params = array(
    'category' => $paramcategory,
    'component' => $paramcomponent,
    'switchdir' => $paramswitchdir
);
$url = new moodle_url('/admin/tool/elementlibrary/index.php', $params);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('pluginname', 'tool_elementlibrary'));
$PAGE->set_heading(get_string('development', 'admin'));
$PAGE->navbar->add(get_string('development', 'admin'));
$PAGE->navbar->add(get_string('pluginname', 'tool_elementlibrary'), $url);

require_login();

require_capability('moodle/site:config', context_system::instance());

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'tool_elementlibrary'));

$loader = new \core\output\renderer_sample_generator_loader();
$generators = $loader->load_all_generators();

$components = array();
$pluginmanager = core_plugin_manager::instance();

foreach ($generators as $component => $generator) {
    if ($component == 'core') {
        $components[$component] = get_string('core', 'tool_elementlibrary');
    } else {
        $components[$component] = $pluginmanager->plugin_name($component);
    }
}
// TODO - convert all output below this point to use a renderer (ironic).
echo $OUTPUT->box_start('tool_elementlibrary_filters');

$params = array(
    'category' => $paramcategory,
    'component' => $paramcomponent,
    'switchdir' => !$paramswitchdir
);
$url = new moodle_url('/admin/tool/elementlibrary/index.php', $params);
// TODO - Add a renderer for this "pull-right" thing (with RTL support).
echo $OUTPUT->box_start('adminsettingsflags');
echo $OUTPUT->single_button($url, get_string('switchlanguagedirection', 'tool_elementlibrary'));
echo $OUTPUT->box_end();

$params = array(
    'category' => $paramcategory,
    'switchdir' => $paramswitchdir
);
$url = new moodle_url('/admin/tool/elementlibrary/index.php', $params);
echo $OUTPUT->single_select($url,
                            'component',
                            $components,
                            '',
                            array('' => get_string('choosecomponent', 'tool_elementlibrary')));

$categories = array(
    \core\output\renderer_sample_base::CATEGORY_ELEMENT => get_string('categoryelement', 'tool_elementlibrary'),
    \core\output\renderer_sample_base::CATEGORY_COMPONENT => get_string('categorycomponent', 'tool_elementlibrary'),
    \core\output\renderer_sample_base::CATEGORY_LAYOUT => get_string('categorylayout', 'tool_elementlibrary')
);

$params = array(
    'component' => $paramcomponent,
    'switchdir' => $paramswitchdir
);
$url = new moodle_url('/admin/tool/elementlibrary/index.php', $params);
echo $OUTPUT->single_select($url,
                            'category',
                            $categories,
                            '',
                            array(0 => get_string('allcategories', 'tool_elementlibrary')));

echo $OUTPUT->box_end();

echo html_writer::tag('hr', '');

if ($paramcomponent == '') {
    echo $OUTPUT->notification(get_string('selectcomponent', 'tool_elementlibrary'), 'notifyinfo');
} else {
    if (empty($paramcategory)) {
        $categoryname = get_string('allcategories', 'tool_elementlibrary');
    } else {
        $categoryname = $categories[$paramcategory];
    }
    $componentname = $components[$paramcomponent];
    $params = array(
        'component' => $componentname,
        'category' => $categoryname
    );

    $filters = get_string('filters', 'tool_elementlibrary', $params);
    echo $OUTPUT->heading($filters, 3);
    $testsfound = false;

    foreach ($generators as $component => $generator) {
        if ($component == $paramcomponent) {
            $tests = $generator->create_samples();

            foreach ($tests as $test) {
                if (($test->get_category() == $paramcategory) || empty($paramcategory)) {
                    // This should be a 2 column list.
                    echo html_writer::tag('hr', '');
                    echo $OUTPUT->heading(get_string('testname', 'tool_elementlibrary'), 4);
                    echo $OUTPUT->box($test->get_name());
                    echo $OUTPUT->heading(get_string('testdocs', 'tool_elementlibrary'), 4);
                    echo $OUTPUT->box($test->get_docs());
                    echo $OUTPUT->heading(get_string('testoutput', 'tool_elementlibrary'), 4);
                    echo $OUTPUT->box($test->execute());

                    $testsfound = true;
                }
            }
        }
    }
    if (!$testsfound) {
        echo $OUTPUT->notification(get_string('notestsfound', 'tool_elementlibrary'), 'notifyproblem');
    }
}

echo $OUTPUT->footer();

