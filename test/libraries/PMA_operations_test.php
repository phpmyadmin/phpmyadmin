<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for mime.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/operations.lib.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/CommonFunctions.class.php';
require_once 'libraries/Theme.class.php';

class PMA_operations_test extends PHPUnit_Framework_TestCase
{
    /**
     * Set up global environment.
     */
    public function setup() {
        $GLOBALS['table'] = 'table';
        $GLOBALS['db'] = 'db';
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION[' PMA_token '] = 'token';
        $GLOBALS['cfg'] = array(
            'MySQLManualType' => 'none',
            'AjaxEnable' => true,
            'ServerDefault' => 1,
            'PropertiesIconic' => true,
        );
        $GLOBALS['server'] = 1;

        if (! function_exists('PMA_generateCharsetDropdownBox')) {
            function PMA_generateCharsetDropdownBox()
            {
            }
        }
        if (! defined('PMA_CSDROPDOWN_CHARSET')) {
            define('PMA_CSDROPDOWN_CHARSET', '');
        }
        if (! defined('PMA_CSDROPDOWN_COLLATION')) {
            define('PMA_CSDROPDOWN_COLLATION', '');
        }
    }

    /**
     * Test for PMA_getHtmlForDatabaseComment
     */
    public function testPMA_getHtmlForDatabaseComment(){

        $this->assertRegExp(
            '/.*db_operations.php(.|[\n])*Database comment.*name="comment"([\n]|.)*/m',
            PMA_getHtmlForDatabaseComment("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForRenameDatabase
     */
    public function testPMA_getHtmlForRenameDatabase(){

        $_REQUEST['db_collation'] = 'db1';
        $this->assertRegExp(
            '/.*db_operations.php(.|[\n])*db_rename([\n]|.)*Rename database to.*/m',
            PMA_getHtmlForRenameDatabase("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForDropDatabaseLink
     */
    public function testPMA_getHtmlForDropDatabaseLink(){

        $this->assertRegExp(
            '/.*DROP.DATABASE.*db_operations.php.*Drop the database.*/',
            PMA_getHtmlForDropDatabaseLink("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForCopyDatabase
     */
    public function testPMA_getHtmlForCopyDatabase(){

        $_REQUEST['db_collation'] = 'db1';
        $this->assertRegExp(
            '/.*db_operations.php(.|[\n])*db_copy([\n]|.)*Copy database to.*/m',
            PMA_getHtmlForCopyDatabase("pma")
        );
    }

    /**
     * Test for PMA_getHtmlForChangeDatabaseCharset
     */
    public function testPMA_getHtmlForChangeDatabaseCharset(){

        $_REQUEST['db_collation'] = 'db1';
        $this->assertRegExp(
            '/.*db_operations.php(.|[\n])*select_db_collation([\n]|.)*Collation.*/m',
            PMA_getHtmlForChangeDatabaseCharset("pma", "bookmark")
        );
    }

    /**
     * Test for PMA_getHtmlForExportRelationalSchemaView
     */
    public function testPMA_getHtmlForExportRelationalSchemaView(){

        $this->assertRegExp(
            '/.*schema_edit.php.*Edit or export relational schema<.*/',
            PMA_getHtmlForExportRelationalSchemaView("id=001&name=pma")
        );
    }

    /**
     * Test for PMA_getHtmlForOrderTheTable
     */
    public function testPMA_getHtmlForOrderTheTable(){

        $this->assertRegExp(
            '/.*tbl_operations.php(.|[\n])*Alter table order by([\n]|.)*order_order.*/m',
            PMA_getHtmlForOrderTheTable(
                array(array('Field' => "column1"), array('Field' => "column2"))
            )
        );
    }

    /**
     * Test for PMA_getHtmlForTableRow
     */
    public function testPMA_getHtmlForTableRow(){

        $this->assertEquals(
            '<tr><td><label for="name">lable</label></td><td><input type="checkbox" name="name" id="name" value="1"/></td></tr>',
            PMA_getHtmlForTableRow("name", "lable", "value")
        );
    }

    /**
     * Test for PMA_getMaintainActionlink
     */
    public function testPMA_getMaintainActionlink(){

        $this->assertRegExp(
            '/.*href="tbl_operations.php.*post.*/',
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
     */
    public function testPMA_getHtmlForDeleteDataOrTable(){

        $this->assertRegExp(
            '/.*Delete data or table.*Empty the table.*Delete the table.*/m',
            PMA_getHtmlForDeleteDataOrTable(
                array("truncate" => 'foo'), array("drop" => 'bar')
            )
        );
    }

    /**
     * Test for PMA_getDeleteDataOrTablelink
     */
    public function testPMA_getDeleteDataOrTablelink(){

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
     */
    public function testPMA_getHtmlForPartitionMaintenance(){

        $this->assertRegExp(
            '/.*action="tbl_operations.php"(.|[\n])*ANALYZE([\n]|.)*REBUILD([\n]|.)*/m',
            PMA_getHtmlForPartitionMaintenance(
                array("partition1", "partion2"),
                array("param1" => 'foo', "param2" => 'bar')
            )
        );
    }

    /**
     * Test for PMA_getHtmlForReferentialIntegrityCheck
     */
    public function testPMA_getHtmlForReferentialIntegrityCheck(){

        $this->assertRegExp(
            '/.*Check referential integrity.*href="sql.php(.|[\n])*/m',
            PMA_getHtmlForReferentialIntegrityCheck(
                array(
                    array(
                        'foreign_table' => "foreign1",
                        'foreign_field' => "foreign2"
                    )
                ),
                array("param1" => 'a', "param2" => 'b')
            )
        );
    }


}
