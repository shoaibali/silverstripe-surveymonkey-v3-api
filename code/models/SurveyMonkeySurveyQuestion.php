<?php

class SurveyMonkeySurveyQuestion extends DataObject {
	

	static $api_access = false;

    private static $db = array(
        'QuestionID' => 'Varchar',
        'Title' => 'Varchar(255)',
        'Position' => 'Int'
    );

	private static $field_labels = array(
		'Title' => 'Questions'
	);

	private static $summary_fields = array(
		'QuestionID',
		'Title'
	);

    private static $has_many = array(
        'SurveyMonkeySurveyChoices' => 'SurveyMonkeySurveyChoice'
    );

	private static $has_one = array(
		'SurveyMonkeySurvey' => 'SurveyMonkeySurvey'
	);

}