<?php
/* For licensing terms, see /license.txt */

/**
 * The ScormQuestion class is a gateway to getting the answers exported
 * (the question is just an HTML text, while the answers are the most important).
 * It is important to note that the SCORM export process is done in two parts.
 * First, the HTML part (which is the presentation), and second the JavaScript
 * part (the process).
 * The two bits are separate to allow for a one-big-javascript and a one-big-html
 * files to be built. Each export function thus returns an array of HTML+JS
 *
 *
 * @author Claro Team <cvs@claroline.net>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 *
 * @package chamilo.exercise.scorm
 */
class ScormQuestion extends Question
{
	/**
	 * Returns the HTML + JS flow corresponding to one question
	 *
	 * @param int $questionId The question ID
	 * @param bool $standalone (ie including XML tag, DTD declaration, etc)
	 * @param int  $js_id The JavaScript ID for this question.
	 * Due to the nature of interactions, we must have a natural sequence for
	 * questions in the generated JavaScript.
	 * @param integer $js_id
	 */
	public static function export_question($questionId, $standalone = true, $js_id)
	{
		$question = new ScormQuestion();
		$qst = $question->read($questionId);
		if (!$qst) {
			return '';
		}
		$question->id = $qst->id;
		$question->js_id = $js_id;
		$question->type = $qst->type;
		$question->question = $qst->question;
		$question->description = $qst->description;
		$question->weighting=$qst->weighting;
		$question->position=$qst->position;
		$question->picture=$qst->picture;
		$assessmentItem = new ScormAssessmentItem($question, $standalone);

		return $assessmentItem->export();
	}

