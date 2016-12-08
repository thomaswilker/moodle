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
 * This is the external API for messages
 *
 * @package    local_conversations
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
namespace local_conversations;
require_once($CFG->dirroot . "/lib/externallib.php");

use external_api;
use external_description;
use external_function_parameters;
use external_value;
use external_multiple_structure;
use external_single_structure;
use external_warnings;
use context_system;
use core_user;

class external extends external_api {
    /**
     * Override clean_return value to add $key to multiple structures.
     * We cant just override the multple bit and use parent because of the lack of use of static binding in parent class
     * see MDL-56372
     *
     * @param external_description $description
     * @param mixed $response
     * @return array|mixed
     * @throws \invalid_parameter_exception
     * @throws invalid_response_exception
     */
    public static function clean_returnvalue(external_description $description, $response) {
        if ($description instanceof external_value) {
            if (is_array($response) or is_object($response)) {
                throw new invalid_response_exception('Scalar type expected, array or object received.');
            }

            if ($description->type == PARAM_BOOL) {
                // special case for PARAM_BOOL - we want true/false instead of the usual 1/0 - we can not be too strict here ;-)
                if (is_bool($response) or $response === 0 or $response === 1 or $response === '0' or $response === '1') {
                    return (bool)$response;
                }
            }
            $debuginfo = 'Invalid external api response: the value is "' . $response .
                         '", the server was expecting "' . $description->type . '" type';
            try {
                return validate_param($response, $description->type, $description->allownull, $debuginfo);
            } catch (invalid_parameter_exception $e) {
                //proper exception name, to be recursively catched to build the path to the faulty attribut
                throw new invalid_response_exception($e->debuginfo);
            }

        } else if ($description instanceof external_single_structure) {
            if (!is_array($response) && !is_object($response)) {
                throw new invalid_response_exception('Only arrays/objects accepted. The bad value is: \'' .
                                                     print_r($response, true) . '\'');
            }

            // Cast objects into arrays.
            if (is_object($response)) {
                $response = (array) $response;
            }

            $result = array();
            foreach ($description->keys as $key=>$subdesc) {
                if (!array_key_exists($key, $response)) {
                    if ($subdesc->required == VALUE_REQUIRED) {
                        throw new invalid_response_exception('Error in response - Missing following required key in a single structure: ' . $key);
                    }
                    if ($subdesc instanceof external_value) {
                        if ($subdesc->required == VALUE_DEFAULT) {
                            try {
                                $result[$key] = self::clean_returnvalue($subdesc, $subdesc->default);
                            } catch (invalid_response_exception $e) {
                                //build the path to the faulty attribut
                                throw new invalid_response_exception($key." => ".$e->getMessage() . ': ' . $e->debuginfo);
                            }
                        }
                    }
                } else {
                    try {
                        $result[$key] = self::clean_returnvalue($subdesc, $response[$key]);
                    } catch (invalid_response_exception $e) {
                        //build the path to the faulty attribut
                        throw new invalid_response_exception($key." => ".$e->getMessage() . ': ' . $e->debuginfo);
                    }
                }
                unset($response[$key]);
            }

            return $result;

        } else if ($description instanceof external_multiple_structure) {
            if (!is_array($response)) {
                throw new invalid_response_exception('Only arrays accepted. The bad value is: \'' .
                                                     print_r($response, true) . '\'');
            }
            $result = array();
            foreach ($response as $key=> $param) {
                $result[$key] = self::clean_returnvalue($description->content, $param);
            }
            return $result;

        } else {
            throw new invalid_response_exception('Invalid external api response description');
        }
    }

    public static function send_message($useridto, $message) {
        global $USER;
        $params = self::validate_parameters(self::send_message_parameters(), array('useridto' => $useridto, 'message' => $message));
        $useridtoobj = core_user::get_user($params['useridto']);
        if (trim($message) == '') {
            $errors[] = array('message' => 'Message is empty');
        }
        if (!empty($errors)) {
            return array('error' => true, 'warnings' => $errors);
        }
        if (message_can_post_message($useridtoobj, $USER)) {
            $messageid = message_post_message($USER, $useridtoobj, $params['message'], FORMAT_MOODLE);

            if (!$messageid) {
                throw new moodle_exception('errorwhilesendingmessage', 'core_message');
            }
        } else {
            throw new moodle_exception('unabletomessageuser', 'core_message');
        }

        return array('error' => false);
    }

