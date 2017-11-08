<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for operations
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Operations;
use PhpMyAdmin\Theme;
use PHPUnit\Framework\TestCase;

/**
 * tests for operations
 *
 * @package PhpMyAdmin-test
 */
class OperationsTest extends TestCase
{
    /**
     * Set up global environment.
     *
     * @return void
     */
    public function setup()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'db';
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
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * Test for Operations::getHtmlForDatabaseComment
     *
     * @return void
     */
    public function testGetHtmlForDatabaseComment()
    {

        $this->assertRegExp(
            '/.*db_operations.php(.|[\n])*Database comment.*name="comment"([\n]|.)*/m',
            Operations::getHtmlForDatabaseComment("pma")
        );
    }

    /**
     * Test for Operations::getHtmlForRenameDatabase
     *
     * @return void
     */
    public function testGetHtmlForRenameDatabase()
    {

        $_REQUEST['db_collation'] = 'db1';
        $html = Operations::getHtmlForRenameDatabase("pma");
        $this->assertContains('db_operations.php', $html);
        $this->assertRegExp(
            '/.*db_rename.*Rename database to.*/',
            $html
        );
    }

    /**
     * Test for Operations::getHtmlForDropDatabaseLink
     *
     * @return void
     */
    public function testGetHtmlForDropDatabaseLink()
    {

        $this->assertRegExp(
            '/.*DROP.DATABASE.*db_operations.php.*Drop the database.*/',
            Operations::getHtmlForDropDatabaseLink("pma")
        );
    }

    /**
     * Test for Operations::getHtmlForCopyDatabase
     *
     * @return void
     */
    public function testGetHtmlForCopyDatabase()
    {
        $_REQUEST['db_collation'] = 'db1';
        $html = Operations::getHtmlForCopyDatabase("pma");
        $this->assertRegExp('/.*db_operations.php.*/', $html);
        $this->assertRegExp('/.*db_copy.*/', $html);
        $this->assertRegExp('/.*Copy database to.*/', $html);
    }

    /**
     * Test for Operations::getHtmlForChangeDatabaseCharset
     *
     * @return void
     */
    public function testGetHtmlForChangeDatabaseCharset()
    {

        $_REQUEST['db_collation'] = 'db1';
        $result = Operations::getHtmlForChangeDatabaseCharset("pma", "bookmark");
        $this->assertRegExp(
            '/.*select_db_collation.*Collation.*/m', $result
        );
        $this->assertRegExp(
            '/.*db_operations.php.*/', $result
        );
    }

    /**
     * Test for Operations::getHtmlForOrderTheTable
     *
     * @return void
     */
    public function testGetHtmlForOrderTheTable()
    {

        $this->assertRegExp(
            '/.*tbl_operations.php(.|[\n])*Alter table order by([\n]|.)*order_order.*/m',
            Operations::getHtmlForOrderTheTable(
                array(array('Field' => "column1"), array('Field' => "column2"))
            )
        );
    }

    /**
     * Test for Operations::getHtmlForTableRow
     *
     * @return void
     */
    public function testGetHtmlForTableRow()
    {

        $this->assertEquals(
            '<tr><td class="vmiddle"><label for="name">lable</label></td><td><input type="checkbox" name="name" id="name" value="1"/></td></tr>',
            Operations::getHtmlForTableRow("name", "lable", "value")
        );
    }

    /**
     * Test for Operations::getMaintainActionlink
     *
     * @return void
     */
    public function testGetMaintainActionlink()
    {

        $this->assertRegExp(
            '/.*href="sql.php.*post.*/',
            Operations::getMaintainActionlink(
                "post",
                array("name" => 'foo', "value" => 'bar'),
                array(),
                'doclink'
            )
        );
    }

    /**
     * Test for Operations::getHtmlForDeleteDataOrTable
     *
     * @return void
     */
    public function testGetHtmlForDeleteDataOrTable()
    {

        $this->assertRegExp(
            '/.*Delete data or table.*Empty the table.*Delete the table.*/m',
            Operations::getHtmlForDeleteDataOrTable(
                array("truncate" => 'foo'), array("drop" => 'bar')
            )
        );
    }

    /**
     * Test for Operations::getDeleteDataOrTablelink
     *
     * @return void
     */
    public function testGetDeleteDataOrTablelink()
    {

        $this->assertRegExp(
            '/.*TRUNCATE.TABLE.foo.*id_truncate.*Truncate table.*/m',
            Operations::getDeleteDataOrTablelink(
                array("sql" => 'TRUNCATE TABLE foo'),
                "TRUNCATE_TABLE",
                "Truncate table",
                "id_truncate"
            )
        );
    }

    /**
     * Test for Operations::getHtmlForPartitionMaintenance
     *
     * @return void
     */
    public function testGetHtmlForPartitionMaintenance()
    {
        $html = Operations::getHtmlForPartitionMaintenance(
            array("partition1", "partion2"),
            array("param1" => 'foo', "param2" => 'bar')
        );
        $this->assertRegExp('/.*action="tbl_operations.php".*/', $html);
        $this->assertRegExp('/.*ANALYZE.*/', $html);
        $this->assertRegExp('/.*REBUILD.*/', $html);
    }

    /**
     * Test for Operations::getHtmlForReferentialIntegrityCheck
     *
     * @return void
     */
    public function testGetHtmlForReferentialIntegrityCheck()
    {

        $this->assertRegExp(
            '/.*Check referential integrity.*href="sql.php(.|[\n])*/m',
            Operations::getHtmlForReferentialIntegrityCheck(
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
