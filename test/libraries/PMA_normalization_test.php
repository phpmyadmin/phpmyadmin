<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for normalization.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
$GLOBALS['server'] = 1;
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/Message.class.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Types.class.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/normalization.lib.php';
require_once 'libraries/Index.class.php';

/**
 * tests for normalization.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_Normalization_Test extends PHPUnit_Framework_TestCase
{
    /**
     * prepares environment for tests
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['PMA_Types'] = new PMA_Types_MySQL();
        $GLOBALS['cfg']['ServerDefault'] = "PMA_server";
        $GLOBALS['pmaThemeImage'] = 'image';
        $GLOBALS['cfg']['ShowHint'] = true;
        $GLOBALS['cfg']['CharEditing'] = '';
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['db'] = 'PMA_db';
        $GLOBALS['table'] = 'PMA_table';
        $GLOBALS['server'] = 1;

        //$_SESSION
        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();

        //mock DBI
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['dbi'] = $dbi;
        // set expectations
        $dbi->expects($this->any())
            ->method('selectDb')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('getColumns')
            ->will(
                $this->returnValue(
                    array(
                        "id"=>array("Type"=>"integer"),
                        "col1"=>array("Type"=>'varchar(100)'),
                        "col2"=>array("Type"=>'DATETIME')
                    )
                )
            );
        $dbi->expects($this->any())
            ->method('getColumnNames')
            ->will($this->returnValue(array("id", "col1", "col2")));
        $map = array(
          array('PMA_db', 'PMA_table1', null, array()),
          array(
            'PMA_db', 'PMA_table', null,
            array(array('Key_name'=>'PRIMARY', 'Column_name'=>'id'))
          ),
          array(
              'PMA_db', 'PMA_table2', null,
              array(
                array('Key_name'=>'PRIMARY', 'Column_name'=>'id'),
                array('Key_name'=>'PRIMARY', 'Column_name'=>'col1')
              )
          ),
        );
        $dbi->expects($this->any())
            ->method('getTableIndexes')
            ->will($this->returnValueMap($map));
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will($this->returnValue(array(0)));

    }

    /**
     * Test for PMA_getHtmlForColumnsList
     *
     * @return void
     */
    public function testPMAGetHtmlForColumnsList()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $this->assertContains(
            '<option value="id">id [ integer ]</option>',
            PMA_getHtmlForColumnsList($table, $db)
        );
        $this->assertEquals(
            '<input type="checkbox" value="col1"/>col1 [ varchar(100) ]</br>',
            PMA_getHtmlForColumnsList($table, $db, 'String', 'checkbox')
        );
    }

    /**
     * Test for PMA_getHtmlForCreateNewColumn
     *
     * @return void
     */
    public function testPMAGetHtmlForCreateNewColumn()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $num_fields = 1;
        $result = PMA_getHtmlForCreateNewColumn($num_fields, $db, $table);
        $this->assertContains(
            '<table id="table_columns"',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlFor1NFStep1
     *
     * @return void
     */
    public function testPMAGetHtmlFor1NFStep1()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $normalizedTo = '1nf';
        $result = PMA_getHtmlFor1NFStep1($db, $table, $normalizedTo);
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
            PMA_getHtmlForColumnsList(
                $db, $table, _pgettext('string types', 'String')
            ), $result
        );

    }

    /**
     * Test for PMA_getHtmlContentsFor1NFStep2
     *
     * @return void
     */
    public function testPMAGetHtmlContentsFor1NFStep2()
    {
        $db = "PMA_db";
        $table= "PMA_table1";
        $result = PMA_getHtmlContentsFor1NFStep2($db, $table);
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
        $result1 = PMA_getHtmlContentsFor1NFStep2($db, 'PMA_table');
        $this->assertEquals('1', $result1['hasPrimaryKey']);
    }

    /**
     * Test for PMA_getHtmlContentsFor1NFStep4
     *
     * @return void
     */
    public function testPMAGetHtmlContentsFor1NFStep4()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $result = PMA_getHtmlContentsFor1NFStep4($db, $table);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertContains(__('Step 1.') . 4, $result['legendText']);
        $this->assertContains(
            PMA_getHtmlForColumnsList($db, $table, 'all', "checkbox"),
            $result['extra']
        );
        $this->assertContains(
            '<input type="submit" id="removeRedundant"',
            $result['extra']
        );
    }

    /**
     * Test for PMA_getHtmlContentsFor1NFStep3
     *
     * @return void
     */
    public function testPMAGetHtmlContentsFor1NFStep3()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $result = PMA_getHtmlContentsFor1NFStep3($db, $table);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertArrayHasKey('primary_key', $result);
        $this->assertContains(__('Step 1.') . 3, $result['legendText']);
        $this->assertContains(
            PMA_getHtmlForColumnsList($db, $table, 'all', "checkbox"),
            $result['extra']
        );
        $this->assertContains(
            '<input type="submit" id="moveRepeatingGroup"',
            $result['extra']
        );
        $this->assertEquals(json_encode(array('id')), $result['primary_key']);
    }

    /**
     * Test for PMA_getHtmlFor2NFstep1
     *
     * @return void
     */
    public function testPMAGetHtmlFor2NFstep1()
    {
        $db = "PMA_db";
        $table= "PMA_table";
        $result = PMA_getHtmlFor2NFstep1($db, $table);
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('subText', $result);
        $this->assertArrayHasKey('extra', $result);
        $this->assertArrayHasKey('primary_key', $result);
        $this->assertContains(__('Step 2.') . 1, $result['legendText']);
        $this->assertEquals('id', $result['primary_key']);
        $result1 = PMA_getHtmlFor2NFstep1($db, "PMA_table2");
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
     * Test for PMA_getHtmlForNewTables2NF
     *
     * @return void
     */
    public function testPMAGetHtmlForNewTables2NF()
    {
        $table= "PMA_table";
        $partialDependencies = array('col1'=>array('col2'));
        $result = PMA_getHtmlForNewTables2NF($partialDependencies, $table);
        $this->assertContains(
            '<input type="text" name="col1"',
            $result
        );
    }

    /**
     * Test for PMA_createNewTablesFor2NF
     *
     * @return void
     */
    public function testPMACreateNewTablesFor2NF()
    {
        $table= "PMA_table";
        $db = 'PMA_db';
        $tablesName = new stdClass();
        $tablesName->id = 'PMA_table';
        $tablesName->col1 = 'PMA_table1';
        $partialDependencies = array('id'=>array('col2'));
        $result = PMA_createNewTablesFor2NF(
            $partialDependencies, $tablesName, $table, $db
        );
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('queryError', $result);
        $partialDependencies = array('id'=>array('col2'), 'col1'=>array('col2'));
        $result1 = PMA_createNewTablesFor2NF(
            $partialDependencies, $tablesName, $table, $db
        );
        $this->assertArrayHasKey('extra', $result1);
        $this->assertEquals(__('End of step'), $result1['legendText']);
        $this->assertEquals('', $result1['extra']);
    }

    /**
     * Test for PMA_getHtmlForNewTables3NF
     *
     * @return void
     */
    public function testPMAGetHtmlForNewTables3NF()
    {
        $tables= array("PMA_table"=>array('col1'));
        $db = 'PMA_db';
        $dependencies = new stdClass();
        $dependencies->col1 = array('col2');
        $result = PMA_getHtmlForNewTables3NF($dependencies, $tables, $db);
        $this->assertEquals(
            array(
                'html' => '',
                'newTables' => array()
                ), $result
        );
        $tables= array("PMA_table"=>array('col1', 'PMA_table'));
        $dependencies->PMA_table = array('col4', 'col5');
        $result1 = PMA_getHtmlForNewTables3NF($dependencies, $tables, $db);
        $this->assertInternalType('array', $result1);
        $this->assertContains(
            '<input type="text" name="PMA_table"',
            $result1['html']
        );
        $this->assertEquals(
            array(
                'PMA_table' => array (
                    'PMA_table' => array (
                        'pk' => 'col1',
                        'nonpk' => 'col2'
                    ),
                    'table2' => array (
                        'pk' => 'id',
                        'nonpk' => 'col4, col5'
                    )
                )
            ), $result1['newTables']
        );
    }

    /**
     * Test for PMA_createNewTablesFor3NF
     *
     * @return void
     */
    public function testPMACreateNewTablesFor3NF()
    {
        $db = 'PMA_db';
        $cols = new stdClass();
        $cols->pk = 'id';
        $cols->nonpk = 'col1, col2';
        $cols1 = new stdClass();
        $cols1->pk = 'col2';
        $cols1->nonpk = 'col3, col4';
        $newTables = array('PMA_table'=>array('PMA_table'=>$cols, 'table1'=>$cols1));
        $result = PMA_createNewTablesFor3NF(
            $newTables, $db
        );
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('legendText', $result);
        $this->assertArrayHasKey('headText', $result);
        $this->assertArrayHasKey('queryError', $result);
        $newTables1 = array();
        $result1 = PMA_createNewTablesFor3NF(
            $newTables1, $db
        );
        $this->assertArrayHasKey('queryError', $result1);
        $this->assertEquals(__('End of step'), $result1['legendText']);
        $this->assertEquals(false, $result1['queryError']);
    }

    /**
     * Test for PMA_moveRepeatingGroup
     *
     * @return void
     */
    public function testPMAMoveRepeatingGroup()
    {
        $repeatingColumns = 'col1, col2';
        $primary_columns = 'id,col1';
        $newTable = 'PMA_newTable';
        $newColumn = 'PMA_newCol';
        $table= "PMA_table";
        $db = 'PMA_db';
        $result = PMA_moveRepeatingGroup(
            $repeatingColumns, $primary_columns, $newTable, $newColumn, $table, $db
        );
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('queryError', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertInstanceOf(
            'PMA_Message', $result['message']
        );
    }

    /**
     * Test for PMA_getHtmlFor3NFstep1
     *
     * @return void
     */
    public function testPMAGetHtmlFor3NFstep1()
    {
        $db = "PMA_db";
        $tables= array("PMA_table");
        $result = PMA_getHtmlFor3NFstep1($db, $tables);
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
        $result1 = PMA_getHtmlFor3NFstep1($db, array("PMA_table2"));
        $this->assertEquals(
            '', $result1['subText']
        );
    }

    /**
     * Test for PMA_getHtmlForNormalizetable
     *
     * @return void
     */
    public function testPMAGetHtmlForNormalizetable()
    {
        $result = PMA_getHtmlForNormalizetable();
        $this->assertContains(
            '<form method="post" action="normalization.php"'
            . ' name="normalize" id="normalizeTable"',
            $result
        );
        $this->assertContains(
            '<input type="hidden" name="step1" value="1">', $result
        );
        $choices = array(
            '1nf' => __('First step of normalization (1NF)'),
            '2nf'      => __('Second step of normalization (1NF+2NF)'),
            '3nf'  => __('Third step of normalization (1NF+2NF+3NF)'));

        $html_tmp = PMA_Util::getRadioFields(
            'normalizeTo', $choices, '1nf', true
        );
        $this->assertContains($html_tmp, $result);
    }

    /**
     * Test for PMA_findPartialDependencies
     *
     * @return void
     */
    public function testPMAFindPartialDependencies()
    {
        $table= "PMA_table2";
        $db = 'PMA_db';
        $result = PMA_findPartialDependencies($table, $db);
        $this->assertContains(
            '<div class="dependencies_box"',
            $result
        );
        $this->assertContains(__('No partial dependencies found!'), $result);
    }

    /**
     * Test for PMA_getAllCombinationPartialKeys
     *
     * @return void
     */
    public function testPMAGetAllCombinationPartialKeys()
    {
        $primaryKey = array('id', 'col1', 'col2');
        $result = PMA_getAllCombinationPartialKeys($primaryKey);
        $this->assertEquals(
            array('', 'id', 'col1', 'col1,id', 'col2', 'col2,id', 'col2,col1'),
            $result
        );
    }
}
