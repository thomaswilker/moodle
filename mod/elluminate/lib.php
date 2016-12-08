<?php

/**
 * Blackboard Collaborate Module
 *
 * Allows Blackboard Collaborate meetings to be created and managed on an
 * Blackboard Collaborate server via a Moodle activity module.
 *
 * @author Danny Wieser Major Refactor for Module version 2.2
 */

require_once($CFG->dirroot . '/mod/elluminate/include/container.php');
require_once($CFG->libdir . '/gradelib.php');

const ERROR_DELAY_TIME = 5;

// elluminate_getContextInstance replaces deprecated get_context_instance(), by using context_xxxx::instance() instead.
function elluminate_getContextInstance($contextlevel, $instance = 0, $strictness = IGNORE_MISSING) {

    $instances = (array)$instance;
    $contexts = array();

    $classname = context_helper::get_class_for_level($contextlevel);

    // we do not load multiple contexts any more, PAGE should be responsible for any preloading
    foreach ($instances as $inst) {
        $contexts[$inst] = $classname::instance($inst, $strictness);
    }

    if (is_array($instance)) {
        return $contexts;
    } else {
        return $contexts[$instance];
    }
}

/**
 * Execute tasks required on install of module
 *
 * MOODLE REQUIRED
 *
 * @return boolean
 */
function elluminate_install() {
   $result = true;
   $timenow = time();
   $sysctx = elluminate_getContextInstance(CONTEXT_SYSTEM);
   return $result;
}

/**
 * Indicate Module Support for specific Moodle Features
 *
 * MOODLE REQUIRED
 *
 * @param unknown_type $feature
 * @return boolean|NULL
 */
function elluminate_supports($feature) {
   switch ($feature) {
      case FEATURE_GROUPINGS:
         return true;
      case FEATURE_GROUPS:
         return true;
      case FEATURE_GROUPMEMBERSONLY:
         return true;
      case FEATURE_BACKUP_MOODLE2:
         return true;
      case FEATURE_MOD_INTRO:
         return false;
// OUA Custom: add completion settings.
      case FEATURE_COMPLETION_TRACKS_VIEWS:
         return true;
      case FEATURE_COMPLETION_HAS_RULES:
         return true;
// End OUA Custom.
      default:
         return null;
   }
}
// OUA Custom: add completion settings.
/**
 * Obtains the automatic completion state for this lesson based on any conditions
 * in elluminate settings.
 *
 * @param object $course Course
 * @param object $cm course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function elluminate_get_completion_state($course, $cm, $userid, $type) {
   global $CFG, $DB;

   // Get lesson details.
   $session = $DB->get_record('elluminate', array('id' => $cm->instance), '*', MUST_EXIST);

   $result = $type; // Default return value.
   // If completion option is enabled, evaluate it and return true/false.
   if ($session->customcompletionstate == 1) { // completionsetting
      $value = time() >= $session->timestart;
      if ($type == COMPLETION_AND) {
         $result = $result && $value;
      } else {
         $result = $result || $value;
      }
   }

   return $result;
}
// End OUA Custom.

/**
 * Run the Collaborate Module Cron Job
 *
 * MOODLE REQUIRED
 *
 */
function elluminate_cron() {
   try {
      global $CFG, $ELLUMINATE_CONTAINER;
      require_once($CFG->dirroot . '/mod/elluminate/include/cron-includes.php');
      $cronRunner = $ELLUMINATE_CONTAINER['cronRunner'];
      $cronRunner->loadActions();
      echo $cronRunner->executeCronActions();
   } catch (Exception $e) {
      echo "Elluminate Cron Failure: [" . $e->getMessage() . "] - see log for more details\n";
      elluminate_error($e);
   }
}

/**
 * Add a new Collaborate Session Instance to the DB and SAS
 *
 * All errors here are handled by redirects that will end execution and direct
 * the user to another page.
 *
 * MOODLE REQUIRED
 *
 * @param mod form stdclass $modForm
 * @param string $facilitatorid
 */
function elluminate_add_instance($modForm, $facilitatorid = false) {
   global $ELLUMINATE_CONTAINER, $USER;

   try {
      $addController = $ELLUMINATE_CONTAINER['sessionAddController'];
      $newSession = $addController->createSession(
         $modForm,
         elluminate_getContextInstance(CONTEXT_MODULE, $modForm->coursemodule),
         $USER->id);
      // OUA Custom: add completion settings.
      // Force update custom field:
      global $DB;
      $customcompletionstate = isset($newSession->customcompletionstate) ? 1 : 0;
      $DB->set_field("elluminate", 'customcompletionstate', $customcompletionstate, array('id' => $newSession->id));
      // End OUA Custom.
   } catch (Elluminate_Exception $addException) {
      elluminate_add_instance_error_redirect($modForm->course, $modForm->section, $addException);
   } catch (Exception $e) {
      elluminate_error($e);
      print_error(get_string('user_error_processing', 'elluminate'));
   }

   return $newSession->id;
}

/**
 * Redirect to error page in the case of an issue
 * during the add_instance process
 * @param unknown_type $action
 * @param unknown_type $errorSession
 * @param unknown_type $errorMessageKey
 */
