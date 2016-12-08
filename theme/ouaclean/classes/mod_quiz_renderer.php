<?php
use \theme_ouaclean\output\countdowntimer\renderable as countdowntimer_renderable;
use \theme_ouaclean\output\awesomebar\renderable as awesomebar_renderable;

/**
 *  Override for the quiz status display screen
 */
class theme_ouaclean_mod_quiz_renderer extends mod_quiz_renderer {

    const SUBMISSION_STATUS_NOT_YET_OPEN = 'notyetopen';
    const SUBMISSION_STATUS_NOT_ATTEMPTED = 'new';
    const SUBMISSION_STATUS_IN_PROGRESS = 'inprogress'; // Custom "quiz in progress" state.
    const SUBMISSION_STATUS_HAS_SUBMITTED = 'submitted';
    const GRADING_STATUS_NO_FINISHED_ATTEMPT = 'incomplete';
    const GRADING_STATUS_NOT_GRADED = 'notgraded';
    const GRADING_STATUS_GRADED = 'graded';
    const GRADING_STATUS_FINAL_GRADE_AWARDED = 'final';
    const NOT_SATISFACTORY = 3;
    const SATISFACTORY = 4;

    /*
     * View Page
     */
    /**
     * Generates the view page
     *
     * @param int                  $course The id of the course
     * @param array                $quiz Array conting quiz data
     * @param int                  $cm Course Module ID
     * @param int                  $context The page context ID
     * @param array                $infomessages information about this quiz
     * @param mod_quiz_view_object $viewobj
     * @param string               $buttontext text for the start/continue attempt button, if
     *      it should be shown.
     * @param array                $infomessages further information about why the student cannot
     *      attempt this quiz now, if appicable this quiz
     */

    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        if (0) { // For Development purposes, TODO: put in a per quiz option to use default renderer.
            return parent::view_page($course, $quiz, $cm, $context, $viewobj);
        }
        $viewobj->showbacktocourse = false; // We never show back to course.
        $output = '';
        $output .= $this->heading(format_string($quiz->name), 2, 'block-title pane-title');
        if ($quiz->timeclose != 0 && $quiz->timeclose < time()) {
            $output .= html_writer::tag('div', get_string('quizisclosed', 'mod_quiz'),
                array('class' => 'alert alert-error assess-alert closed'));
        }
        $output .= $this->oua_awesome_bar($quiz, $context, $cm, $viewobj);
        $output .= html_writer::start_tag('div', array('class' => 'assess-wrapper'));
        $output .= $this->view_information_custom($quiz, $cm, $context, $viewobj);
        $output .= $this->view_table($quiz, $context, $viewobj);
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt btn-container');
        $output .= html_writer::end_tag('div');