	/**
	 * Include the correct answer class and create answer
	 */
	public function setAnswer()
	{
		switch ($this->type) {
			case MCUA:
				$this->answer = new ScormAnswerMultipleChoice($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case MCMA:
			case GLOBAL_MULTIPLE_ANSWER:
				$this->answer = new ScormAnswerMultipleChoice($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case TF:
				$this->answer = new ScormAnswerTrueFalse($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case FIB:
				$this->answer = new ScormAnswerFillInBlanks($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case MATCHING:
			case MATCHING_DRAGGABLE:
			case DRAGGABLE:
				$this->answer = new ScormAnswerMatching($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case ORAL_EXPRESSION:
			case FREE_ANSWER:
				$this->answer = new ScormAnswerFree($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case HOT_SPOT:
				$this->answer = new ScormAnswerHotspot($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case MULTIPLE_ANSWER_COMBINATION:
				$this->answer = new ScormAnswerMultipleChoice($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case HOT_SPOT_ORDER:
				$this->answer = new ScormAnswerHotspot($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			case HOT_SPOT_DELINEATION:
				$this->answer = new ScormAnswerHotspot($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			// not supported
			case UNIQUE_ANSWER_NO_OPTION:
			case MULTIPLE_ANSWER_TRUE_FALSE:
			case MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE:
			case UNIQUE_ANSWER_IMAGE:
			case CALCULATED_ANSWER:
				$this->answer = new ScormAnswerMultipleChoice($this->id);
				$this->answer->questionJSId = $this->js_id;
				break;
			default:
				$this->answer = new stdClass();
				$this->answer->questionJSId = $this->js_id;
				break;
		}

		return true;
	}

	function export()
	{
		$html = $this->getQuestionHTML();
		$js = $this->getQuestionJS();

		if (is_object($this->answer) && $this->answer instanceof Answer) {
			list($js2, $html2) = $this->answer->export();
			$js .= $js2;
			$html .= $html2;
		} else {
			throw new \Exception('Question not supported. Exercise: '.$this->selectTitle());
		}

		return array($js, $html);
	}

	function createAnswersForm($form)
	{
		return true;
	}

	function processAnswersCreation($form)
	{
		return true;
	}

	/**
	 * Returns an HTML-formatted question
	 */
	function getQuestionHTML()
	{
        $title = $this->selectTitle();
        $description = $this->selectDescription();
		$cols = 2;
		$s = '<tr>
			<td colspan="'.$cols.'" id="question_'.$this->id.'_title" valign="middle" style="background-color:#d6d6d6;">
			'.$title.'
			</td>
			</tr>
			<tr>
			<td valign="top" colspan="'.$cols.'">
			<i>'.$description.'</i>
			</td>
			</tr>';
		return $s;
	}

	/**
	 * Return the JavaScript code bound to the question
	 */
	function getQuestionJS()
	{
		$w = $this->selectWeighting();
		$s = 'questions.push('.$this->js_id.');'."\n";
        if ($this->type == HOT_SPOT) {
            //put the max score to 0 to avoid discounting the points of
            //non-exported quiz types in the SCORM
            $w = 0;
        }
		$s .= 'questions_score_max['.$this->js_id.'] = '.$w.";";

		return $s;
	}
}

/**
 * This class handles the export to SCORM of a multiple choice question
 * (be it single answer or multiple answers)
 * @package chamilo.exercise.scorm
 */
class ScormAnswerMultipleChoice extends Answer
{
	/**
	 * Return HTML code for possible answers
	 */
	function export()
	{
		$js = '';
		$html = '<tr><td colspan="2"><table width="100%">';
		$type = $this->getQuestionType();
		$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();';
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;';

		//not sure if we are going to export also the MULTIPLE_ANSWER_COMBINATION to SCORM
		//if ($type == MCMA  || $type == MULTIPLE_ANSWER_COMBINATION ) {
		if ($type == MCMA) {
			//$questionTypeLang = get_lang('MultipleChoiceMultipleAnswers');
			$id = 1;
			$jstmp = '';
			$jstmpc = '';
            foreach ($this->answer as $i => $answer) {
				$identifier = 'question_'.$this->questionJSId.'_multiple_'.$i;
				$html .=
					'<tr>
					<td align="center" width="5%">
					<input name="'.$identifier.'" id="'.$identifier.'" value="'.$i.'" type="checkbox" />
					</td>
					<td width="95%">
					<label for="'.$identifier.'">' . $this->answer[$i] . '</label>
					</td>
					</tr>';

				$jstmp .= $i.',';
                if ($this->correct[$i]) {
                    $jstmpc .= $i.',';
                }
				$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$this->weighting[$i].";\n";
				$id++;
			}
			$js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp,0,-1).');'."\n";
			$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.substr($jstmpc,0,-1).');'."\n";
			if ($type == MCMA) {
				$js .= 'questions_types['.$this->questionJSId.'] = \'mcma\';'."\n";
			} else {
				$js .= 'questions_types['.$this->questionJSId.'] = \'exact\';'."\n";
			}
			$js .= $jstmpw;
		} elseif ($type == MULTIPLE_ANSWER_COMBINATION) {
			//To this items we show the ThisItemIsNotExportable
			$qId = $this->questionJSId;
			$js = '';
			$html = '<tr><td colspan="2"><table width="100%">';
			// some javascript must be added for that kind of questions
			$html .= '<tr>
				<td>
				<textarea name="question_'.$qId.'_free" id="question_'.$qId.'_exact" rows="20" cols="100"></textarea>
				</td>
				</tr>';
			$html .= '</table></td></tr>';
			// currently the exact answers cannot be displayed, so ignore the textarea
			$html = '<tr><td colspan="2">'.get_lang('ThisItemIsNotExportable').'</td></tr>';
			$js .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
			$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
			$js .= 'questions_types['.$this->questionJSId.'] = \'exact\';'."\n";
			$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'.";\n";
			$js .= $jstmpw;
			return array($js, $html);
		} else {
			//$questionTypeLang = get_lang('MultipleChoiceUniqueAnswer');
			$id = 1;
			$jstmp = '';
			$jstmpc = '';
            foreach ($this->answer as $i => $answer) {
                $identifier = 'question_'.$this->questionJSId.'_unique_'.$i;
				$identifier_name = 'question_'.$this->questionJSId.'_unique_answer';
				$html .=
					'<tr>
					<td align="center" width="5%">
					<input name="'.$identifier_name.'" id="'.$identifier.'" value="'.$i.'" type="radio"/>
					</td>
					<td width="95%">
					<label for="'.$identifier.'">' . $this->answer[$i] . '</label>
					</td>
					</tr>';
				$jstmp .= $i.',';
                if ($this->correct[$i]) {
                    $jstmpc .= $i;
                }
				$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$this->weighting[$i].";";
				$id++;
			}
			$js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp,0,-1).');';
			$js .= 'questions_answers_correct['.$this->questionJSId.'] = '.$jstmpc.';';
			$js .= 'questions_types['.$this->questionJSId.'] = \'mcua\';';
			$js .= $jstmpw;
		}
		$html .= '</table></td></tr>';

		return array($js, $html);
	}
}

/**
 * This class handles the SCORM export of true/false questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerTrueFalse extends Answer
{
	/**
	 * Return the XML flow for the possible answers.
	 * That's one <response_lid>, containing several <flow_label>
	 *
	 * @author Amand Tihon <amand@alrj.org>
	 */
	function export()
	{
		$js = '';
		$html = '<tr><td colspan="2"><table width="100%">';
		$identifier = 'question_'.$this->questionJSId.'_tf';
		$identifier_true  = $identifier.'_true';
		$identifier_false = $identifier.'_false';
		$html .=
			'<tr>
				<td align="center" width="5%">
				<input name="'.$identifier_true.'" id="'.$identifier_true.'" value="'.$this->trueGrade.'" type="radio" />
				</td>
				<td width="95%">
				<label for="'.$identifier_true.'">' . get_lang('True') . '</label>
				</td>
				</tr>';
		$html .=
			'<tr>
			<td align="center" width="5%">
			<input name="'.$identifier_false.'" id="'.$identifier_false.'" value="'.$this->falseGrade.'" type="radio" />
			</td>
			<td width="95%">
			<label for="'.$identifier_false.'">' . get_lang('False') . '</label>
			</td>
			</tr></table></td></tr>';
		$js .= 'questions_answers['.$this->questionJSId.'] = new Array(\'true\',\'false\');'."\n";
		$js .= 'questions_types['.$this->questionJSId.'] = \'tf\';'."\n";
		if ($this->response === 'TRUE') {
			$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array(\'true\');'."\n";
		} else {
			$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array(\'false\');'."\n";
		}
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = '.$this->weighting[1].";\n";
		$js .= $jstmpw;

		return array($js, $html);
	}
}

/**
 * This class handles the SCORM export of fill-in-the-blanks questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerFillInBlanks extends Answer
{
	/**
	 * Export the text with missing words.
	 *
	 * As a side effect, it stores two lists in the class :
	 * the missing words and their respective weightings.
	 */
	function export()
	{
		global $charset;
		$js = '';
		$html = '<tr><td colspan="2"><table width="100%">';
		// get all enclosed answers
		$blankList = array();
		// build replacement
		$replacementList = array();
		foreach ($this->answer as $i => $answer) {
			$blankList[] = '['.$answer.']';
		}
		$answerCount = count($blankList);

		// splits text and weightings that are joined with the character '::'
		list($answer,$weight)=explode('::',$answer);
		$weights = explode(',',$weight);
		// because [] is parsed here we follow this procedure:
		// 1. find everything between the [ and ] tags
		$i=1;
		$jstmp = '';
		$jstmpc = '';
		$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
		$startlocations=api_strpos($answer,'[');
		$endlocations=api_strpos($answer,']');
		while ($startlocations !== false && $endlocations !== false) {
			$texstring = api_substr($answer,$startlocations,($endlocations-$startlocations)+1);
			$answer = api_substr_replace(
                $answer,
                '<input type="text" name="question_'.$this->questionJSId.'_fib_'.$i.'" id="question_'.$this->questionJSId.'_fib_'.$i.'" size="10" value="" />',
                $startlocations,
                ($endlocations-$startlocations)+1
            );
            $jstmp .= $i.',';
            $jstmpc .= "'".api_htmlentities(api_substr($texstring, 1, -1), ENT_QUOTES, $charset)."',";
            $my_weight = explode('@', $weights[$i - 1]);
            if (count($my_weight) == 2) {
                $weight_db = $my_weight[0];
            } else {
                $weight_db = $my_weight[0];
            }
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.']['.$i.'] = '.$weight_db.";\n";
			$i++;
			$startlocations = api_strpos($answer, '[');
			$endlocations = api_strpos($answer, ']');
		}

		$html .= '<tr>
			<td>
			'.$answer.'
			</td>
			</tr></table></td></tr>';
		$js .= 'questions_answers['.$this->questionJSId.'] = new Array('.api_substr($jstmp,0,-1).');'."\n";
		$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.api_substr($jstmpc,0,-1).');'."\n";
		$js .= 'questions_types['.$this->questionJSId.'] = \'fib\';'."\n";
		$js .= $jstmpw;

		return array($js,$html);
	}
}

/**
 * This class handles the SCORM export of matching questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerMatching extends Answer
{
	/**
	 * Export the question part as a matrix-choice, with only one possible answer per line.
	 * @author Amand Tihon <amand@alrj.org>
	 */
	function export()
	{
		$js = '';
		$html = '<tr><td colspan="2"><table width="100%">';
		// prepare list of right proposition to allow
		// - easiest display
		// - easiest randomisation if needed one day
		// (here I use array_values to change array keys from $code1 $code2 ... to 0 1 ...)

		// get max length of displayed array

		$nbrAnswers = $this->selectNbrAnswers();
		$cpt1='A';
		$cpt2=1;
		$Select = array();
		$qId = $this->questionJSId;
		$s = '';
		$jstmp = '';
		$jstmpc = '';
		$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
		$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";

		for ($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
			$identifier = 'question_'.$qId.'_matching_';
			$answer=$this->selectAnswer($answerId);
			$answerCorrect=$this->isCorrect($answerId);
			$weight=$this->selectWeighting($answerId);
			$jstmp .= $answerId.',';

			if (!$answerCorrect) {
				// options (A, B, C, ...) that will be put into the list-box
				$Select[$answerId]['Lettre']=$cpt1;
				// answers that will be shown at the right side
				$Select[$answerId]['Reponse'] = $answer;
				$cpt1++;
			} else {
				$s.='<tr>';
				$s.='<td width="40%" valign="top"><b>'.$cpt2.'</b>.&nbsp;'.$answer."</td>";
				$s.='<td width="20%" align="center">&nbsp;&nbsp;<select name="'.$identifier.$cpt2.'" id="'.$identifier.$cpt2.'">';
				$s.=' <option value="0">--</option>';
				// fills the list-box
                foreach ($Select as $key => $val) {
                    $s .= '<option value="'.$key.'">'.$val['Lettre'].'</option>';
                }  // end foreach()

				$s.='</select>&nbsp;&nbsp;</td>';
				$s.='<td width="40%" valign="top">';
                if (isset($Select[$cpt2])) {
                    $s .= '<b>'.$Select[$cpt2]['Lettre'].'.</b> '.$Select[$cpt2]['Reponse'];
                } else {
                    $s .= '&nbsp;';
                }
				$s.="</td></tr>";

				$jstmpc .= '['.$answerCorrect.','.$cpt2.'],';

                $my_weight = explode('@', $weight);
                if (count($my_weight) == 2) {
                    $weight = $my_weight[0];
                } else {
                    $weight = $my_weight[0];
                }
				$jstmpw .= 'questions_answers_ponderation['.$qId.']['.$cpt2.'] = '.$weight.";\n";
				$cpt2++;

				// if the left side of the "matching" has been completely shown
				if ($answerId == $nbrAnswers) {
					// if there remain answers to be shown on the right side
					while (isset($Select[$cpt2])) {
						$s.= '<tr>';
						$s.= '<td width="60%" colspan="2">&nbsp;</td>';
						$s.= '<td width="40%" valign="top">';
						$s.= '<b>'.$Select[$cpt2]['Lettre'].'.</b> '.$Select[$cpt2]['Reponse'];
						$s.= "</td></tr>";
						$cpt2++;
					}	// end while()
				}  // end if()
			}
		}
		$js .= 'questions_answers['.$this->questionJSId.'] = new Array('.substr($jstmp,0,-1).');'."\n";
		$js .= 'questions_answers_correct['.$this->questionJSId.'] = new Array('.substr($jstmpc,0,-1).');'."\n";
		$js .= 'questions_types['.$this->questionJSId.'] = \'matching\';'."\n";
		$js .= $jstmpw;
		$html .= $s;
		$html .= '</table></td></tr>' . "\n";

		return array($js, $html);
	}
}

/**
 * This class handles the SCORM export of free-answer questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerFree extends Answer
{
	/**
	 * Export the text with missing words.
	 *
	 * As a side effect, it stores two lists in the class :
	 * the missing words and their respective weightings.
	 *
	 */
	function export()
	{
		$js = '';

        $identifier = 'question_'.$this->questionJSId.'_free';
		// currently the free answers cannot be displayed, so ignore the textarea
		$html = '<tr><td colspan="2">';
        $html .= '<textarea minlength="20" name="'.$identifier.'" id="'.$identifier.'" ></textarea>';
        $html .= '</td></tr>';
        $score = $this->selectWeighting(1);
		$js .= 'questions_answers['.$this->questionJSId.'] = new Array();';
		$js .= 'questions_answers_correct['.$this->questionJSId.'] = "";';
		$js .= 'questions_types['.$this->questionJSId.'] = \'free\';';
		$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = "'.$score.'";';
		$js .= $jstmpw;

		return array($js, $html);
	}
}
/**
 * This class handles the SCORM export of hotpot questions
 * @package chamilo.exercise.scorm
 */
class ScormAnswerHotspot extends Answer
{
	/**
	 * Returns the javascript code that goes with HotSpot exercises
	 * @return string	The JavaScript code
	 */
	function get_js_header()
	{
		if ($this->standalone) {
			$header = '<script>';
			$header .= file_get_contents('../inc/lib/javascript/hotspot/js/hotspot.js');
			$header .= '</script>';
			//because this header closes so many times the <script> tag, we have to reopen our own
			$header .= '<script>';
			$header .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
			$header .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
			$header .= 'questions_types['.$this->questionJSId.'] = \'hotspot\';'."\n";
			$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'.";\n";
			$header .= $jstmpw;
		} else {
			$header = '';
			$header .= 'questions_answers['.$this->questionJSId.'] = new Array();'."\n";
			$header .= 'questions_answers_correct['.$this->questionJSId.'] = new Array();'."\n";
			$header .= 'questions_types['.$this->questionJSId.'] = \'hotspot\';'."\n";
			$jstmpw = 'questions_answers_ponderation['.$this->questionJSId.'] = new Array();'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][0] = 0;'."\n";
			$jstmpw .= 'questions_answers_ponderation['.$this->questionJSId.'][1] = 0;'."\n";
			$header .= $jstmpw;
		}

		return $header;
	}
	/**
	 * Export the text with missing words.
	 *
	 * As a side effect, it stores two lists in the class :
	 * the missing words and their respective weightings.
	 *
	 */
	function export()
	{
		$js = $this->get_js_header();
		$html = '<tr><td colspan="2"><table width="100%">';
		// some javascript must be added for that kind of questions
		$html .= '';

		// Get the answers, make a list
		$nbrAnswers=$this->selectNbrAnswers();

		$answer_list = '<div style="padding: 10px; margin-left: -8px; border: 1px solid #4271b5; height: 448px; width: 200px;"><b>'.get_lang('HotspotZones').'</b><ol>';
        for ($answerId = 1; $answerId <= $nbrAnswers; $answerId++) {
            $answer_list .= '<li>'.$this->selectAnswer($answerId).'</li>';
        }
        $answer_list .= '</ol></div>';
		$canClick = true;
		$relPath = api_get_path(REL_PATH);
		$html .= <<<HTML
            <tr>
                <td>
                    <div id="hotspot-{$this->questionJSId}"></div>
                    <script>
                        document.addEventListener('DOMContentListener', function () {
                            new HotspotQuestion({
                                questionId: {$this->questionJSId},
                                selector: '#hotspot-{$this->questionJSId}',
                                for: 'user',
                                relPath: '$relPath'
                            });
                        });
                    </script>
                </td>
                <td>
                    $answer_list
                </td>
            <tr>
HTML;
		$html .= '</table></td></tr>';

		// currently the free answers cannot be displayed, so ignore the textarea
		$html = '<tr><td colspan="2">'.get_lang('ThisItemIsNotExportable').'</td></tr>';

		return array($js, $html);
	}
}

/**
 * A SCORM item. It corresponds to a single question.
 * This class allows export from Dokeos SCORM 1.2 format of a single question.
 * It is not usable as-is, but must be subclassed, to support different kinds of questions.
 *
 * Every start_*() and corresponding end_*(), as well as export_*() methods return a string.
 *
 * Attached files are NOT exported.
 * @package chamilo.exercise.scorm
 */
class ScormAssessmentItem
{
	public $question;
	public $question_ident;
	public $answer;
	public $standalone;

	/**
	 * Constructor.
	 *
	 * @param ScormQuestion $question The Question object we want to export.
	 */
	public function __construct($question, $standalone = false)
	{
		$this->question = $question;
		$this->question->setAnswer();
		$this->questionIdent = "QST_" . $question->id ;
		$this->standalone = $standalone;
	}

	/**
	 * Start the XML flow.
	 *
	 * This opens the <item> block, with correct attributes.
	 *
	 */
	function start_page()
	{
		$charset = 'UTF-8';
        $head = '';
        if ($this->standalone) {
            $head = '<?xml version="1.0" encoding="'.$charset.'" standalone="no"?>';
            $head .= '<html>';
        }
		return $head;
	}

	/**
	 * End the XML flow, closing the </item> tag.
	 *
	 */
	function end_page()
    {
		if ($this->standalone) {
			return '</html>';
		}

		return '';
	}

	/**
	 * Start document header
	 */
	function start_header()
	{
		if ($this->standalone) {
			return '<head>';
		}

		return '';
	}

	/**
	 * Print CSS inclusion
	 */
	function css()
	{
		$css = '';
		if ($this->standalone) {
			$css = '<style type="text/css" media="screen, projection">';
			$css .= '/*<![CDATA[*/'."\n";
			$css .= '/*]]>*/'."\n";
			$css .= '</style>'."\n";
			$css .= '<style type="text/css" media="print">';
			$css .= '/*<![CDATA[*/'."\n";
			$css .= '/*]]>*/'."\n";
			$css .= '</style>';
		}
		return $css;
	}

	/**
	 * End document header
	 */
	function end_header()
	{
		if ($this->standalone) {
			return '</head>';
		}

		return '';
	}
	/**
	 * Start the itemBody
	 *
	 */
	function start_js()
	{
		if ($this->standalone) {
			return '<script>';
		}
		return '';
	}

	/**
	 * Common JS functions
	 */
	function common_js()
	{
		$js = file_get_contents('../lp/js/api_wrapper.js');
		$js .= 'var questions = new Array();';
		$js .= 'var questions_answers = new Array();';
		$js .= 'var questions_answers_correct = new Array();';
		$js .= 'var questions_types = new Array();';
		$js .= "\n" .
			'/**
             * Assigns any event handler to any element
             * @param	object	Element on which the event is added
             * @param	string	Name of event
             * @param	string	Function to trigger on event
             * @param	boolean	Capture the event and prevent
             */
            function addEvent(elm, evType, fn, useCapture)
            { //by Scott Andrew
                if(elm.addEventListener){
            		elm.addEventListener(evType, fn, useCapture);
            		return true;
            	} else if(elm.attachEvent) {
            		var r = elm.attachEvent(\'on\' + evType, fn);
            		return r;
            	} else {
            		elm[\'on\' + evType] = fn;
            	}
            }
            /**
             * Adds the event listener
             */
            function addListeners(e) {
            	loadPage();
            	/*
            	var my_form = document.getElementById(\'dokeos_scorm_form\');
            	addEvent(my_form,\'submit\',checkAnswers,false);
            	*/
            	var my_button = document.getElementById(\'dokeos_scorm_submit\');
            	addEvent(my_button,\'click\',doQuit,false);
            	//addEvent(my_button,\'click\',checkAnswers,false);
            	//addEvent(my_button,\'change\',checkAnswers,false);
            	addEvent(window,\'unload\',unloadPage,false);
            }'."\n\n";

		$js .= '';
		$js .= 'addEvent(window,\'load\',addListeners,false);'."\n";
        if ($this->standalone) {
            return $js."\n";
        }
		return '';
	}

	/**
	 * End the itemBody part.
	 *
	 */
	function end_js()
	{
        if ($this->standalone) {
            return '</script>';
        }

        return '';
	}

	/**
	 * Start the itemBody
	 *
	 */
	function start_body()
	{
		if ($this->standalone) {
			return '<body><form id="dokeos_scorm_form" method="post" action="">';
		}

		return '';
	}

	/**
	 * End the itemBody part.
	 *
	 */
	function end_body()
	{
		if ($this->standalone) {
			return '<br /><input type="button" id="dokeos_scorm_submit" name="dokeos_scorm_submit" value="OK" /></form></body>';
		}

		return '';
	}

	/**
	 * Export the question as a SCORM Item.
	 *
	 * This is a default behaviour, some classes may want to override this.
	 *
	 * @param $standalone: Boolean stating if it should be exported as a stand-alone question
	 * @return A string, the XML flow for an Item.
	 */
	function export()
	{
		$js = $html = '';
		list($js,$html) = $this->question->export();
		if ($this->standalone) {
			$res = $this->start_page()
				. $this->start_header()
				. $this->css()
				. $this->start_js()
				. $this->common_js()
				. $js
				. $this->end_js()
				. $this->end_header()
				. $this->start_body()
                . $html
				. $this->end_body()
				. $this->end_page();
			return $res;
		} else {
			return array($js, $html);
		}
	}
}

/**
 * This class represents an entire exercise to be exported in SCORM.
 * It will be represented by a single <section> containing several <item>.
 *
 * Some properties cannot be exported, as SCORM does not support them :
 *   - type (one page or multiple pages)
 *   - start_date and end_date
 *   - max_attempts
 *   - show_answer
 *   - anonymous_attempts
 * @package chamilo.exercise.scorm
 */
class ScormSection
{
	public $exercise;
	public $standalone;

	/**
	 * Send a complete exercise in SCORM format, from its ID
	 *
	 * @param int $exerciseId The exercise to exporte
	 * @param boolean $standalone Wether it should include XML tag and DTD line.
	 * @return string XML as a string, or an empty string if there's no exercise with given ID.
	 */
	public static function export_exercise_to_scorm($exerciseId, $standalone = true)
	{
		$exercise = new Exercise();
		if (!$exercise->read($exerciseId)) {
			return '';
		}
		$ims = new ScormSection($exercise);
		$xml = $ims->export($standalone);

		return $xml;
	}

	/**
	 * Constructor.
	 * @param Exercise $exe The Exercise instance to export
	 * @author Amand Tihon <amand@alrj.org>
	 */
	public function __construct($exe)
	{
		$this->exercise = $exe;
	}

	/**
	 * Start the XML flow.
	 *
	 * This opens the <item> block, with correct attributes.
	 *
	 */
	function start_page()
	{
		global $charset;
		$head = '<?xml version="1.0" encoding="'.$charset.'" standalone="no"?><html>';

		return $head;
	}

	/**
	 * End the XML flow, closing the </item> tag.
	 *
	 */
	function end_page()
	{
		return '</html>';
	}

	/**
	 * Start document header
	 */
	function start_header()
	{
		return '<head>';
	}

	/**
	 * Print CSS inclusion
	 */
	function css()
	{
		$css = '<style type="text/css" media="screen, projection">';
		$css .= '/*<![CDATA[*/'."\n";
		$css .= '/*]]>*/'."\n";
		$css .= '</style>'."\n";
		$css .= '<style type="text/css" media="print">';
		$css .= '/*<![CDATA[*/'."\n";
		$css .= '/*]]>*/'."\n";
		$css .= '</style>';

		return $css;
	}

	/**
	 * End document header
	 */
	function end_header()
	{
		return '</head>';
	}

	/**
	 * Start the itemBody
	 *
	 */
	function start_js()
	{
		return '<script>';
	}

	/**
	 * Common JS functions
	 */
	function common_js()
	{
		$js = "\n";
		$js .= file_get_contents('../inc/lib/javascript/hotspot/js/hotspot.js');
		$js .= file_get_contents('../lp/js/api_wrapper.js');
		$js .= 'var questions = new Array();' . "\n";
		$js .= 'var questions_answers = new Array();' . "\n";
		$js .= 'var questions_answers_correct = new Array();' . "\n";
		$js .= 'var questions_types = new Array();' . "\n";
		$js .= "\n" .
			'/**
             * Assigns any event handler to any element
             * @param	object	Element on which the event is added
             * @param	string	Name of event
             * @param	string	Function to trigger on event
             * @param	boolean	Capture the event and prevent
             */
            function addEvent(elm, evType, fn, useCapture)
            { //by Scott Andrew
                if(elm.addEventListener){
            		elm.addEventListener(evType, fn, useCapture);
            		return true;
            	} else if(elm.attachEvent) {
            		var r = elm.attachEvent(\'on\' + evType, fn);
            		return r;
            	} else {
            		elm[\'on\' + evType] = fn;
            	}
            }
            /**
             * Adds the event listener
             */
            function addListeners(e) {
            	loadPage();
            	/*
            	var my_form = document.getElementById(\'dokeos_scorm_form\');
            	addEvent(my_form,\'submit\',checkAnswers,false);
            	*/
            	var my_button = document.getElementById(\'dokeos_scorm_submit\');
            	addEvent(my_button,\'click\',doQuit,false);
                addEvent(my_button,\'click\',disableButton,false);
            	//addEvent(my_button,\'click\',checkAnswers,false);
            	//addEvent(my_button,\'change\',checkAnswers,false);
            	addEvent(window,\'unload\',unloadPage,false);
            }
            /** Disables the submit button on SCORM result submission **/
            function disableButton() {
              var mybtn = document.getElementById(\'dokeos_scorm_submit\');
              mybtn.setAttribute(\'disabled\',\'disabled\');
            }
            '."\n";

		$js .= '';
		$js .= 'addEvent(window,\'load\',addListeners,false);'."\n";
		return $js. "\n";
	}

	/**
	 * End the itemBody part.
	 *
	 */
	function end_js()
    {
		return '</script>';
	}

	/**
	 * Start the itemBody
	 *
	 */
	function start_body()
	{
		return '<body>'.
		'<h1>'.$this->exercise->selectTitle().'</h1><p>'.$this->exercise->selectDescription()."</p>".
		'<form id="dokeos_scorm_form" method="post" action="">'.
		'<table width="100%">';
	}

	/**
	 * End the itemBody part.
	 *
	 */
	function end_body()
	{
		return '</table><br /><input type="button" id="dokeos_scorm_submit" name="dokeos_scorm_submit" value="OK" /></form></body>';
	}

	/**
	 * Export the question as a SCORM Item.
	 *
	 * This is a default behaviour, some classes may want to override this.
	 *
	 * @param $standalone: Boolean stating if it should be exported as a stand-alone question
	 * @return string string, the XML flow for an Item.
	 */
	function export()
	{
		global $charset;

		$head = '';
		if ($this->standalone) {
			$head = '<?xml version = "1.0" encoding = "' . $charset . '" standalone = "no"?>' . "\n"
				. '<!DOCTYPE questestinterop SYSTEM "ims_qtiasiv2p1.dtd">' . "\n";
		}

		list($js, $html) = $this->export_questions();
		$res = $this->start_page()
			. $this->start_header()
			. $this->css()
			. $this->start_js()
			. $this->common_js()
			. $js
			. $this->end_js()
			. $this->end_header()
			. $this->start_body()
			. $html
			. $this->end_body()
			. $this->end_page();

		return $res;
	}

	/**
	 * Export the questions, as a succession of <items>
	 * @author Amand Tihon <amand@alrj.org>
	 */
	function export_questions()
	{
		$js = $html = "";
		$js_id = 0;
		foreach ($this->exercise->selectQuestionList() as $q) {
			list($jstmp,$htmltmp)= ScormQuestion::export_question($q, false, $js_id);
			$js .= $jstmp."\n";
			$html .= $htmltmp."\n";
			++$js_id;
		}

		return array($js, $html);
	}
}