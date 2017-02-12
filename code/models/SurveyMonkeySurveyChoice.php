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
		'Text' => 'Choice'
	);

	private static $summary_fields = array(
		'ChoiceID',
		'Text',
		'Position',
	);
	
	private static $has_one = array(
		'SurveyMonkeySurveyQuestion' => 'SurveyMonkeySurveyQuestion',
	);

	public function getTitle(){
		return $this->Text;
	}
}