        return $output;
    }

    public function oua_awesome_bar($quiz, $context, $cm, $viewobj) {

        $duedate = $quiz->timeclose;
        $opendate = $quiz->timeopen;

        $output = '';
        $time = time();
        $submissionstatus = '';
        $gradingstatus = self::GRADING_STATUS_NO_FINISHED_ATTEMPT;
        $stateclasses = '';

        // Have any attempts got a grade?
        $userresult = $this->get_quiz_results_obj($quiz, $context, $cm, $viewobj);
        if (isset($viewobj->mygrade) || isset($userresult['gradesofar']) || isset($userresult['finalgrade'])) {
            $gradingstatus = self::GRADING_STATUS_GRADED;
        }

        if ($time <= $quiz->timeopen) { // Var $quiz->timeopen is always set it is  0 if there is no open time.
            $submissionstatus = self::SUBMISSION_STATUS_NOT_YET_OPEN;
        } else {
            if (!$viewobj->attempts) { // No attempts at all.
                $submissionstatus = self::SUBMISSION_STATUS_NOT_ATTEMPTED;
            } else if ($viewobj->attempts && $gradingstatus == self::GRADING_STATUS_NO_FINISHED_ATTEMPT) {
                $submissionstatus = self::SUBMISSION_STATUS_IN_PROGRESS;
            } else {
                $submissionstatus = self::SUBMISSION_STATUS_HAS_SUBMITTED;
                $stateclasses .= ' submitted';
            }
        }

        // First Box:
        // If not open display not yet open status
        // If open display submission status

        $box1header = get_string('assess:submissionstatus', 'theme_ouaclean');
        $box1headershort = get_string('assess:submissionstatusshort', 'theme_ouaclean');

        if ($submissionstatus == self::SUBMISSION_STATUS_NOT_YET_OPEN) {
            $box1content = get_string('assess:submissionstatus_notyetopen', 'theme_ouaclean');
        } else {
            $box1content = get_string('assess:submissionstatus_' . $submissionstatus, 'theme_ouaclean');
        }

        // Second Box:
        // If not open, Display open date
        // If due Display due date
        // If submitted display submission date

        if ($submissionstatus == self::SUBMISSION_STATUS_NOT_YET_OPEN) {
            $box2header = get_string('assess:submissionopendate', 'theme_ouaclean');
            $box2headershort = get_string('assess:submissionopendateshort', 'theme_ouaclean');
            $box2content = userdate($opendate, '%e %B %Y, %l:%M %p');
        } else if (isset($userresult['finalgrade'])) {
            $submitteddate = $viewobj->lastfinishedattempt->timemodified;
            $box2header = get_string('assess:submitted', 'theme_ouaclean');
            $box2headershort = get_string('assess:submitteddateshort', 'theme_ouaclean');
            $box2content = userdate($submitteddate, '%e %B %Y, %l:%M %p');
        } else {
            // Submission Open, and due soonish
            $box2header = get_string('assess:submissionduedate', 'theme_ouaclean');
            $box2headershort = get_string('assess:submissionduedateshort', 'theme_ouaclean');
            if ($duedate != 0) { // Displays '--' if no due date
                $box2content = userdate($duedate, '%e %B %Y, %l:%M %p');
            } else {
                $box2content = '--';
            }
        }

        // Third Box:
        // If not yet open, count down to open date
        // If due, countdown to submission date
        // If over due, display how long over due by
        // If submitted show grading status
        if ($submissionstatus == self::SUBMISSION_STATUS_NOT_YET_OPEN) {
            $box3header = get_string('assess:submissionopenin', 'theme_ouaclean');
            $box3headershort = get_string('assess:submissionopeninshort', 'theme_ouaclean');

            $countdowntimerrenderable = new countdowntimer_renderable($opendate);
            $countdownrenderer = $this->page->get_renderer('theme_ouaclean', 'countdowntimer');
            $countdowntimer = $countdownrenderer->render($countdowntimerrenderable);
            $box3content = $countdowntimer;

            $stateclasses .= ' notopen';
        } else if (isset($userresult['finalgrade'])) {
            $stateclasses .= ' submissiongraded';
            $box3header = get_string('grade');
            $box3headershort = get_string('grade');
            $box3content = $userresult['finalgrade'];
        } else if ($duedate == 0) { // No Due date
            $box3header = "";
            $box3headershort = "";
            $box3content = "";
        } else if ($duedate < $time) {
            // Overdue - Overwrites the last box to display overdue
            $box3header = get_string('assess:submissionoverdueby', 'theme_ouaclean');
            $box3headershort = get_string('assess:submissionoverduebyshort', 'theme_ouaclean');
            $box3content = format_time($time - $duedate);
            $stateclasses .= ' overdue due1day';
        } else {
            // Submission Open, and due soonish
            $box3header = get_string('assess:timeremaining', 'theme_ouaclean');
            $box3headershort = get_string('assess:submissiondueinshort', 'theme_ouaclean');
            $countdowntimerrenderable = new countdowntimer_renderable($duedate);
            $countdownrenderer = $this->page->get_renderer('theme_ouaclean', 'countdowntimer');
            $countdowntimer = $countdownrenderer->render($countdowntimerrenderable);
            $box3content = $countdowntimer;

            $stateclasses = $submissionstatus;
            $delta = $duedate - $time;
            if ($delta < 1 * 24 * 60 * 60) {
                $stateclasses .= ' due1day';
            } else if ($delta < 2 * 24 * 60 * 60) {
                $stateclasses .= ' due2days';
            }
        }

        $awesomebar = new awesomebar_renderable($stateclasses, $box1header, $box1headershort, $box1content, $box2header, $box2headershort, $box2content, $box3header, $box3headershort, $box3content);
        $awesomebarrenderer = $this->page->get_renderer('theme_ouaclean', 'awesomebar');

        return $awesomebarrenderer->render($awesomebar);
    }

    /**
     * Output the page information
     *
     * @param object $quiz the quiz settings.
     * @param object $cm the course_module object.
     * @param object $context the quiz context.
     * @param array  $messages any access messages that should be described.
     *
     * @return string HTML to output.
     */
    public function view_information_custom($quiz, $cm, $context, $viewobj) {

        $quizobj = new quiz($quiz, $cm, null);

        $output = '';

        $output .= $this->quiz_intro($quiz, $cm);

        $quizobj->preload_questions();
        $quizobj->load_questions();
        /*
        // Disable quiz summary table for this iteration
        $quizsummarytable = new \theme_ouaclean\output\quizsummarytable\renderable($quizobj);
        $quizsummarytablerenderer = $this->page->get_renderer('theme_ouaclean', 'quizsummarytable');
        $output .= $quizsummarytablerenderer->render($quizsummarytable);
        */
        // Display number of questions in quiz instead
        $allquestions = $quizobj->get_questions();
        $output .= html_writer::tag('p', get_string('numquestionsx', 'quiz', count($allquestions)));
        $output .= html_writer::tag('p', get_string('attemptsallowedn', 'quizaccess_numattempts', $quiz->attempts));

        // Show number of attempts summary to those who can view reports.
        if (has_capability('mod/quiz:viewreports', $context)) {
            if ($strattemptnum = $this->quiz_attempt_summary_link_to_reports($quiz, $cm, $context)) {
                $output .= html_writer::tag('div', $strattemptnum,
                    array('class' => 'quizattemptcounts'));
            }
        }

        return $output;
    }

    /**
     * Generates custom view attempt button with class
     *
     * @param int                  $course The course ID
     * @param array                $quiz Array containging quiz date
     * @param int                  $cm The Course Module ID
     * @param int                  $context The page Context ID
     * @param mod_quiz_view_object $viewobj
     * @param string               $buttontext
     */
    public function start_attempt_button($buttontext, moodle_url $url,
        $startattemptwarning, $popuprequired, $popupoptions) {

        $button = new single_button($url, $buttontext);
        $button->class .= ' quizstartbuttondiv submissionaction';
        $button->buttonclass = ' primary_btn rightarrow';
        $warning = '';
        if ($popuprequired) {
            $this->page->requires->js_module(quiz_get_js_module());
            $this->page->requires->js('/mod/quiz/module.js');
            $popupaction = new popup_action('click', $url, 'quizpopup', $popupoptions);

            $button->class .= ' quizsecuremoderequired';
            $button->add_action(new component_action('click',
                'M.mod_quiz.secure_window.start_attempt_action', array(
                    'url'                 => $url->out(false),
                    'windowname'          => 'quizpopup',
                    'options'             => $popupaction->get_js_options(),
                    'fullscreen'          => true,
                    'startattemptwarning' => $startattemptwarning,
                )));

            $warning = html_writer::tag('noscript', $this->heading(get_string('noscript', 'quiz')));
        } else if ($startattemptwarning) {
            $button->add_action(new confirm_action($startattemptwarning, null,
                get_string('startattempt', 'quiz')));
        }

        return $this->render($button) . $warning;
    }

    /**
     * Generates data pertaining to quiz results
     *
     * @param array                $quiz Array containing quiz data
     * @param int                  $context The page context ID
     * @param int                  $cm The Course Module Id
     * @param mod_quiz_view_object $viewobj
     */
    public function get_quiz_results_obj($quiz, $context, $cm, $viewobj) {
        $results = array();
        if (!$viewobj->numattempts && !$viewobj->gradecolumn && is_null($viewobj->mygrade)) {
            return $results;
        }
        $resultinfo = '';

        if ($viewobj->overallstats) {
            if ($viewobj->moreattempts) {
                $a = new stdClass();
                $a->method = quiz_get_grading_option_name($quiz->grademethod);
                $a->mygrade = quiz_format_grade($quiz, $viewobj->mygrade);
                $a->quizgrade = quiz_format_grade($quiz, $quiz->grade);
                $results['gradesofar'] = $a;
                // $resultinfo .= $this->heading(get_string('gradesofar', 'quiz', $a), 3);
            } else {
                $a = new stdClass();
                $a->grade = quiz_format_grade($quiz, $viewobj->mygrade);
                $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
                if (is_null($viewobj->mygrade)) {
                    // Not Graded yet.
                    $a = $a->grade;
                } else {
                    $a = get_string('outof', 'quiz', $a);
                }
                $results['finalgrade'] = $a;
            }
        }

        /*

        // Do we want to display a grade overridden notice?

        if ($viewobj->mygradeoverridden) {
            $resultinfo .= html_writer::tag('p', get_string('overriddennotice', 'grades'),
                    array('class' => 'overriddennotice'))."\n";
        }
        */

        /*

        // Do we want to display quiz gradebook overall feedback?
        // Overall Quiz feedback is new to 2.9, and can only be added by overriding the gradebook.
        if ($viewobj->gradebookfeedback) {
            $resultinfo .= $this->heading(get_string('comment', 'quiz'), 3);
            $resultinfo .= html_writer::div($viewobj->gradebookfeedback, 'quizteacherfeedback') . "\n";
        }
        if ($viewobj->feedbackcolumn) {
            $resultinfo .= $this->heading(get_string('overallfeedback', 'quiz'), 3);
            $resultinfo .= html_writer::div(
                    quiz_feedback_for_grade($viewobj->mygrade, $quiz, $context),
                    'quizgradefeedback') . "\n";
        }
        */

        return $results;
    }

    /**
     * OUA Custom adds nojavascript statement of authorship to quiz summary page
     * @param quiz_attempt $attemptobj
     */
    public function summary_page_controls($attemptobj) {
        $output = '';

        // Return to place button.
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button = new single_button(
                new moodle_url($attemptobj->attempt_url(null, $attemptobj->get_currentpage())),
                get_string('returnattempt', 'quiz'));
            $output .= $this->container($this->container($this->render($button),
                                                         'controls'), 'submitbtns mdl-align');
        }

        // Finish attempt button.
        $options = array(
            'attempt' => $attemptobj->get_attemptid(),
            'finishattempt' => 1,
            'timeup' => 0,
            'slots' => '',
            'sesskey' => sesskey(),
        );

        $duedate = $attemptobj->get_due_date();
        $message = '';
        if ($attemptobj->get_state() == quiz_attempt::OVERDUE) {
            $message = get_string('overduemustbesubmittedby', 'quiz', userdate($duedate));

        } else if ($duedate) {
            $message = get_string('mustbesubmittedby', 'quiz', userdate($duedate));
        }

        $button = new single_button(
            new moodle_url($attemptobj->processattempt_url(), $options),
            get_string('submitallandfinish', 'quiz'));
        $button->id = 'responseform';
        if ($attemptobj->get_state() == quiz_attempt::IN_PROGRESS) {
            $button->add_action(new confirm_action(get_string('confirmclose', 'quiz'), null,
                                                   get_string('submitallandfinish', 'quiz')));
            $message .=  html_writer::tag('div', get_string('confirmclose', 'quiz'),
                                                            array('id' => 'quiznojsconfirmclose',
                                                                  'class' => 'alert alert-warning',
                                                                  'role' => 'alert'));
        }



        $output .= $this->countdown_timer($attemptobj, time());
        $output .= $this->container($message . $this->container(
                                        $this->render($button), 'controls'), 'submitbtns mdl-align');

        return $output;
    }
}