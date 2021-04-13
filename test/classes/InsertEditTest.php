<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Header;
use PhpMyAdmin\InsertEdit;
use PhpMyAdmin\Response;
use PhpMyAdmin\Scripts;
use PhpMyAdmin\Table;
use PhpMyAdmin\Url;
use ReflectionProperty;
use stdClass;

use function hash;
use function md5;
use function sprintf;

use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_TINY;

/**
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
        parent::loadDefaultConfig();
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
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);
    }

    /**
     * Teardown all objects
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $response = new ReflectionProperty(Response::class, 'instance');
        $response->setAccessible(true);
        $response->setValue(null);
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

        $this->assertEquals(
            [
                'db'        => 'dbname',
                'table'     => 'tablename',
                'goto'      => 'index.php',
                'err_url'   => 'localhost',
                'sql_query' => 'SELECT a',
                'where_clause[foo]' => 'bar',
                'where_clause[1]' => 'test',
                'clause_is_unique' => false,
            ],
            $result
        );
    }

    /**
     * Test for getWhereClauseArray
     */
    public function testGetWhereClauseArray(): void
    {
        $this->assertEquals(
            [],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getWhereClauseArray',
                [null]
            )
        );

        $this->assertEquals(
            [
                1,
                2,
                3,
            ],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getWhereClauseArray',
                [[1, 2, 3]]
            )
        );

        $this->assertEquals(
            ['clause'],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getWhereClauseArray',
                ['clause']
            )
        );
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

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
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
                ['assoc1'],
                ['assoc2']
            );

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

        $this->assertEquals(
            [
                [
                    'a=1',
                    'b="fo\\\\o"',
                ],
                [
                    'result1',
                    'result2',
                ],
                [
                    ['assoc1'],
                    ['assoc2'],
                ],
                '',
            ],
            $result
        );
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

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with('result1')
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
                ['1' => 'result1'],
            ]
        );

        $this->assertTrue($result);

        // case 2
        $GLOBALS['cfg']['ShowSQL'] = false;

        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addHtml'])
            ->getMock();

        $restoreInstance = Response::getInstance();
        $response = new ReflectionProperty(Response::class, 'instance');
        $response->setAccessible(true);
        $response->setValue($responseMock);

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

        $response->setValue($restoreInstance);

        $this->assertFalse($result);
    }

    /**
     * Test for loadFirstRow
     */
    public function testLoadFirstRow(): void
    {
        $GLOBALS['cfg']['InsertRows'] = 2;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with(
                'SELECT * FROM `db`.`table` LIMIT 1;',
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            )
            ->will($this->returnValue('result1'));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'loadFirstRow',
            ['table', 'db']
        );

        $this->assertEquals(
            [
                'result1',
                [
                    false,
                    false,
                ],
            ],
            $result
        );
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

        $this->assertEquals(
            [
                '0' => 1,
                'where_clause' => 'bar=2',
                'sql_query' => 'SELECT 1',
            ],
            $result
        );
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

        $this->assertStringContainsString(
            'index.php?route=/table/change',
            $result
        );
        $this->assertStringContainsString(
            'ShowFunctionFields=1&amp;ShowFieldTypesInDataEditView=1&amp;goto=index.php%3Froute%3D%2Fsql',
            $result
        );
        $this->assertStringContainsString(
            'Function',
            $result
        );

        // case 2
        $result = $this->insertEdit->showTypeOrFunction('function', $url_params, true);

        $this->assertStringContainsString(
            'index.php?route=/table/change',
            $result
        );
        $this->assertStringContainsString(
            'ShowFunctionFields=0&amp;ShowFieldTypesInDataEditView=1&amp;goto=index.php%3Froute%3D%2Fsql',
            $result
        );
        $this->assertStringContainsString(
            'Function',
            $result
        );

        // case 3
        $result = $this->insertEdit->showTypeOrFunction('type', $url_params, false);

        $this->assertStringContainsString(
            'index.php?route=/table/change',
            $result
        );
        $this->assertStringContainsString(
            'ShowFunctionFields=1&amp;ShowFieldTypesInDataEditView=1&amp;goto=index.php%3Froute%3D%2Fsql',
            $result
        );
        $this->assertStringContainsString(
            'Type',
            $result
        );

        // case 4
        $result = $this->insertEdit->showTypeOrFunction('type', $url_params, true);

        $this->assertStringContainsString(
            'index.php?route=/table/change',
            $result
        );
        $this->assertStringContainsString(
            'ShowFunctionFields=1&amp;ShowFieldTypesInDataEditView=0&amp;goto=index.php%3Froute%3D%2Fsql',
            $result
        );
        $this->assertStringContainsString(
            'Type',
            $result
        );
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
            ' text-nowrap'
        );

        $this->assertEquals(
            $result['Field'],
            '1<2'
        );
    }

    /**
     * Test for getColumnTitle
     */
    public function testGetColumnTitle(): void
    {
        $column = [];
        $column['Field'] = 'f1<';
        $column['Field_html'] = 'f1&lt;';

        $this->assertEquals(
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getColumnTitle',
                [
                    $column,
                    [],
                ]
            ),
            'f1&lt;'
        );

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

        $this->assertStringContainsString(
            'title="comment&gt;"',
            $result
        );

        $this->assertStringContainsString(
            'f1&lt;',
            $result
        );
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
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'Binaryfoo';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'varbinaryfoo';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'barbinaryfoo';
        $this->assertFalse($this->insertEdit->isColumn($column, $types));

        $types = [
            'char',
            'varchar',
        ];

        $column['Type'] = 'char(10)';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'VarChar(20)';
        $this->assertTrue($this->insertEdit->isColumn($column, $types));

        $column['Type'] = 'foochar';
        $this->assertFalse($this->insertEdit->isColumn($column, $types));

        $types = [
            'blob',
            'tinyblob',
            'mediumblob',
            'longblob',
        ];

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
     */
    public function testGetEnumAndTimestampColumns(): void
    {
        $column = [];
        $column['True_Type'] = 'set';
        $this->assertEquals(
            [
                'set',
                '',
                false,
            ],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getEnumSetAndTimestampColumns',
                [
                    $column,
                    false,
                ]
            )
        );

        $column['True_Type'] = 'enum';
        $this->assertEquals(
            [
                'enum',
                '',
                false,
            ],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getEnumSetAndTimestampColumns',
                [
                    $column,
                    false,
                ]
            )
        );

        $column['True_Type'] = 'timestamp';
        $column['Type'] = 'date';
        $this->assertEquals(
            [
                'date',
                ' text-nowrap',
                true,
            ],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getEnumSetAndTimestampColumns',
                [
                    $column,
                    false,
                ]
            )
        );

        $column['True_Type'] = 'timestamp';
        $column['Type'] = 'date';
        $this->assertEquals(
            [
                'date',
                ' text-nowrap',
                false,
            ],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getEnumSetAndTimestampColumns',
                [
                    $column,
                    true,
                ]
            )
        );

        $column['True_Type'] = 'SET';
        $column['Type'] = 'num';
        $this->assertEquals(
            [
                'num',
                ' text-nowrap',
                false,
            ],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getEnumSetAndTimestampColumns',
                [
                    $column,
                    false,
                ]
            )
        );

        $column['True_Type'] = '';
        $column['Type'] = 'num';
        $this->assertEquals(
            [
                'num',
                ' text-nowrap',
                false,
            ],
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getEnumSetAndTimestampColumns',
                [
                    $column,
                    false,
                ]
            )
        );
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
        $this->assertEquals(
            '1',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [
                    $column,
                    $foreigners,
                    [],
                ]
            )
        );

        $column['True_Type'] = 'enum';
        $column['Type'] = 'abababababababababab';
        $this->assertEquals(
            '2',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [
                    $column,
                    $foreigners,
                    [],
                ]
            )
        );

        $column['True_Type'] = 'set';
        $this->assertEquals(
            '3',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [
                    $column,
                    $foreigners,
                    [],
                ]
            )
        );

        $column['True_Type'] = '';
        $foreigners['f'] = true;
        $foreignData['foreign_link'] = '';
        $this->assertEquals(
            '4',
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getNullifyCodeForNullColumn',
                [
                    $column,
                    $foreigners,
                    $foreignData,
                ]
            )
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

        $this->assertStringContainsString(
            '<textarea name="fieldsb" class="char charField" '
            . 'data-maxlength="10" rows="7" cols="1" dir="abc/" '
            . 'id="field_1_3" tabindex="2" data-type="CHAR">',
            $result
        );
    }

    /**
     * Test for getColumnEnumValues
     */
    public function testGetColumnEnumValues(): void
    {
        $extracted_columnspec = $column = [];
        $extracted_columnspec['enum_set_values'] = [
            '<abc>',
            '"foo"',
        ];

        $column['values'] = 'abc';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnEnumValues',
            [
                $column,
                $extracted_columnspec,
            ]
        );
        $this->assertEquals(
            [
                [
                    'plain' => '<abc>',
                    'html' => '&lt;abc&gt;',
                ],
                [
                    'plain' => '"foo"',
                    'html' => '&quot;foo&quot;',
                ],
            ],
            $result
        );
    }

    /**
     * Test for getColumnSetValueAndSelectSize
     */
    public function testGetColumnSetValueAndSelectSize(): void
    {
        $extracted_columnspec = $column = [];
        $extracted_columnspec['enum_set_values'] = [
            'a',
            '<',
        ];
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getColumnSetValueAndSelectSize',
            [
                [],
                $extracted_columnspec,
            ]
        );

        $this->assertEquals(
            [
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
            ],
            $result
        );

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
                $extracted_columnspec,
            ]
        );

        $this->assertEquals(
            [
                [
                    1,
                    2,
                ],
                3,
            ],
            $result
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

        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datefield" c tabindex="25" id="field_0_3">',
            $result
        );

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
        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datetimefield" c tabindex="25" id="field_0_3">',
            $result
        );

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
        $this->assertEquals(
            '<input type="text" name="fieldsa" value="b" size="30" data-type="DATE"'
            . ' class="textfield datetimefield" c tabindex="25" id="field_0_3">',
            $result
        );
    }

    /**
     * Test for getMaxUploadSize
     */
    public function testGetMaxUploadSize(): void
    {
        $GLOBALS['max_upload_size'] = 257;
        $column = [];
        $column['pma_type'] = 'tinyblob';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getMaxUploadSize',
            [
                $column,
                256,
            ]
        );

        $this->assertEquals(
            [
                "(Max: 256B)\n",
                256,
            ],
            $result
        );

        // case 2
        $GLOBALS['max_upload_size'] = 250;
        $column['pma_type'] = 'tinyblob';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getMaxUploadSize',
            [
                $column,
                20,
            ]
        );

        $this->assertEquals(
            [
                "(Max: 250B)\n",
                250,
            ],
            $result
        );
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
        $extracted_columnspec['spec_in_brackets'] = 25;
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

        $this->assertEquals(
            "a\n\na\n"
            . '<textarea name="fieldsb" class="char charField" '
            . 'data-maxlength="25" rows="7" cols="1" dir="/" '
            . 'id="field_1_3" c tabindex="34" data-type="CHAR">'
            . '&lt;</textarea>',
            $result
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

        $this->assertEquals(
            "a\n"
            . '<input type="text" name="fieldsb" value="&lt;" size="20" data-type="'
            . 'DATE" class="textfield datetimefield" c tabindex="34" id="field_1_3"'
            . '><input type="hidden" name="auto_incrementb" value="1">'
            . '<input type="hidden" name="fields_typeb" value="timestamp">',
            $result
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

        $this->assertStringContainsString(
            '<input type="hidden" name="fields_typeb" value="datetime">',
            $result
        );
    }

    /**
     * Test for getColumnSize
     */
    public function testGetColumnSize(): void
    {
        $column = $extracted_columnspec = [];
        $column['is_char'] = true;
        $extracted_columnspec['spec_in_brackets'] = 45;
        $GLOBALS['cfg']['MinSizeForInputField'] = 30;
        $GLOBALS['cfg']['MaxSizeForInputField'] = 40;

        $this->assertEquals(
            40,
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getColumnSize',
                [
                    $column,
                    $extracted_columnspec,
                ]
            )
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
            $this->callFunction(
                $this->insertEdit,
                InsertEdit::class,
                'getColumnSize',
                [
                    $column,
                    $extracted_columnspec,
                ]
            )
        );
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

        $result = $this->insertEdit->getContinueInsertionForm(
            'tbl',
            'db',
            $where_clause_array,
            'localhost'
        );

        $this->assertStringContainsString(
            '<form id="continueForm" method="post" action="' . Url::getFromRoute('/table/replace')
            . '" name="continueForm">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="db" value="db">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="table" value="tbl">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="goto" value="index.php">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="err_url" value="localhost">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="sql_query" value="SELECT 1">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="hidden" name="where_clause[0]" value="a&lt;b">',
            $result
        );
    }

    /**
     * Test for getActionsPanel
     */
    public function testGetActionsPanel(): void
    {
        $GLOBALS['cfg']['ShowHint'] = false;
        $result = $this->insertEdit->getActionsPanel(null, 'back', 2, 1, false);

        $this->assertStringContainsString(
            '<select name="submit_type" class="control_at_footer" tabindex="4">',
            $result
        );

        $this->assertStringContainsString(
            '<select name="after_insert"',
            $result
        );

        $this->assertStringContainsString(
            '<input type="submit" class="btn btn-primary control_at_footer" value="Go" '
            . 'tabindex="11" id="buttonYes"',
            $result
        );
    }

    /**
     * Test for getSubmitTypeDropDown
     */
    public function testGetSubmitTypeDropDown(): void
    {
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSubmitTypeDropDown',
            [
                [],
                2,
                2,
            ]
        );

        $this->assertStringContainsString(
            '<select name="submit_type" class="control_at_footer" tabindex="5">',
            $result
        );

        $this->assertStringContainsString(
            '<option value="save">',
            $result
        );
    }

    /**
     * Test for getAfterInsertDropDown
     */
    public function testGetAfterInsertDropDown(): void
    {
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getAfterInsertDropDown',
            [
                '`t`.`f` = 2',
                'new_insert',
                true,
            ]
        );

        $this->assertStringContainsString(
            '<option value="new_insert" selected="selected">',
            $result
        );

        $this->assertStringContainsString(
            '<option value="same_insert"',
            $result
        );

        $this->assertStringContainsString(
            '<option value="edit_next" >',
            $result
        );
    }

    /**
     * Test for getSubmitAndResetButtonForActionsPanel
     */
    public function testGetSubmitAndResetButtonForActionsPanel(): void
    {
        $GLOBALS['cfg']['ShowHint'] = false;
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSubmitAndResetButtonForActionsPanel',
            [
                1,
                0,
            ]
        );

        $this->assertStringContainsString(
            '<input type="submit" class="btn btn-primary control_at_footer" value="Go" '
            . 'tabindex="9" id="buttonYes">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="button" class="btn btn-secondary preview_sql" value="Preview SQL" '
            . 'tabindex="7">',
            $result
        );

        $this->assertStringContainsString(
            '<input type="reset" class="btn btn-secondary control_at_footer" value="Reset" '
            . 'tabindex="8">',
            $result
        );
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

        $this->assertStringContainsString(
            'index.php?route=/table/change',
            $result
        );

        $this->assertStringContainsString(
            'ShowFunctionFields=1&amp;ShowFieldTypesInDataEditView=0',
            $result
        );

        $this->assertStringContainsString(
            'ShowFunctionFields=0&amp;ShowFieldTypesInDataEditView=1',
            $result
        );
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
                false,
                [],
                'a',
                false,
            ]
        );

        $this->assertEquals(
            [
                true,
                null,
                null,
                null,
                '<input type="hidden" name="fields_preva" value="">',
            ],
            $result
        );

        // Case 2 (bit)
        unset($_POST['default_action']);

        $current_row['f'] = '123';
        $extracted_columnspec['spec_in_brackets'] = 20;
        $column['True_Type'] = 'bit';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                $extracted_columnspec,
                false,
                [],
                'a',
                false,
            ]
        );

        $this->assertEquals(
            [
                false,
                '',
                '00000000000001111011',
                null,
                '<input type="hidden" name="fields_preva" value="123">',
            ],
            $result
        );

        $current_row['f'] = 'abcd';
        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                $extracted_columnspec,
                false,
                [],
                'a',
                true,
            ]
        );

        $this->assertEquals(
            [
                false,
                '',
                'abcd',
                null,
                '<input type="hidden" name="fields_preva" value="abcd">',
            ],
            $result
        );

        // Case 3 (bit)
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $current_row['f'] = '123';
        $extracted_columnspec['spec_in_brackets'] = 20;
        $column['True_Type'] = 'int';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForExistingRow',
            [
                $current_row,
                $column,
                $extracted_columnspec,
                false,
                ['int'],
                'a',
                false,
            ]
        );

        $this->assertEquals(
            [
                false,
                '',
                "'',",
                null,
                '<input type="hidden" name="fields_preva" value="\'\',">',
            ],
            $result
        );

        // Case 4 (else)
        $column['is_binary'] = false;
        $column['is_blob'] = true;
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $current_row['f'] = '11001';
        $extracted_columnspec['spec_in_brackets'] = 20;
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
                false,
                ['int'],
                'a',
                false,
            ]
        );

        $this->assertEquals(
            [
                false,
                '3131303031',
                '3131303031',
                '3131303031',
                '<input type="hidden" name="fields_preva" value="3131303031">',
            ],
            $result
        );

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
                false,
                ['int'],
                'a',
                false,
            ]
        );

        $this->assertEquals(
            [
                false,
                '313130303100',
                '313130303100',
                '313130303100',
                '<input type="hidden" name="fields_preva" value="313130303100">',
            ],
            $result
        );
    }

    /**
     * Test for getSpecialCharsAndBackupFieldForInsertingMode
     */
    public function testGetSpecialCharsAndBackupFieldForInsertingMode(): void
    {
        $column = [];
        $column['True_Type'] = 'bit';
        $column['Default'] = 'b\'101\'';
        $column['is_binary'] = true;
        $GLOBALS['cfg']['ProtectBinary'] = false;
        $GLOBALS['cfg']['ShowFunctionFields'] = true;

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForInsertingMode',
            [
                $column,
                false,
            ]
        );

        $this->assertEquals(
            [
                false,
                'b\'101\'',
                '101',
                '',
                '101',
            ],
            $result
        );

        // case 2
        unset($column['Default']);
        $column['True_Type'] = 'char';

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getSpecialCharsAndBackupFieldForInsertingMode',
            [
                $column,
                false,
            ]
        );

        $this->assertEquals(
            [
                true,
                '',
                '',
                '',
                '',
            ],
            $result
        );
    }

    /**
     * Test for getParamsForUpdateOrInsert
     */
    public function testGetParamsForUpdateOrInsert(): void
    {
        $_POST['where_clause'] = 'LIMIT 1';
        $_POST['submit_type'] = 'showinsert';

        $result = $this->insertEdit->getParamsForUpdateOrInsert();

        $this->assertEquals(
            [
                ['LIMIT 1'],
                true,
                true,
                false,
            ],
            $result
        );

        // case 2 (else)
        unset($_POST['where_clause']);
        $_POST['fields']['multi_edit'] = [
            'a' => 'b',
            'c' => 'd',
        ];
        $result = $this->insertEdit->getParamsForUpdateOrInsert();

        $this->assertEquals(
            [
                [
                    'a',
                    'c',
                ],
                false,
                true,
                false,
            ],
            $result
        );
    }

    /**
     * Test for isInsertRow
     */
    public function testIsInsertRow(): void
    {
        $_POST['insert_rows'] = 5;
        $GLOBALS['cfg']['InsertRows'] = 2;

        $scriptsMock = $this->getMockBuilder(Scripts::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFile'])
            ->getMock();

        $scriptsMock->expects($this->exactly(2))
            ->method('addFile');

        $headerMock = $this->getMockBuilder(Header::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getScripts'])
            ->getMock();

        $headerMock->expects($this->once())
            ->method('getScripts')
            ->will($this->returnValue($scriptsMock));

        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getHeader'])
            ->getMock();

        $responseMock->expects($this->once())
            ->method('getHeader')
            ->will($this->returnValue($headerMock));

        $restoreInstance = Response::getInstance();
        $response = new ReflectionProperty(Response::class, 'instance');
        $response->setAccessible(true);
        $response->setValue($responseMock);

        $this->insertEdit->isInsertRow();

        $response->setValue($restoreInstance);

        $this->assertEquals(5, $GLOBALS['cfg']['InsertRows']);
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
        $res = 'foobar';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->with('SELECT * FROM `db`.`table` WHERE `a` > 2 LIMIT 1;')
            ->will($this->returnValue($res));

        $dbi->expects($this->once())
            ->method('fetchRow')
            ->with($res)
            ->will($this->returnValue($row));

        $dbi->expects($this->once())
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
     */
    public function testGetGotoInclude(): void
    {
        $GLOBALS['goto'] = '123.php';
        $GLOBALS['table'] = '';

        $this->assertEquals(
            '/database/sql',
            $this->insertEdit->getGotoInclude('index')
        );

        $GLOBALS['table'] = 'tbl';
        $this->assertEquals(
            '/table/sql',
            $this->insertEdit->getGotoInclude('index')
        );

        $GLOBALS['goto'] = 'index.php?route=/database/sql';

        $this->assertEquals(
            '/database/sql',
            $this->insertEdit->getGotoInclude('index')
        );

        $this->assertEquals(
            '',
            $GLOBALS['table']
        );

        $_POST['after_insert'] = 'new_insert';
        $this->assertEquals(
            '/table/change',
            $this->insertEdit->getGotoInclude('index')
        );
    }

    /**
     * Test for getErrorUrl
     */
    public function testGetErrorUrl(): void
    {
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $this->assertEquals(
            'index.php?route=/table/change&amp;lang=en',
            $this->insertEdit->getErrorUrl([])
        );

        $_POST['err_url'] = 'localhost';
        $this->assertEquals(
            'localhost',
            $this->insertEdit->getErrorUrl([])
        );
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

        $this->assertEquals(
            ['INSERT IGNORE INTO `table` (a, b) VALUES (1), (2)'],
            $this->insertEdit->buildSqlQuery(true, $query_fields, $value_sets)
        );

        $this->assertEquals(
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

        $this->assertEquals(['sql_query' => 'SELECT * FROM `test_db`.`test_table`;'], $result[0]);
        $this->assertEquals([], $result[3]);
        $this->assertEquals('SELECT * FROM `test_db`.`test_table`;', $result[5]);
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

        $this->assertEquals(['sql_query' => 'SELECT * FROM `test_db`.`test_table`;'], $result[0]);
        $this->assertEquals([], $result[3]);
        $this->assertEquals('SELECT * FROM `test_db`.`test_table`;', $result[5]);
    }

    /**
     * Test for getWarningMessages
     */
    public function testGetWarningMessages(): void
    {
        $warnings = [
            [
                'Level' => 1,
                'Code' => 42,
                'Message' => 'msg1',
            ],
            [
                'Level' => 2,
                'Code' => 43,
                'Message' => 'msg2',
            ],
        ];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getWarnings')
            ->will($this->returnValue($warnings));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getWarningMessages',
            []
        );

        $this->assertEquals(
            [
                '1: #42 msg1',
                '2: #43 msg2',
            ],
            $result
        );
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

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
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
            ->with('r1')
            ->will($this->returnValue(['2']));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

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

        $sqlSignature = Core::signSqlQuery(
            'SELECT * FROM `information_schema`.`TABLES` WHERE `f`=1'
        );

        $this->assertEquals(
            '<a href="index.php?route=/sql&amp;db=information_schema&amp;table=TABLES&amp;pos=0&amp;'
            . 'sql_signature=' . $sqlSignature . '&amp;'
            . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
            . '+%60f%60%3D1&amp;lang=en" title="a&gt;">b&lt;</a>',
            $result
        );

        $_SESSION['tmpval']['relational_display'] = 'D';
        $result = $this->insertEdit->getLinkForRelationalDisplayField($map, 'f', '=1', 'a>', 'b<');

        $this->assertEquals(
            '<a href="index.php?route=/sql&amp;db=information_schema&amp;table=TABLES&amp;pos=0&amp;'
            . 'sql_signature=' . $sqlSignature . '&amp;'
            . 'sql_query=SELECT+%2A+FROM+%60information_schema%60.%60TABLES%60+WHERE'
            . '+%60f%60%3D1&amp;lang=en" title="b&lt;">a&gt;</a>',
            $result
        );
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

        $this->assertEquals(
            [
                'a' => 'b',
                'transformations' => ['cnameoption ,, quoted'],
            ],
            $result
        );
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

        $this->assertEquals(
            [
                [
                    1,
                    'foo',
                ],
                [
                    2,
                    '`fld`',
                ],
            ],
            $result
        );

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

        $this->assertEquals(
            [
                [
                    1,
                    '`fld` = foo',
                ],
                [2],
            ],
            $result
        );

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

        $this->assertEquals(
            [
                [1],
                [2],
            ],
            $result
        );

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
            0,
            []
        );

        $this->assertEquals(
            [
                [
                    1,
                    '`fld` = foo',
                ],
                [2],
            ],
            $result
        );
    }

    /**
     * Test for getCurrentValueAsAnArrayForMultipleEdit
     */
    public function testGetCurrentValueAsAnArrayForMultipleEdit(): void
    {
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            [],
            [],
            [],
            'currVal',
            [],
            [],
            [],
            '0'
        );

        $this->assertEquals('currVal', $result);

        // case 2
        $multi_edit_funcs = ['UUID'];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with('SELECT UUID()')
            ->will($this->returnValue('uuid1234'));

        $GLOBALS['dbi'] = $dbi;
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

        $this->assertEquals("'uuid1234'", $result);

        // case 3
        $multi_edit_funcs = ['AES_ENCRYPT'];
        $multi_edit_salt = [''];
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            $multi_edit_salt,
            [],
            "'''",
            [],
            ['func'],
            ['func'],
            '0'
        );
        $this->assertEquals("AES_ENCRYPT(''','')", $result);

        // case 4
        $multi_edit_funcs = ['func'];
        $multi_edit_salt = [];
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            $multi_edit_salt,
            [],
            "'''",
            [],
            ['func'],
            ['func'],
            '0'
        );
        $this->assertEquals("func(''')", $result);

        // case 5
        $result = $this->insertEdit->getCurrentValueAsAnArrayForMultipleEdit(
            $multi_edit_funcs,
            $multi_edit_salt,
            [],
            "''",
            [],
            ['func'],
            ['func'],
            '0'
        );
        $this->assertEquals('func()', $result);
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

        $this->assertEquals(
            '123',
            $result
        );

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

        $this->assertEquals(
            'NULL',
            $result
        );

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

        $this->assertEquals(
            "''",
            $result
        );

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

        $this->assertEquals(
            "''",
            $result
        );

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

        $this->assertEquals(
            '0x313031',
            $result
        );

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

        $this->assertEquals(
            '',
            $result
        );

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

        $this->assertEquals(
            "b'00010'",
            $result
        );

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

        $this->assertEquals(
            "'20\\'12'",
            $result
        );

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

        $this->assertEquals(
            'NULL',
            $result
        );

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

        $this->assertEquals(
            "''",
            $result
        );
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

        $dbi->expects($this->exactly(3))
            ->method('tryQuery')
            ->with('SELECT `table`.`a` FROM `db`.`table` WHERE 1');

        $meta1 = new FieldMetadata(MYSQLI_TYPE_TINY, 0, (object) []);
        $meta2 = new FieldMetadata(MYSQLI_TYPE_TINY, 0, (object) []);
        $meta3 = new FieldMetadata(MYSQLI_TYPE_TIMESTAMP, 0, (object) []);
        $dbi->expects($this->exactly(3))
            ->method('getFieldsMeta')
            ->will($this->onConsecutiveCalls([$meta1], [$meta2], [$meta3]));

        $dbi->expects($this->exactly(3))
            ->method('fetchRow')
            ->will($this->onConsecutiveCalls(null, [0 => '123'], [0 => '2013-08-28 06:34:14']));

        $dbi->expects($this->exactly(3))
            ->method('freeResult');

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
            'db',
            'table',
            'a',
            $extra_data
        );

        $this->assertFalse($extra_data['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
            'db',
            'table',
            'a',
            $extra_data
        );

        $this->assertEquals('123', $extra_data['truncatableFieldValue']);
        $this->assertTrue($extra_data['isNeedToRecheck']);

        $this->insertEdit->verifyWhetherValueCanBeTruncatedAndAppendExtraData(
            'db',
            'table',
            'a',
            $extra_data
        );

        $this->assertEquals(
            '2013-08-28 06:34:14.000000',
            $extra_data['truncatableFieldValue']
        );
        $this->assertTrue($extra_data['isNeedToRecheck']);
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
            ->will($this->returnValue(['a' => 'b', 'c' => 'd']));

        $GLOBALS['dbi'] = $dbi;
        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->getTableColumns('db', 'table');

        $this->assertEquals(
            [
                'b',
                'd',
            ],
            $result
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

        $GLOBALS['dbi'] = $dbi;
        $_POST['where_clause'] = '1';
        $_SESSION['edit_next'] = '1';
        $_POST['ShowFunctionFields'] = true;
        $_POST['ShowFieldTypesInDataEditView'] = true;
        $_POST['after_insert'] = 'edit_next';
        $GLOBALS['cfg']['InsertRows'] = 2;
        $GLOBALS['cfg']['ShowSQL'] = false;
        $_POST['default_action'] = 'insert';

        $responseMock = $this->getMockBuilder(Response::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addHtml'])
            ->getMock();

        $restoreInstance = Response::getInstance();
        $response = new ReflectionProperty(Response::class, 'instance');
        $response->setAccessible(true);
        $response->setValue($responseMock);

        $this->insertEdit = new InsertEdit($GLOBALS['dbi']);

        $result = $this->insertEdit->determineInsertOrEdit('1', 'db', 'table');

        $this->assertEquals(
            [
                false,
                null,
                [1],
                null,
                [null],
                [null],
                false,
                'edit_next',
            ],
            $result
        );

        // case 2
        unset($_POST['where_clause']);
        unset($_SESSION['edit_next']);
        $_POST['default_action'] = '';

        $result = $this->insertEdit->determineInsertOrEdit(null, 'db', 'table');

        $response->setValue($restoreInstance);

        $this->assertEquals(
            [
                true,
                null,
                [],
                null,
                null,
                [
                    false,
                    false,
                ],
                false,
                'edit_next',
            ],
            $result
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
            ->with('db', 'table', null, true)
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

        $this->assertEquals(
            [],
            $this->insertEdit->getCommentsMap('db', 'table')
        );

        $GLOBALS['cfg']['ShowPropertyComments'] = true;

        $this->assertEquals(
            ['d' => 'b'],
            $this->insertEdit->getCommentsMap('db', 'table')
        );
    }

    /**
     * Test for getUrlParameters
     */
    public function testGetUrlParameters(): void
    {
        global $goto;

        $_POST['sql_query'] = 'SELECT';
        $goto = 'tbl_sql.php';

        $this->assertEquals(
            [
                'db' => 'foo',
                'sql_query' => 'SELECT',
                'table' => 'bar',
            ],
            $this->insertEdit->getUrlParameters('foo', 'bar')
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
            $this->insertEdit->getHtmlForIgnoreOption(1)
        );

        $this->assertEquals(
            sprintf($expected, ''),
            $this->insertEdit->getHtmlForIgnoreOption(1, false)
        );
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
        $table_columns = [
            [
                'Field' => 'col',
                'Type' => 'varchar(20)',
                'Null' => 'Yes',
                'Privileges' => 'insert,update,select',
            ],
        ];
        $repopulate = [md5('col') => 'val'];
        $column_mime = [
            'input_transformation' => 'Input/Image_JPEG_Upload.php',
            'input_transformation_options' => '150',
        ];

        // Test w/ input transformation
        $actual = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlForInsertEditFormColumn',
            [
                $table_columns,
                0,
                [],
                false,
                [],
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

        $this->assertStringContainsString(
            'col',
            $actual
        );
        $this->assertStringContainsString(
            '<option>AES_ENCRYPT</option>',
            $actual
        );
        $this->assertStringContainsString(
            '<span class="column_type" dir="ltr">varchar(20)</span>',
            $actual
        );
        $this->assertStringContainsString(
            '<tr class="noclick">',
            $actual
        );
        $this->assertStringContainsString(
            '<span class="default_value hide">',
            $actual
        );
        $this->assertStringContainsString(
            '<img src="" width="150" height="100" '
            . 'alt="Image preview here">',
            $actual
        );
        $this->assertStringContainsString(
            '<input type="file" '
            . 'name="fields_upload[d89e2ddb530bb8953b290ab0793aecb0]" '
            . 'accept="image/*" '
            . 'class="image-upload"'
            . '>',
            $actual
        );

        // Test w/o input_transformation
        $table_columns = [
            [
                'Field' => 'qwerty',
                'Type' => 'datetime',
                'Null' => 'Yes',
                'Key' => '',
                'Extra' => '',
                'Default' => null,
                'Privileges' => 'insert,update,select',
            ],
        ];
        $repopulate = [md5('qwerty') => '12-10-14'];
        $actual = $this->callFunction(
            $this->insertEdit,
            InsertEdit::class,
            'getHtmlForInsertEditFormColumn',
            [
                $table_columns,
                0,
                [],
                false,
                [],
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
        $this->assertStringContainsString('qwerty', $actual);
        $this->assertStringContainsString('<option>UUID</option>', $actual);
        $this->assertStringContainsString('<span class="column_type" dir="ltr">datetime</span>', $actual);
        $this->assertStringContainsString(
            '<input type="text" '
            . 'name="fields[a][0][d8578edf8458ce06fbc5bb76a58c5ca4]" '
            . 'value="12-10-14.000000"',
            $actual
        );

        $this->assertStringContainsString(
            '<select name="funcs[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]"'
            . ' onchange="return verificationsAfterFieldChange(\'d8578edf8458ce06fbc5bb76a58c5ca4\','
            . ' \'0\', \'datetime\')" id="field_1_1">',
            $actual
        );
        $this->assertStringContainsString('<option>DATE</option>', $actual);

        $this->assertStringContainsString(
            '<input type="hidden" name="fields_null_prev[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]">',
            $actual
        );

        $this->assertStringContainsString(
            '<input type="checkbox" class="checkbox_null"'
            . ' name="fields_null[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" id="field_1_2"'
            . ' aria-label="Use the NULL value for this column.">',
            $actual
        );

        $this->assertStringContainsString(
            '<input type="hidden" class="nullify_code"'
            . ' name="nullify_code[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="5"',
            $actual
        );

        $this->assertStringContainsString(
            '<input type="hidden" class="hashed_field"'
            . ' name="hashed_field[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" '
            . 'value="d8578edf8458ce06fbc5bb76a58c5ca4">',
            $actual
        );

        $this->assertStringContainsString(
            '<input type="hidden" class="multi_edit"'
            . ' name="multi_edit[multi_edit][0][d8578edf8458ce06fbc5bb76a58c5ca4]" value="[multi_edit][0]"',
            $actual
        );
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
        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $table_columns,
            [],
            false,
            [],
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
        $this->assertStringContainsString(
            'test',
            $actual
        );
        $this->assertStringContainsString(
            '<th>Column</th>',
            $actual
        );
        $this->assertStringContainsString(
            '<a',
            $actual
        );
        $this->assertStringContainsString(
            '<th class="fillPage">Value</th>',
            $actual
        );
        $this->assertStringContainsString(
            '<span class="column_type" dir="ltr">longtext</span>',
            $actual
        );
        $this->assertStringContainsString(
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
        $actual = $this->insertEdit->getHtmlForInsertEditRow(
            [],
            $table_columns,
            [],
            false,
            [],
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
        $this->assertStringContainsString(
            'foo',
            $actual
        );
        $this->assertStringNotContainsString(
            'bar',
            $actual
        );

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
            [],
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
        $this->assertStringContainsString(
            'foo',
            $actual
        );
        $this->assertStringContainsString(
            '<textarea name="fields[multi_edit][0][37b51d194a7513e45b56f6524f2d51f2]"',
            $actual
        );
        $this->assertStringContainsString(
            '<a href="#" target="_blank"><span class="text-nowrap"><img src="themes/dot.'
            . 'gif" title="Edit/Insert" alt="Edit/Insert" class="icon ic_b_edit">&nbsp;Edit/Insert'
            . '</span></a>',
            $actual
        );
    }
}
