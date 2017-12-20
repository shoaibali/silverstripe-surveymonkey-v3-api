<?php

/********************* BIG FAT NOTE **********************
* I have purposely not use RestfuLService instead using
* good old fashioned cURL requests as going towards version 4
* of silverstripe it will be deprecated, and I cant be bothered 
* coming back here and redoing all the code. Therefore, this is
* going to remain as good old cURL requests (like it or not)
*
************************ END OF NOTE **********************/


class ExportSurveyMonkeySurveyCSVTask extends BuildTask {

	protected $title = 'Export your SurveyMonkey Survey in CSV';

	protected $description = 'This task will let you export a SurveyMonkey Survey given a SurveyMonkeySurveyID';

	private $surveyMonkeySurveyID = 0;

	private $fileName = "";

	private $config = null;

	private $debug = false; // @TODO set it to true on development/test environment

	private $SSLVerify = false;

	private $rememberMe = '&remember_me=on';

	private $cookieFile = "/cookies.txt";

	protected static $websiteUrl = "https://www.surveymonkey.com";

	protected static $loginUrl = "/user/sign-in/"; // <-- Don't remove that trailing forward slash

	protected static $dashUrl = "/dashboard";

	protected static $exportUrl = "/analyze/ajax/export/create";

	protected static $downloadUrl = "/analyze/export/download/";

