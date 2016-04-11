<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for faked database access
 *
 * @package PhpMyAdmin-test
 */

require_once 'test/PMATestCase.php';

/**
 * Tests basic functionality of dummy dbi driver
 *
 * @package PhpMyAdmin-test
 */
class DatabaseInterfaceTest extends PMATestCase
{

    private $_dbi;

    /**
     * Configures test parameters.
     *
     * @return void
     */
    function setup()
    {
        $extension = new PMA\libraries\dbi\DBIDummy();
        $this->_dbi = new PMA\libraries\DatabaseInterface($extension);
    }

    /**
     * Tests for DBI::getColumnMapFromSql() method.
     *
     * @return void
     * @test
     */
    public function testPMAGetColumnMap()
    {
        $extension = $this->getMockBuilder('PMA\libraries\dbi\DBIDummy')
            ->disableOriginalConstructor()
            ->getMock();

        $extension->expects($this->any())
            ->method('realQuery')
            ->will($this->returnValue(true));

        $meta1 = new FieldMeta();
        $meta1->table = "meta1_table";
        $meta1->name = "meta1_name";

        $meta2 = new FieldMeta();
        $meta2->table = "meta2_table";
        $meta2->name = "meta2_name";

        $extension->expects($this->any())
            ->method('getFieldsMeta')
            ->will(
                $this->returnValue(
                    array(
                        $meta1, $meta2
                    )
                )
            );

        $dbi = new PMA\libraries\DatabaseInterface($extension);

        $sql_query = "PMA_sql_query";
        $view_columns = array(
            "view_columns1", "view_columns2"
        );

        $column_map = $dbi->getColumnMapFromSql(
            $sql_query, $view_columns
        );

        $this->assertEquals(
            array(
                'table_name' => 'meta1_table',
                'refering_column' => 'meta1_name',
                'real_column' => 'view_columns1'
            ),
            $column_map[0]
        );
        $this->assertEquals(
            array(
                'table_name' => 'meta2_table',
                'refering_column' => 'meta2_name',
                'real_column' => 'view_columns2'
            ),
            $column_map[1]
        );
    }

    /**
     * Tests for DBI::getSystemDatabase() method.
     *
     * @return void
     * @test
     */
    public function testGetSystemDatabase()
    {
        $sd = $this->_dbi->getSystemDatabase();
        $this->assertInstanceOf('PMA\libraries\SystemDatabase', $sd);
    }

    /**
     * Test for getDbCollation
     *
     * @return void
     * @test
     */
    public function testGetDbCollation()
    {
        $GLOBALS['server'] = 1;
        // test case for system schema
        $this->assertEquals(
            'utf8_general_ci',
            $this->_dbi->getDbCollation("information_schema")
        );

        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['DBG']['sql'] = false;

        $this->assertEquals(
            'utf8_general_ci',
            $this->_dbi->getDbCollation('pma_test')
        );
    }

    /**
     * Test for getServerCollation
     *
     * @return void
     * @test
     */
    public function testGetServerCollation()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['DBG']['sql'] = true;
        $this->assertEquals('utf8_general_ci', $this->_dbi->getServerCollation());
    }
}

/**
 * class for Table Field Meta
 *
 * @package PhpMyAdmin-test
 */
class FieldMeta
{
    public $table;
    public $name;
}
