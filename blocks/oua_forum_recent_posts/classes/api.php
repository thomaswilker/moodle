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

namespace block_oua_forum_recent_posts;

use context_module;
use mod_forum_external;
use cache;
use cache_store;
use context_user;
/**
 * API exposed by block_oua_recent posts
 *
 * @copyright  2015 Ben Kelada (ben.kelada@open.edu.au)
 */
class api {

    /**
     * Returns a list of forum discussions in a parent/child tree structure.
     *
     * @param int $forumid the forum instance id
     * @param string $sortby sort by this element (id, timemodified, timestart or timeend)
     * @param string $sortdirection sort direction: ASC or DESC
     * @param int $page page number
     * @param int $perpage items per page
     *
     */
    public static function get_oua_forum_discussions_with_posts_paginated($forum, $sortby = 'timemodified', $sortdirection = 'DESC',
                                                                          $page = -1, $perpage = 0) {
        global $CFG, $OUTPUT, $USER;
        $forumcm = get_fast_modinfo($forum->course, 0)->instances['forum'][$forum->id];

        // This require must be here, see mod/forum/discuss.php.
        require_once($CFG->dirroot . "/mod/forum/externallib.php");
        $modcontext = context_module::instance($forumcm->id);
        // Check they have the view forum capability.
        require_capability('mod/forum:viewdiscussion', $modcontext, null, true, 'noviewdiscussionspermission', 'forum');

        // We create the cache on the fly as we want to be able to purge the cache on a forum level.
        // We also use an application cache as we cannot purge everyoens session cache.
        $cache = self::make_forum_discussion_cache($forum->id);
        $pagecachekey = '';
        if ($page !== -1) {
            $pagecachekey = $page;
        }
        // We cache on a per user basis as users can have different permissions for forum posts.
        // We cant use a session cache as there is no way to clear everyones session cache on an invalidation event.
        $cachekey = $USER->id . "_" . $pagecachekey . "_" . $perpage . '_' . $sortby . '_' . $sortdirection;
        $discussions = $cache->get($cachekey);

        if (!empty($discussions)) {
            return $discussions;
        }

        $rawdiscussions = mod_forum_external::get_forum_discussions_paginated($forumcm->instance, $sortby, $sortdirection, $page,
                                                                              $perpage);
        $discussions = $rawdiscussions['discussions'];
        $strftimerecent = get_string('strftimerecent');

        // Cache for this run any 'super' capabilities that are given at forum or site level.
        $usercapabilities = array();

        $usercapabilities['mod/forum:viewdiscussion'] = has_capability('mod/forum:viewdiscussion', $forumcm->context);
        $usercapabilities['moodle/site:viewfullnames'] = has_capability('moodle/site:viewfullnames', $forumcm->context);
        $usercapabilities['mod/forum:editanypost'] = has_capability('mod/forum:editanypost', $forumcm->context);
        // $forumcm->cache->caps['mod/forum:splitdiscussions'] = has_capability('mod/forum:splitdiscussions', $forumcm->context);
        $usercapabilities['mod/forum:deleteownpost'] = has_capability('mod/forum:deleteownpost', $forumcm->context);
        $usercapabilities['mod/forum:deleteanypost'] = has_capability('mod/forum:deleteanypost', $forumcm->context);
        $usercapabilities['mod/forum:viewanyrating'] = has_capability('mod/forum:viewanyrating', $forumcm->context);
        // $forumcm->cache->caps['mod/forum:exportpost']       = has_capability('mod/forum:exportpost', $forumcm->context);
        // $forumcm->cache->caps['mod/forum:exportownpost']    = has_capability('mod/forum:exportownpost', $forumcm->context);
        $usercapabilities['mod/forum:pindiscussions'] = has_capability('mod/forum:pindiscussions', $forumcm->context);

        foreach ($discussions as &$discussion) {
            $discussion->formatted_discussion_created_date = userdate($discussion->created, $strftimerecent);
            $discussion->formatted_discussion_modified_date = userdate($discussion->timemodified, $strftimerecent);

            $userprofileurl = new \moodle_url('/user/view.php', array('id' => $discussion->userid));
            $discussion->discussionuserprofileurl = $userprofileurl->out();

            $postsraw = mod_forum_external::get_forum_discussion_posts($discussion->discussion, 'created', 'ASC');
            $posts = $postsraw['posts'];
            $discussion->canreply = forum_user_can_post($forum, $discussion, $USER, $forumcm, $forumcm->course, $modcontext);
            // Remove discussion pinning this round.
            // $discussion->canpindiscussion =  $usercapabilities['mod/forum:pindiscussions'];
            $discussion->canpindiscussion = false;

            // ajax load, passing in permission to moderate discussion
            $discussion->canmanageforum = has_capability('mod/forum:pindiscussions', $forumcm->context) || has_capability('mod/forum:editanypost', $forumcm->context);

            if ($discussion->pinned == false) {
                $discussion->pinned = false; // Force php falsey value to be false because mustache js treats string '0' as falsey.
            }

            foreach ($posts as &$post) {
                $ownpost = ($USER->id == $post->userid);
                $age = time() - $post->created;

                $post->formatted_created_date = userdate($post->created, $strftimerecent);
                $post->formatted_modified_date = userdate($post->modified, $strftimerecent);

                if ($post->parent == false) {
                    $post->parent = false; // Force php falsey value to be false because mustache js is not properly treating '0' as falsey, javascript does not
                }

                if(isset($post->attachments) && $post->attachments !== '') {
                    foreach ($post->attachments as &$attach) {
                        /*
                            This is dodgy string replace beacuse the replace because mod_forum_external::get_forum_discussion_posts
                            explicity calls moodle_url::make_webservice_pluginfile_url which does not use external_settings:: helper
                            so we can not nicely overrride the '/webservice/' url re-write.
                        */
                        $attach['fileurl'] = str_replace('/webservice/', '/', $attach['fileurl']);
                        // MDL-52469 Mustache pix helper doesnt take mustache variables yet.
                        $attach['icon'] = file_file_icon($attach);
                        $attach['pix_url_html'] = $OUTPUT->pix_icon(file_file_icon($attach), $attach['mimetype'], 'moodle', array('class' => 'icon'));
                    }
                    $post->hasattachments = true;
                } else {
                    // Set to false so mustache doesnt inherit attachments from parent contexts.
                   $post->attachments = array();
                   $post->hasattachments = false;
                }

                $post->candeletepost = (
                    ($ownpost
                        && ($age < $CFG->maxeditingtime)
                        && $usercapabilities['mod/forum:deleteownpost']
                        && empty($post->children) // if there are no replies
                    )
                    || $usercapabilities['mod/forum:deleteanypost']
                );

                // Remove edit capability this round.
                // $post->caneditpost = (($ownpost && $age < $CFG->maxeditingtime) || $usercapabilities['mod/forum:editanypost']);
                $post->caneditpost = false;

                $modereateforumurl = new \moodle_url('/mod/forum/discuss.php', array('d'=>$post->discussion));
                $post->modereateforumurl = $modereateforumurl->out();

                $userprofileurl = new \moodle_url('/user/view.php', array('id'=>$post->userid));
                $post->userprofileurl = $userprofileurl->out();
            }
            $discussion->posts = api::get_child_posts_recursive($posts, 0);
        }
        $cache->set($cachekey, $discussions);
        return $discussions;
    }
    /**
     * Delete a forum post
     * Basically copied from the delete section of post.php, with the redirects removed.
     *
     * @param int $postid the post id to delete
     *
     */
    public static function delete_forum_post($postid) {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . "/mod/forum/lib.php");
        if (! $post = forum_get_post_full($postid)) {
            print_error('invalidpostid', 'forum');
        }
        if (! $discussion = $DB->get_record("forum_discussions", array("id" => $post->discussion))) {
            print_error('notpartofdiscussion', 'forum');
        }
        if (! $forum = $DB->get_record("forum", array("id" => $discussion->forum))) {
            print_error('invalidforumid', 'forum');
        }
        if (!$cm = get_coursemodule_from_instance("forum", $forum->id, $forum->course)) {
            print_error('invalidcoursemodule');
        }
        if (!$course = $DB->get_record('course', array('id' => $forum->course))) {
            print_error('invalidcourseid');
        }

