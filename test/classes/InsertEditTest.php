<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Warning;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Url;
use ReflectionProperty;
use stdClass;

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
use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\InsertEdit
 * @group medium
 */
class InsertEditTest extends AbstractTestCase
{
    /** @var InsertEdit */
    private $insertEdit;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        parent::setTheme();
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

        if (! empty($GLOBALS['dbi'])) {
            $GLOBALS['dbi']->setVersion([
                '@@version' => '10.9.3-MariaDB-1:10.9.3+maria~ubu2204',
                '@@version_comment' => 'mariadb.org binary distribution',
            ]);
        }

        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
    }

    /**
     * Teardown all objects
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $response = new ReflectionProperty(ResponseRenderer::class, 'instance');
        if (PHP_VERSION_ID < 80100) {
            $response->setAccessible(true);
        }

        $response->setValue(null, null);
        if (PHP_VERSION_ID >= 80100) {
            return;
        }

        $response->setAccessible(false);
    }

    /**
     * Test for getFormParametersForInsertForm
     */
    public function testGetFormParametersForInsertForm(): void
    {
        $where_clause = [
            'foo' => 'bar ',
            '1' => ' test',
        ];
        $_POST['clause_is_unique'] = false;
        $_POST['sql_query'] = 'SELECT a';
        $GLOBALS['goto'] = 'index.php';

        $result = $this->insertEdit->getFormParametersForInsertForm(
            'dbname',
            'tablename',
            [],
            $where_clause,
            'localhost'
        );

        self::assertSame([
            'db' => 'dbname',
            'table' => 'tablename',
            'goto' => 'index.php',
            'err_url' => 'localhost',
            'sql_query' => 'SELECT a',
            'where_clause[foo]' => 'bar',
            'where_clause[1]' => 'test',
            'clause_is_unique' => false,
        ], $result);
    }

    /**
     * Test for getFormParametersForInsertForm
     */
    public function testGetFormParametersForInsertFormGet(): void
    {
        $where_clause = [
            'foo' => 'bar ',
            '1' => ' test',
        ];
        $_GET['clause_is_unique'] = false;
        $_GET['sql_query'] = 'SELECT a';
        $_GET['sql_signature'] = Core::signSqlQuery($_GET['sql_query']);
        $GLOBALS['goto'] = 'index.php';

        $result = $this->insertEdit->getFormParametersForInsertForm(
            'dbname',
            'tablename',
            [],
            $where_clause,
            'localhost'
        );

        self::assertSame([
            'db' => 'dbname',
            'table' => 'tablename',
            'goto' => 'index.php',
            'err_url' => 'localhost',
            'sql_query' => 'SELECT a',
            'where_clause[foo]' => 'bar',
            'where_clause[1]' => 'test',
            'clause_is_unique' => false,
        ], $result);
    }

    /**
     * Test for getWhereClauseArray
     */
    public function testGetWhereClauseArray(): void
    {
        self::assertSame([], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getWhereClauseArray',
            [null]
        ));

        self::assertSame([
            1,
            2,
            3,
        ], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getWhereClauseArray',
            [[1, 2, 3]]
        ));

        self::assertSame(['clause'], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getWhereClauseArray',
            ['clause']
        ));
    }

    /**
     * Test for analyzeWhereClauses
     */
    public function testAnalyzeWhereClause(): void
    {
        $clauses = [
            'a=1',
            'b="fo\o"',
        ];

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
                []
            );

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'analyzeWhereClauses',
            [
                $clauses,
                'table',
                'db',
            ]
        );

        self::assertSame([
            [
                'a=1',
                'b="fo\\\\o"',
            ],
            [
                $resultStub1,
                $resultStub2,
            ],
            [
                ['assoc1'],
                ['assoc2'],
            ],
            false,
        ], $result);
    }

    /**
     * Test for showEmptyResultMessageOrSetUniqueCondition
     */
    public function testShowEmptyResultMessageOrSetUniqueCondition(): void
    {
        $temp = new stdClass();
        $temp->orgname = 'orgname';
        $temp->table = 'table';
        $meta_arr = [new FieldMetadata(MYSQLI_TYPE_DECIMAL, MYSQLI_PRI_KEY_FLAG, $temp)];

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with($resultStub)
            ->will($this->returnValue($meta_arr));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'showEmptyResultMessageOrSetUniqueCondition',
            [
                ['1' => ['1' => 1]],
                1,
                [],
                'SELECT',
                ['1' => $resultStub],
            ]
        );

        self::assertTrue($result);

        // case 2
        $GLOBALS['cfg']['ShowSQL'] = false;

        $responseMock = $this->getMockBuilder(ResponseRenderer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addHtml'])
            ->getMock();

        $restoreInstance = ResponseRenderer::getInstance();
        $response = new ReflectionProperty(ResponseRenderer::class, 'instance');
        if (PHP_VERSION_ID < 80100) {
            $response->setAccessible(true);
        }

        $response->setValue(null, $responseMock);

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'showEmptyResultMessageOrSetUniqueCondition',
            [
                [false],
                0,
                ['1'],
                'SELECT',
                ['1' => 'result1'],
            ]
        );

        $response->setValue(null, $restoreInstance);

        self::assertFalse($result);
    }

    public static function dataProviderConfigValueInsertRows(): array
    {
        return [
            [
                2,
                [
                    false,
                    false,
                ],
            ],
            [
                '2',
                [
                    false,
                    false,
                ],
            ],
            [
                3,
                [
                    false,
                    false,
                    false,
                ],
            ],
            [
                '3',
                [
                    false,
                    false,
                    false,
                ],
            ],
        ];
    }

    /**
     * Test for loadFirstRow
     *
     * @param string|int $configValue
     *
     * @dataProvider dataProviderConfigValueInsertRows
     */
    public function testLoadFirstRow($configValue, array $rowsValue): void
    {
        $GLOBALS['cfg']['InsertRows'] = $configValue;

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with(
                'SELECT * FROM `db`.`table` LIMIT 1;'
            )
            ->will($this->returnValue($resultStub));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'loadFirstRow',
            ['table', 'db']
        );

        self::assertSame([
            $resultStub,
            $rowsValue,
        ], $result);
    }

    /**
     * Test for urlParamsInEditMode
     */
    public function testUrlParamsInEditMode(): void
    {
        $where_clause_array = [
            'foo=1',
            'bar=2',
        ];
        $_POST['sql_query'] = 'SELECT 1';

        $result = $this->insertEdit->urlParamsInEditMode([1], $where_clause_array);

        self::assertSame([
            '0' => 1,
            'where_clause' => 'bar=2',
            'sql_query' => 'SELECT 1',
        ], $result);
    }

    /**
     * Test for showTypeOrFunction
     */
    public function testShowTypeOrFunction(): void
    {
        $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $url_params = ['ShowFunctionFields' => 2];

        $result = $this->insertEdit->showTypeOrFunction('function', $url_params, false);

        self::assertStringContainsString('index.php?route=/table/change', $result);
        self::assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result
        );
        self::assertStringContainsString('Function', $result);

        // case 2
        $result = $this->insertEdit->showTypeOrFunction('function', $url_params, true);

        self::assertStringContainsString('index.php?route=/table/change', $result);
        self::assertStringContainsString(
            'ShowFunctionFields=0&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result
        );
        self::assertStringContainsString('Function', $result);

        // case 3
        $result = $this->insertEdit->showTypeOrFunction('type', $url_params, false);

        self::assertStringContainsString('index.php?route=/table/change', $result);
        self::assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=1&goto=index.php%3Froute%3D%2Fsql',
            $result
        );
        self::assertStringContainsString('Type', $result);

        // case 4
        $result = $this->insertEdit->showTypeOrFunction('type', $url_params, true);

        self::assertStringContainsString('index.php?route=/table/change', $result);
        self::assertStringContainsString(
            'ShowFunctionFields=1&ShowFieldTypesInDataEditView=0&goto=index.php%3Froute%3D%2Fsql',
            $result
        );
        self::assertStringContainsString('Type', $result);
    }

    /**
     * Test for analyzeTableColumnsArray
     */
    public function testAnalyzeTableColumnsArray(): void
    {
        $column = [
            'Field' => '1<2',
            'Field_md5' => 'pswd',
            'Type' => 'float(10, 1)',
        ];

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'analyzeTableColumnsArray',
            [
                $column,
                [],
                false,
            ]
        );

        self::assertSame($result['Field_md5'], '4342210df36bf2ff2c4e2a997a6d4089');

        self::assertSame($result['True_Type'], 'float');

        self::assertSame($result['len'], 100);

        self::assertSame($result['Field_title'], '1&lt;2');

        self::assertSame($result['is_binary'], false);

        self::assertSame($result['is_blob'], false);

        self::assertSame($result['is_char'], false);

        self::assertSame($result['pma_type'], 'float(10, 1)');

        self::assertSame($result['wrap'], ' text-nowrap');

        self::assertSame($result['Field'], '1<2');
    }

    /**
     * Test for getColumnTitle
     */
    public function testGetColumnTitle(): void
    {
        $column = [];
        $column['Field'] = 'f1<';

        self::assertSame($this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnTitle',
            [
                $column,
                [],
            ]
        ), 'f1&lt;');

        $comments = [];
        $comments['f1<'] = 'comment>';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnTitle',
            [
                $column,
                $comments,
            ]
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
        $column = [];
        $types = [
            'binary',
            'varbinary',
        ];

        $column['Type'] = 'binaryfoo';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'Binaryfoo';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'varbinaryfoo';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'barbinaryfoo';
        self::assertFalse($this->insertEdit->isColumn($column, $types));

        $types = [
            'char',
            'varchar',
        ];

        $column['Type'] = 'char(10)';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'VarChar(20)';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'foochar';
        self::assertFalse($this->insertEdit->isColumn($column, $types));

        $types = [
            'blob',
            'tinyblob',
            'mediumblob',
            'longblob',
        ];

        $column['Type'] = 'blob';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'bloB';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'mediumBloB';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'tinyblobabc';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'longblob';
        self::assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'foolongblobbar';
        self::assertFalse($this->insertEdit->isColumn($column, $types));
    }

    /**
     * Test for getEnumSetAndTimestampColumns
     */
    public function testGetEnumAndTimestampColumns(): void
    {
        $column = [];
        $column['True_Type'] = 'set';
        self::assertSame([
            'set',
            '',
            false,
        ], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getEnumSetAndTimestampColumns',
            [
                $column,
                false,
            ]
        ));

        $column['True_Type'] = 'enum';
        self::assertSame([
            'enum',
            '',
            false,
        ], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getEnumSetAndTimestampColumns',
            [
                $column,
                false,
            ]
        ));

        $column['True_Type'] = 'timestamp';
        $column['Type'] = 'date';
        self::assertSame([
            'date',
            ' text-nowrap',
            true,
        ], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getEnumSetAndTimestampColumns',
            [
                $column,
                false,
            ]
        ));

        $column['True_Type'] = 'timestamp';
        $column['Type'] = 'date';
        self::assertSame([
            'date',
            ' text-nowrap',
            false,
        ], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getEnumSetAndTimestampColumns',
            [
                $column,
                true,
            ]
        ));

        $column['True_Type'] = 'SET';
        $column['Type'] = 'num';
        self::assertSame([
            'num',
            ' text-nowrap',
            false,
        ], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getEnumSetAndTimestampColumns',
            [
                $column,
                false,
            ]
        ));

        $column['True_Type'] = '';
        $column['Type'] = 'num';
        self::assertSame([
            'num',
            ' text-nowrap',
            false,
        ], $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getEnumSetAndTimestampColumns',
            [
                $column,
                false,
            ]
        ));
    }

    /**
     * Test for getNullifyCodeForNullColumn
     */
    public function testGetNullifyCodeForNullColumn(): void
    {
        $column = $foreignData = [];
        $foreigners = [
            'foreign_keys_data' => [],
        ];
        $column['Field'] = 'f';
        $column['True_Type'] = 'enum';
        $column['Type'] = 'ababababababababababa';
        self::assertSame('1', $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getNullifyCodeForNullColumn',
            [
                $column,
                $foreigners,
                [],
            ]
        ));

        $column['True_Type'] = 'enum';
        $column['Type'] = 'abababababababababab';
        self::assertSame('2', $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getNullifyCodeForNullColumn',
            [
                $column,
                $foreigners,
                [],
            ]
        ));

        $column['True_Type'] = 'set';
        self::assertSame('3', $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getNullifyCodeForNullColumn',
            [
                $column,
                $foreigners,
                [],
            ]
        ));

        $column['True_Type'] = '';
        $foreigners['f'] = true;
        $foreignData['foreign_link'] = '';
        self::assertSame('4', $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getNullifyCodeForNullColumn',
            [
                $column,
                $foreigners,
                $foreignData,
            ]
        ));
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
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getTextarea',
            [
                $column,
                'a',
                'b',
                '',
                2,
                0,
                1,
                'abc/',
                'foobar',
                'CHAR',
                false,
            ]
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<textarea name="fieldsb" class="charField" '
        . 'data-maxlength="10" rows="7" cols="1" dir="abc/" '
        . 'id="field_1_3" tabindex="2" data-type="CHAR">', $result);
    }

    /**
     * Test for getColumnEnumValues
     */
    public function testGetColumnEnumValues(): void
    {
        $enum_set_values = [
            '<abc>',
            '"foo"',
        ];

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnEnumValues',
            [$enum_set_values]
        );
        self::assertSame([
            [
                'plain' => '<abc>',
                'html' => '&lt;abc&gt;',
            ],
            [
                'plain' => '"foo"',
                'html' => '&quot;foo&quot;',
            ],
        ], $result);
    }

    /**
     * Test for getColumnSetValueAndSelectSize
     */
    public function testGetColumnSetValueAndSelectSize(): void
    {
        $column = [];
        $enum_set_values = [
            'a',
            '<',
        ];
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnSetValueAndSelectSize',
            [
                [],
                $enum_set_values,
            ]
        );

        self::assertSame([
            [
                [
                    'plain' => 'a',
                    'html' => 'a',
                ],
                [
                    'plain' => '<',
                    'html' => '&lt;',
                ],
            ],
            2,
        ], $result);

        $column['values'] = [
            1,
            2,
        ];
        $column['select_size'] = 3;
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnSetValueAndSelectSize',
            [
                $column,
                $enum_set_values,
            ]
        );

        self::assertSame([
            [
                1,
                2,
            ],
            3,
        ], $result);
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
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlInput',
            [
                $column,
                'a',
                'b',
                30,
                'c',
                23,
                2,
                0,
                'DATE',
                false,
            ]
        );

        self::assertSame('<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
        . ' class="textfield datefield" c tabindex="25" id="field_0_3">', $result);

        // case 2 datetime
        $column['pma_type'] = 'datetime';
        $column['True_Type'] = 'datetime';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlInput',
            [
                $column,
                'a',
                'b',
                30,
                'c',
                23,
                2,
                0,
                'DATE',
                false,
            ]
        );
        self::assertSame('<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
        . ' class="textfield datetimefield" c tabindex="25" id="field_0_3">', $result);

        // case 3 timestamp
        $column['pma_type'] = 'timestamp';
        $column['True_Type'] = 'timestamp';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlInput',
            [
                $column,
                'a',
                'b',
                30,
                'c',
                23,
                2,
                0,
                'DATE',
                false,
            ]
        );
        self::assertSame('<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
        . ' class="textfield datetimefield" c tabindex="25" id="field_0_3">', $result);
    }

    /**
     * Test for getMaxUploadSize
     */
    public function testGetMaxUploadSize(): void
    {
        $GLOBALS['config']->set('max_upload_size', 257);
        $pma_type = 'tinyblob';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getMaxUploadSize',
            [
                $pma_type,
                256,
            ]
        );

        self::assertSame([
            "(Max: 256B)\n",
            256,
        ], $result);

        // case 2
        $GLOBALS['config']->set('max_upload_size', 250);
        $pma_type = 'tinyblob';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getMaxUploadSize',
            [
                $pma_type,
                20,
            ]
        );

        self::assertSame([
            "(Max: 250B)\n",
            250,
        ], $result);
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

        $extracted_columnspec = [];
        $extracted_columnspec['spec_in_brackets'] = '25';
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
                22,
                '&lt;',
                12,
                1,
                '/',
                '&lt;',
                "foo\nbar",
                $extracted_columnspec,
                false,
            ]
        );

        self::assertSame("a\n\na\n"
        . '<textarea name="fieldsb" class="charField" '
        . 'data-maxlength="25" rows="7" cols="1" dir="/" '
        . 'id="field_1_3" c tabindex="34" data-type="CHAR">'
        . '&lt;</textarea>', $result);

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
                22,
                '&lt;',
                12,
                1,
                '/',
                '&lt;',
                "foo\nbar",
                $extracted_columnspec,
                false,
            ]
        );

        self::assertSame("a\n"
        . '<input type="text" name="fieldsb" value="&lt;" size="20" data-type="'
        . 'DATE" class="textfield datetimefield" c tabindex="34" id="field_1_3"'
        . '><input type="hidden" name="auto_incrementb" value="1">'
        . '<input type="hidden" name="fields_typeb" value="timestamp">', $result);

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
                22,
                '&lt;',
                12,
                1,
                '/',
                '&lt;',
                "foo\nbar",
                $extracted_columnspec,
                false,
            ]
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<input type="hidden" name="fields_typeb" value="datetime">', $result);

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
                22,
                '&lt;',
                12,
                1,
                '/',
                '&lt;',
                "foo\nbar",
                $extracted_columnspec,
                false,
            ]
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<input type="hidden" name="fields_typeb" value="date">', $result);

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
                22,
                '&lt;',
                12,
                1,
                '/',
                '&lt;',
                "foo\nbar",
                $extracted_columnspec,
                false,
            ]
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<input type="hidden" name="fields_typeb" value="bit">', $result);

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
                22,
                '&lt;',
                12,
                1,
                '/',
                '&lt;',
                "foo\nbar",
                $extracted_columnspec,
                false,
            ]
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('<input type="hidden" name="fields_typeb" value="uuid">', $result);
    }

    /**
     * Test for getColumnSize
     */
    public function testGetColumnSize(): void
    {
        $column = [];
        $column['is_char'] = true;
        $spec_in_brackets = '45';
        $GLOBALS['cfg']['MinSizeForInputField'] = 30;
        $GLOBALS['cfg']['MaxSizeForInputField'] = 40;

        self::assertSame(40, $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnSize',
            [
                $column,
                $spec_in_brackets,
            ]
        ));

        self::assertSame('textarea', $GLOBALS['cfg']['CharEditing']);

        // case 2
        $column['is_char'] = false;
        $column['len'] = 20;
        self::assertSame(30, $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnSize',
            [
                $column,
                $spec_in_brackets,
            ]
        ));
    }

    /**
     * Test for getContinueInsertionForm
     */
    public function testGetContinueInsertionForm(): void
    {
        $where_clause_array = ['a<b'];
        $GLOBALS['cfg']['InsertRows'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['goto'] = 'index.php';
        $_POST['where_clause'] = true;
        $_POST['sql_query'] = 'SELECT 1';

        $result = $this->insertEdit->getContinueInsertionForm('tbl', 'db', $where_clause_array, 'localhost');

        self::assertStringContainsString(
            '<form id="continueForm" method="post" action="' . Url::getFromRoute('/table/replace')
            . '" name="continueForm">',
            $result
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
        $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = true;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $url_params = ['ShowFunctionFields' => 2];

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHeadAndFootOfInsertRowTable',
            [$url_params]
        );

        $result = $this->parseString($result);

        self::assertStringContainsString('index.php?route=/table/change', $result);

        self::assertStringContainsString('ShowFunctionFields=1&ShowFieldTypesInDataEditView=0', $result);

        self::assertStringContainsString('ShowFunctionFields=0&ShowFieldTypesInDataEditView=1', $result);
    }

    /**
     * Test for getSpecialCharsAndBackupFieldForExistingRow
     */
    public function testGetSpecialCharsAndBackupFieldForExistingRow(): void
    {
        $column = $current_row = $extracted_columnspec = [];
        $column['Field'] = 'f';
        $current_row['f'] = null;
        $_POST['default_action'] = 'insert';
        $column['Key'] = 'PRI';
        $column['Extra'] = 'fooauto_increment';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                [],
                [],
                'a',
                false,
            ]
        );

        self::assertSame([
            true,
            null,
            null,
            null,
            '<input type="hidden" name="fields_preva" value="">',
        ], $result);

        // Case 2 (bit)
        unset($_POST['default_action']);

        $current_row['f'] = '123';
        $extracted_columnspec['spec_in_brackets'] = '20';
        $column['True_Type'] = 'bit';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                $extracted_columnspec,
                [],
                'a',
                false,
            ]
        );

        self::assertSame([
            false,
            '',
            '00000000000001111011',
            null,
            '<input type="hidden" name="fields_preva" value="123">',
        ], $result);

        $current_row['f'] = 'abcd';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                $extracted_columnspec,
                [],
                'a',
                true,
            ]
        );

        self::assertSame([
            false,
            '',
            'abcd',
            null,
            '<input type="hidden" name="fields_preva" value="abcd">',
        ], $result);

        // Case 3 (bit)
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $current_row['f'] = '123';
        $extracted_columnspec['spec_in_brackets'] = '20';
        $column['True_Type'] = 'int';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                $extracted_columnspec,
                ['int'],
                'a',
                false,
            ]
        );

        self::assertSame([
            false,
            '',
            "'',",
            null,
            '<input type="hidden" name="fields_preva" value="\'\',">',
        ], $result);

        // Case 4 (else)
        $column['is_binary'] = false;
        $column['is_blob'] = true;
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $current_row['f'] = '11001';
        $extracted_columnspec['spec_in_brackets'] = '20';
        $column['True_Type'] = 'char';
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                $extracted_columnspec,
                ['int'],
                'a',
                false,
            ]
        );

        self::assertSame([
            false,
            '3131303031',
            '3131303031',
            '3131303031',
            '<input type="hidden" name="fields_preva" value="3131303031">',
        ], $result);

        // Case 5
        $current_row['f'] = "11001\x00";

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                $extracted_columnspec,
                ['int'],
                'a',
                false,
            ]
        );

        self::assertSame([
            false,
            '313130303100',
            '313130303100',
            '313130303100',
            '<input type="hidden" name="fields_preva" value="313130303100">',
        ], $result);
    }

    /**
     * Test for getSpecialCharsAndBackupFieldForInsertingMode
     *
     * @param array $column   Column parameters
     * @param array $expected Expected result
     * @psalm-param array<string, string|bool|null> $column
     * @psalm-param array<bool|string> $expected
     *
     * @dataProvider providerForTestGetSpecialCharsAndBackupFieldForInsertingMode
     */
    public function testGetSpecialCharsAndBackupFieldForInsertingMode(
        array $column,
        array $expected
    ): void {
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $result = (array) $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForInsertingMode',
            [$column]
        );

        self::assertSame($expected, $result);
    }

    /**
     * Data provider for test getSpecialCharsAndBackupFieldForInsertingMode()
     *
     * @return array
     * @psalm-return array<string, array{array<string, string|bool|null>, array<bool|string>}>
     */
    public static function providerForTestGetSpecialCharsAndBackupFieldForInsertingMode(): array
    {
        return [
            'bit' => [
                [
                    'True_Type' => 'bit',
                    'Default' => 'b\'101\'',
                    'is_binary' => true,
                ],
                [
                    false,
                    'b\'101\'',
                    '101',
                    '',
                    '101',
                ],
            ],
            'char' => [
                [
                    'True_Type' => 'char',
                    'is_binary' => true,
                ],
                [
                    true,
                    '',
                    '',
                    '',
                    '',
                ],
            ],
            'time with CURRENT_TIMESTAMP value' => [
                [
                    'True_Type' => 'time',
                    'Default' => 'CURRENT_TIMESTAMP',
                ],
                [
                    false,
                    'CURRENT_TIMESTAMP',
                    'CURRENT_TIMESTAMP',
                    '',
                    'CURRENT_TIMESTAMP',
                ],
            ],
            'time with current_timestamp() value' => [
                [
                    'True_Type' => 'time',
                    'Default' => 'current_timestamp()',
                ],
                [
                    false,
                    'current_timestamp()',
                    'current_timestamp()',
                    '',
                    'current_timestamp()',
                ],
            ],
            'time with no dot value' => [
                [
                    'True_Type' => 'time',
                    'Default' => '10',
                ],
                [
                    false,
                    '10',
                    '10.000000',
                    '',
                    '10.000000',
                ],
            ],
            'time with dot value' => [
                [
                    'True_Type' => 'time',
                    'Default' => '10.08',
                ],
                [
                    false,
                    '10.08',
                    '10.080000',
                    '',
                    '10.080000',
                ],
            ],
            'any text with escape text default' => [
                [
                    'True_Type' => 'text',
                    'Default' => '"lorem\"ipsem"',
                ],
                [
                    false,
                    '"lorem\"ipsem"',
                    'lorem&quot;ipsem',
                    '',
                    'lorem&quot;ipsem',
                ],
            ],
            'varchar with html special chars' => [
                [
                    'True_Type' => 'varchar',
                    'Default' => 'hello world<br><b>lorem</b> ipsem',
                ],
                [
                    false,
                    'hello world<br><b>lorem</b> ipsem',
                    'hello world&lt;br&gt;&lt;b&gt;lorem&lt;/b&gt; ipsem',
                    '',
                    'hello world&lt;br&gt;&lt;b&gt;lorem&lt;/b&gt; ipsem',
                ],
            ],
            'text with html special chars' => [
                ['True_Type' => 'text', 'Default' => '\'</textarea><script>alert(1)</script>\''],
                [
                    false,
                    '\'</textarea><script>alert(1)</script>\'',
                    '&lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;',
                    '',
                    '&lt;/textarea&gt;&lt;script&gt;alert(1)&lt;/script&gt;',
                ],
            ],
        ];
    }

    /**
     * Test for getParamsForUpdateOrInsert
     */
    public function testGetParamsForUpdateOrInsert(): void
    {
        $_POST['where_clause'] = 'LIMIT 1';
        $_POST['submit_type'] = 'showinsert';

        $result = $this->insertEdit->getParamsForUpdateOrInsert();

        self::assertSame([
            ['LIMIT 1'],
            true,
            true,
            false,
        ], $result);

        // case 2 (else)
        unset($_POST['where_clause']);
        $_POST['fields']['multi_edit'] = [
            'a' => 'b',
            'c' => 'd',
        ];
        $result = $this->insertEdit->getParamsForUpdateOrInsert();

        self::assertSame([
            [
                'a',
                'c',
            ],
            false,
            true,
            false,
        ], $result);
    }

    /**
     * Test for setSessionForEditNext
     */
    public function testSetSessionForEditNext(): void
    {
        $temp = new stdClass();
        $temp->orgname = 'orgname';
        $temp->table = 'table';
        $temp->orgtable = 'table';
        $meta_arr = [new FieldMetadata(MYSQLI_TYPE_DECIMAL, MYSQLI_PRI_KEY_FLAG, $temp)];

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
            ->will($this->returnValue($meta_arr));

        $GLOBALS['dbi'] = $dbi;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
        $this->insertEdit->setSessionForEditNext('`a` = 2');

        self::assertSame('CONCAT(`table`.`orgname`) IS NULL', $_SESSION['edit_next']);
    }

    /**
     * Test for getGotoInclude
     */
    public function testGetGotoInclude(): void
    {
        $GLOBALS['goto'] = '123.php';
        $GLOBALS['table'] = '';

        self::assertSame('/database/sql', $this->insertEdit->getGotoInclude('index'));

        $GLOBALS['table'] = 'tbl';
        self::assertSame('/table/sql', $this->insertEdit->getGotoInclude('index'));

        $GLOBALS['goto'] = 'index.php?route=/database/sql';

        self::assertSame('/database/sql', $this->insertEdit->getGotoInclude('index'));

        self::assertSame('', $GLOBALS['table']);

        $GLOBALS['goto'] = 'index.php?route=/sql&server=2';

        self::assertSame('/sql', $this->insertEdit->getGotoInclude('index'));

        $_POST['after_insert'] = 'new_insert';
        self::assertSame('/table/change', $this->insertEdit->getGotoInclude('index'));
    }

    /**
     * Test for getErrorUrl
     */
    public function testGetErrorUrl(): void
    {
        $GLOBALS['cfg']['ServerDefault'] = 1;
        self::assertSame('index.php?route=/table/change&lang=en', $this->insertEdit->getErrorUrl([]));

        $_POST['err_url'] = 'localhost';
        self::assertSame('localhost', $this->insertEdit->getErrorUrl([]));
    }

    /**
     * Test for buildSqlQuery
     */
    public function testBuildSqlQuery(): void
    {
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $query_fields = [
            'a',
            'b',
        ];
        $value_sets = [
            1,
            2,
        ];

        self::assertSame(
            ['INSERT IGNORE INTO `table` (a, b) VALUES (1), (2)'],
            $this->insertEdit->buildSqlQuery(true, $query_fields, $value_sets)
        );

        self::assertSame(
            ['INSERT INTO `table` (a, b) VALUES (1), (2)'],
            $this->insertEdit->buildSqlQuery(false, $query_fields, $value_sets)
        );
    }

    /**
     * Test for executeSqlQuery
     */
    public function testExecuteSqlQuery(): void
    {
        $query = [
            'SELECT * FROM `test_db`.`test_table`;',
            'SELECT * FROM `test_db`.`test_table_yaml`;',
        ];
        $GLOBALS['sql_query'] = 'SELECT * FROM `test_db`.`test_table`;';
        $GLOBALS['cfg']['IgnoreMultiSubmitErrors'] = false;
        $_POST['submit_type'] = '';

        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
        $result = $this->insertEdit->executeSqlQuery([], $query);

        self::assertSame(['sql_query' => 'SELECT * FROM `test_db`.`test_table`;'], $result[0]);
        self::assertSame([], $result[3]);
        self::assertSame('SELECT * FROM `test_db`.`test_table`;', $result[5]);
    }

    /**
     * Test for executeSqlQuery
     */
    public function testExecuteSqlQueryWithTryQuery(): void
    {
        $query = [
            'SELECT * FROM `test_db`.`test_table`;',
            'SELECT * FROM `test_db`.`test_table_yaml`;',
        ];
        $GLOBALS['sql_query'] = 'SELECT * FROM `test_db`.`test_table`;';
        $GLOBALS['cfg']['IgnoreMultiSubmitErrors'] = true;
        $_POST['submit_type'] = '';

        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
        $result = $this->insertEdit->executeSqlQuery([], $query);

        self::assertSame(['sql_query' => 'SELECT * FROM `test_db`.`test_table`;'], $result[0]);
        self::assertSame([], $result[3]);
        self::assertSame('SELECT * FROM `test_db`.`test_table`;', $result[5]);
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
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = (array) $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getWarningMessages',
            []
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

        $resultStub = $this->createMock(DummyResult::class);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with(
                'SELECT `TABLE_COMMENT` FROM `information_schema`.`TABLES` WHERE `f`=1'
            )
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->once())
            ->method('numRows')
            ->will($this->returnValue('2'));

        $resultStub->expects($this->once())
            ->method('fetchValue')
            ->with(0)
            ->will($this->returnValue('2'));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getDisplayValueForForeignTableColumn('=1', $map, 'f');

        self::assertEquals(2, $result);
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

        self::assertSame('<a href="index.php?route=/sql&db=information_schema&table=TABLES&pos=0&'
        . 'sql_signature=' . $sqlSignature . '&'
        . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
        . '+%60f%60%3D1&lang=en" title="a&gt;">b&lt;</a>', $result);

        $_SESSION['tmpval']['relational_display'] = 'D';
        $result = $this->insertEdit->getLinkForRelationalDisplayField($map, 'f', '=1', 'a>', 'b<');

        self::assertSame('<a href="index.php?route=/sql&db=information_schema&table=TABLES&pos=0&'
        . 'sql_signature=' . $sqlSignature . '&'
        . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
        . '+%60f%60%3D1&lang=en" title="b&lt;">a&gt;</a>', $result);
    }

    /**
     * Test for transformEditedValues
     */
    public function testTransformEditedValues(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $edited_values = [
            ['c' => 'cname'],
        ];
        $GLOBALS['cfg']['DefaultTransformations']['PreApPend'] = [
            '',
            '',
        ];
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $_POST['where_clause'] = '1';
        $_POST['where_clause_sign'] = Core::signSqlQuery($_POST['where_clause']);
        $transformation = ['transformation_options' => "'','option ,, quoted',abd"];
        $result = $this->insertEdit->transformEditedValues(
            'db',
            'table',
            $transformation,
            $edited_values,
            'Text_Plain_PreApPend.php',
            'c',
            ['a' => 'b'],
            'transformation'
        );

        self::assertSame([
            'a' => 'b',
            'transformations' => ['cnameoption ,, quoted'],
        ], $result);
    }

    /**
     * Test for getQueryValuesForInsertAndUpdateInMultipleEdit
     */
    public function testGetQueryValuesForInsertAndUpdateInMultipleEdit(): void
    {
        $multi_edit_columns_name = ['0' => 'fld'];

        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            [],
            '',
            [],
            [],
            true,
            [1],
            [2],
            'foo',
            [],
            '0',
            []
        );

        self::assertSame([
            [
                1,
                'foo',
            ],
            [
                2,
                '`fld`',
            ],
        ], $result);

        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            [],
            '',
            [],
            [],
            false,
            [1],
            [2],
            'foo',
            [],
            '0',
            ['a']
        );

        self::assertSame([
            [
                1,
                '`fld` = foo',
            ],
            [2],
        ], $result);

        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            ['b'],
            "'`c`'",
            ['c'],
            [],
            false,
            [1],
            [2],
            'foo',
            [],
            '0',
            ['a']
        );

        self::assertSame([
            [1],
            [2],
        ], $result);

        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            ['b'],
            "'`c`'",
            ['c'],
            [3],
            false,
            [1],
            [2],
            'foo',
            [],
            '0',
            []
        );

        self::assertSame([
            [
                1,
                '`fld` = foo',
            ],
            [2],
        ], $result);

        // Test to see if a zero-string is not ignored
        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            [],
            '0',
            [],
            [],
            false,
            [],
            [],
            "'0'",
            [],
            '0',
            []
        );

        self::assertSame([
            ["`fld` = '0'"],
            [],
        ], $result);

        // Can only happen when table contains blob field that was left unchanged during edit
        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            [],
            '',
            [],
            [],
            false,
            [],
            [],
            '',
            [],
            '0',
            []
        );

        self::assertSame([
            [],
            [],
        ], $result);

        // Test to see if a field will be set to null when it wasn't null previously
        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            ['on'],
            '',
            [],
            [],
            false,
            [],
            [],
            'NULL',
            [],
            '0',
            []
        );

        self::assertSame([
            ['`fld` = NULL'],
            [],
        ], $result);

        // Test to see if a field will be ignored if it was null previously
        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            ['on'],
            '',
            [],
            [],
            false,
            [],
            [],
            'NULL',
            [],
            '0',
            ['on']
        );

        self::assertSame([
            [],
            [],
        ], $result);

        // Test to see if a field will be ignored if it the value is unchanged
        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            [],
            "a'b",
            ["a'b"],
            [],
            false,
            [],
            [],
            "'a\'b'",
            [],
            '0',
            []
        );

        self::assertSame([
            [],
            [],
        ], $result);

        // Test to see if a field can be set to NULL
        $result = $this->insertEdit->getQueryValuesForInsertAndUpdateInMultipleEdit(
            $multi_edit_columns_name,
            ['on'],
            '',
            [''],
            [],
            false,
            [],
            [],
            'NULL',
            [],
            '0',
            []
        );

        self::assertSame([
            ['`fld` = NULL'],
            [],
        ], $result);
    }

    /**
     * Test for getCurrentValueAsAnArrayForMultipleEdit
     */
    public function testGetCurrentValueAsAnArrayForMultipleEdit(): void
    {
        // case 2
        $multi_edit_funcs = ['UUID'];

        $this->dummyDbi->addResult(
            'SELECT UUID()',
            [
                ['uuid1234'],// Minimal working setup for 2FA
            ]
        );
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            [],
            [],
            'currVal',
            [],
            [],
            [],
            '0'
        );

        self::assertSame("'uuid1234'", $result);

        // case 3
        $multi_edit_funcs = ['AES_ENCRYPT'];
        $multi_edit_salt = [''];
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            $multi_edit_salt,
            [],
            "'",
            [],
            ['func'],
            ['func'],
            '0'
        );
        self::assertSame("AES_ENCRYPT('\\'','')", $result);

        // case 4
        $multi_edit_funcs = ['func'];
        $multi_edit_salt = [];
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            $multi_edit_salt,
            [],
            "'",
            [],
            ['func'],
            ['func'],
            '0'
        );
        self::assertSame("func('\\'')", $result);

        // case 5
        $multi_edit_funcs = ['RAND'];
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            $multi_edit_salt,
            [],
            '',
            [],
            ['func'],
            ['RAND'],
            '0'
        );
        self::assertSame('RAND()', $result);

        // case 6
        $multi_edit_funcs = ['PHP_PASSWORD_HASH'];
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            $multi_edit_salt,
            [],
            "a'c",
            [],
            [],
            [],
            '0'
        );
        self::assertTrue(password_verify("a'c", mb_substr($result, 1, -1)));

        // case 7 / 8 / 9 / 10
        $gisParams = [
            ['ST_GeomFromText'],
            [],
            ['ST_GeomFromText'],
            "'POINT(3 4)',4326",
            [],
            [],
            [],
            '0',
        ];
        // case 7
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(...$gisParams);
        self::assertSame('ST_GeomFromText(\'POINT(3 4)\',4326)', $result);

        // case 8
        $gisParams[3] = 'POINT(3 4),4326';
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(...$gisParams);
        self::assertSame('ST_GeomFromText(\'POINT(3 4)\',4326)', $result);

        // case 9
        $gisParams[3] = "'POINT(3 4)'";
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(...$gisParams);
        self::assertSame('ST_GeomFromText(\'POINT(3 4)\')', $result);

        // case 10
        $gisParams[3] = 'POINT(3 4)';
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(...$gisParams);
        self::assertSame('ST_GeomFromText(\'POINT(3 4)\')', $result);
    }

    /**
     * Test for getCurrentValueForDifferentTypes
     */
    public function testGetCurrentValueForDifferentTypes(): void
    {
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            '123',
            '0',
            [],
            '',
            [],
            0,
            [],
            [],
            [],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame('123', $result);

        // case 2
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['test'],
            '',
            [1],
            0,
            [],
            [],
            [],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame('NULL', $result);

        // case 3
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['test'],
            '',
            [],
            0,
            [],
            [],
            [],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame("''", $result);

        // case 4
        $_POST['fields']['multi_edit'][0][0] = [];
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['set'],
            '',
            [],
            0,
            [],
            [],
            [],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame("''", $result);

        // case 5
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['protected'],
            '',
            [],
            0,
            ['name'],
            [],
            [],
            true,
            true,
            '`id` = 4',
            'test_table',
            []
        );

        self::assertSame('0x313031', $result);

        // case 6
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['protected'],
            '',
            [],
            0,
            ['a'],
            [],
            [],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame('', $result);

        // case 7
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['bit'],
            '20\'12',
            [],
            0,
            ['a'],
            [],
            [],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame("b'00010'", $result);

        // case 7
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['date'],
            '20\'12',
            [],
            0,
            ['a'],
            [],
            [],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame("'20\\'12'", $result);

        // case 8
        $_POST['fields']['multi_edit'][0][0] = [];
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['set'],
            '',
            [],
            0,
            [],
            [1],
            [],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame('NULL', $result);

        // case 9
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['protected'],
            '',
            [],
            0,
            ['a'],
            [],
            [1],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame("''", $result);

        // case 10
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['uuid'],
            '',
            [],
            0,
            ['a'],
            [],
            [1],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame('uuid()', $result);

        // case 11
        $result = $this->insertEdit->getCurrentValueForDifferentTypes(
            false,
            '0',
            ['uuid'],
            'uuid()',
            [],
            0,
            ['a'],
            [],
            [1],
            true,
            true,
            '',
            'test_table',
            []
        );

        self::assertSame('uuid()', $result);
    }

    /**
     * Test for verifyWhetherValueCanBeTruncatedAndAppendExtraData
     */
    public function testVerifyWhetherValueCanBeTruncatedAndAppendExtraData(): void
    {
        $extra_data = ['isNeedToRecheck' => true];

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

        $meta1 = new FieldMetadata(MYSQLI_TYPE_TINY, 0, (object) []);
        $meta2 = new FieldMetadata(MYSQLI_TYPE_TINY, 0, (object) []);
        $meta3 = new FieldMetadata(MYSQLI_TYPE_TIMESTAMP, 0, (object) []);
        $dbi->expects($this->exactly(3))
            ->method('getFieldsMeta')
            ->will($this->onConsecutiveCalls([$meta1], [$meta2], [$meta3]));

        $resultStub->expects($this->exactly(3))
            ->method('fetchValue')
            ->will($this->onConsecutiveCalls(false, '123', '2013-08-28 06:34:14'));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extra_data);

        self::assertFalse($extra_data['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extra_data);

        self::assertSame('123', $extra_data['truncatableFieldValue']);
        self::assertTrue($extra_data['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData('db', 'table', 'a', $extra_data);

        self::assertSame('2013-08-28 06:34:14.000000', $extra_data['truncatableFieldValue']);
        self::assertTrue($extra_data['isNeedToRecheck']);
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
            ->will($this->returnValue([
                ['a' => 'b', 'c' => 'd'],
                ['e' => 'f', 'g' => 'h'],
            ]));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getTableColumns('db', 'table');

        self::assertSame([
            ['a' => 'b', 'c' => 'd'],
            ['e' => 'f', 'g' => 'h'],
        ], $result);
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
        if (PHP_VERSION_ID < 80100) {
            $response->setAccessible(true);
        }

        $response->setValue(null, $responseMock);

        $this->insertEdit = new InsertEdit($dbi);

        $result = $this->insertEdit->determineInsertOrEdit('1', 'db', 'table');

        self::assertEquals([
            false,
            null,
            [1],
            null,
            [$resultStub],
            [[]],
            false,
            'edit_next',
        ], $result);

        // case 2
        unset($_POST['where_clause']);
        unset($_SESSION['edit_next']);
        $_POST['default_action'] = '';

        $result = $this->insertEdit->determineInsertOrEdit(null, 'db', 'table');

        $response->setValue(null, $restoreInstance);

        self::assertSame([
            true,
            null,
            [],
            null,
            $resultStub,
            [
                false,
                false,
            ],
            false,
            'edit_next',
        ], $result);
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
                    [
                        [
                            'Comment' => 'b',
                            'Field' => 'd',
                        ],
                    ]
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

        self::assertSame([], $this->insertEdit->getCommentsMap('db', 'table'));

        $GLOBALS['cfg']['ShowPropertyComments'] = true;

        self::assertSame(['d' => 'b'], $this->insertEdit->getCommentsMap('db', 'table'));
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
        self::assertSame(sprintf($expected, $checked), $this->insertEdit->getHtmlForIgnoreOption(1));

        self::assertSame(sprintf($expected, ''), $this->insertEdit->getHtmlForIgnoreOption(1, false));
    }

    /**
     * Test for getHtmlForInsertEditFormColumn
     */
    public function testGetHtmlForInsertEditFormColumn(): void
    {
        $_SESSION[' HMAC_secret '] = hash('sha1', 'test');
        $o_rows = 0;
        $tabindex = 0;
        $GLOBALS['plugin_scripts'] = [];
        $foreigners = ['foreign_keys_data' => []];
        $table_column = [
            'Field' => 'col',
            'Type' => 'varchar(20)',
            'Null' => 'Yes',
            'Privileges' => 'insert,update,select',
        ];
        $repopulate = [md5('col') => 'val'];
        $column_mime = [
            'input_transformation' => 'Input/Image_JPEG_Upload.php',
            'input_transformation_options' => '150',
        ];

        $resultStub = $this->createMock(DummyResult::class);
        $resultStub->expects($this->any())
            ->method('getFieldsMeta')
            ->will($this->returnValue([new FieldMetadata(0, 0, (object) ['length' => -1])]));

        // Test w/ input transformation
        $actual = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlForInsertEditFormColumn',
            [
                $table_column,
                0,
                [],
                false,
                $resultStub,
                '',
                '',
                '',
                false,
                [],
                &$o_rows,
                &$tabindex,
                0,
                false,
                $foreigners,
                0,
                'table',
                'db',
                0,
                0,
                '',
                '',
                $repopulate,
                $column_mime,
                '',
            ]
        );

        $actual = $this->parseString($actual);

        self::assertStringContainsString('col', $actual);
        self::assertStringContainsString('<option>AES_ENCRYPT</option>', $actual);
        self::assertStringContainsString('<span class="column_type" dir="ltr">varchar(20)</span>', $actual);
        self::assertStringContainsString('<tr class="noclick">', $actual);
        self::assertStringContainsString('<span class="default_value hide">', $actual);
        self::assertStringContainsString('<img src="" width="150" height="100" alt="Image preview here">', $actual);
        self::assertStringContainsString('<input type="file" '
        . 'name="fields_upload[d89e2ddb530bb8953b290ab0793aecb0]" '
        . 'accept="image/*" '
        . 'class="image-upload"'
        . '>', $actual);

        // Test w/o input_transformation
        $table_column = [
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
                $table_column,
                0,
                [],
                false,
                $resultStub,
                '',
                '',
                '[a][0]',
                true,
                [],
                &$o_rows,
                &$tabindex,
                0,
                false,
                $foreigners,
                0,
                'table',
                'db',
                0,
                0,
                '',
                '',
                $repopulate,
                [],
                '',
            ]
        );

        $actual = $this->parseString($actual);

        self::assertStringContainsString('qwerty', $actual);
        self::assertStringContainsString('<option>UUID</option>', $actual);
        self::assertStringContainsString('<span class="column_type" dir="ltr">datetime</span>', $actual);
        self::assertStringContainsString(
            '<input type="text" name="fields[a][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="12-10-14.000000"',
            $actual
        );

        self::assertStringContainsString('<select name="funcs[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]"'
        . ' onchange="return verificationsAfterFieldChange(\'d8578edf8458ce06fbc5bb76a58c5ca4\','
        . ' \'0\', \'datetime\')" id="field_1_1">', $actual);
        self::assertStringContainsString('<option>DATE</option>', $actual);

        self::assertStringContainsString(
            '<input type="hidden" name="fields_null_prev[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]">',
            $actual
        );

        self::assertStringContainsString('<input type="checkbox" class="checkbox_null"'
        . ' name="fields_null[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" id="field_1_2"'
        . ' aria-label="Use the NULL value for this column.">', $actual);

        self::assertStringContainsString('<input type="hidden" class="nullify_code"'
        . ' name="nullify_code[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="5"', $actual);

        self::assertStringContainsString('<input type="hidden" class="hashed_field"'
        . ' name="hashed_field[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" '
        . 'value="d8578edf8458ce06fbc5bb76a58c5ca4">', $actual);

        self::assertStringContainsString('<input type="hidden" class="multi_edit"'
        . ' name="multi_edit[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="[multi_edit][0]"', $actual);
    }

    /**
     * Test for getHtmlForInsertEditRow
     */
    public function testGetHtmlForInsertEditRow(): void
    {
        $o_rows = 0;
        $tabindex = 0;
        $GLOBALS['plugin_scripts'] = [];
        $GLOBALS['cfg']['LongtextDoubleTextarea'] = true;
        $GLOBALS['cfg']['CharEditing'] = true;
        $GLOBALS['cfg']['TextareaRows'] = 10;
        $GLOBALS['cfg']['TextareaCols'] = 11;
        $foreigners = ['foreign_keys_data' => []];
        $table_columns = [
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
            ->will($this->returnValue([new FieldMetadata(0, 0, (object) ['length' => -1])]));

        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $table_columns,
            [],
            false,
            $resultStub,
            '',
            '',
            '',
            false,
            [],
            $o_rows,
            $tabindex,
            1,
            false,
            $foreigners,
            0,
            'table',
            'db',
            0,
            0,
            'ltr',
            [],
            ['wc']
        );
        self::assertStringContainsString('test', $actual);
        self::assertStringContainsString('<th>Column</th>', $actual);
        self::assertStringContainsString('<a', $actual);
        self::assertStringContainsString('<th class="w-50">Value</th>', $actual);
        self::assertStringContainsString('<span class="column_type" dir="ltr">longtext</span>', $actual);
        self::assertStringContainsString(
            '<textarea name="fields[multi_edit][0][098f6bcd4621d373cade4e832627b4f6]" id="field_1_3"'
            . ' data-type="CHAR" dir="ltr" rows="20" cols="22"',
            $actual
        );
    }

    /**
     * Test for getHtmlForInsertEditRow based on the column privilges
     */
    public function testGetHtmlForInsertEditRowBasedOnColumnPrivileges(): void
    {
        $o_rows = 0;
        $tabindex = 0;
        $GLOBALS['plugin_scripts'] = [];
        $GLOBALS['cfg']['LongtextDoubleTextarea'] = true;
        $GLOBALS['cfg']['CharEditing'] = true;
        $foreigners = ['foreign_keys_data' => []];

        // edit
        $table_columns = [
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
                new FieldMetadata(0, 0, (object) ['length' => -1]),
                new FieldMetadata(0, 0, (object) ['length' => -1]),
                new FieldMetadata(0, 0, (object) ['length' => -1]),
            ]));

        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $table_columns,
            [],
            false,
            $resultStub,
            '',
            '',
            '',
            false,
            [],
            $o_rows,
            $tabindex,
            1,
            false,
            $foreigners,
            0,
            'table',
            'db',
            0,
            0,
            '',
            [],
            ['wc']
        );
        self::assertStringContainsString('foo', $actual);
        self::assertStringNotContainsString('bar', $actual);

        // insert
        $table_columns = [
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
            $table_columns,
            [],
            false,
            $resultStub,
            '',
            '',
            '',
            true,
            [],
            $o_rows,
            $tabindex,
            3,
            false,
            $foreigners,
            0,
            'table',
            'db',
            0,
            0,
            '',
            [],
            ['wc']
        );
        self::assertStringContainsString('foo', $actual);
        self::assertStringContainsString(
            '<textarea name="fields[multi_edit][0][37b51d194a7513e45b56f6524f2d51f2]"',
            $actual
        );
        self::assertStringContainsString(
            '<a href="#" ><span class="text-nowrap"><img src="themes/dot.gif" title="Edit/Insert"'
            . ' alt="Edit/Insert" class="icon ic_b_edit">&nbsp;Edit/Insert</span></a>',
            $actual
        );
    }

    /**
     * Convert mixed type value to string
     *
     * @param mixed $value
     *
     * @return string
     */
    private function parseString($value)
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
