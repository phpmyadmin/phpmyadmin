<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Types;
use PhpMyAdmin\Url;
use stdClass;
use function json_encode;

class NormalizationTest extends AbstractTestCase
{
    /** @var Normalization */
    private $normalization;

    /**
     * prepares environment for tests
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['ServerDefault'] = 'PMA_server';
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['CharEditing'] = '';
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['db'] = 'PMA_db';
        $GLOBALS['table'] = 'PMA_table';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $_POST['change_column'] = null;

        //$_SESSION

        //mock DBI
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->types = new Types($dbi);
        $GLOBALS['dbi'] = $dbi;
        // set expectations
        $dbi->expects($this->any())
            ->method('selectDb')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('getColumns')
            ->will(
                $this->returnValue(
                    [
                        'id' => ['Type' => 'integer'],
                        'col1' => ['Type' => 'varchar(100)'],
                        'col2' => ['Type' => 'DATETIME'],
                    ]
                )
            );
        $dbi->expects($this->any())
            ->method('getColumnNames')
            ->will($this->returnValue(['id', 'col1', 'col2']));
        $map = [
            [
                'PMA_db',
                'PMA_table1',
                DatabaseInterface::CONNECT_USER,
                [],
            ],
            [
                'PMA_db',
                'PMA_table',
                DatabaseInterface::CONNECT_USER,
                [
                    [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                ],
            ],
            [
                'PMA_db',
                'PMA_table2',
                DatabaseInterface::CONNECT_USER,
                [
                    [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'id',
                    ],
                    [
                        'Key_name' => 'PRIMARY',
                        'Column_name' => 'col1',
                    ],
                ],
            ],
        ];
        $dbi->expects($this->any())
            ->method('getTableIndexes')
            ->will($this->returnValueMap($map));
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will($this->returnValue([0]));

        $template = new Template();
        $this->normalization = new Normalization($dbi, new Relation($dbi, $template), new Transformations(), $template);
    }

    /**
     * Test for getHtmlForColumnsList
     */
    public function testGetHtmlForColumnsList(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        $this->assertStringContainsString(
            '<option value="id">id [ integer ]</option>',
            $this->normalization->getHtmlForColumnsList($table, $db)
        );
        $this->assertEquals(
            '<input type="checkbox" value="col1">col1 [ varchar(100) ]<br>',
            $this->normalization->getHtmlForColumnsList($table, $db, 'String', 'checkbox')
        );
    }

