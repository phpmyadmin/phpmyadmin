<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Index;
use PhpMyAdmin\ListDatabase;
use PhpMyAdmin\Query\Cache;
use PhpMyAdmin\Table;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;

/** @covers \PhpMyAdmin\Table */
class TableTest extends AbstractTestCase
{
    /**
     * Configures environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * SET these to avoid undefined index error
         */
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['MaxExactCount'] = 100;
        $GLOBALS['cfg']['MaxExactCountViews'] = 100;
        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['sql_auto_increment'] = true;
        $GLOBALS['sql_if_not_exists'] = true;
        $GLOBALS['sql_drop_table'] = true;
        $GLOBALS['cfg']['Server']['table_uiprefs'] = 'pma__table_uiprefs';

        $sqlIsViewTrue = 'SELECT 1'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'PMA\''
            . ' AND TABLE_NAME = \'PMA_BookMark\'';

        $sqlIsViewFalse = 'SELECT 1'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'PMA\''
            . ' AND TABLE_NAME = \'PMA_BookMark_2\'';

        $sqlIsUpdatableViewTrue = 'SELECT 1'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'PMA\''
            . ' AND TABLE_NAME = \'PMA_BookMark\''
            . ' AND IS_UPDATABLE = \'YES\'';

        $sqlIsUpdatableViewFalse = 'SELECT 1'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'PMA\''
            . ' AND TABLE_NAME = \'PMA_BookMark_2\''
            . ' AND IS_UPDATABLE = \'YES\'';

        $sqlAnalyzeStructureTrue = 'SELECT COLUMN_NAME, DATA_TYPE'
            . ' FROM information_schema.COLUMNS'
            . ' WHERE TABLE_SCHEMA = \'PMA\''
            . ' AND TABLE_NAME = \'PMA_BookMark\'';

        $sqlCopyData = 'SELECT 1'
            . ' FROM information_schema.VIEWS'
            . ' WHERE TABLE_SCHEMA = \'PMA_new\''
            . ' AND TABLE_NAME = \'PMA_BookMark_new\'';

        $getUniqueColumnsSql = 'SHOW INDEXES FROM `PMA`.`PMA_BookMark`';

        $fetchResult = [
            [
                $sqlAnalyzeStructureTrue,
                null,
                null,
                Connection::TYPE_USER,
                [['COLUMN_NAME' => 'COLUMN_NAME', 'DATA_TYPE' => 'DATA_TYPE']],
            ],
            [
                $getUniqueColumnsSql . ' WHERE (Non_unique = 0)',
                ['Key_name', null],
                'Column_name',
                Connection::TYPE_USER,
                [['index1'], ['index3'], ['index5']],
            ],
            [
                $getUniqueColumnsSql,
                'Column_name',
                'Column_name',
                Connection::TYPE_USER,
                ['column1', 'column3', 'column5', 'ACCESSIBLE', 'ADD', 'ALL'],
            ],
            [
                'SHOW COLUMNS FROM `PMA`.`PMA_BookMark`',
                'Field',
                'Field',
                Connection::TYPE_USER,
                ['column1', 'column3', 'column5', 'ACCESSIBLE', 'ADD', 'ALL'],
            ],
            [
                'SHOW COLUMNS FROM `PMA`.`PMA_BookMark`',
                null,
                null,
                Connection::TYPE_USER,
                [
                    [
                        'Field' => 'COLUMN_NAME1',
                        'Type' => 'INT(10)',
                        'Null' => 'NO',
                        'Key' => '',
                        'Default' => null,
                        'Extra' => '',
                    ],
                    [
                        'Field' => 'COLUMN_NAME2',
                        'Type' => 'INT(10)',
                        'Null' => 'YES',
                        'Key' => '',
                        'Default' => null,
                        'Extra' => 'STORED GENERATED',
                    ],
                ],
            ],
            [
                'SHOW TRIGGERS FROM `PMA` LIKE \'PMA_BookMark\';',
                null,
                null,
                Connection::TYPE_USER,
                [
                    [
                        'Trigger' => 'name1',
                        'Event' => 'INSERT',
                        'Table' => 'PMA_BookMark',
                        'Timing' => 'AFTER',
                        'Statement' => 'BEGIN END',
                        'Definer' => 'test_user@localhost',
                    ],
                    [
                        'Trigger' => 'name2',
                        'Event' => 'INSERT',
                        'Table' => 'PMA_BookMark',
                        'Timing' => 'AFTER',
                        'Statement' => 'BEGIN END',
                        'Definer' => 'test_user@localhost',
                    ],
                    [
                        'Trigger' => 'name3',
                        'Event' => 'INSERT',
                        'Table' => 'PMA_BookMark',
                        'Timing' => 'AFTER',
                        'Statement' => 'BEGIN END',
                        'Definer' => 'test_user@localhost',
                    ],
                ],
            ],
            [
                'SHOW TRIGGERS FROM `PMA` LIKE \'PMA_.BookMark\';',
                null,
                null,
                Connection::TYPE_USER,
                [
                    [
                        'Trigger' => 'name1',
                        'Event' => 'INSERT',
                        'Table' => 'PMA_.BookMark',
                        'Timing' => 'AFTER',
                        'Statement' => 'BEGIN END',
                        'Definer' => 'test_user@localhost',
                    ],
                    [
                        'Trigger' => 'name2',
                        'Event' => 'INSERT',
                        'Table' => 'PMA_.BookMark',
                        'Timing' => 'AFTER',
                        'Statement' => 'BEGIN END',
                        'Definer' => 'test_user@localhost',
                    ],
                    [
                        'Trigger' => 'name3',
                        'Event' => 'INSERT',
                        'Table' => 'PMA_.BookMark',
                        'Timing' => 'AFTER',
                        'Statement' => 'BEGIN END',
                        'Definer' => 'test_user@localhost',
                    ],
                ],
            ],
        ];

