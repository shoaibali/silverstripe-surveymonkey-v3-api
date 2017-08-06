<?php
class GenerateSurveyMonkeyTokenTask extends BuildTask {

	protected $title = 'Generate your SurveyMonkey Access Token';

	protected $description = 'This task will let you generate SurveyMonkey Access Token, please make sure you have saved your Site Settings with App ID and App Secret in SiteConfiguration - see documentation #configuration section for more details';


	private $config = null;

	private $SSLVerify = FALSE;

	protected static $api_url = "https://api.surveymonkey.net/oauth";



	/**
	 * @TODO Handle Expired tokens
	*/

	public function run($request) {

		if(!Director::is_cli() && !Director::isDev() && !Permission::check('ADMIN')) { echo "Fail";
			return Security::permissionFailure();
		}


		$this->config = SiteConfig::current_site_config();
		$this->SSLVerify = $this->config->SSLVerify;


		$surveyMonkeyClientID = $this->config->SurveyMonkeyClientID;
		$surveyMonkeySecret = $this->config->SurveyMonkeySecret;
		$surveyMonkeyOAuthRedirectURL = $this->config->SurveyMonkeyOAuthRedirectURL;
		$surveyMonkeyAccessToken = $this->config->SurveyMonkeyAccessToken;

		if(	isset($surveyMonkeyClientID) && 
			isset($surveyMonkeySecret) && 
			isset($surveyMonkeyOAuthRedirectURL) &&
			isset($surveyMonkeyAccessToken)
		  ){

			// first request access /permission to 
			// if the $request["code"] is present it means we just got back from SurveyMonkey hence skip to set_access
			if (!empty($request['code'])) {

				// save the access_token in the configuration
				$accessCode = $this->get_token($request["code"], $request["state"]);

				if(isset($accessCode)){

					echo "Your access_token is: <strong>" . $accessCode . "</strong> - it has been stored in the database/site configuration for you!";

					$this->config->SurveryMonkeyAccessCode = $accessCode;
					$this->config->write();

				}

			} else {
				$state = $this->set_access($request); // no poit in passing in $request when redirecting using header()
			}

		} else {
			$msg = 'Missing SurveyMonkey App information under Site Config settings';
			echo $msg;
			user_error($msg, E_USER_WARNING);

		}
	}


	/**
	 * Requests access to the SurveyMonkey App
	 *
	 * Redirects browser to the SurveyMonkey Request for Permission page so
	 * that the widget can gain access to Facebook. A session string is
	 * stored for later verification.
	 *
	 */
	private function set_access($request) {

		// CSRF protection
		if (empty(Session::get('SurveyMonkeyState'))) {
			$session = md5(uniqid(rand(), TRUE));

			Session::set_cookie_secure(false);
			Session::set('SurveyMonkeyState', $session);

		} else {
			// if its not empty already force it to be a new one!
			// Session::clear('SurveyMonkeyState');
		}


		$dialog_url =
			self::$api_url .
			"/authorize" .
			"?response_type=code&".
			"redirect_uri=" . $this->config->SurveyMonkeyOAuthRedirectURL . "&" .
			"client_id=" . $this->config->SurveyMonkeyClientID . "&" .
			// "state=" . $session;
			"state="; // fix the issue with state and sent it with request

		// var_dump(Session::get('SurveyMonkeyState')); die();

		// SilverStripe is amazing like that! not letting me redirect in here .. GG
		// I tried $request, $response and even Director::force_redirect  #fail
		header('Location:' . $dialog_url);
		exit;
	}


	/**
	 * Requests access token from SurveyMonkey
	 *
	 * The access token is used by requests to consume 
	 * api for questions and choices etc
	 *
	 * @return string the access token.
	 *
	 * @access private
	 */
	private function get_token($code, $state) {

		$access_token = NULL;
		$session_state = Session::get('SurveyMonkeyState');

		// var_dump($state);

		// var_dump($session_state);

		// die();

		// check for a matching session
		// if ($state == $session_state) {
		if (is_null($session_state)) {

			$token_url =
				self::$api_url . "/token" .
				"?client_id=" . $this->config->SurveyMonkeyClientID .
				"&client_secret=" . $this->config->SurveyMonkeySecret .
				"&code=" . $code . 
				"&grant_type=authorization_code" . 
				// "&redirect_uri=" . "http://" . $_SERVER["HTTP_HOST"] . $_SERVER['REDIRECT_URL'];
				"&redirect_uri=" . $this->config->SurveyMonkeyOAuthRedirectURL;

			$err_msg = '';
			$response = FALSE;


			$postFields = ['client_id' => $this->config->SurveyMonkeyClientID,
							'client_secret' =>  $this->config->SurveyMonkeySecret,
							'code' => $code,
							'grant_type' => 'authorization_code',
							'redirect_uri' => $this->config->SurveyMonkeyOAuthRedirectURL

							];

			if (in_array('curl', get_loaded_extensions())) {
				$ch = curl_init();

				curl_setopt($ch, CURLOPT_URL, $token_url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->SSLVerify);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch,CURLOPT_POSTFIELDS, $postFields);

				$response = curl_exec($ch);

				if (!$response) {
					$err_msg = '[' . curl_errno($ch) . '] ' .
						curl_error($ch);
				}

				curl_close($ch);
			}


			// check if allow_url_fopen is on
			if (!$response && ini_get('allow_url_fopen')) {
				echo $token_url;die();
				$response = @file_get_contents($token_url);

				if (!$response && empty($err_msg)) {
					$err_msg = 'file_get_contents failed to open URL.';

				}
			}

			// no way to get the access token
			if (!$response && empty($err_msg))
				$err_msg = 'Server Configuration Error: allow_url_fopen is off and cURL is not loaded.';

			if (!$response && !empty($err_msg)) {
				user_error($err_msg, E_USER_ERROR);
				//$this->error_msg_fn( $err_msg );
				return $access_token;
			}

			$params = json_decode($response, TRUE);

			if (isset($params[ 'access_token' ])) {
				$access_token = $params[ 'access_token' ];
			} else {
				$response = json_decode($response, TRUE);
				if (isset($response[ 'error' ]))
					user_error($response[ 'error' ] . ': ' . $response[ 'error_description' ], E_USER_ERROR);
				else
					user_error('No access token returned.  Please double check you have the correct Facebook ID, App ID, and App Secret.', E_USER_ERROR);
			}

		// if the session doesn't match alert the user
		} else {
			user_error('The state does not match. You may be a victim of a man in the middle attack.', E_USER_ERROR);
		}


		return $access_token;
	} // End get_token function
}