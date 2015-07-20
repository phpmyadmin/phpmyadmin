<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_relation.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/relation.lib.php';
require_once 'libraries/Theme.class.php';

/**
 * Tests for libraries/tbl_relation.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblRelationTest extends PHPUnit_Framework_TestCase
{
    /**
     * Configures environment
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['pmaThemeImage'] = 'theme/';
        $GLOBALS['cfg']['ShowHint'] = true;
        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        $GLOBALS['pma'] = new DataBasePMAMockForTblRelation();
        $GLOBALS['pma']->databases = new DataBaseMockForTblRelation();

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Tests for PMA_getSQLToCreateForeignKey() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetSQLToCreateForeignKey()
    {
        // @todo Move this test to PMA_Table_test
        /*
        $table = "PMA_table";
        $field = array("PMA_field1", "PMA_field2");
        $foreignDb = "foreignDb";
        $foreignTable = "foreignTable";
        $foreignField = array("foreignField1", "foreignField2");

        $sql =  PMA_getSQLToCreateForeignKey(
            $table, $field, $foreignDb, $foreignTable, $foreignField
        );
        $sql_excepted = 'ALTER TABLE `PMA_table` ADD  '
            . 'FOREIGN KEY (`PMA_field1`, `PMA_field2`) REFERENCES '
            . '`foreignDb`.`foreignTable`(`foreignField1`, `foreignField2`);';
        $this->assertEquals(
            $sql_excepted,
            $sql
        );
        */
    }

    /**
     * Tests for PMA_getHtmlForCommonForm() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetHtmlForCommonForm()
    {
        // @todo Find out a better method to test for HTML
    }

    /**
     * Tests for PMA_getQueryForDisplayUpdate() method.
     * @todo Move this test to PMA_Table_test
     *
     * @return void
     * @test
     */
    public function testPMAGetQueryForDisplayUpdate()
    {
        /*
        $disp = true;
        $display_field = '';
        $db = "pma_db";
        $table = "pma_table";
        $cfgRelation = array(
            'displaywork' => true,
            'relwork' => true,
            'displaywork' => true,
            'table_info' => 'table_info',
        );

        $GLOBALS['cfgRelation']['db'] = 'global_db';

        //case 1: $disp == true && $display_field == ''
        $query = PMA_getQueryForDisplayUpdate(
            $disp, $display_field, $db, $table, $cfgRelation
        );
        $query_expect = "DELETE FROM `global_db`.`table_info` "
            . "WHERE db_name  = 'pma_db' AND table_name = 'pma_table'";
        $this->assertEquals(
            $query_expect,
            $query
        );

        //case 2: $disp == true && $display_field == 'display_field'
        $display_field == 'display_field';
        $query = PMA_getQueryForDisplayUpdate(
            $disp, $display_field, $db, $table, $cfgRelation
        );
        $query_expect = "DELETE FROM `global_db`.`table_info` "
            . "WHERE db_name  = 'pma_db' AND table_name = 'pma_table'";
        $this->assertEquals(
            $query_expect,
            $query
        );

        //case 3: $disp == false && $display_field == 'display_field'
        $disp = false;
        $display_field = 'display_field';
        $query = PMA_getQueryForDisplayUpdate(
            $disp, $display_field, $db, $table, $cfgRelation
        );
        $query_expect = "INSERT INTO `global_db`.`table_info`"
            . "(db_name, table_name, display_field)"
            . " VALUES('pma_db','pma_table','display_field')";
        $this->assertEquals(
            $query_expect,
            $query
        );

        //case 4: $disp == false && $display_field == ''
        $disp = false;
        $display_field = '';
        $query = PMA_getQueryForDisplayUpdate(
            $disp, $display_field, $db, $table, $cfgRelation
        );
        $query_expect = '';
        $this->assertEquals(
            $query_expect,
            $query
        );*/
    }
}

/**
 * Mock class for DataBasePMAMock
 *
 * @package PhpMyAdmin-test
 */
Class DataBasePMAMockForTblRelation
{
    var $databases;
}

/**
 * Mock class for DataBaseMock
 *
 * @package PhpMyAdmin-test
 */
Class DataBaseMockForTblRelation
{
    /**
     * mock function to return table is existed
     *
     * @param string $name table name
     *
     * @return bool
     */
    function exists($name)
    {
        return true;
    }
}
