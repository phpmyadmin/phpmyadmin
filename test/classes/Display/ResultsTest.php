<?php
/**
 * Tests for displaying results
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_External;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Link;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use stdClass;
use function count;
use function hex2bin;
use const MYSQLI_TYPE_TIMESTAMP;
use const MYSQLI_TYPE_DATE;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_NOT_NULL_FLAG;

/**
 * Test cases for displaying results.
 */
class ResultsTest extends AbstractTestCase
{
    /** @var DisplayResults */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setLanguage();
        parent::setGlobalConfig();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $this->object = new DisplayResults('as', '', 0, '', '');
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $_SESSION[' HMAC_secret '] = 'test';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for isSelect function
     */
    public function testisSelect(): void
    {
        $parser = new Parser('SELECT * FROM pma');
        $this->assertTrue(
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'isSelect',
                [
                    [
                        'statement' => $parser->statements[0],
                        'select_from' => true,
                    ],
                ]
            )
        );
    }

    /**
     * Test for navigation buttons
     *
     * @param string $caption        iconic caption for button
     * @param string $title          text for button
     * @param int    $pos            position for next query
     * @param string $html_sql_query query ready for display
     *
     * @dataProvider providerForTestGetTableNavigationButton
     */
    public function testGetTableNavigationButton(
        string $caption,
        string $title,
        int $pos,
        string $html_sql_query
    ): void {
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $_SESSION[' PMA_token '] = 'token';

        $actual = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'getTableNavigationButton',
            [
                &$caption,
                $title,
                $pos,
                $html_sql_query,
                true,
            ]
        );

        $this->assertStringContainsString(
            '<form action="index.php?route=/sql',
            $actual
        );
        $this->assertStringContainsString(
            '" method="post" >',
            $actual
        );
        $this->assertStringContainsString(
            'name="sql_query" value="SELECT * FROM `pma_bookmark` WHERE 1"',
            $actual
        );
        $this->assertStringContainsString(
            'name="pos" value="1"',
            $actual
        );
        $this->assertStringContainsString(
            'value="btn" title="Submit"',
            $actual
        );
    }

    /**
     * Provider for testGetTableNavigationButton
     *
     * @return array array data for testGetTableNavigationButton
     */
    public function providerForTestGetTableNavigationButton(): array
    {
        return [
            [
                'btn',
                'Submit',
                1,
                'SELECT * FROM `pma_bookmark` WHERE 1',
            ],
        ];
    }

    /**
     * Provider for testing table navigation
     *
     * @return array data for testGetTableNavigation
     */
    public function providerForTestGetTableNavigation(): array
    {
        return [
            [
                21,
                41,
                false,
                '310',
            ],
        ];
    }

    /**
     * Data provider for testGetClassesForColumn
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetClassesForColumn(): array
    {
        return [
            [
                'grid_edit',
                'not_null',
                '',
                '',
                '',
                'data grid_edit not_null   ',
            ],
        ];
    }

    /**
     * @param string $grid_edit_class  the class for all editable columns
     * @param string $not_null_class   the class for not null columns
     * @param string $relation_class   the class for relations in a column
     * @param string $hide_class       the class for visibility of a column
     * @param string $field_type_class the class related to type of the field
     * @param string $output           output of__getResettedClassForInlineEdit
     *
     * @dataProvider dataProviderForTestGetClassesForColumn
     */
    public function testGetClassesForColumn(
        string $grid_edit_class,
        string $not_null_class,
        string $relation_class,
        string $hide_class,
        string $field_type_class,
        string $output
    ): void {
        $GLOBALS['cfg']['BrowsePointerEnable'] = true;
        $GLOBALS['cfg']['BrowseMarkerEnable'] = true;

        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getClassesForColumn',
                [
                    $grid_edit_class,
                    $not_null_class,
                    $relation_class,
                    $hide_class,
                    $field_type_class,
                ]
            )
        );
    }

    public function testGetClassForDateTimeRelatedFieldsCase1(): void
    {
        $this->assertEquals(
            'datetimefield',
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getClassForDateTimeRelatedFields',
                [new FieldMetadata(MYSQLI_TYPE_TIMESTAMP, 0, (object) [])]
            )
        );
    }

    public function testGetClassForDateTimeRelatedFieldsCase2(): void
    {
        $this->assertEquals(
            'datefield',
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getClassForDateTimeRelatedFields',
                [new FieldMetadata(MYSQLI_TYPE_DATE, 0, (object) [])]
            )
        );
    }

    public function testGetClassForDateTimeRelatedFieldsCase3(): void
    {
        $this->assertEquals(
            'text',
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getClassForDateTimeRelatedFields',
                [new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [])]
            )
        );
    }

    /**
     * Test for getOffsets - case 1
     */
    public function testGetOffsetsCase1(): void
    {
        $_SESSION['tmpval']['max_rows'] = DisplayResults::ALL_ROWS;
        $this->assertEquals(
            [
                0,
                0,
            ],
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getOffsets',
                []
            )
        );
    }

    /**
     * Test for getOffsets - case 2
     */
    public function testGetOffsetsCase2(): void
    {
        $_SESSION['tmpval']['max_rows'] = 5;
        $_SESSION['tmpval']['pos'] = 4;
        $this->assertEquals(
            [
                9,
                0,
            ],
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getOffsets',
                []
            )
        );
    }

    /**
     * Data provider for testGetSpecialLinkUrl
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetSpecialLinkUrl(): array
    {
        return [
            [
                'information_schema',
                'routines',
                'circumference',
                [
                    'routine_name' => 'circumference',
                    'routine_schema' => 'data',
                    'routine_type' => 'FUNCTION',
                ],
                'routine_name',
                'index.php?route=/database/routines&item_name=circumference&db=data'
                . '&item_type=FUNCTION&server=0&lang=en',
            ],
            [
                'information_schema',
                'routines',
                'area',
                [
                    'routine_name' => 'area',
                    'routine_schema' => 'data',
                    'routine_type' => 'PROCEDURE',
                ],
                'routine_name',
                'index.php?route=/database/routines&item_name=area&db=data'
                . '&item_type=PROCEDURE&server=0&lang=en',
            ],
            [
                'information_schema',
                'columns',
                'CHARACTER_SET_NAME',
                [
                    'table_schema' => 'information_schema',
                    'table_name' => 'CHARACTER_SETS',
                ],
                'column_name',
                'index.php?sql_query=SELECT+%60CHARACTER_SET_NAME%60+FROM+%60info'
                . 'rmation_schema%60.%60CHARACTER_SETS%60&db=information_schema'
                . '&test_name=value&server=0&lang=en',
            ],
        ];
    }

    /**
     * Test getSpecialLinkUrl
     *
     * @param string $db           the database name
     * @param string $table        the table name
     * @param string $column_value column value
     * @param array  $row_info     information about row
     * @param string $field_name   column name
     * @param string $output       output of getSpecialLinkUrl
     *
     * @dataProvider dataProviderForTestGetSpecialLinkUrl
     */
    public function testGetSpecialLinkUrl(
        string $db,
        string $table,
        string $column_value,
        array $row_info,
        string $field_name,
        string $output
    ): void {
        $specialSchemaLinks = [
            'information_schema' => [
                'routines' => [
                    'routine_name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'routine_schema',
                            ],
                            1 => [
                                'param_info' => 'item_type',
                                'column_name' => 'routine_type',
                            ],
                        ],
                        'default_page' => 'index.php?route=/database/routines',
                    ],
                ],
                'columns' => [
                    'column_name' => [
                        'link_param' => [
                            'sql_query',
                            'table_schema',
                            'table_name',
                        ],
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                            1 => [
                                'param_info' => [
                                    'test_name',
                                    'value',
                                ],
                            ],
                        ],
                        'default_page' => 'index.php',
                    ],
                ],
            ],
        ];

        $this->object->properties['db'] = $db;
        $this->object->properties['table'] = $table;

        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getSpecialLinkUrl',
                [
                    $specialSchemaLinks,
                    $column_value,
                    $row_info,
                    $field_name,
                ]
            )
        );
    }

    /**
     * Data provider for testGetRowInfoForSpecialLinks
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetRowInfoForSpecialLinks(): array
    {
        $column_names = [
            'host',
            'db',
            'user',
            'select_privilages',
        ];
        $fields_mata = [];

        foreach ($column_names as $column_name) {
            $field_meta = new stdClass();
            $field_meta->orgname = $column_name;
            $fields_mata[] = $field_meta;
        }

        return [
            [
                $fields_mata,
                count($fields_mata),
                [
                    0 => 'localhost',
                    1 => 'phpmyadmin',
                    2 => 'pmauser',
                    3 => 'Y',
                ],
                [
                    0 => '0',
                    1 => '3',
                    2 => '1',
                    3 => '2',
                ],
                [
                    'host' => 'localhost',
                    'select_privilages' => 'Y',
                    'db' => 'phpmyadmin',
                    'user' => 'pmauser',
                ],
            ],
        ];
    }

    /**
     * Test getRowInfoForSpecialLinks
     *
     * @param array $fields_meta  meta information about fields
     * @param int   $fields_count number of fields
     * @param array $row          current row data
     * @param array $col_order    the column order
     * @param array $output       output of getRowInfoForSpecialLinks
     *
     * @dataProvider dataProviderForTestGetRowInfoForSpecialLinks
     */
    public function testGetRowInfoForSpecialLinks(
        array $fields_meta,
        int $fields_count,
        array $row,
        array $col_order,
        array $output
    ): void {
        $this->object->properties['fields_meta'] = $fields_meta;
        $this->object->properties['fields_cnt'] = $fields_count;

        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getRowInfoForSpecialLinks',
                [
                    $row,
                    $col_order,
                ]
            )
        );
    }

    /**
     * Data provider for testSetHighlightedColumnGlobalField
     *
     * @return array parameters and output
     */
    public function dataProviderForTestSetHighlightedColumnGlobalField(): array
    {
        $parser = new Parser(
            'SELECT * FROM db_name WHERE `db_name`.`tbl`.id > 0 AND `id` < 10'
        );

        return [
            [
                ['statement' => $parser->statements[0]],
                [
                    'db_name' => 'true',
                    'tbl' => 'true',
                    'id' => 'true',
                ],
            ],
        ];
    }

    /**
     * Test setHighlightedColumnGlobalField
     *
     * @param array $analyzed_sql the analyzed query
     * @param array $output       setting value of setHighlightedColumnGlobalField
     *
     * @dataProvider dataProviderForTestSetHighlightedColumnGlobalField
     */
    public function testSetHighlightedColumnGlobalField(array $analyzed_sql, array $output): void
    {
        $this->callFunction(
            $this->object,
            DisplayResults::class,
            'setHighlightedColumnGlobalField',
            [$analyzed_sql]
        );

        $this->assertEquals(
            $output,
            $this->object->properties['highlight_columns']
        );
    }

    /**
     * Data provider for testGetPartialText
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetPartialText(): array
    {
        return [
            [
                'P',
                10,
                'foo',
                [
                    false,
                    'foo',
                    3,
                ],
            ],
            [
                'P',
                1,
                'foo',
                [
                    true,
                    'f...',
                    3,
                ],
            ],
            [
                'F',
                10,
                'foo',
                [
                    false,
                    'foo',
                    3,
                ],
            ],
            [
                'F',
                1,
                'foo',
                [
                    false,
                    'foo',
                    3,
                ],
            ],
        ];
    }

    /**
     * Test getPartialText
     *
     * @param string $pftext     Partial or Full text
     * @param int    $limitChars Partial or Full text
     * @param string $str        the string to be tested
     * @param array  $output     return value of getPartialText
     *
     * @dataProvider dataProviderForTestGetPartialText
     */
    public function testGetPartialText(string $pftext, int $limitChars, string $str, array $output): void
    {
        $_SESSION['tmpval']['pftext'] = $pftext;
        $GLOBALS['cfg']['LimitChars'] = $limitChars;
        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getPartialText',
                [$str]
            )
        );
    }

    /**
     * Data provider for testHandleNonPrintableContents
     *
     * @return array parameters and output
     */
    public function dataProviderForTestHandleNonPrintableContents(): array
    {
        $transformation_plugin = new Text_Plain_Link();
        $meta = new FieldMetadata(MYSQLI_TYPE_BLOB, 0, (object) ['orgtable' => 'bar']);
        $url_params = [
            'db' => 'foo',
            'table' => 'bar',
            'where_clause' => 'where_clause',
        ];

        return [
            [
                true,
                true,
                'BLOB',
                '1001',
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [],
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                $meta,
                $url_params,
                null,
                'class="disableAjax">1001</a>',
            ],
            [
                true,
                true,
                'BLOB',
                hex2bin('123456'),
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [],
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                $meta,
                $url_params,
                null,
                'class="disableAjax">0x123456</a>',
            ],
            [
                true,
                false,
                'BLOB',
                '1001',
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [],
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                $meta,
                $url_params,
                null,
                'class="disableAjax">[BLOB - 4 B]</a>',
            ],
            [
                false,
                false,
                'BINARY',
                '1001',
                $transformation_plugin,
                [],
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                $meta,
                $url_params,
                null,
                '1001',
            ],
            [
                false,
                true,
                'GEOMETRY',
                null,
                '',
                [],
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                $meta,
                $url_params,
                null,
                '[GEOMETRY - NULL]',
            ],
        ];
    }

    /**
     * Test handleNonPrintableContents
     *
     * @param bool         $display_binary        show binary contents?
     * @param bool         $display_blob          show blob contents?
     * @param string       $category              BLOB|BINARY|GEOMETRY
     * @param string       $content               the binary content
     * @param array|object $transformation_plugin transformation plugin.
     *                                            Can also be the default function:
     *                                             PhpMyAdmin\Core::mimeDefaultFunction
     * @param array|object $transform_options     transformation parameters
     * @param array        $default_function      default transformation function
     * @param object       $meta                  the meta-information about the field
     * @param array        $url_params            parameters that should go to the
     *                                            download link
     * @param bool|null    $is_truncated          the result is truncated or not
     * @param string       $output                the output of this function
     *
     * @dataProvider dataProviderForTestHandleNonPrintableContents
     */
    public function testHandleNonPrintableContents(
        bool $display_binary,
        bool $display_blob,
        string $category,
        ?string $content,
        $transformation_plugin,
        $transform_options,
        array $default_function,
        $meta,
        array $url_params,
        ?bool $is_truncated,
        string $output
    ): void {
        $_SESSION['tmpval']['display_binary'] = $display_binary;
        $_SESSION['tmpval']['display_blob'] = $display_blob;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $this->assertStringContainsString(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'handleNonPrintableContents',
                [
                    $category,
                    $content,
                    $transformation_plugin,
                    $transform_options,
                    $default_function,
                    $meta,
                    $url_params,
                    &$is_truncated,
                ]
            )
        );
    }

    /**
     * Data provider for testGetDataCellForNonNumericColumns
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetDataCellForNonNumericColumns(): array
    {
        $transformation_plugin = new Text_Plain_Link();
        $transformation_plugin_external = new Text_Plain_External();

        $meta = new stdClass();
        $meta->db = 'foo';
        $meta->table = 'tbl';
        $meta->orgtable = 'tbl';
        $meta->name = 'tblob';
        $meta->orgname = 'tblob';
        $meta->charsetnr = 63;
        $meta = new FieldMetadata(MYSQLI_TYPE_BLOB, 0, $meta);

        $meta2 = new stdClass();
        $meta2->db = 'foo';
        $meta2->table = 'tbl';
        $meta2->orgtable = 'tbl';
        $meta2->name = 'varchar';
        $meta2->orgname = 'varchar';
        $meta2 = new FieldMetadata(MYSQLI_TYPE_STRING, 0, $meta2);

        $meta3 = new stdClass();
        $meta3->db = 'foo';
        $meta3->table = 'tbl';
        $meta3->orgtable = 'tbl';
        $meta3->name = 'datetime';
        $meta3->orgname = 'datetime';
        $meta3 = new FieldMetadata(MYSQLI_TYPE_DATETIME, 0, $meta3);

        $url_params = [
            'db' => 'foo',
            'table' => 'tbl',
            'where_clause' => 'where_clause',
        ];

        return [
            [
                'all',
                '1001',
                'grid_edit',
                $meta,
                [],
                $url_params,
                false,
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                ['https://www.example.com/'],
                false,
                [],
                0,
                'binary',
                'class="disableAjax">[BLOB - 4 B]</a>' . "\n"
                . '</td>' . "\n",
            ],
            [
                'noblob',
                '1001',
                'grid_edit',
                $meta,
                [],
                $url_params,
                false,
                $transformation_plugin,
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [],
                false,
                [],
                0,
                'binary',
                '<td class="text-start grid_edit  transformed hex">' . "\n"
                . '    1001' . "\n"
                . '</td>' . "\n",
            ],
            [
                'noblob',
                null,
                'grid_edit',
                $meta2,
                [],
                $url_params,
                false,
                $transformation_plugin,
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [],
                false,
                [],
                0,
                '',
                '<td ' . "\n"
                . '    data-decimals="0"' . "\n"
                . '    data-type="string"' . "\n"
                . '        class="grid_edit  null">' . "\n"
                . '    <em>NULL</em>' . "\n"
                . '</td>' . "\n",
            ],
            [
                'all',
                'foo bar baz',
                'grid_edit',
                $meta2,
                [],
                $url_params,
                false,
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [],
                false,
                [],
                0,
                '',
                '<td data-decimals="0" data-type="string" '
                . 'data-originallength="11" '
                . 'class="grid_edit pre_wrap">foo bar baz</td>' . "\n",
            ],
            [
                'all',
                'foo bar baz',
                'grid_edit',
                $meta2,
                [],
                $url_params,
                false,
                $transformation_plugin_external,
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [],
                false,
                [],
                0,
                '',
                '<td data-decimals="0" data-type="string" '
                . 'data-originallength="11" '
                . 'class="grid_edit text-nowrap transformed">foo bar baz</td>' . "\n",
            ],
            [
                'all',
                '2020-09-20 16:35:00',
                'grid_edit',
                $meta3,
                [],
                $url_params,
                false,
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [
                    Core::class,
                    'mimeDefaultFunction',
                ],
                [],
                false,
                [],
                0,
                '',
                '<td data-decimals="0" data-type="datetime" '
                . 'data-originallength="19" '
                . 'class="grid_edit text-nowrap">2020-09-20 16:35:00</td>' . "\n",
            ],
        ];
    }

    /**
     * Test getDataCellForNonNumericColumns
     *
     * @param string       $protectBinary         all|blob|noblob|no
     * @param string|null  $column                the relevant column in data row
     * @param string       $class                 the html class for column
     * @param object       $meta                  the meta-information about the field
     * @param array        $map                   the list of relations
     * @param array        $_url_params           the parameters for generate url
     * @param bool         $condition_field       the column should highlighted
     *                                            or not
     * @param array|object $transformation_plugin the name of transformation function
     * @param array|object $default_function      the default transformation function
     * @param array        $transform_options     the transformation parameters
     * @param bool         $is_field_truncated    is data truncated due to LimitChars
     * @param array        $analyzed_sql_results  the analyzed query
     * @param int          $dt_result             the link id associated to the query
     *                                            which results have to be displayed
     * @param int|string   $col_index             the column index
     * @param string       $output                the output of this function
     *
     * @dataProvider dataProviderForTestGetDataCellForNonNumericColumns
     */
    public function testGetDataCellForNonNumericColumns(
        string $protectBinary,
        ?string $column,
        string $class,
        $meta,
        array $map,
        array $_url_params,
        bool $condition_field,
        $transformation_plugin,
        $default_function,
        array $transform_options,
        bool $is_field_truncated,
        array $analyzed_sql_results,
        int $dt_result,
        $col_index,
        string $output
    ): void {
        $_SESSION['tmpval']['display_binary'] = true;
        $_SESSION['tmpval']['display_blob'] = false;
        $_SESSION['tmpval']['relational_display'] = false;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['ProtectBinary'] = $protectBinary;
        $this->assertStringContainsString(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getDataCellForNonNumericColumns',
                [
                    $column,
                    $class,
                    $meta,
                    $map,
                    $_url_params,
                    $condition_field,
                    $transformation_plugin,
                    $default_function,
                    $transform_options,
                    $is_field_truncated,
                    $analyzed_sql_results,
                    &$dt_result,
                    $col_index,
                ]
            )
        );
    }

    /**
     * Simple output transformation test
     *
     * It mocks data needed to display two transformations and asserts
     * they are rendered.
     */
    public function testOutputTransformations(): void
    {
        // Fake relation settings
        $_SESSION['tmpval']['relational_display'] = 'K';
        $_SESSION['relation'][$GLOBALS['server']]['PMA_VERSION'] = PMA_VERSION;
        $_SESSION['relation'][$GLOBALS['server']]['mimework'] = true;
        $_SESSION['relation'][$GLOBALS['server']]['column_info'] = 'column_info';
        $GLOBALS['cfg']['BrowseMIME'] = true;

        // Basic data
        $result = 0;
        $query = 'SELECT 1';
        $this->object->properties['db'] = 'db';
        $this->object->properties['fields_cnt'] = 2;

        // Field meta information
        $meta = new stdClass();
        $meta->db = 'db';
        $meta->table = 'table';
        $meta->orgtable = 'table';
        $meta->name = '1';
        $meta->orgname = '1';
        $meta2 = new stdClass();
        $meta2->db = 'db';
        $meta2->table = 'table';
        $meta2->orgtable = 'table';
        $meta2->name = '2';
        $meta2->orgname = '2';
        $fields_meta = [
            new FieldMetadata(MYSQLI_TYPE_LONG, MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG, $meta),
            new FieldMetadata(MYSQLI_TYPE_LONG, MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG, $meta2),
        ];
        $this->object->properties['fields_meta'] = $fields_meta;

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // MIME transformations
        $dbi->expects($this->exactly(1))
            ->method('fetchResult')
            ->willReturn(
                [
                    'db.table.1' => [
                        'mimetype' => '',
                        'transformation' => 'output/text_plain_dateformat.php',
                    ],
                    'db.table.2' => [
                        'mimetype' => '',
                        'transformation' => 'output/text_plain_bool2text.php',
                    ],
                ]
            );

        $GLOBALS['dbi'] = $dbi;

        $transformations = new Transformations();
        $this->object->properties['mime_map'] = $transformations->getMime('db', 'table');

        // Actually invoke tested method
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'getRowValues',
            [
                &$result,
                [
                    3600,
                    true,
                ],
                0,
                false,
                [],
                '',
                false,
                $query,
                Query::getAll($query),
            ]
        );

        // Dateformat
        $this->assertStringContainsString(
            'Jan 01, 1970 at 01:00 AM',
            $output
        );
        // Bool2Text
        $this->assertStringContainsString(
            '>T<',
            $output
        );
        unset($_SESSION['tmpval']);
        unset($_SESSION['relation']);
    }
}
