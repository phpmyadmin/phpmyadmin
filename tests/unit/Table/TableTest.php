<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\ListDatabase;
use PhpMyAdmin\Query\Cache;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\Table\MoveMode;
use PhpMyAdmin\Table\MoveScope;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Table\TableMover;
use PhpMyAdmin\Table\UiProperty;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

use const MYSQLI_TYPE_STRING;

#[CoversClass(Table::class)]
class TableTest extends AbstractTestCase
{
    private DatabaseInterface&MockObject $mockedDbi;

    /**
     * Configures environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * SET these to avoid undefined index error
         */
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $config->settings['MaxExactCount'] = 100;
        $config->settings['MaxExactCountViews'] = 100;
        $config->selectedServer['pmadb'] = 'pmadb';
        $config->selectedServer['table_uiprefs'] = 'pma__table_uiprefs';

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

        $fetchResultSimple = [
            [
                $sqlAnalyzeStructureTrue,
                ConnectionType::User,
                [['COLUMN_NAME' => 'COLUMN_NAME', 'DATA_TYPE' => 'DATA_TYPE']],
            ],
            [
                'SHOW COLUMNS FROM `PMA`.`PMA_BookMark`',
                ConnectionType::User,
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
                ConnectionType::User,
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
                ConnectionType::User,
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
            [
                'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, '
                    . 'ACTION_STATEMENT, EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER FROM '
                    . "information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA COLLATE utf8_bin= 'PMA' "
                    . "AND EVENT_OBJECT_TABLE COLLATE utf8_bin = 'PMA_BookMark';",
                ConnectionType::User,
                [
                    [],
                ],
            ],
            [
                'SELECT TRIGGER_SCHEMA, TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE, ACTION_TIMING, '
                    . 'ACTION_STATEMENT, EVENT_OBJECT_SCHEMA, EVENT_OBJECT_TABLE, DEFINER FROM '
                    . "information_schema.TRIGGERS WHERE EVENT_OBJECT_SCHEMA COLLATE utf8_bin= 'aa' "
                    . "AND EVENT_OBJECT_TABLE COLLATE utf8_bin = 'ad';",
                ConnectionType::User,
                [
                    [],
                ],
            ],
            [
                'SHOW COLUMNS FROM `aa`.`ad`',
                ConnectionType::User,
                [],
            ],
        ];

        $fetchResultMultidimensional = [
            [
                $getUniqueColumnsSql . ' WHERE (Non_unique = 0)',
                ['Key_name', null],
                'Column_name',
                ConnectionType::User,
                [['index1'], ['index3'], ['index5']],
            ],
        ];

        $fetchResult = [
            [
                $getUniqueColumnsSql,
                'Column_name',
                'Column_name',
                ConnectionType::User,
                ['column1', 'column3', 'column5', 'ACCESSIBLE', 'ADD', 'ALL'],
            ],
            [
                'SHOW COLUMNS FROM `PMA`.`PMA_BookMark`',
                'Field',
                'Field',
                ConnectionType::User,
                ['column1', 'column3', 'column5', 'ACCESSIBLE', 'ADD', 'ALL'],
            ],
        ];

        $fetchValue = [
            [$sqlIsViewTrue, 0, ConnectionType::User, 'PMA_BookMark'],
            [$sqlCopyData, 0, ConnectionType::User, false],
            [$sqlIsViewFalse, 0, ConnectionType::User, false],
            [$sqlIsUpdatableViewTrue, 0, ConnectionType::User, 'PMA_BookMark'],
            [$sqlIsUpdatableViewFalse, 0, ConnectionType::User, false],
            [
                "SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'aa' AND TABLE_NAME = 'ad'",
                0,
                ConnectionType::User,
                'ad',
            ],
            [
                "SELECT 1 FROM information_schema.VIEWS WHERE TABLE_SCHEMA = 'bb' AND TABLE_NAME = 'ad'",
                0,
                ConnectionType::User,
                false,
            ],
        ];

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $databaseList = self::createStub(ListDatabase::class);
        $databaseList->method('exists')->willReturn(true);
        $dbi->expects(self::any())->method('getDatabaseList')->willReturn($databaseList);

        $dbi->expects(self::any())->method('fetchResult')
            ->willReturnMap($fetchResult);

        $dbi->expects(self::any())->method('fetchResultSimple')
            ->willReturnMap($fetchResultSimple);

        $dbi->expects(self::any())->method('fetchResultMultidimensional')
            ->willReturnMap($fetchResultMultidimensional);

        $dbi->expects(self::any())->method('fetchValue')
            ->willReturnMap($fetchValue);

        $cache = new Cache();
        $dbi->expects(self::any())->method('getCache')
            ->willReturn($cache);

        $dbi->expects(self::any())->method('getColumnNames')
            ->willReturnMap([
                [
                    'PMA',
                    'PMA_BookMark',
                    ConnectionType::User,
                    ['column1', 'column3', 'column5', 'ACCESSIBLE', 'ADD', 'ALL'],
                ],
            ]);

        $databases = [];
        $databaseName = 'PMA';
        $databases[$databaseName]['SCHEMA_TABLES'] = 1;
        $databases[$databaseName]['SCHEMA_TABLE_ROWS'] = 3;
        $databases[$databaseName]['SCHEMA_DATA_LENGTH'] = 5;
        $databases[$databaseName]['SCHEMA_MAX_DATA_LENGTH'] = 10;
        $databases[$databaseName]['SCHEMA_INDEX_LENGTH'] = 10;
        $databases[$databaseName]['SCHEMA_LENGTH'] = 10;

        $dbi->expects(self::any())->method('getTablesFull')
            ->willReturn($databases);

        $dbi->expects(self::any())->method('query')
            ->willReturn($resultStub);

        $dbi->expects(self::any())->method('insertId')
            ->willReturn(10);

        $resultStub->expects(self::any())->method('fetchAssoc')
            ->willReturn([]);

        $value = ['Auto_increment' => 'Auto_increment'];
        $dbi->expects(self::any())->method('fetchSingleRow')
            ->willReturn($value);

        $resultStub->expects(self::any())->method('fetchRow')
            ->willReturn([]);

        $dbi->expects(self::any())->method('quoteString')
            ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        DatabaseInterface::$instance = $dbi;
        $this->mockedDbi = $dbi;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        DatabaseInterface::$instance = null;
    }

