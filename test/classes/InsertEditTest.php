<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Warning;
use PhpMyAdmin\EditField;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use ReflectionProperty;

use function hash;
use function is_object;
use function is_scalar;
use function is_string;
use function mb_substr;
use function md5;
use function password_verify;
use function sprintf;
use function strval;

use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_TINY;

/**
 * @covers \PhpMyAdmin\InsertEdit
 * @group medium
 */
class InsertEditTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    private InsertEdit $insertEdit;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setLanguage();

        parent::setGlobalConfig();

        parent::setTheme();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
        $GLOBALS['server'] = 1;
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
        $this->insertEdit = new InsertEdit(
            $this->dbi,
            new Relation($this->dbi),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $this->dbi->setVersion([
            '@@version' => '10.9.3-MariaDB-1:10.9.3+maria~ubu2204',
            '@@version_comment' => 'mariadb.org binary distribution',
        ]);
    }

    /**
     * Teardown all objects
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $response = new ReflectionProperty(ResponseRenderer::class, 'instance');
        $response->setValue(null);
    }

    /**
     * Test for getFormParametersForInsertForm
     */
    public function testGetFormParametersForInsertForm(): void
    {
        $whereClause = ['foo' => 'bar ', '1' => ' test'];
        $_POST['clause_is_unique'] = false;
        $_POST['sql_query'] = 'SELECT a';
        $GLOBALS['goto'] = 'index.php';

        $result = $this->insertEdit->getFormParametersForInsertForm(
            'dbname',
            'tablename',
            [],
            $whereClause,
            'localhost',
        );

        $this->assertEquals(
            [
                'db' => 'dbname',
                'table' => 'tablename',
                'goto' => 'index.php',
                'err_url' => 'localhost',
                'sql_query' => 'SELECT a',
                'where_clause[foo]' => 'bar',
                'where_clause[1]' => 'test',
                'clause_is_unique' => false,
            ],
            $result,
        );
    }

    /**
     * Test for getFormParametersForInsertForm
     */
    public function testGetFormParametersForInsertFormGet(): void
    {
        $whereClause = ['foo' => 'bar ', '1' => ' test'];
        $_GET['clause_is_unique'] = false;
        $_GET['sql_query'] = 'SELECT a';
        $_GET['sql_signature'] = Core::signSqlQuery($_GET['sql_query']);
        $GLOBALS['goto'] = 'index.php';

        $result = $this->insertEdit->getFormParametersForInsertForm(
            'dbname',
            'tablename',
            [],
            $whereClause,
            'localhost',
        );

        $this->assertEquals(
            [
                'db' => 'dbname',
                'table' => 'tablename',
                'goto' => 'index.php',
                'err_url' => 'localhost',
                'sql_query' => 'SELECT a',
                'where_clause[foo]' => 'bar',
                'where_clause[1]' => 'test',
                'clause_is_unique' => false,
            ],
            $result,
        );
    }

    /**
     * Test for analyzeWhereClauses
     */
    public function testAnalyzeWhereClause(): void
    {
        $clauses = ['a=1', 'b="fo\o"'];

        $resultStub1 = $this->createMock(DummyResult::class);
        $resultStub2 = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->exactly(2))
            ->method('query')
            ->willReturnOnConsecutiveCalls($resultStub1, $resultStub2);

        $resultStub1->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue(['assoc1']));

        $resultStub2->expects($this->once())
            ->method('fetchAssoc')
            ->will($this->returnValue(['assoc2']));

        $dbi->expects($this->exactly(2))
            ->method('getFieldsMeta')
            ->willReturnOnConsecutiveCalls(
                [],
                [],
            );

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'analyzeWhereClauses',
            [$clauses, 'table', 'db'],
        );

        $this->assertSame(
            [['a=1', 'b="fo\\\\o"'], [$resultStub1, $resultStub2], [['assoc1'], ['assoc2']], false],
            $result,
        );
    }

    /**
     * Test for showEmptyResultMessageOrSetUniqueCondition
     */
    public function testShowEmptyResultMessageOrSetUniqueCondition(): void
    {
        $meta = FieldHelper::fromArray([
            'type' => MYSQLI_TYPE_DECIMAL,
            'flags' => MYSQLI_PRI_KEY_FLAG,
            'table' => 'table',
            'orgname' => 'orgname',
        ]);

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue([$meta]));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'showEmptyResultMessageOrSetUniqueCondition',
            [['1' => ['1' => 1]], 1, [], 'SELECT', ['1' => $resultStub]],
        );

        $this->assertTrue($result);

        // case 2
        $GLOBALS['cfg']['ShowSQL'] = false;

        $responseMock = $this->getMockBuilder(ResponseRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addHtml'])
            ->getMock();

        $restoreInstance = ResponseRenderer::getInstance();
        $response = new ReflectionProperty(ResponseRenderer::class, 'instance');
        $response->setValue($responseMock);

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'showEmptyResultMessageOrSetUniqueCondition',
            [[false], 0, ['1'], 'SELECT', ['1' => 'result1']],
        );

        $response->setValue($restoreInstance);

        $this->assertFalse($result);
    }

    public function testLoadFirstRow(): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `db`.`table` LIMIT 1;')
            ->will($this->returnValue($resultStub));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'loadFirstRow',
            ['table', 'db'],
        );

        $this->assertEquals($resultStub, $result);
    }

    /** @return list<array{int|string, array<false>}> */
    public static function dataProviderConfigValueInsertRows(): array
    {
        return [[2, [false, false]], ['2', [false, false]], [3, [false, false, false]], ['3', [false, false, false]]];
    }

    /**
     * Test for loadFirstRow
     *
     * @param array<false> $rowsValue
     *
     * @dataProvider dataProviderConfigValueInsertRows
     */
    public function testGetInsertRows(string|int $configValue, array $rowsValue): void
    {
        $GLOBALS['cfg']['InsertRows'] = $configValue;

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getInsertRows',
            [],
        );

        $this->assertEquals($rowsValue, $result);
    }

    /**
     * Test for showTypeOrFunction
     */
    public function testShowTypeOrFunction(): void
    {
        $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $urlParams = ['ShowFunctionFields' => 2];

        $result = $this->insertEdit->showTypeOrFunction('function', $urlParams, false);

        $this->assertStringContainsString('index.php?route=/table/change', $result);
        $this->assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result,
        );
        $this->assertStringContainsString('Function', $result);

        // case 2
        $result = $this->insertEdit->showTypeOrFunction('function', $urlParams, true);

        $this->assertStringContainsString('index.php?route=/table/change', $result);
        $this->assertStringContainsString(
            'ShowFunctionFields=0&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result,
        );
        $this->assertStringContainsString('Function', $result);

        // case 3
        $result = $this->insertEdit->showTypeOrFunction('type', $urlParams, false);

        $this->assertStringContainsString('index.php?route=/table/change', $result);
        $this->assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result,
        );
        $this->assertStringContainsString('Type', $result);

        // case 4
        $result = $this->insertEdit->showTypeOrFunction('type', $urlParams, true);

        $this->assertStringContainsString('index.php?route=/table/change', $result);
        $this->assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=0&goto=index.php%3Froute%3D%2Fsql',
            $result,
        );
        $this->assertStringContainsString('Type', $result);
    }

    /**
     * Test for analyzeTableColumnsArray
     */
    public function testAnalyzeTableColumnsArray(): void
    {
        $column = ['Field' => '1<2', 'Field_md5' => 'pswd', 'Type' => 'float(10, 1)'];

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'analyzeTableColumnsArray',
            [$column, []],
        );

        $this->assertEquals($result['Field_md5'], '4342210df36bf2ff2c4e2a997a6d4089');

        $this->assertEquals($result['True_Type'], 'float');

        $this->assertEquals($result['len'], 100);

        $this->assertEquals($result['Field_title'], '1&lt;2');

        $this->assertEquals($result['is_binary'], false);

        $this->assertEquals($result['is_blob'], false);

        $this->assertEquals($result['is_char'], false);

        $this->assertEquals($result['pma_type'], 'float(10, 1)');

        $this->assertEquals($result['wrap'], ' text-nowrap');

        $this->assertEquals($result['Field'], '1<2');
    }

    /**
     * Test for getColumnTitle
     */
    public function testGetColumnTitle(): void
    {
        $column = [];
        $column['Field'] = 'f1<';

        $this->assertEquals(
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getColumnTitle',
                [$column, []],
            ),
            'f1&lt;',
        );

        $comments = [];
        $comments['f1<'] = 'comment>';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnTitle',
            [$column, $comments],
        );

        $result = $this->parseString($result);

        $this->assertStringContainsString('title="comment&gt;"', $result);

        $this->assertStringContainsString('f1&lt;', $result);
    }

    /**
     * Test for isColumn
     */
    public function testIsColumn(): void
    {
        $column = [];
        $types = ['binary', 'varbinary'];

        $column['Type'] = 'binaryfoo';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'Binaryfoo';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'varbinaryfoo';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'barbinaryfoo';
        $this->assertFalse($this->insertEdit->isColumn($column, $types));

        $types = ['char', 'varchar'];

        $column['Type'] = 'char(10)';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'VarChar(20)';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'foochar';
        $this->assertFalse($this->insertEdit->isColumn($column, $types));

        $types = ['blob', 'tinyblob', 'mediumblob', 'longblob'];

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
     * Test for getNullifyCodeForNullColumn
     */
    public function testGetNullifyCodeForNullColumn(): void
    {
        $column = $foreignData = [];
        $foreigners = ['foreign_keys_data' => []];
        $column['Field'] = 'f';
        $column['True_Type'] = 'enum';
        $column['Type'] = 'ababababababababababa';
        $this->assertEquals(
            '1',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [$column, $foreigners, []],
            ),
        );

        $column['True_Type'] = 'enum';
        $column['Type'] = 'abababababababababab';
        $this->assertEquals(
            '2',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [$column, $foreigners, []],
            ),
        );

        $column['True_Type'] = 'set';
        $this->assertEquals(
            '3',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [$column, $foreigners, []],
            ),
        );

        $column['True_Type'] = '';
        $foreigners['f'] = ['something'/* What should the mocked value actually be? */];
        $foreignData['foreign_link'] = '';
        $this->assertEquals(
            '4',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [$column, $foreigners, $foreignData],
            ),
        );
    }

    /**
     * Test for getTextarea
     */
    public function testGetTextarea(): void
    {
        $GLOBALS['cfg']['TextareaRows'] = 20;
        $GLOBALS['cfg']['TextareaCols'] = 10;
        $GLOBALS['cfg']['CharTextareaRows'] = 7;
        $GLOBALS['cfg']['CharTextareaCols'] = 1;
        $GLOBALS['cfg']['LimitChars'] = 20;

        $column = [];
        $column['is_char'] = true;
        $column['Type'] = 'char(10)';
        $column['True_Type'] = 'char';
        (new ReflectionProperty(InsertEdit::class, 'fieldIndex'))->setValue($this->insertEdit, 2);
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getTextarea',
            [$column, 'a', 'b', '', 'abc/', 'foobar', 'CHAR'],
        );

        $result = $this->parseString($result);

        $this->assertStringContainsString(
            '<textarea name="fieldsb" class="char charField" '
            . 'data-maxlength="10" rows="7" cols="1" dir="abc/" '
            . 'id="field_2_3" tabindex="2" data-type="CHAR">',
            $result,
        );
    }

    /**
     * Test for getHtmlInput
     */
    public function testGetHTMLinput(): void
    {
        $GLOBALS['cfg']['ShowFunctionFields'] = true;
        $column = [];
        $column['pma_type'] = 'date';
        $column['True_Type'] = 'date';
        (new ReflectionProperty(InsertEdit::class, 'fieldIndex'))->setValue($this->insertEdit, 23);
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlInput',
            [$column, 'a', 'b', 30, 'c', 'DATE'],
        );

        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datefield" onchange="c" tabindex="23" id="field_23_3">',
            $result,
        );

        // case 2 datetime
        $column['pma_type'] = 'datetime';
        $column['True_Type'] = 'datetime';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlInput',
            [$column, 'a', 'b', 30, 'c', 'DATE'],
        );
        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datetimefield" onchange="c" tabindex="23" id="field_23_3">',
            $result,
        );

        // case 3 timestamp
        $column['pma_type'] = 'timestamp';
        $column['True_Type'] = 'timestamp';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlInput',
            [$column, 'a', 'b', 30, 'c', 'DATE'],
        );
        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datetimefield" onchange="c" tabindex="23" id="field_23_3">',
            $result,
        );

        // case 4 int
        $column['pma_type'] = 'int';
        $column['True_Type'] = 'int';
        $column['Type'] = 'int(11)';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlInput',
            [$column, 'a', 'b', 11, 'c', 'INT'],
        );
        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="11" min="-2147483648" max="2147483647" data-type="INT"'
            . ' class="textfield" onchange="c" tabindex="23" inputmode="numeric" id="field_23_3">',
            $result,
        );
    }

    /**
     * Test for getMaxUploadSize
     */
    public function testGetMaxUploadSize(): void
    {
        $GLOBALS['config']->set('max_upload_size', 257);
        $pmaType = 'tinyblob';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getMaxUploadSize',
            [$pmaType],
        );

        $this->assertEquals("(Max: 256B)\n", $result);

        // case 2
        $GLOBALS['config']->set('max_upload_size', 250);
        $pmaType = 'tinyblob';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getMaxUploadSize',
            [$pmaType],
        );

        $this->assertEquals("(Max: 250B)\n", $result);
    }

    /**
     * Test for getValueColumnForOtherDatatypes
     */
    public function testGetValueColumnForOtherDatatypes(): void
    {
        $column = [];
        $column['len'] = 20;
        $column['is_char'] = true;
        $column['Type'] = 'char(25)';
        $column['True_Type'] = 'char';
        $GLOBALS['cfg']['CharEditing'] = '';
        $GLOBALS['cfg']['MaxSizeForInputField'] = 30;
        $GLOBALS['cfg']['MinSizeForInputField'] = 10;
        $GLOBALS['cfg']['TextareaRows'] = 20;
        $GLOBALS['cfg']['TextareaCols'] = 10;
        $GLOBALS['cfg']['CharTextareaRows'] = 7;
        $GLOBALS['cfg']['CharTextareaCols'] = 1;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $extractedColumnSpec = [];
        $extractedColumnSpec['spec_in_brackets'] = '25';
        (new ReflectionProperty(InsertEdit::class, 'fieldIndex'))->setValue($this->insertEdit, 22);
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getValueColumnForOtherDatatypes',
            [
                $column,
                'defchar',
                'a',
                'b',
                'c',
                '&lt;',
                '/',
                '&lt;',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $this->assertEquals(
            "a\na\n"
            . '<textarea name="fieldsb" class="char charField" '
            . 'data-maxlength="25" rows="7" cols="1" dir="/" '
            . 'id="field_22_3" onchange="c" tabindex="22" data-type="CHAR">'
            . '&lt;</textarea>',
            $result,
        );

        // case 2: (else)
        $column['is_char'] = false;
        $column['Extra'] = 'auto_increment';
        $column['pma_type'] = 'timestamp';
        $column['True_Type'] = 'timestamp';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getValueColumnForOtherDatatypes',
            [
                $column,
                'defchar',
                'a',
                'b',
                'c',
                '&lt;',
                '/',
                '&lt;',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $this->assertEquals(
            "a\n"
            . '<input type="text" name="fieldsb" value="&lt;" size="20" data-type="'
            . 'DATE" class="textfield datetimefield" onchange="c" tabindex="22" id="field_22_3"'
            . '><input type="hidden" name="auto_incrementb" value="1">'
            . '<input type="hidden" name="fields_typeb" value="timestamp">',
            $result,
        );

        // case 3: (else -> datetime)
        $column['pma_type'] = 'datetime';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getValueColumnForOtherDatatypes',
            [
                $column,
                'defchar',
                'a',
                'b',
                'c',
                '&lt;',
                '/',
                '&lt;',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $result = $this->parseString($result);

        $this->assertStringContainsString('<input type="hidden" name="fields_typeb" value="datetime">', $result);

        // case 4: (else -> date)
        $column['pma_type'] = 'date';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getValueColumnForOtherDatatypes',
            [
                $column,
                'defchar',
                'a',
                'b',
                'c',
                '&lt;',
                '/',
                '&lt;',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $result = $this->parseString($result);

        $this->assertStringContainsString('<input type="hidden" name="fields_typeb" value="date">', $result);

        // case 5: (else -> bit)
        $column['True_Type'] = 'bit';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getValueColumnForOtherDatatypes',
            [
                $column,
                'defchar',
                'a',
                'b',
                'c',
                '&lt;',
                '/',
                '&lt;',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $result = $this->parseString($result);

        $this->assertStringContainsString('<input type="hidden" name="fields_typeb" value="bit">', $result);

        // case 6: (else -> uuid)
        $column['True_Type'] = 'uuid';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getValueColumnForOtherDatatypes',
            [
                $column,
                'defchar',
                'a',
                'b',
                'c',
                '&lt;',
                '/',
                '&lt;',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $result = $this->parseString($result);

        $this->assertStringContainsString('<input type="hidden" name="fields_typeb" value="uuid">', $result);
    }

    /**
     * Test for getColumnSize
     */
    public function testGetColumnSize(): void
    {
        $column = [];
        $column['is_char'] = true;
        $specInBrackets = '45';
        $GLOBALS['cfg']['MinSizeForInputField'] = 30;
        $GLOBALS['cfg']['MaxSizeForInputField'] = 40;

        $this->assertEquals(
            40,
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getColumnSize',
                [$column, $specInBrackets],
            ),
        );

        $this->assertEquals('textarea', $GLOBALS['cfg']['CharEditing']);

        // case 2
        $column['is_char'] = false;
        $column['len'] = 20;
        $this->assertEquals(
            30,
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getColumnSize',
                [$column, $specInBrackets],
            ),
        );
    }

    /**
     * Test for getContinueInsertionForm
     */
    public function testGetContinueInsertionForm(): void
    {
        $whereClauseArray = ['a<b'];
        $GLOBALS['cfg']['InsertRows'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['goto'] = 'index.php';
        $_POST['where_clause'] = true;
        $_POST['sql_query'] = 'SELECT 1';

        $result = $this->insertEdit->getContinueInsertionForm('tbl', 'db', $whereClauseArray, 'localhost');

        $this->assertStringContainsString(
            '<form id="continueForm" method="post" action="' . Url::getFromRoute('/table/replace')
            . '" name="continueForm">',
            $result,
        );

        $this->assertStringContainsString('<input type="hidden" name="db" value="db">', $result);

        $this->assertStringContainsString('<input type="hidden" name="table" value="tbl">', $result);

        $this->assertStringContainsString('<input type="hidden" name="goto" value="index.php">', $result);

        $this->assertStringContainsString('<input type="hidden" name="err_url" value="localhost">', $result);

        $this->assertStringContainsString('<input type="hidden" name="sql_query" value="SELECT 1">', $result);

        $this->assertStringContainsString('<input type="hidden" name="where_clause[0]" value="a&lt;b">', $result);
    }

    public function testIsWhereClauseNumeric(): void
    {
        $this->assertFalse(InsertEdit::isWhereClauseNumeric(null));
        $this->assertFalse(InsertEdit::isWhereClauseNumeric(''));
        $this->assertFalse(InsertEdit::isWhereClauseNumeric([]));
        $this->assertTrue(InsertEdit::isWhereClauseNumeric('`actor`.`actor_id` = 1'));
        $this->assertTrue(InsertEdit::isWhereClauseNumeric(['`actor`.`actor_id` = 1']));
        $this->assertFalse(InsertEdit::isWhereClauseNumeric('`actor`.`first_name` = \'value\''));
        $this->assertFalse(InsertEdit::isWhereClauseNumeric(['`actor`.`first_name` = \'value\'']));
    }

    /**
     * Test for getHeadAndFootOfInsertRowTable
     */
    public function testGetHeadAndFootOfInsertRowTable(): void
    {
        $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = true;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $urlParams = ['ShowFunctionFields' => 2];

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHeadAndFootOfInsertRowTable',
            [$urlParams],
        );

        $result = $this->parseString($result);

        $this->assertStringContainsString('index.php?route=/table/change', $result);

        $this->assertStringContainsString('ShowFunctionFields=1&ShowFieldTypesInDataEditView=0', $result);

        $this->assertStringContainsString('ShowFunctionFields=0&ShowFieldTypesInDataEditView=1', $result);
    }

    /**
     * Test for getSpecialCharsAndBackupFieldForExistingRow
     */
    public function testGetSpecialCharsAndBackupFieldForExistingRow(): void
    {
        $column = $currentRow = $extractedColumnSpec = [];
        $column['Field'] = 'f';
        $currentRow['f'] = null;
        $_POST['default_action'] = 'insert';
        $column['Key'] = 'PRI';
        $column['Extra'] = 'fooauto_increment';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [$currentRow, $column, [], [], 'a', false],
        );

        $this->assertEquals(
            [true, null, null, null, '<input type="hidden" name="fields_preva" value="">'],
            $result,
        );

        // Case 2 (bit)
        unset($_POST['default_action']);

        $currentRow['f'] = '123';
        $extractedColumnSpec['spec_in_brackets'] = '20';
        $column['True_Type'] = 'bit';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, [], 'a', false],
        );

        $this->assertEquals(
            [false, '', '00000000000001111011', null, '<input type="hidden" name="fields_preva" value="123">'],
            $result,
        );

        $currentRow['f'] = 'abcd';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, [], 'a', true],
        );

        $this->assertEquals(
            [false, '', 'abcd', null, '<input type="hidden" name="fields_preva" value="abcd">'],
            $result,
        );

        // Case 3 (bit)
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $currentRow['f'] = '123';
        $extractedColumnSpec['spec_in_brackets'] = '20';
        $column['True_Type'] = 'int';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, ['int'], 'a', false],
        );

        $this->assertEquals(
            [false, '', "'',", null, '<input type="hidden" name="fields_preva" value="\'\',">'],
            $result,
        );

        // Case 4 (else)
        $column['is_binary'] = false;
        $column['is_blob'] = true;
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $currentRow['f'] = '11001';
        $extractedColumnSpec['spec_in_brackets'] = '20';
        $column['True_Type'] = 'char';
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, ['int'], 'a', false],
        );

        $this->assertEquals(
            [
                false,
                '3131303031',
                '3131303031',
                '3131303031',
                '<input type="hidden" name="fields_preva" value="3131303031">',
            ],
            $result,
        );

        // Case 5
        $currentRow['f'] = "11001\x00";

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, ['int'], 'a', false],
        );

        $this->assertEquals(
            [
                false,
                '313130303100',
                '313130303100',
                '313130303100',
                '<input type="hidden" name="fields_preva" value="313130303100">',
            ],
            $result,
        );
    }

    /**
     * Test for getSpecialCharsForInsertingMode
     *
     * @param array  $column   Column parameters
     * @param string $expected Expected result
     * @psalm-param array<string, string|bool|null> $column
     *
     * @dataProvider providerForTestGetSpecialCharsForInsertingMode
     */
    public function testGetSpecialCharsForInsertingMode(
        array $column,
        string $expected,
    ): void {
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        /** @var string $result */
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsForInsertingMode',
            [$column['Default'] ?? null, $column['True_Type']],
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for test getSpecialCharsForInsertingMode()
     *
     * @return array<string, array{array<string, string|bool|null>, string}>
     */
    public static function providerForTestGetSpecialCharsForInsertingMode(): array
    {
        return [
            'bit' => [
                ['True_Type' => 'bit', 'Default' => 'b\'101\'', 'is_binary' => true],
                '101',
            ],
            'char' => [['True_Type' => 'char', 'is_binary' => true], ''],
            'time with CURRENT_TIMESTAMP value' => [
                ['True_Type' => 'time', 'Default' => 'CURRENT_TIMESTAMP'],
                'CURRENT_TIMESTAMP',
            ],
            'time with current_timestamp() value' => [
                ['True_Type' => 'time', 'Default' => 'current_timestamp()'],
                'current_timestamp()',
            ],
            'time with no dot value' => [
                ['True_Type' => 'time', 'Default' => '10'],
                '10.000000',
            ],
            'time with dot value' => [
                ['True_Type' => 'time', 'Default' => '10.08'],
                '10.080000',
            ],
            'any text with escape text default' => [
                ['True_Type' => 'text', 'Default' => '"lorem\"ipsem"'],
                'lorem"ipsem',
            ],
            'varchar with html special chars' => [
                ['True_Type' => 'varchar', 'Default' => 'hello world<br><b>lorem</b> ipsem'],
                'hello world&lt;br&gt;&lt;b&gt;lorem&lt;/b&gt; ipsem',
            ],
        ];
    }

    /**
     * Test for setSessionForEditNext
     */
    public function testSetSessionForEditNext(): void
    {
        $meta = FieldHelper::fromArray([
            'type' => MYSQLI_TYPE_DECIMAL,
            'flags' => MYSQLI_PRI_KEY_FLAG,
            'orgname' => 'orgname',
            'table' => 'table',
            'orgtable' => 'table',
        ]);

        $row = ['1' => 1];

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `db`.`table` WHERE `a` > 2 LIMIT 1;')
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('fetchRow')
            ->will($this->returnValue($row));

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue([$meta]));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );
        $this->insertEdit->setSessionForEditNext('`a` = 2');

        $this->assertEquals('CONCAT(`table`.`orgname`) IS NULL', $_SESSION['edit_next']);
    }

    /**
     * Test for getGotoInclude
     */
    public function testGetGotoInclude(): void
    {
        $GLOBALS['goto'] = '123.php';
        $GLOBALS['table'] = '';

        $this->assertEquals(
            '/database/sql',
            $this->insertEdit->getGotoInclude('index'),
        );

        $GLOBALS['table'] = 'tbl';
        $this->assertEquals(
            '/table/sql',
            $this->insertEdit->getGotoInclude('index'),
        );

        $GLOBALS['goto'] = 'index.php?route=/database/sql';

        $this->assertEquals(
            '/database/sql',
            $this->insertEdit->getGotoInclude('index'),
        );

        $this->assertEquals('', $GLOBALS['table']);

        $_POST['after_insert'] = 'new_insert';
        $this->assertEquals(
            '/table/change',
            $this->insertEdit->getGotoInclude('index'),
        );
    }

    /**
     * Test for getErrorUrl
     */
    public function testGetErrorUrl(): void
    {
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $this->assertEquals(
            'index.php?route=/table/change&lang=en',
            $this->insertEdit->getErrorUrl([]),
        );

        $_POST['err_url'] = 'localhost';
        $this->assertEquals(
            'localhost',
            $this->insertEdit->getErrorUrl([]),
        );
    }

    /**
     * Test for buildSqlQuery
     */
    public function testBuildSqlQuery(): void
    {
        $queryFields = ['a', 'b'];
        $valueSets = ['1', '2'];

        $this->assertEquals(
            'INSERT IGNORE INTO `table` (a, b) VALUES (1), (2)',
            $this->insertEdit->buildInsertSqlQuery('table', true, $queryFields, $valueSets),
        );

        $this->assertEquals(
            'INSERT INTO `table` (a, b) VALUES (1), (2)',
            $this->insertEdit->buildInsertSqlQuery('table', false, $queryFields, $valueSets),
        );
    }

    /**
     * Test for executeSqlQuery
     */
    public function testExecuteSqlQuery(): void
    {
        $query = ['SELECT * FROM `test_db`.`test_table`;', 'SELECT * FROM `test_db`.`test_table_yaml`;'];
        $GLOBALS['cfg']['IgnoreMultiSubmitErrors'] = false;
        $_POST['submit_type'] = '';

        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );
        $result = $this->insertEdit->executeSqlQuery($query);

        $this->assertEquals([], $result[3]);
    }

    /**
     * Test for executeSqlQuery
     */
    public function testExecuteSqlQueryWithTryQuery(): void
    {
        $query = ['SELECT * FROM `test_db`.`test_table`;', 'SELECT * FROM `test_db`.`test_table_yaml`;'];
        $GLOBALS['cfg']['IgnoreMultiSubmitErrors'] = true;
        $_POST['submit_type'] = '';

        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );
        $result = $this->insertEdit->executeSqlQuery($query);

        $this->assertEquals([], $result[3]);
    }

    /**
     * Test for getWarningMessages
     */
    public function testGetWarningMessages(): void
    {
        $warnings = [
            Warning::fromArray(['Level' => 'Error', 'Code' => '1001', 'Message' => 'Message 1']),
            Warning::fromArray(['Level' => 'Warning', 'Code' => '1002', 'Message' => 'Message 2']),
        ];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getWarnings')
            ->will($this->returnValue($warnings));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $result = (array) $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getWarningMessages',
            [],
        );

        $this->assertEquals(['Error: #1001 Message 1', 'Warning: #1002 Message 2'], $result);
    }

    /**
     * Test for getDisplayValueForForeignTableColumn
     */
    public function testGetDisplayValueForForeignTableColumn(): void
    {
        $map = [];
        $map['f']['foreign_db'] = 'information_schema';
        $map['f']['foreign_table'] = 'TABLES';
        $map['f']['foreign_field'] = 'f';

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with('SELECT `TABLE_COMMENT` FROM `information_schema`.`TABLES` WHERE `f`=1')
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numRows')
            ->will($this->returnValue(2));

        $resultStub->expects($this->once())
            ->method('fetchValue')
            ->with(0)
            ->will($this->returnValue('2'));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $result = $this->insertEdit->getDisplayValueForForeignTableColumn('=1', $map, 'f');

        $this->assertEquals(2, $result);
    }

    /**
     * Test for getLinkForRelationalDisplayField
     */
    public function testGetLinkForRelationalDisplayField(): void
    {
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_SESSION['tmpval']['relational_display'] = 'K';
        $map = [];
        $map['f']['foreign_db'] = 'information_schema';
        $map['f']['foreign_table'] = 'TABLES';
        $map['f']['foreign_field'] = 'f';

        $result = $this->insertEdit->getLinkForRelationalDisplayField($map, 'f', '=1', 'a>', 'b<');

        $sqlSignature = Core::signSqlQuery('SELECT * FROM `information_schema`.`TABLES` WHERE `f`=1');

        $this->assertEquals(
            '<a href="index.php?route=/sql&db=information_schema&table=TABLES&pos=0&'
            . 'sql_signature=' . $sqlSignature . '&'
            . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
            . '+%60f%60%3D1&lang=en" title="a&gt;">b&lt;</a>',
            $result,
        );

        $_SESSION['tmpval']['relational_display'] = 'D';
        $result = $this->insertEdit->getLinkForRelationalDisplayField($map, 'f', '=1', 'a>', 'b<');

        $this->assertEquals(
            '<a href="index.php?route=/sql&db=information_schema&table=TABLES&pos=0&'
            . 'sql_signature=' . $sqlSignature . '&'
            . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
            . '+%60f%60%3D1&lang=en" title="b&lt;">a&gt;</a>',
            $result,
        );
    }

    /**
     * Test for transformEditedValues
     */
    public function testTransformEditedValues(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $editedValues = [['c' => 'cname']];
        $GLOBALS['cfg']['DefaultTransformations']['PreApPend'] = ['', ''];
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_POST['where_clause'] = '1';
        $_POST['where_clause_sign'] = Core::signSqlQuery($_POST['where_clause']);
        $transformation = ['transformation_options' => "'','option ,, quoted',abd"];
        $result = $this->insertEdit->transformEditedValues(
            'db',
            'table',
            $transformation,
            $editedValues,
            'Text_Plain_PreApPend.php',
            'c',
            ['a' => 'b'],
            'transformation',
        );

        $this->assertEquals(
            ['a' => 'b', 'transformations' => ['cnameoption ,, quoted']],
            $result,
        );
    }

    /**
     * Test for getQueryValuesForInsert
     */
    public function testGetQueryValuesForInsert(): void
    {
        // Simple insert
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                'fld',
                'foo',
                '',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals("'foo'", $result);

        // Test for file upload
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '0x123',
                '',
                false,
                false,
                false,
                '',
                null,
                null,
                true,
            ),
            false,
            '',
        );

        $this->assertEquals('0x123', $result);

        // Test functions
        $this->dummyDbi->addResult(
            'SELECT UUID()',
            [
                ['uuid1234'],// Minimal working setup for 2FA
            ],
        );

        // case 1
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '',
                '',
                false,
                false,
                false,
                'UUID',
                null,
                null,
                false,
            ),
            false,
            '',
        );

        $this->assertEquals("'uuid1234'", $result);

        // case 2
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                "'",
                '',
                false,
                false,
                false,
                'AES_ENCRYPT',
                '',
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals("AES_ENCRYPT('\\'','')", $result);

        // case 3
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                "'",
                '',
                false,
                false,
                false,
                'ABS',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals("ABS('\\'')", $result);

        // case 4
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '',
                '',
                false,
                false,
                false,
                'RAND',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals('RAND()', $result);

        // case 5
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                "a'c",
                '',
                false,
                false,
                false,
                'PHP_PASSWORD_HASH',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertTrue(password_verify("a'c", mb_substr($result, 1, -1)));

        // case 7
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField('', "'POINT(3 4)',4326", '', true, false, false, 'ST_GeomFromText', null, null, false),
            false,
            '',
        );
        $this->assertEquals('ST_GeomFromText(\'POINT(3 4)\',4326)', $result);

        // case 8
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField('', 'POINT(3 4),4326', '', true, false, false, 'ST_GeomFromText', null, null, false),
            false,
            '',
        );
        $this->assertEquals('ST_GeomFromText(\'POINT(3 4)\',4326)', $result);

        // case 9
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField('', "'POINT(3 4)'", '', true, false, false, 'ST_GeomFromText', null, null, false),
            false,
            '',
        );
        $this->assertEquals('ST_GeomFromText(\'POINT(3 4)\')', $result);

        // case 10
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField('', 'POINT(3 4)', '', true, false, false, 'ST_GeomFromText', null, null, false),
            false,
            '',
        );
        $this->assertEquals('ST_GeomFromText(\'POINT(3 4)\')', $result);

        // Test different data types

        // Datatype: protected copied from the databse
        $GLOBALS['table'] = 'test_table';
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                'name',
                '',
                'protected',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            true,
            '`id` = 4',
        );
        $this->assertEquals('0x313031', $result);

        // An empty value for auto increment column should be converted to NULL
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '', // empty for null
                '',
                true,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals('NULL', $result);

        // Simple empty value
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '',
                '',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals("''", $result);

        // Datatype: set
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '', // doesn't matter what the value is
                'set',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals("''", $result);

        // Datatype: protected with no value should produce an empty string
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '',
                'protected',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals('', $result);

        // Datatype: protected with null flag set
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '',
                'protected',
                false,
                true,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals('NULL', $result);

        // Datatype: bit
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '20\'12',
                'bit',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals("b'00010'", $result);

        // Datatype: date
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '20\'12',
                'date',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals("'20\\'12'", $result);

        // A NULL checkbox
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '',
                'set',
                false,
                true,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals('NULL', $result);

        // Datatype: protected but NULL checkbox was unchecked without uploading a file
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '',
                'protected',
                false,
                false,
                true, // was previously NULL
                '',
                null,
                null,
                false, // no upload
            ),
            false,
            '',
        );
        $this->assertEquals("''", $result);

        // Datatype: date with default value
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                'current_timestamp()',
                'date',
                false,
                false,
                true, // NULL should be ignored
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals('current_timestamp()', $result);

        // Datatype: hex without 0x
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '222aaafff',
                'hex',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals('0x222aaafff', $result);

        // Datatype: hex with 0x
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField(
                '',
                '0x222aaafff',
                'hex',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
            false,
            '',
        );
        $this->assertEquals('0x222aaafff', $result);
    }

    /**
     * Test for getQueryValuesForUpdate
     */
    public function testGetQueryValuesForUpdate(): void
    {
        // Simple update
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                'foo',
                '',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
        );
        $this->assertEquals("`fld` = 'foo'", $result);

        // Update of null when it was null previously
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                '', // null fields will have no value
                '',
                false,
                true,
                true,
                '',
                null,
                null,
                false,
            ),
        );
        $this->assertEquals('', $result);

        // Update of null when it was NOT null previously
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                '', // null fields will have no value
                '',
                false,
                true,
                false,
                '',
                null,
                '', // in edit mode the previous value will be empty string
                false,
            ),
        );
        $this->assertEquals('`fld` = NULL', $result);

        // Update to NOT null when it was null previously
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                "ab'c",
                '',
                false,
                false,
                true,
                '',
                null,
                null,
                false,
            ),
        );
        $this->assertEquals("`fld` = 'ab\'c'", $result);

        // Test to see if a zero-string is not ignored
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                '0', // zero-string provided as value
                '',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
        );
        $this->assertEquals("`fld` = '0'", $result);

        // Test to check if blob field that was left unchanged during edit will be ignored
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                '', // no value
                'protected',
                false,
                false,
                false,
                '',
                null,
                null,
                false,
            ),
        );
        $this->assertEquals('', $result);

        // Test to see if a field will be ignored if it the value is unchanged
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                "a'b",
                '',
                false,
                false,
                false,
                '',
                null,
                "a'b",
                false,
            ),
        );

        $this->assertEquals('', $result);

        // Test that an empty value uses the uuid function to generate a value
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                "''",
                'uuid',
                false,
                false,
                false,
                '',
                null,
                '',
                false,
            ),
        );

        $this->assertEquals('`fld` = uuid()', $result);

        // Test that the uuid function as a value uses the uuid function to generate a value
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                "'uuid()'",
                'uuid',
                false,
                false,
                false,
                '',
                null,
                '',
                false,
            ),
        );

        $this->assertEquals('`fld` = uuid()', $result);

        // Test that the uuid function as a value uses the uuid function to generate a value
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                'uuid()',
                'uuid',
                false,
                false,
                false,
                '',
                null,
                '',
                false,
            ),
        );

        $this->assertEquals('`fld` = uuid()', $result);

        // Test that the uuid type does not have a default value other than null when it is nullable
        $result = $this->insertEdit->getQueryValueForUpdate(
            new EditField(
                'fld',
                '',
                'uuid',
                false,
                true,
                false,
                '',
                null,
                '',
                false,
            ),
        );

        $this->assertEquals('`fld` = NULL', $result);
    }

    /**
     * Test for verifyWhetherValueCanBeTruncatedAndAppendExtraData
     */
    public function testVerifyWhetherValueCanBeTruncatedAndAppendExtraData(): void
    {
        $extraData = ['isNeedToRecheck' => true];

        $_POST['where_clause'] = [];
        $_POST['where_clause'][0] = 1;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->exactly(3))
            ->method('tryQuery')
            ->with('SELECT `table`.`a` FROM `db`.`table` WHERE 1')
            ->willReturn($resultStub);

        $meta1 = FieldHelper::fromArray(['type' => MYSQLI_TYPE_TINY]);
        $meta2 = FieldHelper::fromArray(['type' => MYSQLI_TYPE_TINY]);
        $meta3 = FieldHelper::fromArray(['type' => MYSQLI_TYPE_TIMESTAMP]);
        $dbi->expects($this->exactly(3))
            ->method('getFieldsMeta')
            ->will($this->onConsecutiveCalls([$meta1], [$meta2], [$meta3]));

        $resultStub->expects($this->exactly(3))
            ->method('fetchValue')
            ->will($this->onConsecutiveCalls(false, '123', '2013-08-28 06:34:14'));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extraData);

        $this->assertFalse($extraData['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extraData);

        $this->assertEquals('123', $extraData['truncatableFieldValue']);
        $this->assertTrue($extraData['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extraData);

        $this->assertEquals('2013-08-28 06:34:14.000000', $extraData['truncatableFieldValue']);
        $this->assertTrue($extraData['isNeedToRecheck']);
    }

    /**
     * Test for getTableColumns
     */
    public function testGetTableColumns(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('selectDb')
            ->with('db');

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('db', 'table')
            ->will($this->returnValue([['a' => 'b', 'c' => 'd'], ['e' => 'f', 'g' => 'h']]));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $result = $this->insertEdit->getTableColumns('db', 'table');

        $this->assertEquals(
            [['a' => 'b', 'c' => 'd'], ['e' => 'f', 'g' => 'h']],
            $result,
        );
    }

    /**
     * Test for determineInsertOrEdit
     */
    public function testDetermineInsertOrEdit(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resultStub = $this->createMock(DummyResult::class);

        $dbi->expects($this->exactly(2))
            ->method('query')
            ->will($this->returnValue($resultStub));

        $GLOBALS['dbi'] = $dbi;
        $_POST['where_clause'] = '1';
        $_SESSION['edit_next'] = '1';
        $_POST['ShowFunctionFields'] = true;
        $_POST['ShowFieldTypesInDataEditView'] = true;
        $_POST['after_insert'] = 'edit_next';
        $GLOBALS['cfg']['InsertRows'] = 2;
        $GLOBALS['cfg']['ShowSQL'] = false;
        $_POST['default_action'] = 'insert';

        $responseMock = $this->getMockBuilder(ResponseRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addHtml'])
            ->getMock();

        $restoreInstance = ResponseRenderer::getInstance();
        $response = new ReflectionProperty(ResponseRenderer::class, 'instance');
        $response->setValue($responseMock);

        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $result = $this->insertEdit->determineInsertOrEdit('1', 'db', 'table');

        $this->assertEquals(
            [false, null, [1], null, [$resultStub], [[]], false, 'edit_next'],
            $result,
        );

        // case 2
        unset($_POST['where_clause']);
        unset($_SESSION['edit_next']);
        $_POST['default_action'] = '';

        $result = $this->insertEdit->determineInsertOrEdit(null, 'db', 'table');

        $response->setValue($restoreInstance);

        $this->assertEquals(
            [true, null, [], null, $resultStub, [false, false], false, 'edit_next'],
            $result,
        );
    }

    /**
     * Test for getCommentsMap
     */
    public function testGetCommentsMap(): void
    {
        $GLOBALS['cfg']['ShowPropertyComments'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getColumns')
            ->with('db', 'table', true)
            ->will(
                $this->returnValue(
                    [['Comment' => 'b', 'Field' => 'd']],
                ),
            );

        $dbi->expects($this->any())
            ->method('getTable')
            ->will(
                $this->returnValue(
                    new Table('table', 'db', $GLOBALS['dbi']),
                ),
            );

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit(
            $GLOBALS['dbi'],
            new Relation($GLOBALS['dbi']),
            new Transformations(),
            new FileListing(),
            new Template(),
        );

        $this->assertEquals(
            [],
            $this->insertEdit->getCommentsMap('db', 'table'),
        );

        $GLOBALS['cfg']['ShowPropertyComments'] = true;

        $this->assertEquals(
            ['d' => 'b'],
            $this->insertEdit->getCommentsMap('db', 'table'),
        );
    }

    /**
     * Test for getHtmlForIgnoreOption
     */
    public function testGetHtmlForIgnoreOption(): void
    {
        $expected = '<input type="checkbox" %sname="insert_ignore_1"'
            . ' id="insert_ignore_1"><label for="insert_ignore_1">'
            . 'Ignore</label><br>' . "\n";
        $checked = 'checked="checked" ';
        $this->assertEquals(
            sprintf($expected, $checked),
            $this->insertEdit->getHtmlForIgnoreOption(1),
        );

        $this->assertEquals(
            sprintf($expected, ''),
            $this->insertEdit->getHtmlForIgnoreOption(1, false),
        );
    }

    /**
     * Test for getHtmlForInsertEditFormColumn
     */
    public function testGetHtmlForInsertEditFormColumn(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $GLOBALS['plugin_scripts'] = [];
        $foreigners = ['foreign_keys_data' => []];
        $tableColumn = [
            'Field' => 'col',
            'Type' => 'varchar(20)',
            'Null' => 'Yes',
            'Privileges' => 'insert,update,select',
        ];
        $repopulate = [md5('col') => 'val'];
        $columnMime = [
            'input_transformation' => 'Input/Image_JPEG_Upload.php',
            'input_transformation_options' => '150',
        ];

        $resultStub = $this->createMock(DummyResult::class);
        $resultStub->expects($this->any())
            ->method('getFieldsMeta')
            ->will($this->returnValue([FieldHelper::fromArray(['type' => 0, 'length' => -1])]));

        // Test w/ input transformation
        $actual = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlForInsertEditFormColumn',
            [
                $tableColumn,
                0,
                [],
                $resultStub,
                false,
                [],
                0,
                false,
                $foreigners,
                'table',
                'db',
                0,
                '',
                '',
                $repopulate,
                $columnMime,
                '',
            ],
        );

        $actual = $this->parseString($actual);

        $this->assertStringContainsString('col', $actual);
        $this->assertStringContainsString('<option>AES_ENCRYPT</option>', $actual);
        $this->assertStringContainsString('<span class="column_type" dir="ltr">varchar(20)</span>', $actual);
        $this->assertStringContainsString('<tr class="noclick">', $actual);
        $this->assertStringContainsString('<span class="default_value hide">', $actual);
        $this->assertStringContainsString('<img src="" width="150" height="100" alt="Image preview here">', $actual);
        $this->assertStringContainsString(
            '<input type="file" '
            . 'name="fields_upload[multi_edit][0][d89e2ddb530bb8953b290ab0793aecb0]" '
            . 'accept="image/*" '
            . 'class="image-upload"'
            . '>',
            $actual,
        );

        // Test w/o input_transformation
        $tableColumn = [
            'Field' => 'qwerty',
            'Type' => 'datetime',
            'Null' => 'Yes',
            'Key' => '',
            'Extra' => '',
            'Default' => null,
            'Privileges' => 'insert,update,select',
        ];
        $repopulate = [md5('qwerty') => '12-10-14'];
        $actual = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlForInsertEditFormColumn',
            [
                $tableColumn,
                0,
                [],
                $resultStub,
                true,
                [],
                0,
                false,
                $foreigners,
                'table',
                'db',
                0,
                '',
                '',
                $repopulate,
                [],
                '',
            ],
        );

        $actual = $this->parseString($actual);

        $this->assertStringContainsString('qwerty', $actual);
        $this->assertStringContainsString('<option>UUID</option>', $actual);
        $this->assertStringContainsString('<span class="column_type" dir="ltr">datetime</span>', $actual);
        $this->assertStringContainsString(
            '<input type="text" name="fields[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="12-10-14.000000"',
            $actual,
        );

        $this->assertStringContainsString(
            '<select name="funcs[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]"'
            . ' onchange="return verificationsAfterFieldChange(\'d8578edf8458ce06fbc5bb76a58c5ca4\','
            . ' \'0\', \'datetime\')" id="field_1_1">',
            $actual,
        );
        $this->assertStringContainsString('<option>DATE</option>', $actual);

        $this->assertStringContainsString(
            '<input type="hidden" name="fields_null_prev[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]">',
            $actual,
        );

        $this->assertStringContainsString(
            '<input type="checkbox" class="checkbox_null"'
            . ' name="fields_null[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" id="field_1_2"'
            . ' aria-label="Use the NULL value for this column.">',
            $actual,
        );

        $this->assertStringContainsString(
            '<input type="hidden" class="nullify_code"'
            . ' name="nullify_code[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="5"',
            $actual,
        );

        $this->assertStringContainsString(
            '<input type="hidden" class="hashed_field"'
            . ' name="hashed_field[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" '
            . 'value="d8578edf8458ce06fbc5bb76a58c5ca4">',
            $actual,
        );

        $this->assertStringContainsString(
            '<input type="hidden" class="multi_edit"'
            . ' name="multi_edit[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="[multi_edit][0]"',
            $actual,
        );
    }

    /**
     * Test for getHtmlForInsertEditRow
     */
    public function testGetHtmlForInsertEditRow(): void
    {
        $GLOBALS['plugin_scripts'] = [];
        $GLOBALS['cfg']['LongtextDoubleTextarea'] = true;
        $GLOBALS['cfg']['CharEditing'] = 'input';
        $GLOBALS['cfg']['TextareaRows'] = 10;
        $GLOBALS['cfg']['TextareaCols'] = 11;
        $foreigners = ['foreign_keys_data' => []];
        $tableColumns = [
            [
                'Field' => 'test',
                'Extra' => '',
                'Type' => 'longtext',
                'Null' => 'Yes',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,insert,update,references',
            ],
        ];

        $resultStub = $this->createMock(DummyResult::class);
        $resultStub->expects($this->any())
            ->method('getFieldsMeta')
            ->will($this->returnValue([FieldHelper::fromArray(['type' => 0, 'length' => -1])]));

        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $tableColumns,
            [],
            $resultStub,
            false,
            [],
            false,
            $foreigners,
            'table',
            'db',
            0,
            'ltr',
            [],
            ['wc'],
        );
        $this->assertStringContainsString('test', $actual);
        $this->assertStringContainsString('<th>Column</th>', $actual);
        $this->assertStringContainsString('<a', $actual);
        $this->assertStringContainsString('<th class="w-50">Value</th>', $actual);
        $this->assertStringContainsString('<span class="column_type" dir="ltr">longtext</span>', $actual);
        $this->assertStringContainsString(
            '<textarea name="fields[multi_edit][0][098f6bcd4621d373cade4e832627b4f6]" id="field_1_3"'
                . ' data-type="CHAR" dir="ltr" rows="20" cols="22"',
            $actual,
        );
    }

    /**
     * Test for getHtmlForInsertEditRow based on the column privilges
     */
    public function testGetHtmlForInsertEditRowBasedOnColumnPrivileges(): void
    {
        $GLOBALS['plugin_scripts'] = [];
        $GLOBALS['cfg']['LongtextDoubleTextarea'] = true;
        $GLOBALS['cfg']['CharEditing'] = 'input';
        $foreigners = ['foreign_keys_data' => []];

        // edit
        $tableColumns = [
            [
                'Field' => 'foo',
                'Type' => 'longtext',
                'Extra' => '',
                'Null' => 'Yes',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,insert,update,references',
            ],
            [
                'Field' => 'bar',
                'Type' => 'longtext',
                'Extra' => '',
                'Null' => 'Yes',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,insert,references',
            ],
        ];

        $resultStub = $this->createMock(DummyResult::class);
        $resultStub->expects($this->any())
            ->method('getFieldsMeta')
            ->will($this->returnValue([
                FieldHelper::fromArray(['type' => 0, 'length' => -1]),
                FieldHelper::fromArray(['type' => 0, 'length' => -1]),
                FieldHelper::fromArray(['type' => 0, 'length' => -1]),
            ]));

        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $tableColumns,
            [],
            $resultStub,
            false,
            [],
            false,
            $foreigners,
            'table',
            'db',
            0,
            '',
            [],
            ['wc'],
        );
        $this->assertStringContainsString('foo', $actual);
        $this->assertStringContainsString('bar', $actual);

        // insert
        $tableColumns = [
            [
                'Field' => 'foo',
                'Type' => 'longtext',
                'Extra' => '',
                'Null' => 'Yes',
                'Key' => '',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,insert,update,references',
            ],
            [
                'Field' => 'bar',
                'Type' => 'longtext',
                'Extra' => '',
                'Null' => 'Yes',
                'Key' => '',
                'pma_type' => 'longtext',
                'True_Type' => 'longtext',
                'Privileges' => 'select,update,references',
            ],
            [
                'Field' => 'point',
                'Type' => 'point',
                'Extra' => '',
                'Null' => 'No',
                'Key' => '',
                'pma_type' => 'point',
                'True_Type' => 'point',
                'Privileges' => 'select,update,references',
            ],
        ];
        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $tableColumns,
            [],
            $resultStub,
            true,
            [],
            false,
            $foreigners,
            'table',
            'db',
            0,
            '',
            [],
            ['wc'],
        );
        $this->assertStringContainsString('foo', $actual);
        $this->assertStringContainsString(
            '<textarea name="fields[multi_edit][0][37b51d194a7513e45b56f6524f2d51f2]"',
            $actual,
        );
        $this->assertStringContainsString(
            '<a href="#" target="_blank"><span class="text-nowrap"><img src="themes/dot.'
            . 'gif" title="Edit/Insert" alt="Edit/Insert" class="icon ic_b_edit">&nbsp;Edit/Insert'
            . '</span></a>',
            $actual,
        );
    }

    /**
     * Convert mixed type value to string
     */
    private function parseString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) || is_scalar($value)) {
            return strval($value);
        }

        return '';
    }
}
