<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Selenium;

use Closure;
use Exception;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Exception\InvalidSelectorException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\WebDriverException;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverSelect;
use InvalidArgumentException;
use PHPUnit\Framework\SkippedTest;
use PHPUnit\Framework\TestCase;
use Throwable;

use function bin2hex;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function curl_init;
use function curl_setopt;
use function current;
use function end;
use function file_put_contents;
use function getenv;
use function is_bool;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strtolower;
use function preg_match;
use function property_exists;
use function random_bytes;
use function reset;
use function sprintf;
use function str_ends_with;
use function strlen;
use function substr;
use function time;
use function trim;
use function usleep;

use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const CURLOPT_USERPWD;
use const DIRECTORY_SEPARATOR;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;

abstract class TestBase extends TestCase
{
    protected RemoteWebDriver $webDriver;

    /**
     * Name of database for the test
     */
    public string $databaseName;

    /**
     * The session Id (Browserstack)
     */
    protected string $sessionId;

    /**
     * The window handle for the SQL tab
     */
    private string|null $sqlWindowHandle = null;

    private const SESSION_REST_URL = 'https://api.browserstack.com/automate/sessions/';

    /**
     * Create a test database for this test class
     */
    protected static bool $createDatabase = true;

    /**
     * Did the test create the phpMyAdmin storage database ?
     */
    private bool $hadStorageDatabaseInstall = false;

    /**
     * Configures the selenium and database link.
     *
     * @throws Exception
     */
    protected function setUp(): void
    {
        /**
         * Needs to be implemented
         *
         * @ENV TESTSUITE_SELENIUM_COVERAGE
         * @ENV TESTSUITE_FULL
         */
        parent::setUp();

        if ($this->getHubUrl() === '') {
            self::markTestSkipped('Selenium testing is not configured.');
        }

        if ($this->getTestSuiteUrl() === '') {
            self::markTestSkipped('The ENV "TESTSUITE_URL" is not defined.');
        }

        if ($this->getTestSuiteUserLogin() === '') {
            //TODO: handle config mode
            self::markTestSkipped(
                'The ENV "TESTSUITE_USER" is not defined, you may also want to define "TESTSUITE_PASSWORD".',
            );
        }

        $capabilities = $this->getCapabilities();
        $this->addCapabilities($capabilities);
        $url = $this->getHubUrl();

        $this->webDriver = RemoteWebDriver::create($url, $capabilities);

        // The session Id is only used by BrowserStack
        if ($this->hasBrowserstackConfig()) {
            $this->sessionId = $this->webDriver->getSessionID();
        }

        $this->navigateTo('');
        $this->webDriver->manage()->window()->maximize();

        if (! static::$createDatabase) {
            // Stop here, we were not asked to create a database
            return;
        }

        $this->createDatabase();
    }

    /**
     * Create a test database
     */
    protected function createDatabase(): void
    {
        $this->databaseName = $this->getDbPrefix() . bin2hex(random_bytes(4));
        $this->dbQuery(
            'CREATE DATABASE IF NOT EXISTS `' . $this->databaseName . '`; USE `' . $this->databaseName . '`;',
        );
        static::$createDatabase = true;
    }

    public function getDbPrefix(): string
    {
        $envVar = getenv('TESTSUITE_DATABASE_PREFIX');
        if ($envVar) {
            return $envVar;
        }

        return '';
    }

    private function getBrowserStackCredentials(): string
    {
        return (string) getenv('TESTSUITE_BROWSERSTACK_USER') . ':' . (string) getenv('TESTSUITE_BROWSERSTACK_KEY');
    }

    protected function getTestSuiteUserLogin(): string
    {
        $user = getenv('TESTSUITE_USER');

        return $user === false ? '' : $user;
    }

    protected function getTestSuiteUserPassword(): string
    {
        $user = getenv('TESTSUITE_PASSWORD');

        return $user === false ? '' : $user;
    }