    public static function send_message_parameters() {
        return new external_function_parameters(array('useridto' => new external_value(PARAM_INT, 'user id of message to send to'),
                                                      'message' => new external_value(PARAM_RAW, 'text of message to send')));
    }

    public static function send_message_returns() {
        return new external_single_structure(array('error' => new external_value(PARAM_BOOL, 'Did the message send successfully'),
                                                   'warnings' => new external_warnings()));
    }

    /**
     * @param $useridfrom
     * @return array conversation preview cache + all messages for and from given user.
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function get_conversation($useridfrom) {
        global $PAGE, $USER;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.

        $params = self::validate_parameters(self::get_conversation_parameters(), array('useridfrom' => $useridfrom));
        $messageslist = api::get_user_conversations($USER->id, $params['useridfrom']);
        $messages = api::group_user_conversations($messageslist, $USER);
        $conversationpreviewcache = api::get_cached_unread_conversation_preview($USER);
        $return = array('conversation_preview_cache' => $conversationpreviewcache, 'messages' => $messages);
        return $return;
    }

    public static function get_conversation_parameters() {
        return new external_function_parameters(array('useridfrom' => new external_value(PARAM_INT,
                                                                                         'user id of conversation to get, if not specified, returns all users.',
                                                                                         VALUE_DEFAULT, null)));
    }

    public static function get_conversation_returns() {
        return new external_single_structure(
                    array('conversation_preview_cache' => new external_single_structure(
                                                            array('unread_conversation_count' => new external_value(PARAM_INT, ''),
                                                                 'all_conversations_link' => new external_value(PARAM_TEXT, ''),
                                                                 'users_with_unread' => new external_multiple_structure(new external_value(PARAM_INT, '')),
                                                                 'unread_conversation_preview' => new external_multiple_structure(
                                                                                        new external_single_structure(
                                                                                            array("useridfrom" => new external_value(PARAM_TEXT),
                                                                                                  "lastmessagesnippet" => new external_value(PARAM_TEXT),
                                                                                                  "timecreated" => new external_value(PARAM_TEXT),
                                                                                                  "id" => new external_value(PARAM_TEXT),
                                                                                                  "picture" => new external_value(PARAM_TEXT),
                                                                                                  "firstname" => new external_value(PARAM_TEXT),
                                                                                                  "lastname" => new external_value(PARAM_TEXT),
                                                                                                  "firstnamephonetic" => new external_value(PARAM_TEXT),
                                                                                                  "lastnamephonetic" => new external_value(PARAM_TEXT),
                                                                                                  "middlename" => new external_value(PARAM_TEXT),
                                                                                                  "alternatename" => new external_value(PARAM_TEXT),
                                                                                                  "imagealt" => new external_value(PARAM_TEXT),
                                                                                                  "email" => new external_value(PARAM_TEXT),
                                                                                                  "lastaccess" => new external_value(PARAM_TEXT),
                                                                                                  "unreadcount" => new external_value(PARAM_TEXT),
                                                                                                  "otheruserpictureurl" => new external_value(PARAM_TEXT),
                                                                                                  "otheruserprofileurl" => new external_value(PARAM_TEXT),
                                                                                                  "otheruserfullname" => new external_value(PARAM_TEXT),
                                                                                                  "lastmessagetimeformatted" => new external_value(PARAM_TEXT)), '', VALUE_OPTIONAL),
                                                                                    '', VALUE_OPTIONAL),
                                                            '', VALUE_OPTIONAL)),
                                           'messages' => new external_multiple_structure(
                                                new external_single_structure(
                                                    array( 'otheruserpictureurl' => new external_value(PARAM_TEXT, 'Other user picture url'),
                                                           'otheruserprofileurl' => new external_value(PARAM_TEXT, 'Other user profile url'),
                                                           'otheruserfullname' => new external_value(PARAM_TEXT, 'Other user full name'),
                                                           'lastmessagesnippet' => new external_value(PARAM_TEXT, 'Last message snippet from'),
                                                           'id' => new external_value(PARAM_INT, 'Other user id'),
                                                           'firstname' => new external_value(PARAM_TEXT, 'Other User firstname'),
                                                           'lastname' => new external_value(PARAM_TEXT, 'Other user lastname'),
                                                           'messages' => new external_multiple_structure(
                                                                               new external_single_structure(
                                                                                   array(
                                                                                       'messageid' => new external_value(PARAM_INT, 'Message id'),
                                                                                       'id' => new external_value(PARAM_INT, 'Message id'),
                                                                                       'userfromid' => new external_value(PARAM_INT, 'User from id'),
                                                                                       'useridto' => new external_value(PARAM_INT, 'User to id'),
                                                                                       'subject' => new external_value(PARAM_TEXT, 'The message subject'),
                                                                                       'smallmessage' => new external_value(PARAM_RAW, 'The shorten message'),
                                                                                       'notification' => new external_value(PARAM_INT, 'Is a notification?'),
                                                                                       'timecreated' => new external_value(PARAM_INT, 'Time created'),
                                                                                       'formatted_timecreated'=> new external_value(PARAM_TEXT, 'Time created formatted'),
                                                                                       'fromme'=> new external_value(PARAM_BOOL, 'Is message sent by me'),
                                                                                       'unread'=> new external_value(PARAM_INT, 'Unread'),
                                                                                       'userpictureurl'=> new external_value(PARAM_TEXT, 'User picture url (may be self)'),
                                                                                       'userprofileurl'=> new external_value(PARAM_TEXT, 'User profile url (may be my self)'),
                                                                                   )
                                                                                )
                                                           )
                                                    )
                                                )
                                           )
                                     ));
    }

    /**
     * Returns a list of contacts in a format that suits the mustache context for contact list
     * Optionally filtered.
     *
     * @param $query
     * @return mixed
     * @throws \dml_exception
     * @throws \invalid_parameter_exception
     */
    public static function search_contacts($query) {
        global $PAGE, $USER;
        $PAGE->set_context(context_system::instance());
        $params = self::validate_parameters(self::search_contacts_parameters(), array('searchstring' => $query));
        $users = api::search_connected_and_messaged_users($USER->id, $params['searchstring']);
        $formattedusers = api::format_contact_list($users);

        $return = array_values($formattedusers);
        return $return;
    }

