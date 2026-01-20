<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Sql;
use PhpMyAdmin\SqlParser\Translator;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Utils\HttpRequest;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Abstract class to hold some usefull methods used in tests
 * And make tests clean
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * Prepares environment for the test.
     * Clean all variables
     */
    protected function setUp(): void
    {
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

        Current::$server = 1;
        Current::$database = '';
        Current::$table = '';
        Current::$sqlQuery = '';
        Current::$message = null;
        Current::$lang = 'en';
        Current::$whereClause = null;
        Current::$displayQuery = null;
        Current::$charset = null;
        Current::$numTables = 0;
        DatabaseInterface::$errorNumber = null;
        Sql::$showAsPhp = null;
        ExportSql::$noConstraintsComments = false;

        // Config before DBI
        Cache::purge();
        Tracker::disable();

        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        ContainerBuilder::$container = null;
    }

    protected function createDatabaseInterface(
        DbiExtension|null $extension = null,
        Config|null $config = null,
    ): DatabaseInterface {
        return DatabaseInterface::getInstanceForTest($extension ?? $this->createDbiDummy(), $config);
    }

    protected function createDbiDummy(): DbiDummy
    {
        return new DbiDummy();
    }

    protected function setLanguage(string $code = 'en'): void
    {
        Current::$lang = $code;
        $languageManager = LanguageManager::getInstance();
        /* Ensure default language is active */
        $languageEn = $languageManager->getLanguage($code);
        if ($languageEn === false) {
            return;
        }

        $languageManager->activate($languageEn);
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
        ContainerBuilder::$container = null;
        DatabaseInterface::$instance = null;
        Config::$instance = null;
        (new ReflectionProperty(Template::class, 'twig'))->setValue(null, null);
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

    /**
     * Get a private or protected property via reflection.
     *
     * @param object $object       The object to inspect, pass null for static objects()
     * @param string $className    The class name
     * @param string $propertyName The method name
     * @phpstan-param class-string $className
     */
    protected function getProperty(object $object, string $className, string $propertyName): mixed
    {
        $class = new ReflectionClass($className);
        $property = $class->getProperty($propertyName);

        return $property->getValue($object);
    }
}
