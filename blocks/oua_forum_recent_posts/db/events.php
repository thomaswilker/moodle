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
 * Event observer.
 *
 * @package   block_oua_forum_recent_posts
 * @category  event
 * @copyright 2015 Ben Kelada (ben.kelada@open.edu.au)
 */

defined('MOODLE_INTERNAL') || die();

$observers = array (
    array (
        'eventname' => '\core\event\role_assigned',
        'callback'  => 'block_oua_forum_recent_posts_observer::invalidate_cache',
        'internal'  => true,
        'priority'  => 1000,
    ),
    array(
        'eventname' => '\mod_forum\event\discussion_created',
        'callback' => 'block_oua_forum_recent_posts_observer::forum_cache_clear'
    ),
    array(
        'eventname' => '\mod_forum\event\discussion_deleted',
        'callback' => 'block_oua_forum_recent_posts_observer::forum_cache_clear'
    ),
    array(
        'eventname' => '\mod_forum\event\discussion_moved',
        'callback' => 'block_oua_forum_recent_posts_observer::forum_cache_clear'
    ),
   array(
        'eventname' => '\mod_forum\event\discussion_pinned',
        'callback' => 'block_oua_forum_recent_posts_observer::forum_cache_clear'
    ),
   array(
        'eventname' => '\mod_forum\event\discussion_unpinned',
        'callback' => 'block_oua_forum_recent_posts_observer::forum_cache_clear'
    ),
   array(
        'eventname' => '\mod_forum\event\post_created',
        'callback' => 'block_oua_forum_recent_posts_observer::forum_cache_clear'
    ),
   array(
        'eventname' => '\mod_forum\event\post_deleted',
        'callback' => 'block_oua_forum_recent_posts_observer::forum_cache_clear'
    ),
   array(
        'eventname' => '\mod_forum\event\post_updated',
        'callback' => 'block_oua_forum_recent_posts_observer::forum_cache_clear'
    ),
);
