<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Language;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\SqlParser\Translator;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\Response;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Utils\HttpRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function in_array;

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

    protected function setGlobalDbi(): void
    {
        global $dbi;
        $dbi = DatabaseInterface::load(new DbiDummy());
    }

    protected function setGlobalConfig(): void
    {
        global $config;
        $config = new Config();
        $config->set('environment', 'development');
    }

    protected function setTheme(): void
    {
        global $theme;
        $theme = Theme::load('pmahomme');
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
        HttpRequest::setProxySettingsFromEnv();
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
