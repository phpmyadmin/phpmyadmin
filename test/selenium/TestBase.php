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
 * @group      selenium
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
    private static $_selenium_enabled = false;

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
            self::$_selenium_enabled = true;

            $strategy = 'shared';
            $build_local = false;
            $build_id = 'Manual';
            $project_name = 'phpMyAdmin';
            if (getenv('BUILD_TAG')) {
                $build_id = getenv('BUILD_TAG');
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
                'desiredCapabilities' => $capabilities,
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
        $this->database_name = $GLOBALS['TESTSUITE_DATABASE']
            . /*overload*/mb_substr(md5(rand()), 0, 7);
        $this->dbQuery(
            'CREATE DATABASE IF NOT EXISTS ' . $this->database_name
        );
        $this->dbQuery(
            'USE ' . $this->database_name
        );
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
     * Sleeps while waiting for browser to perform an action.
     *
     * @todo This method should not be used, but rather there would be
     *       explicit waiting for some elements.
     *
     * @return void
     */
    public function sleep()
    {
        usleep(5000);
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
        return $element->text();
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
        return $element->text();
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
         * http://code.google.com/p/selenium/issues/detail?id=4136
         */
        if (/*overload*/mb_strtolower($this->getBrowser()) == 'safari') {
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
         * http://code.google.com/p/selenium/issues/detail?id=4136
         */
        if (/*overload*/mb_strtolower($this->getBrowser()) == 'safari') {
            $this->markTestSkipped('MoveTo not supported on Safari browser.');
        }
        parent::moveto($element);
    }

    /**
     * Wrapper around alertText method to not use it on not supported
     * browsers.
     *
     * @return void
     */
    public function alertText()
    {
        /**
         * Not supported in Safari Webdriver, see
         * http://code.google.com/p/selenium/issues/detail?id=4136
         */
        if (/*overload*/mb_strtolower($this->getBrowser()) == 'safari') {
            $this->markTestSkipped('Alerts not supported on Safari browser.');
        }
        return parent::alertText();
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
        /**
         * Firefox needs some escaping of a text, see
         * http://code.google.com/p/selenium/issues/detail?id=1723
         */
        if (/*overload*/mb_strtolower($this->getBrowser()) == 'firefox') {
            $text = str_replace(
                "(",
                PHPUnit_Extensions_Selenium2TestCase_Keys::SHIFT
                . PHPUnit_Extensions_Selenium2TestCase_Keys::NUMPAD9
                . PHPUnit_Extensions_Selenium2TestCase_Keys::NULL,
                $text
            );
        }

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
        try {
            $this->waitForElement('byCssSelector', 'li.submenu > a');
        } catch (PHPUnit_Extensions_Selenium2TestCase_WebDriverException $e) {
            return;
        }
        /* We need to resize to ensure it fits into accessible area */
        $this->execute(
            array(
                'script' => "$('#topmenu').css('font-size', '50%');"
                    . "$(window).resize()",
                'args' => array()
            )
        );
        $this->sleep();
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
        // go to database page
        $this->waitForElement("byLinkText", $this->database_name)->click();

        /* Wait for loading and expanding tree */
        $this->waitForElement(
            'byCssSelector',
            'li.last.table'
        );

        /* TODO: Timing issue of expanding navigation tree */
        $this->sleep();

        // go to table page
        $this->waitForElement(
            "byXPath",
            "//*[@id='pma_navigation_tree_content']//a[contains(., '$table')]"
        )->click();

        // Wait for it to load
        $this->waitForElement(
            "byXPath",
            "//a[@class='tabactive' and contains(., 'Browse')]"
        );
    }
}
?>
