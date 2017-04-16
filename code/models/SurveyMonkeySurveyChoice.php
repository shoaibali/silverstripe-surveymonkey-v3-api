<?php

class SurveyMonkeySurveyChoice extends DataObject {
	

	static $api_access = false;

    private static $db = array(
        'ChoiceID' => 'Varchar',
        'SurveyID' => 'Varchar',
        'Position' => 'Int',
        'Text' => 'Varchar(255)', /* Poor choice of field name */
        'Visible' => 'Boolean',
        'IsRow' => 'Boolean',
    );

	private static $field_labels = array(
		'Text' => 'Choice',
		'getQuestionPagePosition' => 'Question Page Position',
		'getPagePosition' => 'Page Position'
	);

	private static $summary_fields = array(
		'IsRow',
		'ChoiceID',
		'Text',
		'Position',
		'getQuestionPagePosition',
		'getPagePosition'
	);
	
	private static $has_one = array(
		'SurveyMonkeySurveyQuestion' => 'SurveyMonkeySurveyQuestion',
	);

	public function getTitle(){
		return $this->Text;
	}

	public function getQuestionPagePosition()
	{		
		return $this->SurveyMonkeySurveyQuestion()->PagePosition;
	}

	public function getPagePosition()
	{		
		return $this->SurveyMonkeySurveyQuestion()->Position;
	}

}