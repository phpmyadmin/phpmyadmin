<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Language;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\SqlParser\Translator;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Theme;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use const PHP_SAPI;
use function define;
use function defined;
use function getenv;
use function in_array;
use function is_array;
use function parse_url;

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
    private $globalsAllowList = [
        '__composer_autoload_files',
        'GLOBALS',
        '_SERVER',
        '__composer_autoload_files',
        '__PHPUNIT_CONFIGURATION_FILE',
        '__PHPUNIT_BOOTSTRAP',
    ];

    /**
     * The DatabaseInterface loaded by setGlobalDbi
     *
     * @var DatabaseInterface
     */
    protected $dbi;

    /**
     * The DbiDummy loaded by setGlobalDbi
     *
     * @var DbiDummy
     */
    protected $dummyDbi;

    /**
     * Prepares environment for the test.
     * Clean all variables
     */
    protected function setUp(): void
    {
        foreach ($GLOBALS as $key => $val) {
            if (in_array($key, $this->globalsAllowList)) {
                continue;
            }
            unset($GLOBALS[$key]);
        }
        $_GET = [];
        $_POST = [];
        $_SERVER = [
            // https://github.com/sebastianbergmann/phpunit/issues/4033
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
            'REQUEST_TIME' => $_SERVER['REQUEST_TIME'],
            'REQUEST_TIME_FLOAT' => $_SERVER['REQUEST_TIME_FLOAT'],
            'PHP_SELF' => $_SERVER['PHP_SELF'],
            'argv' => $_SERVER['argv'],
        ];
        $_SESSION = [' PMA_token ' => 'token'];
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

    protected function assertAllQueriesConsumed(): void
    {
        if ($this->dummyDbi->hasUnUsedQueries() === false) {
            $this->assertTrue(true);// increment the assertion count

            return;
        }

        $this->fail('Some queries where no used !');
    }

    protected function loadContainerBuilder(): void
    {
        global $containerBuilder;

        $containerBuilder = Core::getContainerBuilder();
    }

    protected function loadDbiIntoContainerBuilder(): void
    {
        global $containerBuilder, $dbi;

        $containerBuilder->set(DatabaseInterface::class, $dbi);
        $containerBuilder->setAlias('dbi', DatabaseInterface::class);
    }

    protected function loadResponseIntoContainerBuilder(): void
    {
        global $containerBuilder;

        $response = new Response();
        $containerBuilder->set(Response::class, $response);
        $containerBuilder->setAlias('response', Response::class);
    }

    protected function getResponseHtmlResult(): string
    {
        global $containerBuilder;
        $response = $containerBuilder->get(Response::class);

        /** @var Response $response */
        return $response->getHTMLResult();
    }

    protected function getResponseJsonResult(): array
    {
        global $containerBuilder;
        $response = $containerBuilder->get(Response::class);

        /** @var Response $response */
        return $response->getJSONResult();
    }

    protected function setGlobalDbi(): void
    {
        global $dbi;
        $this->dummyDbi = new DbiDummy();
        $this->dbi = DatabaseInterface::load($this->dummyDbi);
        $dbi = $this->dbi;
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
        $PMA_Theme = Theme::load('./themes/pmahomme', ROOT_PATH . 'themes/pmahomme/');
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
        if (PHP_SAPI === 'cli' && is_array($urlInfo)) {
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

    /**
     * Desctroys the environment built for the test.
     * Clean all variables
     */
    protected function tearDown(): void
    {
        foreach ($GLOBALS as $key => $val) {
            if (in_array($key, $this->globalsAllowList)) {
                continue;
            }
            unset($GLOBALS[$key]);
        }
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param object|null $object     The object to inspect, pass null for static objects()
     * @param string      $className  The class name
     * @param string      $methodName The method name
     * @param array       $params     The parameters for the invocation
     *
     * @return mixed the output from the protected method.
     *
     * @phpstan-param class-string $className
     */
    protected function callFunction($object, string $className, string $methodName, array $params)
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $params);
    }
}
