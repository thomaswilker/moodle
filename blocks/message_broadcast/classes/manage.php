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
namespace block_message_broadcast;
defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden');

use context_system;
use context_course;
use stdClass;
use moodle_url;

class manage {
    /**
     * Gets message for manage message table
     * If context provided only retrieves messages that are sent _only_ to that context
     * @param null $coursecontextid
     * @return array
     */
    public function get_messages($coursecontextid = null) {
        global $DB;

        if ($DB->get_dbfamily() == 'postgres') {
            $aggregate = "string_agg(c.shortname, ',')";
        } else if ($DB->get_dbfamily() == 'mysql') {
            $aggregate = "group_concat(c.shortname)";
        }

        // Selects messages for the context specified or all messages if contextid is null
        // also provides the course shortname if the context is a course
        // or a comma seperated list of coursenames when a message has been sent to multiple contexts
        // LEFT JOIN as system context is contextlevel 10 not 50
        $messagesql = "SELECT mb.*, $aggregate as contextname
                         FROM {message_broadcast} mb
                         JOIN {message_broadcast_context} mbcx ON (mb.id = mbcx.messagebroadcastid)
                    LEFT JOIN {context} cx ON (mbcx.contextid = cx.id AND cx.contextlevel = ?)
                    LEFT JOIN {course} c ON (cx.instanceid = c.id)";

        $messagesqlparams = array(CONTEXT_COURSE);

        if ($coursecontextid !== null) {
            // For non admins, only show messages that have sent to this course context ONLY (i.e. exclude multi messages)
            $messagesql .= "  WHERE mbcx.contextid = ?
                                AND mb.id in (SELECT mb.id
                                                FROM {message_broadcast} mb
                                           LEFT JOIN {message_broadcast_context} mbcx ON (mb.id = mbcx.messagebroadcastid)
                                            GROUP BY mb.id
                                              HAVING count(mb.id) = 1)"; // Restrict to messages sent to only 1 course.
            $messagesqlparams[] = $coursecontextid;
        }
        $messagesql .= " GROUP BY 1,2,3,4
                         ORDER BY mb.enddate <> 0, mb.enddate DESC, mb.startdate DESC, mb.id DESC";

        $messages = $DB->get_records_sql($messagesql, $messagesqlparams);

        return $messages;
    }

    public function save_message($data, $now = null) {
        global $DB;

        if ($now === null) {
            $now = time();
        }

        $message = new message();
        $message->headingtitle = $data->headingtitle;
        $message->messagebody = $data->messagebody;
        $message->userid = $data->uid;

        // Set startdate and enddate.
        list($message->startdate, $message->enddate) = $this->set_startdate_enddate($data, $now);

        $message->timecreated = $now;
        $message->lasteditdate = $now;
        $messagebroadcastid = $DB->insert_record('message_broadcast', $message);

        // Save attachments if any.
        $this->save_draft_area_files($messagebroadcastid);

        $transaction = $DB->start_delegated_transaction();
        foreach ($data->courseids as $courseid) {
            if ($courseid == 0) {
                $context = context_system::instance();
            } else {
                $context = context_course::instance($courseid);
            }
            $messagebroadcastcontext = new stdClass();
            $messagebroadcastcontext->messagebroadcastid = $messagebroadcastid;
            $messagebroadcastcontext->contextid = (int)$context->id;
            $messagebroadcastcontextids[] = $DB->insert_record('message_broadcast_context', $messagebroadcastcontext);
        }

        $DB->commit_delegated_transaction($transaction);
        return array($messagebroadcastid, $messagebroadcastcontextids);
    }

    public function edit_message($data, $now = null) {
        global $DB;

        if ($now === null) {
            $now = time();
        }

        $message = new message();
        $message->id = $data->id;
        $message->headingtitle = $data->headingtitle;
        $message->messagebody = $data->messagebody;
        $message->lasteditdate = $now;
        $message->userid = $data->uid;

        // Set startdate and enddate.
        list($message->startdate, $message->enddate) = $this->set_startdate_enddate($data, $now);

        // Save attachments if any.
        $this->save_draft_area_files($message->id);

        $transaction = $DB->start_delegated_transaction();

        $DB->delete_records('message_broadcast_read', array('messagebroadcastid' => $data->id));
        $DB->delete_records('message_broadcast_context', array('messagebroadcastid' => $data->id));
        $DB->update_record('message_broadcast', $message);

        foreach ($data->courseids as $courseid) {
            if ($courseid == 0) {
                $context = context_system::instance();
            } else {
                $context = context_course::instance($courseid);
            }
            $messagebroadcastcontext = new stdClass();
            $messagebroadcastcontext->messagebroadcastid = $data->id;
            $messagebroadcastcontext->contextid = (int)$context->id;
            $DB->insert_record('message_broadcast_context', $messagebroadcastcontext);
        }
        $DB->commit_delegated_transaction($transaction);
    }

    public function delete_message($messageid) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $messagecontexts = $DB->get_records("message_broadcast_context", (array("messagebroadcastid" => $messageid)));

        $DB->delete_records('message_broadcast_context', array('messagebroadcastid' => $messageid));
        $DB->delete_records('message_broadcast_read', array('messagebroadcastid' => $messageid));
        $DB->delete_records('message_broadcast', array('id' => $messageid));

        $fs = get_file_storage();


