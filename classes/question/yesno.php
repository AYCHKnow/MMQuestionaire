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
 * This file contains the parent class for yesno question types.
 *
 * @author Mike Churchward
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questiontypes
 */

namespace mod_questionnaire\question;
defined('MOODLE_INTERNAL') || die();

class yesno extends question {

    protected function responseclass() {
        return '\\mod_questionnaire\\responsetype\\boolean';
    }

    public function helpname() {
        return 'yesno';
    }

    /**
     * Override and return a form template if provided. Output of question_survey_display is iterpreted based on this.
     * @return boolean | string
     */
    public function question_template() {
        return 'mod_questionnaire/question_yesno';
    }

    /**
     * Override and return a response template if provided. Output of question_survey_display is iterpreted based on this.
     * @return boolean | string
     */
    public function response_template() {
        return 'mod_questionnaire/response_yesno';
    }

    /**
     * Override this and return true if the question type allows dependent questions.
     * @return boolean
     */
    public function allows_dependents() {
        return true;
    }

    /**
     * True if question type supports feedback options. False by default.
     */
    public function supports_feedback() {
        return true;
    }

    /**
     * True if the question supports feedback and has valid settings for feedback. Override if the default logic is not enough.
     */
    public function valid_feedback() {
        return $this->required();
    }

    /**
     * Get the maximum score possible for feedback if appropriate. Override if default behaviour is not correct.
     * @return int | boolean
     */
    public function get_feedback_maxscore() {
        if ($this->valid_feedback()) {
            $maxscore = 1;
        } else {
            $maxscore = false;
        }
        return $maxscore;
    }

    /**
     * Returns an array of dependency options for the question as an array of id value / display value pairs. Override in specific
     * question types that support this.
     * @return array An array of valid pair options.
     */
    protected function get_dependency_options() {
        $options = [];
        if ($this->name != '') {
            $options[$this->id . ',0'] = $this->name . '->' . get_string('yes');
            $options[$this->id . ',1'] = $this->name . '->' . get_string('no');
        }
        return $options;
    }

    /**
     * Return the context tags for the check question template.
     * @param object $data
     * @param array $dependants Array of all questions/choices depending on this question.
     * @param boolean $blankquestionnaire
     * @return object The check question context tags.
     *
     */
    protected function question_survey_display($data, $dependants=[], $blankquestionnaire=false) {
        global $idcounter;  // To make sure all radio buttons have unique ids. // JR 20 NOV 2007.

        $stryes = get_string('yes');
        $strno = get_string('no');

        $val1 = 'y';
        $val2 = 'n';

        if ($blankquestionnaire) {
            $stryes = ' (1) '.$stryes;
            $strno = ' (0) '.$strno;
        }

        $options = [$val1 => $stryes, $val2 => $strno];
        $name = 'q'.$this->id;
        $checked = (isset($data->{'q'.$this->id}) ? $data->{'q'.$this->id} : '');
        $ischecked = false;

        $choicetags = new \stdClass();
        $choicetags->qelements = new \stdClass();
        $choicetags->qelements->choice = [];

        foreach ($options as $value => $label) {
            $htmlid = 'auto-rb'.sprintf('%04d', ++$idcounter);
            $option = new \stdClass();
            $option->name = $name;
            $option->id = $htmlid;
            $option->value = $value;
            $option->label = $label;
            if ($value == $checked) {
                $option->checked = true;
                $ischecked = true;
            }
            if ($blankquestionnaire) {
                $option->disabled = true;
            }
            $choicetags->qelements->choice[] = $option;
        }
        // CONTRIB-846.
        if (!$this->required()) {
            $id = '';
            $htmlid = 'auto-rb'.sprintf('%04d', ++$idcounter);
            $content = get_string('noanswer', 'questionnaire');
            $option = new \stdClass();
            $option->name = $name;
            $option->id = $htmlid;
            $option->value = $id;
            $option->label = format_text($content, FORMAT_HTML, ['noclean' => true]);
            if (!$ischecked && !$blankquestionnaire) {
                $option->checked = true;
            }
            $choicetags->qelements->choice[] = $option;
        }
        // End CONTRIB-846.

        return $choicetags;
    }