        $fetchValue = [
            [$sqlIsViewTrue, 0, Connection::TYPE_USER, 'PMA_BookMark'],
            [$sqlCopyData, 0, Connection::TYPE_USER, false],
            [$sqlIsViewFalse, 0, Connection::TYPE_USER, false],
            [$sqlIsUpdatableViewTrue, 0, Connection::TYPE_USER, 'PMA_BookMark'],
            [$sqlIsUpdatableViewFalse, 0, Connection::TYPE_USER, false],
            [
                "SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'aa' AND TABLE_NAME = 'ad'",
                0,
                Connection::TYPE_USER,
                'ad',
            ],
            [
                "SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'bb' AND TABLE_NAME = 'ad'",
                0,
                Connection::TYPE_USER,
                false,
            ],
        ];

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $databaseList = $this->createStub(ListDatabase::class);
        $databaseList->method('exists')->willReturn(true);
        $dbi->expects($this->any())->method('getDatabaseList')->willReturn($databaseList);

        $dbi->expects($this->any())->method('fetchResult')
            ->will($this->returnValueMap($fetchResult));

        $dbi->expects($this->any())->method('fetchValue')
            ->will($this->returnValueMap($fetchValue));

        $cache = new Cache();
        $dbi->expects($this->any())->method('getCache')
            ->will($this->returnValue($cache));

        $databases = [];
        $databaseName = 'PMA';
        $databases[$databaseName]['SCHEMA_TABLES'] = 1;
        $databases[$databaseName]['SCHEMA_TABLE_ROWS'] = 3;
        $databases[$databaseName]['SCHEMA_DATA_LENGTH'] = 5;
        $databases[$databaseName]['SCHEMA_MAX_DATA_LENGTH'] = 10;
        $databases[$databaseName]['SCHEMA_INDEX_LENGTH'] = 10;
        $databases[$databaseName]['SCHEMA_LENGTH'] = 10;

        $dbi->expects($this->any())->method('getTablesFull')
            ->will($this->returnValue($databases));

