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
 * Strings for component 'theme_ouaclean', language 'en'
 *
 * @package   theme_ouaclean
 * @copyright 2015 Open Universities Australia
 * @author    Ben Kelada (ben.kelada@open.edu.au)
 */

$string['choosereadme'] = '
Customisable Theme based on Moodle Clean Theme/Bootstrap from OUA';

$string['configtitle'] = 'OUA Clean theme';

$string['customcss'] = 'Custom CSS';
$string['customcssdesc'] = 'Whatever CSS rules you add to this textarea will be reflected in every page, making for easier customization of this theme.';

$string['footnote'] = 'Footnote';
$string['footnotedesc'] = 'Whatever you add to this textarea will be displayed in the footer throughout your Moodle site.';

$string['customheadermenu'] = 'Custom Header Menu';
$string['customheadermenudesc'] = 'Whatever you add to this textarea will be used as the menu in the header/banner area';

$string['customfooter'] = 'Custom Footer';
$string['customfooterdesc'] = 'Whatever you add to this textarea will be displayed in the custom footer area throughout the site';

$string['logo'] = 'Logo';
$string['logodesc'] = 'The logo is displayed in the header.<br /> If the height of your logo is more than 75px add div.logo {height: 100px;} to the Custom CSS box below, amending accordingly if the height is other than 100px.';


$string['appleicon'] = 'Apple Touch Icon';
$string['appleicondesc'] = 'Icon used to display as bookmark on iOS homescreens.';


$string['favicon'] = 'Custom Fav Icon';
$string['favicondesc'] = 'Custom Fav Icon';

$string['welcome'] = 'Welcome';
$string['messages'] = 'Messages';
$string['notifications'] = 'Notifications';
$string['keydates'] = 'Key Dates';

$string['activities'] = 'Activities';
$string['resources'] = 'Resources';
$string['students'] = 'Students';

$string['pluginname'] = 'Customisable Theme by OUA ';

$string['block-region-text'] = 'Block Region: ';
$string['region-side-post'] = 'Right';
$string['region-side-pre'] = 'Left';
$string['region-side-a'] = 'Pre Course Header'; /* Names are limited to 16 Chars. */
$string['region-side-b'] = 'Post Course Header';
$string['region-side-c'] = 'Post Course Content';
$string['region-side-d'] = 'Pre Footer';
$string['region-side-tabhead'] = 'Nav Head';
$string['region-side-tabfoot'] = 'Nav Footer';
$string['region-side-tabx'] = 'Tab 0 (mobile view only)';
$string['region-side-taba'] = 'Tab 1';
$string['region-side-tabb'] = 'Tab 2';
$string['region-side-tabc'] = 'Tab 3';

/* assignment holding page renderer */

$string['answertext'] = 'Your answer:';
$string['answerfile'] = 'File(s) uploaded:';
$string['tutor_response'] = 'Tutor response';
$string['by'] = 'by ';
$string['edited'] = 'Edited: ';


$string['assess:errorgettinggrade'] = 'Error retrieving grade.';

$string['assess:submissionstatus'] = 'Submission status:';
$string['assess:submissionstatusshort'] = 'Status:';

$string['assess:submissionstatus_notyetopen'] = 'Not yet open';
$string['assess:submissionstatus_'] = 'Not submitted';
$string['assess:submissionstatus_new'] = 'Not submitted';
$string['assess:submissionstatus_draft'] = 'Draft (not submitted)';
$string['assess:submissionstatus_inprogress'] = 'In Progress';

$string['assess:submissionstatus_submitted'] = 'Submitted';

$string['assess:submissionstatus_graded'] = 'Graded';
$string['assess:submissionstatus_marked'] = 'Graded';

$string['assess:submissionstatus_reopened'] = 'Reopened';

$string['assess:submissionopendate'] = 'Open date:';
$string['assess:submissionopendateshort'] = 'Opens:';

$string['assess:submissionopenin'] = 'Assessment opens in:';
$string['assess:submissionopeninshort'] = 'Timer:';


$string['assess:submitted'] = 'Submitted:';
$string['assess:submitteddateshort'] = 'Date:';
$string['assess:timeremaining'] = 'Time remaining:';
// Due date from assignment
$string['assess:submissionduedate'] = 'Due date:';
$string['assess:submissionduedateshort'] = 'Due:';

