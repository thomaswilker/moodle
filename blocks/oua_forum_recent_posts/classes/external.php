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
 * This is the external API for suggested connections
 *
 * @package    block_oua_connections
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace block_oua_forum_recent_posts;

use external_api;
use external_description;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use external_format_value;
use external_warnings;
use context_module;
use mod_forum_external;

class external extends external_api {

    /**
     * Override cleaning return values for api
     * Descriptors do not support recursive trees, which forum output contains.
     * It would be too complicated to write a validator for recursive elements for little benefit.
     * So we revert back to 3.0 behaviour of not validating output
     *
     * @param \external_description $description
     * @param mixed $response
     * @return mixed
     */
    public static function clean_returnvalue(external_description $description, $response) {
        return $response;
    }

    /**
     * Describes the parameters for get_forum_discussions_paginated.
     *
     * @return external_external_function_parameters
     */
    public static function get_oua_forum_discussions_with_posts_paginated_parameters() {
        return new external_function_parameters (array('forumid' => new external_value(PARAM_INT, 'forum instance id',
                                                                                       VALUE_REQUIRED),
                                                       'sortby' => new external_value(PARAM_ALPHA,
                                                                                      'sort by this element: id, timemodified, timestart or timeend',
                                                                                      VALUE_DEFAULT, 'timemodified'),
                                                       'sortdirection' => new external_value(PARAM_ALPHA,
                                                                                             'sort direction: ASC or DESC',
                                                                                             VALUE_DEFAULT, 'DESC'),
                                                       'page' => new external_value(PARAM_INT, 'current page', VALUE_DEFAULT, -1),
                                                       'perpage' => new external_value(PARAM_INT, 'items per page', VALUE_DEFAULT,
                                                                                       0)));
    }

    /**
     * Returns a list of forum discussions and child posts in a parent/child tree structure.
     *
     * @param int $forumid the forum instance id
     * @param string $sortby sort by this element (id, timemodified, timestart or timeend)
     * @param string $sortdirection sort direction: ASC or DESC
     * @param int $page page number
     * @param int $perpage items per page
     *
     */
    public static function get_oua_forum_discussions_with_posts_paginated($forumid, $sortby = 'timemodified',
                                                                          $sortdirection = 'DESC', $page = -1, $perpage = 0) {
        global $DB;
        $params = self::validate_parameters(self::get_oua_forum_discussions_with_posts_paginated_parameters(), array('forumid' => $forumid,
                                                                                         'sortby' => $sortby,
                                                                                         'sortdirection' => $sortdirection,
                                                                                         'page' => $page,
                                                                                         'perpage' => $perpage));

        $forum = $DB->get_record('forum', array('id' => $params['forumid']), '*', MUST_EXIST);
        $forumcm = get_fast_modinfo($forum->course, 0)->instances['forum'][$params['forumid']];

        // Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
        $modcontext = context_module::instance($forumcm->id);
        self::validate_context($modcontext);
        $discussions = api::get_oua_forum_discussions_with_posts_paginated($forum, $params['sortby'], $params['sortdirection'], $params['page'],
                                                                            $params['perpage']);
        return array('discussions' => $discussions);
    }