    protected function getTestSuiteUrl(): string
    {
        $user = getenv('TESTSUITE_URL');

        return $user === false ? '' : $user;
    }

    /**
     * Has CI config ( CI_MODE == selenium )
     */
    public function hasCIConfig(): bool
    {
        $mode = getenv('CI_MODE');
        if (empty($mode)) {
            return false;
        }

        return $mode === 'selenium';
    }

    /**
     * Has ENV variables set for Browserstack
     */
    public function hasBrowserstackConfig(): bool
    {
        return ! empty(getenv('TESTSUITE_BROWSERSTACK_USER'))
            && ! empty(getenv('TESTSUITE_BROWSERSTACK_KEY'));
    }

    /**
     * Has ENV variables set for local Selenium server
     */
    public function hasSeleniumConfig(): bool
    {
        return ! empty(getenv('TESTSUITE_SELENIUM_HOST'))
            && ! empty(getenv('TESTSUITE_SELENIUM_PORT'));
    }

    /**
     * Get the selenium hub url
     */
    private function getHubUrl(): string
    {
        if ($this->hasBrowserstackConfig()) {
            return 'https://'
            . $this->getBrowserStackCredentials() .
            '@hub-cloud.browserstack.com/wd/hub';
        }

        if ($this->hasSeleniumConfig()) {
            return 'http://'
            . (string) getenv('TESTSUITE_SELENIUM_HOST') . ':'
            . (string) getenv('TESTSUITE_SELENIUM_PORT') . '/wd/hub';
        }

        return '';
    }

    /**
     * Navigate to URL
     *
     * @param string $url The URL
     */
    private function navigateTo(string $url): void
    {
        $suiteUrl = getenv('TESTSUITE_URL');
        if ($suiteUrl === false) {
            $suiteUrl = '';
        }

        if (str_ends_with($suiteUrl, '/')) {
            $url = $suiteUrl . $url;
        } else {
            $url = $suiteUrl . '/' . $url;
        }

        $this->webDriver->get($url);
    }

    /**
     * Get the current running test name
     *
     * Usefull for browserstack
     *
     * @see https://github.com/phpmyadmin/phpmyadmin/pull/14595#issuecomment-418541475
     * Reports the name of the test to browserstack
     */
    public function getTestName(): string
    {
        $className = substr(static::class, strlen('PhpMyAdmin\Tests\Selenium\\'));

        return $className . ': ' . $this->name();
    }

    /**
     * Add specific capabilities
     *
     * @param DesiredCapabilities $capabilities The capabilities object
     */
    public function addCapabilities(DesiredCapabilities $capabilities): void
    {
        $buildLocal = true;
        $buildId = 'Manual';
        $projectName = 'phpMyAdmin';
        $buildTagEnv = getenv('BUILD_TAG');
        $githubActionEnv = getenv('GITHUB_ACTION');

        if ($buildTagEnv) {
            $buildId = $buildTagEnv;
            $buildLocal = false;
            $projectName = 'phpMyAdmin (Jenkins)';
        } elseif ($githubActionEnv) {
            $buildId = 'github-' . $githubActionEnv;
            $buildLocal = true;
            $projectName = 'phpMyAdmin (GitHub - Actions)';
        }

        if (! $buildLocal) {
            return;
        }

        $capabilities->setCapability(
            'bstack:options',
            [
                'os' => 'Windows',
                'osVersion' => '10',
                'resolution' => '1920x1080',
                'projectName' => $projectName,
                'sessionName' => $this->getTestName(),
                'buildName' => $buildId,
                'localIdentifier' => $buildId,
                'local' => $buildLocal,
                'debug' => false,
                'consoleLogs' => 'verbose',
                'networkLogs' => true,
            ],
        );
    }

