<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Console;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Console\History;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(History::class)]
final class HistoryTest extends AbstractTestCase
{
    public function testGetHistoryUsesRelationParametersKnownAfterConstruction(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $config = new Config();
        $config->set('QueryHistoryDB', true);

        // The service is created before the connection: the configuration storage is not known yet.
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, RelationParameters::fromArray([]));
        $history = new History($dbi, new Relation($dbi, $config), $config);

        // Relation parameters are initialized after a successful login.
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::HISTORY_WORK => true,
            RelationParameters::HISTORY => 'pma__history',
        ]));

        $dbiDummy->addResult(
            'SELECT `db`, `table`, `sqlquery`, `timevalue` FROM `pmadb`.`pma__history`'
            . ' WHERE `username` = \'root\' ORDER BY `id` DESC',
            [['test_db', 'test_table', 'SELECT 1', '2026-10-23 10:00:00']],
            ['db', 'table', 'sqlquery', 'timevalue'],
        );

        self::assertSame(
            [
                [
                    'db' => 'test_db',
                    'table' => 'test_table',
                    'sqlquery' => 'SELECT 1',
                    'timevalue' => '2026-10-23 10:00:00',
                ],
            ],
            $history->getHistory('root'),
        );
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetHistoryWithoutConfigurationStorage(): void
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, RelationParameters::fromArray([]));
        $history = new History($dbi, new Relation($dbi, $config), $config);

        self::assertFalse($history->getHistory('root'));
    }
}
