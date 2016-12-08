<?php
/*
 * This module is downloaded from blackboard and requires an account
 * http://support.blackboardcollaborate.com/ics/support/default.asp?deptID=8336&task=knowledge&questionID=271
 * or
 * https://blackboard.secure.force.com/login
 * The contents of this directory was unzipped from moodle_315-3-for_bcwc
 * mod/elluminate
 * blocks/elluminate
 * It needed some customisations to remove deprecated elements to avoid warnings
 */
$plugin->requires = 2014051200;
$plugin->version  = 2014122200;  // The current module version (Date: YYYYMMDDxx)
$plugin->release  = '3.1.5-3'; 	 // Human Readable version number
$plugin->maturity = MATURITY_STABLE;
$plugin->component = 'block_elluminate';

$plugin->dependencies = array(
   'mod_elluminate' => '2014122200'
);
