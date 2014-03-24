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
 * A scheduled task to send unread emails.
 *
 * @package    mod_forum
 * @copyright  2013 onwards Martin Dougiamas  http://dougiamas.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_forum\task;

/**
 * Simple task to send unread emails.
 */
class send_unread_emails extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendunreademails', 'mod_forum');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $CFG, $DB;

        // Make sure the global is included.
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $site = get_site();

        // All users that are subscribed to any post that needs sending,
        // please increase $CFG->extramemorylimit on large sites that
        // send notifications to a large number of users.
        $users = array();
        $userscount = 0; // Cached user counter - count($users) in PHP is horribly slow!!!

        // Status arrays.
        $mailcount  = array();
        $errorcount = array();

        // Caches.
        $discussions     = array();
        $forums          = array();
        $courses         = array();
        $coursemodules   = array();
        $subscribedusers = array();


        // Posts older than 2 days will not be mailed.  This is to avoid the problem where
        // cron has not been running for a long time, and then suddenly people are flooded
        // with mail from the past few weeks or months
        $timenow   = time();
        $endtime   = $timenow - $CFG->maxeditingtime;
        $starttime = $endtime - 48 * 3600;   // Two days earlier.

        // Get the list of forum subscriptions for per-user per-forum maildigest settings.
        $digestsset = $DB->get_recordset('forum_digests', null, '', 'id, userid, forum, maildigest');
        $digests = array();
        foreach ($digestsset as $thisrow) {
            if (!isset($digests[$thisrow->forum])) {
                $digests[$thisrow->forum] = array();
            }
            $digests[$thisrow->forum][$thisrow->userid] = $thisrow->maildigest;
        }
        $digestsset->close();

        if ($posts = forum_get_unmailed_posts($starttime, $endtime, $timenow)) {
            // Mark them all now as being mailed.  It's unlikely but possible there
            // might be an error later so that a post is NOT actually mailed out,
            // but since mail isn't crucial, we can accept this risk.  Doing it now
            // prevents the risk of duplicated mails, which is a worse problem.

            if (!forum_mark_old_posts_as_mailed($endtime)) {
                // We throw an exception here to use the scheduled task retry logic.
                throw new \moodle_exception('Errors occurred while trying to mark some posts as being mailed.');
            }

            // Checking post validity, and adding users to loop through later.
            foreach ($posts as $pid => $post) {

                $discussionid = $post->discussion;
                if (!isset($discussions[$discussionid])) {
                    if ($discussion = $DB->get_record('forum_discussions', array('id'=> $post->discussion))) {
                        $discussions[$discussionid] = $discussion;
                    } else {
                        mtrace('Could not find discussion '.$discussionid);
                        unset($posts[$pid]);
                        continue;
                    }
                }
                $forumid = $discussions[$discussionid]->forum;
                if (!isset($forums[$forumid])) {
                    if ($forum = $DB->get_record('forum', array('id' => $forumid))) {
                        $forums[$forumid] = $forum;
                    } else {
                        mtrace('Could not find forum '.$forumid);
                        unset($posts[$pid]);
                        continue;
                    }
                }
                $courseid = $forums[$forumid]->course;
                if (!isset($courses[$courseid])) {
                    if ($course = $DB->get_record('course', array('id' => $courseid))) {
                        $courses[$courseid] = $course;
                    } else {
                        mtrace('Could not find course '.$courseid);
                        unset($posts[$pid]);
                        continue;
                    }
                }
                if (!isset($coursemodules[$forumid])) {
                    if ($cm = get_coursemodule_from_instance('forum', $forumid, $courseid)) {
                        $coursemodules[$forumid] = $cm;
                    } else {
                        mtrace('Could not find course module for forum '.$forumid);
                        unset($posts[$pid]);
                        continue;
                    }
                }


                // Caching subscribed users of each forum.
                if (!isset($subscribedusers[$forumid])) {
                    $modcontext = context_module::instance($coursemodules[$forumid]->id);
                    if ($subusers = forum_subscribed_users($courses[$courseid], $forums[$forumid], 0, $modcontext, "u.*")) {
                        foreach ($subusers as $postuser) {
                            // This user is subscribed to this forum.
                            $subscribedusers[$forumid][$postuser->id] = $postuser->id;
                            $userscount++;
                            if ($userscount > FORUM_CRON_USER_CACHE) {
                                // Store minimal user info.
                                $minuser = new \stdClass();
                                $minuser->id = $postuser->id;
                                $users[$postuser->id] = $minuser;
                            } else {
                                // Cache full user record.
                                forum_cron_minimise_user_record($postuser);
                                $users[$postuser->id] = $postuser;
                            }
                        }
                        // Release memory.
                        unset($subusers);
                        unset($postuser);
                    }
                }

                $mailcount[$pid] = 0;
                $errorcount[$pid] = 0;
            }
        }

        if ($users && $posts) {

            $urlinfo = parse_url($CFG->wwwroot);
            $hostname = $urlinfo['host'];

            foreach ($users as $userto) {

                core_php_time_limit::raise(120); // Terminate if processing of any account takes longer than 2 minutes.

                mtrace('Processing user '.$userto->id);

                // Init user caches - we keep the cache for one cycle only,
                // otherwise it could consume too much memory.
                if (isset($userto->username)) {
                    $userto = clone($userto);
                } else {
                    $userto = $DB->get_record('user', array('id' => $userto->id));
                    forum_cron_minimise_user_record($userto);
                }
                $userto->viewfullnames = array();
                $userto->canpost       = array();
                $userto->markposts     = array();

                // Set this so that the capabilities are cached, and environment matches receiving user.
                cron_setup_user($userto);

                // Reset the caches.
                foreach ($coursemodules as $forumid=>$unused) {
                    $coursemodules[$forumid]->cache       = new \stdClass();
                    $coursemodules[$forumid]->cache->caps = array();
                    unset($coursemodules[$forumid]->uservisible);
                }

                foreach ($posts as $pid => $post) {

                    // Set up the environment for the post, discussion, forum, course
                    $discussion = $discussions[$post->discussion];
                    $forum      = $forums[$discussion->forum];
                    $course     = $courses[$forum->course];
                    $cm         =& $coursemodules[$forum->id];

                    // Do some checks  to see if we can bail out now
                    // Only active enrolled users are in the list of subscribers
                    if (!isset($subscribedusers[$forum->id][$userto->id])) {
                        continue; // User does not subscribe to this forum.
                    }

                    // Don't send email if the forum is Q&A and the user has not posted
                    // Initial topics are still mailed
                    if ($forum->type == 'qanda' && !forum_get_user_posted_time($discussion->id, $userto->id) && $pid != $discussion->firstpost) {
                        mtrace('Did not email '.$userto->id.' because user has not posted in discussion');
                        continue;
                    }

                    // Get info about the sending user
                    if (array_key_exists($post->userid, $users)) { // we might know him/her already
                        $userfrom = $users[$post->userid];
                        if (!isset($userfrom->idnumber)) {
                            // Minimalised user info, fetch full record.
                            $userfrom = $DB->get_record('user', array('id' => $userfrom->id));
                            forum_cron_minimise_user_record($userfrom);
                        }

                    } else if ($userfrom = $DB->get_record('user', array('id' => $post->userid))) {
                        forum_cron_minimise_user_record($userfrom);
                        // Fetch only once if possible, we can add it to user list, it will be skipped anyway.
                        if ($userscount <= FORUM_CRON_USER_CACHE) {
                            $userscount++;
                            $users[$userfrom->id] = $userfrom;
                        }

                    } else {
                        mtrace('Could not find user '.$post->userid);
                        continue;
                    }

                    // If we want to check that userto and userfrom are not the same person this is probably the spot to do it.

                    // Setup global $COURSE properly - needed for roles and languages.
                    cron_setup_user($userto, $course);

                    // Fill caches
                    if (!isset($userto->viewfullnames[$forum->id])) {
                        $modcontext = context_module::instance($cm->id);
                        $userto->viewfullnames[$forum->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                    }
                    if (!isset($userto->canpost[$discussion->id])) {
                        $modcontext = context_module::instance($cm->id);
                        $canpost = forum_user_can_post($forum, $discussion, $userto, $cm, $course, $modcontext);
                        $userto->canpost[$discussion->id] = $canpost;
                    }
                    if (!isset($userfrom->groups[$forum->id])) {
                        if (!isset($userfrom->groups)) {
                            $userfrom->groups = array();
                            if (isset($users[$userfrom->id])) {
                                $users[$userfrom->id]->groups = array();
                            }
                        }
                        $userfrom->groups[$forum->id] = groups_get_all_groups($course->id, $userfrom->id, $cm->groupingid);
                        if (isset($users[$userfrom->id])) {
                            $users[$userfrom->id]->groups[$forum->id] = $userfrom->groups[$forum->id];
                        }
                    }

                    // Make sure groups allow this user to see this email.
                    if ($discussion->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {
                        // Groups are being used.
                        if (!groups_group_exists($discussion->groupid)) { // Can't find group.
                            continue;                           // Be safe and don't send it to anyone.
                        }

                        if (!groups_is_member($discussion->groupid) and
                            !has_capability('moodle/site:accessallgroups', $modcontext)) {
                            // Do not send posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS.
                            continue;
                        }
                    }

                    // Make sure we're allowed to see it...
                    if (!forum_user_can_see_post($forum, $discussion, $post, NULL, $cm)) {
                        mtrace('user '.$userto->id. ' can not see '.$post->id);
                        continue;
                    }

                    // OK so we need to send the email.

                    // Does the user want this post in a digest?  If so postpone it for now.
                    $maildigest = forum_get_user_maildigest_bulk($digests, $userto, $forum->id);

                    if ($maildigest > 0) {
                        // This user wants the mails to be in digest form.
                        $queue = new \stdClass();
                        $queue->userid       = $userto->id;
                        $queue->discussionid = $discussion->id;
                        $queue->postid       = $post->id;
                        $queue->timemodified = $post->created;
                        $DB->insert_record('forum_queue', $queue);
                        continue;
                    }


                    // Prepare to actually send the post now, and build up the content.

                    $cleanforumname = str_replace('"', "'", strip_tags(format_string($forum->name)));

                    $userfrom->customheaders = array (  // Headers to make emails easier to track.
                               'Precedence: Bulk',
                               'List-Id: "'.$cleanforumname.'" <moodleforum'.$forum->id.'@'.$hostname.'>',
                               'List-Help: '.$CFG->wwwroot.'/mod/forum/view.php?f='.$forum->id,
                               'Message-ID: '.forum_get_email_message_id($post->id, $userto->id, $hostname),
                               'X-Course-Id: '.$course->id,
                               'X-Course-Name: '.format_string($course->fullname, true)
                    );

                    if ($post->parent) {  // This post is a reply, so add headers for threading (see MDL-22551).
                        $userfrom->customheaders[] = 'In-Reply-To: '.forum_get_email_message_id($post->parent, $userto->id, $hostname);
                        $userfrom->customheaders[] = 'References: '.forum_get_email_message_id($post->parent, $userto->id, $hostname);
                    }

                    $shortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));

                    $a = new \stdClass();
                    $a->courseshortname = $shortname;
                    $a->forumname = $cleanforumname;
                    $a->subject = format_string($post->subject, true);
                    $postsubject = html_to_text(get_string('postmailsubject', 'forum', $a));
                    $posttext = forum_make_mail_text($course, $cm, $forum, $discussion, $post, $userfrom, $userto);
                    $posthtml = forum_make_mail_html($course, $cm, $forum, $discussion, $post, $userfrom, $userto);

                    // Send the post now!

                    mtrace('Sending ', '');

                    $eventdata = new \stdClass();
                    $eventdata->component        = 'mod_forum';
                    $eventdata->name             = 'posts';
                    $eventdata->userfrom         = $userfrom;
                    $eventdata->userto           = $userto;
                    $eventdata->subject          = $postsubject;
                    $eventdata->fullmessage      = $posttext;
                    $eventdata->fullmessageformat = FORMAT_PLAIN;
                    $eventdata->fullmessagehtml  = $posthtml;
                    $eventdata->notification = 1;

                    // If forum_replytouser is not set then send mail using the noreplyaddress.
                    if (empty($CFG->forum_replytouser)) {
                        // Clone userfrom as it is referenced by $users.
                        $cloneduserfrom = clone($userfrom);
                        $cloneduserfrom->email = $CFG->noreplyaddress;
                        $eventdata->userfrom = $cloneduserfrom;
                    }

                    $smallmessagestrings = new \stdClass();
                    $smallmessagestrings->user = fullname($userfrom);
                    $smallmessagestrings->forumname = "$shortname: ".format_string($forum->name,true).": ".$discussion->name;
                    $smallmessagestrings->message = $post->message;
                    // Make sure strings are in message recipients language.
                    $eventdata->smallmessage = get_string_manager()->get_string('smallmessage', 'forum', $smallmessagestrings, $userto->lang);

                    $eventdata->contexturl = "{$CFG->wwwroot}/mod/forum/discuss.php?d={$discussion->id}#p{$post->id}";
                    $eventdata->contexturlname = $discussion->name;

                    $mailresult = message_send($eventdata);
                    if (!$mailresult){
                        mtrace("Error: Forum send unread emails: Could not send out mail for id $post->id to user $userto->id".
                             " ($userto->email) .. not trying again.");
                        $errorcount[$post->id]++;
                    } else {
                        $mailcount[$post->id]++;

                        // Mark post as read if forum_usermarksread is set off.
                        if (!$CFG->forum_usermarksread) {
                            $userto->markposts[$post->id] = $post->id;
                        }
                    }

                    mtrace('post '.$post->id. ': '.$post->subject);
                }

                // Mark processed posts as read.
                forum_tp_mark_posts_read($userto, $userto->markposts);
                unset($userto);
            }
        }

        if ($posts) {
            foreach ($posts as $post) {
                mtrace($mailcount[$post->id]." users were sent post $post->id, '$post->subject'");
                if ($errorcount[$post->id]) {
                    $DB->set_field('forum_posts', 'mailed', FORUM_MAILED_ERROR, array('id' => $post->id));
                }
            }
        }

        // Release some memory.
        unset($subscribedusers);
        unset($mailcount);
        unset($errorcount);

        cron_setup_user();

    }

}
