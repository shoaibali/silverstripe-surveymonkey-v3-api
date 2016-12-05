<?php

class SurveyMonkeySurvey extends DataObject implements PermissionProvider  {
	

	static $api_access = false;

    private static $db = array(
        'SurveyID' => 'Varchar',
        'Title' => 'Varchar(255)',
        'ResponsesCount' => 'Int',
        'QuestionCount' => 'Int',
        'PageCount' => 'Int',
    );

	private static $field_labels = array(
		'QuestionCount' => 'Questions',
		'ResponsesCount' => 'Responses'
	);

	private static $summary_fields = array(
		'SurveyID',
		'Title',
		'ResponsesCount'
	);

	private static $has_many = array(
		'SurveyMonkeySurveyQuestions' => 'SurveyMonkeySurveyQuestion'
	);


	function canView($member = false) {
		return Permission::check('SURVEY_VIEW');
	}

	function canEdit($member = false) {
		return Permission::check('SURVEY_EDIT');
	}

	function canDelete($member = false) {
		return Permission::check('SURVEY_DELETE');
	}

	function canCreate($member = false) {
		return Permission::check('SURVEY_CREATE');
	}

	function providePermissions() {
		return array(
			'SURVEY_VIEW' => 'Read an survey object',
			'SURVEY_EDIT' => 'Edit an survey object',
			'SURVEY_DELETE' => 'Delete an survey object',
			'SURVEY_CREATE' => 'Create an survey object',
		);
	}

}