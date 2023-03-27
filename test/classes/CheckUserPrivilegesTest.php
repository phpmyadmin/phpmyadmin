<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\CheckUserPrivileges;

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
     * Test for getItemsFromShowGrantsRow
     */
    public function testGetItemsFromShowGrantsRow(): void
    {
        // TEST CASE 1

        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON *.* TO \'root\'@\'localhost\' WITH GRANT OPTION',
        );

        $this->assertEquals('ALL PRIVILEGES', $showGrantsStr);

        $this->assertEquals('*', $showGrantsDbname);

        $this->assertEquals('*', $showGrantsTblname);

        // TEST CASE 2

        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION',
        );

        $this->assertEquals('ALL PRIVILEGES', $showGrantsStr);

        $this->assertEquals('mysql', $showGrantsDbname);

        $this->assertEquals('*', $showGrantsTblname);

        // TEST CASE 3

        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`columns_priv` TO \'root\'@\'localhost\'',
        );

        $this->assertEquals('SELECT, INSERT, UPDATE, DELETE', $showGrantsStr);

        $this->assertEquals('mysql', $showGrantsDbname);

        $this->assertEquals('columns_priv', $showGrantsTblname);

        // TEST CASE 4

        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON `cptest\_.`.* TO \'cptest\'@\'localhost\'',
        );

        $this->assertEquals('cptest\_.', $showGrantsDbname);

        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON `cptest\_.a.b.c.d.e.f.g.h.i.j.k.'
                . 'l.m.n.o.p.q.r.s.t.u.v.w.x.y.z`.* TO \'cptest\'@\'localhost\'',
        );

        $this->assertEquals('cptest\_.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z', $showGrantsDbname);
    }

    /**
     * Test for checkRequiredPrivilegesForAdjust
     */
    public function testCheckRequiredPrivilegesForAdjust(): void
    {
        // TEST CASE 1
        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON *.* TO \'root\'@\'localhost\' WITH GRANT OPTION',
        );

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust(
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        );

        $this->assertTrue($GLOBALS['col_priv']);

        $this->assertTrue($GLOBALS['db_priv']);

        $this->assertTrue($GLOBALS['proc_priv']);

        $this->assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 2
        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION',
        );

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust(
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        );

        $this->assertTrue($GLOBALS['col_priv']);

        $this->assertTrue($GLOBALS['db_priv']);

        $this->assertTrue($GLOBALS['proc_priv']);

        $this->assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 3
        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.* TO \'root\'@\'localhost\'',
        );

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust(
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        );

        $this->assertTrue($GLOBALS['col_priv']);

        $this->assertTrue($GLOBALS['db_priv']);

        $this->assertTrue($GLOBALS['proc_priv']);

        $this->assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 4
        [
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`db` TO \'root\'@\'localhost\'',
        );

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust(
            $showGrantsStr,
            $showGrantsDbname,
            $showGrantsTblname,
        );

        $this->assertFalse($GLOBALS['col_priv']);

        $this->assertTrue($GLOBALS['db_priv']);

        $this->assertFalse($GLOBALS['proc_priv']);

        $this->assertFalse($GLOBALS['table_priv']);
    }
}
