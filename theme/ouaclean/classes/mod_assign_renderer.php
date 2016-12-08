<?php
use \theme_ouaclean\output\countdowntimer\renderable as countdowntimer_renderable;
use \theme_ouaclean\output\awesomebar\renderable as awesomebar_renderable;

/**
 *  Override for the assignment display
 */
class theme_ouaclean_mod_assign_renderer extends mod_assign_renderer {
    protected $headerobj;
    public $feedbackstatusrenderable;
    public $usedefaultrender = false;

    /**
     * Custom Renderer the submission page, pull out feedback status so we can display it inside
     * submission status renderable
     *
     * @param $page
     *
     * @return bool|string
     * @throws moodle_exception
     */
    public function render_assign_submission_page($page) {
        global $PAGE;
        // Use the default renderer for report page.
        if (strpos($PAGE->url->get_path(), '/mod/assign/') === false) {
            return parent::render_assign_submission_page($page);
        }
        $data = $page->export_for_template($this);
        $submissionstatus = null;
        $this->feedbackstatusrenderable = null;
        foreach ($data->renderables as $name => $renderable) {
            if ($name == 'feedback_status') { // We will pull the renderable out and render it inside submission status
                $this->feedbackstatusrenderable = $renderable;
            } else if ($name == 'submission_status') { // Render submission status last as we want to render feedback_status inside it
                $submissionstatus = $renderable;
            } else {
                $data->$name = $this->render($renderable);
            }
        }
        if ($submissionstatus !== null) {
            $data->submission_status = $this->render($submissionstatus);
        }

        return $this->render_from_template('mod_assign/submission_page', $data);
    }

    /**
     * Render the header, store the header for alter use as it contains the assignment description
     * We want to display the assignment description AFTER the submission status
     *
     * @param assign_header $header
     *
     * @return string
     */
    public function render_assign_header(assign_header $header) {
        // Assign the header object to a protected var so it can bee accessed by the other methods.
        $this->headerobj = $header;

        $o = '';

        if ($header->subpage) {
            $this->page->navbar->add($header->subpage);
        }

        $this->page->set_title(get_string('pluginname', 'assign'));
        $this->page->set_heading($this->page->course->fullname);

        $o .= $this->output->header();
        $heading = format_string($header->assign->name, false, array('context' => $header->context));
        $o .= $this->output->heading($heading);
        if ($header->preface) {
            $o .= $header->preface;
        }

        if ($header->showintro && $header->subpage == 'Edit submission') { // OUA Custom: We show the intro after submission status. //? ??Do we want to display on edit submission page?
            $o .= $this->output->box_start('generalbox boxaligncenter assess-wrapper', 'intro');
            $o .= format_module_intro('assign', $header->assign, $header->coursemoduleid);
            $o .= $header->postfix;
            $o .= $this->output->box_end();
        }

        return $o;
    }

