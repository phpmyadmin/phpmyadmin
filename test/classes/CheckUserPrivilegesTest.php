<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\CheckUserPrivileges;

/**
 * @covers \PhpMyAdmin\CheckUserPrivileges
 */
class CheckUserPrivilegesTest extends AbstractTestCase
{
    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /**
     * prepares environment for tests
     */
    protected function setUp(): void
    {
        parent::setUp();
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
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON *.* TO \'root\'@\'localhost\' WITH GRANT OPTION'
        );

        self::assertSame('ALL PRIVILEGES', $show_grants_str);

        self::assertSame('*', $show_grants_dbname);

        self::assertSame('*', $show_grants_tblname);

        // TEST CASE 2

        [
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION'
        );

        self::assertSame('ALL PRIVILEGES', $show_grants_str);

        self::assertSame('mysql', $show_grants_dbname);

        self::assertSame('*', $show_grants_tblname);

        // TEST CASE 3

        [
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`columns_priv` TO \'root\'@\'localhost\''
        );

        self::assertSame('SELECT, INSERT, UPDATE, DELETE', $show_grants_str);

        self::assertSame('mysql', $show_grants_dbname);

        self::assertSame('columns_priv', $show_grants_tblname);

        // TEST CASE 4

        [
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON `cptest\_.`.* TO \'cptest\'@\'localhost\''
        );

        self::assertSame('cptest\_.', $show_grants_dbname);

        [
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON `cptest\_.a.b.c.d.e.f.g.h.i.j.k.'
                . 'l.m.n.o.p.q.r.s.t.u.v.w.x.y.z`.* TO \'cptest\'@\'localhost\''
        );

        self::assertSame('cptest\_.a.b.c.d.e.f.g.h.i.j.k.l.m.n.o.p.q.r.s.t.u.v.w.x.y.z', $show_grants_dbname);
    }

    /**
     * Test for checkRequiredPrivilegesForAdjust
     */
    public function testCheckRequiredPrivilegesForAdjust(): void
    {
        // TEST CASE 1
        [
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON *.* TO \'root\'@\'localhost\' WITH GRANT OPTION'
        );

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust(
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname
        );

        self::assertTrue($GLOBALS['col_priv']);

        self::assertTrue($GLOBALS['db_priv']);

        self::assertTrue($GLOBALS['proc_priv']);

        self::assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 2
        [
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT ALL PRIVILEGES ON `mysql`.* TO \'root\'@\'localhost\' WITH GRANT OPTION'
        );

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust(
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname
        );

        self::assertTrue($GLOBALS['col_priv']);

        self::assertTrue($GLOBALS['db_priv']);

        self::assertTrue($GLOBALS['proc_priv']);

        self::assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 3
        [
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.* TO \'root\'@\'localhost\''
        );

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust(
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname
        );

        self::assertTrue($GLOBALS['col_priv']);

        self::assertTrue($GLOBALS['db_priv']);

        self::assertTrue($GLOBALS['proc_priv']);

        self::assertTrue($GLOBALS['table_priv']);

        // re-initialise the privileges
        $this->setUp();

        // TEST CASE 4
        [
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname,
        ] = $this->checkUserPrivileges->getItemsFromShowGrantsRow(
            'GRANT SELECT, INSERT, UPDATE, DELETE ON `mysql`.`db` TO \'root\'@\'localhost\''
        );

        // call the to-be-tested function
        $this->checkUserPrivileges->checkRequiredPrivilegesForAdjust(
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname
        );

        self::assertFalse($GLOBALS['col_priv']);

        self::assertTrue($GLOBALS['db_priv']);

        self::assertFalse($GLOBALS['proc_priv']);

        self::assertFalse($GLOBALS['table_priv']);
    }
}
