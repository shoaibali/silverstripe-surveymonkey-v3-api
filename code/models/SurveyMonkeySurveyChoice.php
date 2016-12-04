<?php

class SurveyMonkeySurveyChoice extends DataObject {
	

	static $api_access = false;

    private static $db = array(
        'ChoiceID' => 'Int',
        'Position' => 'Int',
        'Text' => 'Varchar(255)',
        'Visible' => 'Boolean'
    );

	private static $field_labels = array(
		'Text' => 'Choice'
	);

	private static $summary_fields = array(
		'ChoiceID',
		'Text'
	);
	
	private static $has_one = array(
		'SurveyMonkeySurveyQuestion' => 'SurveyMonkeySurveyQuestion',
		'SurveyMonkeySurveyAnswer' => 'SurveyMonkeySurveyAnswer'
	);
}