    /**
     * Render a table containing the current status of the submission.
     * Renders the "OUA Awesome bar"
     * Overrides default behavior to insert assignment description
     * Renders the tutor response inside the awesome bar
     * handles the assignment display
     *
     * @param assign_submission_status $status
     *
     * @return string
     */
    public function render_assign_submission_status(assign_submission_status $status) {
        global $PAGE;
        // OUA Stuff that we don't handle yet.
        // If we choose to use things that we havent yet catered for, default to displaying the core renderer for now
        if (strpos($PAGE->url->get_path(), '/mod/assign/') === false) {

            $this->usedefaultrender = true;
            $o = '';
            $o .= parent::render_assign_submission_status($status);
            if ($this->feedbackstatusrenderable) {
                $o .= $this->render($this->feedbackstatusrenderable);
            }

            return $o;
        }

        if (!$status->submission) {
            $submissionstatus = ''; // For Testing.
        } else {
            $submissionstatus = $status->submission->status;
        }
        $o = '';

        $o .= $this->output->container_start('submissionstatustable');
        $time = time();

        $opendate = $status->allowsubmissionsfromdate; // Date is submission open date
        $duedate = $status->duedate; // Date is submission open date
        $cutoffdate = $status->cutoffdate;
        $cutoffpassed = false;

        if ($status->extensionduedate) {
            // If the user is granted an extension, that is considered their due date.
            $duedate = $status->extensionduedate;
            if ($status->cutoffdate && ($status->extensionduedate > $status->cutoffdate)) {
                $cutoffdate = $status->cutoffdate;
            }
        }
        if ($duedate == 0) {
            $overdue = 0;
        } else {
            $overdue = (($duedate - $time) < 0);
        }
        if (isset($status->cutoffdate)
                && $cutoffdate !== 0
                && $time > $cutoffdate) {
            $cutoffpassed = true;
        }
// ----------------------------------------------------------------------------------------------------------------------------

        $submissionsnotyetopen = false;
        $submittedclass = '';
        $stateclasses = '';

        // Build 3 boxes.
        // First box is submission status.

        // Second box is open date or submission date.

        // Third box is countdown to opendate, countdown to submission date  or grading status if submitted.

        if (isset($status->allowsubmissionsfromdate)
                && $time <= $status->allowsubmissionsfromdate) {
            // STATUS = Not yet open.
            $submissionsnotyetopen = true;
        }

        if ($submissionstatus == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            $stateclasses .= ' submitted';
        }

        // First Box:
        // If not open display not yet open status
        // If open display submission status

        $box1header = get_string('assess:submissionstatus', 'theme_ouaclean');
        $box1headershort = get_string('assess:submissionstatusshort', 'theme_ouaclean');

        if ($submissionsnotyetopen) {
            $box1content = get_string('assess:submissionstatus_notyetopen', 'theme_ouaclean');
        } else {
            $box1content = get_string('assess:submissionstatus_' . $submissionstatus, 'theme_ouaclean');
        }

        // Second Box:
        // If not open, Display open date
        // If due Display due date
        // If submitted display submission date

        if ($submissionsnotyetopen) {
            $box2header = get_string('assess:submissionopendate', 'theme_ouaclean');
            $box2headershort = get_string('assess:submissionopendateshort', 'theme_ouaclean');
            $box2content = userdate($opendate, '%e %B %Y, %l:%M %p');
        } else if ($submissionstatus == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            $submitteddate = $status->submission->timemodified;
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
        if ($submissionsnotyetopen) {
            $box3header = get_string('assess:submissionopenin', 'theme_ouaclean');
            $box3headershort = get_string('assess:submissionopeninshort', 'theme_ouaclean');

            $countdowntimerrenderable = new countdowntimer_renderable($opendate);
            $countdownrenderer = $this->page->get_renderer('theme_ouaclean', 'countdowntimer');
            $countdowntimer = $countdownrenderer->render($countdowntimerrenderable);
            $box3content = $countdowntimer;

            $stateclasses .= ' notopen';
        } else if ($submissionstatus == ASSIGN_SUBMISSION_STATUS_SUBMITTED
                   || $status->gradingstatus == ASSIGN_MARKING_WORKFLOW_STATE_RELEASED
                   || $status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED) {
            // Assign Submitted so change last box to grade
            $box3header = get_string('assess:gradingstatus', 'theme_ouaclean');
            $box3headershort = get_string('assess:gradingstatushort', 'theme_ouaclean');

            if ($status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED
                    || $status->gradingstatus == ASSIGN_GRADING_STATUS_NOT_GRADED) {
                $box3content = get_string($status->gradingstatus, 'assign');
            } else {
                $gradingstatus = 'markingworkflowstate' . $status->gradingstatus;
                $box3content = get_string($gradingstatus, 'assign');
            }
            if ($status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED
                    || $status->gradingstatus == ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
                $stateclasses .= ' submissiongraded';
                $box3header = get_string('grade');
                $box3headershort = get_string('grade');
                if ($this->feedbackstatusrenderable) {
                    $box3content = $this->feedbackstatusrenderable->gradefordisplay;
                } else {
                    //grade hidden
                    get_string('assess:errorgettinggrade', 'theme_ouaclean');
                }
            } else {
                $stateclasses .= ' submissionnotgraded';
            }
        } else if ($overdue) {
            // Overdue - Overwrites the last box to display overdue
            $box3header = get_string('assess:submissionoverdueby', 'theme_ouaclean');
            $box3headershort = get_string('assess:submissionoverduebyshort', 'theme_ouaclean');
            $box3content = format_time($time - $duedate);
            $stateclasses .= ' overdue due1day';
        } else if ($duedate == 0) { // No Due date
            $box3header = "";
            $box3headershort = "";
            $box3content = "";
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

        if ($submissionstatus != ASSIGN_SUBMISSION_STATUS_SUBMITTED) { // If already submitted don't worry about cutoffs and locked.
            if ($status->cutoffdate // Cut off date passed.
                    && $cutoffpassed
                    && !$status->canedit
                    && !$submissionsnotyetopen) {
                $o .= html_writer::tag('div', get_string('submissionsclosed', 'mod_assign'),
                    array('class' => 'alert alert-error'));
            } else if ($status->locked) { // Assignment has been locked from gradebook.
                $o .= html_writer::tag('div', get_string('submissionslocked', 'mod_assign'),
                    array('class' => 'alert alert-warning'));
            }
        }

        $feedbackcontent = '';
        if ($this->feedbackstatusrenderable) { // generate feedbackstatus to display inside the Awesomebar.
            $feedbackcontent = $this->render($this->feedbackstatusrenderable);
        }
        $awesomebar = new awesomebar_renderable($stateclasses, $box1header, $box1headershort, $box1content, $box2header, $box2headershort, $box2content, $box3header, $box3headershort, $box3content, $feedbackcontent);
        $awesomebarrenderer = $this->page->get_renderer('theme_ouaclean', 'awesomebar');

        $o .= $awesomebarrenderer->render($awesomebar);


        // ----------------------------------------------------------------------------------
        // OUA Custom: Show Team Submission
        // ----------------------------------------------------------------------------------

        if ($status->teamsubmissionenabled) {
            $submissiongrouplabel = html_writer::tag('span', get_string('submissionteam', 'assign'));
            $submissiongrouplabelcontainer = $this->output->container($submissiongrouplabel, 'submission-group-label');
            $group = $status->submissiongroup;
            if ($group) {
                $submissiongroupmessage = html_writer::tag('span', format_string($group->name, false, $status->context));
            } else if ($status->preventsubmissionnotingroup) {
                $submissiongroupmessage = html_writer::tag('span', get_string('noteam', 'assign'), array('class'=>'no-team'));
            } else {
                $submissiongroupmessage = html_writer::tag('span', get_string('defaultteam', 'assign'), array('class'=>'default-team'));
            }
            $submissiongroupmessagecontainer = $this->output->container($submissiongroupmessage, 'submission-group-message');
            $o .= $this->output->container($submissiongrouplabelcontainer . $submissiongroupmessagecontainer, 'submission-group-container');
        }

        // ----------------------------------------------------------------------------------
        // OUA Custom: Show Attempts
        // ----------------------------------------------------------------------------------

        if ($status->attemptreopenmethod != ASSIGN_ATTEMPT_REOPEN_METHOD_NONE) {
            $currentattempt = 1;
            if (!$status->teamsubmissionenabled) {
                if ($status->submission) {
                    $currentattempt = $status->submission->attemptnumber + 1;
                }
            } else {
                if ($status->teamsubmission) {
                    $currentattempt = $status->teamsubmission->attemptnumber + 1;
                }
            }
            $maxattempts = $status->maxattempts;
            $message = html_writer::tag('span', get_string('currentattempt', 'assign', $currentattempt), array('class'=>'current-attempt'));
            if ($maxattempts != ASSIGN_UNLIMITED_ATTEMPTS) {
                $message .= html_writer::tag('span', get_string('maxattemptsallowed', 'theme_ouaclean', $maxattempts), array('class'=>'max-attempts'));
            }
            $o .= $this->output->container($message, 'attempts-number');
        }


        // ----------------------------------------------------------------------------------
        // OUA Custom: We show the intro after submission status.
        // ----------------------------------------------------------------------------------
        $o .= $this->output->box_start('generalbox boxaligncenter assess-wrapper', 'intro');
        if (isset($this->headerobj->showintro) && $this->headerobj->showintro) {

            $o .= format_module_intro('assign', $this->headerobj->assign, $this->headerobj->coursemoduleid);
            $o .= $this->headerobj->postfix;
        }
        if (!$submissionsnotyetopen && $status->submission) { // OUA Custom show the submissions inside info box. Only show if submissions open
            $o .= $this->render_assign_student_submissions($status);
        }
        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;

        // Links.
        if ($status->view == assign_submission_status::STUDENT_VIEW) {
            if ($status->canedit) {
                if (!$submission || $submission->status == ASSIGN_SUBMISSION_STATUS_NEW) {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $o .= $this->output->single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                        get_string('addsubmission', 'assign'), 'get');
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('editsubmission_help', 'assign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                } else if ($submission->status == ASSIGN_SUBMISSION_STATUS_REOPENED) {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id'      => $status->coursemoduleid,
                                       'action'  => 'editprevioussubmission',
                                       'sesskey' => sesskey());

                    $button1 = new single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                        get_string('reattempt', 'theme_ouaclean'), 'get');
                    $button1->buttonclass = "primary_btn rightarrow";
                    $o .= html_writer::tag('button', get_string('addnewattempt', 'assign'), array('class' => 'btn',
                        'data-toggle' => 'modal',
                        'data-target' => '#attempt-option',
                    ));
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $button2 = new single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                        get_string('newattempt', 'theme_ouaclean'), 'get');
                    $button2->buttonclass = "primary_btn rightarrow";
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('addnewattempt_help', 'assign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();

                    $o .= '
                        <!-- Modal -->
                        <div class="modal fade" id="attempt-option" tabindex="-1" role="dialog" aria-labelledby="attempt-option-label">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                <h4 class="modal-title text-center" id="attempt-option-label">' . get_string('selectattempt', 'theme_ouaclean') . '</h4>
                              </div>
                              <div class="modal-body">
                                <div class="col-sm-6 newattempt">
                                ' . $this->render($button2) . '<i class="fa fa-arrow-down"></i>' . html_writer::tag('p', get_string('addnewattempt_help', 'assign')) . '
                                </div>
                                <div class="col-sm-6 reattempt">
                                ' . $this->render($button1) . '<i class="fa fa-arrow-down"></i>' . html_writer::tag('p', get_string('addnewattemptfromprevious_help', 'assign')) . '
                                </div>
                                <div class="clearfix"></div>
                              </div>
                            </div>
                          </div>
                        </div>
                    ';

                } else {
                    $o .= $this->output->box_start('generalbox submissionaction');
                    $urlparams = array('id' => $status->coursemoduleid, 'action' => 'editsubmission');
                    $button = new single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                        get_string('editsubmission', 'assign'), 'get');
                    $button->buttonclass = "primary_btn rightarrow";
                    $o .= $this->render($button);
                    $o .= $this->output->box_start('boxaligncenter submithelp');
                    $o .= get_string('editsubmission_help', 'assign');
                    $o .= $this->output->box_end();
                    $o .= $this->output->box_end();
                }
            }

            if ($status->cansubmit) {
                $urlparams = array('id' => $status->coursemoduleid, 'action' => 'submit');
                $o .= $this->output->box_start('generalbox submissionaction');
                $button = new single_button(new moodle_url('/mod/assign/view.php', $urlparams),
                    get_string('submitassignment', 'assign'), 'get');
                $button->buttonclass = "primary_btn rightarrow";
                $o .= $this->render($button);
                $o .= $this->output->box_start('boxaligncenter submithelp');
                $o .= get_string('submitassignment_help', 'assign');
                $o .= $this->output->box_end();
                $o .= $this->output->box_end();
            }
        }
        $o .= $this->output->box_end();
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * OUA Custom: display the files a student has submitted for their assignment
     *
     * @param assign_submission_status $status
     *
     * @return string
     * @throws coding_exception
     */
    public function render_assign_student_submissions(assign_submission_status $status) {
        $o = '';
        $filesubmission = '';
        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        if (isset($submission->timemodified)) {
            $lastmodified = userdate($submission->timemodified);
        }
        foreach ($status->submissionplugins as $plugin) {
            $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
            if ($plugin->is_enabled()
                    && $plugin->is_visible()
                    && $plugin->has_user_summary()
                    && $pluginshowsummary) {
                $displaymode = assign_submission_plugin_submission::SUMMARY;
                $pluginsubmission = new assign_submission_plugin_submission($plugin,
                    $submission,
                    $displaymode,
                    $status->coursemoduleid,
                    $status->returnaction,
                    $status->returnparams);

                $renderedsubmission = $this->render($pluginsubmission);

                // OUA Custom: custom display for online text, display submitted files last, no heading for submission comments.
                if ($plugin instanceof assign_submission_onlinetext) {
                    // Make sure the empty text submission so when we display the header there is
                    // something below it that is visible to the user.
                    $submissiontext = $plugin->view($submission);
                    $emptysub = strip_tags($submissiontext);
                    $emptysub = trim($emptysub, " \t\n\r\0\x0B\xc2\xa0");

                    if (!empty($emptysub)) {
                        $o .= $this->output->heading(get_string('answertext', 'theme_ouaclean'), 4);
                        $o .= $this->output->box_start('submissiontext');
                        $o .= $submissiontext;
                        $o .= $this->output->box_end();
                    }
                } else if ($plugin instanceof assign_submission_file) {
                    $filesubmission .= $renderedsubmission;
                } else if ($plugin instanceof assign_submission_comments) {
                    $o .= $renderedsubmission;
                } else {
                    $o .= $this->output->heading($plugin->get_name(), 4);
                    $o .= $renderedsubmission;
                }
            }
        }
        // Force file submissions to be displayed last.
        if (!empty($filesubmission)) {
            $o .= $this->output->heading(get_string('answerfile', 'theme_ouaclean'), 4);;
            $o .= $filesubmission;
        }

        return $o;
    }