$string['assess:submissionoverduedateshort'] = 'Overdue';
$string['assess:submissionoverdueby'] = 'Assessment overdue by:';
$string['assess:submissionoverduebyshort'] = 'Overdue:';

$string['assess:submissionduein'] = 'Assessment due in:';
$string['assess:submissiondueinshort'] = 'Timer';

$string['assess:gradingstatus'] = 'Grading status:';
$string['assess:gradingstatushort'] = 'Grade:';

$string['quiz:summaryheadernum'] = '#';
$string['quiz:summaryheaderquestion'] = 'Question';
$string['quiz:summaryheaderstatus'] = 'Status';
$string['quiz:summaryheadermark'] = 'Mark';


$string['day'] = 'Day';
$string['hour'] = 'Hour';
$string['min'] = 'Min';
$string['sec'] = 'Sec';
$string['days'] = 'Days';
$string['hours'] = 'Hours';
$string['mins'] = 'Mins';
$string['secs'] = 'Secs';

// Assignment submission
$string['confirm'] = 'Please Confirm';
$string['oncesubmit'] = 'Once submitted you\'ll no longer be able to make any changes. Okay?';
$string['goahead'] = 'Yes, go ahead and submit my assignment';
$string['or'] = 'Or';
$string['dontsubmit'] = 'Don\'t submit yet I might want to make a change.';
$string['maxattemptsallowed'] = '({$a} attempts allowed)';
$string['selectattempt'] = 'Select Attempt Option';
$string['newattempt'] = 'Start Fresh Submission';
$string['reattempt'] = 'Modify Last Submission';

$string['less_btn_link_primary'] = 'Colour for Primary Links/Buttons';
$string['less_btn_link_primary_desc'] = 'This is the primary colour for links, icons and buttons based on the brand (default blue)';

$string['less_btn_link_hover'] = 'Colour on hover for Links/Buttons';
$string['less_btn_link_hover_desc'] = 'This is the hover colour for primary Links/Buttons (default orange)';

$string['less_link_light_default'] = 'Colour for Secondary Links/Buttons';
$string['less_link_light_default_desc'] = 'This is the secondary colour for links and buttons e.g Header Links (default white)';

$string['less_link_light_hover'] = 'Colour on hover for Secondary Links/Buttons';
$string['less_link_light_hover_desc'] = 'This is the hover colour for secondary links/buttons (default greyish blue)';

$string['less_link_hover'] = 'Colour on hover for Generic links';
$string['less_link_hover_desc'] = 'This is the hover colour for generic links with underline)';

$string['less_section_light_bg'] = 'Colour for Section Background (Light)';
$string['less_section_light_bg_desc'] = 'This is the background colour for tabs, section headers and subtle links (default light grey)';

$string['less_section_dark_bg'] = 'Colour for Section Background (Dark)';
$string['less_section_dark_bg_desc'] = 'This is the background colour for darker sections e.g footer and non-link icons (default dark blue)';

$string['less_page_background'] = 'Colour for Page Background';
$string['less_page_background_desc'] = 'This is the main page background colour (default light grey)';

$string['less_header'] = 'Colour for Main Header';
$string['less_header_desc'] = 'This is the background colour of the main header (default dark blue)';

$string['less_header_border'] = 'Colour for Header Bottom Border';
$string['less_header_border_desc'] = 'This is the colour for line bordering the header (default darker blue)';

$string['less_header_border'] = 'Colour for Header Bottom Border';
$string['less_header_border_desc'] = 'This is the colour for line bordering the header (default darker blue)';

$string['less_keyline'] = 'Colour for Section Borders';
$string['less_keyline_desc'] = 'This is the colour on around sections and tabs (default middle grey)';

$string['less_bodycopy'] = 'Colour for Body Copy';
$string['less_bodycopy_desc'] = 'This is the colour for default body text (default black)';

$string['less_progressbar'] = 'Colour for Progress Bar';
$string['less_progressbar_desc'] = 'This is the colour on the progress bar (default light green)';

$string['less_extra'] = 'Additional Less / CSS';
$string['less_extra_desc'] = 'Any additional less required, mixins supported<span class="alert">INVALID LESS WILL BREAK THE THEME!</span>';

