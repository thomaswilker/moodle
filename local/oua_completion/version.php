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
 * Version file for OUA's custom completion.
 *
 * @package    local
 * @subpackage oua_completion
 * @author     Russell Smith <russell.smith@catalyst-au.net>
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version    = 2015071501;   // The format is YYYYMMDDXX.
$plugin->requires   = 2015051101;   // Moodle 2.9 onwards.
$plugin->cron       = 0;
$plugin->component  = 'local_oua_completion';
$plugin->maturity   = MATURITY_ALPHA;
$plugin->release    = '0.5 (Build: 2015071501)';
