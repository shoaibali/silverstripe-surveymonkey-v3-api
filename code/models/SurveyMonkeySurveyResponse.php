<?php

class SurveyMonkeySurveyResponse extends DataObject {
	

	static $api_access = false;

    private static $db = array(
    	'ResponseID' => 'Varchar',
    	'SurveyID' => 'Varchar',
        'CollectorID' => 'Varchar',
        'RecipientID' => 'Varchar',
        'IPAddress' => 'Varchar',
        'ResponseStatus' => 'Varchar',
        'EmailAddress' => 'Varchar',
        'TotalTime' => 'Int'
    );

	private static $field_labels = array(
		'ResponseID' => 'ResponseID',
		'SurveyID' => 'SurveyID',
		'Text' => 'Comments',
		'SurveyMonkeySurveyChoice.Text' =>  'Choice'
	);

	private static $summary_fields = array(
		'ResponseID',
		'SurveyID',
		'CollectorID',
		'EmailAddress'
	);
	

	private static $has_one = array(
		'SurveyMonkeySurvey' => 'SurveyMonkeySurvey',
		'SurveyMonkeySurveyCollector' => 'SurveyMonkeySurveyCollector'
	);

	private static $has_many = array(
		'SurveyMonkeySurveyAnswers' => 'SurveyMonkeySurveyAnswer'
	);


	public function getTitle()
	{
		return $this->EmailAddress;
	}

}