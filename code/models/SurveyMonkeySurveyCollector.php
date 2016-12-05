<?php

class SurveyMonkeySurveyCollector extends DataObject {
	

	static $api_access = false;

    private static $db = array(
        'CollectorID' => 'Varchar',
        'Name' => 'Varchar(255)',
        'Type' => 'Varchar(255)',
        'SurveyID' => 'Varchar',
        'ResponseCount' => 'Int',
        'Status' => 'Varchar'
    );

	private static $field_labels = array(
		'Name' => 'Title'
	);

	private static $summary_fields = array(
		'CollectorID',
		'Name'
	);

    private static $has_many = array(
        'SurveyMonkeySurveyChoices' => 'SurveyMonkeySurveyChoice',
        'SurveyMonkeySurveyAnswers' => 'SurveyMonkeySurveyAnswer'
    );

	private static $has_one = array(
		'SurveyMonkeySurvey' => 'SurveyMonkeySurvey'
	);
	
}