    /**
     * Return the context tags for the text response template.
     * @param \mod_questionnaire\responsetype\response\response $response
     * @return object The radio question response context tags.
     * @throws \coding_exception
     */
    protected function response_survey_display($response) {
        static $uniquetag = 0;  // To make sure all radios have unique names.

        $resptags = new \stdClass();

        $resptags->yesname = 'q'.$this->id.$uniquetag++.'y';
        $resptags->noname = 'q'.$this->id.$uniquetag++.'n';
        $resptags->stryes = get_string('yes');
        $resptags->strno = get_string('no');
        if (!isset($response->answers[$this->id])) {
            $response->answers[$this->id][] = new \mod_questionnaire\responsetype\answer\answer();
        }
        $answer = reset($response->answers[$this->id]);
        if ($answer->value == 'y') {
            $resptags->yesselected = 1;
        }
        if ($answer->value == 'n') {
            $resptags->noselected = 1;
        }

        return $resptags;
    }

    protected function form_length(\MoodleQuickForm $mform, $helpname = '') {
        return question::form_length_hidden($mform);
    }

    protected function form_precise(\MoodleQuickForm $mform, $helpname = '') {
        return question::form_precise_hidden($mform);
    }

    /**
     * True if question provides mobile support.
     *
     * @return bool
     */
    public function supports_mobile() {
        return true;
    }

    /**
     * @param $qnum
     * @param $fieldkey
     * @param bool $autonum
     * @return \stdClass
     * @throws \coding_exception
     */
    public function get_mobile_question_data($qnum, $autonum = false) {
        $mobiledata = parent::get_mobile_question_data($qnum, $autonum = false);
        $mobiledata->questionsinfo['isbool'] = true;
        return $mobiledata;
    }

    /**
     * @param $mobiledata
     * @return mixed
     */
    public function add_mobile_question_choice_data($mobiledata) {
        $mobiledata->questions = [];
        $mobiledata->questions[0] = new \stdClass();
        $mobiledata->questions[0]->id = 0;
        $mobiledata->questions[0]->choice_id = 'n';
        $mobiledata->questions[0]->question_id = $this->id;
        $mobiledata->questions[0]->value = null;
        $mobiledata->questions[0]->content = get_string('no');
        $mobiledata->questions[0]->isbool = true;
        $mobiledata->questions[1] = new \stdClass();
        $mobiledata->questions[1]->id = 1;
        $mobiledata->questions[1]->choice_id = 'y';
        $mobiledata->questions[1]->question_id = $this->id;
        $mobiledata->questions[1]->value = null;
        $mobiledata->questions[1]->content = get_string('yes');
        $mobiledata->questions[1]->isbool = true;
        if ($this->required()) {
            $mobiledata->questions[1]->value = 'y';
            $mobiledata->questions[1]->firstone = true;
        }
        $mobiledata->responses = 'n';

        return $mobiledata;
    }

    /**
     * @param $rid
     * @return \stdClass
     */
    public function get_mobile_response_data($rid) {
        $results = $this->get_results($rid);
        $resultdata = new \stdClass();
        $resultdata->answered = false;
        $resultdata->questions = [];
        $resultdata->responses = [];
        if (!empty($results)) {
            $resultdata->answered = true;
            foreach ($results as $result) {
                if ('y' == $result->choice_id) {
                    $resultdata->questions[1] = new \stdClass();
                    $resultdata->questions[1]->value = 'y';
                    $resultdata->responses['response_' . $this->type_id . '_' . $this->id] = 'y';
                } else {
                    $resultdata->questions[0] = new \stdClass();
                    $resultdata->questions[0]->value = 'n';
                    $resultdata->responses['response_' . $this->type_id . '_' . $this->id] = 'n';
                }
            }
        }

        return $resultdata;
    }
}