    public static function search_contacts_parameters() {
        return new external_function_parameters(array('searchstring' => new external_value(PARAM_TEXT, 'search string',
                                                                                           VALUE_DEFAULT, null)));
    }

    public static function search_contacts_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                     "id" => new external_value(PARAM_TEXT),
                     "picture" => new external_value(PARAM_TEXT),
                     "firstname" => new external_value(PARAM_TEXT),
                     "lastname" => new external_value(PARAM_TEXT),
                     "firstnamephonetic" => new external_value(PARAM_TEXT),
                     "lastnamephonetic" => new external_value(PARAM_TEXT),
                     "middlename" => new external_value(PARAM_TEXT),
                     "alternatename" => new external_value(PARAM_TEXT),
                     "imagealt" => new external_value(PARAM_TEXT),
                     "email" => new external_value(PARAM_TEXT),
                     "lastaccess" => new external_value(PARAM_TEXT),
                     "sortfirstname" => new external_value(PARAM_TEXT),
                     "sortlastname" => new external_value(PARAM_TEXT),
                     "otheruserpictureurl" => new external_value(PARAM_TEXT),
                      "otheruserprofileurl" => new external_value(PARAM_TEXT),
                      "otheruserfullname" => new external_value(PARAM_TEXT)
                )
            )
        );
    }

    /**
     * Mark a message as read given the id.
     * @param $messageids
     * @return array
     * @throws \invalid_parameter_exception
     */
    public static function mark_messages_read_by_id($messageids) {
        $params = self::validate_parameters(self::mark_messages_read_by_id_parameters(), array('messageids' => $messageids));
        api::mark_messages_read_by_id($params['messageids']);
        return true;
    }

    /**
     * @return external_function_parameters
     */
    public static function mark_messages_read_by_id_parameters() {
        return new external_function_parameters(array('messageids' => new external_multiple_structure(new external_value(PARAM_INT,
                                                                                                                         'Message ids'),
                                                                                                      'List of message ids to mark as read',
                                                                                                      VALUE_REQUIRED)));
    }

    /**
     * Description of result value.
     * @return external_multiple_structure
     */
    public static function mark_messages_read_by_id_returns() {
        return new external_value(PARAM_BOOL, 'Did the message get marked as read successfully');
    }

    /**
     * Delete conversation (messages) to/from $otheruserid.
     *
     * @param $otheruserid
     * @throws \invalid_parameter_exception
     */
    public static function delete_conversation($otheruserid) {
        global $USER;
        $params = self::validate_parameters(self::delete_conversation_parameters(), array('otheruserid' => $otheruserid));

        api::delete_conversation($USER->id, $params['otheruserid']);
        return true;
    }

    /**
     * @return external_function_parameters
     */
    public static function delete_conversation_parameters() {
        return new external_function_parameters(array('otheruserid' => new external_value(PARAM_INT,
                                                                                          'Delete messages to/from this useridto ')));
    }

    /**
     * Description of result value.
     * @return external_multiple_structure
     */
    public static function delete_conversation_returns() {
        return new external_value(PARAM_BOOL, 'Did the conversation delete successfully');
    }

    /**
     * Deletes the given notification id
     *
     * @param $messageids Id of notification to delete
     * @return array
     */
    public static function delete_messages_by_id($messageids) {
        global $USER;
        $params = self::validate_parameters(self::delete_messages_by_id_parameters(), array('messageids' => $messageids));

        $return =  api::delete_messages_by_id($USER->id, $params['messageids']);
        return true;
    }

    /**
     * Returns description of delete_messages_by_id() parameters.
     *
     * @return external_function_parameters
     */
    public static function delete_messages_by_id_parameters() {
        return new external_function_parameters(array('messageids' => new external_multiple_structure(new external_value(PARAM_INT,
                                                                                                                         'Message ids'),
                                                                                                      'List of message ids to delete',
                                                                                                      VALUE_REQUIRED)));
    }

    /**
     * Returns description of request_connection() result value.
     *
     * @return external_description
     */
    public static function delete_messages_by_id_returns() {
        return new external_value(PARAM_BOOL, 'Did the message(s) delete successfully');
    }

    /**
     * @param $foruser
     * @return array all read/unread conversations for user
     */
    public static function get_all_notifications() {
        global $PAGE;
        $PAGE->set_context(context_system::instance()); // This is required because we call a renderer, context is not auto set.

        $notifications = new  \local_conversations\output\my_notifications();
        $renderer = $PAGE->get_renderer('core');
        $return = array('notification_preview_cache' => array(), 'notifications' => $notifications->export_for_template($renderer));
        return $return;
    }

    public static function get_all_notifications_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_all_notifications_returns() {
        return new external_single_structure(
            array('notification_preview_cache' => new external_single_structure(
                array('unread_notification_count' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                      'all_notifications_link' => new external_value(PARAM_TEXT, '', VALUE_OPTIONAL),
                      'unread_notification_preview' => new external_multiple_structure(
                          new external_single_structure(
                              array("id" => new external_value(PARAM_TEXT),
                                    "notificationid" => new external_value(PARAM_TEXT),
                                    "useridfrom" => new external_value(PARAM_TEXT),
                                    "useridto" => new external_value(PARAM_TEXT),
                                    "subject" => new external_value(PARAM_TEXT),
                                    "fullmessage" => new external_value(PARAM_TEXT),
                                    "fullmessageformat" => new external_value(PARAM_TEXT),
                                    "fullmessagehtml" => new external_value(PARAM_RAW),
                                    "smallmessage" => new external_value(PARAM_TEXT),
                                    "notification" => new external_value(PARAM_TEXT),
                                    "timecreated" => new external_value(PARAM_TEXT),
                                    "timeread" => new external_value(PARAM_TEXT),
                                    "unread" => new external_value(PARAM_BOOL),
                                    "realuseridfrom" => new external_value(PARAM_TEXT),
                                    "picture" => new external_value(PARAM_TEXT),
                                    "firstname" => new external_value(PARAM_TEXT),
                                    "lastname" => new external_value(PARAM_TEXT),
                                    "firstnamephonetic" => new external_value(PARAM_TEXT),
                                    "lastnamephonetic" => new external_value(PARAM_TEXT),
                                    "middlename" => new external_value(PARAM_TEXT),
                                    "alternatename" => new external_value(PARAM_TEXT),
                                    "imagealt" => new external_value(PARAM_TEXT),
                                    "email" => new external_value(PARAM_TEXT),
                                    "iconclass" => new external_value(PARAM_TEXT),
                                    "timecreated_formatted" => new external_value(PARAM_CLEANHTML),
                                    "simplenotification" => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                    "userpictureurl" => new external_value(PARAM_TEXT),
                                    "userprofileurl" => new external_value(PARAM_TEXT),
                                    "userfullname" => new external_value(PARAM_TEXT)
                              ), '', VALUE_OPTIONAL
                          ), '', VALUE_OPTIONAL
                      )
                    )
                ),
            'notifications' =>  new external_single_structure(
                array('hasnotifications' => new external_value(PARAM_BOOL),
                      'mynotifications' => new external_multiple_structure(
                                new external_single_structure (
                                    array( "id" => new external_value(PARAM_TEXT),
                                          "notificationid" => new external_value(PARAM_TEXT),
                                          "useridfrom" => new external_value(PARAM_TEXT),
                                          "useridto" => new external_value(PARAM_TEXT),
                                          "subject" => new external_value(PARAM_TEXT),
                                          "fullmessage" => new external_value(PARAM_TEXT),
                                          "fullmessageformat" => new external_value(PARAM_TEXT),
                                          "fullmessagehtml" => new external_value(PARAM_RAW),
                                          "smallmessage" => new external_value(PARAM_TEXT),
                                          "notification" => new external_value(PARAM_TEXT),
                                          "timecreated" => new external_value(PARAM_TEXT),
                                          "timeread" => new external_value(PARAM_TEXT),
                                          "unread" => new external_value(PARAM_BOOL),
                                          "realuseridfrom" => new external_value(PARAM_TEXT),
                                          "picture" => new external_value(PARAM_TEXT),
                                          "firstname" => new external_value(PARAM_TEXT),
                                          "lastname" => new external_value(PARAM_TEXT),
                                          "firstnamephonetic" => new external_value(PARAM_TEXT),
                                          "lastnamephonetic" => new external_value(PARAM_TEXT),
                                          "middlename" => new external_value(PARAM_TEXT),
                                          "alternatename" => new external_value(PARAM_TEXT),
                                          "imagealt" => new external_value(PARAM_TEXT),
                                          "email" => new external_value(PARAM_TEXT),
                                          "iconclass" => new external_value(PARAM_TEXT),
                                          "timecreated_formatted" => new external_value(PARAM_CLEANHTML),
                                          "simplenotification" => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                          "userpictureurl" => new external_value(PARAM_TEXT),
                                          "userprofileurl" => new external_value(PARAM_TEXT),
                                          "userfullname" => new external_value(PARAM_TEXT)
                                    ), '', VALUE_OPTIONAL
                                )
                      )
                )
            )
            )
        );


    }


    /**
     * @param $foruser
     * @return array all read/unread conversations for user
     */
    public static function get_cached_header_previews() {
        global $USER, $PAGE;
        $PAGE->set_context(context_system::instance());
        $conversationpreviewcache = api::get_cached_unread_conversation_preview($USER);
        $notificationpreviewcache = api::get_cached_unread_notification_preview($USER);
        $return = array('notification_preview_cache' => $notificationpreviewcache, 'conversation_preview_cache' => $conversationpreviewcache);
        return $return;
    }

    public static function get_cached_header_previews_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_cached_header_previews_returns() {
        return new external_single_structure(
                    array('notification_preview_cache' => new external_single_structure(
                                                                array('unread_notification_count' => new external_value(PARAM_INT, ''),
                                                                      'all_notifications_link' => new external_value(PARAM_TEXT, ''),
                                                                      'unread_notification_preview' => new external_multiple_structure(
                                                                            new external_single_structure(
                                                                                    array("id" => new external_value(PARAM_TEXT),
                                                                                        "notificationid" => new external_value(PARAM_TEXT),
                                                                                        "useridfrom" => new external_value(PARAM_TEXT),
                                                                                        "useridto" => new external_value(PARAM_TEXT),
                                                                                        "subject" => new external_value(PARAM_TEXT),
                                                                                        "fullmessage" => new external_value(PARAM_TEXT),
                                                                                        "fullmessageformat" => new external_value(PARAM_TEXT),
                                                                                        "fullmessagehtml" => new external_value(PARAM_RAW),
                                                                                        "smallmessage" => new external_value(PARAM_TEXT),
                                                                                        "notification" => new external_value(PARAM_TEXT),
                                                                                        "timecreated" => new external_value(PARAM_TEXT),
                                                                                        "timeread" => new external_value(PARAM_TEXT),
                                                                                        "unread" => new external_value(PARAM_BOOL),
                                                                                        "realuseridfrom" => new external_value(PARAM_TEXT),
                                                                                        "picture" => new external_value(PARAM_TEXT),
                                                                                        "firstname" => new external_value(PARAM_TEXT),
                                                                                        "lastname" => new external_value(PARAM_TEXT),
                                                                                        "firstnamephonetic" => new external_value(PARAM_TEXT),
                                                                                        "lastnamephonetic" => new external_value(PARAM_TEXT),
                                                                                        "middlename" => new external_value(PARAM_TEXT),
                                                                                        "alternatename" => new external_value(PARAM_TEXT),
                                                                                        "imagealt" => new external_value(PARAM_TEXT),
                                                                                        "email" => new external_value(PARAM_TEXT),
                                                                                        "iconclass" => new external_value(PARAM_TEXT),
                                                                                        "timecreated_formatted" => new external_value(PARAM_CLEANHTML),
                                                                                        "simplenotification" => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                                                                        "userpictureurl" => new external_value(PARAM_TEXT),
                                                                                        "userprofileurl" => new external_value(PARAM_TEXT),
                                                                                        "userfullname" => new external_value(PARAM_TEXT)
                                                                                    ), '', VALUE_OPTIONAL
                                                                            )

                                                                      )
                                                                )
                                                        ),

                        'conversation_preview_cache' => new external_single_structure(
                                                                array('unread_conversation_count' => new external_value(PARAM_INT, ''),
                                                                      'all_conversations_link' => new external_value(PARAM_TEXT, ''),
                                                                      'users_with_unread' => new external_multiple_structure(new external_value(PARAM_INT, '')),
                                                                      'unread_conversation_preview' => new external_multiple_structure(
                                                                                    new external_single_structure(
                                                                                        array("useridfrom" => new external_value(PARAM_TEXT),
                                                                                                "lastmessagesnippet" => new external_value(PARAM_TEXT),
                                                                                                "timecreated" => new external_value(PARAM_TEXT),
                                                                                                "id" => new external_value(PARAM_TEXT),
                                                                                                "picture" => new external_value(PARAM_TEXT),
                                                                                                "firstname" => new external_value(PARAM_TEXT),
                                                                                                "lastname" => new external_value(PARAM_TEXT),
                                                                                                "firstnamephonetic" => new external_value(PARAM_TEXT),
                                                                                                "lastnamephonetic" => new external_value(PARAM_TEXT),
                                                                                                "middlename" => new external_value(PARAM_TEXT),
                                                                                                "alternatename" => new external_value(PARAM_TEXT),
                                                                                                "imagealt" => new external_value(PARAM_TEXT),
                                                                                                "email" => new external_value(PARAM_TEXT),
                                                                                                "lastaccess" => new external_value(PARAM_TEXT),
                                                                                                "unreadcount" => new external_value(PARAM_TEXT),
                                                                                                "otheruserpictureurl" => new external_value(PARAM_TEXT),
                                                                                                "otheruserprofileurl" => new external_value(PARAM_TEXT),
                                                                                                "otheruserfullname" => new external_value(PARAM_TEXT),
                                                                                                "lastmessagetimeformatted" => new external_value(PARAM_TEXT)
                                                                                        ), '', VALUE_OPTIONAL
                                                                                    ), '', VALUE_OPTIONAL
                                                                      ), '', VALUE_OPTIONAL
                                                                )
                                                        )
                    )
        );
    }
}
