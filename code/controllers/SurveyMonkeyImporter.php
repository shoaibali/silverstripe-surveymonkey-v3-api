<?php

use Spliced\SurveyMonkey\Client;


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

		 $config = SiteConfig::current_site_config();

		 $client = new Client($config->SurveyMonkeyAccessToken, $config->SurveryMonkeyAccessCode);
		 $surveysResponse = $client->getSurveys();

		 // header('Content-Type: application/json');

		 $surveyID = array_pop($surveysResponse->getData()['data'])['id'];


		 echo "Survey ID => ";
		 print_r($surveyID);

		 echo "<br/>";

		 echo "Title => "; 
		 print_r(array_pop($surveysResponse->getData()['data'])['title']);

		 echo "<br/>";

		 echo "Questions => <br/>";

		 foreach($client->getSurveyPages($surveyID)->getData()['data'] as $pk) {

		 	foreach($client->getSurveyPageQuestions($surveyID, $pk['id'])->getData()['data'] as $questions){
		 		echo  $questions['id'] . "=> " . $questions['heading'] . "<br/>";

		 		foreach ($client->getSurveyPageQuestion($surveyID, $pk['id'], $questions['id'])->getData() as $q) {
		 			if (is_array($q)) {
		 				if (array_key_exists('choices', $q)) {
		 					foreach($q['choices'] as $c){
			 					echo $c['id'] . "-->" . $c['text'] . "<br/>";
		 					}
		 				}
		 			}
		 		}
		 	}

		 }

		 echo "<br/>";


		 echo "Survey Responses => ";
		 $answers = $client->getSurveyResponses(array_pop($surveysResponse->getData()['data'])['id']);

		 echo "<br/>";

		 foreach($answers->getData()['data'] as $a) 
		 {
		 	echo $a['id'] . "<br/>";

		 	// $clien->
		 }

		 echo "<br/>";

		 echo "Collector Responses => <br/>";

		 $collectors = $client->getCollectorsForSurvey($surveyID);

		 foreach($collectors->getData()['data'] as $c)
		 {

		 	if (is_array($c)) {

		 		$cresponses = $client->getCollectorResponses($c['id'], true);

		 		foreach($cresponses->getData()['data'] as $k => $v) {
		 			// var_dump($v['pages'][0]['questions']);
		 			foreach($v['pages'][0]['questions'] as $ck => $cv) {
		 				// var_dump($cv);
		 				echo "Choice ID: " . $cv['answers'][0]['choice_id'] . "<br/>";
		 			}
		 		}
		 	}

		 	// collector ids
		 	// echo $c['data']['id'] . "<br/>";
		 }


		 die();
	}

	function complete() {
		return array(
			"Content" => "<p>All your surveys have been imported.</p>",
			"Form" => " ",
		);
	}



}