	protected static $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:57.0) Gecko/20100101 Firefox/57.0";


	/**
	 * @TODO Dont do anything
	*/

	public function run($request) {

		if(!Director::is_cli() && !Director::isDev() && !Permission::check('ADMIN')) { echo "Fail";
			return Security::permissionFailure();
		}

		

		//@TODO also validate the SurveyID to check if its actually a SurveyID in SurveyMonkey before starting this!
		if (!isset($request['SurveyID'])) {
			return 'Please provide SurveyMonkey SurveyID as a parameter /dev/tasks/ExportSurveyMonkeySurveyCSVTask/?SurveyID=120710061';
		}

		$this->config = SiteConfig::current_site_config();
		$this->SSLVerify = $this->config->SSLVerify;
		$this->surveyMonkeySurveyID = $request['SurveyID'];
		$this->fileName = "SS_" . $request['SurveyID'];
		$this->cookieFile = sys_get_temp_dir() . $this->cookieFile;

		$this->loginToSurveyMonkey();
		$exportJobID = $this->createExportJob();
		$this->downloadExportFile($exportJobID);

		echo "Done saving file to assets";

	}

	/**
	 * A cURL request to check if we have a valid session still using cookie file
	 *
	 * If we don't have a valid session then we should see the word 'remember_me'
	 * on the page. Which means we are not loggedin
	 * 
	 * @return Boolean True => Logged In, False => Not Logged in
	 *
	 */
	private function checkSurveyMonkeyLoginSession() 
	{

	    $ch = curl_init();
	    // visit a protected page, if we get a username/password box then we are not
	    // logged in.
	    curl_setopt($ch, CURLOPT_URL, self::$websiteUrl . self::$dashUrl);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
	    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
	    curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);
	    
	    // TODO in production we do want to verify
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->SSLVerify);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->SSLVerify);

	    $response = curl_exec($ch);

		if (!$response) {
			$err_msg = '[' . curl_errno($ch) . '] ' . curl_error($ch);
			return user_error($err_msg, E_USER_ERROR);
		}

	    curl_close($ch);

	    // if response contains the word remember_me then we are on the login page
	    $match = !(preg_match("/remember_me/", $response));
	    // $match = preg_match_all('#\b(username|password|remember_me)\b#', $output, $matches);

	    return $match;		
	}

	/**
	 * A cURL request to log the user in to SurveyMonkey using username and password
	 *
	 */
	private function loginToSurveyMonkey() 
	{

		// first check if we are already logged in or not
		if ($this->checkSurveyMonkeyLoginSession()) 
		{
			echo 'You are already logged in';
			return true;
		}

		echo "You are not logged in, I am going to log you in";

		// @TODO Check if they have a valid username and password entered in SilverStripe settings
		$username = urlencode($this->config->SurveyMonkeyUserName);
		$password = urlencode($this->config->SurveyMonkeyPassword);

		// create curl resource
		$ch = curl_init();
		// First we need to login and save the cookie jar
		curl_setopt($ch, CURLOPT_URL, self::$websiteUrl . self::$loginUrl);
		// follow redirect
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);

		// @TODO Don't think it is a good idea to store it in TEMP directory
		// maybe look at storing in the database?
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);


		curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);
		// TODO in production we do want to verify
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->SSLVerify);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->SSLVerify);

		// post enabled
		curl_setopt($ch, CURLOPT_POST, 1);
		// post username and password
		curl_setopt($ch, CURLOPT_POSTFIELDS,
		            'username='.$username.'&password='.$password. $this->rememberMe);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		              "Referer: " . self::$websiteUrl . self::$loginUrl , // <-- This is important (without it will give 403)
		              "cache-control: no-cache",
		              "Content-Type: application/x-www-form-urlencoded"));

		//return the transfer as a string (return server response)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		// $output contains the output string
		$response = curl_exec($ch);

		if (!$response) {
			$err_msg = '[' . curl_errno($ch) . '] ' . curl_error($ch);
			return user_error($err_msg, E_USER_ERROR);
		}

		curl_close($ch);

		return true;
	}

	/**
	 * A cURL request to start the job of making an export in SurveyMonkey
	 *
	 */
	private function createExportJob() {

		// @TODO Make this more configurable via SiteConfig settings etc.\
		// @TODO I am not sure what view_id is? or where to get it from etc.
		// @TODO -46800000 timezone offset will break when Day light savings are over
		// @TODO respondent_count will also need to be established

		$json = '{	"survey_id":"'. $this->surveyMonkeySurveyID . '",
					"view_id":"28831697",
					"exportjob":
						{	"custom_filename":"'. $this->fileName . '.zip",
							"format":"excel",
							"email": "' . $this->config->SurveyMonkeyUserName . '",
							"export_data":
							{	"column_setting":"expanded",
								"cell_type":"actual_choice_text",
								"multilingual":false,
								"use_cassandra":false,
								"respondent_count":19,
								"package_type":"ADVANTAGE",
								"timezone_offset":-46800000
							},
						"export_type":"full",
						"job_info":
							{	"view_name":"Original View",
								"pages":"All",
								"questions":"All"
							},
						"type":"full"
						}
				}';



		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, self::$websiteUrl . self::$exportUrl);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);

		// TODO in production we do want to verify
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->SSLVerify);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->SSLVerify);
		// RETURNTRANSFER is important if you want to parse the response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, 1);

		curl_setopt($ch, CURLOPT_POSTFIELDS, $json);

		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		  'Content-Type: application/json',
		  'Content-Length: ' . strlen($json))
		);

		$response = curl_exec($ch);


		if (!$response) {
			$err_msg = '[' . curl_errno($ch) . '] ' . curl_error($ch);
			return user_error($err_msg, E_USER_ERROR);
		}


		curl_close($ch);

		$jsonResponse = json_decode($response, true);

		// @TODO check to make sure we can access this array with those indexes!
		$exportJobID = $jsonResponse['data']['export_job']['exportjob_id'];
		
		return $exportJobID; // this ID is used to download the file
	}


	/**
	 * A cURL request downloads the ZIP file and saves it to disk
	 *
	 */
	private function downloadExportFile($exportJobID) {
		
		$downloadUrl = self::$websiteUrl . self::$downloadUrl . '?survey_id=' . $this->surveyMonkeySurveyID  . '&export_job_id=' . $exportJobID;
		
		$ch = curl_init($downloadUrl);

		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->SSLVerify);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->SSLVerify);
		curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent);

		$output = curl_exec($ch);

		// this is how you do error handling in curl, not sure if it does SSL verification errors tho?
		// this also takes care of SSL ceriticate issues

		if(curl_errno($ch)){
		echo 'error:' . curl_error($ch);
		}
		
		curl_close($ch);

		// @TODO Add configuration to save it to NFS or whever we can pick it up from later and add permissions etc

		file_put_contents( realpath(__DIR__ . "../../../../assets/") . "/" . $this->fileName . ".zip", $output);

		return (filesize(realpath(__DIR__ . "../../../../assets/") . "/" . $this->fileName . ".zip") > 0)? true : false;

	}

}