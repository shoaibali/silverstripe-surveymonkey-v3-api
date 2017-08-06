<?php

use Spliced\SurveyMonkey\Client;


class SurveyMonkeyManager extends Page_Controller {
    static $allowed_actions = array(
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


}