        require_login($course, false, $cm);
        $modcontext = context_module::instance($cm->id);

        if ( !(($post->userid == $USER->id && has_capability('mod/forum:deleteownpost', $modcontext))
               || has_capability('mod/forum:deleteanypost', $modcontext)) ) {
            print_error('cannotdeletepost', 'forum');
        }

        $replycount = forum_count_replies($post);

        //check user capability to delete post.
        $timepassed = time() - $post->created;
        if (($timepassed > $CFG->maxeditingtime) && !has_capability('mod/forum:deleteanypost', $modcontext)) {
            print_error("cannotdeletepost", "forum");
        }

        if ($post->totalscore) {
            print_error(get_string('couldnotdeleteratings', 'rating'));

        }

        if ($replycount && !has_capability('mod/forum:deleteanypost', $modcontext)) {
            print_error("couldnotdeletereplies", "forum");

        }

        if (! $post->parent) {  // post is a discussion topic as well, so delete discussion
            if ($forum->type == 'single') {
                print_error("Sorry, but you are not allowed to delete that discussion!");
            }
            forum_delete_discussion($discussion, false, $course, $cm, $forum);

            $params = array(
                'objectid' => $discussion->id,
                'context' => $modcontext,
                'other' => array(
                    'forumid' => $forum->id,
                )
            );

            $event = \mod_forum\event\discussion_deleted::create($params);
            $event->add_record_snapshot('forum_discussions', $discussion);
            $event->trigger();

        } else if (forum_delete_post($post, has_capability('mod/forum:deleteanypost', $modcontext),
                                     $course, $cm, $forum)) {
            return true;
        } else {
            print_error('errorwhiledelete', 'forum');
        }

