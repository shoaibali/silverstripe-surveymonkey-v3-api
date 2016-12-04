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
		return $this->renderWith(array("SurveyMonkeyImporter", "Page"));
	}


	public function Content() {
		$msg = <<<HTML
		<p>This tool will let you import all SurveyMonkey surveys in to SilverStripe:</p>
HTML;

		 return $msg;
	}

	function Form() {

		$fields = new FieldList();

		foreach($this->getSurveys() as $s) {
			$f = new CheckboxField("SurveyID-". $s->ID, $s->Title);
			$f->setValue(TRUE);

			$fields->push($f);
		}


		$deleteExistingCheckBox = new CheckboxField("DeleteExisting", "Clear out all existing surveys?");
		$deleteExistingCheckBox->setValue(TRUE);

		$fields->push($deleteExistingCheckBox);

		return new Form($this, "Form", $fields, new FieldList(
			new FormAction("import", "Begin Import")
		));
	}

	public function import($data, $form) {

		 $surveyIDs = self::getSelectedSurveyIDS($data);

		 self::deleteAllSurveysData($data);

		 $config = SiteConfig::current_site_config();

		 $client = new Client($config->SurveyMonkeyAccessToken, $config->SurveryMonkeyAccessCode);

		 foreach($surveyIDs as $si)
		 {
		 	$existingSurvey = SurveyMonkeySurvey::get()->filter(array('SurveyID' => $si))->First();

		 	if (!$existingSurvey) {

		 		// TODO could also get Pages here with second parameter to getSurvey
		 		$surveyResponse 	= $client->getSurvey($si)->getData();
		 		$pagesResponse 		= $client->getSurveyPages($si)->getData()['data'];
		 		$collectors 		= $client->getCollectorsForSurvey($si)->getData()['data'];

		 		// create a new survey
		 		$survey = new SurveyMonkeySurvey();
		 		$survey->SurveyID = $si;
		 		$survey->Title = $surveyResponse['title'];
		 		$survey->QuestionCount = $surveyResponse['question_count'];
		 		$survey->PageCount = $surveyResponse['page_count'];
		 		$survey->ResponsesCount = $surveyResponse['response_count'];
		 		$survey->write();

		 		/* IMPORT QUESTIONS */
		 		foreach($pagesResponse as $pk) {

		 			foreach($client->getSurveyPageQuestions($si, $pk['id'])->getData()['data'] as $questions){

		 				$question = new SurveyMonkeySurveyQuestion();
		 				$question->QuestionID 			= $questions['id'];
		 				$question->Title 				= $questions['heading'];
		 				$question->Position 			= $questions['position'];
		 				$question->SurveyMonkeySurveyID = $survey->ID;
		 				$question->write();

		 				/* IMPORT CHOICES */
		 				foreach($client->getSurveyPageQuestion($si, $pk['id'], $questions['id'])->getData() as $q) {
				 			if (is_array($q)) {

								/* * * CHOICES / COLUMNS * * */
				 				if (array_key_exists('rows', $q)) {
				 					foreach($q['rows'] as $r){
					 					// echo "ROW:" . $r['id'] . "-->" . $r['text'] . "<br/>";
										$choice = new SurveyMonkeySurveyChoice();
										$choice->ChoiceID 	= $r['id'];
										$choice->Position 	= $r['position'];
										$choice->Text 		= $r['text'];
										$choice->Visible	= (bool) $r['visible'];
										$choice->SurveyMonkeySurveyQuestionID = $question->ID;
										$choice->write();
				 					}
				 				}

								/* * * ROWS * * */
				 				if (array_key_exists('choices', $q)) {
				 					foreach($q['choices'] as $c){
					 					// echo "CHOICE:" . $c['id'] . "-->" . $c['text'] . "<br/>";
										$choice = new SurveyMonkeySurveyChoice();
										$choice->ChoiceID 	= $c['id'];
										$choice->Position 	= $c['position'];
										$choice->Text 		= $c['text'];
										$choice->Visible	= (bool) $c['visible'];
										$choice->SurveyMonkeySurveyQuestionID = $question->ID;
										$choice->write();
				 					}
				 				}

				 				/* * * OTHER * * */
				 				if (array_key_exists('other', $q)) {
					 				// echo "OTHER: " . $q['other']['id'] . "-->" . $q['other']['text'] . "<br/>";
									$choice = new SurveyMonkeySurveyChoice();
									$choice->ChoiceID 	= $q['other']['id'];
									$choice->Text 		= $q['other']['text'];
									$choice->SurveyMonkeySurveyQuestionID = $question->ID;
									$choice->write();
				 				}	

				 			}
				 		}


		 			}
		 		}

		 		/* IMPORT ANSWERS */
		 		foreach($collectors as $c) {

		 			$collector = new SurveyMonkeySurveyCollector();
		 			$collector->ColletorID = $c['id'];
		 			$collector->Name = $c['name'];
		 			$collector->Type = $c['type'];
		 			$collector->SurveyMonkeySurveyID = $survey->ID;
		 			$collector->SurveyID = $si;
		 			$collector->write();

	
					if (is_array($c)) {

						$collectorResponse 	= $client->getCollectorResponses($c['id'], true)->getData()['data'];

						foreach($collectorResponse as $k => $v) {

							foreach($v['pages'][0]['questions'] as $ck => $cv) {


								foreach($cv['answers'] as $answer) {

									$sanswer = new SurveyMonkeySurveyAnswer();
							 		$survey->SurveyID = $si;

					 				// we are dealing with a row
					 				if (array_key_exists('row_id', $answer)) {
					 					$sanswer->ChoiceID = $answer['choice_id'];
					 					$sanswer->RowID = $answer['row_id'];
					 				} else {
					 					$sanswer->ChoiceID = $answer['choice_id'];
					 				}

					 				$choice = SurveyMonkeySurveyChoice::get()
					 					->filter(array('ChoiceID' => $answer['choice_id']))
					 					->First();

					 				$sanswer->SurveyMonkeySurveyChoiceID = $choice->ID;
					 				$sanswer->SurveyMonkeySurveyCollectorID = $collector->ID;
							 		$sanswer->write();

								}
							}
						}
					}
				}

		 	}
		 }


		 // header('Content-Type: application/json');

		 // $surveyID = array_pop($surveysResponse->getData()['data'])['id'];


		 // echo "Survey ID => ";
		 // print_r($surveyID);

		 // echo "<br/>";

		 // echo "Title => "; 
		 // print_r(array_pop($surveysResponse->getData()['data'])['title']);

		 // echo "<br/>";

		 // echo "Questions => <br/>";

		 // foreach($client->getSurveyPages($surveyID)->getData()['data'] as $pk) {

		 // 	foreach($client->getSurveyPageQuestions($surveyID, $pk['id'])->getData()['data'] as $questions){
			// 	// echo "<pre>";
			// 	// print_r($questions);
			// 	// echo "</pre><br/><br/>";

		 // 		echo  $questions['id'] . "=> " . $questions['heading'] . "<br/>";

		 // 		foreach ($client->getSurveyPageQuestion($surveyID, $pk['id'], $questions['id'])->getData() as $q) {
		 // 			if (is_array($q)) {
			// 			// echo "<pre>";
			// 			// print_r($q);
			// 			// echo "</pre><br/><br/>";

			// 			/* * * CHOICES / COLUMNS * * */
		 // 				if (array_key_exists('rows', $q)) {
		 // 					foreach($q['rows'] as $r){
			//  					echo "ROW:" . $r['id'] . "-->" . $r['text'] . "<br/>";
		 // 					}
		 // 				}

			// 			/* * * ROWS * * */
		 // 				if (array_key_exists('choices', $q)) {
		 // 					foreach($q['choices'] as $c){
			//  					echo "CHOICE:" . $c['id'] . "-->" . $c['text'] . "<br/>";
		 // 					}
		 // 				}

		 // 				/* * * OTHER * * */
		 // 				if (array_key_exists('other', $q)) {
			//  				echo "OTHER: " . $q['other']['id'] . "-->" . $q['other']['text'] . "<br/>";
		 // 				}	

		 // 			}
		 // 		}
		 // 	}

		 // }

		 // echo "<br/>";


		 // echo "Survey Responses => ";
		 // $answers = $client->getSurveyResponses(array_pop($surveysResponse->getData()['data'])['id']);

		 // echo "<br/>";

		 // foreach($answers->getData()['data'] as $a) 
		 // {
		 // 	echo $a['id'] . "<br/>";
		 // }

		 // echo "<br/>";


		 // $collectors = $client->getCollectorsForSurvey($surveyID);
		 // $collectors = $collectors->getData();


		 // echo "You have " . count($collectors['data']) . ' collectors' . "<br/>";
		 // echo "Namely " . implode( "," , array_column($collectors['data'], 'name' ))  . " => ID: " . implode( "," , array_column($collectors['data'], 'id' )) . " <br/>";

		 // // just to calculate number of responses
		 // foreach($collectors['data'] as $c) {
		 // 	$cresponses = $client->getCollectorResponses($c['id'], true)->getData()['data'];

		 // 	echo "For ". $c['id'] . " => ". $c['name'] . " we have " . count($cresponses)   . " responses <br/>";
		 // }

		 // echo "<br/>";
		 // echo "<br/>";


		 // foreach($collectors['data'] as $c)
		 // {


		 // 	if (is_array($c)) {

		 // 		$cresponses = $client->getCollectorResponses($c['id'], true);
		

 		// 		// echo "<hr><pre>";
 		// 		// print_r($cresponses->getData()['data']);
 		// 		// echo "</pre><hr>";

 		// 		// die();

		 // 		$i = 1;
		 // 		foreach($cresponses->getData()['data'] as $k => $v) {
		 // 			echo "Choices for your question  no# $i<br/>";

		 // 			// var_dump($v['pages'][0]['questions']);
	 	// 			// echo "<hr><pre>";
	 	// 			// print_r($v);
	 	// 			// echo "</pre><hr>";

		 // 			foreach($v['pages'][0]['questions'] as $ck => $cv) {
		 // 				// echo "<hr><pre>";
		 // 				// print_r($cv);
		 // 				// echo "</pre><hr>";

		 // 				foreach($cv['answers'] as $answer) {
			//  				// we are dealing with a row
			//  				if (array_key_exists('row_id', $answer)) {

			//  					echo "ChoiceID: " . $answer['choice_id'] . "/ RowID: " . $answer['row_id'] . "<br/>";
			//  				} else {
			//  					echo "Choice ID: " . $answer['choice_id'] . "<br/>";

			//  				}

		 // 				}


		 // 			}

		 // 			$i++;
		 // 		}
		 // 	}

		 // 	// collector ids
		 // 	// echo $c['data']['id'] . "<br/>";
		 // }

		 echo "Done";

		 die();
	}

	public function complete() {
		return array(
			"Content" => "<p>All your surveys have been imported.</p>",
			"Form" => " ",
		);
	}

	public function getSurveys() {
		$surveys = new ArrayList();

		$config = SiteConfig::current_site_config();

		$client = new Client($config->SurveyMonkeyAccessToken, $config->SurveryMonkeyAccessCode);
		$surveysResponse = $client->getSurveys()->getData();

		foreach($surveysResponse['data'] as $r) {

			$surveys->push(Array(
							"Title" => $r['title'],
							"ID" => $r['id']
			));
		}

		return $surveys;
	}


	protected function getSelectedSurveyIDS($data) {
		$ids = array();

		foreach($data as $dk => $dv) {
			if (strpos($dk,"SurveyID") !== FALSE) {
				$ids []= explode("-", $dk)[1];
			}
		}

		return $ids;
	}

	protected function deleteAllSurveysData($data) {

		if(isset($data['DeleteExisting']) && $data['DeleteExisting']) {
			$surveys = SurveyMonkeySurvey::get();
			$collectors = SurveyMonkeySurveyCollector::get();
			$questions = SurveyMonkeySurveyQuestion::get();
			$choices = SurveyMonkeySurveyChoice::get();
			$answers = SurveyMonkeySurveyAnswer::get();

			foreach($surveys as $s){
				$s->delete();
			}

			foreach($questions as $q){
				$q->delete();
			}

			foreach($choices as $c){
				$c->delete();
			}

			foreach($answers as $a){
				$a->delete();
			}					

			foreach($collectors as $col){
				$col->delete();
			}						
		}
	}


}