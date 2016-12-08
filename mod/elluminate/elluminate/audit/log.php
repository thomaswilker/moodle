<?php
class Elluminate_Audit_Log{
   const MOD_NAME = 'elluminate';
    
   public static function log($auditEvent, $url, $pageSession = null, $courseModule = null){
      global $USER;
      
      if ($pageSession == null){
         $course = 0;
         $info = '';
      }else{
         $course = $pageSession->course;
         $info = "Session ID: " . $pageSession->id . " Name: " . $pageSession->sessionname;
      }

      if ($courseModule == null){
         $cmid = 0;
      }else{
         $cmid = $courseModule->id;
      }
      $contextid = elluminate_getContextInstance(CONTEXT_SYSTEM)->id;
      
      $logger = Elluminate_Logger_Factory::getLogger("loadmeeting");
      $logger->info("Audit Log Event [" . $auditEvent . "], URL [" . $url . "]"); 
      
      // This code uses MOODLE Event_2 handling, which is not supported before MOODLE 2.6.0.
      // This code replaces using add_to_log(), which is deprecated in MOODLE 2.7.0.
      
      $event = mod_elluminate\event\loggedaudit_event::create(array(
         'contextid' => $contextid,
         'userid' => $USER->id,
      	 // 'other' passes our own properties that are not part of the standard base event set.
         'other' => array('course' => $course, 'modulename' => self::MOD_NAME, 'event' => $auditEvent,
                        'url' => $url, 'info' => $info, 'cmid' => $cmid)
         ));
  
      $event->trigger();
   }
}