    /**
     * Describes the get_forum_discussions_paginated return value.
     */
    public static function get_oua_forum_discussions_with_posts_paginated_returns() {
        return new external_single_structure(
            array('discussions' => new external_multiple_structure(
                new external_single_structure(array('id' => new external_value(PARAM_INT, 'Post id'),
                                            'discussion' => new external_value(PARAM_INT, 'Discussion id'),
                                            'parent' => new external_value(PARAM_TEXT, 'Parent id', VALUE_OPTIONAL),
                                            'userid' => new external_value(PARAM_INT, 'User who started the discussion id'),
                                            'created' => new external_value(PARAM_INT, 'Creation time'),
                                            'modified' => new external_value(PARAM_TEXT, 'Time modified'),
                                            'mailed' => new external_value(PARAM_INT, 'Mailed?'),
                                            'subject' => new external_value(PARAM_TEXT, 'The post subject'),
                                            'message' => new external_value(PARAM_RAW, 'The post message'),
                                            'messageformat' => new external_format_value('message'),
                                            'messagetrust' => new external_value(PARAM_INT, 'Can we trust?'),
                                            'attachment' => new external_value(PARAM_RAW, 'Has attachments?'),
                                            'totalscore' => new external_value(PARAM_INT, 'The post message total score'),
                                            'name' => new external_value(PARAM_TEXT, 'Discussion name'),
                                            'groupid' => new external_value(PARAM_INT, 'Group id'),
                                            'usermodified' => new external_value(PARAM_INT, 'The id of the user who last modified'),
                                            'timestart' => new external_value(PARAM_INT, 'Time discussion can start'),
                                            'timeend' => new external_value(PARAM_INT, 'Time discussion ends'),
                                            'modified' => new external_value(PARAM_INT, 'Time modified'),
                                            'mailnow' => new external_value(PARAM_INT, 'Mail now?'),
                                            'posts' => new external_multiple_structure(
                                                    new external_single_structure(
                                                            array('id' => new external_value(PARAM_INT, 'Post id'),
                                                                   'discussion' => new external_value(PARAM_INT, 'Discussion id'),
                                                                   'parent' => new external_value(PARAM_TEXT, 'Parent id', VALUE_OPTIONAL),
                                                                   'userid' => new external_value(PARAM_INT, 'User id'),
                                                                   'created' => new external_value(PARAM_INT, 'Creation time'),
                                                                   'modified' => new external_value(PARAM_INT, 'Time modified'),
                                                                   'mailed' => new external_value(PARAM_INT, 'Mailed?'),
                                                                   'subject' => new external_value(PARAM_TEXT, 'The post subject'),
                                                                   'message' => new external_value(PARAM_RAW, 'The post message'),
                                                                   'messageformat' => new external_format_value('message'),
                                                                   'messagetrust' => new external_value(PARAM_INT, 'Can we trust?'),
                                                                   'hasattachments' => new external_value(PARAM_BOOL, 'Has attachments?'),
                                                                   'attachments' => new external_multiple_structure(
                                                                       new external_single_structure(
                                                                           array('filename' => new external_value(PARAM_FILE, 'file name'),
                                                                                 'fileurl' => new external_value(PARAM_URL, 'file download url')
                                                                           )
                                                                       ),'attachments', VALUE_OPTIONAL) ,'' , VALUE_OPTIONAL,
                                                                   'totalscore' => new external_value(PARAM_INT, 'The post message total score'),
                                                                   'mailnow' => new external_value(PARAM_INT, 'Mail now?'),
                                                                   'children' => new external_multiple_structure(new external_value(PARAM_INT, 'children post id')),
                                                                   'canreply' => new external_value(PARAM_BOOL, 'The user can reply to posts?'),
                                                                   'postread' => new external_value(PARAM_BOOL, 'The post was read'),
                                                                   'candeletepost' => new external_value(PARAM_BOOL, 'The post was read'),
                                                                   'caneditpost' => new external_value(PARAM_BOOL, 'The post was read'),
                                                                   'userfullname' => new external_value(PARAM_TEXT, 'Post author full name'),
                                                                   'userpictureurl' => new external_value(PARAM_URL, 'Post author picture.',  VALUE_OPTIONAL)),
                                                             'post', VALUE_OPTIONAL), '' , VALUE_OPTIONAL),


                                            'userfullname' => new external_value(PARAM_TEXT, 'Post author full name'),
                                            'usermodifiedfullname' => new external_value(PARAM_TEXT, 'Post modifier full name'),
                                            'userpictureurl' => new external_value(PARAM_URL, 'Post author picture.'),
                                            'usermodifiedpictureurl' => new external_value(PARAM_URL, 'Post modifier picture.'),
                                            'numreplies' => new external_value(PARAM_TEXT, 'The number of replies in the discussion'),
                                            'numunread' => new external_value(PARAM_INT, 'The number of unread discussions.'),
                                                    'formatted_discussion_created_date' => new external_value(PARAM_TEXT, 'Post author full name'),
                                                    'formatted_discussion_modified_date' => new external_value(PARAM_TEXT, 'Post author full name'),
                                                    'discussionuserprofileurl' => new external_value(PARAM_TEXT, 'Post author full name'),

                                                    'pinned' => new external_value(PARAM_BOOL, 'Is the discussion pinned'),
                                            'canreply' => new external_value(PARAM_BOOL, 'Is the discussion pinned'),
                                            'canpindiscussion' => new external_value(PARAM_BOOL, 'Is the discussion pinned'),
                                            'canmanageforum' => new external_value(PARAM_BOOL, 'Is the discussion pinned')),
                                      'post')),
                               'warnings' => new external_warnings()));
    }
    /**
     * Describes the parameters for get_forum_discussions_paginated.
     *
     * @return external_external_function_parameters
     */
    public static function get_oua_forum_discussion_by_id_with_post_parameters() {
        return new external_function_parameters (array('discussionid' => new external_value(PARAM_INT, 'forum instance id',
                                                                                       VALUE_REQUIRED)));
    }

