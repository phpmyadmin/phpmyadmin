<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Table.class.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Table.class.php';
require_once 'libraries/mysql_charsets.lib.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';

/**
 * Tests behaviour of PMA_Table class
 *
 * @package PhpMyAdmin-test
 */
class PMA_Table_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Configures environment
     *
     * @return void
     */
    protected function setUp()
    {
        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['MySQLManualType'] = 'viewable';
        $GLOBALS['cfg']['MySQLManualBase'] = 'http://dev.mysql.com/doc/refman';
        $GLOBALS['cfg']['ActionLinksMode'] = 'both';
        $_SESSION['PMA_Theme'] = new PMA_Theme();
        $GLOBALS['pmaThemeImage'] = 'themes/dot.gif';
        $GLOBALS['is_ajax_request'] = false;
        $GLOBALS['cfgRelation'] = PMA_getRelationsParam();
        
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        
        $sql_isView_true =  "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = 'PMA'
                AND TABLE_NAME = 'PMA_BookMark'";
        
        $sql_isView_false =  "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = 'PMA'
                AND TABLE_NAME = 'PMA_BookMark_2'";
                
        $sql_isUpdatableView_true = "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = 'PMA'
                AND TABLE_NAME = 'PMA_BookMark'
                AND IS_UPDATABLE = 'YES'";
                
        $sql_isUpdatableView_false = "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = 'PMA'
                AND TABLE_NAME = 'PMA_BookMark_2'
                AND IS_UPDATABLE = 'YES'";
                
        $sql_analyzeStructure_true = "SELECT COLUMN_NAME, DATA_TYPE
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = 'PMA'
                AND TABLE_NAME = 'PMA_BookMark'";
        
        $getUniqueColumns_sql = "select unique column";
        
        $fetchResult = array(
            array(
                $sql_isView_true,
                null,
                null,
                null,
                0,
                true
            ),
            array(
                $sql_isView_false,
                null,
                null,
                null,
                0,
                false
            ),
            array(
                $sql_isUpdatableView_true,
                null,
                null,
                null,
                0,
                true
            ),
            array(
                $sql_isUpdatableView_false,
                null,
                null,
                null,
                0,
                false
            ),
            array(
                $sql_analyzeStructure_true,
                null,
                null,
                null,
                0,
                array(
                    array('COLUMN_NAME'=>'COLUMN_NAME', 'DATA_TYPE'=>'DATA_TYPE')
                )
            ),
            array(
                $getUniqueColumns_sql,
                array('Key_name', null),
                'Column_name',
                null,
                0,
                array(
                    array('index1'),
                    array('index3'),
                    array('index5'),
                )
            ),
            array(
                $getUniqueColumns_sql,
                'Column_name',
                'Column_name',
                null,
                0,
                array(
                    'column1',
                    'column3',
                    'column5',
                )
            ),
            array(
                'SHOW COLUMNS FROM `PMA`.`PMA_BookMark`',
                'Field',
                'Field',
                null,
                0,
                array(
                    'column1',
                    'column3',
                    'column5',
                )
            ),
        );
        
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        
        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $databases = array();
        $database_name = 'PMA';
        $databases[$database_name]['SCHEMA_TABLES'] = 1;
        $databases[$database_name]['SCHEMA_TABLE_ROWS'] = 3;
        $databases[$database_name]['SCHEMA_DATA_LENGTH'] = 5;
        $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH'] = 10;
        $databases[$database_name]['SCHEMA_INDEX_LENGTH'] = 10;
        $databases[$database_name]['SCHEMA_LENGTH'] = 10;
        
        $dbi->expects($this->any())->method('getTablesFull')
            ->will($this->returnValue($databases));  
                  
        $dbi->expects($this->any())->method('isSystemSchema')
            ->will($this->returnValue(false));   
                  
        $dbi->expects($this->any())->method('numRows')
            ->will($this->returnValue(20));  

        $triggers = array(
            array("name" => "name1", "create"=>"crate1"),
            array("name" => "name2", "create"=>"crate2"),
            array("name" => "name3", "create"=>"crate3"),
        );   
                  
        $dbi->expects($this->any())->method('getTriggers')
            ->will($this->returnValue($triggers));     
                  
        $dbi->expects($this->any())->method('query')
            ->will($this->returnValue("executed"));     

        $dbi->expects($this->any())->method('getTableIndexesSql')
            ->will($this->returnValue($getUniqueColumns_sql));
        
        $GLOBALS['dbi'] = $dbi;
        
        //RunKit
        if (!defined("PMA_DRIZZLE")) {
            define("PMA_DRIZZLE", true);
        }
                
        if (PMA_DRIZZLE) {
            if (PMA_HAS_RUNKIT) {
                runkit_constant_redefine("PMA_DRIZZLE", false);
            }
        }
    }

    /**
     * tearDown function for test cases
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        if (PMA_HAS_RUNKIT) {
            runkit_constant_redefine("PMA_DRIZZLE", false);
        }
    }
    
    /**
     * Test object creating
     *
     * @return void
     */
    public function testCreate()
    {
        $table = new PMA_Table('table1', 'pma_test');
        $this->assertInstanceOf('PMA_Table', $table);
    }

    /**
     * Test for constructor
     *
     * @return void
     */
    public function testConstruct()
    {
        $table = new PMA_Table("PMA_BookMark", "PMA");
        $this->assertEquals(
            'PMA_BookMark',
            $table->__toString()
        );
        $this->assertEquals(
            'PMA_BookMark',
            $table->getName()
        );
        $this->assertEquals(
            'PMA',
            $table->getDbName()
        );
        $this->assertEquals(
            'PMA.PMA_BookMark',
            $table->getFullName()
        );
    }

    /**
     * Test getName & getDbName
     *
     * @return void
     */
    public function testGetName()
    {
        $table = new PMA_Table('table1', 'pma_test');
        $this->assertEquals(
            "table1",
            $table->getName()
        );
        $this->assertEquals(
            "`table1`",
            $table->getName(true)
        );
        $this->assertEquals(
            "pma_test",
            $table->getDbName()
        );
        $this->assertEquals(
            "`pma_test`",
            $table->getDbName(true)
        );
    }

    /**
     * Test Set & Get
     *
     * @return void
     */
    public function testSetAndGet()
    {
        $table = new PMA_Table('table1', 'pma_test');
        $table->set('production', 'Phpmyadmin');
        $table->set('db', 'mysql');
        $this->assertEquals(
            "Phpmyadmin",
            $table->get("production")
        );
        $this->assertEquals(
            "mysql",
            $table->get("db")
        );
    }

    /**
     * Test getLastError & getLastMessage
     *
     * @return void
     */
    public function testGetLastErrorAndMessage()
    {
        $table = new PMA_Table('table1', 'pma_test');
        $table->errors[] = "error1";
        $table->errors[] = "error2";
        $table->errors[] = "error3";
        
        $table->messages[] = "messages1";
        $table->messages[] = "messages2";
        $table->messages[] = "messages3";
        
        $this->assertEquals(
            "error3",
            $table->getLastError()
        );
        $this->assertEquals(
            "messages3",
            $table->getLastMessage()
        );
    }

    /**
     * Test name validation
     *
     * @param string  $name   name to test
     * @param boolena $result expected result
     *
     * @return void
     *
     * @dataProvider dataValidateName
     */
    public function testValidateName($name, $result)
    {
        $this->assertEquals(
            $result,
            PMA_Table::isValidName($name)
        );
    }

    /**
     * Data provider for name validation
     *
     * @return array with test data
     */
    public function dataValidateName()
    {
        return array(
            array('test', true),
            array('te/st', false),
            array('te.st', false),
            array('te\\st', false),
        );
    }
    
    /**
     * Test for isView
     *
     * @return void
     */
    public function testIsView()
    {
        $this->assertEquals(
            false,
            PMA_Table::isView()
        );

        //validate that it is the same as DBI fetchResult
        $this->assertEquals(
            true,
            PMA_Table::isView('PMA', 'PMA_BookMark')
        );
        $this->assertEquals(
            false,
            PMA_Table::isView('PMA', 'PMA_BookMark_2')
        );
    }

    /**
     * Test for isUpdatableView
     *
     * @return void
     */
    public function testIsUpdatableView()
    {
        $this->assertEquals(
            false,
            PMA_Table::isUpdatableView()
        );
        
        //validate that it is the same as DBI fetchResult
        $this->assertEquals(
            true,
            PMA_Table::isUpdatableView('PMA', 'PMA_BookMark')
        );
        $this->assertEquals(
            false,
            PMA_Table::isUpdatableView('PMA', 'PMA_BookMark_2')
        );
    }

    /**
     * Test for analyzeStructure
     *
     * @return void
     */
    public function testAnalyzeStructure()
    {
        $this->assertEquals(
            false,
            PMA_Table::analyzeStructure()
        );
        
        //validate that it is the same as DBI fetchResult
        $show_create_table = PMA_Table::analyzeStructure('PMA', 'PMA_BookMark');
        $this->assertEquals(
            array('type'=>'DATA_TYPE'),
            $show_create_table[0]['create_table_fields']['COLUMN_NAME']
        );
    }

    /**
     * Test for isMerge
     *
     * @return void
     */
    public function testIsMerge()
    {
        $this->assertEquals(
            false,
            PMA_Table::isMerge()
        );
          
        //validate that it is Merge?
        $result = PMA_Table::isMerge('PMA', 'PMA_BookMark');
        $this->assertEquals(
            '',
            $result
        );
        
        $table = 'PMA_BookMark';
        $db = 'PMA';
        PMA_Table::$cache[$db][$table] = array('table_name' => "PMA_BookMark");
        $result = PMA_Table::isMerge($db, $table);
        $this->assertEquals(
            false,
            $result
        );

        PMA_Table::$cache[$db][$table] = array('ENGINE' => "MERGE");
        $result = PMA_Table::isMerge($db, $table);
        $this->assertEquals(
            true,
            $result
        );

        unset(PMA_Table::$cache[$db][$table]);
        PMA_Table::$cache[$db][$table] = array('ENGINE' => "MRG_MYISAM");
        $result = PMA_Table::isMerge($db, $table);
        $this->assertEquals(
            true,
            $result
        );

        unset(PMA_Table::$cache[$db][$table]);

        PMA_Table::$cache[$db][$table] = array('ENGINE' => "ISDB");
        $result = PMA_Table::isMerge($db, $table);
        $this->assertEquals(
            false,
            $result
        );
    }

    /**
     * Test for sGetToolTip
     *
     * @return void
     */
    public function testSGetToolTip()
    {    
        $table = 'PMA_BookMark';
        $db = 'PMA';
        
        PMA_Table::$cache[$db][$table] = array('Comment' => "Comment222");
        
        PMA_Table::$cache[$db][$table]['ExactRows'] = 10;
        $result = PMA_Table::sGetToolTip($db, $table);   
        
        $this->assertEquals(
            'Comment222 (10 Rows)',
            $result
        );     
    }

    /**
     * Test for generateAlter
     *
     * @return void
     */
    public function testGenerateAlter()
    {    
        $table = 'PMA_BookMark';
        $db = 'PMA';
        
        //parameter
        $oldcol = 'name';
        $newcol = 'new_name';
        $type = 'VARCHAR';
        $length = '2';
        $attribute = 'new_name';
        $collation = 'charset1';
        $null = 'NULL';
        $default_type = 'USER_DEFINED';
        $default_value = 'VARCHAR';
        $extra = 'AUTO_INCREMENT';
        $comment = 'PMA comment';
        $field_primary = 'new_name';
        $index = array('new_name');
        $move_to = 'new_name';
        
        $result = PMA_Table::generateAlter(
            $oldcol, $newcol, $type, $length,
            $attribute, $collation, $null, $default_type, $default_value,
            $extra, $comment, $field_primary, $index, $move_to
        );   

        $expect = "";
        if (PMA_DRIZZLE) {
            $expect = "`name` `new_name` VARCHAR(2) new_name " 
                . "COLLATE charset1 NULL DEFAULT 'VARCHAR' " 
                . "AUTO_INCREMENT COMMENT 'PMA comment' AFTER `new_name`";
        } else {
            $expect = "`name` `new_name` VARCHAR(2) new_name CHARACTER " 
                . "SET charset1 NULL DEFAULT 'VARCHAR' " 
                . "AUTO_INCREMENT COMMENT 'PMA comment' AFTER `new_name`";
        }
        
        $this->assertEquals(
            $expect,
            $result
        );     
    }

    /**
     * Test for rename
     *
     * @return void
     */
    public function testRename()
    {    
        $table = 'PMA_BookMark';
        $db = 'PMA';
        
        $table = new PMA_Table($table, $db);
        
        //rename to same name
        $table_new = 'PMA_BookMark';
        $result = $table->rename($table_new);
        $this->assertEquals(
            true,
            $result
        );
        
        //isValidName
        //space in table name
        $table_new = 'PMA_BookMark ';
        $result = $table->rename($table_new);
        $this->assertEquals(
            false,
            $result
        );
        //empty name
        $table_new = '';
        $result = $table->rename($table_new);
        $this->assertEquals(
            false,
            $result
        );
        //dot in table name
        $table_new = 'PMA_.BookMark';
        $result = $table->rename($table_new);
        $this->assertEquals(
            false,
            $result
        );
        

        $table_new = 'PMA_BookMark_new';
        $db_new = 'PMA_new';
        $GLOBALS['pma'] = new DataBasePMAMock();
        $GLOBALS['pma']->databases = new DataBaseMock();
        $result = $table->rename($table_new, $db_new);
        $this->assertEquals(
            true,
            $result
        );
        //message
        $this->assertEquals(
            "Table PMA_BookMark has been renamed to PMA_BookMark_new.",
            $table->getLastMessage()
        );       
    }


    /**
     * Test for getUniqueColumns
     *
     * @return void
     */
    public function testGetUniqueColumns()
    {    
        $table = 'PMA_BookMark';
        $db = 'PMA';
        
        $table = new PMA_Table($table, $db);
        $return = $table->getUniqueColumns();
        $expect = array(
            '`PMA`.`PMA_BookMark`.`index1`',
            '`PMA`.`PMA_BookMark`.`index3`',
            '`PMA`.`PMA_BookMark`.`index5`'
        );
        $this->assertEquals(
            $expect,
            $return
        );     
    }


    /**
     * Test for getIndexedColumns
     *
     * @return void
     */
    public function testGetIndexedColumns()
    {    
        $table = 'PMA_BookMark';
        $db = 'PMA';
        
        $table = new PMA_Table($table, $db);
        $return = $table->getIndexedColumns();
        $expect = array(
            '`PMA`.`PMA_BookMark`.`column1`',
            '`PMA`.`PMA_BookMark`.`column3`',
            '`PMA`.`PMA_BookMark`.`column5`'
        );
        $this->assertEquals(
            $expect,
            $return
        );     
    }


    /**
     * Test for getColumns
     *
     * @return void
     */
    public function testGetColumns()
    {    
        $table = 'PMA_BookMark';
        $db = 'PMA';
        
        $table = new PMA_Table($table, $db);
        $return = $table->getColumns();
        $expect = array(
            '`PMA`.`PMA_BookMark`.`column1`',
            '`PMA`.`PMA_BookMark`.`column3`',
            '`PMA`.`PMA_BookMark`.`column5`'
        );
        $this->assertEquals(
            $expect,
            $return
        );     
    }
}

//mock PMA
Class DataBasePMAMock
{
    var $databases;    
}

//mock $GLOBALS['pma']->databases
Class DataBaseMock
{
    function exists($name)
    {
        return true;
    }    
}
?>
