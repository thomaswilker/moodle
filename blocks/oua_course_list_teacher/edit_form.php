<?php

class block_oua_course_list_teacher_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $mform->addElement('text', 'config_defaultcourselistlength', get_string('config_defaultcourselistlength', 'block_oua_course_list_teacher'));
        $mform->setType('config_defaultcourselistlength', PARAM_INT);
        $mform->setDefault('config_defaultcourselistlength', 10);

        $options = \coursecat::make_categories_list('moodle/category:manage');
        $emptyoption = array('' => get_string('none'));
        $options = $emptyoption + $options;
        $mform->addElement('select', 'config_hiddencategoryid', get_string('config_hiddencategoryid', 'block_oua_course_list_teacher'), $options);
        $mform->setType('config_hiddencategoryid', PARAM_ALPHANUM);
        $mform->setDefault('config_hiddencategoryid', null);
        return $mform;
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ($data['config_defaultcourselistlength'] < 0) {
            $errors['config_defaultcourselistlength'] = get_string('error:config_defaultcourselistlength', 'block_oua_course_list_teacher');
        }
        return $errors;
    }
}