function elluminate_add_instance_error_redirect($course, $section, $addException) {
   global $CFG;
   redirect($CFG->wwwroot . '/course/mod.php?id='
      . $course
      . '&amp;sesskey=' . sesskey()
      . '&amp;add=elluminate'
      . '&amp;section=' . $section,
      get_string($addException->getUserMessageKey(), 'elluminate', $addException->getDetails()), ERROR_DELAY_TIME);
}

/**
 * Given a Session ID, permanently delete the session from the Moodle Database
 * and SAS.
 *
 * MOODLE REQUIRED
 *
 * @param String $id
 * @return boolean
 */
function elluminate_delete_instance($id) {
   // OUA CUSTOM:
   global $CFG;
   // Don't delete ellumniate server instances unless specifically set.
   if (empty($CFG->elluminate_delete_from_server) || $CFG->elluminate_delete_from_server != true ) {
    return true;
   }
   // END OUA CUSTOM.
   global $ELLUMINATE_CONTAINER;
   $loader = $ELLUMINATE_CONTAINER['sessionLoader'];
   $deleteSession = $loader->getSessionById($id);

   $cm = get_coursemodule_from_instance('elluminate', $deleteSession->id, $deleteSession->course);

   elluminate_debug("Delete Session Start [" . $deleteSession->id . "]");

   $context = elluminate_getContextInstance(CONTEXT_MODULE, $cm->id);
   $permissions = $ELLUMINATE_CONTAINER['sessionPermissions'];
   $permissions->setContext($context);

   if (!$permissions->doesUserHaveDeletePermissionsForSession()) {
      elluminate_error("Delete Session Failure: sessiondeleteparticipanterror: " . $id);
      return false;
   }

   //Delete session and log error if not successful
   //Note that all we can do here is log an error.  This is an ajax call from moodle and
   //there is no way to present an error to the client.
   $deleteSession->deleteSession();

   //If we make it here, we are successful
   elluminate_debug("Delete Session Success for : " . $id);
   return true;
}

/**
 * Update a Collaborate Session
 *
 * All errors here are handled by redirects that will end execution and direct
 * the user to another page.
 *
 * MOODLE REQUIRED
 *
 * @param StdClass from Mod Form $modFormSession
 * @return boolean
 */
function elluminate_update_instance($modFormSession) {
   global $ELLUMINATE_CONTAINER;
   $courseModule = $modFormSession->coursemodule;

   try {
// OUA Custom: add completion settings.
      // Force update custom field:
      global $DB;
      $customcompletionstate = isset($modFormSession->customcompletionstate) ? 1 : 0;
      $DB->set_field("elluminate", 'customcompletionstate', $customcompletionstate, array('id' => $modFormSession->instance));
// End OUA Custom.
      //Session With Form Updates
      $updateController = $ELLUMINATE_CONTAINER['sessionUpdateController'];
      $updateController->updateSession($modFormSession, elluminate_getContextInstance(CONTEXT_COURSE, $modFormSession->course));
   } catch (Elluminate_Exception $updateException) {
      elluminate_update_instance_error_redirect($courseModule, $updateException);
   } catch (Exception $e) {
      print_error(get_string('user_error_processing', 'elluminate'));
   }

   //Success if we make it to this point
   return true;
}

/*
* Redirect to error page in the case of an issue
* during the add_instance process
* @param Course Context
* @param Elluminate_Exception $errorMessage
*/
function elluminate_update_instance_error_redirect($courseModule, $updateException) {
   global $CFG;
   redirect($CFG->wwwroot . '/course/modedit.php?update='
      . $courseModule
      . '&amp;return=1',
      get_string($updateException->getUserMessageKey(), 'elluminate', $updateException->getDetails()), ERROR_DELAY_TIME);
}

/**
 * MOODLE REQUIRED FUNCTION
 *
 * Called by gradelib.php to "Force full update of module grades in central gradebook".
 * The scenario that I have confirmed this occurs in is the locking/unlocking of a grade item
 * in the grade book (edit grades).  After a grade entry is unlocked, this function is called
 * to force a refresh/synch with the module.
 *
 * @param object $elluminate null means all elluminates
 * @param int $userid specific user only, 0 mean all
 * @see grade_update_mod_grades in moodle lib/gradelib.php
 */
function elluminate_update_grades($session = null, $userid = 0, $nullifnone = true) {
   global $ELLUMINATE_CONTAINER;
   elluminate_debug("elluminate_update_grades session id: " . $session->id . " user id: " . $userid);
   try {
      $loader = $ELLUMINATE_CONTAINER['sessionLoader'];
      $updateSession = $loader->getSessionById($session->id);
      if ($userid != 0) {
         $updateSession->synchSessionGradeBookForUser($userid);
      } else {
         $updateSession->synchSessionGradeBookForAllUsers();
      }
   } catch (Elluminate_Exception $e) {
      print_error(get_string('deletesessionloaderror', 'elluminate') . get_string($e->getUserMessage(), 'elluminate'));
   } catch (Exception $e) {
      print_error(get_string('user_error_processing', 'elluminate'));
   }
}