    /**
     * Test for constructor
     */
    public function testConstruct(): void
    {
        $table = new Table('PMA_BookMark', 'PMA', $this->mockedDbi);
        self::assertSame(
            'PMA_BookMark',
            $table->__toString(),
        );
        self::assertSame(
            'PMA_BookMark',
            $table->getName(),
        );
        self::assertSame(
            'PMA',
            $table->getDbName(),
        );
        self::assertSame(
            'PMA.PMA_BookMark',
            $table->getFullName(),
        );
    }

    /**
     * Test getName & getDbName
     */
    public function testGetName(): void
    {
        $table = new Table('table1', 'pma_test', $this->mockedDbi);
        self::assertSame(
            'table1',
            $table->getName(),
        );
        self::assertSame(
            '`table1`',
            $table->getName(true),
        );
        self::assertSame(
            'pma_test',
            $table->getDbName(),
        );
        self::assertSame(
            '`pma_test`',
            $table->getDbName(true),
        );
    }

    /**
     * Test getLastError & getLastMessage
     */
    public function testGetLastErrorAndMessage(): void
    {
        $table = new Table('table1', 'pma_test', $this->mockedDbi);
        $table->errors[] = 'error1';
        $table->errors[] = 'error2';
        $table->errors[] = 'error3';

        $table->messages[] = 'messages1';
        $table->messages[] = 'messages2';
        $table->messages[] = 'messages3';

        self::assertSame(
            'error3',
            $table->getLastError(),
        );
        self::assertSame(
            'messages3',
            $table->getLastMessage(),
        );

        $table->errors = [];
        self::assertSame(
            '',
            $table->getLastError(),
        );

        $table->messages = [];
        self::assertSame(
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
     */
    #[DataProvider('dataValidateName')]
    public function testValidateName(string $name, bool $result, bool $isBackquoted = false): void
    {
        self::assertSame(
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
        $table = new Table('', '', $this->mockedDbi);
        self::assertFalse(
            $table->isView(),
        );

        //validate that it is the same as DBI fetchResult
        $table = new Table('PMA_BookMark', 'PMA', $this->mockedDbi);
        self::assertTrue(
            $table->isView(),
        );

        $table = new Table('PMA_BookMark_2', 'PMA', $this->mockedDbi);
        self::assertFalse(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame(
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
        self::assertSame('`PMA_name` UUID PMA_attribute NULL DEFAULT uuid()', $query);

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
        self::assertSame('`PMA_name` UUID PMA_attribute NULL DEFAULT uuid()', $query);

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
        self::assertSame('`PMA_name` BOOLEAN PMA_attribute NULL INCREMENT COMMENT \'PMA_comment\' FIRST', $query);

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
        self::assertSame('`ids` INT(11) PMA_attribute NULL AUTO_INCREMENT COMMENT \'PMA_comment\' FIRST', $query);

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
        self::assertSame(
            '`ids` INT(11) PMA_attribute NULL AUTO_INCREMENT '
            . "COMMENT 'PMA_comment' FIRST, ADD PRIMARY KEY (`ids`)",
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
        self::assertSame('`id` INT(11) PMA_attribute NULL DEF COMMENT \'PMA_comment\' FIRST', $query);

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
        self::assertSame('`ids` INT(11) PMA_attribute NULL DEF COMMENT \'PMA_comment\' FIRST', $query);

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
        self::assertSame(
            '`ids` INT(11) PMA_attribute NULL DEF COMMENT \'PMA_comment\' FIRST, ADD PRIMARY KEY (`ids`)',
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
        self::assertSame(
            '`ids` INT(11) PMA_attribute AS (1) VIRTUAL NULL USER_DEFINED COMMENT \'PMA_comment\' FIRST',
            $query,
        );
    }

    public function testGenerateFieldSpecForRename(): void
    {
        // Rename a TIMESTAMP column from created_at to created_ts
        $name = 'created_ts';
        $type = 'TIMESTAMP';
        $length = '6';
        $attribute = '';
        $collation = '';
        $null = 'NO';
        $defaultType = 'USER_DEFINED';
        $defaultValue = 'current_timestamp(6)';
        $extra = '';
        $comment = '';
        $virtuality = '';
        $expression = '';
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
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        self::assertSame('`created_ts` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)', $query);

        // Rename an INT column from created_at to created_ts
        // This default values does not insert, but the column definition is valid
        // And can be renamed
        $name = 'created_ts';
        $type = 'INT';
        $length = '6';
        $attribute = '';
        $collation = '';
        $null = 'NO';
        $defaultType = 'USER_DEFINED';
        $defaultValue = 'current_timestamp(6)';
        $extra = '';
        $comment = '';
        $virtuality = '';
        $expression = '';
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
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        self::assertSame('`created_ts` INT(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6)', $query);

        // Rename an INT column from created_at to created_ts
        $name = 'created_ts';
        $type = 'INT';
        $length = '6';
        $attribute = '';
        $collation = '';
        $null = 'NO';
        $defaultType = 'USER_DEFINED';
        $defaultValue = 'UNIX_TIMESTAMP()';
        $extra = '';
        $comment = '';
        $virtuality = '';
        $expression = '';
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
            $comment,
            $virtuality,
            $expression,
            $moveTo,
        );
        self::assertSame('`created_ts` INT(6) NOT NULL DEFAULT UNIX_TIMESTAMP()', $query);
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
            RelationParameters::DATABASE => 'PMA_db',
            RelationParameters::REL_WORK => true,
            RelationParameters::RELATION => 'relation',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $object = new TableMover($this->mockedDbi, new Relation($this->mockedDbi));
        $ret = $object->duplicateInfo(
            RelationParameters::REL_WORK,
            RelationParameters::RELATION,
            $getFields,
            $whereFields,
            $newFields,
        );
        self::assertSame(-1, $ret);
    }

    /**
     * Test for isUpdatableView
     */
    public function testIsUpdatableView(): void
    {
        $table = new Table('', '', $this->mockedDbi);
        self::assertFalse(
            $table->isUpdatableView(),
        );

        //validate that it is the same as DBI fetchResult
        $table = new Table('PMA_BookMark', 'PMA', $this->mockedDbi);
        self::assertTrue(
            $table->isUpdatableView(),
        );

        $table = new Table('PMA_BookMark_2', 'PMA', $this->mockedDbi);
        self::assertFalse(
            $table->isUpdatableView(),
        );
    }

    /**
     * Test for isMerge -- when there's no ENGINE info cached
     */
    public function testIsMergeCase1(): void
    {
        $tableObj = new Table('PMA_BookMark', 'PMA', $this->mockedDbi);
        self::assertFalse(
            $tableObj->isMerge(),
        );
    }

    /**
     * Test for isMerge -- when ENGINE info is MERGE
     */
    public function testIsMergeCase2(): void
    {
        $this->mockedDbi->getCache()->cacheTableValue('PMA', 'PMA_BookMark', 'ENGINE', 'MERGE');

        $tableObj = new Table('PMA_BookMark', 'PMA', $this->mockedDbi);
        self::assertTrue(
            $tableObj->isMerge(),
        );
    }

    /**
     * Test for isMerge -- when ENGINE info is MRG_MYISAM
     */
    public function testIsMergeCase3(): void
    {
        $this->mockedDbi->getCache()->cacheTableValue('PMA', 'PMA_BookMark', 'ENGINE', 'MRG_MYISAM');

        $tableObj = new Table('PMA_BookMark', 'PMA', $this->mockedDbi);
        self::assertTrue(
            $tableObj->isMerge(),
        );
    }

    /**
     * Test for Table::isMerge -- when ENGINE info is ISDB
     */
    public function testIsMergeCase4(): void
    {
        $tableObj = new Table('PMA_BookMark', 'PMA', $this->mockedDbi);
        self::assertFalse(
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

        self::assertSame($expect, $result);
    }

    /**
     * Test for rename
     */
    public function testRename(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $table = 'PMA_BookMark';
        $db = 'PMA';

        $this->mockedDbi->expects(self::any())->method('tryQuery')->willReturn($this->createMock(DummyResult::class));

        $table = new Table($table, $db, $this->mockedDbi);

        //rename to same name
        $tableNew = 'PMA_BookMark';
        $result = $table->rename($tableNew);
        self::assertTrue($result);

        //isValidName
        //space in table name
        $tableNew = 'PMA_BookMark ';
        $result = $table->rename($tableNew);
        self::assertFalse($result);
        //empty name
        $tableNew = '';
        $result = $table->rename($tableNew);
        self::assertFalse($result);
        //dot in table name
        $tableNew = 'PMA_.BookMark';
        $result = $table->rename($tableNew);
        self::assertTrue($result);

        //message
        self::assertSame(
            'Table PMA_BookMark has been renamed to PMA_.BookMark.',
            $table->getLastMessage(),
        );

        $tableNew = 'PMA_BookMark_new';
        $dbNew = 'PMA_new';
        $result = $table->rename($tableNew, $dbNew);
        self::assertTrue($result);
        //message
        self::assertSame(
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

        $table = new Table($table, $db, $this->mockedDbi);
        $return = $table->getUniqueColumns();
        $expect = ['`PMA`.`PMA_BookMark`.`index1`', '`PMA`.`PMA_BookMark`.`index3`', '`PMA`.`PMA_BookMark`.`index5`'];
        self::assertSame($expect, $return);
    }

    /**
     * Test for getIndexedColumns
     */
    public function testGetIndexedColumns(): void
    {
        $table = 'PMA_BookMark';
        $db = 'PMA';

        $table = new Table($table, $db, $this->mockedDbi);
        $return = $table->getIndexedColumns();
        $expect = [
            '`PMA`.`PMA_BookMark`.`column1`',
            '`PMA`.`PMA_BookMark`.`column3`',
            '`PMA`.`PMA_BookMark`.`column5`',
            '`PMA`.`PMA_BookMark`.`ACCESSIBLE`',
            '`PMA`.`PMA_BookMark`.`ADD`',
            '`PMA`.`PMA_BookMark`.`ALL`',
        ];
        self::assertSame($expect, $return);
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

        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SELECT * FROM `db`.`table` LIMIT 1')
            ->willReturn($resultStub);

        $dummyFieldMetadata = FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING]);

        $dbi->expects(self::once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->willReturn([$dummyFieldMetadata]);

        DatabaseInterface::$instance = $dbi;

        $tableObj = new Table('table', 'db', $dbi);

        self::assertSame(
            $tableObj->getColumnsMeta(),
            [$dummyFieldMetadata],
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

        $tableObj = new Table('PMA_table', 'db', $this->mockedDbi);

        $sql = $this->callFunction(
            $tableObj,
            Table::class,
            'getSQLToCreateForeignKey',
            [$table, $field, $foreignDb, $foreignTable, $foreignField],
        );
        $sqlExcepted = 'ALTER TABLE `PMA_table` ADD  '
            . 'FOREIGN KEY (`PMA_field1`, `PMA_field2`) REFERENCES '
            . '`foreignDb`.`foreignTable`(`foreignField1`, `foreignField2`);';
        self::assertSame($sqlExcepted, $sql);

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
        self::assertSame($sqlExcepted, $sql);
    }

    /**
     * Test for getColumns
     */
    public function testGetColumns(): void
    {
        Context::load();
        $table = 'PMA_BookMark';
        $db = 'PMA';

        $table = new Table($table, $db, $this->mockedDbi);
        $return = $table->getColumns();
        $expect = [
            '`PMA`.`PMA_BookMark`.`column1`',
            '`PMA`.`PMA_BookMark`.`column3`',
            '`PMA`.`PMA_BookMark`.`column5`',
            '`PMA`.`PMA_BookMark`.`ACCESSIBLE`',
            '`PMA`.`PMA_BookMark`.`ADD`',
            '`PMA`.`PMA_BookMark`.`ALL`',
        ];
        self::assertSame($expect, $return);
    }

    /**
     * Test for checkIfMinRecordsExist
     */
    public function testCheckIfMinRecordsExist(): void
    {
        $oldDbi = $this->mockedDbi;

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects(self::any())
            ->method('tryQuery')
            ->willReturn($resultStub);
        $resultStub->expects(self::any())
            ->method('numRows')
            ->willReturn(0, 10, 200);
        $dbi->expects(self::any())
            ->method('fetchResult')
            ->willReturn(
                ['`one_ind`', '`sec_ind`'],
                [], // No Uniques found
            );
        $dbi->expects(self::any())
            ->method('fetchResultMultidimensional')
            ->willReturn(
                [['`one_pk`']],
                [], // No Indexed found
                [], // No Uniques found
            );

        DatabaseInterface::$instance = $dbi;

        $table = 'PMA_BookMark';
        $db = 'PMA';
        $tableObj = new Table($table, $db, $dbi);

        // Case 1 : Check if table is non-empty
        $return = $tableObj->checkIfMinRecordsExist();
        self::assertTrue($return);

        // Case 2 : Check if table contains at least 100
        $return = $tableObj->checkIfMinRecordsExist(100);
        self::assertFalse($return);

        // Case 3 : Check if table contains at least 100
        $return = $tableObj->checkIfMinRecordsExist(100);
        self::assertTrue($return);

        DatabaseInterface::$instance = $oldDbi;
    }

    /**
     * Test for Table::countRecords
     */
    public function testCountRecords(): void
    {
        $resultStub = $this->createMock(DummyResult::class);
        $resultStub->expects(self::any())
            ->method('numRows')
            ->willReturn(20);

        $dbi = clone $this->mockedDbi;
        $dbi->expects(self::any())->method('tryQuery')
            ->willReturn($resultStub);

        $table = 'PMA_BookMark';
        $db = 'PMA';
        $tableObj = new Table($table, $db, $dbi);

        self::assertSame(
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

        $table = new Table($tableName, $db, $this->mockedDbi);

        $property = UiProperty::ColumnOrder;
        $value = 'UiProp_value';
        $tableCreateTime = null;
        $table->setUiProp($property, $value, $tableCreateTime);

        //set UI prop successfully
        self::assertSame($value, $table->uiprefs[$property->value]);

        //removeUiProp
        $table->removeUiProp($property);
        $isDefineProperty = isset($table->uiprefs[$property->value]);
        self::assertFalse($isDefineProperty);

        //getUiProp after removeUiProp
        $isDefineProperty = $table->getUiProp($property);
        self::assertFalse($isDefineProperty);
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

        $getTableMap = [
            [$targetDb, $targetTable, new Table($targetTable, $targetDb, $this->mockedDbi)],
            ['aa', 'ad', new Table('ad', 'aa', $this->mockedDbi)],
        ];

        $this->mockedDbi->expects(self::any())->method('getTable')
            ->willReturnMap($getTableMap);

        $object = new TableMover($this->mockedDbi, new Relation($this->mockedDbi));

        $return = $object->moveCopy(
            $sourceDb,
            $sourceTable,
            $targetDb,
            $targetTable,
            MoveScope::Move,
            MoveMode::SingleTable,
            true,
        );

        //successfully
        self::assertTrue($return);
        $sqlQuery = 'INSERT INTO `PMA_new`.`PMA_BookMark_new`(`COLUMN_NAME1`)'
            . ' SELECT `COLUMN_NAME1` FROM '
            . '`PMA`.`PMA_BookMark`';
        self::assertStringContainsString($sqlQuery, Current::$sqlQuery);
        $sqlQuery = 'DROP VIEW `PMA`.`PMA_BookMark`';
        self::assertStringContainsString($sqlQuery, Current::$sqlQuery);

        $return = $object->moveCopy(
            $sourceDb,
            $sourceTable,
            $targetDb,
            $targetTable,
            MoveScope::DataOnly,
            MoveMode::SingleTable,
            true,
        );

        //successfully
        self::assertTrue($return);
        $sqlQuery = 'INSERT INTO `PMA_new`.`PMA_BookMark_new`(`COLUMN_NAME1`)'
            . ' SELECT `COLUMN_NAME1` FROM '
            . '`PMA`.`PMA_BookMark`';
        self::assertStringContainsString($sqlQuery, Current::$sqlQuery);
        $sqlQuery = 'DROP VIEW `PMA`.`PMA_BookMark`';
        self::assertStringNotContainsString($sqlQuery, Current::$sqlQuery);

        // Renaming DB with a view bug
        $resultStub = $this->createMock(DummyResult::class);
        $this->mockedDbi->expects(self::any())->method('tryQuery')
            ->willReturnMap([
                [
                    'SHOW CREATE TABLE `aa`.`ad`',
                    ConnectionType::User,
                    false,
                    true,
                    $resultStub,
                ],
                [
                    'SHOW TABLE STATUS FROM `aa` WHERE Name = \'ad\'',
                    ConnectionType::User,
                    false,
                    true,
                    $resultStub,
                ],
                ['USE `aa`', ConnectionType::User, false, true, $resultStub],
                [
                    'RENAME TABLE `PMA`.`PMA_BookMark` TO `PMA`.`PMA_.BookMark`;',
                    ConnectionType::User,
                    false,
                    true,
                    false,
                ],
                [
                    'RENAME TABLE `aa`.`ad` TO `bb`.`ad`;',
                    ConnectionType::User,
                    false,
                    true,
                    false,
                ],
            ]);
        $resultStub->expects(self::any())
            ->method('fetchRow')
            ->willReturn([
                'ad',
                'CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost`' .
                ' SQL SECURITY DEFINER VIEW `ad` AS select `aa`.`bb`.`ac` AS `ac` from `bb`',
                'utf8mb4',
                'utf8mb4_unicode_ci',
            ]);

        Current::$sqlQuery = '';
        $return = $object->moveCopy('aa', 'ad', 'bb', 'ad', MoveScope::Move, MoveMode::WholeDatabase, true);
        self::assertTrue($return);
        self::assertStringContainsString('DROP TABLE IF EXISTS `bb`.`ad`;', Current::$sqlQuery);
        self::assertStringContainsString(
            'CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost`' .
            ' SQL SECURITY DEFINER VIEW `bb`.`ad`  AS SELECT `bb`.`ac` AS `ac` FROM `bb` ;',
            Current::$sqlQuery,
        );
        self::assertStringContainsString('DROP VIEW `aa`.`ad`;', Current::$sqlQuery);
    }

    /**
     * Test for getStorageEngine
     */
    public function testGetStorageEngine(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = DatabaseInterface::getInstanceForTest($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo();
        $expect = 'DBIdummy';
        $tblStorageEngine = $dbi->getTable($targetDb, $targetTable)->getStorageEngine();
        self::assertSame($expect, $tblStorageEngine);
    }

    /**
     * Test for getComment
     */
    public function testGetComment(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = DatabaseInterface::getInstanceForTest($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo();
        $expect = 'Test comment for "table1" in \'pma_test\'';
        $showComment = $dbi->getTable($targetDb, $targetTable)->getComment();
        self::assertSame($expect, $showComment);
    }

    /**
     * Test for getCollation
     */
    public function testGetCollation(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = DatabaseInterface::getInstanceForTest($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo();
        $expect = 'utf8mb4_general_ci';
        $tblCollation = $dbi->getTable($targetDb, $targetTable)->getCollation();
        self::assertSame($expect, $tblCollation);
    }

    /**
     * Test for getRowFormat
     */
    public function testGetRowFormat(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = DatabaseInterface::getInstanceForTest($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo();
        $expect = 'Redundant';
        $rowFormat = $dbi->getTable($targetDb, $targetTable)->getRowFormat();
        self::assertSame($expect, $rowFormat);
    }

    /**
     * Test for getAutoIncrement
     */
    public function testGetAutoIncrement(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = DatabaseInterface::getInstanceForTest($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo();
        $expect = '5';
        $autoIncrement = $dbi->getTable($targetDb, $targetTable)->getAutoIncrement();
        self::assertSame($expect, $autoIncrement);
    }

    /**
     * Test for getCreateOptions
     */
    public function testGetCreateOptions(): void
    {
        $targetTable = 'table1';
        $targetDb = 'pma_test';
        $extension = new DbiDummy();
        $dbi = DatabaseInterface::getInstanceForTest($extension);
        $tblObject = new Table($targetTable, $targetDb, $dbi);
        $tblObject->getStatusInfo();
        $expect = ['pack_keys' => 'DEFAULT', 'row_format' => 'REDUNDANT'];
        $createOptions = $dbi->getTable($targetDb, $targetTable)->getCreateOptions();
        self::assertEquals($expect, $createOptions);
    }
}
