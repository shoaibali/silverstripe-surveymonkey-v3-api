<?php

use Spliced\SurveyMonkey\Client;


class SurveyMonkeyPage extends Page_Controller {



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

    public function getCollectors($SurveyID) {
    	
    }

}