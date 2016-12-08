<?php
namespace theme_ouaclean\output\quizsummarytable;

class renderable implements \renderable, \templatable {
    private $questions = array();

    public function __construct($quizobj) {

        $questions = $quizobj->get_questions();
        $questionnum = 1;
        $notyetanswered = get_string('notyetanswered', 'question');
        $quizdecimalpoints = $quizobj->get_quiz()->decimalpoints;
        foreach ($questions as $question) {
            $questionclass = \question_bank::get_qtype($question->qtype);
            if (!$questionclass->is_real_question_type()) {
                continue;
            }
            $row = array('num' => $questionnum, 'name' => $question->name, 'status' => $notyetanswered, 'mark' => 'Out of ' . format_float($question->maxmark, $quizdecimalpoints, true, true));
            $this->questions[] = $row;
            $questionnum++;
        }


    }

    public function export_for_template(\renderer_base $output) {
        $data = new \StdClass();
        $data->quizquestions = new \ArrayIterator($this->questions);

        return $data;
    }
}