    /**
     * Get basic capabilities
     */
    public function getCapabilities(): DesiredCapabilities
    {
        switch (getenv('TESTSUITE_SELENIUM_BROWSER')) {
            case 'chrome':
            default:
                $capabilities = DesiredCapabilities::chrome();
                $chromeOptions = new ChromeOptions();
                $chromeOptions->addArguments(['--lang=en']);
                $capabilities->setCapability(ChromeOptions::CAPABILITY_W3C, $chromeOptions);
                $capabilities->setCapability(
                    'loggingPrefs',
                    ['browser' => 'ALL'],
                );

                if ($this->hasCIConfig() && $this->hasBrowserstackConfig()) {
                    $capabilities->setCapability(
                        'os',
                        'Windows', // Force windows
                    );
                    $capabilities->setCapability(
                        'os_version',
                        '10', // Force windows 10
                    );
                    $capabilities->setCapability(
                        'browser_version',
                        '80.0', // Force chrome 80.0
                    );
                    $capabilities->setCapability('resolution', '1920x1080');
                }

                return $capabilities;

            case 'safari':
                $capabilities = DesiredCapabilities::safari();
                if ($this->hasCIConfig() && $this->hasBrowserstackConfig()) {
                    $capabilities->setCapability(
                        'os',
                        'OS X', // Force OS X
                    );
                    $capabilities->setCapability(
                        'os_version',
                        'Sierra', // Force OS X Sierra
                    );
                    $capabilities->setCapability(
                        'browser_version',
                        '10.1', // Force Safari 10.1
                    );
                }

                return $capabilities;

            case 'edge':
                $capabilities = DesiredCapabilities::microsoftEdge();
                if ($this->hasCIConfig() && $this->hasBrowserstackConfig()) {
                    $capabilities->setCapability(
                        'os',
                        'Windows', // Force windows
                    );
                    $capabilities->setCapability(
                        'os_version',
                        '10', // Force windows 10
                    );
                    $capabilities->setCapability(
                        'browser_version',
                        'insider preview', // Force Edge insider preview
                    );
                }

                return $capabilities;
        }
    }

    /**
     * Checks whether the user is a superuser.
     */
    protected function isSuperUser(): bool
    {
        return $this->dbQuery('SELECT COUNT(*) FROM mysql.user');
    }

    /**
     * Skips test if test user is not a superuser.
     */
    protected function skipIfNotSuperUser(): void
    {
        if ($this->isSuperUser()) {
            return;
        }

        self::markTestSkipped('Test user is not a superuser.');
    }

    /**
     * Use the fix relation button to install phpMyAdmin storage
     */
    protected function fixUpPhpMyAdminStorage(): bool
    {
        $this->navigateTo('index.php?route=/check-relations');

        $fixTextSelector = '//div[@class="alert alert-primary" and contains(., "Create a database named")]/a';
        if ($this->isElementPresent('xpath', $fixTextSelector)) {
            $this->byXPath($fixTextSelector)->click();
            $this->waitAjax();

            return true;
        }

        return false;
    }

    /**
     * Skips test if pmadb is not configured.
     */
    protected function skipIfNotPMADB(): void
    {
        $this->navigateTo('index.php?route=/check-relations');
        $pageContent = $this->waitForElement('id', 'page_content');
        if (preg_match('/Configuration of pmadb… not OK/i', $pageContent->getText()) !== 1) {
            return;
        }

        if (! $this->fixUpPhpMyAdminStorage()) {
            self::markTestSkipped('The phpMyAdmin configuration storage is not working.');
        }

        // If it failed the code already has exited with markTestSkipped
        $this->hadStorageDatabaseInstall = true;
    }

