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

class message extends \stdClass {
    public $width = 1; // Hardcoded as we are not implementing this yet.
    public $priority = 2; // This is for the yellow background, hardcoded as this is the only one currently being used.
    public $dismissible = 1; // All should be dismissable
    public $headingicon = 1; // This is for the icon, currently always using the megaphone.
    public $targetinterface = 0; // This means all, allows for more targeted ones later, if desired.
    public $enddate = 0; // We may give message expiry dates at a later stage.

    public $headingtitle;
    public $messagebody;
    public $userid;

    public $lasteditdate;

}