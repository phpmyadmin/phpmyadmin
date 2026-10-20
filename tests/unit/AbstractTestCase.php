<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Cache;
use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\DbiExtension;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Export\OutputHandler;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Import\Import;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Plugins\PluginFactory;
use PhpMyAdmin\Sql;
use PhpMyAdmin\SqlParser\Translator;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\TestCase;
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

        ContainerBuilder::$container = null;
        DatabaseInterface::$instance = null;
        Config::$instance = null;
        (new ReflectionProperty(Template::class, 'twig'))->setValue(null, null);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, null);
        (new ReflectionProperty(Charsets::class, 'charsets'))->setValue(null, []);
        (new ReflectionProperty(Charsets::class, 'collations'))->setValue(null, []);
        (new ReflectionProperty(Encoding::class, 'engine'))->setValue(null, null);
    }

    protected function createDatabaseInterface(
        DbiExtension|null $extension = null,
        Config|null $config = null,
    ): DatabaseInterface {
        return DatabaseInterface::getInstanceForTest($extension ?? $this->createDbiDummy(), $config);
    }

    protected function createPluginFactory(
        DatabaseInterface $dbi,
        Config $config = new Config(),
        OutputHandler $outputHandler = new OutputHandler(),
    ): PluginFactory {
        $relation = new Relation($dbi);

        return new PluginFactory(
            $relation,
            $outputHandler,
            new Transformations($dbi, $relation),
            $dbi,
            $config,
            new Import($dbi, new ResponseRenderer(), $config),
        );
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
}
