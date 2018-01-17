<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Base class for Selenium tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
namespace PhpMyAdmin\Tests\Selenium;

/**
 * Base class for Selenium tests.
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 * @group      selenium
 */
abstract class TestBase extends \PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * mysqli object
     *
     * @access private
     * @var mysqli
     */
    protected $_mysqli;

    /**
     * Name of database for the test
     *
     * @access public
     * @var string
     */
    public $database_name;

    /**
     * Whether Selenium testing should be enabled.
     *
     * @access private
     * @var boolean
     */
    private static $_selenium_enabled = false;

    /**
     * Lists browsers to test
     *
     * @return Array of browsers to test
     */
    public static function browsers()
    {
        if (! empty($GLOBALS['CI_MODE'] && $GLOBALS['CI_MODE'] != 'selenium')) {
            return;
        }
        if (! empty($GLOBALS['TESTSUITE_BROWSERSTACK_USER'])
            && ! empty($GLOBALS['TESTSUITE_BROWSERSTACK_KEY'])
        ) {
            /* BrowserStack integration */
            self::$_selenium_enabled = true;

            $strategy = 'shared';
            $build_local = true;
            $build_id = 'Manual';
            $project_name = 'phpMyAdmin';
            if (getenv('BUILD_TAG')) {
                $build_id = getenv('BUILD_TAG');
                $build_local = false;
                $strategy = 'isolated';
                $project_name = 'phpMyAdmin (Jenkins)';
            } elseif (getenv('TRAVIS_JOB_NUMBER')) {
                $build_id = 'travis-' . getenv('TRAVIS_JOB_NUMBER');
                $build_local = true;
                $strategy = 'isolated';
                $project_name = 'phpMyAdmin (Travis)';
            }

            $capabilities = array(
                'browserstack.user' => $GLOBALS['TESTSUITE_BROWSERSTACK_USER'],
                'browserstack.key' => $GLOBALS['TESTSUITE_BROWSERSTACK_KEY'],
                'browserstack.debug' => false,
                'project' => $project_name,
                'build' => $build_id,
            );

            if ($build_local) {
                $capabilities['browserstack.local'] = $build_local;
                $capabilities['browserstack.localIdentifier'] = $build_id;
                $capabilities['browserstack.debug'] = true;
            }

            $result = array();
            $result[] = array(
                'browserName' => 'chrome',
                'host' => 'hub.browserstack.com',
                'port' => 80,
                'timeout' => 30000,
                'sessionStrategy' => $strategy,
                'desiredCapabilities' => array_merge(
                    $capabilities,
                    array(
                        'os' => 'Windows',
                    )
                )
            );

            /* Only one browser for continuous integration for speed */
            if (empty($GLOBALS['TESTSUITE_FULL'])) {
                return $result;
            }

            /*
            $result[] = array(
                'browserName' => 'Safari',
                'host' => 'hub.browserstack.com',
                'port' => 80,
                'timeout' => 30000,
                'sessionStrategy' => $strategy,
                'desiredCapabilities' => array_merge(
                    $capabilities,
                    array(
                        'os' => 'OS X',
                        'os_version' => 'Mavericks',
                    )
                )
            );
            */
            $result[] = array(
                'browserName' => 'firefox',
                'host' => 'hub.browserstack.com',
                'port' => 80,
                'timeout' => 30000,
                'sessionStrategy' => $strategy,
                'desiredCapabilities' => $capabilities,
            );
            /* TODO: testing is MSIE is currently broken, so disabled
            $result[] = array(
                'browserName' => 'internet explorer',
                'host' => 'hub.browserstack.com',
                'port' => 80,
                'timeout' => 30000,
                'sessionStrategy' => $strategy,
                'desiredCapabilities' => array_merge(
                    $capabilities,
                    array(
                        'os' => 'windows',
                        'os_version' => '7',
                    )
                )
            );
            */
            return $result;
        } elseif (! empty($GLOBALS['TESTSUITE_SELENIUM_HOST'])) {
            self::$_selenium_enabled = true;
            return array(
                array(
                    'browserName' => $GLOBALS['TESTSUITE_SELENIUM_BROWSER'],
                    'host' => $GLOBALS['TESTSUITE_SELENIUM_HOST'],
                    'port' => intval($GLOBALS['TESTSUITE_SELENIUM_PORT']),
                )
            );
        } else {
            return array();
        }
    }

    /**
     * Configures the selenium and database link.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function setUp()
    {
        if (! self::$_selenium_enabled) {
            $this->markTestSkipped('Selenium testing not configured.');
        }

        $caps = $this->getDesiredCapabilities();
        $this->setDesiredCapabilities(
            array_merge(
                $caps,
                array('name' => get_class($this) . '__' . $this->getName())
            )
        );

        parent::setUp();
        $this->setBrowserUrl($GLOBALS['TESTSUITE_URL']);
        $this->_mysqli = new \mysqli(
            $GLOBALS['TESTSUITE_SERVER'],
            $GLOBALS['TESTSUITE_USER'],
            $GLOBALS['TESTSUITE_PASSWORD']
        );
        if ($this->_mysqli->connect_errno) {
            throw new Exception(
                'Failed to connect to MySQL (' . $this->_mysqli->error . ')'
            );
        }
        $this->database_name = $GLOBALS['TESTSUITE_DATABASE']
            . mb_substr(md5(rand()), 0, 7);
        $this->dbQuery(
            'CREATE DATABASE IF NOT EXISTS ' . $this->database_name
        );
        $this->dbQuery(
            'USE ' . $this->database_name
        );
    }

    /**
     * Configures the browser window.
     *
     * @return void
     *
     */
    public function setUpPage()
    {
        $this->currentWindow()->maximize();
    }

    /**
     * Checks whether user is a superuser.
     *
     * @return boolean
     */
    protected function isSuperUser()
    {
        $result = $this->dbQuery('SELECT COUNT(*) FROM mysql.user');
        if ($result !== false) {
            $result->free();
            return true;
        }
        return false;
    }

    /**
     * Skips test if test user is not a superuser.
     *
     * @return void
     */
    protected function skipIfNotSuperUser()
    {
        if (! $this->isSuperUser()) {
            $this->markTestSkipped('Test user is not a superuser.');
        }
    }

    /**
     * Skips test if pmadb is not configured.
     *
     * @return void
     */
    protected function skipIfNotPMADB()
    {
        $this->url('chk_rel.php');
        $this->waitForElement('byId', 'page_content');
        if ($this->isElementPresent('byXPath', '//span[contains(@style, \'color:red\')]')) {
            $this->markTestSkipped(
                'The phpMyAdmin configuration storage is not working.'
            );
        }
    }

    /**
     * Tear Down function for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        if ($this->_mysqli != null) {
            $this->dbQuery('DROP DATABASE IF EXISTS ' . $this->database_name);
            $this->_mysqli->close();
            $this->_mysqli = null;
        }
    }

    /**
     * perform a login
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return void
     */
    public function login($username = '', $password = '')
    {
        if ($username == '') {
            $username = $GLOBALS['TESTSUITE_USER'];
        }
        if ($password == '') {
            $password = $GLOBALS['TESTSUITE_PASSWORD'];
        }
        $this->url('');
        $this->waitForElementNotPresent('byId', 'cfs-style');

        /* Return if already logged in */
        if ($this->isSuccessLogin()) {
            return;
        }
        $usernameField = $this->waitForElement('byId', 'input_username');
        $usernameField->value($username);
        $passwordField = $this->byId('input_password');
        $passwordField->value($password);
        $this->byId('input_go')->click();
    }

    /**
     * Checks whether the login is successful
     *
     * @return boolean
     */
    public function isSuccessLogin()
    {
        return $this->isElementPresent("byXPath", "//*[@id=\"serverinfo\"]");
    }

    /**
    * Checks whether the login is unsuccessful
    *
    * @return boolean
    */
    public function isUnsuccessLogin()
    {
        return $this->isElementPresent("byCssSelector", "div.error");
    }

    /**
    * Used to go to the homepage
    *
    * @return void
    */
    public function gotoHomepage()
    {
        $e = $this->byPartialLinkText("Server: ");
        $e->click();
        $this->waitAjax();
    }

    /**
     * Executes a database query
     *
     * @param string $query SQL Query to be executed
     *
     * @return void|boolean|mysqli_result
     *
     * @throws Exception
     */
    public function dbQuery($query)
    {
        return $this->_mysqli->query($query);
    }

    /**
     * Check if user is logged in to phpmyadmin
     *
     * @return boolean Where or not user is logged in
     */
    public function isLoggedIn()
    {
        return $this->isElementPresent(
            'byXPath', '//*[@id="serverinfo"]/a[1]'
        );
    }

    /**
     * Perform a logout, if logged in
     *
     * @return void
     */
    public function logOutIfLoggedIn()
    {
        if ($this->isLoggedIn()) {
            $this->byCssSelector("img.icon.ic_s_loggoff")->click();
        }
    }

    /**
     * Wait for an element to be present on the page
     *
     * @param string $func Locate using - byCss, byXPath, etc
     * @param string $arg  Selector
     *
     * @return PHPUnit_Extensions_Selenium2TestCase_Element  Element waited for
     */
    public function waitForElement($func, $arg)
    {
        try {
            return call_user_func_array(
                array($this, $func), array($arg)
            );
        } catch(\Exception $e) {
            // Element not present, fall back to waiting
        }
        $this->timeouts()->implicitWait(10000);
        $element = call_user_func_array(
            array($this, $func), array($arg)
        );
        $this->timeouts()->implicitWait(0);
        return $element;
    }

    /**
     * Wait for an element to disappear
     *
     * @param string $func Locate using - byCss, byXPath, etc
     * @param string $arg  Selector
     *
     * @return bool Whether or not the element disappeared
     */
    public function waitForElementNotPresent($func, $arg)
    {
        while (true) {
            if (!$this->isElementPresent($func, $arg)) {
                return true;
            }
            usleep(5000);
        }
    }

    /**
     * Check if element is present or not
     *
     * @param string $func Locate using - byCss, byXPath, etc
     * @param string $arg  Selector
     *
     * @return bool Whether or not the element is present
     */
    public function isElementPresent($func, $arg)
    {
        try {
            $element = call_user_func_array(
                array($this, $func), array($arg)
            );
        } catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            // Element not present
            return false;
        } catch (\InvalidArgumentException $e) {
            // Element not present
            return false;
        }
        // Element Present
        return true;
    }

    /**
     * Get table cell data by the ID of the table
     *
     * @param string $tableID Table identifier
     * @param int    $row     Table row
     * @param int    $column  Table column
     *
     * @return text Data from the particular table cell
     */
    public function getCellByTableId($tableID, $row, $column)
    {
        $sel = "table#{$tableID} tbody tr:nth-child({$row}) "
            . "td:nth-child({$column})";
        $element = $this->byCssSelector(
            $sel
        );
        $text = $element->attribute('innerText');

        return ($text && is_string($text)) ? trim($text) : '';
    }

    /**
     * Get table cell data by the class attribute of the table
     *
     * @param string $tableClass Class of the table
     * @param int    $row        Table row
     * @param int    $column     Table column
     *
     * @return text Data from the particular table cell
     */
    public function getCellByTableClass($tableClass, $row, $column)
    {
        $sel = "table.{$tableClass} tbody tr:nth-child({$row}) "
            . "td:nth-child({$column})";
        $element = $this->byCssSelector(
            $sel
        );
        $text = $element->attribute('innerText');

        return ($text && is_string($text)) ? trim($text) : '';
    }

    /**
     * Wrapper around keys method to not use it on not supported
     * browsers.
     *
     * @param string $text Keys to send
     *
     * @return void
     */
    public function keys($text)
    {
        /**
         * Not supported in Safari Webdriver, see
         * https://github.com/seleniumhq/selenium-google-code-issue-archive/issues/4136
         */
        if (mb_strtolower($this->getBrowser()) == 'safari') {
            $this->markTestSkipped('Can not send keys to Safari browser.');
        }
        parent::keys($text);
    }

    /**
     * Wrapper around moveto method to not use it on not supported
     * browsers.
     *
     * @param object $element element
     *
     * @return void
     */
    public function moveto($element)
    {
        /**
         * Not supported in Safari Webdriver, see
         * https://github.com/seleniumhq/selenium-google-code-issue-archive/issues/4136
         */
        if (mb_strtolower($this->getBrowser()) == 'safari') {
            $this->markTestSkipped('MoveTo not supported on Safari browser.');
        }
        parent::moveto($element);
    }

    /**
     * Wrapper around alertText method to not use it on not supported
     * browsers.
     *
     * @return mixed
     */
    public function alertText()
    {
        /**
         * Not supported in Safari Webdriver, see
         * https://github.com/seleniumhq/selenium-google-code-issue-archive/issues/4136
         */
        if (mb_strtolower($this->getBrowser()) == 'safari') {
            $this->markTestSkipped('Alerts not supported on Safari browser.');
        }
        return parent::alertText();
    }

    /**
     * Type text in textarea (CodeMirror enabled)
     *
     * @param string $text  Text to type
     * @param int    $index Index of CodeMirror instance to write to
     *
     * @return void
     */
    public function typeInTextArea($text, $index=0)
    {
        $this->waitForElement('byCssSelector', 'div.cm-s-default');
        $this->execute(
            array(
                'script' => "$('.cm-s-default')[$index].CodeMirror.setValue('" . $text . "');",
                'args' => array()
            )
        );
    }

    /**
     * Kills the More link in the menu
     *
     * @return void
     */
    public function expandMore()
    {
        $ele = null;
        try {
            $ele = $this->waitForElement('byCssSelector', 'li.submenu > a');
        } catch (\PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            return;
        }

        // Will never be 'null' here
        $ele->click();
        $this->waitForElement('byCssSelector', 'li.submenuhover > a');

        $this->waitUntil(function () {
            return $this->isElementPresent(
                'byCssSelector',
                'li.submenuhover.submenu.shown'
            );
        }, 5000);
    }

    /**
     * Navigates browser to a table page.
     *
     * @param string $table Name of table
     *
     * @return void
     */
    public function navigateTable($table)
    {
        $this->navigateDatabase($this->database_name);

        // go to table page
        $this->waitForElement(
            "byXPath",
            "//th//a[contains(., '$table')]"
        )->click();
        $this->waitAjax();

        $this->waitForElement(
            "byXPath",
            "//a[@class='tabactive' and contains(., 'Browse')]"
        );
    }

    /**
     * Navigates browser to a database page.
     *
     * @param string $database Name of database
     *
     * @return void
     */
    public function navigateDatabase($database, $gotoHomepageRequired = false)
    {
        if ($gotoHomepageRequired) {
            $this->gotoHomepage();
        }

        // Go to server databases
        $this->waitForElement('byPartialLinkText','Databases')->click();
        $this->waitAjax();

        // go to specific database page
        $this->waitForElement(
            'byXPath',
            '//tr[(contains(@class, "db-row"))]//a[contains(., "' . $this->database_name . '")]'
        )->click();
        $this->waitAjax();

        // Wait for it to load
        $this->waitForElement(
            "byXPath",
            "//a[@class='tabactive' and contains(., 'Structure')]"
        );
    }

    /**
     * Scrolls to a coordinate such that the element with given id is visible
     *
     * @param string $element_id Id of the element
     * @param int    $offset     Offset from Y-coordinate of element
     *
     * @return void
     */
    public function scrollIntoView($element_id, $offset = 70)
    {
        // 70pt offset by-default so that the topmenu does not cover the element
        $this->execute(
            array(
                'script' => 'var position = document.getElementById("'
                            . $element_id . '").getBoundingClientRect();'
                            . 'window.scrollBy(0, position.top-(' . $offset . '));',
                'args'   => array()
            )
        );
    }

    /**
     * Scroll to the bottom of page
     *
     * @return void
     */
    public function scrollToBottom()
    {
        $this->execute(
            array(
                'script' => 'window.scrollTo(0,document.body.scrollHeight);',
                'args' => array()
            )
        );
    }

    /**
     * Wait for AJAX completion
     *
     * @return void
     */
    public function waitAjax()
    {
        /* Wait while code is loading */
        while ($this->execute(array('script' => 'return AJAX.active;', 'args' => array()))) {
            usleep(5000);
        }
    }

    /**
     * Wait for AJAX message disappear
     *
     * @return void
     */
    public function waitAjaxMessage()
    {
        /* Get current message count */
        $ajax_message_count = $this->execute(
            array(
                'script' => 'return ajax_message_count;',
                'args' => array()
            )
        );
        /* Ensure the popup is gone */
        $this->waitForElementNotPresent(
            'byId',
            'ajax_message_num_' . $ajax_message_count
        );
    }

    /**
     * Mark unsuccessful tests as 'Failures' on Browerstack
     *
     * @return void
     */
    public function onNotSuccessfulTest($e)
    {
        // If this is being run on Browerstack,
        // mark the test on Browerstack as failure
        if (! empty($GLOBALS['TESTSUITE_BROWSERSTACK_USER'])
            && ! empty($GLOBALS['TESTSUITE_BROWSERSTACK_KEY'])
            && ! ($e instanceof PHPUnit_Framework_SkippedTestError)
            && ! ($e instanceof PHPUnit_Framework_IncompleteTestError)
        ) {
            $SESSION_REST_URL = 'https://www.browserstack.com/automate/sessions/';
            $sessionId = $this->getSessionId();
            $payload = json_encode(
                array(
                    'status' => 'failed',
                    'reason' => $e->getMessage()
                )
            );

            $ch = curl_init();
            curl_setopt(
                $ch,
                CURLOPT_URL,
                $SESSION_REST_URL . $sessionId . ".json"
            );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt(
                $ch,
                CURLOPT_USERPWD,
                $GLOBALS['TESTSUITE_BROWSERSTACK_USER']
                    . ":" . $GLOBALS['TESTSUITE_BROWSERSTACK_KEY']
            );

            $headers = array();
            $headers[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                echo 'Error: ' . curl_error($ch);
            }
            curl_close ($ch);
        }

        // Call parent's onNotSuccessful to handle everything else
        parent::onNotSuccessfulTest($e);
    }
}