        $dbi->expects($this->any())->method('query')
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->any())->method('insertId')
            ->will($this->returnValue(10));

        $resultStub->expects($this->any())->method('fetchAssoc')
            ->will($this->returnValue([]));

        $value = ['Auto_increment' => 'Auto_increment'];
        $dbi->expects($this->any())->method('fetchSingleRow')
            ->will($this->returnValue($value));

        $resultStub->expects($this->any())->method('fetchRow')
            ->will($this->returnValue([]));

        $dbi->expects($this->any())->method('escapeString')
            ->will($this->returnArgument(0));

        $dbi->expects($this->any())->method('quoteString')
            ->will($this->returnCallback(static fn (string $string): string => "'" . $string . "'"));

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Test object creating
     */
    public function testCreate(): void
    {
        $table = new Table('table1', 'pma_test', $GLOBALS['dbi']);
        $this->assertInstanceOf(Table::class, $table);
    }

    /**
     * Test for constructor
     */
    public function testConstruct(): void
    {
        $table = new Table('PMA_BookMark', 'PMA', $GLOBALS['dbi']);
        $this->assertEquals(
            'PMA_BookMark',
            $table->__toString(),
        );
        $this->assertEquals(
            'PMA_BookMark',
            $table->getName(),
        );
        $this->assertEquals(
            'PMA',
            $table->getDbName(),
        );
        $this->assertEquals(
            'PMA.PMA_BookMark',
            $table->getFullName(),
        );
    }

    /**
     * Test getName & getDbName
     */
    public function testGetName(): void
    {
        $table = new Table('table1', 'pma_test', $GLOBALS['dbi']);
        $this->assertEquals(
            'table1',
            $table->getName(),
        );
        $this->assertEquals(
            '`table1`',
            $table->getName(true),
        );
        $this->assertEquals(
            'pma_test',
            $table->getDbName(),
        );
        $this->assertEquals(
            '`pma_test`',
            $table->getDbName(true),
        );
    }

    /**
     * Test getLastError & getLastMessage
     */
    public function testGetLastErrorAndMessage(): void
    {
        $table = new Table('table1', 'pma_test', $GLOBALS['dbi']);
        $table->errors[] = 'error1';
        $table->errors[] = 'error2';
        $table->errors[] = 'error3';

        $table->messages[] = 'messages1';
        $table->messages[] = 'messages2';
        $table->messages[] = 'messages3';

        $this->assertEquals(
            'error3',
            $table->getLastError(),
        );
        $this->assertEquals(
            'messages3',
            $table->getLastMessage(),
        );

        $table->errors = [];
        $this->assertEquals(
            '',
            $table->getLastError(),
        );

        $table->messages = [];
        $this->assertEquals(
            '',
            $table->getLastMessage(),
        );
    }

    /**
     * Test name validation
     *
     * @param string $name         name to test
     * @param bool   $result       expected result
     * @param bool   $isBackquoted is backquoted
     *
     * @dataProvider dataValidateName
     */
    public function testValidateName(string $name, bool $result, bool $isBackquoted = false): void
    {
        $this->assertEquals(
            $result,
            Table::isValidName($name, $isBackquoted),
        );
    }

    /** @return array<array{0: string, 1: bool, 2?: bool}> */
    public static function dataValidateName(): array
    {
        return [
            ['test', true],
            ['te/st', false],
            ['te.st', false],
            ['te\\st', false],
            ['te st', false],
            ['  te st', true, true],
            ['test ', false],
            ['te.st', false],
            ['test ', false, true],
            ['te.st ', false, true],
        ];
    }

    /**
     * Test for isView
     */
    public function testIsView(): void
    {
        $table = new Table('', '', $GLOBALS['dbi']);
        $this->assertFalse(
            $table->isView(),
        );

        //validate that it is the same as DBI fetchResult
        $table = new Table('PMA_BookMark', 'PMA', $GLOBALS['dbi']);
        $this->assertTrue(
            $table->isView(),
        );

        $table = new Table('PMA_BookMark_2', 'PMA', $GLOBALS['dbi']);
        $this->assertFalse(
            $table->isView(),
        );
    }

    /**
     * Test for generateFieldSpec
     */
    public function testGenerateFieldSpec(): void
    {
        //type is BIT
        $name = 'PMA_name';
        $type = 'BIT';
        $length = '12';
        $attribute = 'PMA_attribute';
        $collation = 'PMA_collation';
        $null = 'YES';
        $defaultType = 'USER_DEFINED';
        $defaultValue = '12';
        $extra = 'AUTO_INCREMENT';
        $comment = 'PMA_comment';
        $virtuality = '';
        $expression = '';
        $moveTo = '-first';

        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` BIT(12) PMA_attribute NULL DEFAULT b\'10\' AUTO_INCREMENT COMMENT \'PMA_comment\' FIRST',
            $query,
        );

        //type is DOUBLE
        $type = 'DOUBLE';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` DOUBLE(12) PMA_attribute NULL DEFAULT \'12\' AUTO_INCREMENT COMMENT \'PMA_comment\' FIRST',
            $query,
        );

        //type is BOOLEAN
        $type = 'BOOLEAN';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` BOOLEAN PMA_attribute NULL DEFAULT TRUE AUTO_INCREMENT COMMENT \'PMA_comment\' FIRST',
            $query,
        );

        //$default_type is NULL
        $defaultType = 'NULL';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` BOOLEAN PMA_attribute NULL DEFAULT NULL AUTO_INCREMENT COMMENT \'PMA_comment\' FIRST',
            $query,
        );

        //$default_type is CURRENT_TIMESTAMP
        $defaultType = 'CURRENT_TIMESTAMP';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` BOOLEAN PMA_attribute NULL DEFAULT CURRENT_TIMESTAMP '
            . "AUTO_INCREMENT COMMENT 'PMA_comment' FIRST",
            $query,
        );

        //$default_type is current_timestamp()
        $defaultType = 'current_timestamp()';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` BOOLEAN PMA_attribute NULL DEFAULT current_timestamp() '
            . "AUTO_INCREMENT COMMENT 'PMA_comment' FIRST",
            $query,
        );

        // $type is 'TIMESTAMP(3), $default_type is CURRENT_TIMESTAMP(3)
        $type = 'TIMESTAMP';
        $length = '3';
        $extra = '';
        $defaultType = 'CURRENT_TIMESTAMP';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` TIMESTAMP(3) PMA_attribute NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT \'PMA_comment\' FIRST',
            $query,
        );

        $type = 'TIMESTAMP';
        $length = '';
        $extra = '';
        $defaultType = 'USER_DEFINED';
        $defaultValue = '\'0000-00-00 00:00:00\'';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` TIMESTAMP PMA_attribute NULL DEFAULT \'0000-00-00 00:00:00\' COMMENT \'PMA_comment\' FIRST',
            $query,
        );

        $type = 'TIMESTAMP';
        $length = '';
        $extra = '';
        $defaultType = 'USER_DEFINED';
        $defaultValue = '\'0000-00-00 00:00:00.0\'';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` TIMESTAMP PMA_attribute NULL DEFAULT \'0000-00-00 00:00:00.0\' COMMENT \'PMA_comment\' FIRST',
            $query,
        );

        $type = 'TIMESTAMP';
        $length = '';
        $extra = '';
        $defaultType = 'USER_DEFINED';
        $defaultValue = '\'0000-00-00 00:00:00.000000\'';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals(
            '`PMA_name` TIMESTAMP PMA_attribute NULL DEFAULT \'0000-00-00 00:00:00.000000\' '
            . "COMMENT 'PMA_comment' FIRST",
            $query,
        );

        //$default_type is UUID
        $type = 'UUID';
        $defaultType = 'UUID';
        $moveTo = '';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            '',
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals('`PMA_name` UUID PMA_attribute NULL DEFAULT uuid()', $query);

        //$default_type is uuid()
        $type = 'UUID';
        $defaultType = 'uuid()';
        $moveTo = '';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            '',
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals('`PMA_name` UUID PMA_attribute NULL DEFAULT uuid()', $query);

        //$default_type is NONE
        $type = 'BOOLEAN';
        $defaultType = 'NONE';
        $extra = 'INCREMENT';
        $moveTo = '-first';
        $query = Table::generateFieldSpec(
            $name,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        $this->assertEquals('`PMA_name` BOOLEAN PMA_attribute NULL INCREMENT COMMENT \'PMA_comment\' FIRST', $query);

        $defaultType = 'NONE';
        $moveTo = '-first';
        $query = Table::generateFieldSpec(
            'ids',
            'INT',
            '11',
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            'AUTO_INCREMENT',
            $comment,
            $virtuality,
            $expression,
            $moveTo,
            ['id'],
            'id',
        );
        $this->assertEquals('`ids` INT(11) PMA_attribute NULL AUTO_INCREMENT COMMENT \'PMA_comment\' FIRST', $query);

        $defaultType = 'NONE';
        $moveTo = '-first';
        $query = Table::generateFieldSpec(
            'ids',
            'INT',
            '11',
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            'AUTO_INCREMENT',
            $comment,
            $virtuality,
            $expression,
            $moveTo,
            ['othercol'],
            'id',
        );
        // Add primary key for AUTO_INCREMENT if missing
        $this->assertEquals(
            '`ids` INT(11) PMA_attribute NULL AUTO_INCREMENT '
            . "COMMENT 'PMA_comment' FIRST, add PRIMARY KEY (`ids`)",
            $query,
        );

        $defaultType = 'NONE';
        $moveTo = '-first';
        $query = Table::generateFieldSpec(
            'id',
            'INT',
            '11',
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            'DEF',
            $comment,
            $virtuality,
            $expression,
            $moveTo,
            ['id'],
            'id',
        );
        // Do not add PK
        $this->assertEquals('`id` INT(11) PMA_attribute NULL DEF COMMENT \'PMA_comment\' FIRST', $query);

        $defaultType = 'NONE';
        $moveTo = '-first';
        $query = Table::generateFieldSpec(
            'ids',
            'INT',
            '11',
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            'DEF',
            $comment,
            $virtuality,
            $expression,
            $moveTo,
            ['id'],
            'id',
        );
        // Do not add PK
        $this->assertEquals('`ids` INT(11) PMA_attribute NULL DEF COMMENT \'PMA_comment\' FIRST', $query);

        $defaultType = 'NONE';
        $moveTo = '-first';
        $query = Table::generateFieldSpec(
            'ids',
            'INT',
            '11',
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            'DEF',
            $comment,
            $virtuality,
            $expression,
            $moveTo,
            ['ids'],
            'id',
        );
        // Add it beaucause it is missing
        $this->assertEquals(
            '`ids` INT(11) PMA_attribute NULL DEF COMMENT \'PMA_comment\' FIRST, add PRIMARY KEY (`ids`)',
            $query,
        );

        $defaultType = 'NONE';
        $moveTo = '-first';
        $query = Table::generateFieldSpec(
            'ids',
            'INT',
            '11',
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            'USER_DEFINED',
            $comment,
            'VIRTUAL',
            '1',
            $moveTo,
            ['othercol'],
            'id',
        );
        // Do not add PK since it is not a AUTO_INCREMENT
        $this->assertEquals(
            '`ids` INT(11) PMA_attribute AS (1) VIRTUAL NULL USER_DEFINED COMMENT \'PMA_comment\' FIRST',
            $query,
        );
    }

    /**
     * Test for duplicateInfo
     */
    public function testDuplicateInfo(): void
    {
        $getFields = ['filed0', 'field6'];
        $whereFields = ['field2', 'filed5'];
        $newFields = ['field3', 'filed4'];

        $relationParameters = RelationParameters::fromArray([
            'db' => 'PMA_db',
            'relwork' => true,
            'relation' => 'relation',
        ]);
        $_SESSION = ['relation' => [$GLOBALS['server'] => $relationParameters->toArray()]];

        $ret = Table::duplicateInfo('relwork', 'relation', $getFields, $whereFields, $newFields);
        $this->assertSame(-1, $ret);
    }

    /**
     * Test for isUpdatableView
     */
    public function testIsUpdatableView(): void
    {
        $table = new Table('', '', $GLOBALS['dbi']);
        $this->assertFalse(
            $table->isUpdatableView(),
        );

        //validate that it is the same as DBI fetchResult
        $table = new Table('PMA_BookMark', 'PMA', $GLOBALS['dbi']);
        $this->assertTrue(
            $table->isUpdatableView(),
        );

        $table = new Table('PMA_BookMark_2', 'PMA', $GLOBALS['dbi']);
        $this->assertFalse(
            $table->isUpdatableView(),
        );
    }

    /**
     * Test for isMerge -- when there's no ENGINE info cached
     */
    public function testIsMergeCase1(): void
    {
        $tableObj = new Table('PMA_BookMark', 'PMA', $GLOBALS['dbi']);
        $this->assertEquals(
            '',
            $tableObj->isMerge(),
        );

        $tableObj = new Table('PMA_BookMark', 'PMA', $GLOBALS['dbi']);
        $this->assertFalse(
            $tableObj->isMerge(),
        );
    }

    /**
     * Test for isMerge -- when ENGINE info is MERGE
     */
    public function testIsMergeCase2(): void
    {
        $GLOBALS['dbi']->getCache()->cacheTableContent(
            ['PMA', 'PMA_BookMark'],
            ['ENGINE' => 'MERGE'],
        );

        $tableObj = new Table('PMA_BookMark', 'PMA', $GLOBALS['dbi']);
        $this->assertTrue(
            $tableObj->isMerge(),
        );
    }

    /**
     * Test for isMerge -- when ENGINE info is MRG_MYISAM
     */
    public function testIsMergeCase3(): void
    {
        $GLOBALS['dbi']->getCache()->cacheTableContent(
            ['PMA', 'PMA_BookMark'],
            ['ENGINE' => 'MRG_MYISAM'],
        );

        $tableObj = new Table('PMA_BookMark', 'PMA', $GLOBALS['dbi']);
        $this->assertTrue(
            $tableObj->isMerge(),
        );
    }

    /**
     * Test for Table::isMerge -- when ENGINE info is ISDB
     */
    public function testIsMergeCase4(): void
    {
        $tableObj = new Table('PMA_BookMark', 'PMA', $GLOBALS['dbi']);
        $this->assertFalse(
            $tableObj->isMerge(),
        );
    }

    /**
     * Test for generateAlter
     */
    public function testGenerateAlter(): void
    {
        //parameter
        $oldcol = 'name';
        $newcol = 'new_name';
        $type = 'VARCHAR';
        $length = '2';
        $attribute = 'new_name';
        $collation = 'charset1';
        $null = 'YES';
        $defaultType = 'USER_DEFINED';
        $defaultValue = 'VARCHAR';
        $extra = 'AUTO_INCREMENT';
        $comment = 'PMA comment';
        $virtuality = '';
        $expression = '';
        $moveTo = 'new_name';

        $result = Table::generateAlter(
            $oldcol,
            $newcol,
            $type,
            $length,
            $attribute,
            $collation,
            $null,
            $defaultType,
            $defaultValue,
            $extra,
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );

        $expect = '`name` `new_name` VARCHAR(2) new_name CHARACTER SET '
            . "charset1 NULL DEFAULT 'VARCHAR' "
            . "AUTO_INCREMENT COMMENT 'PMA comment' AFTER `new_name`";

        $this->assertEquals($expect, $result);
    }

    /**
     * Test for rename
     */
    public function testRename(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $table = 'PMA_BookMark';
        $db = 'PMA';

        $table = new Table($table, $db, $GLOBALS['dbi']);

        //rename to same name
        $tableNew = 'PMA_BookMark';
        $result = $table->rename($tableNew);
        $this->assertTrue($result);

        //isValidName
        //space in table name
        $tableNew = 'PMA_BookMark ';
        $result = $table->rename($tableNew);
        $this->assertFalse($result);
        //empty name
        $tableNew = '';
        $result = $table->rename($tableNew);
        $this->assertFalse($result);
        //dot in table name
        $tableNew = 'PMA_.BookMark';
        $result = $table->rename($tableNew);
        $this->assertTrue($result);

        //message
        $this->assertEquals(
            'Table PMA_BookMark has been renamed to PMA_.BookMark.',
            $table->getLastMessage(),
        );

        $tableNew = 'PMA_BookMark_new';
        $dbNew = 'PMA_new';
        $result = $table->rename($tableNew, $dbNew);
        $this->assertTrue($result);
        //message
        $this->assertEquals(
            'Table PMA_.BookMark has been renamed to PMA_BookMark_new.',
            $table->getLastMessage(),
        );
    }

    /**
     * Test for getUniqueColumns
     */
    public function testGetUniqueColumns(): void
    {
        $table = 'PMA_BookMark';
        $db = 'PMA';

        $table = new Table($table, $db, $GLOBALS['dbi']);
        $return = $table->getUniqueColumns();
        $expect = ['`PMA`.`PMA_BookMark`.`index1`', '`PMA`.`PMA_BookMark`.`index3`', '`PMA`.`PMA_BookMark`.`index5`'];
        $this->assertEquals($expect, $return);
    }

    /**
     * Test for getIndexedColumns
     */
    public function testGetIndexedColumns(): void
    {
        $table = 'PMA_BookMark';
        $db = 'PMA';

        $table = new Table($table, $db, $GLOBALS['dbi']);
        $return = $table->getIndexedColumns();
        $expect = [
            '`PMA`.`PMA_BookMark`.`column1`',
            '`PMA`.`PMA_BookMark`.`column3`',
            '`PMA`.`PMA_BookMark`.`column5`',
            '`PMA`.`PMA_BookMark`.`ACCESSIBLE`',
            '`PMA`.`PMA_BookMark`.`ADD`',
            '`PMA`.`PMA_BookMark`.`ALL`',
        ];
        $this->assertEquals($expect, $return);
    }

    /**
     * Test for getColumnsMeta
     */
    public function testGetColumnsMeta(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SELECT * FROM `db`.`table` LIMIT 1')
            ->will($this->returnValue($resultStub));

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue(['aNonValidExampleToRefactor']));

        $GLOBALS['dbi'] = $dbi;

        $tableObj = new Table('table', 'db', $GLOBALS['dbi']);

        $this->assertEquals(
            $tableObj->getColumnsMeta(),
            ['aNonValidExampleToRefactor'],
        );
    }

    /**
     * Tests for getSQLToCreateForeignKey() method.
     */
    public function testGetSQLToCreateForeignKey(): void
    {
        $table = 'PMA_table';
        $field = ['PMA_field1', 'PMA_field2'];
        $foreignDb = 'foreignDb';
        $foreignTable = 'foreignTable';
        $foreignField = ['foreignField1', 'foreignField2'];

        $tableObj = new Table('PMA_table', 'db', $GLOBALS['dbi']);

        $sql = $this->callFunction(
            $tableObj,
            Table::class,
            'getSQLToCreateForeignKey',
            [$table, $field, $foreignDb, $foreignTable, $foreignField],
        );
        $sqlExcepted = 'ALTER TABLE `PMA_table` ADD  '
            . 'FOREIGN KEY (`PMA_field1`, `PMA_field2`) REFERENCES '
            . '`foreignDb`.`foreignTable`(`foreignField1`, `foreignField2`);';
        $this->assertEquals($sqlExcepted, $sql);

        // Exclude db name when relations are made between table in the same db
        $sql = $this->callFunction(
            $tableObj,
            Table::class,
            'getSQLToCreateForeignKey',
            [$table, $field, 'db', $foreignTable, $foreignField],
        );
        $sqlExcepted = 'ALTER TABLE `PMA_table` ADD  '
            . 'FOREIGN KEY (`PMA_field1`, `PMA_field2`) REFERENCES '
            . '`foreignTable`(`foreignField1`, `foreignField2`);';
        $this->assertEquals($sqlExcepted, $sql);
    }

    /**
     * Tests for getSqlQueryForIndexCreateOrEdit() method.
     */
    public function testGetSqlQueryForIndexCreateOrEdit(): void
    {
        $db = 'pma_db';
        $table = 'pma_table';
        $index = new Index();
        $error = false;

        $_POST['old_index'] = 'PRIMARY';

        $table = new Table($table, $db, $GLOBALS['dbi']);
        $sql = $table->getSqlQueryForIndexCreateOrEdit($index, $error);

        $this->assertEquals('ALTER TABLE `pma_db`.`pma_table` DROP PRIMARY KEY, ADD UNIQUE;', $sql);
    }

    /**
     * Tests for getSqlQueryForIndexCreateOrEdit() method.
     */
    public function testGetSqlQueryForIndexCreateOrEditSecondFormat(): void
    {
        $db = 'pma_db';
        $table = 'pma_table';
        $index = new Index();
        $error = false;

        $_POST['old_index']['Key_name'] = 'PRIMARY';

        $table = new Table($table, $db, $GLOBALS['dbi']);
        $sql = $table->getSqlQueryForIndexCreateOrEdit($index, $error);

        $this->assertEquals('ALTER TABLE `pma_db`.`pma_table` DROP PRIMARY KEY, ADD UNIQUE;', $sql);
    }

    /**
     * Test for getColumns
     */
    public function testGetColumns(): void
    {
        $table = 'PMA_BookMark';
        $db = 'PMA';

        $table = new Table($table, $db, $GLOBALS['dbi']);
        $return = $table->getColumns();
        $expect = [
            '`PMA`.`PMA_BookMark`.`column1`',
            '`PMA`.`PMA_BookMark`.`column3`',
            '`PMA`.`PMA_BookMark`.`column5`',
            '`PMA`.`PMA_BookMark`.`ACCESSIBLE`',
            '`PMA`.`PMA_BookMark`.`ADD`',
            '`PMA`.`PMA_BookMark`.`ALL`',
        ];
        $this->assertEquals($expect, $return);

        $return = $table->getReservedColumnNames();
        $expect = ['ACCESSIBLE', 'ADD', 'ALL'];
        $this->assertEquals($expect, $return);
    }

    /**
     * Test for checkIfMinRecordsExist
     */
    public function testCheckIfMinRecordsExist(): void
    {
        $oldDbi = $GLOBALS['dbi'];

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue($resultStub));
        $resultStub->expects($this->any())
            ->method('numRows')
            ->willReturnOnConsecutiveCalls(0, 10, 200);
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->willReturnOnConsecutiveCalls(
                [['`one_pk`']],
                [], // No Uniques found
                ['`one_ind`', '`sec_ind`'],
                [], // No Uniques found
                [], // No Indexed found
            );

        $GLOBALS['dbi'] = $dbi;

        $table = 'PMA_BookMark';
        $db = 'PMA';
        $tableObj = new Table($table, $db, $GLOBALS['dbi']);

        // Case 1 : Check if table is non-empty
        $return = $tableObj->checkIfMinRecordsExist();
        $expect = true;
        $this->assertEquals($expect, $return);

        // Case 2 : Check if table contains at least 100
        $return = $tableObj->checkIfMinRecordsExist(100);
        $expect = false;
        $this->assertEquals($expect, $return);

        // Case 3 : Check if table contains at least 100
        $return = $tableObj->checkIfMinRecordsExist(100);
        $expect = true;
        $this->assertEquals($expect, $return);

        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * Test for Table::countRecords
     */
    public function testCountRecords(): void
    {
        $resultStub = $this->createMock(DummyResult::class);
        $resultStub->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(20));

        $dbi = clone $GLOBALS['dbi'];
        $dbi->expects($this->any())->method('tryQuery')
            ->will($this->returnValue($resultStub));

        $table = 'PMA_BookMark';
        $db = 'PMA';
        $tableObj = new Table($table, $db, $dbi);

        $this->assertEquals(
            20,
            $tableObj->countRecords(true),
        );
    }

    /**
     * Test for setUiProp
     */
    public function testSetUiProp(): void
    {
        $tableName = 'PMA_BookMark';
        $db = 'PMA';

        $table = new Table($tableName, $db, $GLOBALS['dbi']);

        $property = Table::PROP_COLUMN_ORDER;
        $value = 'UiProp_value';
        $tableCreateTime = null;
        $table->setUiProp($property, $value, $tableCreateTime);

        //set UI prop successfully
        $this->assertEquals($value, $table->uiprefs[$property]);

        //removeUiProp
        $table->removeUiProp($property);
        $isDefineProperty = isset($table->uiprefs[$property]);
        $this->assertFalse($isDefineProperty);

        //getUiProp after removeUiProp
        $isDefineProperty = $table->getUiProp($property);
        $this->assertFalse($isDefineProperty);
    }

    /**
     * Test for moveCopy
     */
    public function testMoveCopy(): void
    {
        $sourceTable = 'PMA_BookMark';
        $sourceDb = 'PMA';
        $targetTable = 'PMA_BookMark_new';
        $targetDb = 'PMA_new';
        $what = 'dataonly';
        $move = true;
        $mode = 'one_table';

        unset($GLOBALS['sql_drop_table']);

        $getTableMap = [
            [$targetDb, $targetTable, new Table($targetTable, $targetDb, $GLOBALS['dbi'])],
            ['aa', 'ad', new Table('ad', 'aa', $GLOBALS['dbi'])],
        ];

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValueMap($getTableMap));

        $return = Table::moveCopy($sourceDb, $sourceTable, $targetDb, $targetTable, $what, $move, $mode, true);

        //successfully
        $expect = true;
        $this->assertEquals($expect, $return);
        $sqlQuery = 'INSERT INTO `PMA_new`.`PMA_BookMark_new`(`COLUMN_NAME1`)'
            . ' SELECT `COLUMN_NAME1` FROM '
            . '`PMA`.`PMA_BookMark`';
        $this->assertStringContainsString($sqlQuery, $GLOBALS['sql_query']);
        $sqlQuery = 'DROP VIEW `PMA`.`PMA_BookMark`';
        $this->assertStringContainsString($sqlQuery, $GLOBALS['sql_query']);

        $return = Table::moveCopy($sourceDb, $sourceTable, $targetDb, $targetTable, $what, false, $mode, true);

        //successfully
        $expect = true;
        $this->assertEquals($expect, $return);
        $sqlQuery = 'INSERT INTO `PMA_new`.`PMA_BookMark_new`(`COLUMN_NAME1`)'
            . ' SELECT `COLUMN_NAME1` FROM '
            . '`PMA`.`PMA_BookMark`';
        $this->assertStringContainsString($sqlQuery, $GLOBALS['sql_query']);
        $sqlQuery = 'DROP VIEW `PMA`.`PMA_BookMark`';
        $this->assertStringNotContainsString($sqlQuery, $GLOBALS['sql_query']);

        // Renaming DB with a view bug
        $resultStub = $this->createMock(DummyResult::class);
        $GLOBALS['dbi']->expects($this->any())->method('tryQuery')
            ->will($this->returnValueMap([
                [
                    'SHOW CREATE TABLE `aa`.`ad`',
                    Connection::TYPE_USER,
                    DatabaseInterface::QUERY_BUFFERED,
                    true,
                    $resultStub,
                ],
                [
                    'SHOW TABLE STATUS FROM `aa` WHERE Name = \'ad\'',
                    Connection::TYPE_USER,
                    DatabaseInterface::QUERY_BUFFERED,
                    true,
                    $resultStub,
                ],
                ['USE `aa`', Connection::TYPE_USER, DatabaseInterface::QUERY_BUFFERED, true, $resultStub],
            ]));
        $resultStub->expects($this->any())
            ->method('fetchRow')
            ->will($this->returnValue([
                'ad',
                'CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost`' .
                    ' SQL SECURITY DEFINER VIEW `ad` AS select `aa`.`bb`.`ac` AS `ac` from `bb`',
                'utf8mb4',
                'utf8mb4_unicode_ci',
            ]));

        $this->loadContainerBuilder();
        $this->loadDbiIntoContainerBuilder();

        $GLOBALS['sql_query'] = '';
        $return = Table::moveCopy('aa', 'ad', 'bb', 'ad', 'structure', true, 'db_copy', true);
        $this->assertEquals(true, $return);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `bb`.`ad`;', $GLOBALS['sql_query']);
        $this->assertStringContainsString(
            'CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost`' .
            ' SQL SECURITY DEFINER VIEW `bb`.`ad`  AS SELECT `bb`.`ac` AS `ac` FROM `bb` ;',
            $GLOBALS['sql_query'],
        );
        $this->assertStringContainsString('DROP VIEW `aa`.`ad`;', $GLOBALS['sql_query']);
    }

    /**
     * Test for getStorageEngine
     */
    public function testGetStorageEngine(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo(null, true);
        $expect = 'DBIDUMMY';
        $tblStorageEngine = $dbi->getTable($targetDb, $targetTable)->getStorageEngine();
        $this->assertEquals($expect, $tblStorageEngine);
    }

    /**
     * Test for getComment
     */
    public function testGetComment(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo(null, true);
        $expect = 'Test comment for "table1" in \'pma_test\'';
        $showComment = $dbi->getTable($targetDb, $targetTable)->getComment();
        $this->assertEquals($expect, $showComment);
    }

    /**
     * Test for getCollation
     */
    public function testGetCollation(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo(null, true);
        $expect = 'utf8mb4_general_ci';
        $tblCollation = $dbi->getTable($targetDb, $targetTable)->getCollation();
        $this->assertEquals($expect, $tblCollation);
    }

    /**
     * Test for getRowFormat
     */
    public function testGetRowFormat(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo(null, true);
        $expect = 'Redundant';
        $rowFormat = $dbi->getTable($targetDb, $targetTable)->getRowFormat();
        $this->assertEquals($expect, $rowFormat);
    }

    /**
     * Test for getAutoIncrement
     */
    public function testGetAutoIncrement(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo(null, true);
        $expect = '5';
        $autoIncrement = $dbi->getTable($targetDb, $targetTable)->getAutoIncrement();
        $this->assertEquals($expect, $autoIncrement);
    }

    /**
     * Test for getCreateOptions
     */
    public function testGetCreateOptions(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = new DatabaseInterface($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo(null, true);
        $expect = ['pack_keys' => 'DEFAULT', 'row_format' => 'REDUNDANT'];
        $createOptions = $dbi->getTable($targetDb, $targetTable)->getCreateOptions();
        $this->assertEquals($expect, $createOptions);
    }
}
