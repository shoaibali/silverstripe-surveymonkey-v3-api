<?php

class SurveyMonkeyImporter extends Page_Controller {
	static $allowed_actions = array(
		'Form',
		'import',
		'complete'
	);

	public function init() {
		parent::init();
		if(!Permission::check('ADMIN')) Security::permissionFailure();
	}

	public function Title() {
		return "SurveyMonkey Importer";
	}

	public function index() {
		return $this->renderWith(array("Page", "SurveyMonkeyImporter"));
	}


	public function Content() {
		$msg = <<<HTML
		<p>This tool will let you import all SurveyMonkey surveys in to SilverStripe:</p>
HTML;

		 return $msg;
	}

	function Form() {
		$deleteExistingCheckBox = new CheckboxField("DeleteExisting", "Clear out all existing surveys?");
		$deleteExistingCheckBox->setValue(TRUE);

		return new Form($this, "Form", new FieldList(
			$deleteExistingCheckBox
		), new FieldList(
			new FormAction("import", "Begin Import")
		));
	}

	public function import($data, $form) {

		 if(isset($data['DeleteExisting']) && $data['DeleteExisting']) {
		 	// TODO Logic for deleting existing surveys will go in here

		 }

		 echo "work in progress"; die();
	}

	function complete() {
		return array(
			"Content" => "<p>All your surveys have been imported.</p>",
			"Form" => " ",
		);
	}



}