        return true;
    }
    /**
     * Create custom adhoc cache, can't use define as we need a cache for each forum id.
     */
    public static function make_forum_discussion_cache($forumid) {
        return cache::make_from_params(cache_store::MODE_APPLICATION, 'block_oua_forum_recent_posts',
                                'user_forum_discussion_with_post_cache_' . $forumid, array(),
                                array('simplekeys' => true));
    }
    /**
     * Recursive helper funciton
     * @param $disucssion
     * @param $fulllist
     */
    private static function get_child_posts_recursive($posts, $parentid) {
        $children = array();
        foreach ($posts as $post) {
            if ($post->parent == $parentid) {
                $post->posts = self::get_child_posts_recursive($posts, $post->id);
                $children[] = $post;
            }
        }
        return empty($children) ? array() : $children;
    }

    /**
     * Extract a single parameter that has been given as a key/value pari via web service.
     *
     * @param string $name Name of the parameter
     * @param array $paramaters Array of key value pairs
     * @param null $default default value of parameter
     * @return array
     * @throws \coding_exception
     */
    public static function extract_clean_parameter($name, $paramaters, $default = null) {
        $value = $default;
        foreach ($paramaters as $option) {
            $tname = trim($option['name']);
            switch ($tname) {
                case $name:
                    $value = clean_param($option['value'], PARAM_INT);
                    break;
            }
        }
        return $value;
    }

    /**
     * Deletes files from a users draft file area
     * Used to re-use a draft file area after an ajax upload
     *
     * @param $itemid
     * @param null $usercontextid
     */
    public static function clean_user_draft_area($itemid, $usercontextid = null) {
        global $USER;
        if ($usercontextid == null) {
            $usercontext = context_user::instance($USER->id);
            $usercontextid = $usercontext->id;
        }
        if ($itemid != 0) {
            $fs = get_file_storage();
            $fs->delete_area_files($usercontextid, 'user', 'draft', $itemid);
        }
    }
}
