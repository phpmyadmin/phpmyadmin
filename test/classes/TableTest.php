<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Table.php
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbi\DbiDummy;
use PhpMyAdmin\Index;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Table;
use PhpMyAdmin\Tests\PmaTestCase;
use ReflectionClass;

/**
 * Tests behaviour of Table class
 *
 * @package PhpMyAdmin-test
 */
class TableTest extends PmaTestCase
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
        $GLOBALS['cfg']['MaxExactCount'] = 100;
        $GLOBALS['cfg']['MaxExactCountViews'] = 100;
        $GLOBALS['cfg']['Server']['pmadb'] = "pmadb";
        $GLOBALS['sql_auto_increment'] = true;
        $GLOBALS['sql_if_not_exists'] = true;
        $GLOBALS['sql_drop_table'] = true;
        $GLOBALS['cfg']['Server']['table_uiprefs'] = "pma__table_uiprefs";

        $relation = new Relation();
        $GLOBALS['cfgRelation'] = $relation->getRelationsParam();
        $GLOBALS['dblist'] = new DataBasePMAMock();
        $GLOBALS['dblist']->databases = new DataBaseMock();

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

        $sql_copy_data = "SELECT TABLE_NAME
            FROM information_schema.VIEWS
            WHERE TABLE_SCHEMA = 'db_data'
                AND TABLE_NAME = 'table_data'";

        $getUniqueColumns_sql = "select unique column";

        $fetchResult = array(
            array(
                $sql_isView_true,
                null,
                null,
                DatabaseInterface::CONNECT_USER,
                0,
                true
            ),
            array(
                $sql_copy_data,
                null,
                null,
                DatabaseInterface::CONNECT_USER,
                0,
                false
            ),
            array(
                $sql_isView_false,
                null,
                null,
                DatabaseInterface::CONNECT_USER,
                0,
                false
            ),
            array(
                $sql_isUpdatableView_true,
                null,
                null,
                DatabaseInterface::CONNECT_USER,
                0,
                true
            ),
            array(
                $sql_isUpdatableView_false,
                null,
                null,
                DatabaseInterface::CONNECT_USER,
                0,
                false
            ),
            array(
                $sql_analyzeStructure_true,
                null,
                null,
                DatabaseInterface::CONNECT_USER,
                0,
                array(
                    array('COLUMN_NAME'=>'COLUMN_NAME', 'DATA_TYPE'=>'DATA_TYPE')
                )
            ),
            array(
                $getUniqueColumns_sql,
                array('Key_name', null),
                'Column_name',
                DatabaseInterface::CONNECT_USER,
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
                DatabaseInterface::CONNECT_USER,
                0,
                array(
                    'column1',
                    'column3',
                    'column5',
                    'ACCESSIBLE',
                    'ADD',
                    'ALL'
                )
            ),
            array(
                'SHOW COLUMNS FROM `PMA`.`PMA_BookMark`',
                'Field',
                'Field',
                DatabaseInterface::CONNECT_USER,
                0,
                array(
                    'column1',
                    'column3',
                    'column5',
                    'ACCESSIBLE',
                    'ADD',
                    'ALL'
                )
            ),
            array(
                'SHOW COLUMNS FROM `PMA`.`PMA_BookMark`',
                null,
                null,
                DatabaseInterface::CONNECT_USER,
                0,
                array(
                    array(
                        'Field'=>'COLUMN_NAME1',
                        'Type'=> 'INT(10)',
                        'Null'=> 'NO',
                        'Key'=> '',
                        'Default'=> NULL,
                        'Extra'=>''
                    ),
                    array(
                        'Field'=>'COLUMN_NAME2',
                        'Type'=> 'INT(10)',
                        'Null'=> 'YES',
                        'Key'=> '',
                        'Default'=> NULL,
                        'Extra'=>'STORED GENERATED'
                    )
                )
            ),
        );

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $dbi->expects($this->any())->method('fetchValue')
            ->will(
                $this->returnValue(
                    "CREATE TABLE `PMA`.`PMA_BookMark_2` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` text NOT NULL
                    )"
                )
            );

        $dbi->_table_cache["PMA"]["PMA_BookMark"] = array(
            'ENGINE' => true,
            'Create_time' => true,
            'TABLE_TYPE' => true,
            'Comment' => true,
        );

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

        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue(10));

        $triggers = array(
            array("name" => "name1", "create"=>"crate1"),
            array("name" => "name2", "create"=>"crate2"),
            array("name" => "name3", "create"=>"crate3"),
        );

        $dbi->expects($this->any())->method('getTriggers')
            ->will($this->returnValue($triggers));

        $create_sql = "CREATE TABLE `PMA`.`PMA_BookMark_2` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `username` text NOT NULL";
        $dbi->expects($this->any())->method('query')
            ->will($this->returnValue($create_sql));

        $dbi->expects($this->any())->method('getTableIndexesSql')
            ->will($this->returnValue($getUniqueColumns_sql));

        $dbi->expects($this->any())->method('insertId')
            ->will($this->returnValue(10));

        $dbi->expects($this->any())->method('fetchAssoc')
            ->will($this->returnValue(false));

        $value = array("Auto_increment" => "Auto_increment");
        $dbi->expects($this->any())->method('fetchSingleRow')
            ->will($this->returnValue($value));

        $dbi->expects($this->any())->method('fetchRow')
            ->will($this->returnValue(false));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test object creating
     *
     * @return void
     */
    public function testCreate()
    {
        $table = new Table('table1', 'pma_test');
        $this->assertInstanceOf('PhpMyAdmin\Table', $table);
    }

    /**
     * Test for constructor
     *
     * @return void
     */
    public function testConstruct()
    {
        $table = new Table("PMA_BookMark", "PMA");
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
        $table = new Table('table1', 'pma_test');
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
     * Test getLastError & getLastMessage
     *
     * @return void
     */
    public function testGetLastErrorAndMessage()
    {
        $table = new Table('table1', 'pma_test');
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
     * @param boolean $result expected result
     *
     * @return void
     *
     * @dataProvider dataValidateName
     */
    public function testValidateName($name, $result, $is_backquoted=false)
    {
        $this->assertEquals(
            $result,
            Table::isValidName($name, $is_backquoted)
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
            array('te st', false),
            array('  te st', true, true),
            array('test ', false),
            array('te.st', false),
            array('test ', false, true),
            array('te.st ', false, true),
        );
    }

    /**
     * Test for isView
     *
     * @return void
     */
    public function testIsView()
    {
        $table = new Table(null, null);
        $this->assertEquals(
            false,
            $table->isView()
        );

        //validate that it is the same as DBI fetchResult
        $table = new Table('PMA_BookMark', 'PMA');
        $this->assertEquals(
            true,
            $table->isView()
        );

        $table = new Table('PMA_BookMark_2', 'PMA');
        $this->assertEquals(
            false,
            $table->isView()
        );
    }

    /**
     * Test for generateFieldSpec
     *
     * @return void
     */
    public function testGenerateFieldSpec()
    {
        //type is BIT
        $name = "PMA_name";
        $type = "BIT";
        $length = '12';
        $attribute = 'PMA_attribute';
        $collation = 'PMA_collation';
        $null = 'NULL';
        $default_type = 'USER_DEFINED';
        $default_value = 12;
        $extra = 'AUTO_INCREMENT';
        $comment = 'PMA_comment';
        $virtuality = '';
        $expression = '';
        $move_to = '-first';

        $query = Table::generateFieldSpec(
            $name, $type, $length, $attribute, $collation,
            $null, $default_type,  $default_value, $extra, $comment,
            $virtuality, $expression, $move_to
        );
        $this->assertEquals(
            "`PMA_name` BIT(12) PMA_attribute NULL DEFAULT b'10' "
            . "AUTO_INCREMENT COMMENT 'PMA_comment' FIRST",
            $query
        );

        //type is DOUBLE
        $type = "DOUBLE";
        $query = Table::generateFieldSpec(
            $name, $type, $length, $attribute, $collation,
            $null, $default_type,  $default_value, $extra, $comment,
            $virtuality, $expression, $move_to
        );
        $this->assertEquals(
            "`PMA_name` DOUBLE(12) PMA_attribute NULL DEFAULT '12' "
            . "AUTO_INCREMENT COMMENT 'PMA_comment' FIRST",
            $query
        );

        //type is BOOLEAN
        $type = "BOOLEAN";
        $query = Table::generateFieldSpec(
            $name, $type, $length, $attribute, $collation,
            $null, $default_type,  $default_value, $extra, $comment,
            $virtuality, $expression, $move_to
        );
        $this->assertEquals(
            "`PMA_name` BOOLEAN PMA_attribute NULL DEFAULT TRUE "
            . "AUTO_INCREMENT COMMENT 'PMA_comment' FIRST",
            $query
        );

        //$default_type is NULL
        $default_type = 'NULL';
        $query = Table::generateFieldSpec(
            $name, $type, $length, $attribute, $collation,
            $null, $default_type,  $default_value, $extra, $comment,
            $virtuality, $expression, $move_to
        );
        $this->assertEquals(
            "`PMA_name` BOOLEAN PMA_attribute NULL DEFAULT NULL "
            . "AUTO_INCREMENT COMMENT 'PMA_comment' FIRST",
            $query
        );

        //$default_type is CURRENT_TIMESTAMP
        $default_type = 'CURRENT_TIMESTAMP';
        $query = Table::generateFieldSpec(
            $name, $type, $length, $attribute, $collation,
            $null, $default_type,  $default_value, $extra, $comment,
            $virtuality, $expression, $move_to
        );
        $this->assertEquals(
            "`PMA_name` BOOLEAN PMA_attribute NULL DEFAULT CURRENT_TIMESTAMP "
            . "AUTO_INCREMENT COMMENT 'PMA_comment' FIRST",
            $query
        );

        //$default_type is current_timestamp()
        $default_type = 'current_timestamp()';
        $query = Table::generateFieldSpec(
            $name, $type, $length, $attribute, $collation,
            $null, $default_type,  $default_value, $extra, $comment,
            $virtuality, $expression, $move_to
        );
        $this->assertEquals(
            "`PMA_name` BOOLEAN PMA_attribute NULL DEFAULT current_timestamp() "
            . "AUTO_INCREMENT COMMENT 'PMA_comment' FIRST",
            $query
        );

        // $type is 'TIMESTAMP(3), $default_type is CURRENT_TIMESTAMP(3)
        $type = 'TIMESTAMP';
        $length = '3';
        $extra = '';
        $default_type = 'CURRENT_TIMESTAMP';
        $query = Table::generateFieldSpec(
            $name, $type, $length, $attribute, $collation,
            $null, $default_type,  $default_value, $extra, $comment,
            $virtuality, $expression, $move_to
        );
        $this->assertEquals(
            "`PMA_name` TIMESTAMP(3) PMA_attribute NULL DEFAULT CURRENT_TIMESTAMP(3) "
            . "COMMENT 'PMA_comment' FIRST",
            $query
        );

        //$default_type is NONE
        $type = 'BOOLEAN';
        $default_type = 'NONE';
        $extra = 'INCREMENT';
        $move_to = '-first';
        $query = Table::generateFieldSpec(
            $name, $type, $length, $attribute, $collation,
            $null, $default_type,  $default_value, $extra, $comment,
            $virtuality, $expression, $move_to
        );
        $this->assertEquals(
            "`PMA_name` BOOLEAN PMA_attribute NULL INCREMENT "
            . "COMMENT 'PMA_comment' FIRST",
            $query
        );
    }


    /**
     * Test for duplicateInfo
     *
     * @return void
     */
    public function testDuplicateInfo()
    {
        $work = "PMA_work";
        $pma_table = "pma_table";
        $get_fields =  array("filed0", "field6");
        $where_fields = array("field2", "filed5");
        $new_fields = array("field3", "filed4");
        $GLOBALS['cfgRelation'][$work] = true;
        $GLOBALS['cfgRelation']['db'] = "PMA_db";
        $GLOBALS['cfgRelation'][$pma_table] = "pma_table";

        $ret = Table::duplicateInfo(
            $work, $pma_table, $get_fields, $where_fields, $new_fields
        );
        $this->assertEquals(
            true,
            $ret
        );
    }
    /**
     * Test for isUpdatableView
     *
     * @return void
     */
    public function testIsUpdatableView()
    {
        $table = new Table(null, null);
        $this->assertEquals(
            false,
            $table->isUpdatableView()
        );

        //validate that it is the same as DBI fetchResult
        $table = new Table('PMA_BookMark', 'PMA');
        $this->assertEquals(
            true,
            $table->isUpdatableView()
        );

        $table = new Table('PMA_BookMark_2', 'PMA');
        $this->assertEquals(
            false,
            $table->isUpdatableView()
        );
    }

    /**
     * Test for isMerge -- when there's no ENGINE info cached
     *
     * @return void
     */
    public function testIsMergeCase1()
    {
        $tableObj = new Table('PMA_BookMark', 'PMA');
        $this->assertEquals(
            '',
            $tableObj->isMerge()
        );

        $GLOBALS['dbi']->expects($this->any())
            ->method('getCachedTableContent')
            ->will($this->returnValue(array('table_name' => "PMA_BookMark")));
        $tableObj = new Table('PMA_BookMark', 'PMA');
        $this->assertEquals(
            false,
            $tableObj->isMerge()
        );
    }

    /**
     * Test for isMerge -- when ENGINE info is MERGE
     *
     * @return void
     */
    public function testIsMergeCase2()
    {
        $map = array(
            array(array('PMA', 'PMA_BookMark'), null, array('ENGINE' => "MERGE")),
            array(array('PMA', 'PMA_BookMark', 'ENGINE'), null, "MERGE")
        );
        $GLOBALS['dbi']->expects($this->any())
            ->method('getCachedTableContent')
            ->will($this->returnValueMap($map));

        $tableObj = new Table('PMA_BookMark', 'PMA');
        $this->assertEquals(
            true,
            $tableObj->isMerge()
        );
    }

    /**
     * Test for isMerge -- when ENGINE info is MRG_MYISAM
     *
     * @return void
     */
    public function testIsMergeCase3()
    {
        $map = array(
            array(array('PMA', 'PMA_BookMark'), null, array('ENGINE' => "MRG_MYISAM")),
            array(array('PMA', 'PMA_BookMark', 'ENGINE'), null, "MRG_MYISAM")
        );
        $GLOBALS['dbi']->expects($this->any())
            ->method('getCachedTableContent')
            ->will($this->returnValueMap($map));

        $tableObj = new Table('PMA_BookMark', 'PMA');
        $this->assertEquals(
            true,
            $tableObj->isMerge()
        );
    }

    /**
     * Test for isMerge -- when ENGINE info is ISDB
     *
     * @return void
     */
    public function testIsMergeCase4()
    {
        $map = array(
            array(array('PMA', 'PMA_BookMark'), null, array('ENGINE' => "ISDB")),
            array(array('PMA', 'PMA_BookMark', 'ENGINE'), null, "ISDB")
        );
        $GLOBALS['dbi']->expects($this->any())
            ->method('getCachedTableContent')
            ->will($this->returnValueMap($map));

        $tableObj = new Table('PMA_BookMark', 'PMA');
        $this->assertEquals(
            false,
            $tableObj->isMerge()
        );
    }

    /**
     * Test for generateAlter
     *
     * @return void
     */
    public function testGenerateAlter()
    {
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
        $virtuality = '';
        $expression = '';
        $move_to = 'new_name';

        $result = Table::generateAlter(
            $oldcol, $newcol, $type, $length,
            $attribute, $collation, $null, $default_type, $default_value,
            $extra, $comment, $virtuality, $expression, $move_to
        );

        $expect = "`name` `new_name` VARCHAR(2) new_name CHARACTER SET "
            . "charset1 NULL DEFAULT 'VARCHAR' "
            . "AUTO_INCREMENT COMMENT 'PMA comment' AFTER `new_name`";

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

        $table = new Table($table, $db);

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
            true,
            $result
        );

        //message
        $this->assertEquals(
            "Table PMA_BookMark has been renamed to PMA_.BookMark.",
            $table->getLastMessage()
        );

        $table_new = 'PMA_BookMark_new';
        $db_new = 'PMA_new';
        $result = $table->rename($table_new, $db_new);
        $this->assertEquals(
            true,
            $result
        );
        //message
        $this->assertEquals(
            "Table PMA_.BookMark has been renamed to PMA_BookMark_new.",
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

        $table = new Table($table, $db);
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

        $table = new Table($table, $db);
        $return = $table->getIndexedColumns();
        $expect = array(
            '`PMA`.`PMA_BookMark`.`column1`',
            '`PMA`.`PMA_BookMark`.`column3`',
            '`PMA`.`PMA_BookMark`.`column5`',
            '`PMA`.`PMA_BookMark`.`ACCESSIBLE`',
            '`PMA`.`PMA_BookMark`.`ADD`',
            '`PMA`.`PMA_BookMark`.`ALL`',
        );
        $this->assertEquals(
            $expect,
            $return
        );
    }

    /**
     * Test for getColumnsMeta
     *
     * @return void
     */
    public function testGetColumnsMeta()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with("SELECT * FROM `db`.`table` LIMIT 1")
            ->will($this->returnValue('v1'));

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with("v1")
            ->will($this->returnValue('movecols'));

        $GLOBALS['dbi'] = $dbi;

        $tableObj = new Table('table', 'db');

        $this->assertEquals(
            $tableObj->getColumnsMeta(),
            'movecols'
        );
    }

    /**
     * Tests for _getSQLToCreateForeignKey() method.
     *
     * @return void
     * @test
     */
    public function testGetSQLToCreateForeignKey()
    {
        $table = "PMA_table";
        $field = array("PMA_field1", "PMA_field2");
        $foreignDb = "foreignDb";
        $foreignTable = "foreignTable";
        $foreignField = array("foreignField1", "foreignField2");

        $class = new ReflectionClass(Table::class);
        $method = $class->getMethod('_getSQLToCreateForeignKey');
        $method->setAccessible(true);
        $tableObj = new Table('PMA_table', 'db');

        $sql = $method->invokeArgs(
            $tableObj, array(
                $table,
                $field,
                $foreignDb,
                $foreignTable,
                $foreignField
            )
        );
        $sql_excepted = 'ALTER TABLE `PMA_table` ADD  '
            . 'FOREIGN KEY (`PMA_field1`, `PMA_field2`) REFERENCES '
            . '`foreignDb`.`foreignTable`(`foreignField1`, `foreignField2`);';
        $this->assertEquals(
            $sql_excepted,
            $sql
        );

        // Exclude db name when relations are made between table in the same db
        $sql = $method->invokeArgs(
            $tableObj, array(
                $table,
                $field,
                'db',
                $foreignTable,
                $foreignField
            )
        );
        $sql_excepted = 'ALTER TABLE `PMA_table` ADD  '
            . 'FOREIGN KEY (`PMA_field1`, `PMA_field2`) REFERENCES '
            . '`foreignTable`(`foreignField1`, `foreignField2`);';
        $this->assertEquals(
            $sql_excepted,
            $sql
        );
    }

    /**
     * Tests for getSqlQueryForIndexCreateOrEdit() method.
     *
     * @return void
     * @test
     */
    public function testGetSqlQueryForIndexCreateOrEdit()
    {
        $db = "pma_db";
        $table = "pma_table";
        $index = new Index();
        $error = false;

        $_POST['old_index'] = "PRIMARY";

        $table = new Table($table, $db);
        $sql = $table->getSqlQueryForIndexCreateOrEdit($index, $error);

        $this->assertEquals(
            "ALTER TABLE `pma_db`.`pma_table` DROP PRIMARY KEY, ADD UNIQUE ;",
            $sql
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

        $table = new Table($table, $db);
        $return = $table->getColumns();
        $expect = array(
            '`PMA`.`PMA_BookMark`.`column1`',
            '`PMA`.`PMA_BookMark`.`column3`',
            '`PMA`.`PMA_BookMark`.`column5`',
            '`PMA`.`PMA_BookMark`.`ACCESSIBLE`',
            '`PMA`.`PMA_BookMark`.`ADD`',
            '`PMA`.`PMA_BookMark`.`ALL`',
        );
        $this->assertEquals(
            $expect,
            $return
        );

        $return = $table->getReservedColumnNames();
        $expect = array(
            'ACCESSIBLE',
            'ADD',
            'ALL',
        );
        $this->assertEquals(
            $expect,
            $return
        );
    }

    /**
     * Test for checkIfMinRecordsExist
     *
     * @return void
     */
    public function testCheckIfMinRecordsExist()
    {
        $old_dbi = $GLOBALS['dbi'];
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue('res'));
        $dbi->expects($this->any())
            ->method('numRows')
            ->willReturnOnConsecutiveCalls(
                0,
                10,
                200
            );
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                array(array('`one_pk`')),

                array(), // No Uniques found
                array('`one_ind`', '`sec_ind`'),

                array(), // No Uniques found
                array()  // No Indexed found
            );

        $GLOBALS['dbi'] = $dbi;

        $table = 'PMA_BookMark';
        $db = 'PMA';
        $tableObj = new Table($table, $db);

        // Case 1 : Check if table is non-empty
        $return = $tableObj->checkIfMinRecordsExist();
        $expect = true;
        $this->assertEquals(
            $expect,
            $return
        );

        // Case 2 : Check if table contains at least 100
        $return = $tableObj->checkIfMinRecordsExist(100);
        $expect = false;
        $this->assertEquals(
            $expect,
            $return
        );

        // Case 3 : Check if table contains at least 100
        $return = $tableObj->checkIfMinRecordsExist(100);
        $expect = true;
        $this->assertEquals(
            $expect,
            $return
        );

        $GLOBALS['dbi'] = $old_dbi;
    }

    /**
     * Test for countRecords
     *
     * @return void
     */
    public function testCountRecords()
    {
        $map = array(
            array(
                array('PMA', 'PMA_BookMark'),
                null,
                array('Comment' => "Comment222", 'TABLE_TYPE' => "VIEW"),
            ),
            array(array('PMA', 'PMA_BookMark', 'TABLE_TYPE'), null, 'VIEW'),
        );
        $GLOBALS['dbi']->expects($this->any())
            ->method('getCachedTableContent')
            ->will($this->returnValueMap($map));

        $table = 'PMA_BookMark';
        $db = 'PMA';
        $tableObj = new Table($table, $db);

        $return = $tableObj->countRecords(true);
        $expect = 20;
        $this->assertEquals(
            $expect,
            $return
        );
    }

    /**
     * Test for setUiProp
     *
     * @return void
     */
    public function testSetUiProp()
    {
        $table_name = 'PMA_BookMark';
        $db = 'PMA';

        $table = new Table($table_name, $db);

        $property = Table::PROP_COLUMN_ORDER;
        $value = "UiProp_value";
        $table_create_time = null;
        $table->setUiProp($property, $value, $table_create_time);

        //set UI prop successfully
        $this->assertEquals(
            $value,
            $table->uiprefs[$property]
        );

        //removeUiProp
        $table->removeUiProp($property);
        $is_define_property = isset($table->uiprefs[$property]) ? true : false;
        $this->assertEquals(
            false,
            $is_define_property
        );

        //getUiProp after removeUiProp
        $is_define_property = $table->getUiProp($property);
        $this->assertEquals(
            false,
            $is_define_property
        );
    }

    /**
     * Test for moveCopy
     *
     * @return void
     */
    public function testMoveCopy()
    {
        $source_table = 'PMA_BookMark';
        $source_db = 'PMA';
        $target_table = 'PMA_BookMark_new';
        $target_db = 'PMA_new';
        $what = "dataonly";
        $move = true;
        $mode = "one_table";

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue(new Table($target_table, $target_db)));

        $_POST['drop_if_exists'] = true;

        $return = Table::moveCopy(
            $source_db, $source_table, $target_db,
            $target_table, $what, $move, $mode
        );

        //successfully
        $expect = true;
        $this->assertEquals(
            $expect,
            $return
        );
        $sql_query = "INSERT INTO `PMA_new`.`PMA_BookMark_new`(`COLUMN_NAME1`)"
            . " SELECT `COLUMN_NAME1` FROM "
            . "`PMA`.`PMA_BookMark`";
        $this->assertContains(
            $sql_query,
            $GLOBALS['sql_query']
        );
        $sql_query = "DROP VIEW `PMA`.`PMA_BookMark`";
        $this->assertContains(
            $sql_query,
            $GLOBALS['sql_query']
        );

        $return = Table::moveCopy(
            $source_db, $source_table, $target_db,
            $target_table, $what, false, $mode
        );

        //successfully
        $expect = true;
        $this->assertEquals(
            $expect,
            $return
        );
        $sql_query = "INSERT INTO `PMA_new`.`PMA_BookMark_new`(`COLUMN_NAME1`)"
            . " SELECT `COLUMN_NAME1` FROM "
            . "`PMA`.`PMA_BookMark`";
        $this->assertContains(
            $sql_query,
            $GLOBALS['sql_query']
        );
        $sql_query = "DROP VIEW `PMA`.`PMA_BookMark`";
        $this->assertNotContains(
            $sql_query,
            $GLOBALS['sql_query']
        );
    }

    /**
     * Test for getStorageEngine
     *
     * @return void
     */
    public function testGetStorageEngine(){
        $target_table = 'table1';
        $target_db = 'pma_test';
        $tbl_object = new Table($target_db, $target_table);
        $tbl_object->getStatusInfo(null, true);
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $expect = '';
        $tbl_storage_engine = $dbi->getTable(
            $target_db,
            $target_table
        )->getStorageEngine();
        $this->assertEquals(
            $expect,
            $tbl_storage_engine
        );
    }

    /**
     * Test for getComment
     *
     * @return void
     */
    public function testGetComment(){
        $target_table = 'table1';
        $target_db = 'pma_test';
        $tbl_object = new Table($target_db, $target_table);
        $tbl_object->getStatusInfo(null, true);
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $expect = '';
        $show_comment = $dbi->getTable(
            $target_db,
            $target_table
        )->getComment();
        $this->assertEquals(
            $expect,
            $show_comment
        );
    }

     /**
     * Test for getCollation
     *
     * @return void
     */
    public function testGetCollation(){
        $target_table = 'table1';
        $target_db = 'pma_test';
        $tbl_object = new Table($target_db, $target_table);
        $tbl_object->getStatusInfo(null, true);
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $expect = '';
        $tbl_collation = $dbi->getTable(
            $target_db,
            $target_table
        )->getCollation();
        $this->assertEquals(
            $expect,
            $tbl_collation
        );
    }

    /**
     * Test for getRowFormat
     *
     * @return void
     */
    public function testGetRowFormat(){
        $target_table = 'table1';
        $target_db = 'pma_test';
        $tbl_object = new Table($target_db, $target_table);
        $tbl_object->getStatusInfo(null, true);
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $expect = '';
        $row_format = $dbi->getTable(
            $target_db,
            $target_table
        )->getRowFormat();
        $this->assertEquals(
            $expect,
            $row_format
        );
    }

    /**
     * Test for getAutoIncrement
     *
     * @return void
     */
    public function testGetAutoIncrement(){
        $target_table = 'table1';
        $target_db = 'pma_test';
        $tbl_object = new Table($target_db, $target_table);
        $tbl_object->getStatusInfo(null, true);
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $expect = '';
        $auto_increment = $dbi->getTable(
            $target_db,
            $target_table
        )->getAutoIncrement();
        $this->assertEquals(
            $expect,
            $auto_increment
        );
    }

    /**
     * Test for getCreateOptions
     *
     * @return void
     */
    public function testGetCreateOptions(){
        $target_table = 'table1';
        $target_db = 'pma_test';
        $tbl_object = new Table($target_db, $target_table);
        $tbl_object->getStatusInfo(null, true);
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $expect = array('pack_keys' => 'DEFAULT');
        $create_options = $dbi->getTable(
            $target_db,
            $target_table
        )->getCreateOptions();
        $this->assertEquals(
            $expect,
            $create_options
        );
    }

}

/**
 * Mock class for DataBasePMAMock
 *
 * @package PhpMyAdmin-test
 */
Class DataBasePMAMock
{
    var $databases;
}

/**
 * Mock class for DataBaseMock
 *
 * @package PhpMyAdmin-test
 */
Class DataBaseMock
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
