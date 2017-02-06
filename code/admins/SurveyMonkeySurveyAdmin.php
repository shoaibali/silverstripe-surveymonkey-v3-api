<?php

class SurveyMonkeySurveyAdmin extends ModelAdmin {
	
    private static $managed_models = array(
        'SurveyMonkeySurvey',
        'SurveyMonkeySurveyCollector',
        'SurveyMonkeySurveyQuestion',
        'SurveyMonkeySurveyChoice',
        'SurveyMonkeySurveyResponse',
        'SurveyMonkeySurveyAnswer'
    );

    private static $url_segment = 'surveys';

    private static $menu_title = 'Survey Monkey';

    private static $menu_icon = '/silverstripe-surveymonkey-v3-api/images/sm.png';

}