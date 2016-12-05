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
		'SurveyMonkeySurveyChoice.Text'
	);
	
	private static $belongs_to = array(
		'SurveyMonkeySurveyChoice' => 'SurveyMonkeySurveyChoice.SurveyMonkeySurveyAnswer',
		'SurveyMonkeySurveyCollector' => 'SurveyMonkeySurveyCollector.SurveyMonkeySurveyAnswer'
	);

	private static $has_one = array(
		'SurveyMonkeySurveyChoice' => 'SurveyMonkeySurveyChoice',
		'SurveyMonkeySurveyCollector' => 'SurveyMonkeySurveyCollector'
	);

	public function getSurveyMonkeySurveyChoice()
	{
		return $this->SurveyMonkeySurveyChoice()->Text;
	}


}