    /**
     * Test for getHtmlForCreateNewColumn
     */
    public function testGetHtmlForCreateNewColumn(): void
    {
        $GLOBALS['cfg']['BrowseMIME'] = true;
        $GLOBALS['cfg']['MaxRows'] = 25;
        $GLOBALS['col_priv'] = false;
        $db = 'PMA_db';
        $table = 'PMA_table';
        $numFields = 1;
        $result = $this->normalization->getHtmlForCreateNewColumn($numFields, $db, $table);
        $this->assertStringContainsString(
            '<table id="table_columns"',
            $result
        );
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
        $this->assertStringContainsString(
            "<h3 class='text-center'>"
            . __('First step of normalization (1NF)') . '</h3>',
            $result
        );
        $this->assertStringContainsString(
            "<div id='mainContent'",
            $result
        );
        $this->assertStringContainsString('<legend>' . __('Step 1.'), $result);

        $this->assertStringContainsString(
            '<h4',
            $result
        );

        $this->assertStringContainsString(
            '<p',
            $result
        );

        $this->assertStringContainsString(
            "<select id='selectNonAtomicCol'",
            $result
        );

        $this->assertStringContainsString(
            $this->normalization->getHtmlForColumnsList(
                $db,
                $table,
                _pgettext('string types', 'String')
            ),
            $result
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
        $this->assertIsArray($result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('hasPrimaryKey', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertStringContainsString(
            '<a href="#" id="createPrimaryKey">',
            $result['subText']
        );
        $this->assertStringContainsString(
            '<a href="#" id="addNewPrimary">',
            $result['extra']
        );
        $this->assertEquals('0', $result['hasPrimaryKey']);
        $this->assertStringContainsString(__('Step 1.') . 2, $result['legendText']);
        $result1 = $this->normalization->getHtmlContentsFor1NFStep2($db, 'PMA_table');
        $this->assertEquals('1', $result1['hasPrimaryKey']);
    }

    /**
     * Test for getHtmlContentsFor1NFStep4
     */
    public function testGetHtmlContentsFor1NFStep4(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        $result = $this->normalization->getHtmlContentsFor1NFStep4($db, $table);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertStringContainsString(__('Step 1.') . 4, $result['legendText']);
        $this->assertStringContainsString(
            $this->normalization->getHtmlForColumnsList($db, $table, 'all', 'checkbox'),
            $result['extra']
        );
        $this->assertStringContainsString(
            '<input class="btn btn-secondary" type="submit" id="removeRedundant"',
            $result['extra']
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
        $this->assertIsArray($result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertArrayHasKey('primary_key', $result);
        $this->assertStringContainsString(__('Step 1.') . 3, $result['legendText']);
        $this->assertStringContainsString(
            $this->normalization->getHtmlForColumnsList($db, $table, 'all', 'checkbox'),
            $result['extra']
        );
        $this->assertStringContainsString(
            '<input class="btn btn-secondary" type="submit" id="moveRepeatingGroup"',
            $result['extra']
        );
        $this->assertEquals(json_encode(['id']), $result['primary_key']);
    }

    /**
     * Test for getHtmlFor2NFstep1
     */
    public function testGetHtmlFor2NFstep1(): void
    {
        $db = 'PMA_db';
        $table = 'PMA_table';
        $result = $this->normalization->getHtmlFor2NFstep1($db, $table);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertArrayHasKey('primary_key', $result);
        $this->assertStringContainsString(__('Step 2.') . 1, $result['legendText']);
        $this->assertEquals('id', $result['primary_key']);
        $result1 = $this->normalization->getHtmlFor2NFstep1($db, 'PMA_table2');
        $this->assertEquals('id, col1', $result1['primary_key']);
        $this->assertStringContainsString(
            '<a href="#" id="showPossiblePd"',
            $result1['headText']
        );
        $this->assertStringContainsString(
            '<input type="checkbox" name="pd" value="id"',
            $result1['extra']
        );
    }

    /**
     * Test for getHtmlForNewTables2NF
     */
    public function testGetHtmlForNewTables2NF(): void
    {
        $table = 'PMA_table';
        $partialDependencies = ['col1' => ['col2']];
        $result = $this->normalization->getHtmlForNewTables2NF($partialDependencies, $table);
        $this->assertStringContainsString(
            '<input type="text" name="col1"',
            $result
        );
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
        $result = $this->normalization->createNewTablesFor2NF(
            $partialDependencies,
            $tablesName,
            $table,
            $db
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('queryError', $result);
        $partialDependencies = [
            'id' => ['col2'],
            'col1' => ['col2'],
        ];
        $result1 = $this->normalization->createNewTablesFor2NF(
            $partialDependencies,
            $tablesName,
            $table,
            $db
        );
        $this->assertArrayHasKey('extra', $result1);
        $this->assertEquals(__('End of step'), $result1['legendText']);
        $this->assertEquals('', $result1['extra']);
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
        $this->assertEquals(
            [
                'html' => '',
                'success' => true,
                'newTables' => [],
            ],
            $result
        );
        $tables = [
            'PMA_table' => [
                'col1',
                'PMA_table',
            ],
        ];
        $dependencies->PMA_table = [
            'col4',
            'col5',
        ];
        $result1 = $this->normalization->getHtmlForNewTables3NF($dependencies, $tables, $db);
        $this->assertIsArray($result1);
        $this->assertStringContainsString(
            '<input type="text" name="PMA_table"',
            $result1['html']
        );
        $this->assertEquals(
            [
                'PMA_table' =>  [
                    'PMA_table' =>  [
                        'pk' => 'col1',
                        'nonpk' => 'col2',
                    ],
                    'table2' =>  [
                        'pk' => 'id',
                        'nonpk' => 'col4, col5',
                    ],
                ],
            ],
            $result1['newTables']
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
                'PMA_table' => [
                    'pk' => 'id',
                    'nonpk' => 'col1, col2',
                ],
                'table1' => [
                    'pk' => 'col2',
                    'nonpk' => 'col3, col4',
                ],
            ],
        ];
        $result = $this->normalization->createNewTablesFor3NF(
            $newTables,
            $db
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('queryError', $result);
        $newTables1 = [];
        $result1 = $this->normalization->createNewTablesFor3NF(
            $newTables1,
            $db
        );
        $this->assertArrayHasKey('queryError', $result1);
        $this->assertEquals(__('End of step'), $result1['legendText']);
        $this->assertFalse($result1['queryError']);
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
            $db
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('queryError', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertInstanceOf(
            Message::class,
            $result['message']
        );
    }

    /**
     * Test for getHtmlFor3NFstep1
     */
    public function testGetHtmlFor3NFstep1(): void
    {
        $db = 'PMA_db';
        $tables = ['PMA_table'];
        $result = $this->normalization->getHtmlFor3NFstep1($db, $tables);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertStringContainsString(__('Step 3.') . 1, $result['legendText']);
        $this->assertStringContainsString(
            '<form',
            $result['extra']
        );
        $this->assertStringContainsString(
            '<input type="checkbox" name="pd" value="col1"',
            $result['extra']
        );
        $result1 = $this->normalization->getHtmlFor3NFstep1($db, ['PMA_table2']);
        $this->assertEquals(
            '',
            $result1['subText']
        );
    }

    /**
     * Test for getHtmlForNormalizeTable
     */
    public function testgetHtmlForNormalizeTable(): void
    {
        $result = $this->normalization->getHtmlForNormalizeTable();
        $this->assertStringContainsString(
            '<form method="post" action="' . Url::getFromRoute('/normalization')
            . '" name="normalize" id="normalizeTable"',
            $result
        );
        $this->assertStringContainsString(
            '<input type="hidden" name="step1" value="1">',
            $result
        );

        $this->assertStringContainsString('type="radio" name="normalizeTo"', $result);
        $this->assertStringContainsString('id="normalizeToRadio1" value="1nf" checked>', $result);
        $this->assertStringContainsString('id="normalizeToRadio2" value="2nf">', $result);
        $this->assertStringContainsString('id="normalizeToRadio3" value="3nf">', $result);
    }

    /**
     * Test for findPartialDependencies
     */
    public function testFindPartialDependencies(): void
    {
        $table = 'PMA_table2';
        $db = 'PMA_db';
        $result = $this->normalization->findPartialDependencies($table, $db);
        $this->assertStringContainsString(
            '<div class="dependencies_box"',
            $result
        );
        $this->assertStringContainsString(__('No partial dependencies found!'), $result);
    }

    /**
     * Test for getAllCombinationPartialKeys
     */
    public function testGetAllCombinationPartialKeys(): void
    {
        $primaryKey = [
            'id',
            'col1',
            'col2',
        ];
        $result = $this->callFunction(
            $this->normalization,
            Normalization::class,
            'getAllCombinationPartialKeys',
            [$primaryKey]
        );

        $this->assertEquals(
            [
                '',
                'id',
                'col1',
                'col1,id',
                'col2',
                'col2,id',
                'col2,col1',
            ],
            $result
        );
    }
}
