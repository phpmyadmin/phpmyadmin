<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Normalization
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Theme;
use PhpMyAdmin\Types;
use PhpMyAdmin\Util;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * tests for PhpMyAdmin\Normalization
 *
 * @package PhpMyAdmin-test
 */
class NormalizationTest extends TestCase
{
    private $normalization;

    /**
     * prepares environment for tests
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['ServerDefault'] = "PMA_server";
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
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
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
                        "id"=>["Type"=>"integer"],
                        "col1"=>["Type"=>'varchar(100)'],
                        "col2"=>["Type"=>'DATETIME']
                    ]
                )
            );
        $dbi->expects($this->any())
            ->method('getColumnNames')
            ->will($this->returnValue(["id", "col1", "col2"]));
        $map = [
          ['PMA_db', 'PMA_table1', DatabaseInterface::CONNECT_USER, []],
          [
            'PMA_db', 'PMA_table', DatabaseInterface::CONNECT_USER,
            [['Key_name'=>'PRIMARY', 'Column_name'=>'id']]
          ],
          [
              'PMA_db', 'PMA_table2', DatabaseInterface::CONNECT_USER,
              [
                ['Key_name'=>'PRIMARY', 'Column_name'=>'id'],
                ['Key_name'=>'PRIMARY', 'Column_name'=>'col1']
              ]
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

        $this->normalization = new Normalization($dbi);
    }

    /**
     * Test for getHtmlForColumnsList
     *
     * @return void
     */
    public function testGetHtmlForColumnsList()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $this->assertContains(
            '<option value="id">id [ integer ]</option>',
            $this->normalization->getHtmlForColumnsList($table, $db)
        );
        $this->assertEquals(
            '<input type="checkbox" value="col1"/>col1 [ varchar(100) ]</br>',
            $this->normalization->getHtmlForColumnsList($table, $db, 'String', 'checkbox')
        );
    }

    /**
     * Test for getHtmlForCreateNewColumn
     *
     * @return void
     */
    public function testGetHtmlForCreateNewColumn()
    {
        $GLOBALS['cfg']['BrowseMIME'] = true;
        $GLOBALS['cfg']['MaxRows'] = 25;
        $GLOBALS['col_priv'] = false;
        $db = "PMA_db";
        $table = "PMA_table";
        $numFields = 1;
        $result = $this->normalization->getHtmlForCreateNewColumn($numFields, $db, $table);
        $this->assertContains(
            '<table id="table_columns"',
            $result
        );
    }

    /**
     * Test for getHtmlFor1NFStep1
     *
     * @return void
     */
    public function testGetHtmlFor1NFStep1()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $normalizedTo = '1nf';
        $result = $this->normalization->getHtmlFor1NFStep1($db, $table, $normalizedTo);
        $this->assertContains(
            "<h3 class='center'>"
            . __('First step of normalization (1NF)') . "</h3>",
            $result
        );
        $this->assertContains(
            "<div id='mainContent'",
            $result
        );
        $this->assertContains("<legend>" . __('Step 1.'), $result);

        $this->assertContains(
            '<h4',
            $result
        );

        $this->assertContains(
            '<p',
            $result
        );

        $this->assertContains(
            "<select id='selectNonAtomicCol'",
            $result
        );

        $this->assertContains(
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
     *
     * @return void
     */
    public function testGetHtmlContentsFor1NFStep2()
    {
        $db = "PMA_db";
        $table= "PMA_table1";
        $result = $this->normalization->getHtmlContentsFor1NFStep2($db, $table);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('hasPrimaryKey', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertContains(
            '<a href="#" id="createPrimaryKey">',
            $result['subText']
        );
        $this->assertContains(
            '<a href="#" id="addNewPrimary">',
            $result['extra']
        );
        $this->assertEquals('0', $result['hasPrimaryKey']);
        $this->assertContains(__('Step 1.') . 2, $result['legendText']);
        $result1 = $this->normalization->getHtmlContentsFor1NFStep2($db, 'PMA_table');
        $this->assertEquals('1', $result1['hasPrimaryKey']);
    }

    /**
     * Test for getHtmlContentsFor1NFStep4
     *
     * @return void
     */
    public function testGetHtmlContentsFor1NFStep4()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $result = $this->normalization->getHtmlContentsFor1NFStep4($db, $table);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertContains(__('Step 1.') . 4, $result['legendText']);
        $this->assertContains(
            $this->normalization->getHtmlForColumnsList($db, $table, 'all', "checkbox"),
            $result['extra']
        );
        $this->assertContains(
            '<input type="submit" id="removeRedundant"',
            $result['extra']
        );
    }

    /**
     * Test for getHtmlContentsFor1NFStep3
     *
     * @return void
     */
    public function testGetHtmlContentsFor1NFStep3()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $result = $this->normalization->getHtmlContentsFor1NFStep3($db, $table);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertArrayHasKey('primary_key', $result);
        $this->assertContains(__('Step 1.') . 3, $result['legendText']);
        $this->assertContains(
            $this->normalization->getHtmlForColumnsList($db, $table, 'all', "checkbox"),
            $result['extra']
        );
        $this->assertContains(
            '<input type="submit" id="moveRepeatingGroup"',
            $result['extra']
        );
        $this->assertEquals(json_encode(['id']), $result['primary_key']);
    }

    /**
     * Test for getHtmlFor2NFstep1
     *
     * @return void
     */
    public function testGetHtmlFor2NFstep1()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $result = $this->normalization->getHtmlFor2NFstep1($db, $table);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertArrayHasKey('primary_key', $result);
        $this->assertContains(__('Step 2.') . 1, $result['legendText']);
        $this->assertEquals('id', $result['primary_key']);
        $result1 = $this->normalization->getHtmlFor2NFstep1($db, "PMA_table2");
        $this->assertEquals('id, col1', $result1['primary_key']);
        $this->assertContains(
            '<a href="#" id="showPossiblePd"',
            $result1['headText']
        );
        $this->assertContains(
            '<input type="checkbox" name="pd" value="id"',
            $result1['extra']
        );
    }

    /**
     * Test for getHtmlForNewTables2NF
     *
     * @return void
     */
    public function testGetHtmlForNewTables2NF()
    {
        $table= "PMA_table";
        $partialDependencies = ['col1'=>['col2']];
        $result = $this->normalization->getHtmlForNewTables2NF($partialDependencies, $table);
        $this->assertContains(
            '<input type="text" name="col1"',
            $result
        );
    }

    /**
     * Test for createNewTablesFor2NF
     *
     * @return void
     */
    public function testCreateNewTablesFor2NF()
    {
        $table= "PMA_table";
        $db = 'PMA_db';
        $tablesName = new stdClass();
        $tablesName->id = 'PMA_table';
        $tablesName->col1 = 'PMA_table1';
        $partialDependencies = ['id'=>['col2']];
        $result = $this->normalization->createNewTablesFor2NF(
            $partialDependencies,
            $tablesName,
            $table,
            $db
        );
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('queryError', $result);
        $partialDependencies = ['id'=>['col2'], 'col1'=>['col2']];
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
     *
     * @return void
     */
    public function testGetHtmlForNewTables3NF()
    {
        $tables= ["PMA_table"=>['col1']];
        $db = 'PMA_db';
        $dependencies = new stdClass();
        $dependencies->col1 = ['col2'];
        $result = $this->normalization->getHtmlForNewTables3NF($dependencies, $tables, $db);
        $this->assertEquals(
            [
                'html' => '',
                'success' => true,
                'newTables' => []
                ],
            $result
        );
        $tables= ["PMA_table"=>['col1', 'PMA_table']];
        $dependencies->PMA_table = ['col4', 'col5'];
        $result1 = $this->normalization->getHtmlForNewTables3NF($dependencies, $tables, $db);
        $this->assertInternalType('array', $result1);
        $this->assertContains(
            '<input type="text" name="PMA_table"',
            $result1['html']
        );
        $this->assertEquals(
            [
                'PMA_table' =>  [
                    'PMA_table' =>  [
                        'pk' => 'col1',
                        'nonpk' => 'col2'
                    ],
                    'table2' =>  [
                        'pk' => 'id',
                        'nonpk' => 'col4, col5'
                    ]
                ]
            ],
            $result1['newTables']
        );
    }

    /**
     * Test for createNewTablesFor3NF
     *
     * @return void
     */
    public function testCreateNewTablesFor3NF()
    {
        $db = 'PMA_db';
        $cols = new stdClass();
        $cols->pk = 'id';
        $cols->nonpk = 'col1, col2';
        $cols1 = new stdClass();
        $cols1->pk = 'col2';
        $cols1->nonpk = 'col3, col4';
        $newTables = ['PMA_table'=>['PMA_table'=>$cols, 'table1'=>$cols1]];
        $result = $this->normalization->createNewTablesFor3NF(
            $newTables,
            $db
        );
        $this->assertInternalType('array', $result);
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
        $this->assertEquals(false, $result1['queryError']);
    }

    /**
     * Test for moveRepeatingGroup
     *
     * @return void
     */
    public function testMoveRepeatingGroup()
    {
        $repeatingColumns = 'col1, col2';
        $primaryColumns = 'id,col1';
        $newTable = 'PMA_newTable';
        $newColumn = 'PMA_newCol';
        $table= "PMA_table";
        $db = 'PMA_db';
        $result = $this->normalization->moveRepeatingGroup(
            $repeatingColumns,
            $primaryColumns,
            $newTable,
            $newColumn,
            $table,
            $db
        );
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('queryError', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertInstanceOf(
            'PhpMyAdmin\Message',
            $result['message']
        );
    }

    /**
     * Test for getHtmlFor3NFstep1
     *
     * @return void
     */
    public function testGetHtmlFor3NFstep1()
    {
        $db = "PMA_db";
        $tables= ["PMA_table"];
        $result = $this->normalization->getHtmlFor3NFstep1($db, $tables);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertContains(__('Step 3.') . 1, $result['legendText']);
        $this->assertContains(
            '<form',
            $result['extra']
        );
        $this->assertContains(
            '<input type="checkbox" name="pd" value="col1"',
            $result['extra']
        );
        $result1 = $this->normalization->getHtmlFor3NFstep1($db, ["PMA_table2"]);
        $this->assertEquals(
            '',
            $result1['subText']
        );
    }

    /**
     * Test for getHtmlForNormalizeTable
     *
     * @return void
     */
    public function testgetHtmlForNormalizeTable()
    {
        $result = $this->normalization->getHtmlForNormalizeTable();
        $this->assertContains(
            '<form method="post" action="normalization.php"'
            . ' name="normalize" id="normalizeTable"',
            $result
        );
        $this->assertContains(
            '<input type="hidden" name="step1" value="1">',
            $result
        );
        $choices = [
            '1nf' => __('First step of normalization (1NF)'),
            '2nf'      => __('Second step of normalization (1NF+2NF)'),
            '3nf'  => __('Third step of normalization (1NF+2NF+3NF)')];

        $htmlTmp = Util::getRadioFields(
            'normalizeTo',
            $choices,
            '1nf',
            true
        );
        $this->assertContains($htmlTmp, $result);
    }

    /**
     * Test for findPartialDependencies
     *
     * @return void
     */
    public function testFindPartialDependencies()
    {
        $table= "PMA_table2";
        $db = 'PMA_db';
        $result = $this->normalization->findPartialDependencies($table, $db);
        $this->assertContains(
            '<div class="dependencies_box"',
            $result
        );
        $this->assertContains(__('No partial dependencies found!'), $result);
    }

    /**
     * Test for getAllCombinationPartialKeys
     *
     * @return void
     */
    public function testGetAllCombinationPartialKeys()
    {
        $class = new ReflectionClass(Normalization::class);
        $method = $class->getMethod('getAllCombinationPartialKeys');
        $method->setAccessible(true);

        $primaryKey = ['id', 'col1', 'col2'];
        $result = $method->invokeArgs($this->normalization, [$primaryKey]);
        $this->assertEquals(
            ['', 'id', 'col1', 'col1,id', 'col2', 'col2,id', 'col2,col1'],
            $result
        );
    }
}
