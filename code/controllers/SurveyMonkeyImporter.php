<?php

use Spliced\SurveyMonkey\Client;


class SurveyMonkeyImporter extends SurveyMonkeyPage {
    private static $allowed_actions = array(
        'Form',
        'import',
        'complete',
        'createEmailCollector'
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

    public function Form() {

        $fields = new FieldList();

        if (!array_key_exists("error", $surveys = $this->owner->getSurveys())) {
            foreach($this->owner->getSurveys() as $s) {
                $title = "<strong>". $s->Title  . "</strong>";
                $info =  " [<strong>Created</strong>: " . $s->DateCreated . "]" 
                            . " [<strong>Responses</strong>: " . $s->ResponseCount . "]"
                            . " [<strong>Questions</strong>: " . $s->QuestionsCount . "]"
                            . " [<strong>Modified</strong>: " . $s->DateModified . "] <br/><br/>";

                $f = new CheckboxField("SurveyID-". $s->ID, $title);
                $lf = new LiteralField("SurveyInfo", $info);

                // $f->setValue(TRUE);

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


        $lfhr = new LiteralField("CollectorStrat", "<hr/>");
        $fields->push($lfhr);

        /** ------------------------------------------ **/

        $collectorNameField = new TextField("collectorName", "Collector name");
        $fields->push($collectorNameField);

        $thankyouMessageField = new TextAreaField("thankyouMessage", "Thank you message");
        $fields->push($thankyouMessageField);

        $disqualificationMessageField = new TextAreaField("disqualificationMessage", "Disqualification message");
        $fields->push($disqualificationMessageField);

        $closeDateField = new DatetimeField('closeDate', 'Close date / time');
        // ISOString format https://www.w3schools.com/jsref/jsref_toisostring.asp
        $closeDateField->setConfig('datavalueformat', 'YYYY-MM-DDTHH:mm:ss.sssZ'); // global setting
        $closeDateField->getDateField()->setConfig('showcalendar', 1); // field-specific setting

        $fields->push($closeDateField);


        $closedpageMessageField = new TextAreaField("closedpageMessage", "Closed page message");
        $fields->push($closedpageMessageField);

        $redirectURLField = new TextField("redirectURL", "Redirect URL");
        $fields->push($redirectURLField);

        $senderEmailField = new EmailField("senderEmail", "Sender email address");
        $fields->push($senderEmailField);

        /** ------------------------------------------ **/


        $lfhr = new LiteralField("CollectorEnd", "<hr/>");
        $fields->push($lfhr);

        //TODO No point in showing submit button if there are no surveys to import or errors
        return new Form($this, "Form", $fields, new FieldList(
            new FormAction("import", "Begin Import"),
            new FormAction("createEmailCollector", "Create Email Collector")
        ));
    }

    public function Link($action = null)
    {
        return Controller::join_links('surveymonkey/importer', $action);
    }

    // this can be private
    public function createMessage($collectors, $client)  {

        // foreach ($collectors as $c) {

            $data = array(
                // other options are 'reminder', and 'thank_you'
                'type' => 'invite',
                // can also send 'recipient_status' if reminder type is set
                'subject' => 'QEDelivery Survey'
            );
            // create a message
            $messageResponse = $client->createCollectorMessage($collectors['id'], $data)->getData();

        // }

        // var_dump($messageResponse->getData()); die();

        return $messageResponse;

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

                        // print_r($client->getCollectorResponses($c['id'], true)->getData());

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
                            // print_r($v['pages']); die();

                            foreach($v['pages'] as $ck => $cv) {

                                foreach($cv['questions'] as $answers => $answer) {

                                        foreach ($answer['answers'] as $row => $r) {

                                        $sanswer = new SurveyMonkeySurveyAnswer();
                                        $sanswer->SurveyID = $si;
                                        $sanswer->AnswerID = $answer['id'];
                                        $sanswer->SurveyMonkeySurveyCollectorID = $collector->ID;

                                        if (array_key_exists('choice_id', $r) && !array_key_exists('row_id', $r)) {
                                            echo "Choice => " . $r['choice_id'] . "<br/>";
                                            $sanswer->ChoiceID = $r['choice_id'];
                                            $choice = SurveyMonkeySurveyChoice::get()
                                                ->filter(array(
                                                    'ChoiceID' => $r['choice_id'],
                                                    'SurveyID' => $si
                                                ))->First();



                                            $sanswer->SurveyMonkeySurveyChoiceID = $choice->ID;
                                            $sanswer->SurveyMonkeySurveyCollectorID = $collector->ID;
                                            $sanswer->write();
                                            $sresponse->SurveyMonkeySurveyAnswers()->add($sanswer);

                                        }
                                        
                                        if (array_key_exists('row_id', $r) && array_key_exists('choice_id', $r)) {
                                            echo "Choice => " . $r['choice_id'] . "<br/>";

                                            $sanswer->ChoiceID = $r['choice_id'];
                                            $sanswer->RowID = $r['row_id'];

                                            $choice = SurveyMonkeySurveyChoice::get()
                                                ->filter(array(
                                                    'ChoiceID' => $r['row_id'],
                                                    'SurveyID' => $si
                                                ))->First();


                                            $sanswer->SurveyMonkeySurveyChoiceID = $choice->ID;
                                            $sanswer->SurveyMonkeySurveyCollectorID = $collector->ID;
                                            $sanswer->write();
                                            $sresponse->SurveyMonkeySurveyAnswers()->add($sanswer);


                                        }

                                        /* Whenever there is an OTHER_ID there is always TEXT */
                                        if (array_key_exists('other_id', $r)) {
                                            echo "RowID => " . $r['row_id'] . "<br/>";
                                            echo "OtherID => " . $r['other_id'] . "<br/>";
                                            echo "Text => " . $r['text'] . "<br/>";


                                            $sanswer->ChoiceID = $r['other_id'];

                                            $choice = SurveyMonkeySurveyChoice::get()
                                                ->filter(array(
                                                    'ChoiceID' => $r['other_id'],
                                                    'SurveyID' => $si
                                                ))->First();

                                            $sanswer->Text = $r['text'];
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
         }


         echo "Import Complete!";

         die();
    }

    public function complete() {
        return array(
            "Content" => "<p>All your surveys have been imported.</p>",
            "Form" => " ",
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