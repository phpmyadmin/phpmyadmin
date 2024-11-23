<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\ListDatabase;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(ListDatabase::class)]
class ListDatabaseTest extends AbstractTestCase
{
    /**
     * Test for ListDatabase::exists
     */
    public function testExists(): void
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['only_db'] = ['single\\_db'];
        $object = new ListDatabase($dbi, $config, new UserPrivilegesFactory($dbi));

        self::assertTrue($object->exists('single_db'));
    }

    #[DataProvider('providerForTestGetList')]
    public function testGetList(string $currentDbName, string $dbName): void
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['only_db'] = ['single\\_db'];
        $object = new ListDatabase($dbi, $config, new UserPrivilegesFactory($dbi));

        Current::$database = $currentDbName;
        self::assertSame(
            [['name' => $dbName, 'is_selected' => $currentDbName === $dbName]],
            $object->getList(),
        );
    }

    /** @return list<list{string,string}> */
    public static function providerForTestGetList(): array
    {
        return [
            ['db', 'single_db'],
            ['single_db', 'single_db'],
        ];
    }

    /**
     * Test for checkHideDatabase
     */
    public function testCheckHideDatabase(): void
    {
        $dbi = $this->createDatabaseInterface();
        $config = new Config();
        $config->selectedServer['DisableIS'] = false;
        $config->selectedServer['only_db'] = ['single\\_db'];
        $config->selectedServer['hide_db'] = 'single\\_db';
        $object = new ListDatabase($dbi, $config, new UserPrivilegesFactory($dbi));

        self::assertSame([], (array) $object);
    }
}