    /**
     * perform a login
     *
     * @param string $username Username
     * @param string $password Password
     */
    public function login(string $username = '', string $password = ''): void
    {
        $this->logOutIfLoggedIn();
        if ($username === '') {
            $username = $this->getTestSuiteUserLogin();
        }

        if ($password === '') {
            $password = $this->getTestSuiteUserPassword();
        }

        $this->navigateTo('');
        /* Wait while page */
        while ($this->webDriver->executeScript('return document.readyState !== "complete";')) {
            usleep(5000);
        }

        // Return if already logged in
        if ($this->isSuccessLogin()) {
            return;
        }

        // Select English if the Language selector is available
        if ($this->isElementPresent('id', 'languageSelect')) {
            $this->selectByLabel($this->byId('languageSelect'), 'English');
        }

        // Clear the input for Microsoft Edge (remembers the username)
        $this->waitForElement('id', 'input_username')->clear()->click()->sendKeys($username);
        $this->byId('input_password')->click()->sendKeys($password);
        $this->byId('input_go')->click();
    }

    /**
     * Get element by Id
     *
     * @param string $id The element ID
     */
    public function byId(string $id): RemoteWebElement
    {
        return $this->webDriver->findElement(WebDriverBy::id($id));
    }

    /**
     * Get element by css selector
     *
     * @param string $selector The element css selector
     */
    public function byCssSelector(string $selector): RemoteWebElement
    {
        return $this->webDriver->findElement(WebDriverBy::cssSelector($selector));
    }

    /**
     * Get element by xpath
     *
     * @param string $xpath The xpath
     */
    public function byXPath(string $xpath): RemoteWebElement
    {
        return $this->webDriver->findElement(WebDriverBy::xpath($xpath));
    }

    /**
     * Get element by linkText
     *
     * @param string $linkText The link text
     */
    public function byLinkText(string $linkText): RemoteWebElement
    {
        return $this->webDriver->findElement(WebDriverBy::linkText($linkText));
    }

    /**
     * Double click
     */
    public function doubleclick(): void
    {
        $this->webDriver->action()->doubleClick()->perform();
    }

    /**
     * Simple click
     */
    public function click(): void
    {
        $this->webDriver->action()->click()->perform();
    }

    /**
     * Get element by byPartialLinkText
     *
     * @param string $partialLinkText The partial link text
     */
    public function byPartialLinkText(string $partialLinkText): RemoteWebElement
    {
        return $this->webDriver->findElement(WebDriverBy::partialLinkText($partialLinkText));
    }

    public function isSafari(): bool
    {
        $capabilities = $this->webDriver->getCapabilities();

        return $capabilities !== null && mb_strtolower($capabilities->getBrowserName()) === 'safari';
    }

    /**
     * Get element by name
     *
     * @param string $name The name
     */
    public function byName(string $name): RemoteWebElement
    {
        return $this->webDriver->findElement(WebDriverBy::name($name));
    }

    /**
     * Checks whether the login is successful
     */
    public function isSuccessLogin(): bool
    {
        return $this->isElementPresent('xpath', '//*[@id="server-breadcrumb"]');
    }

    /**
     * Checks whether the login is unsuccessful
     */
    public function isUnsuccessLogin(): bool
    {
        return $this->isElementPresent('cssSelector', 'div #pma_errors');
    }

    /**
     * Used to go to the homepage
     */
    public function gotoHomepage(): void
    {
        $e = $this->byPartialLinkText('Server: ');
        $e->click();
        $this->waitAjax();
    }