        $systemcontext = new stdClass(); // Ensure files are deleted from system context.
        $systemcontext->contextid = context_system::instance()->id;
        $messagecontexts[] =  $systemcontext;
        foreach($messagecontexts as $context) { // Ensure files are also deleted from individual course contexts.
            $fs->delete_area_files($context->contextid, 'block_message_broadcast', form::ATTACHMENTS_AREA, $messageid);
            $fs->delete_area_files($context->contextid, 'block_message_broadcast', form::ATTACHMENTS_FILEMANAGER, $messageid);
        }

        $DB->commit_delegated_transaction($transaction);
    }

    public function mark_read($user, $messageid, $now = null) {
        global $DB;

        if ($now === null) {
            $now = time();
        }

        $message = new stdClass();
        $message->messagebroadcastid = $messageid;
        $message->useridto = $user->id;
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('message_broadcast_read', (array)$message);
        $message->timeread = $now;
        $DB->insert_record('message_broadcast_read', $message);
        $DB->commit_delegated_transaction($transaction);
    }

    /**
     * Get unread messages for this user and the current context + parent contexts
     *
     * @param $userid moodle user id
     * @param $allcontexts
     * @param null $nowtime default to time()
     * @return array
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_unread_messages($userid, $allcontexts, $nowtime = null) {
        global $DB, $OUTPUT;

        $queryparams = array($userid);

        list($insql, $params) = $DB->get_in_or_equal($allcontexts);
        $queryparams = array_merge($queryparams, $params);

        if ($nowtime === null) {
            $nowtime = time();
        }

        $nowdate = make_timestamp(date('Y', $nowtime), date('m', $nowtime), date('d', $nowtime), 0, 0, 0);
        // Unread messages that visible at now time.
        $queryparams[] = $nowdate;
        $queryparams[] = $nowdate;

        $sql = "SELECT mb.*
                  FROM {message_broadcast} mb
                  JOIN {message_broadcast_context} mbcx ON (mb.id = mbcx.messagebroadcastid)
             LEFT JOIN {message_broadcast_read} mbr ON (mb.id = mbr.messagebroadcastid AND mbr.useridto = ? AND mb.lasteditdate <= mbr.timeread)
                 WHERE mbr.id IS NULL
                   AND mbcx.contextid $insql
                   AND mb.startdate > 0 AND mb.startdate <= ? AND ((mb.enddate >= ?) OR (mb.enddate = 0))
              ORDER BY mb.lasteditdate DESC, mb.id DESC";

        $messages = $DB->get_records_sql($sql, $queryparams);

        $fs = get_file_storage();
        $context = form::get_context();
        $contextid = $context->id;
        $filearea = form::ATTACHMENTS_AREA;

        // Get announcement message attachments.
        foreach ($messages as &$message) {
            $message->attachments = array();
            $files = $fs->get_area_files($contextid, 'block_message_broadcast', $filearea, $message->id);
            foreach ($files as $file) {
                if (!$file->is_directory()) {
                    $message->attachments[] = array('filename' => $file->get_filename(), 'mimetype' => $file->get_mimetype(),
                                                    'fileurl' => $url = moodle_url::make_pluginfile_url($file->get_contextid(),
                                                                                                        $file->get_component(),
                                                                                                        $file->get_filearea(),
                                                                                                        $file->get_itemid(),
                                                                                                        $file->get_filepath(),
                                                                                                        $file->get_filename()),
                                                    'icon' => file_file_icon($file),
                                                    'pix_url_html' => $OUTPUT->pix_icon(file_file_icon($file),
                                                                                        $file->get_mimetype(), 'moodle',
                                                                                        array('class' => 'icon'))

                    );
                }
            }
        }

        return $messages;
    }

    protected function save_draft_area_files($messageid) {
        // Context of block_message_broadcast
        $context = form::get_context();
        $contextid = $context->id;
        $draftitemid = file_get_submitted_draft_itemid(form::ATTACHMENTS_FILEMANAGER);
        file_prepare_draft_area($draftitemid, $contextid, 'block_message_broadcast', form::ATTACHMENTS_AREA, $messageid);
        if ($draftitemid) {
            file_save_draft_area_files($draftitemid, $contextid, 'block_message_broadcast', form::ATTACHMENTS_AREA, $messageid);
        }
    }

    /**
     * Check and set startdate and enddate.
     *
     * @param $data
     * @param $timestamp
     *
     * @return object
     * @internal param $today
     */
    protected function set_startdate_enddate($data, $timestamp) {

        $startdate = make_timestamp(date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp), 0, 0, 0);
        $enddate = 0;

        if (isset($data->startdate) && !empty($data->startdate) && $data->startdate >= 0) {
            $startdate = make_timestamp(date('Y', $data->startdate), date('m', $data->startdate), date('d', $data->startdate), 0, 0, 0);
        }

        if (isset($data->enddate) && !empty($data->enddate) && $data->enddate >= 0) {
            // When enddate is not disabled, then extend the display time to end of the day.
            // When enddate is disabled (0) don't do anything because we always show the message.
            // Ensure enddate stays in the same day for displaying purpose.
            $enddate = make_timestamp(date('Y', $data->enddate), date('m', $data->enddate), date('d', $data->enddate), 0, 0, 0);
        }

        return array($startdate, $enddate);
    }
}
