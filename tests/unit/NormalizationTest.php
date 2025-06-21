<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Types;
use PhpMyAdmin\UserPrivileges;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

use function __;
use function _pgettext;
use function json_encode;

#[CoversClass(Normalization::class)]
class NormalizationTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private Normalization $normalization;

    /**
     * prepares environment for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $config = Config::getInstance();
        $config->settings['LimitChars'] = 50;
        $config->settings['ServerDefault'] = 'PMA_server';
        $config->settings['ShowHint'] = true;
        $config->settings['CharEditing'] = '';
        $config->settings['ActionLinksMode'] = 'icons';
        Current::$database = 'PMA_db';
        Current::$table = 'PMA_table';
        $config->selectedServer['DisableIS'] = false;
        $_POST['change_column'] = null;

        //$_SESSION

        //mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->types = new Types($dbi);
        DatabaseInterface::$instance = $dbi;
        // set expectations
        $dbi->expects(self::any())
            ->method('selectDb')
            ->willReturn(true);
        $dbi->expects(self::any())
            ->method('getColumns')
            ->willReturn([
                'id' => new Column('id', 'integer', null, false, '', null, '', '', ''),
                'col1' => new Column('col1', 'varchar(100)', null, false, '', null, '', '', ''),
                'col2' => new Column('col2', 'DATETIME', null, false, '', null, '', '', ''),
            ]);
        $dbi->expects(self::any())
            ->method('getColumnNames')
            ->willReturn(['id', 'col1', 'col2']);
        $map = [
            ['PMA_db', 'PMA_table1', ConnectionType::User, []],
            ['PMA_db', 'PMA_table', ConnectionType::User, [['Key_name' => 'PRIMARY', 'Column_name' => 'id']]],
            [
                'PMA_db',
                'PMA_table2',
                ConnectionType::User,
                [['Key_name' => 'PRIMARY','Column_name' => 'id'], ['Key_name' => 'PRIMARY','Column_name' => 'col1']],
            ],
        ];
        $dbi->expects(self::any())
            ->method('getTableIndexes')
            ->willReturnMap($map);
        $dbi->expects(self::any())
            ->method('tryQuery')
            ->willReturn(self::createStub(DummyResult::class));
        $dbi->expects(self::any())
            ->method('fetchSingleRow')
            ->willReturn(['`id`_cnt' => 0, '`col1`_cnt' => 0, '`col2`_cnt' => 0]);

        $relation = new Relation($dbi);
        $this->normalization = new Normalization($dbi, $relation, new Transformations($dbi, $relation), new Template());
    }

    /**
     * Test for getHtmlForColumnsList
     */
    public function testGetHtmlForColumnsList(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        self::assertStringContainsString(
            '<option value="id">id [ integer ]</option>',
            $this->normalization->getHtmlForColumnsList($table, $db),
        );
        self::assertSame(
            '<input type="checkbox" value="col1">col1 [ varchar(100) ]<br>',
            $this->normalization->getHtmlForColumnsList($table, $db, 'String', 'checkbox'),
        );
    }

    /**
     * Test for getHtmlForCreateNewColumn
     */
    public function testGetHtmlForCreateNewColumn(): void
    {
        $config = Config::getInstance();
        $config->settings['BrowseMIME'] = true;
        $config->settings['MaxRows'] = 25;
        $userPrivileges = new UserPrivileges();
        $config->selectedServer['DisableIS'] = false;
        DatabaseInterface::$instance = $this->dbi;
        $db = 'testdb';
        $table = 'mytable';
        $numFields = 1;
        $relation = new Relation($this->dbi);
        $normalization = new Normalization(
            $this->dbi,
            $relation,
            new Transformations($this->dbi, $relation),
            new Template(),
        );
        $result = $normalization->getHtmlForCreateNewColumn($userPrivileges, $numFields, $db, $table);
        self::assertStringContainsString('<table id="table_columns"', $result);
    }

    /**
     * Test for getHtmlFor1NFStep1
     */
    public function testGetHtmlFor1NFStep1(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        $normalizedTo = '1nf';
        $result = $this->normalization->getHtmlFor1NFStep1($db, $table, $normalizedTo);
        self::assertStringContainsString('<h3>' . __('First step of normalization (1NF)') . '</h3>', $result);
        self::assertStringContainsString('<div class="card" id="mainContent"', $result);
        self::assertStringContainsString('<div class="card-header">' . __('Step 1.'), $result);

        self::assertStringContainsString('<h4', $result);

        self::assertStringContainsString('<p', $result);

        self::assertStringContainsString("<select id='selectNonAtomicCol'", $result);

        self::assertStringContainsString(
            $this->normalization->getHtmlForColumnsList(
                $db,
                $table,
                _pgettext('string types', 'String'),
            ),
            $result,
        );
    }

    /**
     * Test for getHtmlContentsFor1NFStep2
     */
    public function testGetHtmlContentsFor1NFStep2(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table1';
        $result = $this->normalization->getHtmlContentsFor1NFStep2($db, $table);
        self::assertArrayHasKey('legendText', $result);
        self::assertArrayHasKey('headText', $result);
        self::assertArrayHasKey('subText', $result);
        self::assertArrayHasKey('hasPrimaryKey', $result);
        self::assertArrayHasKey('extra', $result);
        self::assertStringContainsString('<a href="#" id="createPrimaryKey">', $result['subText']);
        self::assertStringContainsString('<a href="#" id="addNewPrimary">', $result['extra']);
        self::assertSame('0', $result['hasPrimaryKey']);
        self::assertStringContainsString(__('Step 1.') . 2, $result['legendText']);
        $result1 = $this->normalization->getHtmlContentsFor1NFStep2($db, 'PMA_table');
        self::assertSame('1', $result1['hasPrimaryKey']);
    }

    /**
     * Test for getHtmlContentsFor1NFStep4
     */
    public function testGetHtmlContentsFor1NFStep4(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        $result = $this->normalization->getHtmlContentsFor1NFStep4($db, $table);
        self::assertArrayHasKey('legendText', $result);
        self::assertArrayHasKey('headText', $result);
        self::assertArrayHasKey('subText', $result);
        self::assertArrayHasKey('extra', $result);
        self::assertStringContainsString(__('Step 1.') . 4, $result['legendText']);
        self::assertStringContainsString(
            $this->normalization->getHtmlForColumnsList($db, $table, 'all', 'checkbox'),
            $result['extra'],
        );
        self::assertStringContainsString(
            '<input class="btn btn-secondary" type="submit" id="removeRedundant"',
            $result['extra'],
        );
    }

    /**
     * Test for getHtmlContentsFor1NFStep3
     */
    public function testGetHtmlContentsFor1NFStep3(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        $result = $this->normalization->getHtmlContentsFor1NFStep3($db, $table);
        self::assertArrayHasKey('legendText', $result);
        self::assertArrayHasKey('headText', $result);
        self::assertArrayHasKey('subText', $result);
        self::assertArrayHasKey('extra', $result);
        self::assertArrayHasKey('primary_key', $result);
        self::assertStringContainsString(__('Step 1.') . 3, $result['legendText']);
        self::assertStringContainsString(
            $this->normalization->getHtmlForColumnsList($db, $table, 'all', 'checkbox'),
            $result['extra'],
        );
        self::assertStringContainsString(
            '<input class="btn btn-secondary" type="submit" id="moveRepeatingGroup"',
            $result['extra'],
        );
        self::assertSame(json_encode(['id']), $result['primary_key']);
    }

    /**
     * Test for getHtmlFor2NFstep1
     */
    public function testGetHtmlFor2NFstep1(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        $result = $this->normalization->getHtmlFor2NFstep1($db, $table);
        self::assertArrayHasKey('legendText', $result);
        self::assertArrayHasKey('headText', $result);
        self::assertArrayHasKey('subText', $result);
        self::assertArrayHasKey('extra', $result);
        self::assertArrayHasKey('primary_key', $result);
        self::assertStringContainsString(__('Step 2.') . 1, $result['legendText']);
        self::assertSame('id', $result['primary_key']);
        $result1 = $this->normalization->getHtmlFor2NFstep1($db, 'PMA_table2');
        self::assertSame('id, col1', $result1['primary_key']);
        self::assertStringContainsString('<a href="#" id="showPossiblePd"', $result1['headText']);
        self::assertStringContainsString('<input type="checkbox" name="pd" value="id"', $result1['extra']);
    }

    /**
     * Test for getHtmlForNewTables2NF
     */
    public function testGetHtmlForNewTables2NF(): void
    {
        $table = 'PMA_table';
        $partialDependencies = ['col1' => ['col2']];
        $result = $this->normalization->getHtmlForNewTables2NF($partialDependencies, $table);
        self::assertStringContainsString('<input type="text" name="col1"', $result);
    }

    /**
     * Test for createNewTablesFor2NF
     */
    public function testCreateNewTablesFor2NF(): void
    {
        $table = 'PMA_table';
        $db = 'PMA_db';
        $tablesName = new stdClass();
        $tablesName->id = 'PMA_table';
        $tablesName->col1 = 'PMA_table1';
        $partialDependencies = ['id' => ['col2']];
        $result = $this->normalization->createNewTablesFor2NF($partialDependencies, $tablesName, $table, $db);
        self::assertArrayHasKey('legendText', $result);
        self::assertArrayHasKey('headText', $result);
        self::assertArrayHasKey('queryError', $result);
        $partialDependencies = ['id' => ['col2'], 'col1' => ['col2']];
        $result1 = $this->normalization->createNewTablesFor2NF($partialDependencies, $tablesName, $table, $db);
        self::assertArrayHasKey('extra', $result1);
        self::assertSame(__('End of step'), $result1['legendText']);
        self::assertSame('', $result1['extra']);
    }

    /**
     * Test for getHtmlForNewTables3NF
     */
    public function testGetHtmlForNewTables3NF(): void
    {
        $tables = ['PMA_table' => ['col1']];
        $db = 'PMA_db';
        $dependencies = new stdClass();
        $dependencies->col1 = ['col2'];
        $result = $this->normalization->getHtmlForNewTables3NF($dependencies, $tables, $db);
        self::assertEquals(
            ['html' => '', 'success' => true, 'newTables' => []],
            $result,
        );
        $tables = ['PMA_table' => ['col1', 'PMA_table']];
        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        $dependencies->PMA_table = ['col4', 'col5'];
        $result1 = $this->normalization->getHtmlForNewTables3NF($dependencies, $tables, $db);
        self::assertStringContainsString('<input type="text" name="PMA_table"', $result1['html']);
        self::assertSame(
            [
                'PMA_table' => [
                    'PMA_table' => ['pk' => 'col1', 'nonpk' => 'col2'],
                    'table2' => ['pk' => 'id', 'nonpk' => 'col4, col5'],
                ],
            ],
            $result1['newTables'],
        );
    }

    /**
     * Test for createNewTablesFor3NF
     */
    public function testCreateNewTablesFor3NF(): void
    {
        $db = 'PMA_db';
        $newTables = [
            'PMA_table' => [
                'PMA_table' => ['pk' => 'id', 'nonpk' => 'col1, col2'],
                'table1' => ['pk' => 'col2', 'nonpk' => 'col3, col4'],
            ],
        ];
        $result = $this->normalization->createNewTablesFor3NF($newTables, $db);
        self::assertArrayHasKey('legendText', $result);
        self::assertArrayHasKey('headText', $result);
        self::assertArrayHasKey('queryError', $result);
        $newTables1 = [];
        $result1 = $this->normalization->createNewTablesFor3NF($newTables1, $db);
        self::assertArrayHasKey('queryError', $result1);
        self::assertSame(__('End of step'), $result1['legendText']);
        self::assertFalse($result1['queryError']);
    }

    /**
     * Test for moveRepeatingGroup
     */
    public function testMoveRepeatingGroup(): void
    {
        $repeatingColumns = 'col1, col2';
        $primaryColumns = 'id,col1';
        $newTable = 'PMA_newTable';
        $newColumn = 'PMA_newCol';
        $table = 'PMA_table';
        $db = 'PMA_db';
        $result = $this->normalization->moveRepeatingGroup(
            $repeatingColumns,
            $primaryColumns,
            $newTable,
            $newColumn,
            $table,
            $db,
        );
        self::assertArrayHasKey('queryError', $result);
        self::assertArrayHasKey('message', $result);
        self::assertInstanceOf(Message::class, $result['message']);
    }

    /**
     * Test for getHtmlFor3NFstep1
     */
    public function testGetHtmlFor3NFstep1(): void
    {
        $db = 'PMA_db';
        $tables = ['PMA_table'];
        $result = $this->normalization->getHtmlFor3NFstep1($db, $tables);
        self::assertArrayHasKey('legendText', $result);
        self::assertArrayHasKey('headText', $result);
        self::assertArrayHasKey('subText', $result);
        self::assertArrayHasKey('extra', $result);
        self::assertStringContainsString(__('Step 3.') . 1, $result['legendText']);
        self::assertStringContainsString('<form', $result['extra']);
        self::assertStringContainsString('<input type="checkbox" name="pd" value="col1"', $result['extra']);
        $result1 = $this->normalization->getHtmlFor3NFstep1($db, ['PMA_table2']);
        self::assertSame('', $result1['subText']);
    }

    /**
     * Test for findPartialDependencies
     */
    public function testFindPartialDependencies(): void
    {
        $table = 'PMA_table2';
        $db = 'PMA_db';
        $result = $this->normalization->findPartialDependencies($table, $db);
        self::assertStringContainsString('<div class="dependencies_box"', $result);
        self::assertStringContainsString(__('No partial dependencies found!'), $result);
    }

    /**
     * Test for getAllCombinationPartialKeys
     */
    public function testGetAllCombinationPartialKeys(): void
    {
        $primaryKey = ['id', 'col1', 'col2'];
        $result = $this->callFunction(
            $this->normalization,
            Normalization::class,
            'getAllCombinationPartialKeys',
            [$primaryKey],
        );

        self::assertSame(
            ['', 'id', 'col1', 'col1,id', 'col2', 'col2,id', 'col2,col1'],
            $result,
        );
    }
}