    /**
     * Execute a database query
     *
     * @param string       $query       SQL Query to be executed
     * @param Closure|null $onResults   The function to call when the results are displayed
     * @param Closure|null $afterSubmit The function to call after the submit button is clicked
     *
     * @throws Exception
     */
    public function dbQuery(string $query, Closure|null $onResults = null, Closure|null $afterSubmit = null): bool
    {
        $didSucceed = false;
        $handles = null;

        if (! $this->sqlWindowHandle) {
            $this->webDriver->executeScript("window.open('about:blank','_blank');", []);
            $this->webDriver->wait()->until(
                WebDriverExpectedCondition::numberOfWindowsToBe(2),
            );
            $handles = $this->webDriver->getWindowHandles();

            $lastWindow = end($handles);
            $this->webDriver->switchTo()->window($lastWindow);
            $this->login();
            $this->sqlWindowHandle = $lastWindow;
        }

        if ($handles === null) {
            $handles = $this->webDriver->getWindowHandles();
        }

        if ($this->sqlWindowHandle) {
            $this->webDriver->switchTo()->window($this->sqlWindowHandle);
            if (! $this->isSuccessLogin()) {
                $this->takeScrenshot('SQL_window_not_logged_in');

                return false;
            }

            $this->byXPath('//*[contains(@class,"nav-item") and contains(., "SQL")]')->click();
            $this->waitAjax();
            $this->typeInTextArea($query);
            $this->scrollIntoView('button_submit_query');
            $this->byId('button_submit_query')->click();
            $afterSubmit?->call($this);

            $this->waitAjax();
            $this->waitForElement('className', 'result_query');
            // If present then
            $didSucceed = $this->isElementPresent('cssSelector', '.result_query .alert-success');
            $onResults?->call($this);
        }

        reset($handles);
        $lastWindow = current($handles);
        $this->webDriver->switchTo()->window($lastWindow);

        return $didSucceed;
    }

    public function takeScrenshot(string $comment): void
    {
        $screenshotDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR
            . '..' . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR
            . 'selenium';
        if ($this->webDriver === null) {
            return;
        }

        $key = time();

        // This call will also create the file path
        $this->webDriver->takeScreenshot(
            $screenshotDir . DIRECTORY_SEPARATOR
            . 'screenshot_' . $key . '_' . $comment . '.png',
        );
        $htmlOutput = $screenshotDir . DIRECTORY_SEPARATOR . 'source_' . $key . '.html';
        file_put_contents($htmlOutput, $this->webDriver->getPageSource());
        $testInfo = $screenshotDir . DIRECTORY_SEPARATOR . 'source_' . $key . '.json';
        file_put_contents($testInfo, json_encode(
            ['filesKey' => $key, 'testName' => $this->getTestName()],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ));
    }

    /**
     * Check if user is logged in to phpmyadmin
     */
    public function isLoggedIn(): bool
    {
        return $this->isElementPresent('xpath', '//*[@class="navigationbar"]');
    }

    /**
     * Perform a logout, if logged in
     */
    public function logOutIfLoggedIn(): void
    {
        if (! $this->isLoggedIn()) {
            return;
        }

        $this->byCssSelector('img.icon.ic_s_loggoff')->click();
    }

