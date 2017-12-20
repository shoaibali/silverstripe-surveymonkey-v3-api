<?php

/**
 * A job that goes to SurveyMonkey and downloads a CSV for a given SurveyID
 * *
 * @author Shoaib Ali
 * @license BSD http://silverstripe.org/bsd-license/
 */
class ExportSurveyMonkeyResponsesCSVJob extends AbstractQueuedJob implements QueuedJob {
	/**
	 * @param DataObject $SuveyMonkeySurvey
	 */
	public function __construct($SuveyMonkeySurvey = null) {
		// this value is automatically persisted between processing requests for
		// this job
		if ($SuveyMonkeySurvey) {
			$this->surveyID = $SuveyMonkeySurvey;
			$this->currentStep = 0;
			// $this->totalSteps = 2;
		}
	}

	protected function getSurvey() {
		return DataObject::get_by_id('SurveyMonkeySurvey', $this->surveyID);
	}

	/**
	 * Defines the title of the job
	 *
	 * @return string
	 */
	public function getTitle() {
		return _t(
			'SurveyMonkeySurvey.Title',
			"Exporting {title}",
			array('title' => $this->getSurvey()->Title)
		);
	}

	/**
	 * Indicate to the system which queue we think we should be in based
	 * on how many objects we're going to touch on while processing.
	 *
	 * We want to make sure we also set how many steps we think we might need to take to
	 * process everything - note that this does not need to be 100% accurate, but it's nice
	 * to give a reasonable approximation
	 *
	 * @return int
	 */
	public function getJobType() {
		$this->totalSteps = 2;
		return QueuedJob::QUEUED;
	}

	public function setup() {

		// Get the username and password for SurveyMonkey from SiteConfig
        $config = SiteConfig::current_site_config();
		$username = $config->SurveyMonkeyUserName;		
		$password = $config->SurveyMonkeyPassword; // TODO Decryption logic will go here

		
		$this->totalSteps = 1;
	}

	/**
	 * Lets process a single node, and publish it if necessary
	 */
	public function process() {

		$this->currentStep++;

		$this->isComplete = true;
		return;
	}
}
