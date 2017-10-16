<?php

use Spliced\SurveyMonkey\Client;


class SurveyMonkeyManager extends SurveyMonkeyPage {

    static $allowed_actions = array(
        'Form',
        'createEmailCollector'
    );

    public function init() {
        parent::init();
        if(!Permission::check('ADMIN')) Security::permissionFailure();
    }

    public function Title() {
        return "SurveyMonkey Manager";
    }

    public function index() {
        return $this->renderWith(array("SurveyMonkeyManager", "Page"));
    }




    public function Form() {

        $fields = new FieldList();


        if (!array_key_exists("error", $surveys = $this->owner->getSurveys())) {


        	$s = array();
        	
        	foreach($surveys as $sk => $sv) {
        		$s[$sv->ID] = $sv->Title;
        	}

			$d = DropdownField::create('SuveyMonkeySurveys', 'Survey', $s);
			
			$d->setEmptyString('(Select survey)');

			$fields->push($d);
		}

        //TODO No point in showing submit button if there are no surveys to import or errors
        return new Form($this, "Form", $fields, new FieldList(
            new FormAction("sendEmail", "Send Email Invitation"),
            new FormAction("createEmailCollector", "Create Email Collector")
        ));
    }


}