    /**
     * Wait for an element to be present on the page
     *
     * @param string $func Locate using - cssSelector, xpath, tagName, partialLinkText, linkText, name, id, className
     * @param string $arg  Selector
     */
    public function waitForElement(string $func, string $arg): RemoteWebElement
    {
        $element = $this->webDriver->wait(30, 500)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::$func($arg)),
        );
        self::assertInstanceOf(RemoteWebElement::class, $element);

        return $element;
    }

    /**
     * Wait for an element to be present on the page or timeout
     *
     * @param string $func    Locate using - cssSelector, xpath, tagName, partialLinkText, linkText, name, id, className
     * @param string $arg     Selector
     * @param int    $timeout Timeout in seconds
     */
    public function waitUntilElementIsPresent(string $func, string $arg, int $timeout): RemoteWebElement
    {
        $element = $this->webDriver->wait($timeout, 500)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(WebDriverBy::$func($arg)),
        );
        self::assertInstanceOf(RemoteWebElement::class, $element);

        return $element;
    }

    /**
     * Wait for an element to be visible on the page or timeout
     *
     * @param string $func    Locate using - cssSelector, xpath, tagName, partialLinkText, linkText, name, id, className
     * @param string $arg     Selector
     * @param int    $timeout Timeout in seconds
     */
    public function waitUntilElementIsVisible(string $func, string $arg, int $timeout = 10): WebDriverElement
    {
        $element = $this->webDriver->wait($timeout, 500)->until(
            WebDriverExpectedCondition::visibilityOfElementLocated(WebDriverBy::$func($arg)),
        );
        self::assertInstanceOf(WebDriverElement::class, $element);

        return $element;
    }

    /**
     * Wait for an element to disappear
     *
     * @param string $func Locate using - byCss, byXPath, etc
     * @param string $arg  Selector
     */
    public function waitForElementNotPresent(string $func, string $arg): void
    {
        while (true) {
            if (! $this->isElementPresent($func, $arg)) {
                return;
            }

            usleep(5000);
        }
    }

    /**
     * Check if element is present or not
     *
     * @param string $func Locate using - cssSelector, xpath, tagName, partialLinkText, linkText, name, id, className
     * @param string $arg  Selector
     */
    public function isElementPresent(string $func, string $arg): bool
    {
        try {
            $this->webDriver->findElement(WebDriverBy::$func($arg));
        } catch (NoSuchElementException | InvalidArgumentException | InvalidSelectorException) {
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
     * @return string text Data from the particular table cell
     */
    public function getCellByTableId(string $tableID, int $row, int $column): string
    {
        $sel = sprintf('table#%s tbody tr:nth-child(%d) td:nth-child(%d)', $tableID, $row, $column);
        $element = $this->byCssSelector($sel);
        $text = $element->getText();

        return $text && is_string($text) ? trim($text) : '';
    }

    /**
     * Get table cell data by the class attribute of the table
     *
     * @param string $tableClass Class of the table
     * @param int    $row        Table row
     * @param int    $column     Table column
     *
     * @return string text Data from the particular table cell
     */
    public function getCellByTableClass(string $tableClass, int $row, int $column): string
    {
        $sel = sprintf('table.%s tbody tr:nth-child(%d) td:nth-child(%d)', $tableClass, $row, $column);
        $element = $this->byCssSelector($sel);
        $text = $element->getText();

        return $text && is_string($text) ? trim($text) : '';
    }

    /**
     * Wrapper around keys method to not use it on not supported
     * browsers.
     *
     * @param string $text Keys to send
     */
    public function keys(string $text): void
    {
        /**
         * Not supported in Safari Webdriver, see
         * https://github.com/seleniumhq/selenium-google-code-issue-archive/issues/4136
         */
        if ($this->isSafari()) {
            self::markTestSkipped('Can not send keys to Safari browser.');
        } else {
            $this->webDriver->getKeyboard()->sendKeys($text);
        }
    }

    /**
     * Wrapper around moveto method to not use it on not supported
     * browsers.
     *
     * @param RemoteWebElement $element element
     */
    public function moveto(RemoteWebElement $element): void
    {
        /**
         * Not supported in Safari Webdriver, see
         * https://github.com/seleniumhq/selenium-google-code-issue-archive/issues/4136
         */
        if ($this->isSafari()) {
            self::markTestSkipped('MoveTo not supported on Safari browser.');
        } else {
            $this->webDriver->getMouse()->mouseMove($element->getCoordinates());
        }
    }

    /**
     * Wrapper around alertText method to not use it on not supported
     * browsers.
     */
    public function alertText(): string
    {
        /**
         * Not supported in Safari Webdriver, see
         * https://github.com/seleniumhq/selenium-google-code-issue-archive/issues/4136
         */
        if (! $this->isSafari()) {
            return $this->webDriver->switchTo()->alert()->getText();
        }

        self::markTestSkipped('Alerts not supported on Safari browser.');
    }

    /**
     * Type text in textarea (CodeMirror enabled)
     *
     * @param string $text  Text to type
     * @param int    $index Index of CodeMirror instance to write to
     */
    public function typeInTextArea(string $text, int $index = 0): void
    {
        $this->waitForElement('cssSelector', 'div.cm-s-default');
        $this->webDriver->executeScript(
            "$('.cm-s-default')[" . $index . '].CodeMirror.setValue(' . json_encode($text) . ');',
        );
    }

    /**
     * Accept alert
     */
    public function acceptAlert(): void
    {
        $this->webDriver->switchTo()->alert()->accept();
    }

    /**
     * Clicks the "More" link in the menu
     */
    public function expandMore(): void
    {
        // "More" menu is not displayed on large screens
        if ($this->isElementPresent('cssSelector', 'li.nav-item.dropdown.d-none')) {
            return;
        }

        // Not found, searching for another alternative
        try {
            $ele = $this->waitForElement('cssSelector', 'li.dropdown > a');

            $ele->click();
            $this->waitForElement('cssSelector', 'li.dropdown.show > a');

            $this->waitUntilElementIsPresent('cssSelector', 'li.nav-item.dropdown.show > ul', 5000);
        } catch (WebDriverException) {
            return;
        }
    }

    /**
     * Navigates browser to a table page.
     *
     * @param string $table                Name of table
     * @param bool   $gotoHomepageRequired Go to homepage required
     */
    public function navigateTable(string $table, bool $gotoHomepageRequired = false): void
    {
        $this->navigateDatabase($this->databaseName, $gotoHomepageRequired);

        // go to table page
        $this->waitForElement('xpath', "//th//a[contains(., '" . $table . "')]")->click();
        $this->waitAjax();
    }

    /**
     * Navigates browser to a database page.
     *
     * @param string $database             Name of database
     * @param bool   $gotoHomepageRequired Go to homepage required
     */
    public function navigateDatabase(string $database, bool $gotoHomepageRequired = false): void
    {
        if ($gotoHomepageRequired) {
            $this->gotoHomepage();
        }

        // Go to server databases
        $this->waitForElement('partialLinkText', 'Databases')->click();
        $this->waitAjax();

        // go to specific database page
        $this->waitForElement(
            'xpath',
            '//tr[(contains(@class, "db-row"))]//a[contains(., "' . $database . '")]',
        )->click();
        $this->waitAjax();
    }

    /**
     * Select an option that matches a value
     *
     * @param WebDriverElement $element The element
     * @param string           $value   The value of the option
     */
    public function selectByValue(WebDriverElement $element, string $value): void
    {
        $select = new WebDriverSelect($element);
        $select->selectByValue($value);
    }

    /**
     * Select an option that matches a text
     *
     * @param WebDriverElement $element The element
     * @param string           $text    The text
     */
    public function selectByLabel(WebDriverElement $element, string $text): void
    {
        $select = new WebDriverSelect($element);
        $select->selectByVisibleText($text);
    }

    /**
     * Scrolls to a coordinate such that the element with given id is visible
     *
     * @param string $elementId Id of the element
     * @param int    $yOffset   Offset from Y-coordinate of element
     */
    public function scrollIntoView(string $elementId, int $yOffset = 70): void
    {
        // 70pt offset by-default so that the topmenu does not cover the element
        $script = <<<'JS'
const elementId = arguments[0];
const yOffset = arguments[1];
const position = document.getElementById(elementId).getBoundingClientRect();
window.scrollBy({left: 0, top: position.top - yOffset, behavior: 'instant'});
JS;
        $this->webDriver->executeScript($script, [$elementId, $yOffset]);
    }

    /**
     * Scrolls to a coordinate such that the element
     *
     * @param WebDriverElement $element The element
     * @param int              $xOffset The x offset to apply (defaults to 0)
     * @param int              $yOffset The y offset to apply (defaults to 0)
     */
    public function scrollToElement(WebDriverElement $element, int $xOffset = 0, int $yOffset = 0): void
    {
        $script = <<<'JS'
const leftValue = arguments[0];
const topValue = arguments[1];
window.scrollBy({left: leftValue, top: topValue, behavior: 'instant'});
JS;
        $this->webDriver->executeScript($script, [
            $element->getLocation()->getX() + $xOffset,
            $element->getLocation()->getY() + $yOffset,
        ]);
    }

    /**
     * Scroll to the bottom of page
     */
    public function scrollToBottom(): void
    {
        $script = <<<'JS'
window.scrollTo({left: 0, top: document.body.scrollHeight, behavior: 'instant'});
JS;
        $this->webDriver->executeScript($script);
    }

    /**
     * Reload the page
     */
    public function reloadPage(): void
    {
        $this->webDriver->executeScript('window.location.reload();');
    }

    /**
     * Wait for AJAX completion
     */
    public function waitAjax(): void
    {
        /* Wait while code is loading */
        $this->webDriver->executeAsyncScript(
            'var callback = arguments[arguments.length - 1];'
            . 'function startWaitingForAjax() {'
            . '    if (! window.AJAX.active) {'
            . '        callback();'
            . '    } else {'
            . '        setTimeout(startWaitingForAjax, 200);'
            . '    }'
            . '}'
            . 'startWaitingForAjax();',
        );
    }

    /**
     * Wait for AJAX message disappear
     */
    public function waitAjaxMessage(): void
    {
        /* Get current message count */
        $ajaxMessageCount = $this->webDriver->executeScript('return window.getAjaxMessageCount();');
        /* Ensure the popup is gone */
        $this->waitForElementNotPresent('id', 'ajax_message_num_' . $ajaxMessageCount);
    }

    /**
     * Tear Down function for test cases
     */
    protected function tearDown(): void
    {
        if (static::$createDatabase) {
            $this->dbQuery('DROP DATABASE IF EXISTS `' . $this->databaseName . '`;');
        }

        if ($this->hadStorageDatabaseInstall) {
            $this->dbQuery('DROP DATABASE IF EXISTS `phpmyadmin`;');
        }

        if ($this->status()->asString() !== 'success') {
            return;
        }

        $this->markTestAs('passed', '');
        $this->sqlWindowHandle = null;
        $this->webDriver->quit();
    }

    /**
     * Mark test as failed or passed on BrowserStack
     *
     * @param string $status  passed or failed
     * @param string $message a message
     */
    private function markTestAs(string $status, string $message): void
    {
        // If this is being run on Browerstack,
        // mark the test on Browerstack as failure
        if (! $this->hasBrowserstackConfig()) {
            return;
        }

        $payload = json_encode(
            ['status' => $status, 'reason' => $message],
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::SESSION_REST_URL . $this->sessionId . '.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt(
            $ch,
            CURLOPT_USERPWD,
            $this->getBrowserStackCredentials(),
        );

        $headers = [];
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_exec($ch);
        if (! curl_errno($ch)) {
            return;
        }

        echo 'Error: ' . curl_error($ch) . "\n";
    }

    private function getErrorVideoUrl(): void
    {
        if (! $this->hasBrowserstackConfig()) {
            return;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::SESSION_REST_URL . $this->sessionId . '.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt(
            $ch,
            CURLOPT_USERPWD,
            $this->getBrowserStackCredentials(),
        );
        $result = curl_exec($ch);
        if (is_bool($result)) {
            echo 'Error: ' . curl_error($ch) . "\n";

            return;
        }

        $proj = json_decode($result);
        if (is_object($proj) && property_exists($proj, 'automation_session')) {
            // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
            echo 'Test failed, get more information here: ' . $proj->automation_session->public_url . "\n";
        }

        if (! curl_errno($ch)) {
            return;
        }

        echo 'Error: ' . curl_error($ch) . "\n";
    }

    /**
     * Mark unsuccessful tests as 'Failures' on Browerstack
     */
    protected function onNotSuccessfulTest(Throwable $t): never
    {
        if ($t instanceof SkippedTest) {
            parent::onNotSuccessfulTest($t);
        }

        $this->markTestAs('failed', $t->getMessage());
        $this->takeScrenshot('test_failed');
        // End testing session
        $this->webDriver->quit();

        $this->sqlWindowHandle = null;

        $this->getErrorVideoUrl();

        // Call parent's onNotSuccessful to handle everything else
        parent::onNotSuccessfulTest($t);
    }
}
