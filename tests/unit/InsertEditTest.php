<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Column;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\Warning;
use PhpMyAdmin\EditField;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\InsertEditColumn;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\TypeClass;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use ReflectionProperty;

use function hash;
use function is_object;
use function is_scalar;
use function is_string;
use function mb_substr;
use function md5;
use function password_verify;
use function sprintf;

use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_TINY;

#[CoversClass(InsertEdit::class)]
#[CoversClass(EditField::class)]
#[CoversClass(InsertEditColumn::class)]
#[Medium]
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

        $this->setLanguage();

        $this->setGlobalConfig();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        $config = Config::getInstance();
        $config->settings['ServerDefault'] = 1;
        Current::$database = 'db';
        Current::$table = 'table';
        $config->settings['LimitChars'] = 50;
        $config->settings['LongtextDoubleTextarea'] = false;
        $config->settings['ShowFieldTypesInDataEditView'] = true;
        $config->settings['ShowFunctionFields'] = true;
        $config->settings['ProtectBinary'] = 'blob';
        $config->settings['MaxSizeForInputField'] = 10;
        $config->settings['MinSizeForInputField'] = 2;
        $config->settings['TextareaRows'] = 5;
        $config->settings['TextareaCols'] = 4;
        $config->settings['CharTextareaRows'] = 5;
        $config->settings['CharTextareaCols'] = 6;
        $config->settings['AllowThirdPartyFraming'] = false;
        $config->set('SendErrorReports', 'ask');
        $config->settings['DefaultTabDatabase'] = '/database/structure';
        $config->settings['ShowDatabasesNavigationAsTree'] = true;
        $config->settings['DefaultTabTable'] = '/sql';
        $config->settings['NavigationTreeDefaultTabTable'] = '/table/structure';
        $config->settings['NavigationTreeDefaultTabTable2'] = '';
        $config->settings['Confirm'] = true;
        $config->settings['LoginCookieValidity'] = 1440;
        $config->settings['enable_drag_drop_import'] = true;
        $relation = new Relation($this->dbi);
        $this->insertEdit = new InsertEdit(
            $this->dbi,
            $relation,
            new Transformations($this->dbi, $relation),
            new FileListing(),
            new Template(),
            $config,
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
        $response->setValue(null, null);
        DatabaseInterface::$instance = null;
    }

    /**
     * Test for getFormParametersForInsertForm
     */
    public function testGetFormParametersForInsertForm(): void
    {
        $whereClause = ['foo' => 'bar ', '1' => ' test'];
        $_POST['clause_is_unique'] = false;
        $_POST['sql_query'] = 'SELECT a';
        UrlParams::$goto = 'index.php';

        $result = $this->insertEdit->getFormParametersForInsertForm('dbname', 'tablename', $whereClause, 'localhost');

        self::assertSame(
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
        UrlParams::$goto = 'index.php';

        $result = $this->insertEdit->getFormParametersForInsertForm('dbname', 'tablename', $whereClause, 'localhost');

        self::assertSame(
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

        $resultStub1 = self::createMock(DummyResult::class);
        $resultStub2 = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::exactly(2))
            ->method('query')
            ->willReturn($resultStub1, $resultStub2);

        $resultStub1->expects(self::once())
            ->method('fetchAssoc')
            ->willReturn(['assoc1']);

        $resultStub2->expects(self::once())
            ->method('fetchAssoc')
            ->willReturn(['assoc2']);

        $dbi->expects(self::exactly(2))
            ->method('getFieldsMeta')
            ->willReturn([], []);

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'analyzeWhereClauses',
            [$clauses, 'table', 'db'],
        );

        self::assertSame(
            [[$resultStub1, $resultStub2], [['assoc1'], ['assoc2']], false],
            $result,
        );
    }

    /**
     * Test for hasUniqueCondition
     */
    public function testHasUniqueCondition(): void
    {
        $meta = FieldHelper::fromArray([
            'type' => MYSQLI_TYPE_DECIMAL,
            'flags' => MYSQLI_PRI_KEY_FLAG,
            'table' => 'table',
            'orgname' => 'orgname',
        ]);

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->willReturn([$meta]);

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'hasUniqueCondition',
            [['1' => 1], $resultStub],
        );

        self::assertTrue($result);

        // TODO: Add test for false case
    }

    public function testLoadFirstRow(): void
    {
        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('query')
            ->with('SELECT * FROM `db`.`table` LIMIT 1;')
            ->willReturn($resultStub);

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'loadFirstRow',
            ['table', 'db'],
        );

        self::assertSame($resultStub, $result);
    }

    /** @return list<array{int, array<array<never>>}> */
    public static function dataProviderConfigValueInsertRows(): array
    {
        return [[2, [[], []]], [3, [[], [], []]]];
    }

    /**
     * Test for loadFirstRow
     *
     * @param array<array<never>> $rowsValue
     */
    #[DataProvider('dataProviderConfigValueInsertRows')]
    public function testGetInsertRows(int $configValue, array $rowsValue): void
    {
        Config::getInstance()->settings['InsertRows'] = $configValue;

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getInsertRows',
            [],
        );

        self::assertSame($rowsValue, $result);
    }

    /**
     * Test for showTypeOrFunction
     */
    public function testShowTypeOrFunction(): void
    {
        $config = Config::getInstance();
        $config->settings['ShowFieldTypesInDataEditView'] = true;
        $config->settings['ServerDefault'] = 1;
        $urlParams = ['ShowFunctionFields' => 2];

        $result = $this->insertEdit->showTypeOrFunction('function', $urlParams, false);

        self::assertStringContainsString('index.php?route=/table/change', $result);
        self::assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result,
        );
        self::assertStringContainsString('Function', $result);

        // case 2
        $result = $this->insertEdit->showTypeOrFunction('function', $urlParams, true);

        self::assertStringContainsString('index.php?route=/table/change', $result);
        self::assertStringContainsString(
            'ShowFunctionFields=0&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result,
        );
        self::assertStringContainsString('Function', $result);

        // case 3
        $result = $this->insertEdit->showTypeOrFunction('type', $urlParams, false);

        self::assertStringContainsString('index.php?route=/table/change', $result);
        self::assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result,
        );
        self::assertStringContainsString('Type', $result);

        // case 4
        $result = $this->insertEdit->showTypeOrFunction('type', $urlParams, true);

        self::assertStringContainsString('index.php?route=/table/change', $result);
        self::assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=0&goto=index.php%3Froute%3D%2Fsql',
            $result,
        );
        self::assertStringContainsString('Type', $result);
    }

    /**
     * Test for getColumnTitle
     */
    public function testGetColumnTitle(): void
    {
        $fieldName = 'f1<';

        self::assertSame(
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getColumnTitle',
                [$fieldName, []],
            ),
            'f1&lt;',
        );

        $comments = [];
        $comments['f1<'] = 'comment>';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnTitle',
            [$fieldName, $comments],
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('title="comment&gt;"', $result);

        self::assertStringContainsString('f1&lt;', $result);
    }

    /**
     * Test for isColumn
     */
    public function testIsColumn(): void
    {
        $types = ['binary', 'varbinary'];

        $columnType = 'binaryfoo';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'Binaryfoo';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'varbinaryfoo';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'barbinaryfoo';
        self::assertFalse($this->insertEdit->isColumn($columnType, $types));

        $types = ['char', 'varchar'];

        $columnType = 'char(10)';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'VarChar(20)';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'foochar';
        self::assertFalse($this->insertEdit->isColumn($columnType, $types));

        $types = ['blob', 'tinyblob', 'mediumblob', 'longblob'];

        $columnType = 'blob';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'bloB';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'mediumBloB';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'tinyblobabc';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'longblob';
        self::assertTrue($this->insertEdit->isColumn($columnType, $types));

        $columnType = 'foolongblobbar';
        self::assertFalse($this->insertEdit->isColumn($columnType, $types));
    }

    /**
     * Test for getNullifyCodeForNullColumn
     */
    public function testGetNullifyCodeForNullColumn(): void
    {
        $foreigners = ['foreign_keys_data' => []];
        $column = new InsertEditColumn(
            'f',
            'enum(ababababababababababa)',
            false,
            '',
            null,
            '',
            -1,
            false,
            false,
            false,
            false,
        );
        self::assertSame(
            '1',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [$column, $foreigners, false],
            ),
        );

        $column = new InsertEditColumn(
            'f',
            'enum(abababababab20)',
            false,
            '',
            null,
            '',
            -1,
            false,
            false,
            false,
            false,
        );
        self::assertSame(
            '2',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [$column, $foreigners, false],
            ),
        );

        $column = new InsertEditColumn('f', 'set', false, '', null, '', -1, false, false, false, false);
        self::assertSame(
            '3',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [$column, $foreigners, false],
            ),
        );

        $column = new InsertEditColumn('f', '', false, '', null, '', -1, false, false, false, false);
        $foreigners['f'] = ['something'/* What should the mocked value actually be? */];
        self::assertSame(
            '4',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [$column, $foreigners, false],
            ),
        );
    }

    /**
     * Test for getTextarea
     */
    public function testGetTextarea(): void
    {
        $config = Config::getInstance();
        $config->settings['TextareaRows'] = 20;
        $config->settings['TextareaCols'] = 10;
        $config->settings['CharTextareaRows'] = 7;
        $config->settings['CharTextareaCols'] = 1;
        $config->settings['LimitChars'] = 20;

        $column = new InsertEditColumn(
            'f',
            'char(10)',
            false,
            '',
            null,
            'auto_increment',
            20,
            false,
            false,
            true,
            false,
        );
        (new ReflectionProperty(InsertEdit::class, 'fieldIndex'))->setValue($this->insertEdit, 2);
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getTextarea',
            [$column, 'a', 'b', '', 'foobar', TypeClass::Char],
        );

        $result = $this->parseString($result);

        self::assertStringContainsString(
            '<textarea name="fieldsb" class="charField" '
            . 'data-maxlength="10" rows="7" cols="1" dir="ltr" '
            . 'id="field_2_3" tabindex="2" data-type="CHAR">',
            $result,
        );
    }

    /**
     * Test for getMaxUploadSize
     */
    public function testGetMaxUploadSize(): void
    {
        $type = 'tinyblob';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getMaxUploadSize',
            [$type],
        );

        self::assertSame("(Max: 256B)\n", $result);

        // case 2
        // this should stub Util::getUploadSizeInBytes() but it's not possible
        $type = 'blob';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getMaxUploadSize',
            [$type],
        );

        self::assertSame("(Max: 64KiB)\n", $result);
    }

    /**
     * Test for getValueColumnForOtherDatatypes
     */
    public function testGetValueColumnForOtherDatatypes(): void
    {
        $column = new InsertEditColumn('f', 'char(25)', false, '', null, '', 20, false, false, true, false);
        $config = Config::getInstance();
        $config->settings['CharEditing'] = '';
        $config->settings['MaxSizeForInputField'] = 30;
        $config->settings['MinSizeForInputField'] = 10;
        $config->settings['TextareaRows'] = 20;
        $config->settings['TextareaCols'] = 10;
        $config->settings['CharTextareaRows'] = 7;
        $config->settings['CharTextareaCols'] = 1;
        $config->settings['LimitChars'] = 50;
        $config->settings['ShowFunctionFields'] = true;

        $extractedColumnSpec = '25';
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
                '\'"<>&',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        self::assertSame(
            "a\na\n"
            . '<textarea name="fieldsb" class="charField" '
            . 'data-maxlength="25" rows="7" cols="1" dir="ltr" '
            . 'id="field_22_3" onchange="c" tabindex="22" data-type="CHAR">'
            . '\'&quot;&lt;&gt;&amp;</textarea>',
            $result,
        );

        // case 2: (else)
        $column = new InsertEditColumn(
            'f',
            'timestamp',
            false,
            '',
            null,
            'auto_increment',
            20,
            false,
            false,
            false,
            false,
        );
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
                '\'"<>&',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        // phpcs:disable Generic.Files.LineLength.TooLong
        self::assertSame(
            <<<'HTML'
            a
              <input type="text" name="fieldsb"
                value="&#039;&quot;&lt;&gt;&amp;" size="20"    data-type="DATE"
                class="textfield datetimefield"
                onchange="c"
                tabindex="22"
                id="field_22_3"><input type="hidden" name="auto_incrementb" value="1"><input type="hidden" name="fields_typeb" value="timestamp">
            HTML,
            $result,
        );
        // phpcs:enable

        // case 3: (else -> datetime)
        $column = new InsertEditColumn(
            'f',
            'datetime',
            false,
            '',
            null,
            'auto_increment',
            20,
            false,
            false,
            false,
            false,
        );
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
                '\'"<>&',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<input type="hidden" name="fields_typeb" value="datetime">', $result);

        // case 4: (else -> date)
        $column = new InsertEditColumn('f', 'date', false, '', null, 'auto_increment', 20, false, false, false, false);
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
                '\'"<>&',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<input type="hidden" name="fields_typeb" value="date">', $result);

        // case 5: (else -> bit)
        $column = new InsertEditColumn('f', 'bit', false, '', null, 'auto_increment', 20, false, false, false, false);
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
                '\'"<>&',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<input type="hidden" name="fields_typeb" value="bit">', $result);

        // case 6: (else -> uuid)
        $column = new InsertEditColumn('f', 'uuid', false, '', null, 'auto_increment', 20, false, false, false, false);
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
                '\'"<>&',
                "foo\nbar",
                $extractedColumnSpec,
            ],
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<input type="hidden" name="fields_typeb" value="uuid">', $result);
    }

    /**
     * Test for getColumnSize
     */
    public function testGetColumnSize(): void
    {
        $column = new InsertEditColumn(
            'f',
            'char(10)',
            false,
            '',
            null,
            'auto_increment',
            20,
            false,
            false,
            true,
            false,
        );
        $specInBrackets = '45';
        $config = Config::getInstance();
        $config->settings['MinSizeForInputField'] = 30;
        $config->settings['MaxSizeForInputField'] = 40;

        self::assertSame(
            40,
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getColumnSize',
                [$column, $specInBrackets],
            ),
        );

        self::assertSame('textarea', $config->settings['CharEditing']);

        // case 2
        $column = new InsertEditColumn(
            'f',
            'char(10)',
            false,
            '',
            null,
            'auto_increment',
            20,
            false,
            false,
            false,
            false,
        );
        self::assertSame(
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
        $config = Config::getInstance();
        $config->settings['InsertRows'] = 1;
        $config->settings['ServerDefault'] = 1;
        UrlParams::$goto = 'index.php';
        $_POST['where_clause'] = true;
        $_POST['sql_query'] = 'SELECT 1';

        $result = $this->insertEdit->getContinueInsertionForm('tbl', 'db', $whereClauseArray, 'localhost');

        self::assertStringContainsString(
            '<form id="continueForm" method="post" action="' . Url::getFromRoute('/table/change')
            . '" name="continueForm" class="row g-3">',
            $result,
        );

        self::assertStringContainsString('<input type="hidden" name="db" value="db">', $result);

        self::assertStringContainsString('<input type="hidden" name="table" value="tbl">', $result);

        self::assertStringContainsString('<input type="hidden" name="goto" value="index.php">', $result);

        self::assertStringContainsString('<input type="hidden" name="err_url" value="localhost">', $result);

        self::assertStringContainsString('<input type="hidden" name="sql_query" value="SELECT 1">', $result);

        self::assertStringContainsString('<input type="hidden" name="where_clause[0]" value="a&lt;b">', $result);
    }

    public function testIsWhereClauseNumeric(): void
    {
        self::assertFalse(InsertEdit::isWhereClauseNumeric(null));
        self::assertFalse(InsertEdit::isWhereClauseNumeric(''));
        self::assertFalse(InsertEdit::isWhereClauseNumeric([]));
        self::assertTrue(InsertEdit::isWhereClauseNumeric('`actor`.`actor_id` = 1'));
        self::assertTrue(InsertEdit::isWhereClauseNumeric(['`actor`.`actor_id` = 1']));
        self::assertFalse(InsertEdit::isWhereClauseNumeric('`actor`.`first_name` = \'value\''));
        self::assertFalse(InsertEdit::isWhereClauseNumeric(['`actor`.`first_name` = \'value\'']));
    }

    /**
     * Test for getHeadAndFootOfInsertRowTable
     */
    public function testGetHeadAndFootOfInsertRowTable(): void
    {
        $config = Config::getInstance();
        $config->settings['ShowFieldTypesInDataEditView'] = true;
        $config->settings['ShowFunctionFields'] = true;
        $config->settings['ServerDefault'] = 1;
        $urlParams = ['ShowFunctionFields' => 2];

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHeadAndFootOfInsertRowTable',
            [$urlParams],
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('index.php?route=/table/change', $result);

        self::assertStringContainsString('ShowFunctionFields=1&ShowFieldTypesInDataEditView=0', $result);

        self::assertStringContainsString('ShowFunctionFields=0&ShowFieldTypesInDataEditView=1', $result);
    }

    /**
     * Test for getDefaultValueAndBackupFieldForExistingRow
     */
    public function testGetSpecialCharsAndBackupFieldForExistingRow(): void
    {
        $currentRow = [];
        $currentRow['f'] = null;
        $_POST['default_action'] = 'insert';
        $column = new InsertEditColumn(
            'f',
            'char(10)',
            false,
            'PRI',
            null,
            'fooauto_increment',
            20,
            false,
            false,
            true,
            false,
        );

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getDefaultValueAndBackupFieldForExistingRow',
            [$currentRow, $column, '', 'a', false],
        );

        self::assertEquals(
            [true, null, null, '<input type="hidden" name="fields_preva" value="">'],
            $result,
        );

        // Case 2 (bit)
        unset($_POST['default_action']);

        $currentRow['f'] = '123';
        $extractedColumnSpec = '20';
        $column = new InsertEditColumn(
            'f',
            'bit',
            false,
            'PRI',
            null,
            'fooauto_increment',
            20,
            false,
            false,
            true,
            false,
        );

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getDefaultValueAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, 'a', false],
        );

        self::assertEquals(
            [false, '00000000000001111011', null, '<input type="hidden" name="fields_preva" value="123">'],
            $result,
        );

        $currentRow['f'] = 'abcd';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getDefaultValueAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, 'a', true],
        );

        self::assertEquals(
            [false, 'abcd', null, '<input type="hidden" name="fields_preva" value="abcd">'],
            $result,
        );

        // Case 3 (bit)
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        $currentRow['f'] = '123';
        $extractedColumnSpec = '20';
        $column = new InsertEditColumn(
            'f',
            'geometry',
            false,
            'PRI',
            null,
            'fooauto_increment',
            20,
            false,
            false,
            true,
            false,
        );

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getDefaultValueAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, 'a', false],
        );

        self::assertEquals(
            [false, "'',", null, '<input type="hidden" name="fields_preva" value="\'\',">'],
            $result,
        );

        // Case 4 (else)
        $column = new InsertEditColumn(
            'f',
            'char',
            false,
            'PRI',
            null,
            'fooauto_increment',
            20,
            false,
            true,
            true,
            false,
        );
        $config = Config::getInstance();
        $config->settings['ProtectBinary'] = false;
        $currentRow['f'] = '11001';
        $extractedColumnSpec = '20';
        $config->settings['ShowFunctionFields'] = true;

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getDefaultValueAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, 'a', false],
        );

        self::assertSame(
            [
                false,
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
            'getDefaultValueAndBackupFieldForExistingRow',
            [$currentRow, $column, $extractedColumnSpec, 'a', false],
        );

        self::assertSame(
            [
                false,
                '313130303100',
                '313130303100',
                '<input type="hidden" name="fields_preva" value="313130303100">',
            ],
            $result,
        );
    }

    /**
     * Test for getDefaultValue
     */
    #[DataProvider('providerForTestGetSpecialCharsForInsertingMode')]
    public function testGetDefaultValue(
        string|null $defaultValue,
        string $trueType,
        string $expected,
    ): void {
        $config = Config::getInstance();
        $config->settings['ProtectBinary'] = false;
        $config->settings['ShowFunctionFields'] = true;

        /** @var string $result */
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getDefaultValue',
            [$defaultValue, $trueType],
        );

        self::assertSame($expected, $result);
    }

    /**
     * Data provider for test getDefaultValue()
     *
     * @return array<string, array{string|null, string, string}>
     */
    public static function providerForTestGetSpecialCharsForInsertingMode(): array
    {
        return [
            'bit' => [
                'b\'101\'',
                'bit',
                '101',
            ],
            'char' => [
                null,
                'char',
                '',
            ],
            'time with CURRENT_TIMESTAMP value' => [
                'CURRENT_TIMESTAMP',
                'time',
                'CURRENT_TIMESTAMP',
            ],
            'time with current_timestamp() value' => [
                'current_timestamp()',
                'time',
                'current_timestamp()',
            ],
            'time with no dot value' => [
                '10',
                'time',
                '10.000000',
            ],
            'time with dot value' => [
                '10.08',
                'time',
                '10.080000',
            ],
            'any text with escape text default' => [
                '"lorem\"ipsem"',
                'text',
                '"lorem\"ipsem"',
            ],
            'varchar with html special chars' => [
                'hello world<br><b>lorem</b> ipsem',
                'varchar',
                'hello world<br><b>lorem</b> ipsem',
            ],
            'text with html special chars' => [
                '\'</textarea><script>alert(1)</script>\'',
                'text',
                '\'</textarea><script>alert(1)</script>\'',
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

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('query')
            ->with('SELECT * FROM `db`.`table` WHERE `a` > 2 LIMIT 1;')
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('fetchRow')
            ->willReturn($row);

        $dbi->expects(self::once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->willReturn([$meta]);

        DatabaseInterface::$instance = $dbi;
        Current::$database = 'db';
        Current::$table = 'table';
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );
        $this->insertEdit->setSessionForEditNext('`a` = 2');

        self::assertSame('CONCAT(`table`.`orgname`) IS NULL', $_SESSION['edit_next']);
    }

    /**
     * Test for getGotoInclude
     */
    public function testGetGotoInclude(): void
    {
        UrlParams::$goto = '123.php';
        Current::$table = '';

        self::assertSame(
            '/database/sql',
            $this->insertEdit->getGotoInclude('index'),
        );

        Current::$table = 'tbl';
        self::assertSame(
            '/table/sql',
            $this->insertEdit->getGotoInclude('index'),
        );

        UrlParams::$goto = 'index.php?route=/database/sql';

        self::assertSame(
            '/database/sql',
            $this->insertEdit->getGotoInclude('index'),
        );

        self::assertSame('', Current::$table);

        UrlParams::$goto = 'index.php?route=/sql&server=2';

        self::assertSame(
            '/sql',
            $this->insertEdit->getGotoInclude('index'),
        );

        $_POST['after_insert'] = 'new_insert';
        self::assertSame(
            '/table/change',
            $this->insertEdit->getGotoInclude('index'),
        );
    }

    /**
     * Test for getErrorUrl
     */
    public function testGetErrorUrl(): void
    {
        Config::getInstance()->settings['ServerDefault'] = 1;
        self::assertSame(
            'index.php?route=/table/change&lang=en',
            $this->insertEdit->getErrorUrl([]),
        );

        $_POST['err_url'] = 'localhost';
        self::assertSame(
            'localhost',
            $this->insertEdit->getErrorUrl([]),
        );
    }

    /**
     * Test for executeSqlQuery
     */
    public function testExecuteSqlQuery(): void
    {
        $query = ['SELECT * FROM `test_db`.`test_table`;', 'SELECT * FROM `test_db`.`test_table_yaml`;'];
        Config::getInstance()->settings['IgnoreMultiSubmitErrors'] = false;
        $_POST['submit_type'] = '';

        $dbi = DatabaseInterface::getInstance();
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );
        $result = $this->insertEdit->executeSqlQuery($query);

        self::assertSame([], $result[3]);
    }

    /**
     * Test for executeSqlQuery
     */
    public function testExecuteSqlQueryWithTryQuery(): void
    {
        $query = ['SELECT * FROM `test_db`.`test_table`;', 'SELECT * FROM `test_db`.`test_table_yaml`;'];
        Config::getInstance()->settings['IgnoreMultiSubmitErrors'] = true;
        $_POST['submit_type'] = '';

        $dbi = DatabaseInterface::getInstance();
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );
        $result = $this->insertEdit->executeSqlQuery($query);

        self::assertSame([], $result[3]);
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

        $dbi->expects(self::once())
            ->method('getWarnings')
            ->willReturn($warnings);

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        $result = (array) $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getWarningMessages',
            [],
        );

        self::assertSame(['Error: #1001 Message 1', 'Warning: #1002 Message 2'], $result);
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

        $resultStub = self::createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('tryQuery')
            ->with('SELECT `TABLE_COMMENT` FROM `information_schema`.`TABLES` WHERE `f`=1')
            ->willReturn($resultStub);

        $resultStub->expects(self::once())
            ->method('numRows')
            ->willReturn(2);

        $resultStub->expects(self::once())
            ->method('fetchValue')
            ->with(0)
            ->willReturn('2');

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        $result = $this->insertEdit->getDisplayValueForForeignTableColumn('=1', $map, 'f');

        self::assertSame('2', $result);
    }

    /**
     * Test for getLinkForRelationalDisplayField
     */
    public function testGetLinkForRelationalDisplayField(): void
    {
        Config::getInstance()->settings['ServerDefault'] = 1;
        $_SESSION['tmpval']['relational_display'] = 'K';
        $map = [];
        $map['f']['foreign_db'] = 'information_schema';
        $map['f']['foreign_table'] = 'TABLES';
        $map['f']['foreign_field'] = 'f';

        $result = $this->insertEdit->getLinkForRelationalDisplayField($map, 'f', '=1', 'a>', 'b<');

        $sqlSignature = Core::signSqlQuery('SELECT * FROM `information_schema`.`TABLES` WHERE `f`=1');

        self::assertSame(
            '<a href="index.php?route=/sql&db=information_schema&table=TABLES&pos=0&'
            . 'sql_signature=' . $sqlSignature . '&'
            . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
            . '+%60f%60%3D1&lang=en" title="a&gt;">b&lt;</a>',
            $result,
        );

        $_SESSION['tmpval']['relational_display'] = 'D';
        $result = $this->insertEdit->getLinkForRelationalDisplayField($map, 'f', '=1', 'a>', 'b<');

        self::assertSame(
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
        $config = Config::getInstance();
        $config->settings['DefaultTransformations']['PreApPend'] = ['', ''];
        $config->settings['ServerDefault'] = 1;
        $_POST['where_clause'] = '1';
        $_POST['where_clause_sign'] = Core::signSqlQuery($_POST['where_clause']);
        $result = $this->insertEdit->transformEditedValues(
            'db',
            'table',
            "'','option ,, quoted',abd",
            $editedValues,
            'Text_Plain_PreApPend.php',
            'c',
            [['a' => 'b']],
        );

        self::assertSame(
            [['a' => 'b'], 'transformations' => ['cnameoption ,, quoted']],
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
        self::assertSame("'foo'", $result);

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

        self::assertSame('0x123', $result);

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

        self::assertSame("'uuid1234'", $result);

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
        self::assertSame("AES_ENCRYPT('\\'','')", $result);

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
        self::assertSame("ABS('\\'')", $result);

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
        self::assertSame('RAND()', $result);

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
        self::assertTrue(password_verify("a'c", mb_substr($result, 1, -1)));

        // case 7
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField('', "'POINT(3 4)',4326", '', true, false, false, 'ST_GeomFromText', null, null, false),
            false,
            '',
        );
        self::assertSame('ST_GeomFromText(\'POINT(3 4)\',4326)', $result);

        // case 8
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField('', 'POINT(3 4),4326', '', true, false, false, 'ST_GeomFromText', null, null, false),
            false,
            '',
        );
        self::assertSame('ST_GeomFromText(\'POINT(3 4)\',4326)', $result);

        // case 9
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField('', "'POINT(3 4)'", '', true, false, false, 'ST_GeomFromText', null, null, false),
            false,
            '',
        );
        self::assertSame('ST_GeomFromText(\'POINT(3 4)\')', $result);

        // case 10
        $result = $this->insertEdit->getQueryValueForInsert(
            new EditField('', 'POINT(3 4)', '', true, false, false, 'ST_GeomFromText', null, null, false),
            false,
            '',
        );
        self::assertSame('ST_GeomFromText(\'POINT(3 4)\')', $result);

        // Test different data types

        // Datatype: protected copied from the databse
        Current::$table = 'test_table';
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
        self::assertSame('0x313031', $result);

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
        self::assertSame('NULL', $result);

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
        self::assertSame("''", $result);

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
        self::assertSame("''", $result);

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
        self::assertSame('', $result);

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
        self::assertSame('NULL', $result);

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
        self::assertSame("b'00010'", $result);

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
        self::assertSame("'20\\'12'", $result);

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
        self::assertSame('NULL', $result);

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
        self::assertSame("''", $result);

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
        self::assertSame('current_timestamp()', $result);

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
        self::assertSame('0x222aaafff', $result);

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
        self::assertSame('0x222aaafff', $result);
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
        self::assertSame("`fld` = 'foo'", $result);

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
        self::assertSame('', $result);

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
        self::assertSame('`fld` = NULL', $result);

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
        self::assertSame("`fld` = 'ab\'c'", $result);

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
        self::assertSame("`fld` = '0'", $result);

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
        self::assertSame('', $result);

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

        self::assertSame('', $result);

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

        self::assertSame('`fld` = uuid()', $result);

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

        self::assertSame('`fld` = uuid()', $result);

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

        self::assertSame('`fld` = uuid()', $result);

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

        self::assertSame('`fld` = NULL', $result);
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

        $resultStub = self::createMock(DummyResult::class);

        $dbi->expects(self::exactly(3))
            ->method('tryQuery')
            ->with('SELECT `table`.`a` FROM `db`.`table` WHERE 1')
            ->willReturn($resultStub);

        $meta1 = FieldHelper::fromArray(['type' => MYSQLI_TYPE_TINY]);
        $meta2 = FieldHelper::fromArray(['type' => MYSQLI_TYPE_TINY]);
        $meta3 = FieldHelper::fromArray(['type' => MYSQLI_TYPE_TIMESTAMP]);
        $dbi->expects(self::exactly(3))
            ->method('getFieldsMeta')
            ->willReturn([$meta1], [$meta2], [$meta3]);

        $resultStub->expects(self::exactly(3))
            ->method('fetchValue')
            ->willReturn(false, '123', '2013-08-28 06:34:14');

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extraData);

        self::assertFalse($extraData['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extraData);

        self::assertSame('123', $extraData['truncatableFieldValue']);
        self::assertTrue($extraData['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extraData);

        self::assertSame('2013-08-28 06:34:14.000000', $extraData['truncatableFieldValue']);
        self::assertTrue($extraData['isNeedToRecheck']);
    }

    /**
     * Test for getTableColumns
     */
    public function testGetTableColumns(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('selectDb')
            ->with('db');

        $columns = [
            new Column('b', 'd', null, false, '', null, '', '', ''),
            new Column('f', 'h', null, true, '', null, '', '', ''),
        ];

        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('db', 'table')
            ->willReturn($columns);

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        $result = $this->insertEdit->getTableColumns('db', 'table');

        self::assertEquals(
            [
                new Column('b', 'd', null, false, '', null, '', '', ''),
                new Column('f', 'h', null, true, '', null, '', '', ''),
            ],
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

        $resultStub = self::createMock(DummyResult::class);

        $dbi->expects(self::exactly(2))
            ->method('query')
            ->willReturn($resultStub);

        DatabaseInterface::$instance = $dbi;
        $_POST['where_clause'] = '1';
        $_SESSION['edit_next'] = '1';
        $_POST['ShowFunctionFields'] = true;
        $_POST['ShowFieldTypesInDataEditView'] = true;
        $_POST['after_insert'] = 'edit_next';
        $config = Config::getInstance();
        $config->settings['InsertRows'] = 2;
        $config->settings['ShowSQL'] = false;
        $_POST['default_action'] = 'insert';

        $responseMock = $this->getMockBuilder(ResponseRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addHtml'])
            ->getMock();

        $restoreInstance = ResponseRenderer::getInstance();
        $response = new ReflectionProperty(ResponseRenderer::class, 'instance');
        $response->setValue(null, $responseMock);

        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        $result = $this->insertEdit->determineInsertOrEdit('1', 'db', 'table');

        self::assertEquals(
            [false, null, [$resultStub], [[]], false, 'edit_next'],
            $result,
        );

        // case 2
        unset($_POST['where_clause']);
        unset($_SESSION['edit_next']);
        $_POST['default_action'] = '';

        $result = $this->insertEdit->determineInsertOrEdit(null, 'db', 'table');

        $response->setValue(null, $restoreInstance);

        self::assertSame(
            [true, null, $resultStub, [[], []], false, 'edit_next'],
            $result,
        );
    }

    /**
     * Test for getCommentsMap
     */
    public function testGetCommentsMap(): void
    {
        $config = Config::getInstance();
        $config->settings['ShowPropertyComments'] = false;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::once())
            ->method('getColumns')
            ->with('db', 'table')
            ->willReturn([new Column('d', 'd', null, false, '', null, '', '', 'b')]);

        $dbi->expects(self::any())
            ->method('getTable')
            ->willReturn(new Table('table', 'db', $dbi));

        DatabaseInterface::$instance = $dbi;
        $relation = new Relation($dbi);
        $this->insertEdit = new InsertEdit(
            $dbi,
            $relation,
            new Transformations($dbi, $relation),
            new FileListing(),
            new Template(),
            Config::getInstance(),
        );

        self::assertSame(
            [],
            $this->insertEdit->getCommentsMap('db', 'table'),
        );

        $config->settings['ShowPropertyComments'] = true;

        self::assertSame(
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
        $checked = 'checked ';
        self::assertSame(
            sprintf($expected, $checked),
            $this->insertEdit->getHtmlForIgnoreOption(1),
        );

        self::assertSame(
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
        InsertEdit::$pluginScripts = [];
        $foreigners = ['foreign_keys_data' => []];
        $tableColumn = new Column('col', 'varchar(20)', null, true, '', null, '', 'insert,update,select', '');
        $repopulate = [md5('col') => 'val'];
        $columnMime = [
            'input_transformation' => 'Input/Image_JPEG_Upload.php',
            'input_transformation_options' => '150',
        ];

        // Test w/ input transformation
        $actual = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlForInsertEditFormColumn',
            [
                $tableColumn,
                0,
                [],
                -1,
                false,
                [],
                0,
                false,
                $foreigners,
                'table',
                'db',
                0,
                '',
                $repopulate,
                $columnMime,
                '',
            ],
        );

        $actual = $this->parseString($actual);

        self::assertStringContainsString('col', $actual);
        self::assertStringContainsString('<option>AES_ENCRYPT</option>', $actual);
        self::assertStringContainsString('<span class="column_type" dir="ltr">varchar(20)</span>', $actual);
        self::assertStringContainsString('<tr class="noclick">', $actual);
        self::assertStringContainsString('<span class="default_value hide">', $actual);
        self::assertStringContainsString('<img src="" width="150" height="100" alt="Image preview here">', $actual);
        self::assertStringContainsString(
            '<input type="file" '
            . 'name="fields_upload[multi_edit][0][d89e2ddb530bb8953b290ab0793aecb0]" '
            . 'accept="image/*" '
            . 'class="image-upload"'
            . '>',
            $actual,
        );

        // Test w/o input_transformation
        $tableColumn = new Column('qwerty', 'datetime', null, true, '', null, '', 'insert,update,select', '');
        $repopulate = [md5('qwerty') => '12-10-14'];
        $actual = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlForInsertEditFormColumn',
            [
                $tableColumn,
                0,
                [],
                -1,
                true,
                [],
                0,
                false,
                $foreigners,
                'table',
                'db',
                0,
                '',
                $repopulate,
                [],
                '',
            ],
        );

        $actual = $this->parseString($actual);

        self::assertStringContainsString('qwerty', $actual);
        self::assertStringContainsString('<option>UUID</option>', $actual);
        self::assertStringContainsString('<span class="column_type" dir="ltr">datetime</span>', $actual);
        self::assertStringContainsString(
            '<input type="text" name="fields[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]"' . "\n"
            . '    value="12-10-14.000000"',
            $actual,
        );

        self::assertStringContainsString(
            '<select name="funcs[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]"'
            . ' onchange="return verificationsAfterFieldChange(\'d8578edf8458ce06fbc5bb76a58c5ca4\','
            . ' \'0\', \'datetime\')" id="field_1_1">',
            $actual,
        );
        self::assertStringContainsString('<option>DATE</option>', $actual);

        self::assertStringContainsString(
            '<input type="hidden" name="fields_null_prev[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]">',
            $actual,
        );

        self::assertStringContainsString(
            '<input type="checkbox" class="checkbox_null"'
            . ' name="fields_null[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" id="field_1_2"'
            . ' aria-label="Use the NULL value for this column.">',
            $actual,
        );

        self::assertStringContainsString(
            '<input type="hidden" class="nullify_code"'
            . ' name="nullify_code[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="5"',
            $actual,
        );

        self::assertStringContainsString(
            '<input type="hidden" class="hashed_field"'
            . ' name="hashed_field[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" '
            . 'value="d8578edf8458ce06fbc5bb76a58c5ca4">',
            $actual,
        );

        self::assertStringContainsString(
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
        InsertEdit::$pluginScripts = [];
        $config = Config::getInstance();
        $config->settings['LongtextDoubleTextarea'] = true;
        $config->settings['CharEditing'] = 'input';
        $config->settings['TextareaRows'] = 10;
        $config->settings['TextareaCols'] = 11;
        $foreigners = ['foreign_keys_data' => []];
        $tableColumns = [
            new Column('test', 'longtext', null, true, '', null, '', 'select,insert,update,references', ''),
        ];

        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $tableColumns,
            [],
            [FieldHelper::fromArray(['type' => 0, 'length' => -1])],
            false,
            [],
            false,
            $foreigners,
            'table',
            'db',
            0,
            [],
            ['wc'],
        );
        self::assertStringContainsString('test', $actual);
        self::assertStringContainsString('<th>Column</th>', $actual);
        self::assertStringContainsString('<a', $actual);
        self::assertStringContainsString('<th class="w-50">Value</th>', $actual);
        self::assertStringContainsString('<span class="column_type" dir="ltr">longtext</span>', $actual);
        self::assertStringContainsString(
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
        InsertEdit::$pluginScripts = [];
        $config = Config::getInstance();
        $config->settings['LongtextDoubleTextarea'] = true;
        $config->settings['CharEditing'] = 'input';
        $foreigners = ['foreign_keys_data' => []];

        // edit
        $tableColumns = [
            new Column('foo', 'longtext', null, true, '', null, '', 'select,insert,update,references', ''),
            new Column('bar', 'longtext', null, true, '', null, '', 'select,insert,references', ''),
        ];

        $fieldMetadata = [
            FieldHelper::fromArray(['type' => 0, 'length' => -1]),
            FieldHelper::fromArray(['type' => 0, 'length' => -1]),
            FieldHelper::fromArray(['type' => 0, 'length' => -1]),
        ];

        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $tableColumns,
            [],
            $fieldMetadata,
            false,
            [],
            false,
            $foreigners,
            'table',
            'db',
            0,
            [],
            ['wc'],
        );
        self::assertStringContainsString('foo', $actual);
        self::assertStringContainsString('bar', $actual);

        // insert
        $tableColumns = [
            new Column('foo', 'longtext', null, true, '', null, '', 'select,insert,update,references', ''),
            new Column('bar', 'longtext', null, true, '', null, '', 'select,update,references', ''),
            new Column('point', 'point', null, false, '', null, '', 'select,update,references', ''),
        ];
        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $tableColumns,
            [],
            $fieldMetadata,
            true,
            [],
            false,
            $foreigners,
            'table',
            'db',
            0,
            [],
            ['wc'],
        );
        self::assertStringContainsString('foo', $actual);
        self::assertStringContainsString(
            '<textarea name="fields[multi_edit][0][37b51d194a7513e45b56f6524f2d51f2]"',
            $actual,
        );
        self::assertStringContainsString(
            '<span class="text-nowrap"><img src="themes/dot.gif" title="Edit/Insert"' .
            ' alt="Edit/Insert" class="icon ic_b_edit">&nbsp;Edit/Insert</span>',
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
            return (string) $value;
        }

        return '';
    }
}