    /**
     * Returns a list of forum discussions and child posts in a parent/child tree structure.
     *
     * @param int $forumid the forum instance id
     * @param string $sortby sort by this element (id, timemodified, timestart or timeend)
     * @param string $sortdirection sort direction: ASC or DESC
     * @param int $page page number
     * @param int $perpage items per page
     *
     */
    public static function get_oua_forum_discussion_by_id_with_post($discussionid) {
        global $DB;
        $params = self::validate_parameters(self::get_oua_forum_discussion_by_id_with_post_parameters(), array('discussionid' => $discussionid));

        $discussion = $DB->get_record('forum_discussions', array('id' => $params['discussionid']), '*', MUST_EXIST);
        $forum = $DB->get_record('forum', array('id' => $discussion->forum), '*', MUST_EXIST);
        $forumcm = get_fast_modinfo($forum->course, 0)->instances['forum'][$forum->id];

        // Validate the module context. It checks everything that affects the module visibility (including groupings, etc..).
        $modcontext = context_module::instance($forumcm->id);
        self::validate_context($modcontext);

        $page = 0;
        $founddiscussion = false;
        $discussionreturn = false;

        /**
         * There is no core functionality to retrieve a single discussion.
         * We could potentially retrieve the discussion from the db then run all the formatting and permissions stuff over the top
         * But Rather than write/re-write multiple core functions (get_forum_discussions_paginated, forum_get_discussions)
         * then refactor get_oua_forum_discussions_with_posts_paginated
         * We cheat and use the existing functionality (get multiple posts), then extract the single discussion thread.
         * As the discussion has just been added/modified it should be at the top of the list, just after pinned posts if any.
         */
        while ($founddiscussion === false) {
            $discussions = api::get_oua_forum_discussions_with_posts_paginated($forum, 'timemodified', 'DESC', $page, 10);
            if (count($discussions) == 0) {
                break;
            }
            foreach ($discussions as $discussion) {
                if ($discussion->discussion == $discussionid) {
                    $founddiscussion = true;
                    $discussionreturn = $discussion;
                    break;
                }
            }
            $page++;
        }
        return array('discussion' => $discussionreturn);
    }

