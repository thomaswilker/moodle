<?php

/**
 *  Override for the Workshop display
 */
class theme_ouaclean_mod_workshop_renderer extends mod_workshop_renderer {
    /**
     * Renders the user plannner tool
     *
     * @param workshop_user_plan $plan prepared for the user
     * @return string html code to be displayed
     */
    protected function render_workshop_user_plan(workshop_user_plan $plan) {
        $tablist = '';
        $tabcontent = '';
        $statebefore = 'completed'; /* phases before currently active phase are considered completed for css styling */
        $canseesetupphase = has_capability('mod/workshop:switchphase', $plan->workshop->context, $plan->userid);
        if ($canseesetupphase) {
            $tabcount = 0;
        } else {
            $tabcount = 1;
            if ( 1 == $plan->phases[workshop::PHASE_SETUP]->active ) { /* before removing Setup phase we check if it is the currently active one */
                $plan->phases[workshop::PHASE_SUBMISSION]->active = 1; /* make Submission phase active instead, for css styling purpose */
            }
            unset($plan->phases[workshop::PHASE_SETUP]); /* remove Setup phase */
        }
        foreach ($plan->phases as $phasecode => $phase) { /* Looping through each phase */
            $actions = '';
            foreach ($phase->actions as $action) {
                switch ($action->type) {
                    case 'switchphase':
                        $icon = 'i/marker';
                        if ($phasecode == workshop::PHASE_ASSESSMENT
                            and $plan->workshop->phase == workshop::PHASE_SUBMISSION
                            and $plan->workshop->phaseswitchassessment) {
                            $icon = 'i/scheduled';
                        }
                        $actions .= $this->output->action_icon($action->url, new pix_icon($icon, get_string('switchphase', 'workshop')));
                        break;
                }
            }
            if (!empty($actions)) {
                $actions = html_writer::tag('span', $actions, array('class' => 'actions'));
            }
            $title = html_writer::tag('h3', $phase->title . $actions);
            if ( $title ) {
                $id = 'phase' . $phasecode;
                if ($phase->active) {
                    $state = 'current active';
                    $statebefore = '';
                } else {
                    $state = $statebefore;
                }
                $phasetitle = html_writer::tag('a', $tabcount, array('href' => '#' . $id, 'role' => 'tab', 'data-toggle' => 'tab'));
                $phasetask = html_writer::tag('div', $this->helper_user_plan_tasks($phase->tasks), array('class' => 'tasks-wrapper')); /* prepare content of each tab content */
                $tablist .= html_writer::tag('li', $phasetitle, array('class' => $state)); /* collect tabs */
                $tabcontent .= html_writer::tag('div', $title . $phasetask, array('class' => 'tab-pane ' . $state, 'role' => 'tabpanel', 'id' => $id)); /* collect tabs' content */
                $tabcount ++;
                $title = ''; /* reset title */
            }
        } /* end loop  through each phase */
        $tablist =  html_writer::tag('ul',  $tablist, array('class' => 'nav nav-tabs nav-justified nav-flat nav-progress no-collapse phase-progress')); /* wrap in ul */
        $tabcontent =  html_writer::tag('div',  $tabcontent, array( 'class' => 'tab-content phase-tasks')); /* wrap in div */
        $out = $tablist . $tabcontent;
        if ($out) {
            $out = html_writer::tag('div', $out, array('class' => 'userplan'));
        }
        return $out;
    }
    /**
     * Renders the workshop grading report
     *
     * @param workshop_grading_report $gradingreport
     * @return string html code
     */
    protected function render_workshop_grading_report(workshop_grading_report $gradingreport) {

        $data       = $gradingreport->get_data();
        $options    = $gradingreport->get_options();
        $grades     = $data->grades;
        $userinfo   = $data->userinfo;

        if (empty($grades)) {
            return '';
        }

        $table = new html_table();
        $table->attributes['class'] = 'table table-striped grading-report';

        $sortbyfirstname = $this->helper_sortable_heading(get_string('firstname'), 'firstname', $options->sortby, $options->sorthow);
        $sortbylastname = $this->helper_sortable_heading(get_string('lastname'), 'lastname', $options->sortby, $options->sorthow);
        if (self::fullname_format() == 'lf') {
            $sortbyname = $sortbylastname . ' / ' . $sortbyfirstname;
        } else {
            $sortbyname = $sortbyfirstname . ' / ' . $sortbylastname;
        }

        $table->head = array();
        $table->head[] = $sortbyname;
        $table->head[] = $this->helper_sortable_heading(get_string('submission', 'workshop'), 'submissiontitle',
            $options->sortby, $options->sorthow);
        $table->head[] = $this->helper_sortable_heading(get_string('receivedgrades', 'workshop'));
        if ($options->showsubmissiongrade) {
            $table->head[] = $this->helper_sortable_heading(get_string('submissiongradeof', 'workshop', $data->maxgrade),
                'submissiongrade', $options->sortby, $options->sorthow);
        }
        $table->head[] = $this->helper_sortable_heading(get_string('givengrades', 'workshop'));
        if ($options->showgradinggrade) {
            $table->head[] = $this->helper_sortable_heading(get_string('gradinggradeof', 'workshop', $data->maxgradinggrade),
                'gradinggrade', $options->sortby, $options->sorthow);
        }

        $table->rowclasses  = array();
        $table->colclasses  = array();
        $table->data        = array();

        foreach ($grades as $participant) {
            $numofreceived  = count($participant->reviewedby);
            $numofgiven     = count($participant->reviewerof);
            $published      = $participant->submissionpublished;

            // compute the number of <tr> table rows needed to display this participant
            if ($numofreceived > 0 and $numofgiven > 0) {
                $numoftrs       = workshop::lcm($numofreceived, $numofgiven);
                $spanreceived   = $numoftrs / $numofreceived;
                $spangiven      = $numoftrs / $numofgiven;
            } elseif ($numofreceived == 0 and $numofgiven > 0) {
                $numoftrs       = $numofgiven;
                $spanreceived   = $numoftrs;
                $spangiven      = $numoftrs / $numofgiven;
            } elseif ($numofreceived > 0 and $numofgiven == 0) {
                $numoftrs       = $numofreceived;
                $spanreceived   = $numoftrs / $numofreceived;
                $spangiven      = $numoftrs;
            } else {
                $numoftrs       = 1;
                $spanreceived   = 1;
                $spangiven      = 1;
            }

            for ($tr = 0; $tr < $numoftrs; $tr++) {
                $row = new html_table_row();
                if ($published) {
                    $row->attributes['class'] = 'published';
                }
                // column #1 - participant - spans over all rows
                if ($tr == 0) {
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_participant($participant, $userinfo);
                    $cell->rowspan = $numoftrs;
                    $cell->attributes['class'] = 'participant';
                    $row->cells[] = $cell;
                }
                // column #2 - submission - spans over all rows
                if ($tr == 0) {
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_submission($participant);
                    $cell->rowspan = $numoftrs;
                    $cell->attributes['class'] = 'submission';
                    $row->cells[] = $cell;
                }
                // column #3 - received grades
                if ($tr % $spanreceived == 0) {
                    $idx = intval($tr / $spanreceived);
                    $assessment = self::array_nth($participant->reviewedby, $idx);
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_assessment($assessment, $options->showreviewernames, $userinfo,
                        get_string('gradereceivedfrom', 'workshop'));
                    $cell->rowspan = $spanreceived;
                    $cell->attributes['class'] = 'receivedgrade';
                    if (is_null($assessment) or is_null($assessment->grade)) {
                        $cell->attributes['class'] .= ' null';
                    } else {
                        $cell->attributes['class'] .= ' notnull';
                    }
                    $row->cells[] = $cell;
                }
                // column #4 - total grade for submission
                if ($options->showsubmissiongrade and $tr == 0) {
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_grade($participant->submissiongrade, $participant->submissiongradeover);
                    $cell->rowspan = $numoftrs;
                    $cell->attributes['class'] = 'submissiongrade';
                    $row->cells[] = $cell;
                }
                // column #5 - given grades
                if ($tr % $spangiven == 0) {
                    $idx = intval($tr / $spangiven);
                    $assessment = self::array_nth($participant->reviewerof, $idx);
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_assessment($assessment, $options->showauthornames, $userinfo,
                        get_string('gradegivento', 'workshop'));
                    $cell->rowspan = $spangiven;
                    $cell->attributes['class'] = 'givengrade';
                    if (is_null($assessment) or is_null($assessment->grade)) {
                        $cell->attributes['class'] .= ' null';
                    } else {
                        $cell->attributes['class'] .= ' notnull';
                    }
                    $row->cells[] = $cell;
                }
                // column #6 - total grade for assessment
                if ($options->showgradinggrade and $tr == 0) {
                    $cell = new html_table_cell();
                    $cell->text = $this->helper_grading_report_grade($participant->gradinggrade);
                    $cell->rowspan = $numoftrs;
                    $cell->attributes['class'] = 'gradinggrade';
                    $row->cells[] = $cell;
                }

                $table->data[] = $row;
            }
        }

        return $this->output->container(html_writer::table($table), 'table-responsive');
    }
}