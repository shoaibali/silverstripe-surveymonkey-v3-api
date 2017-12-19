<?php
  
class SurveyMonkeyConfig extends DataExtension {
	static $db = array(
		'SurveyMonkeyUserName' => 'Varchar(254)',
		'SurveyMonkeyPassword' => 'Varchar(160)', // this is bad
		'SurveyMonkeyClientID' => 'Varchar(255)',
		'SurveyMonkeySecret' => 'Varchar(255)',
		'SurveyMonkeyOAuthRedirectURL' => 'Varchar(255)',
		'SurveyMonkeyAccessToken' => 'Varchar(255)',
		'SurveryMonkeyAccessCode' => 'Varchar(255)',
		'SSLVerify' => 'Boolean'
	);
	

	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeyUserName"));
		
		/* @TODO I was hoping it would encrypt with salting the password using same method it does for MemberPassword
		 * unforunately not, I will work out some other way of onBeforeWrite() of encrypting before putting it 
		 * in the database
		 */
		$fields->addFieldToTab("Root.SurveyMonkey", new PasswordField("SurveyMonkeyPassword"));

		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeyClientID"));
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeySecret"));
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeyOAuthRedirectURL"));
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveyMonkeyAccessToken"));
		$fields->addFieldToTab("Root.SurveyMonkey", new TextField("SurveryMonkeyAccessCode"));
		$fields->addFieldToTab("Root.SurveyMonkey", new CheckBoxField("SSLVerify"));
	}
}