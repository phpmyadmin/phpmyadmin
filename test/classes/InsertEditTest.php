<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for PhpMyAdmin\InsertEdit
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use stdClass;

/**
 * Tests for PhpMyAdmin\InsertEdit
 *
 * @package PhpMyAdmin-test
 * @group medium
 */
class InsertEditTest extends TestCase
{
    private $insertEdit;

    /**
     * Setup for test cases
     *
     * @return void
     */
    public function setUp()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['LongtextDoubleTextarea'] = false;
        $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = true;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;
        $GLOBALS['cfg']['ProtectBinary'] = 'blob';
        $GLOBALS['cfg']['MaxSizeForInputField'] = 10;
        $GLOBALS['cfg']['MinSizeForInputField'] = 2;
        $GLOBALS['cfg']['TextareaRows'] = 5;
        $GLOBALS['cfg']['TextareaCols'] = 4;
        $GLOBALS['cfg']['CharTextareaRows'] = 5;
        $GLOBALS['cfg']['CharTextareaCols'] = 6;
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;
        $GLOBALS['cfg']['SendErrorReports'] = 'ask';
        $GLOBALS['cfg']['DefaultTabDatabase'] = 'structure';
        $GLOBALS['cfg']['ShowDatabasesNavigationAsTree'] = true;
        $GLOBALS['cfg']['DefaultTabTable'] = 'browse';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable'] = 'structure';
        $GLOBALS['cfg']['NavigationTreeDefaultTabTable2'] = '';
        $GLOBALS['cfg']['Confirm'] = true;
        $GLOBALS['cfg']['LoginCookieValidity'] = 1440;
        $GLOBALS['cfg']['enable_drag_drop_import'] = true;
        $GLOBALS['PMA_Config'] = new Config();

        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
    }

    /**
     * Call protected functions by setting visibility to public.
     *
     * @param string     $name   method name
     * @param array      $params parameters for the invocation
     * @param InsertEdit $object InsertEdit instance object
     *
     * @return mixed the output from the protected method.
     */
    private function callProtectedMethod(
        $name,
        array $params = [],
        InsertEdit $object = null
    ) {
        $class = new ReflectionClass(InsertEdit::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs(
            $object !== null ? $object : $this->insertEdit,
            $params
        );
    }

    /**
     * Test for getFormParametersForInsertForm
     *
     * @return void
     */
    public function testGetFormParametersForInsertForm()
    {
        $where_clause = array('foo' => 'bar ', '1' => ' test');
        $_POST['clause_is_unique'] = false;
        $_POST['sql_query'] = 'SELECT a';
        $GLOBALS['goto'] = 'index.php';

        $result = $this->insertEdit->getFormParametersForInsertForm(
            'dbname', 'tablename', array(), $where_clause, 'localhost'
        );

        $this->assertEquals(
            array(
                'db'        => 'dbname',
                'table'     => 'tablename',
                'goto'      => 'index.php',
                'err_url'   => 'localhost',
                'sql_query' => 'SELECT a',
                'where_clause[foo]' => 'bar',
                'where_clause[1]' => 'test',
                'clause_is_unique' => false
            ),
            $result
        );
    }

    /**
     * Test for getWhereClauseArray
     *
     * @return void
     */
    public function testGetWhereClauseArray()
    {
        $this->assertEquals(
            array(),
            $this->callProtectedMethod('getWhereClauseArray', [null])
        );

        $this->assertEquals(
            array(1, 2, 3),
            $this->callProtectedMethod('getWhereClauseArray', [[1, 2, 3]])
        );

        $this->assertEquals(
            array('clause'),
            $this->callProtectedMethod('getWhereClauseArray', ['clause'])
        );
    }

    /**
     * Test for analyzeWhereClauses
     *
     * @return void
     */
    public function testAnalyzeWhereClause()
    {
        $clauses = array('a=1', 'b="fo\o"');

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls(
                'result1',
                'result2'
            );

        $dbi->expects($this->exactly(2))
            ->method('fetchAssoc')
            ->willReturnOnConsecutiveCalls(
                array('assoc1'),
                array('assoc2')
            );

        $dbi->expects($this->exactly(2))
            ->method('getFieldsMeta')
            ->willReturnOnConsecutiveCalls(
                array(),
                array()
            );

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
        $result = $this->callProtectedMethod('analyzeWhereClauses', [
            $clauses,
            'table',
            'db'
        ]);

        $this->assertEquals(
            array(
                array('a=1', 'b="fo\\\\o"'),
                array('result1', 'result2'),
                array(
                    array('assoc1'),
                    array('assoc2')
                ),
                ''
            ),
            $result
        );
    }

    /**
     * Test for showEmptyResultMessageOrSetUniqueCondition
     *
     * @return void
     */
    public function testShowEmptyResultMessageOrSetUniqueCondition()
    {
        $temp = new stdClass;
        $temp->orgname = 'orgname';
        $temp->table = 'table';
        $temp->type = 'real';
        $temp->primary_key = 1;
        $meta_arr = array($temp);

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('getFieldsMeta')
            ->with('result1')
            ->will($this->returnValue($meta_arr));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->callProtectedMethod('showEmptyResultMessageOrSetUniqueCondition', [
            array('1' => array('1' => 1)), 1, array(),
            'SELECT', array('1' => 'result1')
        ]);

        $this->assertTrue($result);

        // case 2
        $GLOBALS['cfg']['ShowSQL'] = false;

        $responseMock = $this->getMockBuilder('PhpMyAdmin\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('addHtml'))
            ->getMock();

        $restoreInstance = Response::getInstance();
        $response = new ReflectionProperty('PhpMyAdmin\Response', '_instance');
        $response->setAccessible(true);
        $response->setValue($responseMock);

        $result = $this->callProtectedMethod('showEmptyResultMessageOrSetUniqueCondition', [
            array(false), 0, array('1'), 'SELECT', array('1' => 'result1')
        ]);

        $response->setValue($restoreInstance);

        $this->assertFalse($result);
    }

    /**
     * Test for loadFirstRow
     *
     * @return void
     */
    public function testLoadFirstRow()
    {
        $GLOBALS['cfg']['InsertRows'] = 2;

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('query')
            ->with(
                'SELECT * FROM `db`.`table` LIMIT 1;',
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            )
            ->will($this->returnValue('result1'));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->callProtectedMethod('loadFirstRow', ['table', 'db']);

        $this->assertEquals(
            array('result1', array(false, false)),
            $result
        );
    }

    /**
     * Test for urlParamsInEditMode
     *
     * @return void
     */
    public function testUrlParamsInEditMode()
    {
        $where_clause_array = array('foo=1', 'bar=2');
        $_POST['sql_query'] = 'SELECT 1';

        $result = $this->insertEdit->urlParamsInEditMode(array(1), $where_clause_array);

        $this->assertEquals(
            array(
                '0' => 1,
                'where_clause' => 'bar=2',
                'sql_query' => 'SELECT 1'
            ),
            $result
        );
    }

    /**
     * Test for showTypeOrFunction
     *
     * @return void
     */
    public function testShowTypeOrFunction()
    {
        $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $url_params = array('ShowFunctionFields' => 2);

        $result = $this->insertEdit->showTypeOrFunction('function', $url_params, false);

        $this->assertEquals(
            ' : <a href="tbl_change.php" data-post="ShowFunctionFields=1&amp;ShowFieldTypesIn'
            . 'DataEditView=1&amp;goto=sql.php&amp;lang=en">'
            . 'Function</a>',
            $result
        );

        // case 2
        $result = $this->insertEdit->showTypeOrFunction('function', $url_params, true);

        $this->assertEquals(
            '<th><a href="tbl_change.php" data-post="ShowFunctionFields=0&amp;ShowFieldTypesIn'
            . 'DataEditView=1&amp;goto=sql.php&amp;lang=en" title='
            . '"Hide">Function</a></th>',
            $result
        );

        // case 3
        $result = $this->insertEdit->showTypeOrFunction('type', $url_params, false);

        $this->assertEquals(
            ' : <a href="tbl_change.php" data-post="ShowFunctionFields=1&amp;ShowFieldTypesIn'
            . 'DataEditView=1&amp;goto=sql.php&amp;lang=en">'
            . 'Type</a>',
            $result
        );

        // case 4
        $result = $this->insertEdit->showTypeOrFunction('type', $url_params, true);

        $this->assertEquals(
            '<th><a href="tbl_change.php" data-post="ShowFunctionFields=1&amp;ShowFieldTypesIn'
            . 'DataEditView=0&amp;goto=sql.php&amp;lang=en" title='
            . '"Hide">Type</a></th>',
            $result
        );
    }

    /**
     * Test for analyzeTableColumnsArray
     *
     * @return void
     */
    public function testAnalyzeTableColumnsArray()
    {
        $column = array(
            'Field' => '1<2',
            'Field_md5' => 'pswd',
            'Type' => 'float(10, 1)'
        );

        $result = $this->callProtectedMethod('analyzeTableColumnsArray', [
            $column, array(), false
        ]);

        $this->assertEquals(
            $result['Field_html'],
            '1&lt;2'
        );

        $this->assertEquals(
            $result['Field_md5'],
            '4342210df36bf2ff2c4e2a997a6d4089'
        );

        $this->assertEquals(
            $result['True_Type'],
            'float'
        );

        $this->assertEquals(
            $result['len'],
            100
        );

        $this->assertEquals(
            $result['Field_title'],
            '1&lt;2'
        );

        $this->assertEquals(
            $result['is_binary'],
            false
        );

        $this->assertEquals(
            $result['is_blob'],
            false
        );

        $this->assertEquals(
            $result['is_char'],
            false
        );

        $this->assertEquals(
            $result['pma_type'],
            'float(10, 1)'
        );

        $this->assertEquals(
            $result['wrap'],
            ' nowrap'
        );

        $this->assertEquals(
            $result['Field'],
            '1<2'
        );
    }

    /**
     * Test for getColumnTitle
     *
     * @return void
     */
    public function testGetColumnTitle()
    {
        $column = array();
        $column['Field'] = 'f1<';
        $column['Field_html'] = 'f1&lt;';

        $this->assertEquals(
            $this->callProtectedMethod('getColumnTitle', [
                $column, array()
            ]),
            'f1&lt;'
        );

        $comments = array();
        $comments['f1<'] = 'comment>';

        $result = $this->callProtectedMethod('getColumnTitle', [
            $column, $comments
        ]);

        $this->assertContains(
            'title="comment&gt;"',
            $result
        );

        $this->assertContains(
            'f1&lt;',
            $result
        );
    }

    /**
     * Test for isColumn
     *
     * @return void
     */
    public function testIsColumn()
    {
        $column = array();
        $types = array('binary', 'varbinary');

        $column['Type'] = 'binaryfoo';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'Binaryfoo';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'varbinaryfoo';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'barbinaryfoo';
        $this->assertFalse($this->insertEdit->isColumn($column, $types));

        $types = array('char', 'varchar');

        $column['Type'] = 'char(10)';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'VarChar(20)';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'foochar';
        $this->assertFalse($this->insertEdit->isColumn($column, $types));

        $types = array('blob', 'tinyblob', 'mediumblob', 'longblob');

        $column['Type'] = 'blob';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'bloB';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'mediumBloB';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'tinyblobabc';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'longblob';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'foolongblobbar';
        $this->assertFalse($this->insertEdit->isColumn($column, $types));
    }

    /**
     * Test for getEnumSetAndTimestampColumns
     *
     * @return void
     */
    public function testGetEnumAndTimestampColumns()
    {
        $column = array();
        $column['True_Type'] = 'set';
        $this->assertEquals(
            array('set', '', false),
            $this->callProtectedMethod('getEnumSetAndTimestampColumns', [
                $column, false
            ])
        );

        $column['True_Type'] = 'enum';
        $this->assertEquals(
            array('enum', '', false),
            $this->callProtectedMethod('getEnumSetAndTimestampColumns', [
                $column, false
            ])
        );

        $column['True_Type'] = 'timestamp';
        $column['Type'] = 'date';
        $this->assertEquals(
            array('date', ' nowrap', true),
            $this->callProtectedMethod('getEnumSetAndTimestampColumns', [
                $column, false
            ])
        );

        $column['True_Type'] = 'timestamp';
        $column['Type'] = 'date';
        $this->assertEquals(
            array('date', ' nowrap', false),
            $this->callProtectedMethod('getEnumSetAndTimestampColumns', [
                $column, true
            ])
        );

        $column['True_Type'] = 'SET';
        $column['Type'] = 'num';
        $this->assertEquals(
            array('num', ' nowrap', false),
            $this->callProtectedMethod('getEnumSetAndTimestampColumns', [
                $column, false
            ])
        );

        $column['True_Type'] = '';
        $column['Type'] = 'num';
        $this->assertEquals(
            array('num', ' nowrap', false),
            $this->callProtectedMethod('getEnumSetAndTimestampColumns', [
                $column, false
            ])
        );
    }

    /**
     * Test for getFunctionColumn
     *
     * @return void
     */
    public function testGetFunctionColumn()
    {
        $GLOBALS['cfg']['ProtectBinary'] = 'blob';
        $column = array();
        $column['is_blob'] = true;
        $this->assertContains(
            '<td class="center">Binary</td>',
            $this->callProtectedMethod('getFunctionColumn', [
                $column, false, '', '', array(), 0, 0, 0, false, false, array()
            ])
        );

        $GLOBALS['cfg']['ProtectBinary'] = 'all';
        $column['is_binary'] = true;
        $this->assertContains(
            '<td class="center">Binary</td>',
            $this->callProtectedMethod('getFunctionColumn', [
                $column, true, '', '', array(), 0, 0, 0, false, false, array()
            ])
        );

        $GLOBALS['cfg']['ProtectBinary'] = 'noblob';
        $column['is_blob'] = false;
        $this->assertContains(
            '<td class="center">Binary</td>',
            $this->callProtectedMethod('getFunctionColumn', [
                $column, true, '', '', array(), 0, 0, 0, false, false, array()
            ])
        );

        $GLOBALS['cfg']['ProtectBinary'] = false;
        $column['True_Type'] = 'enum';
        $this->assertContains(
            '<td class="center">--</td>',
            $this->callProtectedMethod('getFunctionColumn', [
                $column, true, '', '', array(), 0, 0, 0, false, false, array()
            ])
        );

        $column['True_Type'] = 'set';
        $this->assertContains(
            '<td class="center">--</td>',
            $this->callProtectedMethod('getFunctionColumn', [
                $column, true, '', '', array(), 0, 0, 0, false, false, array()
            ])
        );

        $column['True_Type'] = '';
        $column['pma_type'] = 'int';
        $this->assertContains(
            '<td class="center">--</td>',
            $this->callProtectedMethod('getFunctionColumn', [
                $column, true, '', '', array('int'), 0, 0, 0, false, false, array()
            ])
        );

        $column['Field'] = 'num';
        $this->assertContains(
            '<select name="funcsa" b tabindex="5" id="field_3_1"',
            $this->callProtectedMethod('getFunctionColumn', [
                $column, true, 'a', 'b', array(), 2, 3, 3, false, false, array()
            ])
        );
    }

    /**
     * Test for getNullColumn
     *
     * @return void
     */
    public function testGetNullColumn()
    {
        $column = array('Field' => '');
        $column['Null'] = 'YES';
        $column['first_timestamp'] = false;
        $column['True_Type'] = 'enum';
        $column['Type'] = 0;
        $column['Field_md5'] = 'foobar';
        $foreigners = array(
            'foreign_keys_data' => array()
        );

        $result = $this->callProtectedMethod('getNullColumn', [
            $column, 'a', true, 2, 0, 1, "<script>", $foreigners, array(), false
        ]);

        $this->assertContains(
            '<input type="hidden" name="fields_null_preva" value="on" />',
            $result
        );

        $this->assertContains(
            '<input type="checkbox" class="checkbox_null" tabindex="2" '
            . 'name="fields_nulla" checked="checked" id="field_1_2" ',
            $result
        );

        $this->assertContains(
            '<input type="hidden" class="nullify_code" name="nullify_codea" '
            . 'value="2" ',
            $result
        );

        $this->assertContains(
            '<input type="hidden" class="hashed_field" name="hashed_fielda" '
            . 'value="foobar" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" class="multi_edit" name="multi_edita" '
            . 'value="<script>"',
            $result
        );

        // case 2
        $column['Null'] = 'NO';
        $result = $this->callProtectedMethod('getNullColumn', [
            $column, 'a', true, 2, 0, 1, "<script>", array(), array(), false
        ]);

        $this->assertEquals(
            "<td></td>\n",
            $result
        );

        // case 3
        $column['Null'] = 'YES';
        $result = $this->callProtectedMethod('getNullColumn', [
            $column, 'a', true, 2, 0, 1, "<script>", array(), array(), true
        ]);

        $this->assertEquals(
            "<td></td>\n",
            $result
        );
    }

    /**
     * Test for getNullifyCodeForNullColumn
     *
     * @return void
     */
    public function testGetNullifyCodeForNullColumn()
    {
        $column = $foreignData = array();
        $foreigners = array(
            'foreign_keys_data' => array()
        );
        $column['Field'] = 'f';
        $column['True_Type'] = 'enum';
        $column['Type'] = 'ababababababababababa';
        $this->assertEquals(
            '1',
            $this->callProtectedMethod('getNullifyCodeForNullColumn', [
                $column, $foreigners, array()
            ])
        );

        $column['True_Type'] = 'enum';
        $column['Type'] = 'abababababababababab';
        $this->assertEquals(
            '2',
            $this->callProtectedMethod('getNullifyCodeForNullColumn', [
                $column, $foreigners, array()
            ])
        );

        $column['True_Type'] = 'set';
        $this->assertEquals(
            '3',
            $this->callProtectedMethod('getNullifyCodeForNullColumn', [
                $column, $foreigners, array()
            ])
        );

        $column['True_Type'] = '';
        $foreigners['f'] = true;
        $foreignData['foreign_link'] = '';
        $this->assertEquals(
            '4',
            $this->callProtectedMethod('getNullifyCodeForNullColumn', [
                $column, $foreigners, $foreignData
            ])
        );
    }

    /**
     * Test for getForeignLink
     *
     * @return void
     */
    public function testGetForeignLink()
    {
        $column = $titles = array();
        $column['Field'] = 'f';
        $titles['Browse'] = "'";
        $GLOBALS['cfg']['ServerDefault'] = 2;
        $result = $this->callProtectedMethod('getForeignLink', [
            $column, 'a', 'b', 'd', 2, 0, 1, "abc", array('tbl', 'db'), 8,
            $titles, false
        ]);

        $this->assertContains(
            '<input type="hidden" name="fields_typeb" value="foreign"',
            $result
        );

        $this->assertContains(
            '<a class="ajax browse_foreign" href="browse_'
            . 'foreigners.php" data-post="db=db&amp;table=tbl&amp;field=f&amp;rownumber=8'
            . '&amp;data=abc&amp;server=1&amp;lang=en">',
            $result
        );

        $this->assertContains(
            '<input type="text" name="fieldsb" class="textfield" d tabindex="2" '
            . 'id="field_1_3" value="abc"',
            $result
        );
    }

    /**
     * Test for dispRowForeignData
     *
     * @return void
     */
    public function testDispRowForeignData()
    {
        $foreignData = array();
        $foreignData['disp_row'] = array();
        $foreignData['foreign_field'] = null;
        $foreignData['foreign_display'] = null;
        $GLOBALS['cfg']['ForeignKeyMaxLimit'] = 1;
        $GLOBALS['cfg']['NaturalOrder'] = false;
        $result = $this->callProtectedMethod('dispRowForeignData', [
            'a', 'b', 'd', 2, 0, 1, "<s>", $foreignData, false
        ]);

        $this->assertContains(
            "a\n",
            $result
        );

        $this->assertContains(
            '<select name="fieldsb" d class="textfield" tabindex="2" '
            . 'id="field_1_3">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="fields_typeb" value="foreign"',
            $result
        );
    }

    /**
     * Test for getTextarea
     *
     * @return void
     */
    public function testGetTextarea()
    {
        $GLOBALS['cfg']['TextareaRows'] = 20;
        $GLOBALS['cfg']['TextareaCols'] = 10;
        $GLOBALS['cfg']['CharTextareaRows'] = 5;
        $GLOBALS['cfg']['CharTextareaCols'] = 1;
        $GLOBALS['cfg']['LimitChars'] = 20;

        $column = array();
        $column['is_char'] = true;
        $column['Type'] = 'char(10)';
        $column['True_Type'] = 'char';
        $result = $this->callProtectedMethod('getTextarea', [
            $column, 'a', 'b', '', 2, 0, 1, "abc/", 'foobar', 'CHAR', false
        ]);

        $this->assertContains(
            '<textarea name="fieldsb" class="char" '
            . 'data-maxlength="10" rows="5" cols="1" dir="abc/" '
            . 'id="field_1_3" tabindex="2" data-type="CHAR">',
            $result
        );
    }

    /**
     * Test for getPmaTypeEnum
     *
     * @return void
     */
    public function testGetPmaTypeEnum()
    {
        $extracted_columnspec = $column = array();
        $extracted_columnspec['enum_set_values'] = array();
        $column['Type'] = 'abababababababababab';
        $column['values'] = array(
            array(
                'html' => 'foo',
                'plain' => 'data'
            )
        );
        $result = $this->callProtectedMethod('getPmaTypeEnum', [
            $column, 'a', 'b', $extracted_columnspec, 'd', 2, 0, 1,
            'foobar', false
        ]);

        $this->assertContains(
            '<input type="hidden" name="fields_typeb" value="enum" />',
            $result
        );

        $this->assertContains(
            '<input type="radio" name="fieldsb"',
            $result
        );

        $column['Type'] = 'ababababababababababa';
        $result = $this->callProtectedMethod('getPmaTypeEnum', [
            $column, 'a', 'b', $extracted_columnspec, 'd', 2, 0, 1,
            'foobar', false
        ]);

        $this->assertContains(
            '<input type="hidden" name="fields_typeb" value="enum"',
            $result
        );

        $this->assertContains(
            '<select name="fieldsb" d class="textfield" tabindex="2" '
            . 'id="field_1_3">',
            $result
        );
    }

    /**
     * Test for getColumnEnumValues
     *
     * @return void
     */
    public function testGetColumnEnumValues()
    {
        $extracted_columnspec = $column = array();
        $extracted_columnspec['enum_set_values'] = array(
            '<abc>', '"foo"'
        );

        $column['values'] = 'abc';

        $result = $this->callProtectedMethod('getColumnEnumValues', [
            $column, $extracted_columnspec
        ]);
        $this->assertEquals(
            array(
                array('plain' => '<abc>', 'html' => '&lt;abc&gt;'),
                array('plain' => '"foo"', 'html' => '&quot;foo&quot;'),
            ),
            $result
        );
    }

    /**
     * Test for getDropDownDependingOnLength
     *
     * @return void
     */
    public function testGetDropDownDependingOnLength()
    {
        $column_enum_values = array(
            array(
                'html' => 'foo',
                'plain' => 'data'
            ),
            array(
                'html' => 'bar',
                'plain' => ''
            )
        );

        $result = $this->callProtectedMethod('getDropDownDependingOnLength', [
            array(), 'a', 'b', 2, 0, 1, 'data', $column_enum_values, false
        ]);

        $this->assertContains(
            '<select name="fieldsa" b class="textfield" tabindex="2" '
            . 'id="field_1_3">',
            $result
        );

        $this->assertContains(
            '<option value="foo" selected="selected">',
            $result
        );

        $this->assertContains(
            '<option value="bar">',
            $result
        );

        // case 2
        $column_enum_values = array(
            array(
                'html' => 'foo',
                'plain' => 'data'
            )
        );

        $column = array();
        $column['Default'] = 'data';
        $column['Null'] = 'YES';
        $result = $this->callProtectedMethod('getDropDownDependingOnLength', [
            $column, 'a', 'b', 2, 0, 1, '', $column_enum_values, false
        ]);

        $this->assertContains(
            '<option value="foo" selected="selected">',
            $result
        );
    }

    /**
     * Test for getRadioButtonDependingOnLength
     *
     * @return void
     */
    public function testGetRadioButtonDependingOnLength()
    {
        $column_enum_values = array(
            array(
                'html' => 'foo',
                'plain' => 'data'
            ),
            array(
                'html' => 'bar',
                'plain' => ''
            )
        );

        $result = $this->callProtectedMethod('getRadioButtonDependingOnLength', [
            'a', 'b', 2, array(), 0, 1, 'data', $column_enum_values, false
        ]);

        $this->assertContains(
            '<input type="radio" name="fieldsa" class="textfield" value="foo" '
            . 'id="field_1_3_0" b checked="checked" tabindex="2" />',
            $result
        );

        $this->assertContains(
            '<label for="field_1_3_0">foo</label>',
            $result
        );

        $this->assertContains(
            '<input type="radio" name="fieldsa" class="textfield" value="bar" '
            . 'id="field_1_3_1" b tabindex="2" />',
            $result
        );

        $this->assertContains(
            '<label for="field_1_3_1">bar</label>',
            $result
        );

        // case 2
        $column_enum_values = array(
            array(
                'html' => 'foo',
                'plain' => 'data'
            )
        );

        $column = array();
        $column['Default'] = 'data';
        $column['Null'] = 'YES';
        $result = $this->callProtectedMethod('getRadioButtonDependingOnLength', [
            'a', 'b', 2, $column, 0, 1, '', $column_enum_values, false
        ]);

        $this->assertContains(
            '<input type="radio" name="fieldsa" class="textfield" value="foo" '
            . 'id="field_1_3_0" b checked="checked" tabindex="2" />',
            $result
        );
    }

    /**
     * Test for getPmaTypeSet
     *
     * @return void
     */
    public function testGetPmaTypeSet()
    {
        $column = array();
        $column['values']  = array(
            array(
                'html' => '&lt;',
                'plain' => '<'
            )
        );

        $column['select_size'] = 1;

        $result = $this->callProtectedMethod('getPmaTypeSet', [
            $column, array(), 'a', 'b', 'c', 2, 0, 1, 'data,<', false
        ]);

        $this->assertContains("a\n", $result);

        $this->assertContains(
            '<input type="hidden" name="fields_typeb" value="set" />',
            $result
        );

        $this->assertContains(
            '<option value="&lt;" selected="selected">&lt;</option>',
            $result
        );

        $this->assertContains(
            '<select name="fieldsb[]" class="textfield" size="1" '
            . 'multiple="multiple" c tabindex="2" id="field_1_3">',
            $result
        );
    }

    /**
     * Test for getColumnSetValueAndSelectSize
     *
     * @return void
     */
    public function testGetColumnSetValueAndSelectSize()
    {
        $extracted_columnspec = $column = array();
        $extracted_columnspec['enum_set_values'] = array('a', '<');
        $result = $this->callProtectedMethod('getColumnSetValueAndSelectSize', [
            array(), $extracted_columnspec
        ]);

        $this->assertEquals(
            array(
                array(
                    array('plain' => 'a', 'html' => 'a'),
                    array('plain' => '<', 'html' => '&lt;')
                ),
                2
            ),
            $result
        );

        $column['values'] = array(1, 2);
        $column['select_size'] = 3;
        $result = $this->callProtectedMethod('getColumnSetValueAndSelectSize', [
            $column, $extracted_columnspec
        ]);

        $this->assertEquals(
            array(
                array(1, 2),
                3
            ),
            $result
        );
    }

    /**
     * Test for getBinaryAndBlobColumn
     *
     * @return void
     */
    public function testGetBinaryAndBlobColumn()
    {
        $GLOBALS['cfg']['ProtectBinary'] = 'blob';
        $GLOBALS['cfg']['ShowFunctionFields'] = true;
        $column = array();
        $column['is_blob'] = true;
        $column['Field_md5'] = '123';
        $column['pma_type'] = 'blob';
        $column['True_Type'] = 'blob';
        $GLOBALS['max_upload_size'] = 65536;

        $result = $this->callProtectedMethod('getBinaryAndBlobColumn', [
            $column, '12\\"23', null, 20, 'a', 'b', 'c', 2, 1, 1, '/', null,
            'foo', true, false
        ]);

        $this->assertEquals(
            'Binary - do not edit (5 B)<input type="hidden" '
            . 'name="fieldsb" value="" /><input type="hidden" '
            . 'name="fields_typeb" value="protected" />'
            . '<br /><input type="file" name="fields_uploadfoo[123]" class="text'
            . 'field noDragDrop" id="field_1_3" size="10" c/>&nbsp;(Max: 64KiB)' . "\n",
            $result
        );

        // case 2
        $GLOBALS['cfg']['ProtectBinary'] = "all";
        $column['is_binary'] = true;

        $result = $this->callProtectedMethod('getBinaryAndBlobColumn', [
            $column, '1223', null, 20, 'a', 'b', 'c', 2, 1, 1, '/', null,
            'foo', false, false
        ]);

        $this->assertEquals(
            'Binary - do not edit (4 B)<input type="hidden" '
            . 'name="fieldsb" value="" /><input type="hidden" '
            . 'name="fields_typeb" value="protected" />',
            $result
        );

        // case 3
        $GLOBALS['cfg']['ProtectBinary'] = "noblob";
        $column['is_blob'] = false;

        $result = $this->callProtectedMethod('getBinaryAndBlobColumn', [
            $column, '1223', null, 20, 'a', 'b', 'c', 2, 1, 1, '/', null,
            'foo', true, false
        ]);

        $this->assertEquals(
            'Binary - do not edit (4 B)<input type="hidden" '
            . 'name="fieldsb" value="" /><input type="hidden" '
            . 'name="fields_typeb" value="protected" />',
            $result
        );

        // case 4
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $column['is_blob'] = true;
        $column['is_char'] = true;
        $column['Type'] = 'char(255)';
        $GLOBALS['cfg']['TextareaRows'] = 20;
        $GLOBALS['cfg']['TextareaCols'] = 10;
        $GLOBALS['cfg']['CharTextareaRows'] = 5;
        $GLOBALS['cfg']['CharTextareaCols'] = 1;
        $GLOBALS['cfg']['LimitChars'] = 100;

        $result = $this->callProtectedMethod('getBinaryAndBlobColumn', [
            $column, '1223', null, 20, 'a', 'b', 'c', 2, 1, 1, '/', null,
            'foo', true, false
        ]);

        $this->assertEquals(
            "\na\n"
            . '<textarea name="fieldsb" class="char" data-maxlength="255" rows="5" '
            . 'cols="1" dir="/" id="field_1_3" c tabindex="3" data-type="HEX">'
            . '</textarea><input type="hidden" name="fields_typeb" value="hex" />'
            . '<br /><input type="file" name="fields_uploadfoo[123]" class="text'
            . 'field noDragDrop" id="field_1_3" size="10" c/>&nbsp;(Max: 64KiB)' . "\n",
            $result
        );

        // case 5
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $GLOBALS['cfg']['LongtextDoubleTextarea'] = true;
        $GLOBALS['cfg']['LimitChars'] = 100;
        $column['is_blob'] = false;
        $column['len'] = 255;
        $column['is_char'] = false;
        $GLOBALS['cfg']['TextareaRows'] = 20;
        $GLOBALS['cfg']['TextareaCols'] = 10;

        $result = $this->callProtectedMethod('getBinaryAndBlobColumn', [
            $column, '1223', null, 20, 'a', 'b', 'c', 2, 1, 1, '/', null,
            'foo', true, false
        ]);

        $this->assertEquals(
            "\na\n"
            . '<textarea name="fieldsb" class="" rows="20" cols="10" dir="/" '
            . 'id="field_1_3" c tabindex="3" data-type="HEX">'
            . '</textarea><input type="hidden" '
            . 'name="fields_typeb" value="hex" />',
            $result
        );

        // case 6
        $column['is_blob'] = false;
        $column['len'] = 10;
        $GLOBALS['cfg']['LimitChars'] = 40;

        /**
         * This condition should be tested, however, it gives an undefined function
         * PhpMyAdmin\FileListing::getFileSelectOptions error:
         * $GLOBALS['cfg']['UploadDir'] = true;
         *
         */

        $result = $this->callProtectedMethod('getBinaryAndBlobColumn', [
            $column, '1223', null, 20, 'a', 'b', 'c', 2, 1, 1, '/', null,
            'foo', true, false
        ]);

        $this->assertEquals(
            "\na\n"
            . '<input type="text" name="fieldsb" value="" size="10" data-type='
            . '"HEX" class="textfield" c tabindex="3" id="field_1_3" />'
            . '<input type="hidden" name="fields_typeb" value="hex" />',
            $result
        );
    }

    /**
     * Test for getHtmlInput
     *
     * @return void
     */
    public function testGetHTMLinput()
    {
        $GLOBALS['cfg']['ShowFunctionFields'] = true;
        $column = array();
        $column['pma_type'] = 'date';
        $column['True_Type'] = 'date';
        $result = $this->callProtectedMethod('getHtmlInput', [
            $column, 'a', 'b', 30, 'c', 23, 2, 0, 'DATE', false
        ]);

        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datefield" c tabindex="25" id="field_0_3" />',
            $result
        );

        // case 2 datetime
        $column['pma_type'] = 'datetime';
        $column['True_Type'] = 'datetime';
        $result = $this->callProtectedMethod('getHtmlInput', [
            $column, 'a', 'b', 30, 'c', 23, 2, 0, 'DATE', false
        ]);
        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datetimefield" c tabindex="25" id="field_0_3" />',
            $result
        );

        // case 3 timestamp
        $column['pma_type'] = 'timestamp';
        $column['True_Type'] = 'timestamp';
        $result = $this->callProtectedMethod('getHtmlInput', [
            $column, 'a', 'b', 30, 'c', 23, 2, 0, 'DATE', false
        ]);
        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datetimefield" c tabindex="25" id="field_0_3" />',
            $result
        );
    }

    /**
     * Test for getMaxUploadSize
     *
     * @return void
     */
    public function testGetMaxUploadSize()
    {
        $GLOBALS['max_upload_size'] = 257;
        $column = array();
        $column['pma_type'] = 'tinyblob';
        $result = $this->callProtectedMethod('getMaxUploadSize', [
            $column, 256
        ]);

        $this->assertEquals(
            array("(Max: 256B)\n", 256),
            $result
        );

        // case 2
        $GLOBALS['max_upload_size'] = 250;
        $column['pma_type'] = 'tinyblob';
        $result = $this->callProtectedMethod('getMaxUploadSize', [
            $column, 20
        ]);

        $this->assertEquals(
            array("(Max: 250B)\n", 250),
            $result
        );
    }

    /**
     * Test for getValueColumnForOtherDatatypes
     *
     * @return void
     */
    public function testGetValueColumnForOtherDatatypes()
    {
        $column = array();
        $column['len'] = 20;
        $column['is_char'] = true;
        $column['Type'] = 'char(25)';
        $column['True_Type'] = 'char';
        $GLOBALS['cfg']['CharEditing'] = '';
        $GLOBALS['cfg']['MaxSizeForInputField'] = 30;
        $GLOBALS['cfg']['MinSizeForInputField'] = 10;
        $GLOBALS['cfg']['TextareaRows'] = 20;
        $GLOBALS['cfg']['TextareaCols'] = 10;
        $GLOBALS['cfg']['CharTextareaRows'] = 5;
        $GLOBALS['cfg']['CharTextareaCols'] = 1;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $extracted_columnspec = array();
        $extracted_columnspec['spec_in_brackets'] = 25;
        $result = $this->callProtectedMethod('getValueColumnForOtherDatatypes', [
            $column, 'defchar', 'a', 'b', 'c', 22, '&lt;', 12, 1, "/", "&lt;",
            "foo\nbar", $extracted_columnspec, false
        ]);

        $this->assertEquals(
            "a\n\na\n"
            . '<textarea name="fieldsb" class="char" '
            . 'data-maxlength="25" rows="5" cols="1" dir="/" '
            . 'id="field_1_3" c tabindex="34" data-type="CHAR">'
            . '&lt;</textarea>',
            $result
        );

        // case 2: (else)
        $column['is_char'] = false;
        $column['Extra'] = 'auto_increment';
        $column['pma_type'] = 'timestamp';
        $column['True_Type'] = 'timestamp';
        $result = $this->callProtectedMethod('getValueColumnForOtherDatatypes', [
            $column, 'defchar', 'a', 'b', 'c', 22, '&lt;', 12, 1, "/", "&lt;",
            "foo\nbar", $extracted_columnspec, false
        ]);

        $this->assertEquals(
            "a\n"
            . '<input type="text" name="fieldsb" value="&lt;" size="20" data-type="'
            . 'DATE" class="textfield datetimefield" c tabindex="34" id="field_1_3"'
            . ' /><input type="hidden" name="auto_incrementb" value="1" />'
            . '<input type="hidden" name="fields_typeb" value="timestamp" />',
            $result
        );

        // case 3: (else -> datetime)
        $column['pma_type'] = 'datetime';
        $result = $this->callProtectedMethod('getValueColumnForOtherDatatypes', [
            $column, 'defchar', 'a', 'b', 'c', 22, '&lt;', 12, 1, "/", "&lt;",
            "foo\nbar", $extracted_columnspec, false
        ]);

        $this->assertContains(
            '<input type="hidden" name="fields_typeb" value="datetime" />',
            $result
        );
    }

    /**
     * Test for getColumnSize
     *
     * @return void
     */
    public function testGetColumnSize()
    {
        $column = $extracted_columnspec = array();
        $column['is_char'] = true;
        $extracted_columnspec['spec_in_brackets'] = 45;
        $GLOBALS['cfg']['MinSizeForInputField'] = 30;
        $GLOBALS['cfg']['MaxSizeForInputField'] = 40;

        $this->assertEquals(
            40,
            $this->callProtectedMethod('getColumnSize', [
                $column, $extracted_columnspec
            ])
        );

        $this->assertEquals(
            'textarea',
            $GLOBALS['cfg']['CharEditing']
        );

        // case 2
        $column['is_char'] = false;
        $column['len'] = 20;
        $this->assertEquals(
            30,
            $this->callProtectedMethod('getColumnSize', [
                $column, $extracted_columnspec
            ])
        );
    }

    /**
     * Test for getHtmlForGisDataTypes
     *
     * @return void
     */
    public function testGetHTMLforGisDataTypes()
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $GLOBALS['cfg']['LinkLengthLimit'] = 2;
        $this->assertContains(
            '<a href="#" target="_blank"><span class="nowrap"><img src="themes/dot.'
            . 'gif" title="Edit/Insert" alt="Edit/Insert" class="icon ic_b_edit" />'
            . '</span></a>',
            $this->callProtectedMethod('getHtmlForGisDataTypes')
        );
    }

    /**
     * Test for getContinueInsertionForm
     *
     * @return void
     */
    public function testGetContinueInsertionForm()
    {
        $where_clause_array = array("a<b");
        $GLOBALS['cfg']['InsertRows'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['goto'] = "index.php";
        $_POST['where_clause'] = true;
        $_POST['sql_query'] = "SELECT 1";

        $result = $this->insertEdit->getContinueInsertionForm(
            "tbl", "db", $where_clause_array, "localhost"
        );

        $this->assertContains(
            '<form id="continueForm" method="post" action="tbl_replace.php" '
            . 'name="continueForm">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="db" value="db" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="table" value="tbl" />',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="goto" value="index.php">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="err_url" value="localhost">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="sql_query" value="SELECT 1">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="where_clause[0]" value="a&lt;b">',
            $result
        );
    }

    /**
     * Test for getActionsPanel
     *
     * @return void
     */
    public function testGetActionsPanel()
    {
        $GLOBALS['cfg']['ShowHint'] = false;
        $result = $this->insertEdit->getActionsPanel(null, 'back', 2, 1, false);

        $this->assertContains(
            '<select name="submit_type" class="control_at_footer" tabindex="4">',
            $result
        );

        $this->assertContains(
            '<select name="after_insert"',
            $result
        );

        $this->assertContains(
            '<input type="submit" class="control_at_footer" value="Go" '
            . 'tabindex="9" id="buttonYes" ',
            $result
        );
    }

    /**
     * Test for getSubmitTypeDropDown
     *
     * @return void
     */
    public function testGetSubmitTypeDropDown()
    {
        $result = $this->callProtectedMethod('getSubmitTypeDropDown', [
            array(), 2, 2
        ]);

        $this->assertContains(
            '<select name="submit_type" class="control_at_footer" tabindex="5">',
            $result
        );

        $this->assertContains(
            '<option value="save">',
            $result
        );
    }

    /**
     * Test for getAfterInsertDropDown
     *
     * @return void
     */
    public function testGetAfterInsertDropDown()
    {
        $result = $this->callProtectedMethod('getAfterInsertDropDown', [
            "`t`.`f` = 2", 'new_insert', true
        ]);

        $this->assertContains(
            '<option value="new_insert" selected="selected">',
            $result
        );

        $this->assertContains(
            '<option value="same_insert"',
            $result
        );

        $this->assertContains(
            '<option value="edit_next" >',
            $result
        );
    }

    /**
     * Test for getSubmitAndResetButtonForActionsPanel
     *
     * @return void
     */
    public function testGetSubmitAndResetButtonForActionsPanel()
    {
        $GLOBALS['cfg']['ShowHint'] = false;
        $result = $this->callProtectedMethod('getSubmitAndResetButtonForActionsPanel', [
            1, 0
        ]);

        $this->assertContains(
            '<input type="submit" class="control_at_footer" value="Go" '
            . 'tabindex="7" id="buttonYes" />',
            $result
        );

        $this->assertContains(
            '<input type="button" class="preview_sql" value="Preview SQL" '
            . 'tabindex="8" />',
            $result
        );

        $this->assertContains(
            '<input type="reset" class="control_at_footer" value="Reset" '
            . 'tabindex="9" />',
            $result
        );
    }

    /**
     * Test for getHeadAndFootOfInsertRowTable
     *
     * @return void
     */
    public function testGetHeadAndFootOfInsertRowTable()
    {
        $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = true;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $url_params = array('ShowFunctionFields' => 2);

        $result = $this->callProtectedMethod('getHeadAndFootOfInsertRowTable', [
            $url_params
        ]);

        $this->assertContains(
            'tbl_change.php" data-post="ShowFunctionFields=1&amp;ShowFieldTypesInDataEditView=0',
            $result
        );

        $this->assertContains(
            'tbl_change.php" data-post="ShowFunctionFields=0&amp;ShowFieldTypesInDataEditView=1',
            $result
        );
    }

    /**
     * Test for getSpecialCharsAndBackupFieldForExistingRow
     *
     * @return void
     */
    public function testGetSpecialCharsAndBackupFieldForExistingRow()
    {
        $column = $current_row = $extracted_columnspec = array();
        $column['Field'] = 'f';
        $current_row['f'] = null;
        $_POST['default_action'] = 'insert';
        $column['Key'] = 'PRI';
        $column['Extra'] = 'fooauto_increment';

        $result = $this->callProtectedMethod('getSpecialCharsAndBackupFieldForExistingRow', [
            $current_row, $column, array(), false, array(), 'a', false
        ]);

        $this->assertEquals(
            array(
                true,
                null,
                null,
                null,
                '<input type="hidden" name="fields_preva" value="" />'
            ),
            $result
        );

        // Case 2 (bit)
        unset($_POST['default_action']);

        $current_row['f'] = "123";
        $extracted_columnspec['spec_in_brackets'] = 20;
        $column['True_Type'] = 'bit';

        $result = $this->callProtectedMethod('getSpecialCharsAndBackupFieldForExistingRow', [
            $current_row, $column, $extracted_columnspec, false, array(), 'a', false
        ]);

        $this->assertEquals(
            array(
                false,
                "",
                "00000000000001111011",
                null,
                '<input type="hidden" name="fields_preva" value="123" />'
            ),
            $result
        );

        $current_row['f'] = "abcd";
        $result = $this->callProtectedMethod('getSpecialCharsAndBackupFieldForExistingRow', [
            $current_row, $column, $extracted_columnspec, false, array(), 'a', true
        ]);

        $this->assertEquals(
            array(
                false,
                "",
                "abcd",
                null,
                '<input type="hidden" name="fields_preva" value="abcd" />'
            ),
            $result
        );

        // Case 3 (bit)
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $current_row['f'] = "123";
        $extracted_columnspec['spec_in_brackets'] = 20;
        $column['True_Type'] = 'int';

        $result = $this->callProtectedMethod('getSpecialCharsAndBackupFieldForExistingRow', [
            $current_row, $column, $extracted_columnspec,
            false, array('int'), 'a', false
        ]);

        $this->assertEquals(
            array(
                false,
                "",
                "'',",
                null,
                '<input type="hidden" name="fields_preva" value="\'\'," />'
            ),
            $result
        );

        // Case 4 (else)
        $column['is_binary'] = false;
        $column['is_blob'] = true;
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $current_row['f'] = "11001";
        $extracted_columnspec['spec_in_brackets'] = 20;
        $column['True_Type'] = 'char';
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $result = $this->callProtectedMethod('getSpecialCharsAndBackupFieldForExistingRow', [
            $current_row, $column, $extracted_columnspec,
            false, array('int'), 'a', false
        ]);

        $this->assertEquals(
            array(
                false,
                "3131303031",
                "3131303031",
                "3131303031",
                '<input type="hidden" name="fields_preva" value="3131303031" />'
            ),
            $result
        );

        // Case 5
        $current_row['f'] = "11001\x00";

        $result = $this->callProtectedMethod('getSpecialCharsAndBackupFieldForExistingRow', [
            $current_row, $column, $extracted_columnspec,
            false, array('int'), 'a', false
        ]);

        $this->assertEquals(
            array(
                false,
                "313130303100",
                "313130303100",
                "313130303100",
                '<input type="hidden" name="fields_preva" value="313130303100" />'
            ),
            $result
        );
    }

    /**
     * Test for getSpecialCharsAndBackupFieldForInsertingMode
     *
     * @return void
     */
    public function testGetSpecialCharsAndBackupFieldForInsertingMode()
    {
        $column = array();
        $column['True_Type'] = 'bit';
        $column['Default'] = b'101';
        $column['is_binary'] = true;
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $result = $this->callProtectedMethod('getSpecialCharsAndBackupFieldForInsertingMode', [
            $column, false
        ]);

        $this->assertEquals(
            array(
                false,
                '101',
                '101',
                '',
                '101'
            ),
            $result
        );

        // case 2
        unset($column['Default']);
        $column['True_Type'] = 'char';

        $result = $this->callProtectedMethod('getSpecialCharsAndBackupFieldForInsertingMode', [
            $column, false
        ]);

        $this->assertEquals(
            array(
                true,
                '',
                '',
                '',
                ''
            ),
            $result
        );
    }

    /**
     * Test for getParamsForUpdateOrInsert
     *
     * @return void
     */
    public function testGetParamsForUpdateOrInsert()
    {
        $_POST['where_clause'] = 'LIMIT 1';
        $_POST['submit_type'] = 'showinsert';

        $result = $this->insertEdit->getParamsForUpdateOrInsert();

        $this->assertEquals(
            array(
                array('LIMIT 1'),
                true,
                true,
                false
            ),
            $result
        );

        // case 2 (else)
        unset($_POST['where_clause']);
        $_POST['fields']['multi_edit'] = array('a' => 'b', 'c' => 'd');
        $result = $this->insertEdit->getParamsForUpdateOrInsert();

        $this->assertEquals(
            array(
                array('a', 'c'),
                false,
                true,
                false
            ),
            $result
        );
    }

    /**
     * Test for isInsertRow
     *
     * @return void
     */
    public function testIsInsertRow()
    {
        $_POST['insert_rows'] = 5;
        $GLOBALS['cfg']['InsertRows'] = 2;

        $scriptsMock = $this->getMockBuilder('PhpMyAdmin\Scripts')
            ->disableOriginalConstructor()
            ->setMethods(array('addFile'))
            ->getMock();

        $scriptsMock->expects($this->exactly(2))
            ->method('addFile');

        $headerMock = $this->getMockBuilder('PhpMyAdmin\Header')
            ->disableOriginalConstructor()
            ->setMethods(array('getScripts'))
            ->getMock();

        $headerMock->expects($this->once())
            ->method('getScripts')
            ->will($this->returnValue($scriptsMock));

        $responseMock = $this->getMockBuilder('PhpMyAdmin\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getHeader'))
            ->getMock();

        $responseMock->expects($this->once())
            ->method('getHeader')
            ->will($this->returnValue($headerMock));

        $restoreInstance = Response::getInstance();
        $response = new ReflectionProperty('PhpMyAdmin\Response', '_instance');
        $response->setAccessible(true);
        $response->setValue($responseMock);

        $this->insertEdit->isInsertRow();

        $response->setValue($restoreInstance);

        $this->assertEquals(5, $GLOBALS['cfg']['InsertRows']);
    }

    /**
     * Test for setSessionForEditNext
     *
     * @return void
     */
    public function testSetSessionForEditNext()
    {
        $temp = new stdClass;
        $temp->orgname = 'orgname';
        $temp->table = 'table';
        $temp->type = 'real';
        $temp->primary_key = 1;
        $meta_arr = array($temp);

        $row = array('1' => 1);
        $res = 'foobar';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('query')
            ->with('SELECT * FROM `db`.`table` WHERE `a` > 2 LIMIT 1;')
            ->will($this->returnValue($res));

        $dbi->expects($this->at(1))
            ->method('fetchRow')
            ->with($res)
            ->will($this->returnValue($row));

        $dbi->expects($this->at(2))
            ->method('getFieldsMeta')
            ->with($res)
            ->will($this->returnValue($meta_arr));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
        $this->insertEdit->setSessionForEditNext('`a` = 2');

        $this->assertEquals(
            'CONCAT(`table`.`orgname`) IS NULL',
            $_SESSION['edit_next']
        );
    }

    /**
     * Test for getGotoInclude
     *
     * @return void
     */
    public function testGetGotoInclude()
    {
        $GLOBALS['goto'] = '123.php';
        $GLOBALS['table'] = '';

        $this->assertEquals(
            'db_sql.php',
            $this->insertEdit->getGotoInclude('index')
        );

        $GLOBALS['table'] = 'tbl';
        $this->assertEquals(
            'tbl_sql.php',
            $this->insertEdit->getGotoInclude('index')
        );

        $GLOBALS['goto'] = 'db_sql.php';

        $this->assertEquals(
            'db_sql.php',
            $this->insertEdit->getGotoInclude('index')
        );

        $this->assertEquals(
            '',
            $GLOBALS['table']
        );

        $_POST['after_insert'] = 'new_insert';
        $this->assertEquals(
            'tbl_change.php',
            $this->insertEdit->getGotoInclude('index')
        );
    }

    /**
     * Test for getErrorUrl
     *
     * @return void
     */
    public function testGetErrorUrl()
    {
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $this->assertEquals(
            'tbl_change.php?lang=en',
            $this->insertEdit->getErrorUrl(array())
        );

        $_POST['err_url'] = 'localhost';
        $this->assertEquals(
            'localhost',
            $this->insertEdit->getErrorUrl(array())
        );
    }

    /**
     * Test for buildSqlQuery
     *
     * @return void
     */
    public function testBuildSqlQuery()
    {
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $query_fields = array('a', 'b');
        $value_sets = array(1, 2);

        $this->assertEquals(
            array('INSERT IGNORE INTO `table` (a, b) VALUES (1), (2)'),
            $this->insertEdit->buildSqlQuery(true, $query_fields, $value_sets)
        );

        $this->assertEquals(
            array('INSERT INTO `table` (a, b) VALUES (1), (2)'),
            $this->insertEdit->buildSqlQuery(false, $query_fields, $value_sets)
        );
    }

    /**
     * Test for executeSqlQuery
     *
     * @return void
     */
    public function testExecuteSqlQuery()
    {
        $query = array('SELECT 1', 'SELECT 2');
        $GLOBALS['sql_query'] = 'SELECT';
        $GLOBALS['cfg']['IgnoreMultiSubmitErrors'] = false;
        $_POST['submit_type'] = '';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('query')
            ->with('SELECT 1')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('affectedRows')
            ->will($this->returnValue(2));

        $dbi->expects($this->at(2))
            ->method('insertId')
            ->will($this->returnValue(1));

        $dbi->expects($this->at(5))
            ->method('query')
            ->with('SELECT 2')
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('getError')
            ->will($this->returnValue('err'));

        $dbi->expects($this->exactly(2))
            ->method('getWarnings')
            ->will($this->returnValue(array()));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->executeSqlQuery(array(), $query);

        $this->assertEquals(
            array('sql_query' => 'SELECT'),
            $result[0]
        );

        $this->assertEquals(
            2,
            $result[1]
        );

        $this->assertInstanceOf(
            'PhpMyAdmin\Message',
            $result[2][0]
        );

        $msg = $result[2][0];
        $reflectionMsg = new ReflectionProperty('PhpMyAdmin\Message', 'params');
        $reflectionMsg->setAccessible(true);

        $this->assertEquals(
            array(2),
            $reflectionMsg->getValue($msg)
        );

        $this->assertEquals(
            array(),
            $result[3]
        );

        $this->assertEquals(
            array('err'),
            $result[4]
        );

        $this->assertEquals(
            'SELECT',
            $result[5]
        );
    }

    /**
     * Test for executeSqlQuery
     *
     * @return void
     */
    public function testExecuteSqlQueryWithTryQuery()
    {
        $query = array('SELECT 1', 'SELECT 2');
        $GLOBALS['sql_query'] = 'SELECT';
        $GLOBALS['cfg']['IgnoreMultiSubmitErrors'] = true;
        $_POST['submit_type'] = '';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('tryQuery')
            ->with('SELECT 1')
            ->will($this->returnValue(true));

        $dbi->expects($this->at(1))
            ->method('affectedRows')
            ->will($this->returnValue(2));

        $dbi->expects($this->at(2))
            ->method('insertId')
            ->will($this->returnValue(1));

        $dbi->expects($this->at(5))
            ->method('tryQuery')
            ->with('SELECT 2')
            ->will($this->returnValue(false));

        $dbi->expects($this->once())
            ->method('getError')
            ->will($this->returnValue('err'));

        $dbi->expects($this->exactly(2))
            ->method('getWarnings')
            ->will($this->returnValue(array()));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->executeSqlQuery(array(), $query);

        $this->assertEquals(
            array('sql_query' => 'SELECT'),
            $result[0]
        );

        $this->assertEquals(
            2,
            $result[1]
        );

        $this->assertInstanceOf(
            'PhpMyAdmin\Message',
            $result[2][0]
        );

        $msg = $result[2][0];
        $reflectionMsg = new ReflectionProperty('PhpMyAdmin\Message', 'params');
        $reflectionMsg->setAccessible(true);

        $this->assertEquals(
            array(2),
            $reflectionMsg->getValue($msg)
        );

        $this->assertEquals(
            array(),
            $result[3]
        );

        $this->assertEquals(
            array('err'),
            $result[4]
        );

        $this->assertEquals(
            'SELECT',
            $result[5]
        );
    }

    /**
     * Test for getWarningMessages
     *
     * @return void
     */
    public function testGetWarningMessages()
    {
        $warnings = array(
            array('Level' => 1, 'Code' => 42, 'Message' => 'msg1'),
            array('Level' => 2, 'Code' => 43, 'Message' => 'msg2'),
        );

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getWarnings')
            ->will($this->returnValue($warnings));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->callProtectedMethod('getWarningMessages');

        $this->assertEquals(
            array(
                "1: #42 msg1",
                "2: #43 msg2"
            ),
            $result
        );
    }

    /**
     * Test for getDisplayValueForForeignTableColumn
     *
     * @return void
     */
    public function testGetDisplayValueForForeignTableColumn()
    {
        $map = array();
        $map['f']['foreign_db'] = 'information_schema';
        $map['f']['foreign_table'] = 'TABLES';
        $map['f']['foreign_field'] = 'f';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with(
                'SELECT `TABLE_COMMENT` FROM `information_schema`.`TABLES` WHERE '
                . '`f`=1',
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            )
            ->will($this->returnValue('r1'));

        $dbi->expects($this->once())
            ->method('numRows')
            ->with('r1')
            ->will($this->returnValue('2'));

        $dbi->expects($this->once())
            ->method('fetchRow')
            ->with('r1', 0)
            ->will($this->returnValue(array('2')));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getDisplayValueForForeignTableColumn("=1", $map, 'f');

        $this->assertEquals(2, $result);
    }

    /**
     * Test for getLinkForRelationalDisplayField
     *
     * @return void
     */
    public function testGetLinkForRelationalDisplayField()
    {
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_SESSION['tmpval']['relational_display'] = 'K';
        $map = array();
        $map['f']['foreign_db'] = 'information_schema';
        $map['f']['foreign_table'] = 'TABLES';
        $map['f']['foreign_field'] = 'f';

        $result = $this->insertEdit->getLinkForRelationalDisplayField($map, 'f', "=1", "a>", "b<");

        $this->assertEquals(
            '<a href="sql.php?db=information_schema&amp;table=TABLES&amp;pos=0&amp;'
            . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
            . '+%60f%60%3D1&amp;lang=en" title="a&gt;">b&lt;</a>',
            $result
        );

        $_SESSION['tmpval']['relational_display'] = 'D';
        $result = $this->insertEdit->getLinkForRelationalDisplayField($map, 'f', "=1", "a>", "b<");

        $this->assertEquals(
            '<a href="sql.php?db=information_schema&amp;table=TABLES&amp;pos=0&amp;'
            . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
            . '+%60f%60%3D1&amp;lang=en" title="b&lt;">a&gt;</a>',
            $result
        );
    }

    /**
     * Test for transformEditedValues
     *
     * @return void
     */
    public function testTransformEditedValues()
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $edited_values = array(
            array('c' => 'cname')
        );
        $GLOBALS['cfg']['DefaultTransformations']['PreApPend'] = ['', ''];
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_POST['where_clause'] = 1;
        $transformation = array(
            'transformation_options' => "'','option ,, quoted',abd"
        );
        $result = $this->insertEdit->transformEditedValues(
            'db', 'table', $transformation, $edited_values,
            'Text_Plain_PreApPend.php', 'c', array('a' => 'b'),
            'transformation'
        );

        $this->assertEquals(
            array('a' => 'b', 'transformations' => array("cnameoption ,, quoted")),
            $result
        );
    }

    /**
     * Test for getQueryValuesForInsertAndUpdateInMultipleEdit
     *
     * @return void
     */
    public function testGetQueryValuesForInsertAndUpdateInMultipleEdit()
    {
        $multi_edit_columns_name = array('0' => 'fld');

        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name, array(), '', array(), array(), true, array(1),
            array(2), 'foo', array(), '0', array()
        );

        $this->assertEquals(
            array(
                array(1, 'foo'),
                array(2, '`fld`')
            ),
            $result
        );

        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name, array(), '', array(), array(), false, array(1),
            array(2), 'foo', array(), '0', array('a')
        );

        $this->assertEquals(
            array(
                array(1, '`fld` = foo'),
                array(2)
            ),
            $result
        );

        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name, array('b'), "'`c`'", array('c'), array(),
            false, array(1), array(2), 'foo', array(), '0', array('a')
        );

        $this->assertEquals(
            array(
                array(1),
                array(2)
            ),
            $result
        );

        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name, array('b'), "'`c`'", array('c'), array(3),
            false, array(1), array(2), 'foo', array(), 0, array()
        );

        $this->assertEquals(
            array(
                array(1, '`fld` = foo'),
                array(2)
            ),
            $result
        );
    }

    /**
     * Test for getCurrentValueAsAnArrayForMultipleEdit
     *
     * @return void
     */
    public function testGetCurrentValueAsAnArrayForMultipleEdit()
    {
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            array(), array(), array(), 'currVal', array(),
            array(), array(), '0'
        );

        $this->assertEquals('currVal', $result);

        // case 2
        $multi_edit_funcs = array('UUID');

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with('SELECT UUID()')
            ->will($this->returnValue('uuid1234'));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs, array(), array(), 'currVal', array(),
            array(), array(), '0'
        );

        $this->assertEquals("'uuid1234'", $result);

        // case 3
        $multi_edit_funcs = array('AES_ENCRYPT');
        $multi_edit_salt = array("");
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs, $multi_edit_salt, array(), "'''", array(),
            array('func'), array('func'), '0'
        );
        $this->assertEquals("AES_ENCRYPT(''','')", $result);

        // case 4
        $multi_edit_funcs = array('func');
        $multi_edit_salt = array();
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs, $multi_edit_salt, array(), "'''", array(),
            array('func'), array('func'), '0'
        );
        $this->assertEquals("func(''')", $result);

        // case 5
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs, $multi_edit_salt, array(), "''", array(),
            array('func'), array('func'), '0'
        );
        $this->assertEquals("func()", $result);
    }

    /**
     * Test for getCurrentValueForDifferentTypes
     *
     * @return void
     */
    public function testGetCurrentValueForDifferentTypes()
    {
        $prow = array();
        $prow['a'] = b'101';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(4))
            ->method('fetchSingleRow')
            ->with('SELECT * FROM `table` WHERE 1;')
            ->will($this->returnValue($prow));
        $dbi->expects($this->exactly(2))
            ->method('escapeString')
            ->willReturnOnConsecutiveCalls(
                $this->returnArgument(0),
                "20\'12"
            );

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            '123', '0', array(), '', array(), 0, array(), array(),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            '123',
            $result
        );

        // case 2
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('test'), '', array(1), 0, array(), array(),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            'NULL',
            $result
        );

        // case 3
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('test'), '', array(), 0, array(), array(),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            "''",
            $result
        );

        // case 4
        $_POST['fields']['multi_edit'][0][0] = array();
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('set'), '', array(), 0, array(), array(),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            "''",
            $result
        );

        // case 5
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('protected'), '', array(), 0, array('a'), array(),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            "0x313031",
            $result
        );

        // case 6
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('protected'), '', array(), 0, array('a'), array(),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            "",
            $result
        );

        // case 7
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('bit'), '20\'12', array(), 0, array('a'), array(),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            "b'00010'",
            $result
        );

        // case 7
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('date'), '20\'12', array(), 0, array('a'), array(),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            "'20\\'12'",
            $result
        );

        // case 8
        $_POST['fields']['multi_edit'][0][0] = array();
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('set'), '', array(), 0, array(), array(1),
            array(), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            "NULL",
            $result
        );

        // case 9
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false, '0', array('protected'), '', array(), 0, array('a'), array(),
            array(1), true, true, '1', 'table', array()
        );

        $this->assertEquals(
            "''",
            $result
        );
    }

    /**
     * Test for verifyWhetherValueCanBeTruncatedAndAppendExtraData
     *
     * @return void
     */
    public function testVerifyWhetherValueCanBeTruncatedAndAppendExtraData()
    {
        $extra_data = array('isNeedToRecheck' => true);

        $_POST['where_clause'][0] = 1;

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('tryQuery')
            ->with('SELECT `table`.`a` FROM `db`.`table` WHERE 1');
        $meta = new stdClass();
        $meta->type = 'int';
        $dbi->expects($this->at(1))
            ->method('getFieldsMeta')
            ->will($this->returnValue(array($meta)));

        $dbi->expects($this->at(2))
            ->method('fetchRow')
            ->will($this->returnValue(false));

        $dbi->expects($this->at(3))
            ->method('freeResult');

        $dbi->expects($this->at(4))
            ->method('tryQuery')
            ->with('SELECT `table`.`a` FROM `db`.`table` WHERE 1');

        $meta = new stdClass();
        $meta->type = 'int';
        $meta->flags = '';
        $dbi->expects($this->at(5))
            ->method('getFieldsMeta')
            ->will($this->returnValue(array($meta)));

        $dbi->expects($this->at(6))
            ->method('fetchRow')
            ->will($this->returnValue(array(0 => '123')));

        $dbi->expects($this->at(7))
            ->method('freeResult');

        $dbi->expects($this->at(8))
            ->method('tryQuery')
            ->with('SELECT `table`.`a` FROM `db`.`table` WHERE 1');

        $meta = new stdClass();
        $meta->type = 'timestamp';
        $meta->flags = '';
        $dbi->expects($this->at(9))
            ->method('getFieldsMeta')
            ->will($this->returnValue(array($meta)));

        $dbi->expects($this->at(10))
            ->method('fetchRow')
            ->will($this->returnValue(array(0 => '2013-08-28 06:34:14')));

        $dbi->expects($this->at(11))
            ->method('freeResult');

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
            'db', 'table', 'a', $extra_data
        );

        $this->assertFalse($extra_data['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
            'db', 'table', 'a', $extra_data
        );

        $this->assertEquals('123', $extra_data['truncatableFieldValue']);
        $this->assertTrue($extra_data['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
            'db', 'table', 'a', $extra_data
        );

        $this->assertEquals(
            '2013-08-28 06:34:14.000000', $extra_data['truncatableFieldValue']
        );
        $this->assertTrue($extra_data['isNeedToRecheck']);
    }

    /**
     * Test for getTableColumns
     *
     * @return void
     */
    public function testGetTableColumns()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->at(0))
            ->method('selectDb')
            ->with('db');

        $dbi->expects($this->at(1))
            ->method('getColumns')
            ->with('db', 'table')
            ->will($this->returnValue(array('a' => 'b', 'c' => 'd')));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getTableColumns('db', 'table');

        $this->assertEquals(
            array('b', 'd'),
            $result
        );
    }

    /**
     * Test for determineInsertOrEdit
     *
     * @return void
     */
    public function testDetermineInsertOrEdit()
    {
        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        $_POST['where_clause'] = '1';
        $_SESSION['edit_next'] = '1';
        $_POST['ShowFunctionFields'] = true;
        $_POST['ShowFieldTypesInDataEditView'] = true;
        $_POST['after_insert'] = 'edit_next';
        $GLOBALS['cfg']['InsertRows'] = 2;
        $GLOBALS['cfg']['ShowSQL'] = false;
        $_POST['default_action'] = 'insert';

        $responseMock = $this->getMockBuilder('PhpMyAdmin\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('addHtml'))
            ->getMock();

        $restoreInstance = Response::getInstance();
        $response = new ReflectionProperty('PhpMyAdmin\Response', '_instance');
        $response->setAccessible(true);
        $response->setValue($responseMock);

        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->determineInsertOrEdit('1', 'db', 'table');

        $this->assertEquals(
            array(
                false,
                null,
                array(1),
                null,
                array(null),
                array(null),
                false,
                "edit_next"
            ),
            $result
        );

        // case 2
        unset($_POST['where_clause']);
        unset($_SESSION['edit_next']);
        $_POST['default_action'] = '';

        $result = $this->insertEdit->determineInsertOrEdit(null, 'db', 'table');

        $response->setValue($restoreInstance);

        $this->assertEquals(
            array(
                true,
                null,
                array(),
                null,
                null,
                array(false, false),
                false,
                "edit_next"
            ),
            $result
        );
    }

    /**
     * Test for getCommentsMap
     *
     * @return void
     */
    public function testGetCommentsMap()
    {
        $GLOBALS['cfg']['ShowPropertyComments'] = false;

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('db', 'table', null, true)
            ->will(
                $this->returnValue(
                    array(array('Comment' => 'b', 'Field' => 'd'))
                )
            );

        $dbi->expects($this->any())
            ->method('getTable')
            ->will(
                $this->returnValue(
                    new Table('table', 'db')
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $this->assertEquals(
            array(),
            $this->insertEdit->getCommentsMap('db', 'table')
        );

        $GLOBALS['cfg']['ShowPropertyComments'] = true;

        $this->assertEquals(
            array('d' => 'b'),
            $this->insertEdit->getCommentsMap('db', 'table')
        );
    }

    /**
     * Test for getUrlParameters
     *
     * @return void
     */
    public function testGetUrlParameters()
    {
        $_POST['sql_query'] = 'SELECT';
        $GLOBALS['goto'] = 'tbl_change.php';

        $this->assertEquals(
            array(
                'db' => 'foo',
                'sql_query' => 'SELECT',
                'table' => 'bar'
            ),
            $this->insertEdit->getUrlParameters('foo', 'bar')
        );
    }

    /**
     * Test for getHtmlForIgnoreOption
     *
     * @return void
     */
    public function testGetHtmlForIgnoreOption()
    {
        $expected = '<input type="checkbox" %sname="insert_ignore_1"'
            . ' id="insert_ignore_1" /><label for="insert_ignore_1">'
            . 'Ignore</label><br />' . "\n";
        $checked = 'checked="checked" ';
        $this->assertEquals(
            sprintf($expected, $checked),
            $this->insertEdit->getHtmlForIgnoreOption(1)
        );

        $this->assertEquals(
            sprintf($expected, ''),
            $this->insertEdit->getHtmlForIgnoreOption(1, false)
        );
    }

    /**
     * Test for getHtmlForInsertEditFormColumn
     *
     * @return void
     */
    public function testGetHtmlForInsertEditFormColumn()
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $o_rows = 0;
        $tabindex = 0;
        $GLOBALS['plugin_scripts'] = array();
        $table_columns = array(
            array(
                'Field' => 'col',
                'Type' => 'varchar(20)',
                'Null' => 'Yes',
                'Privileges' => 'insert,update,select'
            )
        );
        $repopulate = array(
            md5('col') => 'val'
        );
        $column_mime = array(
            'input_transformation' => 'Input/Image_JPEG_Upload.php',
            'input_transformation_options' => '150'
        );

        // Test w/ input transformation
        $actual = $this->callProtectedMethod('getHtmlForInsertEditFormColumn', [
            $table_columns, 0, array(), false, array(), '', '',
            '', false, array(), &$o_rows, &$tabindex, 0, false, 0,
            array(), 0, 0, 'table', 'db', 0, array(), 0, '', '',
            $repopulate, $column_mime, ''
        ]);

        $this->assertContains(
            'col',
            $actual
        );
        $this->assertContains(
            '<option>AES_ENCRYPT</option>',
            $actual
        );
        $this->assertContains(
            '<span class="column_type" dir="ltr">varchar(20)</span>',
            $actual
        );
        $this->assertContains(
            '<tr class="noclick">',
            $actual
        );
        $this->assertContains(
            '<span class="default_value hide">',
            $actual
        );
        $this->assertContains(
            '<img src="" width="150" height="100" '
            . 'alt="Image preview here"/>',
            $actual
        );
        $this->assertContains(
            '<input type="file" '
            . 'name="fields_upload[d89e2ddb530bb8953b290ab0793aecb0]" '
            . 'accept="image/*" '
            . 'class="image-upload"'
            . '/>',
            $actual
        );

        // Test w/o input_transformation
        $table_columns = array(
            array(
                'Field' => 'qwerty',
                'Type' => 'datetime',
                'Null' => 'Yes',
                'Key' => '',
                'Extra' => '',
                'Default' => null,
                'Privileges' => 'insert,update,select'
            )
        );
        $repopulate = array(
            md5('qwerty') => '12-10-14'
        );
        $actual = $this->callProtectedMethod('getHtmlForInsertEditFormColumn', [
            $table_columns, 0, array(), false, array(), '', '',
            '', true, array(), &$o_rows, &$tabindex, 0, false, 0,
            array(), 0, 0, 'table', 'db', 0, array(), 0, '', '',
            $repopulate, array(), ''
        ]);
        $this->assertContains(
            'qwerty',
            $actual
        );
        $this->assertContains(
            '<option>UUID</option>',
            $actual
        );
        $this->assertContains(
            '<span class="column_type" dir="ltr">datetime</span>',
            $actual
        );
        $this->assertContains(
            '<input type="text" '
            . 'name="fields[d8578edf8458ce06fbc5bb76a58c5ca4]" '
            . 'value="12-10-14.000000"',
            $actual
        );
    }

    /**
     * Test for getHtmlForInsertEditRow
     *
     * @return void
     */
    public function testGetHtmlForInsertEditRow()
    {
        $o_rows = 0;
        $tabindex = 0;
        $GLOBALS['plugin_scripts'] = array();
        $GLOBALS['cfg']['LongtextDoubleTextarea'] = true;
        $GLOBALS['cfg']['CharEditing'] = true;
        $table_columns = array(
            array(
                'Field' => 'test',
                'Extra' => '',
                'Type' => 'longtext',
                'Null' => 'Yes',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,insert,update,references',
            )
        );
        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            array(), $table_columns, array(), false, array(), '', '',
            '', false, array(), $o_rows, $tabindex, 1, false, 0,
            array(), 0, 0, 'table', 'db', 0, array(), 0, '',
            array(), array('wc')
        );
        $this->assertContains(
            'test',
            $actual
        );
        $this->assertContains(
            '<th>Column</th>',
            $actual
        );
        $this->assertContains(
            '<a',
            $actual
        );
        $this->assertContains(
            '<th>Value</th>',
            $actual
        );
        $this->assertContains(
            '<span class="column_type" dir="ltr">longtext</span>',
            $actual
        );
        $this->assertContains(
            '<textarea name="fields[098f6bcd4621d373cade4e832627b4f6]"',
            $actual
        );
    }

    /**
     * Test for getHtmlForInsertEditRow based on the column privilges
     *
     * @return void
     */
    public function testGetHtmlForInsertEditRowBasedOnColumnPrivileges()
    {
        $o_rows = 0;
        $tabindex = 0;
        $GLOBALS['plugin_scripts'] = array();
        $GLOBALS['cfg']['LongtextDoubleTextarea'] = true;
        $GLOBALS['cfg']['CharEditing'] = true;

        // edit
        $table_columns = array(
            array(
                'Field' => 'foo',
                'Type' => 'longtext',
                'Extra' => '',
                'Null' => 'Yes',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,insert,update,references',
            ),
            array(
                'Field' => 'bar',
                'Type' => 'longtext',
                'Extra' => '',
                'Null' => 'Yes',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,insert,references',
            )
        );
        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            array(), $table_columns, array(), false, array(), '', '',
            '', false, array(), $o_rows, $tabindex, 1, false, 0,
            array(), 0, 0, 'table', 'db', 0, array(), 0, '',
            array(), array('wc')
        );
        $this->assertContains(
            'foo',
            $actual
        );
        $this->assertNotContains(
            'bar',
            $actual
        );

        // insert
        $table_columns = array(
            array(
                'Field' => 'foo',
                'Type' => 'longtext',
                'Extra' => '',
                'Null' => 'Yes',
                'Key' => '',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,insert,update,references',
            ),
            array(
                'Field' => 'bar',
                'Type' => 'longtext',
                'Extra' => '',
                'Null' => 'Yes',
                'Key' => '',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,update,references',
            )
        );
        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            array(), $table_columns, array(), false, array(), '', '',
            '', true, array(), $o_rows, $tabindex, 2, false, 0,
            array(), 0, 0, 'table', 'db', 0, array(), 0, '',
            array(), array('wc')
        );
        $this->assertContains(
            'foo',
            $actual
        );
        $this->assertContains(
            '<textarea name="fields[37b51d194a7513e45b56f6524f2d51f2]"',
            $actual
        );
    }
}