    /**
     *
     * Extracted version of the core submission status table
     *
     * @param assign_submission_status $status
     *
     * @return string
     * @throws coding_exception
     */
    public function render_assign_core_submission_status_table(assign_submission_status $status) {
        $time = time();
        $o = $this->output->box_start('boxaligncenter submissionsummarytable');

        $t = new html_table();

        if ($status->teamsubmissionenabled) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submissionteam', 'assign'));
            $group = $status->submissiongroup;
            if ($group) {
                $cell2 = new html_table_cell(format_string($group->name, false, $status->context));
            } else if ($status->preventsubmissionnotingroup) {
                $cell2 = new html_table_cell(get_string('noteam', 'assign'));
            } else {
                $cell2 = new html_table_cell(get_string('defaultteam', 'assign'));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }
        if ($status->attemptreopenmethod != ASSIGN_ATTEMPT_REOPEN_METHOD_NONE) {
            $currentattempt = 1;
            if (!$status->teamsubmissionenabled) {
                if ($status->submission) {
                    $currentattempt = $status->submission->attemptnumber + 1;
                }
            } else {
                if ($status->teamsubmission) {
                    $currentattempt = $status->teamsubmission->attemptnumber + 1;
                }
            }

            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('attemptnumber', 'assign'));
            $maxattempts = $status->maxattempts;
            if ($maxattempts == ASSIGN_UNLIMITED_ATTEMPTS) {
                $message = get_string('currentattempt', 'assign', $currentattempt);
            } else {
                $message = get_string('currentattemptof', 'assign', array('attemptnumber' => $currentattempt,
                                                                          'maxattempts'   => $maxattempts));
            }
            $cell2 = new html_table_cell($message);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
        if (!$status->teamsubmissionenabled) {
            if ($status->submission && $status->submission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
                $statusstr = get_string('submissionstatus_' . $status->submission->status, 'assign');
                $cell2 = new html_table_cell($statusstr);
                $cell2->attributes = array('class' => 'submissionstatus' . $status->submission->status);
            } else {
                if (!$status->submissionsenabled) {
                    $cell2 = new html_table_cell(get_string('noonlinesubmissions', 'assign'));
                } else {
                    $cell2 = new html_table_cell(get_string('noattempt', 'assign'));
                }
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        } else {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('submissionstatus', 'assign'));
            $group = $status->submissiongroup;
            if (!$group && $status->preventsubmissionnotingroup) {
                $cell2 = new html_table_cell(get_string('nosubmission', 'assign'));
            } else if ($status->teamsubmission && $status->teamsubmission->status != ASSIGN_SUBMISSION_STATUS_NEW) {
                $teamstatus = $status->teamsubmission->status;
                $submissionsummary = get_string('submissionstatus_' . $teamstatus, 'assign');
                $groupid = 0;
                if ($status->submissiongroup) {
                    $groupid = $status->submissiongroup->id;
                }

                $members = $status->submissiongroupmemberswhoneedtosubmit;
                $userslist = array();
                foreach ($members as $member) {
                    $urlparams = array('id' => $member->id, 'course' => $status->courseid);
                    $url = new moodle_url('/user/view.php', $urlparams);
                    if ($status->view == assign_submission_status::GRADER_VIEW && $status->blindmarking) {
                        $userslist[] = $member->alias;
                    } else {
                        $fullname = fullname($member, $status->canviewfullnames);
                        $userslist[] = $this->output->action_link($url, $fullname);
                    }
                }
                if (count($userslist) > 0) {
                    $userstr = join(', ', $userslist);
                    $formatteduserstr = get_string('userswhoneedtosubmit', 'assign', $userstr);
                    $submissionsummary .= $this->output->container($formatteduserstr);
                }

                $cell2 = new html_table_cell($submissionsummary);
                $cell2->attributes = array('class' => 'submissionstatus' . $status->teamsubmission->status);
            } else {
                $cell2 = new html_table_cell(get_string('nosubmission', 'assign'));
                if (!$status->submissionsenabled) {
                    $cell2 = new html_table_cell(get_string('noonlinesubmissions', 'assign'));
                } else {
                    $cell2 = new html_table_cell(get_string('nosubmission', 'assign'));
                }
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Is locked?
        if ($status->locked) {
            $row = new html_table_row();
            $cell1 = new html_table_cell();
            $cell2 = new html_table_cell(get_string('submissionslocked', 'assign'));
            $cell2->attributes = array('class' => 'submissionlocked');
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Grading status.
        $row = new html_table_row();
        $cell1 = new html_table_cell(get_string('gradingstatus', 'assign'));

        if ($status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED ||
            $status->gradingstatus == ASSIGN_GRADING_STATUS_NOT_GRADED
        ) {
            $cell2 = new html_table_cell(get_string($status->gradingstatus, 'assign'));
        } else {
            $gradingstatus = 'markingworkflowstate' . $status->gradingstatus;
            $cell2 = new html_table_cell(get_string($gradingstatus, 'assign'));
        }
        if ($status->gradingstatus == ASSIGN_GRADING_STATUS_GRADED
                || $status->gradingstatus == ASSIGN_MARKING_WORKFLOW_STATE_RELEASED) {
            $cell2->attributes = array('class' => 'submissiongraded');
        } else {
            $cell2->attributes = array('class' => 'submissionnotgraded');
        }
        $row->cells = array($cell1, $cell2);
        $t->data[] = $row;

        $submission = $status->teamsubmission ? $status->teamsubmission : $status->submission;
        $duedate = $status->duedate;
        if ($duedate > 0) {
            // Due date.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('duedate', 'assign'));
            $cell2 = new html_table_cell(userdate($duedate));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            if ($status->view == assign_submission_status::GRADER_VIEW) {
                if ($status->cutoffdate) {
                    // Cut off date.
                    $row = new html_table_row();
                    $cell1 = new html_table_cell(get_string('cutoffdate', 'assign'));
                    $cell2 = new html_table_cell(userdate($status->cutoffdate));
                    $row->cells = array($cell1, $cell2);
                    $t->data[] = $row;
                }
            }

            if ($status->extensionduedate) {
                // Extension date.
                $row = new html_table_row();
                $cell1 = new html_table_cell(get_string('extensionduedate', 'assign'));
                $cell2 = new html_table_cell(userdate($status->extensionduedate));
                $row->cells = array($cell1, $cell2);
                $t->data[] = $row;
                $duedate = $status->extensionduedate;
            }

            // Time remaining.
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timeremaining', 'assign'));
            if ($duedate - $time <= 0) {
                if (!$submission || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                    if ($status->submissionsenabled) {
                        $overduestr = get_string('overdue', 'assign', format_time($time - $duedate));
                        $cell2 = new html_table_cell($overduestr);
                        $cell2->attributes = array('class' => 'overdue');
                    } else {
                        $cell2 = new html_table_cell(get_string('duedatereached', 'assign'));
                    }
                } else {
                    if ($submission->timemodified > $duedate) {
                        $latestr = get_string('submittedlate',
                            'assign',
                            format_time($submission->timemodified - $duedate));
                        $cell2 = new html_table_cell($latestr);
                        $cell2->attributes = array('class' => 'latesubmission');
                    } else {
                        $earlystr = get_string('submittedearly',
                            'assign',
                            format_time($submission->timemodified - $duedate));
                        $cell2 = new html_table_cell($earlystr);
                        $cell2->attributes = array('class' => 'earlysubmission');
                    }
                }
            } else {
                $cell2 = new html_table_cell(format_time($duedate - $time));
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Show graders whether this submission is editable by students.
        if ($status->view == assign_submission_status::GRADER_VIEW) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('editingstatus', 'assign'));
            if ($status->canedit) {
                $cell2 = new html_table_cell(get_string('submissioneditable', 'assign'));
                $cell2->attributes = array('class' => 'submissioneditable');
            } else {
                $cell2 = new html_table_cell(get_string('submissionnoteditable', 'assign'));
                $cell2->attributes = array('class' => 'submissionnoteditable');
            }
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Grading criteria preview.
        if (!empty($status->gradingcontrollerpreview)) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('gradingmethodpreview', 'assign'));
            $cell2 = new html_table_cell($status->gradingcontrollerpreview);
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;
        }

        // Last modified.
        if ($submission) {
            $row = new html_table_row();
            $cell1 = new html_table_cell(get_string('timemodified', 'assign'));
            $cell2 = new html_table_cell(userdate($submission->timemodified));
            $row->cells = array($cell1, $cell2);
            $t->data[] = $row;

            if (!$status->teamsubmission || $status->submissiongroup != false || !$status->preventsubmissionnotingroup) {
                foreach ($status->submissionplugins as $plugin) {
                    $pluginshowsummary = !$plugin->is_empty($submission) || !$plugin->allow_submissions();
                    if ($plugin->is_enabled()
                            && $plugin->is_visible()
                            && $plugin->has_user_summary()
                            && $pluginshowsummary) {

                        $row = new html_table_row();
                        $cell1 = new html_table_cell($plugin->get_name());
                        $displaymode = assign_submission_plugin_submission::SUMMARY;
                        $pluginsubmission = new assign_submission_plugin_submission($plugin,
                            $submission,
                            $displaymode,
                            $status->coursemoduleid,
                            $status->returnaction,
                            $status->returnparams);
                        $cell2 = new html_table_cell($this->render($pluginsubmission));
                        $row->cells = array($cell1, $cell2);
                        $t->data[] = $row;
                    }
                }
            }
        }

        $o .= html_writer::table($t);
        $o .= $this->output->box_end();

        return $o;
    }

    /**
     * Render the submit for grading page
     *
     * @param assign_submit_for_grading_page $page $status->coursemoduleid
     *
     * @return string
     */
    public function render_assign_submit_for_grading_page($page) {
        global $DB;
        // return parent::render_assign_submit_for_grading_page($page);
        $o = '';
        $o .= $this->output->container_start('submitforgrading');
        $o .= $this->output->heading(get_string('submitassignment', 'assign'), 3);

        $cancelurl = new moodle_url('/mod/assign/view.php', array('id' => $page->coursemoduleid));
        if (count($page->notifications)) {
            // At least one of the submission plugins is not ready for submission.

            $o .= $this->output->heading(get_string('submissionnotready', 'assign'), 4);

            foreach ($page->notifications as $notification) {
                $o .= $this->output->notification($notification);
            }

            $o .= $this->output->continue_button($cancelurl);
        } else {
            // All submission plugins ready - show the confirmation form.
            $o .= $this->output->box_start('submitconfirm');
            $o .= $this->output->heading(get_string('confirm', 'theme_ouaclean'), 4);
            $o .= html_writer::tag('div', get_string('oncesubmit', 'theme_ouaclean'), array('class' => 'confirm-message'));

            // Global assignment configuration, mod/assign/locallib.php:4028ff for details of core assignment.
            $adminconfig = get_config('assign'); // Get global Assign configuration.
            // Get the setting for this assignment.
            $requiresubmissionstatement = $DB->get_field_sql("SELECT a.requiresubmissionstatement
                                                                FROM {assign} a
                                                               WHERE a.id = :id", array('id' => $this->page->cm->instance));

            $requiresubmissionstatement = $requiresubmissionstatement &&
                                          !empty($adminconfig->submissionstatement);
            $submissionstatement = '';
            if (!empty($adminconfig->submissionstatement)) {
                // Format the submission statement before its sent. We turn off para because this is going within
                // a form element.
                $options = array(
                    'context' => $this->page->context,
                    'para'    => false);
                $submissionstatement = format_text($adminconfig->submissionstatement, FORMAT_MOODLE, $options);
            }
            // Error message doesnt display with javascript off, because this is a differnent form.
            $mform = new theme_ouaclean_submit_confirm_form(null, array($requiresubmissionstatement,
                $submissionstatement,
                $this->page->cm->id,
                new stdClass()));

            $o .= $mform->render();
            $o .= $this->output->heading(get_string('or', 'theme_ouaclean'), 4);
            $o .= html_writer::link($cancelurl, get_string('dontsubmit', 'theme_ouaclean'));

            $o .= $this->output->box_end();
        }
        $o .= $this->output->container_end();

        return $o;
    }

    /**
     * Render a table containing all the current grades and feedback.
     *
     * @param assign_feedback_status $status
     *
     * @return string
     */
    public function render_assign_feedback_status(assign_feedback_status $status) {
        global $DB, $PAGE;
        if (strpos($PAGE->url->get_path(), '/mod/assign/') === false
                || $this->usedefaultrender === true) {
            return parent::render_assign_feedback_status($status);
        }
        $o = '';

        $o .= $this->output->container_start('feedback');
        if (!$status->grader && isset($status->grade->grader)) {
            $status->grader = $DB->get_record('user', array('id' => $status->grade->grader));
        }
        if ($status->grader) {
            // Grader.
            $o .= html_writer::start_tag('div', array('class' => 'tutor_img'));
            $o .= $this->output->user_picture($status->grader);
            $o .= html_writer::end_tag('div');
            $o .= html_writer::tag('span', get_string('tutor_response', 'theme_ouaclean'), array('class' => 'tutor_response'));

            $o .= html_writer::start_tag('span', array('class' => 'tutor_date'));
            $o .= get_string('edited', 'theme_ouaclean');
            $o .= userdate($status->gradeddate, '%e %B %Y, %l:%M %p');
            $o .= html_writer::end_tag('span');

            $o .= html_writer::start_tag('span', array('class' => 'tutor_name'));
            $o .= get_string('by', 'theme_ouaclean');
            $url = new moodle_url('/user/view.php', array("id" => $status->grader->id));

            $o .= html_writer::link($url, fullname($status->grader));
            $o .= html_writer::end_tag('span');
        }

        $commenttexthtml = '';
        $commentfileshtml = '';
        foreach ($status->feedbackplugins as $plugin) {
            if ($plugin->is_enabled() &&
                    $plugin->is_visible() &&
                    $plugin->has_user_summary() &&
                    !empty($status->grade) &&
                    !$plugin->is_empty($status->grade)) {

                if ($plugin instanceof assign_feedback_comments) {
                    $commenttexthtml = $plugin->view($status->grade);
                }
                if ($plugin instanceof assign_feedback_file) {
                    $commentfileshtml = $plugin->view($status->grade);
                }
            }
        }
        // Force comments to always be first, regardless of the order of plugins.
        $o .= $commenttexthtml;
        $o .= $commentfileshtml;
        $o .= $this->output->container_end();
        return $o;
    }

    /**
     * Rendering assignment files on the screen.
     *
     * @param assign_files $tree
     * @param string       $dir A directory to process in the tree.
     *
     * @return string
     */
    public function render_assign_files(assign_files $tree, $dir = '') {
        $html = '';

        if ($dir == '') {
            $dir = $tree->dir;
        }

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }

        // Look in any sub folder and add the files to the list to display.
        foreach ($dir['subdirs'] as $subdir) {
            $html .= $this->render_assign_files($tree, $subdir);
        }

        foreach ($dir['files'] as $file) {
            $html .= $this->output->box_start('submission-file');
            $html .= html_writer::start_tag('div', array('class' => 'file-image'));
            $html .= $this->output->pix_icon(file_file_icon($file),
                $file->get_filename(), 'moodle', array('class' => 'icon'));
            $html .= html_writer::end_tag('div');
            $html .= html_writer::start_tag('div', array('class' => 'file-text'));
            $html .= $file->fileurl;
            $html .= display_size($file->get_filesize());
            $html .= html_writer::end_tag('div');
            $html .= $this->output->box_end();
        }

        return $html;
    }
}