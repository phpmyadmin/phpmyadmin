<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Selenium TestConfig
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

class TestConfig
{
    private $_loginURL;
    private $_timeoutValue;

    public function __construct()
    {
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

    public function setLoginURL($value)
    {
        $this->_loginURL = $value;
    }

    public function getLoginURL()
    {
        return $this->_loginURL;
    }

    public function setTimeoutValue($value)
    {
        $this->_timeoutValue = $value;
    }

    public function getTimeoutValue()
    {
        return $this->_timeoutValue;
    }

    public function setCurrentBrowser($value)
    {
        $this->currentBrowser = $value;
    }

    public function getCurrentBrowser()
    {
        return $this->currentBrowser;
    }
}

?>
