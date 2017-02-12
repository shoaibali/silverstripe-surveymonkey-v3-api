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

        if (!array_key_exists("error", $surveys = $this->getSurveys())) {
            foreach($this->getSurveys() as $s) {
                $title = "<strong>". $s->Title  . "</strong>";
                $info =  " [<strong>Created</strong>: " . $s->DateCreated . "]" 
                            . " [<strong>Responses</strong>: " . $s->ResponseCount . "]"
                            . " [<strong>Questions</strong>: " . $s->QuestionsCount . "]"
                            . " [<strong>Modified</strong>: " . $s->DateModified . "] <br/><br/>";

                $f = new CheckboxField("SurveyID-". $s->ID, $title);
                $lf = new LiteralField("SurveyInfo", $info);

                $f->setValue(TRUE);

                $fields->push($f);
                $fields->push($lf);
            }
        } else {
            $lf = new LiteralField("Error", "<br/><br/><strong>" . $surveys['error'] . " : " . $surveys['name'] . "</strong>");
            $fields->push($lf);
        }

        $deleteExistingCheckBox = new CheckboxField("DeleteExisting", "Clear out all existing surveys?");
        $deleteExistingCheckBox->setValue(TRUE);

        $fields->push($deleteExistingCheckBox);

        //TODO No point in showing submit button if there are no surveys to import or errors
        return new Form($this, "Form", $fields, new FieldList(
            new FormAction("import", "Begin Import")
        ));
    }

    public function import($data, $form) {

         $surveyIDs = self::getSelectedSurveyIDS($data);

         $deleteExisting = self::deleteAllSurveysData($data);

         $config = SiteConfig::current_site_config();

         $client = new Client($config->SurveyMonkeyAccessToken, $config->SurveryMonkeyAccessCode);

         foreach($surveyIDs as $si)
         { 
            $existingSurvey = SurveyMonkeySurvey::get()->filter(array('SurveyID' => $si))->First();

            if (!$existingSurvey || $deleteExisting ) {

                // TODO If the rate limit is reached, we should also halt the app here as well

                // TODO could also get Pages here with second parameter to getSurvey
                $surveyResponse     = $client->getSurvey($si)->getData();
                $pagesResponse      = $client->getSurveyPages($si)->getData()['data'];
                $collectors         = $client->getCollectorsForSurvey($si)->getData()['data'];

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

                    foreach($client->getSurveyPageQuestions($si, $pk['id'])->getData()['data'] as $questions) {

                        $question = new SurveyMonkeySurveyQuestion();
                        $question->QuestionID           = $questions['id'];
                        $question->Title                = $questions['heading'];
                        $question->Position             = $questions['position'];
                        $question->SurveyMonkeySurveyID = $survey->ID;
                        
                        $question->PageID               = $pk['id'];
                        $question->PageTitle            = $pk['title'];
                        $question->PagePosition         = $pk['position'];
                        $question->PageDescription      = $pk['description'];

                        $question->write();

                        /* IMPORT CHOICES */
                        foreach($client->getSurveyPageQuestion($si, $pk['id'], $questions['id'])->getData() as $q) {
                            
                            if (is_array($q)) {

                                /* * * CHOICES / COLUMNS * * */
                                if (array_key_exists('rows', $q)) {
                                    foreach($q['rows'] as $r){
                                        // echo "ROW:" . $r['id'] . "-->" . $r['text'] . "<br/>";
                                        $choice = new SurveyMonkeySurveyChoice();
                                        $choice->ChoiceID   = $r['id'];
                                        $choice->SurveyID   = $si;
                                        $choice->Position   = $r['position'];
                                        $choice->Text       = $r['text'];
                                        $choice->Visible    = (bool) $r['visible'];
                                        $choice->IsRow    = true;
                                        $choice->SurveyMonkeySurveyQuestionID = $question->ID;
                                        $choice->write();

                                    }
                                }

                                /* * * ROWS * * */
                                if (array_key_exists('choices', $q)) {
                                    foreach($q['choices'] as $c){
                                        // echo "CHOICE:" . $c['id'] . "-->" . $c['text'] . "<br/>";
                                        $choice = new SurveyMonkeySurveyChoice();
                                        $choice->ChoiceID   = $c['id'];
                                        $choice->SurveyID   = $si;
                                        $choice->Position   = $c['position'];
                                        $choice->Text       = $c['text'];
                                        $choice->Visible    = (bool) $c['visible'];
                                        $choice->SurveyMonkeySurveyQuestionID = $question->ID;
                                        $choice->write();
                                    }
                                }

                                /* * * OTHER * * */
                                if (array_key_exists('other', $q)) {
                                    // echo "OTHER: " . $q['other']['id'] . "-->" . $q['other']['text'] . "<br/>";
                                    $choice = new SurveyMonkeySurveyChoice();
                                    $choice->ChoiceID   = $q['other']['id'];
                                    $choice->SurveyID   = $si;
                                    $choice->Text       = $q['other']['text'];
                                    $choice->SurveyMonkeySurveyQuestionID = $question->ID;
                                    $choice->write();
                                }   

                            }
                        }


                    }
                }

                /* IMPORT ANSWERS */
                foreach($collectors as $c) {

                    $coll = $client->getCollector($c['id'])->getData();

                    $collector = new SurveyMonkeySurveyCollector();
                    $collector->CollectorID = $c['id'];
                    $collector->Name = $c['name'];
                    $collector->Type = $coll['type'];
                    $collector->ResponseCount = $coll['response_count'];
                    $collector->Status = $coll['status'];
                    $collector->SurveyMonkeySurveyID = $survey->ID;
                    $collector->SurveyID = $si;
                    $collector->write();

    
                    if (is_array($c)) {

                        $collectorResponse  = $client->getCollectorResponses($c['id'], true)->getData()['data'];

                        foreach($collectorResponse as $k => $v) {

                        	// create a response!
                        	$sresponse = new SurveyMonkeySurveyResponse();

                        	$sresponse->ResponseID 		= $v["id"];
                        	$sresponse->SurveyID 		= $v["survey_id"];
                        	$sresponse->CollectorID 	= $v["collector_id"];
                        	$sresponse->RecipientID 	= $v["recipient_id"];
                        	$sresponse->IPAddress 		= $v["ip_address"];
                        	$sresponse->ResponseStatus 	= $v["response_status"];
                        	$sresponse->TotalTime 		= $v["total_time"];

                        	// for web-link type collectors there is no "contact" metadata available
                        	$sresponse->EmailAddress 	= (isset($v["metadata"]["contact"]))? $v["metadata"]["contact"]["email"]["value"] : "";

                        	//internal relationships
                        	$sresponse->SurveyMonkeySurveyID = $survey->ID;
                        	$sresponse->SurveyMonkeySurveyCollectorID = $collector->ID;
                        	$sresponse->write();

                        	// TODO  first_name and last_name should also be stored here (if available)

                            foreach($v['pages'] as $ck => $cv) {

                                foreach($cv['questions'] as $answers => $answer) {

                                    $sanswer = new SurveyMonkeySurveyAnswer();
                                    $sanswer->SurveyID = $si;
                                    $sanswer->AnswerID = $answer['id'];
                                    $sanswer->SurveyMonkeySurveyCollectorID = $collector->ID;

                                    // we are dealing with a row
                                    if (array_key_exists('row_id', $answer['answers'][0])) {
                                        // $sanswer->ChoiceID = $answer['choice_id'];
                                        $sanswer->RowID = $answer['answers'][0]['row_id'];

                                        $choice = SurveyMonkeySurveyChoice::get()
                                            ->filter(array(
                                                'ChoiceID' => $answer['answers'][0]['row_id'],
                                                'SurveyID' => $si
                                            ))->First();

                                        $sanswer->SurveyMonkeySurveyChoiceID = $choice->ID;
                                        $sanswer->SurveyMonkeySurveyCollectorID = $collector->ID;
                                        $sanswer->write();
                                        $sresponse->SurveyMonkeySurveyAnswers()->add($sanswer);
                                    }

                                    if (isset( $answer['answers'][1] )) {
                                        if (array_key_exists('other_id', $answer['answers'][1])) {

                                            $sanswer->ChoiceID = $answer['answers'][1]['other_id'];

                                            $choice = SurveyMonkeySurveyChoice::get()
                                                ->filter(array(
                                                    'ChoiceID' => $answer['answers'][1]['other_id'],
                                                    'SurveyID' => $si
                                                ))->First();

                                            $sanswer->Text = $answer['answers'][1]['text'];
                                            $sanswer->SurveyMonkeySurveyChoiceID = $choice->ID;
                                            $sanswer->SurveyMonkeySurveyCollectorID = $collector->ID;
                                            $sanswer->write();
                                            $sresponse->SurveyMonkeySurveyAnswers()->add($sanswer);

                                        }
                                    }

                                    if (array_key_exists('choice_id', $answer['answers'][0])) {

                                        $sanswer->ChoiceID = $answer['answers'][0]['choice_id'];
                                        $choice = SurveyMonkeySurveyChoice::get()
                                            ->filter(array(
                                                'ChoiceID' => $answer['answers'][0]['choice_id'],
                                                'SurveyID' => $si
                                            ))->First();



                                        $sanswer->SurveyMonkeySurveyChoiceID = $choice->ID;
                                        $sanswer->SurveyMonkeySurveyCollectorID = $collector->ID;
                                        $sanswer->write();
                                        $sresponse->SurveyMonkeySurveyAnswers()->add($sanswer);

                                    }

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

         //     foreach($client->getSurveyPageQuestions($surveyID, $pk['id'])->getData()['data'] as $questions){
            //  // echo "<pre>";
            //  // print_r($questions);
            //  // echo "</pre><br/><br/>";

         //         echo  $questions['id'] . "=> " . $questions['heading'] . "<br/>";

         //         foreach ($client->getSurveyPageQuestion($surveyID, $pk['id'], $questions['id'])->getData() as $q) {
         //             if (is_array($q)) {
            //          // echo "<pre>";
            //          // print_r($q);
            //          // echo "</pre><br/><br/>";

            //          /* * * CHOICES / COLUMNS * * */
         //                 if (array_key_exists('rows', $q)) {
         //                     foreach($q['rows'] as $r){
            //                      echo "ROW:" . $r['id'] . "-->" . $r['text'] . "<br/>";
         //                     }
         //                 }

            //          /* * * ROWS * * */
         //                 if (array_key_exists('choices', $q)) {
         //                     foreach($q['choices'] as $c){
            //                      echo "CHOICE:" . $c['id'] . "-->" . $c['text'] . "<br/>";
         //                     }
         //                 }

         //                 /* * * OTHER * * */
         //                 if (array_key_exists('other', $q)) {
            //                  echo "OTHER: " . $q['other']['id'] . "-->" . $q['other']['text'] . "<br/>";
         //                 }   

         //             }
         //         }
         //     }

         // }

         // echo "<br/>";


         // echo "Survey Responses => ";
         // $answers = $client->getSurveyResponses(array_pop($surveysResponse->getData()['data'])['id']);

         // echo "<br/>";

         // foreach($answers->getData()['data'] as $a) 
         // {
         //     echo $a['id'] . "<br/>";
         // }

         // echo "<br/>";


         // $collectors = $client->getCollectorsForSurvey($surveyID);
         // $collectors = $collectors->getData();


         // echo "You have " . count($collectors['data']) . ' collectors' . "<br/>";
         // echo "Namely " . implode( "," , array_column($collectors['data'], 'name' ))  . " => ID: " . implode( "," , array_column($collectors['data'], 'id' )) . " <br/>";

         // // just to calculate number of responses
         // foreach($collectors['data'] as $c) {
         //     $cresponses = $client->getCollectorResponses($c['id'], true)->getData()['data'];

         //     echo "For ". $c['id'] . " => ". $c['name'] . " we have " . count($cresponses)   . " responses <br/>";
         // }

         // echo "<br/>";
         // echo "<br/>";


         // foreach($collectors['data'] as $c)
         // {


         //     if (is_array($c)) {

         //         $cresponses = $client->getCollectorResponses($c['id'], true);
        

        //      // echo "<hr><pre>";
        //      // print_r($cresponses->getData()['data']);
        //      // echo "</pre><hr>";

        //      // die();

         //         $i = 1;
         //         foreach($cresponses->getData()['data'] as $k => $v) {
         //             echo "Choices for your question  no# $i<br/>";

         //             // var_dump($v['pages'][0]['questions']);
        //          // echo "<hr><pre>";
        //          // print_r($v);
        //          // echo "</pre><hr>";

         //             foreach($v['pages'][0]['questions'] as $ck => $cv) {
         //                 // echo "<hr><pre>";
         //                 // print_r($cv);
         //                 // echo "</pre><hr>";

         //                 foreach($cv['answers'] as $answer) {
            //                  // we are dealing with a row
            //                  if (array_key_exists('row_id', $answer)) {

            //                      echo "ChoiceID: " . $answer['choice_id'] . "/ RowID: " . $answer['row_id'] . "<br/>";
            //                  } else {
            //                      echo "Choice ID: " . $answer['choice_id'] . "<br/>";

            //                  }

         //                 }


         //             }

         //             $i++;
         //         }
         //     }

         //     // collector ids
         //     // echo $c['data']['id'] . "<br/>";
         // }

         echo "Import Complete!";

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

        if (!array_key_exists("error", $surveysResponse)) {
            foreach($surveysResponse['data'] as $r) {

                $survey = $client->getSurvey($r['id'])->getData();

                $surveys->push(Array(
                                "Title" => $r['title'],
                                "ID" => $r['id'],
                                "DateCreated" => $survey['date_created'],
                                "DateModified" => $survey['date_modified'],
                                "QuestionsCount" => $survey['question_count'],
                                "ResponseCount" => $survey['response_count'],
                ));
            }
            return $surveys;
        } 

        return array(
            'error' => $surveysResponse['error']['message'], 
            'name' => $surveysResponse['error']['name']
        );
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

        $delete = false;

        if(isset($data['DeleteExisting']) && $data['DeleteExisting']) {
            $surveys = SurveyMonkeySurvey::get();
            $collectors = SurveyMonkeySurveyCollector::get();
            $questions = SurveyMonkeySurveyQuestion::get();
            $choices = SurveyMonkeySurveyChoice::get();
            $answers = SurveyMonkeySurveyAnswer::get();
            $responses = SurveyMonkeySurveyResponse::get();

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

            foreach($responses as $r){
                $r->delete();
            }


            $delete = true;                     
        }

        return $delete;
    }


}