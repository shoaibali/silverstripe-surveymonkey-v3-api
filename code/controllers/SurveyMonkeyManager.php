<?php

use Spliced\SurveyMonkey\Client;


class SurveyMonkeyManager extends SurveyMonkeyPage
{

    static $allowed_actions = array(
        'Form',
        'createEmailCollector',
        'createSurveyMonkeySurvey',
        'deleteSurveyMonkeySurveys'
    );

    public function init()
    {
        parent::init();
        if (!Permission::check('ADMIN')) Security::permissionFailure();
    }

    public function Title()
    {
        return "SurveyMonkey Manager";
    }

    public function index()
    {
        return $this->renderWith(array("SurveyMonkeyManager", "Page"));
    }



    public function Form() {

        $fields = new FieldList();
        $surveys = $this->owner->getSurveys();

        if (!array_key_exists("error", $surveys)) {

            foreach($surveys as $s) {
                $title = "<strong>". $s->Title  . "</strong>";
                $info =  " [<strong>Created</strong>: " . $s->DateCreated . "]" 
                            . " [<strong>Responses</strong>: " . $s->ResponseCount . "]"
                            . " [<strong>Questions</strong>: " . $s->QuestionsCount . "]"
                            . " [<strong>Modified</strong>: " . $s->DateModified . "] <br/><br/>";

                $f = new CheckboxField("SurveyID-". $s->ID, $title);
                $f->setValue(TRUE);
                $lf = new LiteralField("SurveyInfo", $info);

                // $f->setValue(TRUE);

                $fields->push($f);
                $fields->push($lf);
            }

        } else {
                $lf = new LiteralField("Error", "<br/><br/><strong>" . $surveys['error'] . " : " . $surveys['name'] . "</strong>");
                $fields->push($lf);
        }

        //TODO No point in showing submit button if there are no surveys to import or errors
        return new Form($this, "Form", $fields, new FieldList(
            new FormAction("deleteSurveyMonkeySurveys", "Delete all or selected surveys"),
            new FormAction("sendEmail", "Send Email Invitation"),
            new FormAction("createEmailCollector", "Create Email Collector"),
            new FormAction("createSurveyMonkeySurvey", "Create Survey")
        ));
    }

    public function Link($action = null)
    {
        return Controller::join_links('surveymonkey/manager', $action);
    }

    public function createSurveyMonkeySurvey($data, $form)
    {
        $config = SiteConfig::current_site_config();
        $client = new Client($config->SurveyMonkeyAccessToken, $config->SurveryMonkeyAccessCode);

        $getSurveyID = SurveyMonkeySurvey::get()->first();

        $data = array(
            'title' => 'testing',
            'from_survey_id' => $getSurveyID->SurveyID
        );

        $client->createSurvey($data);
        return 'all done';
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

    public function deleteSurveyMonkeySurveys($data, $form) 
    {

        $surveyIDs = self::getSelectedSurveyIDS($data);


        $config = SiteConfig::current_site_config();

        $client = new Client($config->SurveyMonkeyAccessToken, $config->SurveryMonkeyAccessCode);

        foreach ($surveyIDs as $si) {

            $client->deleteSurvey($si)->getData();

        }

        echo "Done deleting " . count($surveyIDs)  . " surveys";

    }

    public function createEmailCollector($data, $form)
    {
        $surveyIDs = self::getSelectedSurveyIDS($data);

        $config = SiteConfig::current_site_config();

        $client = new Client($config->SurveyMonkeyAccessToken, $config->SurveryMonkeyAccessCode);

        foreach ($surveyIDs as $si) {
            // create collectors for these surveyids.
            // TODO Maybe check to see if this surveyid already has collectors before creating them?
            $d = array(
                'type' => 'email',
                'name' => isset($data['collectorName']) ? $data['collectorName'] : "",
                'thank_you_message' => isset($data['thankyouMessage']) ? $data['thankyouMessage'] : "",
                'disqualification_message' => isset($data['disqualificationMessage']) ? $data['disqualificationMessage'] : "",
                // 'close_date' => isset($data['closeDate'])? $data['closeDate'] : date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2018)),
                'close_date' => date(DATE_ATOM, mktime(0, 0, 0, 7, 1, 2018)),
                'closed_page_message' => isset($data['closedpageMessage']) ? $data['closedpageMessage'] : "",
                // 'redirect_url' => isset($data['redirectURL'])? $data['redirectURL'] : "", // this is only available in platinum account
                // 'display_survey_results' => isset($data['showResults'])? $data['showResults'] : false,
                'edit_response_type' => isset($data['editResponseType']) ? $data['editResponseType'] : 'until_complete',
                // 'anonymous_type' => isset($data['anonymousType'])? $data['anonymousType'] : 'not_anonymous',
                // 'password' =>  isset($data['password'])? $data['password'] : '',
                'sender_email' => isset($data['senderEmail']) ? $data['senderEmail'] : '',
                // 'redirect_type' =>  isset($data['redirectType'])? $data['redirectType'] : 'url',
            );

            // get collectors
            $collectors = $client->getCollectorsForSurvey($si)->getData()['data'];

            // if we do not have any collectors then create one.
            if (count($collectors) == 0) {
                if (array_key_exists("error", $collectorResponse = $client->createCollectorForSurvey($si, $d)->getData())) {
                    echo $collectorResponse['error']['name'] . " => " . $collectorResponse['error']['message'];
                } else {
                    echo "Collector Created";

                    // var_dump($collectorResponse); die();

                    // create message
                    $messageResponse = $this->createMessage($collectorResponse, $client);

                    echo "Messsage created";

                    // add recipients to message
                    $recipientResponse = $client->createCollectorMessageRecipient($collectorResponse['id'], $messageResponse['id'],
                        array('email' => 'shoaib@webstrike.co.nz',
                            'first_name' => 'shoaib',
                            'last_name' => 'ali'
                        )
                    )->getData();

                    // send message
                    $s = $client->sendCollectorMessage($collectorResponse['id'], $messageResponse['id']);

                    echo "Messsage sent";
                }
            } else {

                // // create message
                // $messageResponse = $this->createMessage($collectors, $client);

                // echo "Messsage created";

                // // add recipients to message
                // $m = $client->createCollectorMessageRecipient($collectorResponse['id'], $messageResponse['id'],
                //         array(  'email' => 'shoaib.ali@qedelivery.com',
                //                 'first_name' => 'shoaib',
                //                 'last_name' => 'ali'
                //         )
                // )->getData()['data'];

                // var_dump($m);

                // // send message
                // $s = $client->sendCollectorMessage()->getData()['data'];

                // var_dump($s);
                // echo "Messsage sent";

            }

        }


        die();

    }


}
