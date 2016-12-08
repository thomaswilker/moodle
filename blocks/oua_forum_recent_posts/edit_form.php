<?php

class block_oua_forum_recent_posts_edit_form extends block_edit_form {
    protected function specific_definition($mform) {
        $mform->addElement('text', 'config_numberofpoststodisplay', get_string('config_numberofpoststodisplay', 'block_oua_forum_recent_posts'));
        $mform->setType('config_numberofpoststodisplay', PARAM_INT);
        $mform->setDefault('config_numberofpoststodisplay', 5);

        $mform->addElement('text', 'config_fullscreennumberofposts', get_string('config_fullscreennumberofposts', 'block_oua_forum_recent_posts'));
        $mform->setType('config_fullscreennumberofposts', PARAM_INT);
        $mform->setDefault('config_fullscreennumberofposts', 20);


        return $mform;
    }

}
