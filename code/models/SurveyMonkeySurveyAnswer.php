<?php

class SurveyMonkeySurveyAnswer extends DataObject {
	

	static $api_access = false;

    private static $db = array(
    	'SurveyID' => 'Varchar',
        'ChoiceID' => 'Varchar',
        'RowID' => 'Varchar',
        'Text' => 'Varchar(255)'
    );

	private static $field_labels = array(
		'ChoiceID' => 'ChoiceID',
		'RowID' => 'RowID',
		'Text' => 'Comments',
		'SurveyMonkeySurveyChoice.Text' =>  'Choice'
	);

	private static $summary_fields = array(
		'ChoiceID',
		'RowID',
		'Text',
		'SurveyMonkeySurveyChoice.Text',
		'getSurveyMonkeySurveyChoice',
		'getSurveyMonkeySurveyQuestion.QuestionID',
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

	public function getSurveyMonkeySurveyChoice()
	{

		// TODO I should be able to just get this using the relationship
		if(!is_null($this->RowID)) {
			$choice = SurveyMonkeySurveyChoice::get()->filter(array("ChoiceID" => $this->RowID));
			return $choice->First()->Text;
		}

		return "";
	}


	public function getSurveyMonkeySurveyQuestion()
	{
		$question =  $this->SurveyMonkeySurveyChoice()->SurveyMonkeySurveyQuestion();
		return $question;
	}

}