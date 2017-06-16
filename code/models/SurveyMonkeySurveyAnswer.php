<?php

class SurveyMonkeySurveyAnswer extends DataObject {
	

	static $api_access = false;

    private static $db = array(
    	'AnswerID' => 'Varchar',
    	'SurveyID' => 'Varchar',
        'ChoiceID' => 'Varchar',
        'RowID' => 'Varchar',
        'Text' => 'Text'
    );

	private static $field_labels = array(
		'ChoiceID' => 'ChoiceID',
		'RowID' => 'RowID',
		'Text' => 'Comments',
		'getchoicetitle' => 'Answer',
		'SurveyMonkeySurveyChoice.Text' =>  'Choice',
	);

	private static $summary_fields = array(
		'ChoiceID',
		'RowID',
		'Text',
		'SurveyMonkeySurveyChoice.Text',
		'getChoiceTitle',
		'getSurveyMonkeySurveyQuestion.Title',
	);
	
	private static $belongs_to = array(
		'SurveyMonkeySurveyChoice' => 'SurveyMonkeySurveyChoice.SurveyMonkeySurveyAnswer',
		'SurveyMonkeySurveyCollector' => 'SurveyMonkeySurveyCollector.SurveyMonkeySurveyAnswer'
	);

	private static $has_one = array(
		'SurveyMonkeySurveyChoice' => 'SurveyMonkeySurveyChoice',
		'SurveyMonkeySurveyCollector' => 'SurveyMonkeySurveyCollector',
		'SurveyMonkeySurveyResponse' => 'SurveyMonkeySurveyResponse'
	);


	public function getSurveyMonkeySurveyQuestion()
	{
		$question =  $this->SurveyMonkeySurveyChoice()->SurveyMonkeySurveyQuestion();
		return $question;
	}

	public function getChoiceTitle()
	{
		$choice = SurveyMonkeySurveyChoice::get()->filter(array('ChoiceID' => $this->ChoiceID));

		if ($choice) {
			$choice = $choice->First()->Title;
		}

		return $choice;
	}

	public function isComment()
	{
		$choice = SurveyMonkeySurveyChoice::get()->filter(array('ChoiceID' => $this->ChoiceID));

		 return (stripos($choice->First()->Title, "comments") !== FALSE);
	}

	public function getComment()
	{
		// look for answers with this AnswerID and matching AnswerSection!

		$answers = SurveyMonkeySurveyAnswer::get()->filter(array(
			'AnswerID' => $this->AnswerID,
			'SurveyMonkeySurveyResponseID' => $this->SurveyMonkeySurveyResponseID));

		foreach($answers as $answer) {
			if (($answer->getAnswerSection() == $this->getAnswerSection())) {
				return $answer->Text;
			}
		}

		return "";
	}

	public function getAnswerSection()
	{
		$section = $this->SurveyMonkeySurveyChoice();
		return ($section->InterviewQuestions()->count())? $section->InterviewQuestions()->First()->QuestionCategory  : "";
	}

	public function getSurveyArea() 
	{
		$section = $this->SurveyMonkeySurveyChoice();
		return ($section->InterviewQuestions()->count())? $section->InterviewQuestions()->First()->SurveyArea  : "";
	}

}