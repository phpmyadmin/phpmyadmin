<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\SqlParser\Translator;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme\Theme;
use PhpMyAdmin\Theme\ThemeManager;
use PhpMyAdmin\Utils\HttpRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function array_keys;
use function in_array;

use const DIRECTORY_SEPARATOR;

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
    private array $globalsAllowList = [
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
        foreach (array_keys($GLOBALS) as $key) {
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

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['sql_query'] = '';
        $GLOBALS['text_dir'] = 'ltr';

        // Config before DBI
        $this->setGlobalConfig();
        Cache::purge();

        (new ReflectionClass(Relation::class))->getProperty('cache')->setValue([]);
    }

    protected function loadContainerBuilder(): void
    {
        $GLOBALS['containerBuilder'] = Core::getContainerBuilder();
    }

    protected function loadDbiIntoContainerBuilder(): void
    {
        $GLOBALS['containerBuilder']->set(DatabaseInterface::class, $GLOBALS['dbi']);
        $GLOBALS['containerBuilder']->setAlias('dbi', DatabaseInterface::class);
    }

    protected function loadResponseIntoContainerBuilder(): void
    {
        $response = new ResponseRenderer();
        $GLOBALS['containerBuilder']->set(ResponseRenderer::class, $response);
        $GLOBALS['containerBuilder']->setAlias('response', ResponseRenderer::class);
    }

    protected function setResponseIsAjax(): void
    {
        /** @var ResponseRenderer $response */
        $response = $GLOBALS['containerBuilder']->get(ResponseRenderer::class);

        $response->setAjax(true);
    }

    protected function getResponseHtmlResult(): string
    {
        /** @var ResponseRenderer $response */
        $response = $GLOBALS['containerBuilder']->get(ResponseRenderer::class);

        return $response->getHTMLResult();
    }

    /** @return mixed[] */
    protected function getResponseJsonResult(): array
    {
        /** @var ResponseRenderer $response */
        $response = $GLOBALS['containerBuilder']->get(ResponseRenderer::class);

        return $response->getJSONResult();
    }

    protected function assertResponseWasNotSuccessfull(): void
    {
        /** @var ResponseRenderer $response */
        $response = $GLOBALS['containerBuilder']->get(ResponseRenderer::class);

        $this->assertFalse($response->hasSuccessState(), 'expected the request to fail');
    }

    protected function assertResponseWasSuccessfull(): void
    {
        /** @var ResponseRenderer $response */
        $response = $GLOBALS['containerBuilder']->get(ResponseRenderer::class);

        $this->assertTrue($response->hasSuccessState(), 'expected the request not to fail');
    }

    protected function createDatabaseInterface(DbiExtension|null $extension = null): DatabaseInterface
    {
        return new DatabaseInterface($extension ?? $this->createDbiDummy());
    }

    protected function createDbiDummy(): DbiDummy
    {
        return new DbiDummy();
    }

    protected function createConfig(): Config
    {
        $config = new Config();
        $config->loadAndCheck();

        return $config;
    }

    protected function setGlobalConfig(): void
    {
        $GLOBALS['config'] = $this->createConfig();
        $GLOBALS['config']->set('environment', 'development');
        $GLOBALS['cfg'] = $GLOBALS['config']->settings;
    }

    protected function setTheme(): void
    {
        $GLOBALS['theme'] = Theme::load(
            ThemeManager::getThemesDir() . 'pmahomme',
            ThemeManager::getThemesFsDir() . 'pmahomme' . DIRECTORY_SEPARATOR,
            'pmahomme',
        );
    }

    protected function setLanguage(string $code = 'en'): void
    {
        $GLOBALS['lang'] = $code;
        /* Ensure default language is active */
        $languageEn = LanguageManager::getInstance()->getLanguage($code);
        if ($languageEn === false) {
            return;
        }

        $languageEn->activate();
        Translator::load();
    }

    protected function setProxySettings(): void
    {
        HttpRequest::setProxySettingsFromEnv();
    }

    /**
     * Destroys the environment built for the test.
     * Clean all variables
     */
    protected function tearDown(): void
    {
        foreach (array_keys($GLOBALS) as $key) {
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
     * @param mixed[]     $params     The parameters for the invocation
     * @phpstan-param class-string $className
     *
     * @return mixed the output from the protected method.
     */
    protected function callFunction(object|null $object, string $className, string $methodName, array $params): mixed
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);

        return $method->invokeArgs($object, $params);
    }

    /**
     * Set a private or protected property via reflection.
     *
     * @param object|null $object       The object to inspect, pass null for static objects()
     * @param string      $className    The class name
     * @param string      $propertyName The method name
     * @param mixed       $value        The parameters for the invocation
     * @phpstan-param class-string $className
     */
    protected function setProperty(object|null $object, string $className, string $propertyName, mixed $value): void
    {
        $class = new ReflectionClass($className);
        $property = $class->getProperty($propertyName);

        $property->setValue($object, $value);
    }
}
