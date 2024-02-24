<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ShowGrants;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Utils\SessionCache;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserPrivilegesFactory::class)]
#[CoversClass(UserPrivileges::class)]
#[CoversClass(ShowGrants::class)]
final class UserPrivilegesFactoryTest extends AbstractTestCase
{
    public function testCheckRequiredPrivilegesForAdjust(): void
    {
        $userPrivilegesFactory = new UserPrivilegesFactory($this->createDatabaseInterface());

        // TEST CASE 1
        $userPrivileges = new UserPrivileges();
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON *.* TO \'root\'@\'localhost\' WITH GRANT OPTION');

        // call the to-be-tested function
        $userPrivilegesFactory->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

        self::assertTrue($userPrivileges->column);
        self::assertTrue($userPrivileges->database);
        self::assertTrue($userPrivileges->routines);
        self::assertTrue($userPrivileges->table);

        // TEST CASE 2
        $userPrivileges = new UserPrivileges();
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION');

        // call the to-be-tested function
        $userPrivilegesFactory->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

        self::assertTrue($userPrivileges->column);
        self::assertTrue($userPrivileges->database);
        self::assertTrue($userPrivileges->routines);
        self::assertTrue($userPrivileges->table);

        // TEST CASE 3
        $userPrivileges = new UserPrivileges();
        $showGrants = new ShowGrants('GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.* TO \'root\'@\'localhost\'');

        // call the to-be-tested function
        $userPrivilegesFactory->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

        self::assertTrue($userPrivileges->column);
        self::assertTrue($userPrivileges->database);
        self::assertTrue($userPrivileges->routines);
        self::assertTrue($userPrivileges->table);

        // TEST CASE 4
        $userPrivileges = new UserPrivileges();
        $showGrants = new ShowGrants('GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`db` TO \'root\'@\'localhost\'');

        // call the to-be-tested function
        $userPrivilegesFactory->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

        self::assertFalse($userPrivileges->column);
        self::assertTrue($userPrivileges->database);
        self::assertFalse($userPrivileges->routines);
        self::assertFalse($userPrivileges->table);
    }

    public function testGetPrivilegesWithSkipGrantTables(): void
    {
        SessionCache::set('mysql_cur_user', '@');
        $userPrivilegesFactory = new UserPrivilegesFactory($this->createDatabaseInterface());
        $expected = new UserPrivileges(
            database: true,
            table: true,
            column: true,
            routines: true,
            isReload: true,
            isCreateDatabase: true,
        );
        self::assertEquals($expected, $userPrivilegesFactory->getPrivileges());
    }

    public function testGetPrivilegesFromSessionCache(): void
    {
        SessionCache::set('mysql_cur_user', 'test_user@localhost');

        SessionCache::set('is_create_db_priv', true);
        SessionCache::set('is_reload_priv', true);
        SessionCache::set('db_to_create', 'databaseToCreate');
        SessionCache::set('dbs_to_test', ['databasesToTest']);
        SessionCache::set('proc_priv', true);
        SessionCache::set('table_priv', true);
        SessionCache::set('col_priv', true);
        SessionCache::set('db_priv', true);

        $userPrivilegesFactory = new UserPrivilegesFactory($this->createDatabaseInterface());
        $expected = new UserPrivileges(true, true, true, true, true, true, 'databaseToCreate', ['databasesToTest']);
        self::assertEquals($expected, $userPrivilegesFactory->getPrivileges());
    }

    public function testGetPrivilegesWithoutShowGrantsResult(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SHOW GRANTS', false);

        SessionCache::set('mysql_cur_user', 'test_user@localhost');
        $userPrivilegesFactory = new UserPrivilegesFactory($this->createDatabaseInterface($dbiDummy));
        $expected = new UserPrivileges(databasesToTest: ['information_schema', 'performance_schema', 'mysql', 'sys']);
        self::assertEquals($expected, $userPrivilegesFactory->getPrivileges());

        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetPrivilegesWithoutGrants(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SHOW GRANTS', []);

        SessionCache::set('mysql_cur_user', 'test_user@localhost');
        $userPrivilegesFactory = new UserPrivilegesFactory($this->createDatabaseInterface($dbiDummy));
        $expected = new UserPrivileges(databasesToTest: ['information_schema', 'performance_schema', 'mysql', 'sys']);
        self::assertEquals($expected, $userPrivilegesFactory->getPrivileges());

        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testGetPrivilegesWithAllPrivileges(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SHOW GRANTS',
            [['GRANT ALL PRIVILEGES ON *.* TO \'test_user\'@\'localhost\' WITH GRANT OPTION']],
        );

        SessionCache::set('mysql_cur_user', 'test_user@localhost');
        $userPrivilegesFactory = new UserPrivilegesFactory($this->createDatabaseInterface($dbiDummy));
        $expected = new UserPrivileges(true, true, true, true, true, true);
        self::assertEquals($expected, $userPrivilegesFactory->getPrivileges());

        $dbiDummy->assertAllQueriesConsumed();
    }
}
