<?php
  
class SurveyMonkeyConfig extends DataExtension {
	static $db = array(
		'SurveyMonkeyClientID' => 'Varchar(255)',
		'SurveyMonkeySecret' => 'Varchar(255)',
		'SurveyMonkeyOAuthRedirectURL' => 'Varchar(255)',
		'SurveyMonkeyAccessToken' => 'Varchar(255)',
		'SurveryMonkeyAccessCode' => 'Varchar(255)',
		'SSLVerify' => 'Boolean'
	);
	

	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeyClientID"));
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeySecret"));
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeyOAuthRedirectURL"));
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeyAccessToken"));
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveryMonkeyAccessCode"));
		$fields->addFieldToTab("Root.SurveyMonkey", new CheckBoxField("SSLVerify"));
	}
}