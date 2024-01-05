<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ShowGrants;
use PhpMyAdmin\UserPrivileges;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CheckUserPrivileges::class)]
class CheckUserPrivilegesTest extends AbstractTestCase
{
    private CheckUserPrivileges $checkUserPrivileges;

    /**
     * prepares environment for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
        Config::getInstance()->selectedServer['DisableIS'] = false;
        UserPrivileges::$column = false;
        UserPrivileges::$database = false;
        UserPrivileges::$routines = false;
        UserPrivileges::$table = false;
        UserPrivileges::$isReload = false;

        $this->checkUserPrivileges = new CheckUserPrivileges(DatabaseInterface::getInstance());
    }

    /**
     * Test for checkRequiredPrivilegesForAdjust
     */
    public function testCheckRequiredPrivilegesForAdjust(): void
    {
        // TEST CASE 1
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON *.* TO \'root\'@\'localhost\' WITH GRANT OPTION');

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust($showGrants);

        $this->assertTrue(UserPrivileges::$column);
        $this->assertTrue(UserPrivileges::$database);
        $this->assertTrue(UserPrivileges::$routines);
        $this->assertTrue(UserPrivileges::$table);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 2
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION');

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust($showGrants);

        $this->assertTrue(UserPrivileges::$column);
        $this->assertTrue(UserPrivileges::$database);
        $this->assertTrue(UserPrivileges::$routines);
        $this->assertTrue(UserPrivileges::$table);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 3
        $showGrants = new ShowGrants('GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.* TO \'root\'@\'localhost\'');

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust($showGrants);

        $this->assertTrue(UserPrivileges::$column);
        $this->assertTrue(UserPrivileges::$database);
        $this->assertTrue(UserPrivileges::$routines);
        $this->assertTrue(UserPrivileges::$table);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 4
        $showGrants = new ShowGrants('GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`db` TO \'root\'@\'localhost\'');

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust($showGrants);

        $this->assertFalse(UserPrivileges::$column);
        $this->assertTrue(UserPrivileges::$database);
        $this->assertFalse(UserPrivileges::$routines);
        $this->assertFalse(UserPrivileges::$table);
    }
}