    /**
     * Describes the get_forum_discussions_paginated return value.
     */
    public static function get_oua_forum_discussion_by_id_with_post_returns() {
        return new external_single_structure(array('discussions' => new external_multiple_structure(new external_single_structure(array('id' => new external_value(PARAM_INT, 'Post id'),
                                                                                                                                        'name' => new external_value(PARAM_TEXT, 'Discussion name'),
                                                                                                                                        'groupid' => new external_value(PARAM_INT, 'Group id'),
                                                                                                                                        'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                                                                                                                                        'usermodified' => new external_value(PARAM_INT, 'The id of the user who last modified'),
                                                                                                                                        'timestart' => new external_value(PARAM_INT, 'Time discussion can start'),
                                                                                                                                        'timeend' => new external_value(PARAM_INT, 'Time discussion ends'),
                                                                                                                                        'discussion' => new external_value(PARAM_INT, 'Discussion id'),
                                                                                                                                        'parent' => new external_value(PARAM_INT, 'Parent id'),
                                                                                                                                        'userid' => new external_value(PARAM_INT, 'User who started the discussion id'),
                                                                                                                                        'created' => new external_value(PARAM_INT, 'Creation time'),
                                                                                                                                        'modified' => new external_value(PARAM_INT, 'Time modified'),
                                                                                                                                        'mailed' => new external_value(PARAM_INT, 'Mailed?'),
                                                                                                                                        'subject' => new external_value(PARAM_TEXT, 'The post subject'),
                                                                                                                                        'message' => new external_value(PARAM_RAW, 'The post message'),
                                                                                                                                        'messageformat' => new external_format_value('message'),
                                                                                                                                        'messagetrust' => new external_value(PARAM_INT, 'Can we trust?'),
                                                                                                                                        'attachment' => new external_value(PARAM_RAW, 'Has attachments?'),
                                                                                                                                        'posts' => new external_multiple_structure(new external_single_structure(array('id' => new external_value(PARAM_INT, 'Post id'),
                                                                                                                                                                                                                       'discussion' => new external_value(PARAM_INT, 'Discussion id'),
                                                                                                                                                                                                                       'parent' => new external_value(PARAM_INT, 'Parent id'),
                                                                                                                                                                                                                       'userid' => new external_value(PARAM_INT, 'User id'),
                                                                                                                                                                                                                       'created' => new external_value(PARAM_INT, 'Creation time'),
                                                                                                                                                                                                                       'modified' => new external_value(PARAM_INT, 'Time modified'),
                                                                                                                                                                                                                       'mailed' => new external_value(PARAM_INT, 'Mailed?'),
                                                                                                                                                                                                                       'subject' => new external_value(PARAM_TEXT, 'The post subject'),
                                                                                                                                                                                                                       'message' => new external_value(PARAM_RAW, 'The post message'),
                                                                                                                                                                                                                       'messageformat' => new external_format_value('message'),
                                                                                                                                                                                                                       'messagetrust' => new external_value(PARAM_INT, 'Can we trust?'),
                                                                                                                                                                                                                       'attachment' => new external_value(PARAM_RAW, 'Has attachments?'),
                                                                                                                                                                                                                       'attachments' => new external_multiple_structure(new external_single_structure(array('filename' => new external_value(PARAM_FILE, 'file name'),
                                                                                                                                                                                                                                                                                                            'mimetype' => new external_value(PARAM_RAW, 'mime type'),
                                                                                                                                                                                                                                                                                                            'fileurl' => new external_value(PARAM_URL, 'file download url'))),
                                                                                                                                                                                                                                                                        'attachments',
                                                                                                                                                                                                                                                                        VALUE_OPTIONAL),
                                                                                                                                                                                                                       'totalscore' => new external_value(PARAM_INT, 'The post message total score'),
                                                                                                                                                                                                                       'mailnow' => new external_value(PARAM_INT, 'Mail now?'),
                                                                                                                                                                                                                       'children' => new external_multiple_structure(new external_value(PARAM_INT, 'children post id')),
                                                                                                                                                                                                                       'canreply' => new external_value(PARAM_BOOL, 'The user can reply to posts?'),
                                                                                                                                                                                                                       'postread' => new external_value(PARAM_BOOL, 'The post was read'),
                                                                                                                                                                                                                       'userfullname' => new external_value(PARAM_TEXT, 'Post author full name'),
                                                                                                                                                                                                                       'userpictureurl' => new external_value(PARAM_URL, 'Post author picture.',  VALUE_OPTIONAL)),
                                                                                                                                                                                                                 'post')),
                                                                                                                                        'totalscore' => new external_value(PARAM_INT, 'The post message total score'),
                                                                                                                                        'mailnow' => new external_value(PARAM_INT, 'Mail now?'),
                                                                                                                                        'userfullname' => new external_value(PARAM_TEXT, 'Post author full name'),
                                                                                                                                        'usermodifiedfullname' => new external_value(PARAM_TEXT, 'Post modifier full name'),
                                                                                                                                        'userpictureurl' => new external_value(PARAM_URL, 'Post author picture.'),
                                                                                                                                        'usermodifiedpictureurl' => new external_value(PARAM_URL, 'Post modifier picture.'),
                                                                                                                                        'numreplies' => new external_value(PARAM_TEXT, 'The number of replies in the discussion'),
                                                                                                                                        'numunread' => new external_value(PARAM_INT, 'The number of unread discussions.'),
                                                                                                                                        'pinned' => new external_value(PARAM_TEXT, 'Is the discussion pinned')),
                                                                                                                                  'post')),
                                                   'warnings' => new external_warnings()));
    }
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function oua_forum_add_discussion_parameters() {
        return new external_function_parameters(
            array(
                'forumid' => new external_value(PARAM_INT, 'Forum instance ID'),
                'subject' => new external_value(PARAM_TEXT, 'New Discussion subject'),
                'message' => new external_value(PARAM_RAW, 'New Discussion message (only html format allowed)'),
                'groupid' => new external_value(PARAM_INT, 'The group, default to -1', VALUE_DEFAULT, -1),
                'options' => new external_multiple_structure (
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUM,
                                                         'The allowed keys (value format) are:
                                        discussionsubscribe (bool); subscribe to the discussion?, default to true
                                        discussionpinned    (bool); is the discussion pinned, default to false
                            '),
                            'value' => new external_value(PARAM_RAW, 'The value of the option,
                                                            This param is validated in the external function.'
                            )
                        )
                    ), 'Options', VALUE_DEFAULT, array())
            )
        );
    }

    /**
     * Add a new discussion into an existing forum.
     * Core api does not have ajax enabled, and doesnt do validation.
     * So we wrap them in our own functions to allow ajax and do basic validation.
     *
     * @param int $forumid the forum instance id
     * @param string $subject new discussion subject
     * @param string $message new discussion message (only html format allowed)
     * @param int $groupid the user course group
     * @param array $options optional settings
     * @return array of warnings and the new discussion id
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function oua_forum_add_discussion($forumid, $subject, $message, $groupid = -1, $options = array()) {
        global $CFG;

        $params = self::validate_parameters(self::oua_forum_add_discussion_parameters(), array('forumid' => $forumid,
                                                                                               'subject' => $subject,
                                                                                               'message' => $message,
                                                                                               'groupid' => $groupid,
                                                                                               'options' => $options));
        require_once($CFG->dirroot . "/mod/forum/externallib.php");
        $errors = array();

         if (strlen(trim($subject)) > 255) {
            $errors[] = array('subject' => get_string('validationsubjectlength', 'block_oua_forum_recent_posts'));
        } elseif (trim($subject) == '') {
            $errors[] = array('subject' => 'Subject is empty');
        }
        if (trim($message) == '') {
            $errors[] = array('message' => 'Message is empty');
        }
        if (!empty($errors)) {
            return array('discussionid' => null, 'warnings' => $errors);
        }
        $discussionreturn =  mod_forum_external::add_discussion($params['forumid'], $params['subject'], $params['message'], $params['groupid'], $params['options']);

        $inlineattachmentsid = api::extract_clean_parameter('inlineattachmentsid', $options, 0);
        api::clean_user_draft_area($inlineattachmentsid);

        return $discussionreturn;
    }
    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function oua_forum_add_discussion_returns() {
        return new external_single_structure(
            array(
                'discussionid' => new external_value(PARAM_INT, 'New Discussion ID'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function oua_forum_delete_discussion_post_parameters() {
        return new external_function_parameters(
            array(
                'postid' => new external_value(PARAM_INT, 'Forum Post id'),
            )
        );
    }

    /**
     * Delete a post from a forum.
     * Core does not have an exposed api for this
     *
     * @param int $postid the post id
     * @return array of warnings or success
     * @throws moodle_exception
     */
    public static function oua_forum_delete_discussion_post($postid) {
        $params = self::validate_parameters(self::oua_forum_delete_discussion_post_parameters(), array('postid' => $postid));

        return api::delete_forum_post($params['postid']);
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function oua_forum_delete_discussion_post_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Successful delete'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function oua_forum_add_discussion_post_parameters() {
        return new external_function_parameters(
            array(
                'postid' => new external_value(PARAM_INT, 'the post id we are going to reply to
                                                (can be the initial discussion post'),
                'subject' => new external_value(PARAM_TEXT, 'new post subject'),
                'message' => new external_value(PARAM_RAW, 'new post message (only html format allowed)'),
                'options' => new external_multiple_structure (
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_ALPHANUM,
                                                         'The allowed keys (value format) are:
                                        discussionsubscribe (bool); subscribe to the discussion?, default to true
                                        inlineattachmentsid (int): id of the message inline attachments filearea
                                        attachid (int): id of the attachments filearea
                            '),
                            'value' => new external_value(PARAM_RAW, 'the value of the option,
                                                            this param is validated in the external function.'
                            )
                        )
                    ), 'Options', VALUE_DEFAULT, array())
            )
        );
    }

    /**
     * Add a post to an existing discussion.
     * Calls the core external api, this is required as the core one doesnt support ajax, and doesnt do validation.
     * So we wrap them in our own functions to allow ajax and do basic validation.
     *
     * @param int $postid the post id
     * @param string $subject new discussion subject
     * @param string $message new discussion message (only html format allowed)
     * @param array $options optional settings
     * @return array of warnings or success
     * @throws moodle_exception
     */
    public static function oua_forum_add_discussion_post($postid, $subject, $message, $options = array()) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . "/mod/forum/externallib.php");

        $params = self::validate_parameters(self::oua_forum_add_discussion_post_parameters(), array('postid' => $postid,
                                                                                               'subject' => $subject,
                                                                                               'message' => $message,
                                                                                               'options' => $options));

        $errors = array();
        // Subject is hard coded in javascript.
        if (trim($subject) == '') {
            $errors[] = array('subject' => 'Subject is empty');
        }
        if (trim($message) == '') {
            $errors[] = array('message' => 'Message is empty');
        }
        if (!empty($errors)) {
            return array('discussionid' => null, 'warnings' => $errors);
        }
        $adddiscussionreturn =  mod_forum_external::add_discussion_post($params['postid'], $params['subject'], $params['message'], $params['options']);

        $adddiscussionreturn['discussionid'] = $DB->get_field('forum_posts', 'discussion', array('id' => $adddiscussionreturn['postid']));

        $inlineattachmentsid = api::extract_clean_parameter('inlineattachmentsid', $options, 0);
        api::clean_user_draft_area($inlineattachmentsid);

        return $adddiscussionreturn;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function oua_forum_add_discussion_post_returns() {
        return new external_single_structure(
            array(
                'discussionid' => new external_value(PARAM_INT, 'Discussion ID of post'),
                'postid' => new external_value(PARAM_INT, 'New Post Id'),
                'warnings' => new external_warnings()
            )
        );
    }

}
