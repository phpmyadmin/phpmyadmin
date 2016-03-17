<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for operations
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

use PMA\libraries\Theme;

$GLOBALS['server'] = 1;
require_once 'libraries/operations.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/relation.lib.php';


require_once 'libraries/database_interface.inc.php';

require_once 'libraries/mysql_charsets.inc.php';

/**
 * tests for operations
 *
 * @package PhpMyAdmin-test
 */
class PMA_Operations_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up global environment.
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'db';
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $GLOBALS['cfg'] = array(
            'ServerDefault' => 1,
            'ActionLinksMode' => 'icons',
        );
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['server'] = 1;

        $GLOBALS['db_priv'] = true;
        $GLOBALS['table_priv'] = true;
        $GLOBALS['col_priv'] = true;
        $GLOBALS['proc_priv'] = true;
        $GLOBALS['flush_priv'] = true;
    }

    /**
     * Test for PMA_getHtmlForDatabaseComment
     *
     * @return void
     */
    public function testGetHtmlForDatabaseComment()
    {

        $this->assertRegExp(
            '/.*db_operations.php(.|[\n])*Database comment.*name="comment"([\n]|.)*/m',
            PMA_getHtmlForDatabaseComment("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForRenameDatabase
     *
     * @return void
     */
    public function testGetHtmlForRenameDatabase()
    {

        $_REQUEST['db_collation'] = 'db1';
        $html = PMA_getHtmlForRenameDatabase("pma");
        $this->assertContains('db_operations.php', $html);
        $this->assertRegExp(
            '/.*db_rename.*Rename database to.*/',
            $html
        );
    }

    /**
     * Test for PMA_getHtmlForDropDatabaseLink
     *
     * @return void
     */
    public function testGetHtmlForDropDatabaseLink()
    {

        $this->assertRegExp(
            '/.*DROP.DATABASE.*db_operations.php.*Drop the database.*/',
            PMA_getHtmlForDropDatabaseLink("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForCopyDatabase
     *
     * @return void
     */
    public function testGetHtmlForCopyDatabase()
    {
        $_REQUEST['db_collation'] = 'db1';
        $html = PMA_getHtmlForCopyDatabase("pma");
        $this->assertRegExp('/.*db_operations.php.*/', $html);
        $this->assertRegExp('/.*db_copy.*/', $html);
        $this->assertRegExp('/.*Copy database to.*/', $html);
    }

    /**
     * Test for PMA_getHtmlForChangeDatabaseCharset
     *
     * @return void
     */
    public function testGetHtmlForChangeDatabaseCharset()
    {

        $_REQUEST['db_collation'] = 'db1';
        $this->assertRegExp(
            '/.*db_operations.php(.|[\n])*select_db_collation([\n]|.)*Collation.*/m',
            PMA_getHtmlForChangeDatabaseCharset("pma", "bookmark")
        );
    }

    /**
     * Test for PMA_getHtmlForOrderTheTable
     *
     * @return void
     */
    public function testGetHtmlForOrderTheTable()
    {

        $this->assertRegExp(
            '/.*tbl_operations.php(.|[\n])*Alter table order by([\n]|.)*order_order.*/m',
            PMA_getHtmlForOrderTheTable(
                array(array('Field' => "column1"), array('Field' => "column2"))
            )
        );
    }

    /**
     * Test for PMA_getHtmlForTableRow
     *
     * @return void
     */
    public function testGetHtmlForTableRow()
    {

        $this->assertEquals(
            '<tr><td class="vmiddle"><label for="name">lable</label></td><td><input type="checkbox" name="name" id="name" value="1"/></td></tr>',
            PMA_getHtmlForTableRow("name", "lable", "value")
        );
    }

    /**
     * Test for PMA_getMaintainActionlink
     *
     * @return void
     */
    public function testGetMaintainActionlink()
    {

        $this->assertRegExp(
            '/.*href="sql.php.*post.*/',
            PMA_getMaintainActionlink(
                "post",
                array("name" => 'foo', "value" => 'bar'),
                array(),
                'doclink'
            )
        );
    }

    /**
     * Test for PMA_getHtmlForDeleteDataOrTable
     *
     * @return void
     */
    public function testGetHtmlForDeleteDataOrTable()
    {

        $this->assertRegExp(
            '/.*Delete data or table.*Empty the table.*Delete the table.*/m',
            PMA_getHtmlForDeleteDataOrTable(
                array("truncate" => 'foo'), array("drop" => 'bar')
            )
        );
    }

    /**
     * Test for PMA_getDeleteDataOrTablelink
     *
     * @return void
     */
    public function testGetDeleteDataOrTablelink()
    {

        $this->assertRegExp(
            '/.*TRUNCATE.TABLE.foo.*id_truncate.*Truncate table.*/m',
            PMA_getDeleteDataOrTablelink(
                array("sql" => 'TRUNCATE TABLE foo'),
                "TRUNCATE_TABLE",
                "Truncate table",
                "id_truncate"
            )
        );
    }

    /**
     * Test for PMA_getHtmlForPartitionMaintenance
     *
     * @return void
     */
    public function testGetHtmlForPartitionMaintenance()
    {
        $html = PMA_getHtmlForPartitionMaintenance(
            array("partition1", "partion2"),
            array("param1" => 'foo', "param2" => 'bar')
        );
        $this->assertRegExp('/.*action="tbl_operations.php".*/', $html);
        $this->assertRegExp('/.*ANALYZE.*/', $html);
        $this->assertRegExp('/.*REBUILD.*/', $html);
    }

    /**
     * Test for PMA_getHtmlForReferentialIntegrityCheck
     *
     * @return void
     */
    public function testGetHtmlForReferentialIntegrityCheck()
    {

        $this->assertRegExp(
            '/.*Check referential integrity.*href="sql.php(.|[\n])*/m',
            PMA_getHtmlForReferentialIntegrityCheck(
                array(
                    array(
                        'foreign_db'    => 'db1',
                        'foreign_table' => "foreign1",
                        'foreign_field' => "foreign2"
                    )
                ),
                array("param1" => 'a', "param2" => 'b')
            )
        );
    }


}
