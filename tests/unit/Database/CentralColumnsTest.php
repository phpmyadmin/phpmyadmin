<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Database;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\CentralColumns;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Types;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

use function array_slice;

#[CoversClass(CentralColumns::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class CentralColumnsTest extends AbstractTestCase
{
    private CentralColumns $centralColumns;

    private DatabaseInterface&MockObject $dbi;

    private const COLUMN_DATA = [
        [
            'col_name' => 'id',
            'col_type' => 'integer',
            'col_length' => '0',
            'col_isNull' => '0',
            'col_extra' => 'UNSIGNED,auto_increment',
            'col_default' => '1',
            'col_collation' => '',
        ],
        [
            'col_name' => 'col1',
            'col_type' => 'varchar',
            'col_length' => '100',
            'col_isNull' => '1',
            'col_extra' => 'BINARY',
            'col_default' => '1',
            'col_collation' => '',
        ],
        [
            'col_name' => 'col2',
            'col_type' => 'DATETIME',
            'col_length' => '0',
            'col_isNull' => '1',
            'col_extra' => 'on update CURRENT_TIMESTAMP',
            'col_default' => 'CURRENT_TIMESTAMP',
            'col_collation' => '',
        ],
    ];

    private const MODIFIED_COLUMN_DATA = [
        [
            'col_name' => 'id',
            'col_type' => 'integer',
            'col_length' => '0',
            'col_isNull' => '0',
            'col_extra' => 'auto_increment',
            'col_default' => '1',
            'col_collation' => '',
            'col_attribute' => 'UNSIGNED',
        ],
        [
            'col_name' => 'col1',
            'col_type' => 'varchar',
            'col_length' => '100',
            'col_isNull' => '1',
            'col_extra' => '',
            'col_default' => '1',
            'col_collation' => '',
            'col_attribute' => 'BINARY',
        ],
        [
            'col_name' => 'col2',
            'col_type' => 'DATETIME',
            'col_length' => '0',
            'col_isNull' => '1',
            'col_extra' => '',
            'col_default' => 'CURRENT_TIMESTAMP',
            'col_collation' => '',
            'col_attribute' => 'on update CURRENT_TIMESTAMP',
        ],
    ];

    /**
     * prepares environment for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setGlobalConfig();

        $config = Config::getInstance();
        $config->selectedServer['user'] = 'pma_user';
        $config->selectedServer['DisableIS'] = true;
        $config->settings['MaxRows'] = 10;
        $config->settings['ServerDefault'] = 'PMA_server';
        $config->settings['ActionLinksMode'] = 'icons';
        $config->settings['CharEditing'] = '';
        $config->settings['LimitChars'] = 50;
        Current::$database = 'PMA_db';
        Current::$table = 'PMA_table';

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::CENTRAL_COLUMNS_WORK => true,
            RelationParameters::REL_WORK => true,
            RelationParameters::DATABASE => 'phpmyadmin',
            RelationParameters::RELATION => 'relation',
            RelationParameters::CENTRAL_COLUMNS => 'pma_central_columns',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        // mock DBI
        $this->dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dbi->types = new Types($this->dbi);
        DatabaseInterface::$instance = $this->dbi;

        // set some common expectations
        $this->dbi->expects(self::any())
            ->method('selectDb')
            ->willReturn(true);
        $this->dbi->expects(self::any())
            ->method('getColumns')
            ->willReturn([
                'id' => new Column('id', 'integer', null, false, '', null, '', '', ''),
                'col1' => new Column('col1', 'varchar(100)', null, true, '', null, '', '', ''),
                'col2' => new Column('col2', 'DATETIME', null, false, '', null, '', '', ''),
            ]);
        $this->dbi->expects(self::any())
            ->method('getColumnNames')
            ->willReturn(['id', 'col1', 'col2']);
        $this->dbi->expects(self::any())
            ->method('tryQuery')
            ->willReturn(self::createStub(DummyResult::class));
        $this->dbi->expects(self::any())
            ->method('getTables')
            ->willReturn(['PMA_table', 'PMA_table1', 'PMA_table2']);
        $this->dbi->expects(self::any())->method('quoteString')
        ->willReturnCallback(static fn (string $string): string => "'" . $string . "'");

        $this->centralColumns = new CentralColumns($this->dbi);
    }

    /**
     * Test for getParams
     */
    public function testGetParams(): void
    {
        self::assertSame(
            ['user' => 'pma_user', 'db' => 'phpmyadmin', 'table' => 'pma_central_columns'],
            $this->centralColumns->getParams(),
        );
    }

    /**
     * Test for getColumnsList
     */
    public function testGetColumnsList(): void
    {
        $this->dbi->expects(self::exactly(2))
            ->method('fetchResultSimple')
            ->willReturnOnConsecutiveCalls(
                self::COLUMN_DATA,
                array_slice(self::COLUMN_DATA, 1, 2),
            );

        self::assertSame(
            self::MODIFIED_COLUMN_DATA,
            $this->centralColumns->getColumnsList('phpmyadmin'),
        );
        self::assertSame(
            array_slice(self::MODIFIED_COLUMN_DATA, 1, 2),
            $this->centralColumns->getColumnsList('phpmyadmin', 1, 2),
        );
    }

    /**
     * Test for getCount
     */
    public function testGetCount(): void
    {
        $this->dbi->expects(self::once())
            ->method('fetchValue')
            ->with(
                'SELECT count(db_name) FROM `phpmyadmin`.`pma_central_columns` WHERE db_name = \'phpmyadmin\';',
                0,
                ConnectionType::ControlUser,
            )
            ->willReturn('3');

        self::assertSame(
            3,
            $this->centralColumns->getCount('phpmyadmin'),
        );
    }

    /**
     * Test for syncUniqueColumns
     */
    public function testSyncUniqueColumns(): void
    {
        self::assertTrue(
            $this->centralColumns->syncUniqueColumns(
                DatabaseName::from('PMA_db'),
                ['PMA_table'],
            ),
        );
    }

    /**
     * Test for makeConsistentWithList
     */
    public function testMakeConsistentWithList(): void
    {
        $this->dbi->expects(self::any())
            ->method('fetchResult')
            ->willReturn(self::COLUMN_DATA);
        $this->dbi->expects(self::any())
            ->method('fetchValue')
            ->willReturn('PMA_table=CREATE table `PMA_table` (id integer)');
        self::assertTrue(
            $this->centralColumns->makeConsistentWithList(
                'phpmyadmin',
                ['PMA_table'],
            ),
        );
    }

    /**
     * Test for updateOneColumn
     */
    public function testUpdateOneColumn(): void
    {
        self::assertTrue(
            $this->centralColumns->updateOneColumn(
                'phpmyadmin',
                '',
                '',
                '',
                '',
                '',
                false,
                '',
                '',
                '',
            ),
        );
        self::assertTrue(
            $this->centralColumns->updateOneColumn(
                'phpmyadmin',
                'col1',
                '',
                '',
                '',
                '',
                false,
                '',
                '',
                '',
            ),
        );
    }

    /**
     * Test for updateMultipleColumn
     */
    public function testUpdateMultipleColumn(): void
    {
        $params = [];
        $params['db'] = 'phpmyadmin';
        $params['orig_col_name'] = ['col1', 'col2'];
        $params['field_name'] = ['col1', 'col2'];
        $params['field_default_type'] = ['', ''];
        $params['col_extra'] = ['', ''];
        $params['field_length'] = ['', ''];
        $params['field_attribute'] = ['', ''];
        $params['field_type'] = ['', ''];
        $params['field_collation'] = ['', ''];
        self::assertTrue(
            $this->centralColumns->updateMultipleColumn($params),
        );
    }

    /**
     * Test for getHtmlForEditingPage
     */
    public function testGetHtmlForEditingPage(): void
    {
        $this->dbi->expects(self::any())
            ->method('fetchResultSimple')
            ->with(
                'SELECT * FROM `phpmyadmin`.`pma_central_columns` '
                . "WHERE db_name = 'phpmyadmin' AND col_name IN ('col1','col2');",
                ConnectionType::ControlUser,
            )
            ->willReturn(self::COLUMN_DATA);
        $result = $this->centralColumns->getHtmlForEditingPage(
            ['col1', 'col2'],
            'phpmyadmin',
        );

        self::assertStringContainsString(
            $this->callFunction(
                $this->centralColumns,
                CentralColumns::class,
                'getHtmlForEditTableRow',
                [self::MODIFIED_COLUMN_DATA[0], 0],
            ),
            $result,
        );
    }

    /**
     * Test for getListRaw
     */
    public function testGetListRaw(): void
    {
        $this->dbi->expects(self::once())
            ->method('fetchResultSimple')
            ->with(
                'SELECT * FROM `phpmyadmin`.`pma_central_columns` WHERE db_name = \'phpmyadmin\';',
                ConnectionType::ControlUser,
            )
            ->willReturn(self::COLUMN_DATA);
        self::assertSame(
            self::MODIFIED_COLUMN_DATA,
            $this->centralColumns->getListRaw(
                'phpmyadmin',
                '',
            ),
        );
    }

    /**
     * Test for getListRaw with a table name
     */
    public function testGetListRawWithTable(): void
    {
        $this->dbi->expects(self::once())
            ->method('fetchResultSimple')
            ->with(
                'SELECT * FROM `phpmyadmin`.`pma_central_columns` '
                . "WHERE db_name = 'phpmyadmin' AND col_name "
                . "NOT IN ('id','col1','col2');",
                ConnectionType::ControlUser,
            )
            ->willReturn(self::COLUMN_DATA);
        self::assertSame(
            self::MODIFIED_COLUMN_DATA,
            $this->centralColumns->getListRaw(
                'phpmyadmin',
                'table1',
            ),
        );
    }

    /**
     * Test for findExistingColumns
     */
    public function testFindExistingColNames(): void
    {
        $expectedQuery = 'SELECT * FROM `phpmyadmin`.`pma_central_columns`'
            . ' WHERE db_name = \'phpmyadmin\' AND col_name IN (\'col1\');';
        $this->dbi->expects(self::once())
            ->method('fetchResultSimple')
            ->with($expectedQuery, ConnectionType::ControlUser)
            ->willReturn(array_slice(self::COLUMN_DATA, 1, 1));
        self::assertSame(
            array_slice(self::MODIFIED_COLUMN_DATA, 1, 1),
            $this->callFunction(
                $this->centralColumns,
                CentralColumns::class,
                'findExistingColumns',
                ['phpmyadmin', ['col1']],
            ),
        );
    }

    public function testGetColumnsNotInCentralList(): void
    {
        $columns = $this->centralColumns->getColumnsNotInCentralList('PMA_db', 'PMA_table');
        self::assertSame(['id', 'col1', 'col2'], $columns);
    }
}
