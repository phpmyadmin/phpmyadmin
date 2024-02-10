<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ShowGrants;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserPrivilegesFactory::class)]
final class UserPrivilegesFactoryTest extends AbstractTestCase
{
    private UserPrivilegesFactory $userPrivilegesFactory;

    /**
     * prepares environment for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Config::getInstance()->selectedServer['DisableIS'] = false;

        $this->userPrivilegesFactory = new UserPrivilegesFactory(DatabaseInterface::getInstance());
    }

    /**
     * Test for checkRequiredPrivilegesForAdjust
     */
    public function testCheckRequiredPrivilegesForAdjust(): void
    {
        // TEST CASE 1
        $userPrivileges = new UserPrivileges();
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON *.* TO \'root\'@\'localhost\' WITH GRANT OPTION');

        // call the to-be-tested function
        $this->userPrivilegesFactory->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

        self::assertTrue($userPrivileges->column);
        self::assertTrue($userPrivileges->database);
        self::assertTrue($userPrivileges->routines);
        self::assertTrue($userPrivileges->table);

        // TEST CASE 2
        $userPrivileges = new UserPrivileges();
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION');

        // call the to-be-tested function
        $this->userPrivilegesFactory->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

        self::assertTrue($userPrivileges->column);
        self::assertTrue($userPrivileges->database);
        self::assertTrue($userPrivileges->routines);
        self::assertTrue($userPrivileges->table);

        // TEST CASE 3
        $userPrivileges = new UserPrivileges();
        $showGrants = new ShowGrants('GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.* TO \'root\'@\'localhost\'');

        // call the to-be-tested function
        $this->userPrivilegesFactory->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

        self::assertTrue($userPrivileges->column);
        self::assertTrue($userPrivileges->database);
        self::assertTrue($userPrivileges->routines);
        self::assertTrue($userPrivileges->table);

        // TEST CASE 4
        $userPrivileges = new UserPrivileges();
        $showGrants = new ShowGrants('GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`db` TO \'root\'@\'localhost\'');

        // call the to-be-tested function
        $this->userPrivilegesFactory->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

        self::assertFalse($userPrivileges->column);
        self::assertTrue($userPrivileges->database);
        self::assertFalse($userPrivileges->routines);
        self::assertFalse($userPrivileges->table);
    }
}
