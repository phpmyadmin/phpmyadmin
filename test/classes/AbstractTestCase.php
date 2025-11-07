<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\SqlParser\Translator;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Theme;
use PhpMyAdmin\ThemeManager;
use PhpMyAdmin\Utils\HttpRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function array_keys;
use function in_array;
use function method_exists;

use const DIRECTORY_SEPARATOR;
use const PHP_VERSION_ID;

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
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['PMA_PHP_SELF'] = '';
        $GLOBALS['lang'] = 'en';

        // Config before DBI
        $this->setGlobalConfig();
        $this->loadContainerBuilder();
        $this->setGlobalDbi();
        $this->loadDbiIntoContainerBuilder();
        Cache::purge();
    }

    protected function createDatabaseInterface(?DbiExtension $extension = null): DatabaseInterface
    {
        return new DatabaseInterface($extension ?? $this->createDbiDummy());
    }

    protected function createDbiDummy(): DbiDummy
    {
        return new DbiDummy();
    }

    protected function assertAllQueriesConsumed(): void
    {
        $this->dummyDbi->assertAllQueriesConsumed();
    }

    protected function assertAllSelectsConsumed(): void
    {
        $this->dummyDbi->assertAllSelectsConsumed();
    }

    protected function assertAllErrorCodesConsumed(): void
    {
        $this->dummyDbi->assertAllErrorCodesConsumed();
    }

    /**
     * PHPUnit 8 compatibility
     */
    public static function assertMatchesRegularExpressionCompat(
        string $pattern,
        string $string,
        string $message = ''
    ): void {
        if (method_exists(TestCase::class, 'assertMatchesRegularExpression')) {
            /** @phpstan-ignore-next-line */
            parent::assertMatchesRegularExpression($pattern, $string, $message);
        } else {
            /** @psalm-suppress DeprecatedMethod */
            self::assertRegExp($pattern, $string, $message);
        }
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

        $response = new ResponseRenderer();
        $containerBuilder->set(ResponseRenderer::class, $response);
        $containerBuilder->setAlias('response', ResponseRenderer::class);
    }

    protected function setResponseIsAjax(): void
    {
        global $containerBuilder;

        /** @var ResponseRenderer $response */
        $response = $containerBuilder->get(ResponseRenderer::class);

        $response->setAjax(true);
    }

    protected function getResponseHtmlResult(): string
    {
        global $containerBuilder;

        /** @var ResponseRenderer $response */
        $response = $containerBuilder->get(ResponseRenderer::class);

        return $response->getHTMLResult();
    }

    protected function getResponseJsonResult(): array
    {
        global $containerBuilder;

        /** @var ResponseRenderer $response */
        $response = $containerBuilder->get(ResponseRenderer::class);

        return $response->getJSONResult();
    }

    protected function assertResponseWasNotSuccessfull(): void
    {
        global $containerBuilder;
        /** @var ResponseRenderer $response */
        $response = $containerBuilder->get(ResponseRenderer::class);

        self::assertFalse($response->hasSuccessState(), 'expected the request to fail');
    }

    protected function assertResponseWasSuccessfull(): void
    {
        global $containerBuilder;
        /** @var ResponseRenderer $response */
        $response = $containerBuilder->get(ResponseRenderer::class);

        self::assertTrue($response->hasSuccessState(), 'expected the request not to fail');
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
        global $config, $cfg;
        $config = new Config();
        $config->checkServers();
        $config->set('environment', 'development');
        $cfg = $config->settings;
    }

    protected function setTheme(): void
    {
        global $theme;
        $theme = Theme::load(
            ThemeManager::getThemesDir() . 'pmahomme',
            ThemeManager::getThemesFsDir() . 'pmahomme' . DIRECTORY_SEPARATOR,
            'pmahomme'
        );
    }

    protected function setLanguage(string $code = 'en'): void
    {
        global $lang;

        $lang = $code;
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
     * @param array       $params     The parameters for the invocation
     * @phpstan-param class-string $className
     *
     * @return mixed the output from the protected method.
     */
    protected function callFunction($object, string $className, string $methodName, array $params)
    {
        $class = new ReflectionClass($className);
        $method = $class->getMethod($methodName);
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        return $method->invokeArgs($object, $params);
    }

    /**
     * Get a private or protected property via reflection.
     *
     * @param object $object       The object to inspect, pass null for static objects()
     * @param string $className    The class name
     * @param string $propertyName The method name
     * @phpstan-param class-string $className
     *
     * @return mixed
     */
    protected function getProperty(object $object, string $className, string $propertyName)
    {
        $class = new ReflectionClass($className);
        $property = $class->getProperty($propertyName);
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        return $property->getValue($object);
    }
}
