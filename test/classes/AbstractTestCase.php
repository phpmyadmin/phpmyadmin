<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PHPUnit\Framework\TestCase;
use PhpMyAdmin\SqlParser\Translator;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Language;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Config;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use ReflectionClass;

/**
 * Abstract class to hold some usefull methods used in tests
 * And make tests clean
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * The variables to keep between tests
     *
     * @var string[]
     */
    private $globalsWhiteList = [
        '__composer_autoload_files',
        'GLOBALS',
        '_SERVER',
        '__composer_autoload_files',
        '__PHPUNIT_CONFIGURATION_FILE',
        '__PHPUNIT_BOOTSTRAP',
    ];

    /**
     * Prepares environment for the test.
     * Clean all variables
     */
    protected function setUp(): void
    {
        foreach ($GLOBALS as $key => $val) {
            if (in_array($key, $this->globalsWhiteList)) {
                continue;
            }
            unset($GLOBALS[$key]);
        }
        $_GET = [];
        $_POST = [];
        $_SERVER = [
            // https://github.com/sebastianbergmann/phpunit/issues/4033
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
            'REQUEST_TIME_FLOAT' => $_SERVER['REQUEST_TIME_FLOAT'],
            'PHP_SELF' => $_SERVER['PHP_SELF'],
            'argv' => $_SERVER['argv'],
        ];
        $_SESSION = [
            ' PMA_token ' => 'token',
        ];
        $_COOKIE = [];
        $_FILES = [];
        $_REQUEST = [];
        // Config before DBI
        $this->setGlobalConfig();
        $this->setGlobalDbi();
    }

    protected function loadDefaultConfig(): void
    {
        global $cfg;

        require ROOT_PATH . 'libraries/config.default.php';
    }

    protected function setGlobalDbi(): void
    {
        global $dbi;
        $dbi = DatabaseInterface::load(new DbiDummy());
    }

    protected function setGlobalConfig(): void
    {
        global $PMA_Config;
        $PMA_Config = new Config();
        $PMA_Config->set('environment', 'development');
    }

    protected function setTheme(): void
    {
        global $PMA_Theme;
        $PMA_Theme = Theme::load(ROOT_PATH . 'themes/pmahomme');
    }

    protected function setLanguage(string $code = 'en'): void
    {
        global $lang;

        $lang = $code;
        /* Ensure default language is active */
        /** @var Language $languageEn */
        $languageEn = LanguageManager::getInstance()->getLanguage($code);
        $languageEn->activate();
        Translator::load();
    }

    protected function setProxySettings(): void
    {
        $httpProxy = getenv('http_proxy');
        $urlInfo = parse_url((string) $httpProxy);
        if (PHP_SAPI == 'cli' && is_array($urlInfo)) {
            $proxyUrl = ($urlInfo['host'] ?? '')
                . (isset($urlInfo['port']) ? ':' . $urlInfo['port'] : '');
            $proxyUser = $urlInfo['user'] ?? '';
            $proxyPass = $urlInfo['pass'] ?? '';

            $GLOBALS['cfg']['ProxyUrl'] = $proxyUrl;
            $GLOBALS['cfg']['ProxyUser'] = $proxyUser;
            $GLOBALS['cfg']['ProxyPass'] = $proxyPass;
        }

        // phpcs:disable PSR1.Files.SideEffects
        if (! defined('PROXY_URL')) {
            define('PROXY_URL', $proxyUrl ?? '');
            define('PROXY_USER', $proxyUser ?? '');
            define('PROXY_PASS', $proxyPass ?? '');
        }
        // phpcs:enable
    }

    protected function defineVersionConstants(): void
    {
        global $PMA_Config;
        // Initialize PMA_VERSION variable
        // phpcs:disable PSR1.Files.SideEffects
        if (! defined('PMA_VERSION')) {
            define('PMA_VERSION', $PMA_Config->get('PMA_VERSION'));
            define('PMA_MAJOR_VERSION', $PMA_Config->get('PMA_MAJOR_VERSION'));
        }
        // phpcs:enable
    }

    public static function defineTestingGlobals(): void
    {
        // Selenium tests setup
        $test_defaults = [
            'TESTSUITE_SERVER' => 'localhost',
            'TESTSUITE_USER' => 'root',
            'TESTSUITE_PASSWORD' => '',
            'TESTSUITE_DATABASE' => 'test',
            'TESTSUITE_PORT' => 3306,
            'TESTSUITE_URL' => 'http://localhost/phpmyadmin/',
            'TESTSUITE_SELENIUM_HOST' => '',
            'TESTSUITE_SELENIUM_PORT' => '4444',
            'TESTSUITE_SELENIUM_BROWSER' => 'firefox',
            'TESTSUITE_SELENIUM_COVERAGE' => '',
            'TESTSUITE_BROWSERSTACK_USER' => '',
            'TESTSUITE_BROWSERSTACK_KEY' => '',
            'TESTSUITE_FULL' => '',
            'CI_MODE' => '',
        ];
        if (PHP_SAPI == 'cli') {
            foreach ($test_defaults as $varname => $defvalue) {
                $envvar = getenv($varname);
                if ($envvar) {
                    $GLOBALS[$varname] = $envvar;
                } else {
                    $GLOBALS[$varname] = $defvalue;
                }
            }
        }
    }

    /**
     * Desctroys the environment built for the test.
     * Clean all variables
     */
    protected function tearDown(): void
    {
        foreach ($GLOBALS as $key => $val) {
            if (in_array($key, $this->globalsWhiteList)) {
                continue;
            }
            unset($GLOBALS[$key]);
        }
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param object|null  $object     The object to inspect, pass null for static objects()
     * @param class-string $className  The class name
     * @param string       $methodName The method name
     * @param array        $params     The parameters for the invocation
     *
     * @return mixed the output from the protected method.
     */
    protected function callFunction($object, string $className, string $methodName, array $params)
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $params);
    }
}
