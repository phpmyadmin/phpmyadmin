<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ShowGrants;

/** @covers \PhpMyAdmin\CheckUserPrivileges */
class CheckUserPrivilegesTest extends AbstractTestCase
{
    private CheckUserPrivileges $checkUserPrivileges;

    /**
     * prepares environment for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['col_priv'] = false;
        $GLOBALS['db_priv'] = false;
        $GLOBALS['proc_priv'] = false;
        $GLOBALS['table_priv'] = false;
        $GLOBALS['is_reload_priv'] = false;

        $this->checkUserPrivileges = new CheckUserPrivileges($GLOBALS['dbi']);
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

        $this->assertTrue($GLOBALS['col_priv']);

        $this->assertTrue($GLOBALS['db_priv']);

        $this->assertTrue($GLOBALS['proc_priv']);

        $this->assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 2
        $showGrants = new ShowGrants('GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION');

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust($showGrants);

        $this->assertTrue($GLOBALS['col_priv']);

        $this->assertTrue($GLOBALS['db_priv']);

        $this->assertTrue($GLOBALS['proc_priv']);

        $this->assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 3
        $showGrants = new ShowGrants('GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.* TO \'root\'@\'localhost\'');

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust($showGrants);

        $this->assertTrue($GLOBALS['col_priv']);

        $this->assertTrue($GLOBALS['db_priv']);

        $this->assertTrue($GLOBALS['proc_priv']);

        $this->assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 4
        $showGrants = new ShowGrants('GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`db` TO \'root\'@\'localhost\'');

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust($showGrants);

        $this->assertFalse($GLOBALS['col_priv']);

        $this->assertTrue($GLOBALS['db_priv']);

        $this->assertFalse($GLOBALS['proc_priv']);

        $this->assertFalse($GLOBALS['table_priv']);
    }
}
