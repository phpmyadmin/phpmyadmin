<?php
/**
 * Tests for displaying results
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Link;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Transformations;
use ReflectionClass;
use stdClass;
use function count;
use function hex2bin;

/**
 * Test cases for displaying results.
 */
class ResultsTest extends PmaTestCase
{
    /** @access protected */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $this->object = new DisplayResults('as', '', 0, '', '');
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $_SESSION[' HMAC_secret '] = 'test';

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('fieldFlags')
            ->will($this->returnArgument(1));

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
        unset($this->object);
    }

    /**
     * Call private functions by setting visibility to public.
     *
     * @param string $name   method name
     * @param array  $params parameters for the invocation
     *
     * @return mixed the output from the private method.
     */
    private function _callPrivateFunction($name, array $params)
    {
        $class = new ReflectionClass(DisplayResults::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($this->object, $params);
    }

    /**
     * Test for _isSelect function
     *
     * @return void
     */
    public function testisSelect()
    {
        $parser = new Parser('SELECT * FROM pma');
        $this->assertTrue(
            $this->_callPrivateFunction(
                '_isSelect',
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
     * @return void
     *
     * @dataProvider providerForTestGetTableNavigationButton
     */
    public function testGetTableNavigationButton(
        $caption,
        $title,
        $pos,
        $html_sql_query
    ) {
        $GLOBALS['cfg']['TableNavigationLinksMode'] = 'icons';
        $_SESSION[' PMA_token '] = 'token';

        $actual = $this->_callPrivateFunction(
            '_getTableNavigationButton',
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
    public function providerForTestGetTableNavigationButton()
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
    public function providerForTestGetTableNavigation()
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
    public function dataProviderForTestGetClassesForColumn()
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
     * Test for _getClassesForColumn
     *
     * @param string $grid_edit_class  the class for all editable columns
     * @param string $not_null_class   the class for not null columns
     * @param string $relation_class   the class for relations in a column
     * @param string $hide_class       the class for visibility of a column
     * @param string $field_type_class the class related to type of the field
     * @param string $output           output of__getResettedClassForInlineEdit
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetClassesForColumn
     */
    public function testGetClassesForColumn(
        $grid_edit_class,
        $not_null_class,
        $relation_class,
        $hide_class,
        $field_type_class,
        $output
    ) {
        $GLOBALS['cfg']['BrowsePointerEnable'] = true;
        $GLOBALS['cfg']['BrowseMarkerEnable'] = true;

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getClassesForColumn',
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

    /**
     * Test for _getClassForDateTimeRelatedFields - case 1
     *
     * @return void
     */
    public function testGetClassForDateTimeRelatedFieldsCase1()
    {
        $this->assertEquals(
            'datetimefield',
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                [DisplayResults::DATETIME_FIELD]
            )
        );
    }

    /**
     * Test for _getClassForDateTimeRelatedFields - case 2
     *
     * @return void
     */
    public function testGetClassForDateTimeRelatedFieldsCase2()
    {
        $this->assertEquals(
            'datefield',
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                [DisplayResults::DATE_FIELD]
            )
        );
    }

    /**
     * Test for _getClassForDateTimeRelatedFields - case 3
     *
     * @return void
     */
    public function testGetClassForDateTimeRelatedFieldsCase3()
    {
        $this->assertEquals(
            'text',
            $this->_callPrivateFunction(
                '_getClassForDateTimeRelatedFields',
                [DisplayResults::STRING_FIELD]
            )
        );
    }

    /**
     * Test for _getOffsets - case 1
     *
     * @return void
     */
    public function testGetOffsetsCase1()
    {
        $_SESSION['tmpval']['max_rows'] = DisplayResults::ALL_ROWS;
        $this->assertEquals(
            [
                0,
                0,
            ],
            $this->_callPrivateFunction('_getOffsets', [])
        );
    }

    /**
     * Test for _getOffsets - case 2
     *
     * @return void
     */
    public function testGetOffsetsCase2()
    {
        $_SESSION['tmpval']['max_rows'] = 5;
        $_SESSION['tmpval']['pos'] = 4;
        $this->assertEquals(
            [
                9,
                0,
            ],
            $this->_callPrivateFunction('_getOffsets', [])
        );
    }

    /**
     * Data provider for testGetSpecialLinkUrl
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetSpecialLinkUrl()
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
     * Test _getSpecialLinkUrl
     *
     * @param string $db           the database name
     * @param string $table        the table name
     * @param string $column_value column value
     * @param array  $row_info     information about row
     * @param string $field_name   column name
     * @param bool   $output       output of _getSpecialLinkUrl
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetSpecialLinkUrl
     */
    public function testGetSpecialLinkUrl(
        $db,
        $table,
        $column_value,
        $row_info,
        $field_name,
        $output
    ) {
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

        $this->object->__set('db', $db);
        $this->object->__set('table', $table);

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getSpecialLinkUrl',
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
    public function dataProviderForTestGetRowInfoForSpecialLinks()
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
     * Test _getRowInfoForSpecialLinks
     *
     * @param array $fields_meta  meta information about fields
     * @param int   $fields_count number of fields
     * @param array $row          current row data
     * @param array $col_order    the column order
     * @param bool  $output       output of _getRowInfoForSpecialLinks
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetRowInfoForSpecialLinks
     */
    public function testGetRowInfoForSpecialLinks(
        $fields_meta,
        $fields_count,
        $row,
        $col_order,
        $output
    ) {
        $this->object->__set('fields_meta', $fields_meta);
        $this->object->__set('fields_cnt', $fields_count);

        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getRowInfoForSpecialLinks',
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
    public function dataProviderForTestSetHighlightedColumnGlobalField()
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
     * Test _setHighlightedColumnGlobalField
     *
     * @param array $analyzed_sql the analyzed query
     * @param array $output       setting value of _setHighlightedColumnGlobalField
     *
     * @dataProvider dataProviderForTestSetHighlightedColumnGlobalField
     */
    public function testSetHighlightedColumnGlobalField($analyzed_sql, $output): void
    {
        $this->_callPrivateFunction(
            '_setHighlightedColumnGlobalField',
            [$analyzed_sql]
        );

        $this->assertEquals(
            $output,
            $this->object->__get('highlight_columns')
        );
    }

    /**
     * Data provider for testGetPartialText
     *
     * @return array parameters and output
     */
    public function dataProviderForTestGetPartialText()
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
     * Test _getPartialText
     *
     * @param string $pftext     Partial or Full text
     * @param int    $limitChars Partial or Full text
     * @param string $str        the string to be tested
     * @param bool   $output     return value of _getPartialText
     *
     * @dataProvider dataProviderForTestGetPartialText
     */
    public function testGetPartialText($pftext, $limitChars, $str, $output): void
    {
        $_SESSION['tmpval']['pftext'] = $pftext;
        $GLOBALS['cfg']['LimitChars'] = $limitChars;
        $this->assertEquals(
            $output,
            $this->_callPrivateFunction(
                '_getPartialText',
                [$str]
            )
        );
    }

    /**
     * Data provider for testHandleNonPrintableContents
     *
     * @return array parameters and output
     */
    public function dataProviderForTestHandleNonPrintableContents()
    {
        $transformation_plugin = new Text_Plain_Link();
        $meta = new stdClass();
        $meta->type = 'BLOB';
        $meta->orgtable = 'bar';
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
     * Test _handleNonPrintableContents
     *
     * @param bool   $display_binary        show binary contents?
     * @param bool   $display_blob          show blob contents?
     * @param string $category              BLOB|BINARY|GEOMETRY
     * @param string $content               the binary content
     * @param string $transformation_plugin transformation plugin.
     *                                      Can also be the default function:
     *                                      PhpMyAdmin\Core::mimeDefaultFunction
     * @param array  $transform_options     transformation parameters
     * @param string $default_function      default transformation function
     * @param object $meta                  the meta-information about the field
     * @param array  $url_params            parameters that should go to the
     *                                      download link
     * @param bool   $is_truncated          the result is truncated or not
     * @param string $output                the output of this function
     *
     * @return void
     *
     * @dataProvider dataProviderForTestHandleNonPrintableContents
     */
    public function testHandleNonPrintableContents(
        $display_binary,
        $display_blob,
        $category,
        $content,
        $transformation_plugin,
        array $transform_options,
        $default_function,
        $meta,
        $url_params,
        $is_truncated,
        $output
    ) {
        $_SESSION['tmpval']['display_binary'] = $display_binary;
        $_SESSION['tmpval']['display_blob'] = $display_blob;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $this->assertStringContainsString(
            $output,
            $this->_callPrivateFunction(
                '_handleNonPrintableContents',
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
    public function dataProviderForTestGetDataCellForNonNumericColumns()
    {
        $transformation_plugin = new Text_Plain_Link();
        $meta = new stdClass();
        $meta->db = 'foo';
        $meta->table = 'tbl';
        $meta->orgtable = 'tbl';
        $meta->type = 'BLOB';
        $meta->flags = 'blob binary';
        $meta->name = 'tblob';
        $meta->orgname = 'tblob';

        $meta2 = new stdClass();
        $meta2->db = 'foo';
        $meta2->table = 'tbl';
        $meta2->orgtable = 'tbl';
        $meta2->type = 'string';
        $meta2->flags = '';
        $meta2->decimals = 0;
        $meta2->name = 'varchar';
        $meta2->orgname = 'varchar';
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
                '<td class="left grid_edit  transformed hex">' . "\n"
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
                . 'class="grid_edit ">foo bar baz</td>' . "\n",
            ],
        ];
    }

    /**
     * Test _getDataCellForNonNumericColumns
     *
     * @param bool   $protectBinary         all|blob|noblob|no
     * @param string $column                the relevant column in data row
     * @param string $class                 the html class for column
     * @param object $meta                  the meta-information about the field
     * @param array  $map                   the list of relations
     * @param array  $_url_params           the parameters for generate url
     * @param bool   $condition_field       the column should highlighted
     *                                      or not
     * @param string $transformation_plugin the name of transformation function
     * @param string $default_function      the default transformation function
     * @param array  $transform_options     the transformation parameters
     * @param bool   $is_field_truncated    is data truncated due to LimitChars
     * @param array  $analyzed_sql_results  the analyzed query
     * @param int    $dt_result             the link id associated to the query
     *                                      which results have to be displayed
     * @param int    $col_index             the column index
     * @param string $output                the output of this function
     *
     * @return void
     *
     * @dataProvider dataProviderForTestGetDataCellForNonNumericColumns
     */
    public function testGetDataCellForNonNumericColumns(
        $protectBinary,
        $column,
        $class,
        $meta,
        $map,
        $_url_params,
        $condition_field,
        $transformation_plugin,
        $default_function,
        array $transform_options,
        $is_field_truncated,
        $analyzed_sql_results,
        $dt_result,
        $col_index,
        $output
    ) {
        $_SESSION['tmpval']['display_binary'] = true;
        $_SESSION['tmpval']['display_blob'] = false;
        $_SESSION['tmpval']['relational_display'] = false;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['ProtectBinary'] = $protectBinary;
        $this->assertStringContainsString(
            $output,
            $this->_callPrivateFunction(
                '_getDataCellForNonNumericColumns',
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
     *
     * @return void
     */
    public function testOutputTransformations()
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
        $this->object->__set('db', 'db');
        $this->object->__set('fields_cnt', 2);

        // Field meta information
        $meta = new stdClass();
        $meta->db = 'db';
        $meta->table = 'table';
        $meta->orgtable = 'table';
        $meta->type = 'INT';
        $meta->flags = '';
        $meta->name = '1';
        $meta->orgname = '1';
        $meta->not_null = true;
        $meta->numeric = true;
        $meta->primary_key = false;
        $meta->unique_key = false;
        $meta2 = new stdClass();
        $meta2->db = 'db';
        $meta2->table = 'table';
        $meta2->orgtable = 'table';
        $meta2->type = 'INT';
        $meta2->flags = '';
        $meta2->name = '2';
        $meta2->orgname = '2';
        $meta2->not_null = true;
        $meta2->numeric = true;
        $meta2->primary_key = false;
        $meta2->unique_key = false;
        $fields_meta = [
            $meta,
            $meta2,
        ];
        $this->object->__set('fields_meta', $fields_meta);

        $dbi = $this->getMockBuilder('PhpMyAdmin\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())->method('fieldFlags')
            ->willReturn('');

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
        $this->object->__set(
            'mime_map',
            $transformations->getMime('db', 'table')
        );

        // Actually invoke tested method
        $output = $this->_callPrivateFunction(
            '_getRowValues',
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
