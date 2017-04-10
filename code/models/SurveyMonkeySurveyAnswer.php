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

}