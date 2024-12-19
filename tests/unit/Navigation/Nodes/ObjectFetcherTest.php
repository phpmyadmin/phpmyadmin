<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Navigation\Nodes\ObjectFetcher;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ObjectFetcher::class)]
class ObjectFetcherTest extends AbstractTestCase
{
    public function testGetDataIS(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $dummyDbi->addResult('SELECT @@lower_case_table_names', [['0']]);

        $dummyDbi->addResult(
            'SELECT `TABLE_NAME` AS `name`, `TABLE_TYPE` AS `type` FROM `INFORMATION_SCHEMA`.`TABLES`'
            . ' WHERE `TABLE_SCHEMA`=\'default\' ORDER BY `TABLE_NAME` ASC',
            [
                ['test_table', 'BASE TABLE'],
                ['test_view', 'VIEW'],
            ],
            ['name', 'type'],
        );

        $dummyDbi->addResult(
            'SELECT `ROUTINE_NAME` AS `name` FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` COLLATE utf8_bin=\'default\' AND `ROUTINE_TYPE`=\'FUNCTION\' '
            . 'ORDER BY `ROUTINE_NAME` ASC',
            [['test_function']],
            ['name'],
        );

        $dummyDbi->addResult(
            'SELECT `EVENT_NAME` AS `name` FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE'
            . ' `EVENT_SCHEMA` COLLATE utf8_bin=\'default\' ORDER BY `EVENT_NAME` ASC',
            [['test_event']],
            ['name'],
        );

        $dummyDbi->addResult(
            'SELECT `ROUTINE_NAME` AS `name` FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` COLLATE utf8_bin=\'default\' AND `ROUTINE_TYPE`=\'PROCEDURE\' '
            . 'ORDER BY `ROUTINE_NAME` ASC',
            [['test_procedure']],
            ['name'],
        );

        $objectFetcher = new ObjectFetcher($dbi, $config);

        $tables = $objectFetcher->getTables('default', '');
        self::assertSame(['test_table'], $tables);

        $views = $objectFetcher->getViews('default', '');
        self::assertSame(['test_view'], $views);

        $functions = $objectFetcher->getFunctions('default', '');
        self::assertSame(['test_function'], $functions);

        $events = $objectFetcher->getEvents('default', '');
        self::assertSame(['test_event'], $events);

        $procedures = $objectFetcher->getProcedures('default', '');
        self::assertSame(['test_procedure'], $procedures);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testGetDataISWithSearchClause(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        DatabaseInterface::$instance = $dbi;

        $dummyDbi->addResult('SELECT @@lower_case_table_names', [['0']]);

        $dummyDbi->addResult(
            'SELECT `TABLE_NAME` AS `name`, `TABLE_TYPE` AS `type` FROM `INFORMATION_SCHEMA`.`TABLES`'
            . ' WHERE `TABLE_SCHEMA`=\'default\' AND `TABLE_NAME` LIKE \'%abc%\' ORDER BY `TABLE_NAME` ASC',
            [
                ['test_table', 'BASE TABLE'],
                ['test_view', 'VIEW'],
            ],
            ['name', 'type'],
        );

        $dummyDbi->addResult(
            'SELECT `ROUTINE_NAME` AS `name` FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` COLLATE utf8_bin=\'default\' AND `ROUTINE_TYPE`=\'FUNCTION\' '
            . "AND `ROUTINE_NAME` LIKE '%abc%' "
            . 'ORDER BY `ROUTINE_NAME` ASC',
            [['test_function']],
            ['name'],
        );

        $dummyDbi->addResult(
            'SELECT `EVENT_NAME` AS `name` FROM `INFORMATION_SCHEMA`.`EVENTS` WHERE'
            . ' `EVENT_SCHEMA` COLLATE utf8_bin=\'default\' AND `EVENT_NAME` LIKE \'%abc%\' ORDER BY `EVENT_NAME` ASC',
            [['test_event']],
            ['name'],
        );

        $dummyDbi->addResult(
            'SELECT `ROUTINE_NAME` AS `name` FROM `INFORMATION_SCHEMA`.`ROUTINES` WHERE '
            . '`ROUTINE_SCHEMA` COLLATE utf8_bin=\'default\' AND `ROUTINE_TYPE`=\'PROCEDURE\' '
            . "AND `ROUTINE_NAME` LIKE '%abc%' "
            . 'ORDER BY `ROUTINE_NAME` ASC',
            [['test_procedure']],
            ['name'],
        );

        $objectFetcher = new ObjectFetcher($dbi, $config);

        $tables = $objectFetcher->getTables('default', 'abc');
        self::assertSame(['test_table'], $tables);

        $views = $objectFetcher->getViews('default', 'abc');
        self::assertSame(['test_view'], $views);

        $functions = $objectFetcher->getFunctions('default', 'abc');
        self::assertSame(['test_function'], $functions);

        $events = $objectFetcher->getEvents('default', 'abc');
        self::assertSame(['test_event'], $events);

        $procedures = $objectFetcher->getProcedures('default', 'abc');
        self::assertSame(['test_procedure'], $procedures);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testGetDataNonIS(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->addResult(
            'SHOW FULL TABLES FROM `default`',
            [
                ['test_table', 'BASE TABLE'],
                ['test_view', 'VIEW'],
            ],
        );

        $dummyDbi->addResult(
            'SHOW FUNCTION STATUS WHERE `Db`=\'default\'',
            [['test_function']],
            ['Name'],
        );

        $dummyDbi->addResult(
            'SHOW EVENTS FROM `default`',
            [['test_event']],
            ['Name'],
        );

        $dummyDbi->addResult(
            'SHOW PROCEDURE STATUS WHERE `Db`=\'default\'',
            [['test_procedure']],
            ['Name'],
        );

        $objectFetcher = new ObjectFetcher($dbi, $config);

        $tables = $objectFetcher->getTables('default', '');
        self::assertSame(['test_table'], $tables);

        $views = $objectFetcher->getViews('default', '');
        self::assertSame(['test_view'], $views);

        $functions = $objectFetcher->getFunctions('default', '');
        self::assertSame(['test_function'], $functions);

        $events = $objectFetcher->getEvents('default', '');
        self::assertSame(['test_event'], $events);

        $procedures = $objectFetcher->getProcedures('default', '');
        self::assertSame(['test_procedure'], $procedures);

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testGetDataWithSearchQuery(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->removeDefaultResults();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $dummyDbi->addResult(
            'SHOW FULL TABLES FROM `default` WHERE `Tables_in_default` LIKE \'%abc%\'',
            [
                ['test_table', 'BASE TABLE'],
                ['test_view', 'VIEW'],
            ],
        );

        $dummyDbi->addResult(
            'SHOW FUNCTION STATUS WHERE `Db`=\'default\' AND `Name` LIKE \'%abc%\'',
            [['test_function']],
            ['Name'],
        );

        $dummyDbi->addResult(
            'SHOW EVENTS FROM `default` WHERE `Name` LIKE \'%abc%\'',
            [['test_event']],
            ['Name'],
        );

        $dummyDbi->addResult(
            'SHOW PROCEDURE STATUS WHERE `Db`=\'default\' AND `Name` LIKE \'%abc%\'',
            [['test_procedure']],
            ['Name'],
        );

        $objectFetcher = new ObjectFetcher($dbi, $config);

        $tables = $objectFetcher->getTables('default', 'abc');
        self::assertSame(['test_table'], $tables);

        $views = $objectFetcher->getViews('default', 'abc');
        self::assertSame(['test_view'], $views);

        $functions = $objectFetcher->getFunctions('default', 'abc');
        self::assertSame(['test_function'], $functions);

        $events = $objectFetcher->getEvents('default', 'abc');
        self::assertSame(['test_event'], $events);

        $procedures = $objectFetcher->getProcedures('default', 'abc');
        self::assertSame(['test_procedure'], $procedures);

        $dummyDbi->assertAllQueriesConsumed();
    }
}