/**
 * Create Grading for a Blackboard Collaborate Session
 *
 * This is called by moodle when a grade item is changed from locked to unlocked to
 * refresh the gradebook entry for the session.
 *
 * MOODLE REQUIRED FUNCTION
 *
 * @param Elluminate_Session or StdClass
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function elluminate_grade_item_update($session, $grades = NULL) {
   global $ELLUMINATE_CONTAINER;
   elluminate_debug("elluminate_grade_item_update" . $session->id);
   try {
      $loader = $ELLUMINATE_CONTAINER['sessionLoader'];
      $updateSession = $loader->getSessionById($session->id);
      $updateSession->synchSessionGradeBook();
   } catch (Elluminate_Exception $e) {
      print_error(get_string('deletesessionloaderror', 'elluminate') . get_string($e->getUserMessage(), 'elluminate'));
   } catch (Exception $e) {
      print_error(get_string('user_error_processing', 'elluminate'));
   }
}

/**
 * MOODLE REQUIRED FUNCTION: Called to generate user outline report.
 *
 * @param unknown_type $course
 * @param unknown_type $user
 * @param unknown_type $mod
 * @param unknown_type $elluminate
 * @return stdClass|NULL
 */
function elluminate_user_outline($course, $user, $mod, $elluminate) {
   global $ELLUMINATE_CONTAINER;
   try {
      $loader = $ELLUMINATE_CONTAINER['sessionLoader'];
      $session = $loader->getSessionById($elluminate->id);

      $gradeReport = $ELLUMINATE_CONTAINER['gradesReport'];
      return $gradeReport->buildUserOutlineReportObject($session, $user->id);
   } catch (Elluminate_Exception $e) {
      print_error(get_string('deletesessionloaderror', 'elluminate') . get_string($e->getUserMessage(), 'elluminate'));
   } catch (Exception $e) {
      print_error(get_string('user_error_processing', 'elluminate'));
   }
}

/**
 * MOODLE REQUIRED FUNCTION
 *
 * Builds User Complete Report
 *
 * @param unknown_type $course
 * @param unknown_type $user
 * @param unknown_type $mod
 * @param unknown_type $elluminate
 */
function elluminate_user_complete($course, $user, $mod, $elluminate) {
   global $ELLUMINATE_CONTAINER;
   try {
      $gradeReport = $ELLUMINATE_CONTAINER['gradesReport'];
      $gradeReport->completeReport($elluminate->id, $user->id);
   } catch (Elluminate_Exception $e) {
      print_error(get_string('deletesessionloaderror', 'elluminate') . get_string($e->getUserMessage(), 'elluminate'));
   } catch (Exception $e) {
      print_error(get_string('user_error_processing', 'elluminate'));
   }
}

/**
 * MOODLE REQUIRED FUNCTION
 * Outputs Recent Activity Report - this is a block report typically shown in the right hand column
 * of the course view page.
 *
 * Moodle automatically outputs a section for this report relating to course updates.  This function
 * adds to that a "recently viewed sessions"
 *
 * @param unknown_type $course
 * @param unknown_type $isteacher
 * @param unknown_type $timestart
 * @return boolean
 *
 * This looks like it belongs in blocks. Are we changing that code?
 */
function elluminate_print_recent_activity($course, $isteacher, $timestart) {
   global $ELLUMINATE_CONTAINER;
   $report = $ELLUMINATE_CONTAINER['auditReport'];
   echo $report->getCourseLastViewedSessions($course->id, $timestart);
}

/**
 * MOODLE REQUIRED
 * Checks if scale is being used by any instance of elluminate
 *
 * This is used to find out if scale used anywhere.
 *
 * @param $scaleid int
 * @return boolean True if the scale is used by any elluminate
 */
function elluminate_scale_used_anywhere($scaleid) {
   global $ELLUMINATE_CONTAINER;
   try {
      $gradesDAO = $ELLUMINATE_CONTAINER['gradesDAO'];

      if ($scaleid and $gradesDAO->getScaleUsedAnywhere($scaleid)) {
         return true;
      } else {
         return false;
      }
   } catch (Elluminate_Exception $e) {
      print_error(get_string('sessionloaderror', 'elluminate') . get_string($e->getUserMessage(), 'elluminate'));
   } catch (Exception $e) {
      print_error(get_string('sessionloaderror', 'elluminate') . get_string('user_error_processing', 'elluminate'));
   }
}

/********** Common Helper Methods ***************/


/**
 * Common method for sending a debug-level message to the logs
 * HELPER
 * @param string $message
 */
function elluminate_debug($message) {
   $logger = Elluminate_Logger_Factory::getLogger("lib");
   $logger->debug($message);
}

/**
 * Common method for sending a error-level message to the logs
 * HELPER
 * @param string $message
 */
function elluminate_error($message) {
   $logger = Elluminate_Logger_Factory::getLogger("lib");
   $logger->error($message);
}

/**
 * Get the current user ID from the global $USER variable
 * HELPER
 * @return User ID
 */
function elluminate_get_moodle_user() {
   global $USER;
   return $USER->id;
}
