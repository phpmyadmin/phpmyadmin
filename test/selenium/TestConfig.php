<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test configuration class for selenium test cases
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

/**
 * Selenium TestConfig
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
class TestConfig
{
    /**
     * Login URL
     *
     * @var string
     */
    private $_loginURL;

    /**
     * Timeout value
     *
     * @var int
     */
    private $_timeoutValue;

    /**
     * Creates a new class instance
     *
     * @return new TestConfig object
     */
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

    /**
     * Sets the login url
     *
     * @param string $value login url
     *
     * @return void
     */
    public function setLoginURL($value)
    {
        $this->_loginURL = $value;
    }

    /**
     * Returns the loginURL
     *
     * @return string loginURL
     */
    public function getLoginURL()
    {
        return $this->_loginURL;
    }

    /**
     * Sets the timeout value
     *
     * @param int $value timeout value
     *
     * @return void
     */
    public function setTimeoutValue($value)
    {
        $this->_timeoutValue = $value;
    }

    /**
     * Returns the timeout value
     *
     * @return int timeout value
     */
    public function getTimeoutValue()
    {
        return $this->_timeoutValue;
    }

    /**
     * Sets the current browser
     *
     * @param string $value browser type
     *
     * @return void
     */
    public function setCurrentBrowser($value)
    {
        $this->currentBrowser = $value;
    }

    /**
     * Returns the current browser type
     *
     * @return string browser type
     */
    public function getCurrentBrowser()
    {
        return $this->currentBrowser;
    }
}
?>
