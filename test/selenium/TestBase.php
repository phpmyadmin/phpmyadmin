<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Base class for Selenium tests
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */

/**
 * Base class for Selenium tests.
 *
 * @package    PhpMyAdmin-test
 * @subpackage Selenium
 */
abstract class PMA_SeleniumBase extends PHPUnit_Extensions_Selenium2TestCase
{
    /**
     * mysqli object
     *
     * @access private
     * @var mysqli
     */
    private $_mysqli;

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
    private static $_selenium_enabled = False;

    /**
     * Lists browsers to test
     *
     * @return Array of browsers to test
     */
    public static function browsers()
    {
        if (! empty($GLOBALS['TESTSUITE_BROWSERSTACK_USER'])
            && ! empty($GLOBALS['TESTSUITE_BROWSERSTACK_KEY'])
            ) {
            /* BrowserStack integration */
            self::$_selenium_enabled = True;

            $build_id = 'Manual';
            if (getenv('BUILD_TAG')) {
                $build_id = getenv('BUILD_TAG');
            } elseif (getenv('TRAVIS_JOB_NUMBER')) {
                $build_id = 'travis-' . getenv('TRAVIS_JOB_NUMBER');
            }

            $result = array();
            $result[] = array(
                'browserName' => 'chrome',
                'host' => 'hub.browserstack.com',
                'port' => 80,
                'timeout' => 30000,
                'desiredCapabilities' => array(
                    'browserstack.user' => $GLOBALS['TESTSUITE_BROWSERSTACK_USER'],
                    'browserstack.key' => $GLOBALS['TESTSUITE_BROWSERSTACK_KEY'],
                    'browserstack.debug' => True,
                    'project' => 'phpMyAdmin',
                    'build' => $build_id,
                )
            );
            if (!empty($GLOBALS['TESTSUITE_FULL'])) {
                $result[] = array(
                    'browserName' => 'firefox',
                    'host' => 'hub.browserstack.com',
                    'port' => 80,
                    'timeout' => 30000,
                    'desiredCapabilities' => array(
                        'browserstack.user' => $GLOBALS['TESTSUITE_BROWSERSTACK_USER'],
                        'browserstack.key' => $GLOBALS['TESTSUITE_BROWSERSTACK_KEY'],
                        'project' => 'phpMyAdmin',
                        'build' => $build_id,
                    )
                );
                $result[] = array(
                    'browserName' => 'internet explorer',
                    'host' => 'hub.browserstack.com',
                    'port' => 80,
                    'timeout' => 30000,
                    'desiredCapabilities' => array(
                        'browserstack.user' => $GLOBALS['TESTSUITE_BROWSERSTACK_USER'],
                        'browserstack.key' => $GLOBALS['TESTSUITE_BROWSERSTACK_KEY'],
                        'project' => 'phpMyAdmin',
                        'build' => $build_id,
                        'os' => 'windows',
                        'os_version' => '7',
                    )
                );
                $result[] = array(
                    'browserName' => 'Safari',
                    'host' => 'hub.browserstack.com',
                    'port' => 80,
                    'timeout' => 30000,
                    'desiredCapabilities' => array(
                        'browserstack.user' => $GLOBALS['TESTSUITE_BROWSERSTACK_USER'],
                        'browserstack.key' => $GLOBALS['TESTSUITE_BROWSERSTACK_KEY'],
                        'project' => 'phpMyAdmin',
                        'build' => $build_id,
                        'os' => 'OS X',
                        'os_version' => 'Mavericks',
                    )
                );
            }
            return $result;
        } elseif (! empty($GLOBALS['TESTSUITE_SELENIUM_HOST'])) {
            self::$_selenium_enabled = True;
            return array(
                array(
                    'browserName' => $GLOBALS['TESTSUITE_SELENIUM_BROWSER'],
                    'host' => $GLOBALS['TESTSUITE_SELENIUM_HOST'],
                    'port' => $GLOBALS['TESTSUITE_SELENIUM_PORT'],
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
        $this->_mysqli = new mysqli(
            $GLOBALS['TESTSUITE_SERVER'],
            $GLOBALS['TESTSUITE_USER'],
            $GLOBALS['TESTSUITE_PASSWORD']
        );
        if ($this->_mysqli->connect_errno) {
            throw new Exception(
                'Failed to connect to MySQL (' . $this->_mysqli->error . ')'
            );
        }
        $this->database_name = $GLOBALS['TESTSUITE_DATABASE'] . substr(md5(rand()), 0, 7);
        $this->dbQuery('CREATE DATABASE IF NOT EXISTS ' . $this->database_name);
        $this->dbQuery('USE ' . $this->database_name);
    }

    /**
     * Checks whether user is a superuser.
     *
     * @return boolean
     */
    protected function isSuperUser()
    {
        $result = $this->dbQuery('SELECT COUNT(*) FROM mysql.user');
        if ($result !== False) {
            $result::free();
            return True;
        }
        return False;
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
        if ($this->isElementPresent("byXPath", "//*[@color=\"red\"]")) {
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
        $usernameField = $this->byId('input_username');
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
        if ($this->isElementPresent("byXPath", "//*[@id=\"serverinfo\"]")) {
            return true;
        } else {
            return false;
        }
    }

    /**
    * Checks whether the login is unsuccessful
    *
    * @return boolean
    */
    public function isUnsuccessLogin()
    {
        if ($this->isElementPresent("byCssSelector", "div.error")) {
            return true;
        } else {
            return false;
        }
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
        $this->waitForElementNotPresent('byCssSelector', 'div#loading_parent');
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
        return $this->isElementPresent('byXPath', '//*[@id="serverinfo"]/a[1]');
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
            usleep(100);
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
        } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            // Element not present
            return false;
        }
        // Element Present
        return true;
    }

    /**
     * Get table cell data
     *
     * @param string $tableId Table identifier
     * @param int    $row     Table row
     * @param int    $column  Table column
     *
     * @return text Data from the particular table cell
     */
    public function getTable($tableID, $row, $column)
    {
        $sel = "table#{$tableID} tbody tr:nth-child({$row}) td:nth-child({$column})";
        $element = $this->byCssSelector(
            $sel
        );
        return $element->text();
    }

    /**
     * Type text in textarea (CodeMirror enabled)
     *
     * @param string $text Text to type
     *
     * @return void
     */
    public function typeInTextArea($text)
    {
        $this->byClassName("CodeMirror-scroll")->click();
        $this->keys($text);
    }

    /**
     * Kills the More link in the menu
     *
     * @return void
     */
    public function expandMore()
    {
        $this->execute(array(
            'script' => "$('#topmenu').menuResizer('destroy');",
            'args' => array()
        ));
        usleep(100);
    }
}
?>
