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


    public function getEditForm($id = null, $fields = null) {
        $form = parent::getEditForm($id, $fields);
        
        $listField = $form->Fields()->fieldByName($this->modelClass);
        if ($gridField = $listField->getConfig()->getComponentByType('GridFieldDetailForm'))
            $gridField->setItemRequestClass('SurveyMonkeySurveyDetailForm_ItemRequest');
        
        return $form;
    }
}


class SurveyMonkeySurveyDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest {

    private static $allowed_actions = array (
        'edit',
        'view',
        'ItemEditForm'
    );

    public function ItemEditForm() {
        $form = parent::ItemEditForm();
        $formActions = $form->Actions();
        
        if ($actions = $this->record->getCMSActions())
            foreach ($actions as $action)
                $formActions->push($action);
        
        return $form;
    }
    
    public function doExportFromSurveyMonkeyCSV($data, $form) {
        $message = "TBA";
        $form->sessionMessage($message, 'good', false);

        $controller = $this->getController();
        $controller->getRequest()->addHeader('X-Pjax', 'Content'); // Force a content refresh
        return $controller->redirect($this->Link());
    }

}