<?php

class SurveyMonkeySurveyQuestion extends DataObject {
	

	static $api_access = false;

    private static $db = array(
        'QuestionID' => 'Varchar',
        'Title' => 'Varchar(255)',
        'Position' => 'Int',
        'PageID' => 'Varchar(255)',
        'PageTitle' => 'Varchar(255)',
        'PageDescription' => 'Text',
        'PagePosition' => 'Int',
    );

	private static $field_labels = array(
		'Title' 		=> 'Questions',
		'PagePosition' 	=> 'Page position',
		'PageTitle' 	=> 'Page title',
	);

	private static $summary_fields = array(
		'QuestionID',
		'PagePosition',
		'PageID',
		'PageTitle',
		'Title'
	);

    private static $has_many = array(
        'SurveyMonkeySurveyChoices' => 'SurveyMonkeySurveyChoice'
    );

	private static $has_one = array(
		'SurveyMonkeySurvey' => 'SurveyMonkeySurvey'
	);

}
