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

namespace theme_ouaclean\output\layout;

use renderable;
use templatable;
use renderer_base;
use stdClass;
use moodle_url;

/**
 * Class containing data for mustache layouts
 *
 * @package   theme_ouaclean
 * @copyright 2015 Open Universities Australia
 * @author    Ben Kelada (ben.kelada@open.edu.au)
 */
class base_layout implements renderable, templatable {

    protected $contextcourse = null;
    protected $doctype = null;

    /**
     * Construct this renderable.
     */
    public function __construct($contextcourse, $doctype) {
        $this->contextcourse = $contextcourse;
        $this->doctype = $doctype;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $SITE, $PAGE, $SESSION, $USER;

        $data = new stdClass();
        $logo = $PAGE->theme->setting_file_url('logo', 'logo');
        if (!$logo) {
            $logo = new moodle_url($CFG->wwwroot . '/theme/ouaclean/pix/openunilogo.png');
        }
        $data->logo = $logo;

        $appleicon = $PAGE->theme->setting_file_url('appleicon', 'appleicon');
        if (!$appleicon) {
            $appleicon = new moodle_url($CFG->wwwroot . '/theme/ouaclean/pix/apple-touch-icon-precomposed.png');
        }
        $data->apple_touch_icon_precomposed = $appleicon;

        $favicon = $PAGE->theme->setting_file_url('favicon', 'favicon');
        if (!$favicon) {
            $favicon = $output->favicon();
        }
        $data->favicon = $favicon;

        $data->html_footnote = '';
        if (!empty($PAGE->theme->settings->footnote)) {
            $data->html_footnote = '<div class="footnote text-center">' .
                format_text($PAGE->theme->settings->footnote) . '</div>';
        }
        $data->customheadermenu = '';
        if (!empty($PAGE->theme->settings->customheadermenu)) {
            $data->customheadermenu = $PAGE->theme->settings->customheadermenu;
        }
        $data->customfooter = '';
        if (!empty($PAGE->theme->settings->customfooter)) {
            $data->customfooter = $PAGE->theme->settings->customfooter;
        }
        // Add the other common page data
        $data->doctype = $this->doctype;
        $data->htmlattributes = $output->htmlattributes();
        $data->page_title = $output->page_title();
        $data->standard_head_html = $output->standard_head_html();
        $data->standard_top_of_body_html = $output->standard_top_of_body_html();
        $data->wwwroot = $CFG->wwwroot;
        $data->shortname = format_string($SITE->shortname, true,
            array('context' => $this->contextcourse));
        $data->user_menu = $output->user_menu();
        $data->custom_menu = $output->custom_menu();
        $data->page_heading_menu = $output->page_heading_menu();
        $data->navbar = $output->navbar();
        $data->page_heading_button = $output->page_heading_button();
        $data->course_header = $output->course_header();
        $data->course_content_header = $output->course_content_header();
        $data->context_header = $output->context_header() ;
        $data->main_content = $output->main_content();
        $data->course_content_footer = $output->course_content_footer();
        $data->course_footer = $output->course_footer();
        $data->page_doc_link = $output->page_doc_link();
        $data->login_info = $output->login_info();
        $data->home_link = $output->home_link();

        if(isloggedin()){
            if (!empty($CFG->messaging) && is_callable('\local_conversations\api::get_cached_unread_conversation_preview')) {
                $conversationpreview = \local_conversations\api::get_cached_unread_conversation_preview($USER);
                $data->unread_conversation_preview = $conversationpreview['unread_conversation_preview'];
                $data->unread_conversation_count = $conversationpreview['unread_conversation_count'];
                $data->all_conversations_link =  $conversationpreview['all_conversations_link'];
                $data->show_conversation_preview = true;

                // Notifications.
                $notificationpreview = \local_conversations\api::get_cached_unread_notification_preview($USER);
                $data->unread_notification_preview = $notificationpreview['unread_notification_preview'];
                $data->unread_notification_count = $notificationpreview['unread_notification_count'];
                $data->all_notifications_link =  $notificationpreview['all_notifications_link'];
                $data->show_notification_preview = true;

                $refreshtime = get_config('local_conversations', 'headerrefreshtime');
                $params = array($refreshtime);
                $PAGE->requires->js_call_amd("local_conversations/manage_notifications", 'initialise', $params);
            }

            $data->logout_link = new \moodle_url('/login/logout.php', array('sesskey' => sesskey()));
            $data->user['userid'] = $USER->id;
            $data->user['userfullname'] = fullname($USER, true);
            $data->user['userprofileurl'] = new \moodle_url('/user/profile.php', array(
                'id' => $USER->id
            ));
            $data->user['useravatar'] = $output->user_picture(
                $USER,
                array(
                    'link'                   => true,
                    'visibletoscreenreaders' => false,
                    'class'                  => 'profilepicture',
                    'size'                   => '90'
                )
            );
        }

        $data->standard_footer_html = $output->standard_footer_html();
        $data->standard_end_of_body_html = $output->standard_end_of_body_html();

        return $data;
    }

    /**
     * Allow editing user to show tabs (and content) by adding content under the tab
     *
     * @param string $region
     * @param renderer_base $output
     * @return bool
     */
    protected function region_has_content_or_user_editing($region, renderer_base $output) {
        global $USER, $PAGE;

        if (isset($USER->editing) && $USER->editing == 1) {
            return true;
        } else {
            return $PAGE->blocks->region_has_content($region, $output);
        }
    }
}
