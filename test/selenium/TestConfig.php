<?php

class TestConfig {

	private $loginURL;
	private $timeoutValue;

	public function __construct() {
		$xml = simplexml_load_file("phpunit.xml.dist");
		$xmlDoc = new DOMDocument();
		$xmlDoc->load('phpunit.xml.dist');
		$searchNode = $xmlDoc->getElementsByTagName("browser");
		foreach ($searchNode as $searchNode) {
			$this->setCurrentBrowser($searchNode->getAttribute('browser'));
			$this->setTimeoutValue($searchNode->getAttribute('timeout'));
		}
		$this->setLoginURL(TESTSUITE_PHPMYADMIN_HOST . TESTSUITE_PHPMYADMIN_URL);
	}

	public function setLoginURL($value) {

		$this->loginURL = $value;
	}

	public function getLoginURL() {

		return $this->loginURL;
	}

	public function setTimeoutValue($value) {

		$this->timeoutValue = $value;
	}

	public function getTimeoutValue() {

		return $this->timeoutValue;
	}

	public function setCurrentBrowser($value) {

		$this->currentBrowser = $value;
	}

	public function getCurrentBrowser() {

		return $this->currentBrowser;
	}

}

?>
