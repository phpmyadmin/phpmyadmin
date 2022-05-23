<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\DisplayParts;
use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_External;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Link;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\StatementInfo;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use stdClass;

use function count;
use function explode;
use function hex2bin;
use function htmlspecialchars_decode;
use function urldecode;

use const MYSQLI_NOT_NULL_FLAG;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_BLOB;
use const MYSQLI_TYPE_DATE;
use const MYSQLI_TYPE_DATETIME;
use const MYSQLI_TYPE_DECIMAL;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_TYPE_TIMESTAMP;

/**
 * @covers \PhpMyAdmin\Display\Results
 */
class ResultsTest extends AbstractTestCase
{
    /** @var DisplayResults */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        $this->setTheme();
        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $this->object = new DisplayResults($this->dbi, 'as', '', 0, '', '');
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $_SESSION[' HMAC_secret '] = 'test';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
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
        $this->assertTrue(
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'isSelect',
                [StatementInfo::fromArray(Query::getAll('SELECT * FROM pma'))]
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
                'index.php?route=/database/routines&item_name=area&db=data&item_type=PROCEDURE&server=0&lang=en',
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
                        'link_param' => 'table_schema',
                        'link_dependancy_params' => [
                            0 => [
                                'param_info' => 'db',
                                'column_name' => 'table_schema',
                            ],
                            1 => [
                                'param_info' => 'db2',
                                'column_name' => 'table_schema',
                            ],
                        ],
                        'default_page' => 'index.php',
                    ],
                ],
            ],
        ];

        $this->assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getSpecialLinkUrl',
                [
                    $specialSchemaLinks[$db][$table][$field_name],
                    $column_value,
                    $row_info,
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
     * @param FieldMetadata[] $fields_meta  meta information about fields
     * @param int             $fields_count number of fields
     * @param array           $row          current row data
     * @param array           $col_order    the column order
     * @param array           $output       output of getRowInfoForSpecialLinks
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

    public function testSetHighlightedColumnGlobalField(): void
    {
        $query = 'SELECT * FROM db_name WHERE `db_name`.`tbl`.id > 0 AND `id` < 10';
        $this->callFunction(
            $this->object,
            DisplayResults::class,
            'setHighlightedColumnGlobalField',
            [StatementInfo::fromArray(Query::getAll($query))]
        );

        $this->assertEquals([
            'db_name' => 'true',
            'tbl' => 'true',
            'id' => 'true',
        ], $this->object->properties['highlight_columns']);
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
     * @return mixed[][]
     * @psalm-return array{array{
     *   bool,
     *   bool,
     *   string,
     *   string|null,
     *   TransformationsPlugin|null,
     *   array|object,
     *   object,
     *   array,
     *   bool|null,
     *   string
     * }}
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
                null,
                [],
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
                null,
                [],
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
                null,
                [],
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
                null,
                [],
                $meta,
                $url_params,
                null,
                '[GEOMETRY - NULL]',
            ],
        ];
    }

    /**
     * @param bool         $display_binary    show binary contents?
     * @param bool         $display_blob      show blob contents?
     * @param string       $category          BLOB|BINARY|GEOMETRY
     * @param string|null  $content           the binary content
     * @param array|object $transform_options transformation parameters
     * @param object       $meta              the meta-information about the field
     * @param array        $url_params        parameters that should go to the download link
     * @param bool|null    $is_truncated      the result is truncated or not
     * @param string       $output            the output of this function
     *
     * @dataProvider dataProviderForTestHandleNonPrintableContents
     */
    public function testHandleNonPrintableContents(
        bool $display_binary,
        bool $display_blob,
        string $category,
        ?string $content,
        ?TransformationsPlugin $transformation_plugin,
        $transform_options,
        object $meta,
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
                    $meta,
                    $url_params,
                    &$is_truncated,
                ]
            )
        );
    }

    /**
     * @return mixed[][]
     * @psalm-return array{array{
     *   string,
     *   string|null,
     *   string,
     *   object,
     *   array,
     *   array,
     *   bool,
     *   TransformationsPlugin|null,
     *   array,
     *   string
     * }}
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
                null,
                ['https://www.example.com/'],
                'class="disableAjax">[BLOB - 4 B]</a>'
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
                [],
                '<td class="text-start grid_edit transformed hex">'
                . '1001'
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
                [],
                '<td data-decimals="0"' . "\n"
                . '    data-type="string"' . "\n"
                . '        class="grid_edit null">' . "\n"
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
                null,
                [],
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
                [],
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
                null,
                [],
                '<td data-decimals="0" data-type="datetime" '
                . 'data-originallength="19" '
                . 'class="grid_edit text-nowrap">2020-09-20 16:35:00</td>' . "\n",
            ],
        ];
    }

    /**
     * @param string      $protectBinary     all|blob|noblob|no
     * @param string|null $column            the relevant column in data row
     * @param string      $class             the html class for column
     * @param object      $meta              the meta-information about the field
     * @param array       $map               the list of relations
     * @param array       $_url_params       the parameters for generate url
     * @param bool        $condition_field   the column should highlighted or not
     * @param array       $transform_options the transformation parameters
     * @param string      $output            the output of this function
     *
     * @dataProvider dataProviderForTestGetDataCellForNonNumericColumns
     */
    public function testGetDataCellForNonNumericColumns(
        string $protectBinary,
        ?string $column,
        string $class,
        object $meta,
        array $map,
        array $_url_params,
        bool $condition_field,
        ?TransformationsPlugin $transformation_plugin,
        array $transform_options,
        string $output
    ): void {
        $_SESSION['tmpval']['display_binary'] = true;
        $_SESSION['tmpval']['display_blob'] = false;
        $_SESSION['tmpval']['relational_display'] = false;
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['ProtectBinary'] = $protectBinary;
        $statementInfo = $this->createStub(StatementInfo::class);
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
                    $transform_options,
                    $statementInfo,
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
        $_SESSION['relation'] = [];
        $_SESSION['relation'][$GLOBALS['server']] = RelationParameters::fromArray([
            'db' => 'db',
            'mimework' => true,
            'column_info' => 'column_info',
        ])->toArray();
        $GLOBALS['cfg']['BrowseMIME'] = true;

        // Basic data
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
        $meta->blob = false;
        $meta2 = new stdClass();
        $meta2->db = 'db';
        $meta2->table = 'table';
        $meta2->orgtable = 'table';
        $meta2->name = '2';
        $meta2->orgname = '2';
        $meta2->blob = false;
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
                [
                    3600,
                    true,
                ],
                0,
                false,
                [],
                'disabled',
                false,
                $query,
                StatementInfo::fromArray(Query::getAll($query)),
            ]
        );

        // Dateformat
        $this->assertStringContainsString('Jan 01, 1970 at 01:00 AM', $output);
        // Bool2Text
        $this->assertStringContainsString('>T<', $output);
    }

    public function dataProviderGetSortOrderHiddenInputs(): array
    {
        // SQL to add the column
        // SQL to remove the column
        // The URL params
        // The column name
        return [
            [
                '',
                '',
                ['sql_query' => ''],
                'colname',
                '',
            ],
            [
                'SELECT * FROM `gis_all` ORDER BY `gis_all`.`shape` DESC, `gis_all`.`name` ASC',
                'SELECT * FROM `gis_all` ORDER BY `gis_all`.`name` ASC',
                ['sql_query' => 'SELECT * FROM `gis_all` ORDER BY `gis_all`.`shape` DESC, `gis_all`.`name` ASC'],
                'shape',
                '',
            ],
            [
                'SELECT * FROM `gis_all` ORDER BY `gis_all`.`shape` DESC, `gis_all`.`name` ASC',
                'SELECT * FROM `gis_all` ORDER BY `gis_all`.`shape` DESC',
                ['sql_query' => 'SELECT * FROM `gis_all` ORDER BY `gis_all`.`shape` DESC, `gis_all`.`name` ASC'],
                'name',
                '',
            ],
            [
                'SELECT * FROM `gis_all`',
                'SELECT * FROM `gis_all`',
                ['sql_query' => 'SELECT * FROM `gis_all`'],
                'name',
                '',
            ],
            [
                'SELECT * FROM `gd_cities` ORDER BY `gd_cities`.`region_slug` DESC, '
                . '`gd_cities`.`country_slug` ASC, `gd_cities`.`city_id` ASC, `gd_cities`.`city` ASC',
                'SELECT * FROM `gd_cities` ORDER BY `gd_cities`.`region_slug` DESC, '
                . '`gd_cities`.`country_slug` ASC, `gd_cities`.`city_id` ASC, `gd_cities`.`city` ASC',
                [
                    'sql_query' => 'SELECT * FROM `gd_cities` ORDER BY `gd_cities`.`region_slug` DESC, '
                . '`gd_cities`.`country_slug` ASC, `gd_cities`.`city_id` ASC, `gd_cities`.`city` ASC',
                ],
                '',
                '',
            ],
            [
                'SELECT * FROM `gd_cities` ORDER BY `gd_cities`.`region_slug` DESC, '
                . '`gd_cities`.`country_slug` ASC, `gd_cities`.`city_id` ASC, `gd_cities`.`city` ASC',
                'SELECT * FROM `gd_cities` ORDER BY `gd_cities`.`country_slug` ASC, `gd_cities`.`city_id`'
                . ' ASC, `gd_cities`.`city` ASC',
                [
                    'sql_query' => 'SELECT * FROM `gd_cities` ORDER BY `gd_cities`.`region_slug` DESC, '
                . '`gd_cities`.`country_slug` ASC, `gd_cities`.`city_id` ASC, `gd_cities`.`city` ASC',
                ],
                'region_slug',
                '',
            ],
            [
                'SELECT * FROM `gis_all` ORDER BY `gis_all`.`shape` DESC',
                'SELECT * FROM `gis_all`',
                ['sql_query' => 'SELECT * FROM `gis_all` ORDER BY `gis_all`.`shape` DESC'],
                'shape',
                '&discard_remembered_sort=1',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderGetSortOrderHiddenInputs
     */
    public function testGetSortOrderHiddenInputs(
        string $sqlAdd,
        string $sqlRemove,
        array $urlParams,
        string $colName,
        string $urlParamsRemove
    ): void {
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'getSortOrderHiddenInputs',
            [
                $urlParams,
                $colName,
            ]
        );
        $out = urldecode(htmlspecialchars_decode($output));
        $this->assertStringContainsString(
            'name="url-remove-order" value="index.php?route=/sql&sql_query=' . $sqlRemove,
            $out,
            'The remove query should be found'
        );

        $this->assertStringContainsString(
            'name="url-add-order" value="index.php?route=/sql&sql_query=' . $sqlAdd,
            $out,
            'The add query should be found'
        );

        $firstLine = explode("\n", $out)[0] ?? '';
        $this->assertStringContainsString(
            'url-remove-order',
            $firstLine,
            'The first line should contain url-remove-order input'
        );
        $this->assertStringNotContainsString(
            'url-add-order',
            $firstLine,
            'The first line should contain NOT url-add-order input'
        );

        $this->assertStringContainsString($urlParamsRemove, $firstLine, 'The first line should contain the URL params');
    }

    /**
     * @see https://github.com/phpmyadmin/phpmyadmin/issues/16836
     */
    public function testBuildValueDisplayNoTrainlingSpaces(): void
    {
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'buildValueDisplay',
            [
                'my_class',
                false,
                '  special value  ',
            ]
        );
        $this->assertSame('<td class="text-start my_class">  special value  </td>' . "\n", $output);
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'buildValueDisplay',
            [
                'my_class',
                false,
                '0x11e6ac0cfb1e8bf3bf48b827ebdafb0b',
            ]
        );
        $this->assertSame('<td class="text-start my_class">0x11e6ac0cfb1e8bf3bf48b827ebdafb0b</td>' . "\n", $output);
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'buildValueDisplay',
            [
                'my_class',
                true,// condition mode
                '0x11e6ac0cfb1e8bf3bf48b827ebdafb0b',
            ]
        );
        $this->assertSame(
            '<td class="text-start my_class condition">0x11e6ac0cfb1e8bf3bf48b827ebdafb0b</td>' . "\n",
            $output
        );
    }

    /**
     * @dataProvider providerSetConfigParamsForDisplayTable
     */
    public function testSetConfigParamsForDisplayTable(
        array $session,
        array $get,
        array $post,
        array $request,
        array $expected
    ): void {
        $_SESSION = $session;
        $_GET = $get;
        $_POST = $post;
        $_REQUEST = $request;

        $db = 'test_db';
        $table = 'test_table';
        $query = 'SELECT * FROM `test_db`.`test_table`;';

        $object = new DisplayResults($this->dbi, $db, $table, 1, '', $query);
        $object->setConfigParamsForDisplayTable();

        $this->assertArrayHasKey('tmpval', $_SESSION);
        $this->assertIsArray($_SESSION['tmpval']);
        $this->assertSame($expected, $_SESSION['tmpval']);
    }

    public function providerSetConfigParamsForDisplayTable(): array
    {
        $cfg = ['RelationalDisplay' => DisplayResults::RELATIONAL_KEY, 'MaxRows' => 25, 'RepeatCells' => 100];

        return [
            'default values' => [
                [],
                [],
                [],
                [],
                [
                    'query' => [
                        '27b1330f2076ef45d236f20839a92831' => [
                            'sql' => 'SELECT * FROM `test_db`.`test_table`;',
                            'repeat_cells' => $cfg['RepeatCells'],
                            'max_rows' => $cfg['MaxRows'],
                            'pos' => 0,
                            'pftext' => DisplayResults::DISPLAY_PARTIAL_TEXT,
                            'relational_display' => $cfg['RelationalDisplay'],
                            'geoOption' => DisplayResults::GEOMETRY_DISP_GEOM,
                            'display_binary' => true,
                        ],
                    ],
                    'pftext' => DisplayResults::DISPLAY_PARTIAL_TEXT,
                    'relational_display' => $cfg['RelationalDisplay'],
                    'geoOption' => DisplayResults::GEOMETRY_DISP_GEOM,
                    'display_binary' => true,
                    'display_blob' => false,
                    'hide_transformation' => false,
                    'pos' => 0,
                    'max_rows' => $cfg['MaxRows'],
                    'repeat_cells' => $cfg['RepeatCells'],
                ],
            ],
            'cached values' => [
                [
                    'tmpval' => [
                        'query' => [
                            '27b1330f2076ef45d236f20839a92831' => [
                                'sql' => 'SELECT * FROM `test_db`.`test_table`;',
                                'repeat_cells' => 90,
                                'max_rows' => 26,
                                'pos' => 1,
                                'pftext' => DisplayResults::DISPLAY_FULL_TEXT,
                                'relational_display' => DisplayResults::RELATIONAL_DISPLAY_COLUMN,
                                'geoOption' => DisplayResults::GEOMETRY_DISP_WKB,
                                'display_binary' => false,
                            ],
                            'a' => [],
                            'b' => [],
                            'c' => [],
                            'd' => [],
                            'e' => [],
                            'f' => [],
                            'g' => [],
                            'h' => [],
                            'i' => [],
                            'j' => [],
                        ],
                    ],
                ],
                [],
                [],
                [],
                [
                    'query' => [
                        'b' => [],
                        'c' => [],
                        'd' => [],
                        'e' => [],
                        'f' => [],
                        'g' => [],
                        'h' => [],
                        'i' => [],
                        'j' => [],
                        '27b1330f2076ef45d236f20839a92831' => [
                            'sql' => 'SELECT * FROM `test_db`.`test_table`;',
                            'repeat_cells' => 90,
                            'max_rows' => 26,
                            'pos' => 1,
                            'pftext' => DisplayResults::DISPLAY_FULL_TEXT,
                            'relational_display' => DisplayResults::RELATIONAL_DISPLAY_COLUMN,
                            'geoOption' => DisplayResults::GEOMETRY_DISP_WKB,
                            'display_binary' => true,
                        ],
                    ],
                    'pftext' => DisplayResults::DISPLAY_FULL_TEXT,
                    'relational_display' => DisplayResults::RELATIONAL_DISPLAY_COLUMN,
                    'geoOption' => DisplayResults::GEOMETRY_DISP_WKB,
                    'display_binary' => true,
                    'display_blob' => false,
                    'hide_transformation' => false,
                    'pos' => 1,
                    'max_rows' => 26,
                    'repeat_cells' => 90,
                ],
            ],
            'default and request values' => [
                [],
                ['session_max_rows' => '27'],
                ['session_max_rows' => '28'],
                [
                    'pos' => '2',
                    'pftext' => DisplayResults::DISPLAY_FULL_TEXT,
                    'relational_display' => DisplayResults::RELATIONAL_DISPLAY_COLUMN,
                    'geoOption' => DisplayResults::GEOMETRY_DISP_WKT,
                    'display_binary' => '0',
                    'display_blob' => '0',
                    'hide_transformation' => '0',
                ],
                [
                    'query' => [
                        '27b1330f2076ef45d236f20839a92831' => [
                            'sql' => 'SELECT * FROM `test_db`.`test_table`;',
                            'repeat_cells' => $cfg['RepeatCells'],
                            'max_rows' => 27,
                            'pos' => 2,
                            'pftext' => DisplayResults::DISPLAY_FULL_TEXT,
                            'relational_display' => DisplayResults::RELATIONAL_DISPLAY_COLUMN,
                            'geoOption' => DisplayResults::GEOMETRY_DISP_WKT,
                            'display_binary' => true,
                            'display_blob' => true,
                            'hide_transformation' => true,
                        ],
                    ],
                    'pftext' => DisplayResults::DISPLAY_FULL_TEXT,
                    'relational_display' => DisplayResults::RELATIONAL_DISPLAY_COLUMN,
                    'geoOption' => DisplayResults::GEOMETRY_DISP_WKT,
                    'display_binary' => true,
                    'display_blob' => true,
                    'hide_transformation' => true,
                    'pos' => 2,
                    'max_rows' => 27,
                    'repeat_cells' => $cfg['RepeatCells'],
                ],
            ],
            'cached and request values' => [
                [
                    'tmpval' => [
                        'query' => [
                            '27b1330f2076ef45d236f20839a92831' => [
                                'sql' => 'SELECT * FROM `test_db`.`test_table`;',
                                'repeat_cells' => $cfg['RepeatCells'],
                                'max_rows' => $cfg['MaxRows'],
                                'pos' => 0,
                                'pftext' => DisplayResults::DISPLAY_FULL_TEXT,
                                'relational_display' => DisplayResults::RELATIONAL_DISPLAY_COLUMN,
                                'geoOption' => DisplayResults::GEOMETRY_DISP_GEOM,
                                'display_binary' => true,
                            ],
                            'a' => [],
                            'b' => [],
                            'c' => [],
                            'd' => [],
                            'e' => [],
                            'f' => [],
                            'g' => [],
                            'h' => [],
                            'i' => [],
                        ],
                    ],
                ],
                [],
                ['session_max_rows' => DisplayResults::ALL_ROWS],
                [
                    'pos' => 'NaN',
                    'pftext' => DisplayResults::DISPLAY_PARTIAL_TEXT,
                    'relational_display' => DisplayResults::RELATIONAL_KEY,
                    'geoOption' => DisplayResults::GEOMETRY_DISP_WKB,
                    'display_options_form' => '0',
                ],
                [
                    'query' => [
                        'a' => [],
                        'b' => [],
                        'c' => [],
                        'd' => [],
                        'e' => [],
                        'f' => [],
                        'g' => [],
                        'h' => [],
                        'i' => [],
                        '27b1330f2076ef45d236f20839a92831' => [
                            'sql' => 'SELECT * FROM `test_db`.`test_table`;',
                            'repeat_cells' => $cfg['RepeatCells'],
                            'max_rows' => DisplayResults::ALL_ROWS,
                            'pos' => 0,
                            'pftext' => DisplayResults::DISPLAY_PARTIAL_TEXT,
                            'relational_display' => DisplayResults::RELATIONAL_KEY,
                            'geoOption' => DisplayResults::GEOMETRY_DISP_WKB,
                        ],
                    ],
                    'pftext' => DisplayResults::DISPLAY_PARTIAL_TEXT,
                    'relational_display' => DisplayResults::RELATIONAL_KEY,
                    'geoOption' => DisplayResults::GEOMETRY_DISP_WKB,
                    'display_binary' => false,
                    'display_blob' => false,
                    'hide_transformation' => false,
                    'pos' => 0,
                    'max_rows' => DisplayResults::ALL_ROWS,
                    'repeat_cells' => $cfg['RepeatCells'],
                ],
            ],
        ];
    }

    public function testGetTable(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $query = 'SELECT * FROM `test_db`.`test_table`;';

        $object = new DisplayResults($this->dbi, $GLOBALS['db'], $GLOBALS['table'], 1, '', $query);
        $object->properties['unique_id'] = 1234567890;

        [$statementInfo] = ParseAnalyze::sqlQuery($query, $GLOBALS['db']);
        $fieldsMeta = [
            new FieldMetadata(
                MYSQLI_TYPE_DECIMAL,
                MYSQLI_PRI_KEY_FLAG | MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG,
                (object) ['name' => 'id']
            ),
            new FieldMetadata(MYSQLI_TYPE_STRING, MYSQLI_NOT_NULL_FLAG, (object) ['name' => 'name']),
            new FieldMetadata(MYSQLI_TYPE_DATETIME, MYSQLI_NOT_NULL_FLAG, (object) ['name' => 'datetimefield']),
        ];

        $object->setProperties(
            3,
            $fieldsMeta,
            $statementInfo->isCount,
            $statementInfo->isExport,
            $statementInfo->isFunction,
            $statementInfo->isAnalyse,
            3,
            count($fieldsMeta),
            1.234,
            'ltr',
            $statementInfo->isMaint,
            $statementInfo->isExplain,
            $statementInfo->isShow,
            null,
            null,
            true,
            false
        );

        $_SESSION = ['tmpval' => [], ' PMA_token ' => 'token'];
        $_SESSION['tmpval']['geoOption'] = '';
        $_SESSION['tmpval']['hide_transformation'] = '';
        $_SESSION['tmpval']['display_blob'] = '';
        $_SESSION['tmpval']['display_binary'] = '';
        $_SESSION['tmpval']['relational_display'] = '';
        $_SESSION['tmpval']['possible_as_geometry'] = '';
        $_SESSION['tmpval']['pftext'] = '';
        $_SESSION['tmpval']['max_rows'] = 25;
        $_SESSION['tmpval']['pos'] = 0;
        $_SESSION['tmpval']['repeat_cells'] = 0;
        $_SESSION['tmpval']['query']['27b1330f2076ef45d236f20839a92831']['max_rows'] = 25;

        $dtResult = $this->dbi->tryQuery($query);

        $displayParts = DisplayParts::fromArray([
            'hasEditLink' => true,
            'deleteLink' => DisplayParts::DELETE_ROW,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => false,
            'hasPrintLink' => true,
        ]);

        $this->assertNotFalse($dtResult);
        $actual = $object->getTable($dtResult, $displayParts, $statementInfo);

        $template = new Template();

        $tableHeadersForColumns = $template->render('display/results/table_headers_for_columns', [
            'is_sortable' => true,
            'columns' => [
                [
                    'column_name' => 'id',
                    'order_link' => '<a href="index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0AORDER+BY+%60id%60+ASC'
                        . '&sql_signature=dcfe20b407b35309f6af81f745e77a10f723d39b082d2a8f9cb8e75b17c4d3ce'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en" class="sortlink">id'
                        . '<input type="hidden" value="'
                        . 'index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0AORDER+BY+%60id%60+ASC'
                        . '&sql_signature=dcfe20b407b35309f6af81f745e77a10f723d39b082d2a8f9cb8e75b17c4d3ce'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en"></a>'
                        . '<input type="hidden" name="url-remove-order" value="index.php?route=/sql&db=test_db'
                        . '&table=test_table&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60'
                        . '&sql_signature=61b0c8c5657483469636496ed02311acefd66dda3892b0d5b23d23c621486dd7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en'
                        . '&discard_remembered_sort=1">' . "\n"
                        . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0AORDER+BY+%60id%60+ASC'
                        . '&sql_signature=dcfe20b407b35309f6af81f745e77a10f723d39b082d2a8f9cb8e75b17c4d3ce'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en">',
                    'comments' => '',
                    'is_browse_pointer_enabled' => true,
                    'is_browse_marker_enabled' => true,
                    'is_column_hidden' => false,
                    'is_column_numeric' => true,
                ],
                [
                    'column_name' => 'name',
                    'order_link' => '<a href="index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0AORDER+BY+%60name%60+ASC'
                        . '&sql_signature=0d06fa8d6795b1c69892cca27d6213c08401bd434145d16cb35c365ab3e03039'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en" class="sortlink">name'
                        . '<input type="hidden" value="'
                        . 'index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0AORDER+BY+%60name%60+ASC'
                        . '&sql_signature=0d06fa8d6795b1c69892cca27d6213c08401bd434145d16cb35c365ab3e03039'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en"></a>'
                        . '<input type="hidden" name="url-remove-order" value="index.php?route=/sql&db=test_db'
                        . '&table=test_table&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60'
                        . '&sql_signature=61b0c8c5657483469636496ed02311acefd66dda3892b0d5b23d23c621486dd7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en'
                        . '&discard_remembered_sort=1">' . "\n"
                        . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0AORDER+BY+%60name%60+ASC'
                        . '&sql_signature=0d06fa8d6795b1c69892cca27d6213c08401bd434145d16cb35c365ab3e03039'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en">',
                    'comments' => '',
                    'is_browse_pointer_enabled' => true,
                    'is_browse_marker_enabled' => true,
                    'is_column_hidden' => false,
                    'is_column_numeric' => false,
                ],
                [
                    'column_name' => 'datetimefield',
                    'order_link' => '<a href="index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0A'
                        . 'ORDER+BY+%60datetimefield%60+DESC'
                        . '&sql_signature=1c46f7e3c625f9e0846fb2de844ca1732319e5fb7fb93e96c89a4b6218579358'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en" class="sortlink">datetimefield'
                        . '<input type="hidden" value="'
                        . 'index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0A'
                        . 'ORDER+BY+%60datetimefield%60+DESC'
                        . '&sql_signature=1c46f7e3c625f9e0846fb2de844ca1732319e5fb7fb93e96c89a4b6218579358'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en"></a>'
                        . '<input type="hidden" name="url-remove-order" value="index.php?route=/sql&db=test_db'
                        . '&table=test_table&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60'
                        . '&sql_signature=61b0c8c5657483469636496ed02311acefd66dda3892b0d5b23d23c621486dd7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en'
                        . '&discard_remembered_sort=1">' . "\n"
                        . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60++%0A'
                        . 'ORDER+BY+%60datetimefield%60+DESC'
                        . '&sql_signature=1c46f7e3c625f9e0846fb2de844ca1732319e5fb7fb93e96c89a4b6218579358'
                        . '&session_max_rows=25&is_browse_distinct=0&server=0&lang=en">',
                    'comments' => '',
                    'is_browse_pointer_enabled' => true,
                    'is_browse_marker_enabled' => true,
                    'is_column_hidden' => false,
                    'is_column_numeric' => false,
                ],
            ],
        ]);

        $tableTemplate = $template->render('display/results/table', [
            'sql_query_message' => Generator::getMessage(
                Message::success('Showing rows 0 -  2 (3 total, Query took 1.2340 seconds.)'),
                $query,
                'success'
            ),
            'navigation' => [
                'page_selector' => '',
                'number_total_page' => 1,
                'has_show_all' => true,
                'hidden_fields' => [
                    'db' => $GLOBALS['db'],
                    'table' => $GLOBALS['table'],
                    'server' => 1,
                    'sql_query' => $query,
                    'is_browse_distinct' => false,
                    'goto' => '',
                ],
                'session_max_rows' => 'all',
                'is_showing_all' => false,
                'max_rows' => 25,
                'pos' => 0,
                'sort_by_key' => [
                    'hidden_fields' => [
                        'db' => $GLOBALS['db'],
                        'table' => $GLOBALS['table'],
                        'server' => 1,
                        'sort_by_key' => '1',
                        'session_max_rows' => 25,
                    ],
                    'options' => [
                        [
                            'value' => 'SELECT * FROM `test_db`.`test_table`   ORDER BY `id` ASC',
                            'content' => 'PRIMARY (ASC)',
                            'is_selected' => false,
                        ],
                        [
                            'value' => 'SELECT * FROM `test_db`.`test_table`   ORDER BY `id` DESC',
                            'content' => 'PRIMARY (DESC)',
                            'is_selected' => false,
                        ],
                        [
                            'value' => 'SELECT * FROM `test_db`.`test_table`  ',
                            'content' => 'None',
                            'is_selected' => true,
                        ],
                    ],
                ],
                'is_last_page' => true,
            ],
            'headers' => [
                'column_order' => [
                    'order' => false,
                    'visibility' => false,
                    'is_view' => false,
                    'table_create_time' => '',
                ],
                'options' => '$optionsBlock',
                'has_bulk_actions_form' => false,
                'button' => '<thead><tr>' . "\n",
                'table_headers_for_columns' => $tableHeadersForColumns,
                'column_at_right_side' => "\n" . '<td class="d-print-none"></td>',
            ],
            'body' => '<tr><td data-decimals="0" data-type="real" class="'
                . 'text-end data not_null text-nowrap">1</td>' . "\n"
                . '<td data-decimals="0" data-type="string" data-originallength="4" class="'
                . 'data not_null text pre_wrap">abcd</td>' . "\n"
                . '<td data-decimals="0" data-type="datetime" data-originallength="19" class="'
                . 'data not_null datetimefield text-nowrap">2011-01-20 02:00:02</td>' . "\n"
                . '</tr>' . "\n"
                . '<tr><td data-decimals="0" data-type="real" class="'
                . 'text-end data not_null text-nowrap">2</td>' . "\n"
                . '<td data-decimals="0" data-type="string" data-originallength="3" class="'
                . 'data not_null text pre_wrap">foo</td>' . "\n"
                . '<td data-decimals="0" data-type="datetime" data-originallength="19" class="'
                . 'data not_null datetimefield text-nowrap">2010-01-20 02:00:02</td>' . "\n"
                . '</tr>' . "\n"
                . '<tr><td data-decimals="0" data-type="real" class="'
                . 'text-end data not_null text-nowrap">3</td>' . "\n"
                . '<td data-decimals="0" data-type="string" data-originallength="4" class="'
                . 'data not_null text pre_wrap">Abcd</td>' . "\n"
                . '<td data-decimals="0" data-type="datetime" data-originallength="19" class="'
                . 'data not_null datetimefield text-nowrap">2012-01-20 02:00:02</td>' . "\n"
                . '</tr>' . "\n",
            'bulk_links' => [],
            'operations' => [
                'has_procedure' => false,
                'has_geometry' => false,
                'has_print_link' => true,
                'has_export_link' => true,
                'url_params' => [
                    'db' => $GLOBALS['db'],
                    'table' => $GLOBALS['table'],
                    'printview' => '1',
                    'sql_query' => $query,
                    'single_table' => 'true',
                    'unlim_num_rows' => 3,
                ],
            ],
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'unique_id' => 1234567890,
            'sql_query' => $query,
            'goto' => '',
            'unlim_num_rows' => 3,
            'displaywork' => false,
            'relwork' => false,
            'save_cells_at_once' => false,
            'default_sliders_state' => 'closed',
            'text_dir' => 'ltr',
        ]);

        $this->assertEquals($tableTemplate, $actual);
    }

    public function testGetTable2(): void
    {
        $GLOBALS['cfg']['Server']['DisableIS'] = true;

        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';
        $query = 'SELECT COUNT(*) AS `Rows`, `name` FROM `test_table` GROUP BY `name` ORDER BY `name`';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $object = new DisplayResults($dbi, $GLOBALS['db'], $GLOBALS['table'], 1, '', $query);
        $object->properties['unique_id'] = 1234567890;

        [$statementInfo] = ParseAnalyze::sqlQuery($query, $GLOBALS['db']);
        $fieldsMeta = [
            new FieldMetadata(
                MYSQLI_TYPE_LONG,
                MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG,
                (object) ['name' => 'Rows']
            ),
            new FieldMetadata(MYSQLI_TYPE_STRING, MYSQLI_NOT_NULL_FLAG, (object) ['name' => 'name']),
        ];

        $dummyDbi->addResult($query, [['2', 'abcd'], ['1', 'foo']], ['Rows', 'name'], $fieldsMeta);

        $object->setProperties(
            2,
            $fieldsMeta,
            $statementInfo->isCount,
            $statementInfo->isExport,
            $statementInfo->isFunction,
            $statementInfo->isAnalyse,
            2,
            count($fieldsMeta),
            1.234,
            'ltr',
            $statementInfo->isMaint,
            $statementInfo->isExplain,
            $statementInfo->isShow,
            null,
            null,
            true,
            true
        );

        $_SESSION = ['tmpval' => [], ' PMA_token ' => 'token'];
        $_SESSION['tmpval']['geoOption'] = '';
        $_SESSION['tmpval']['hide_transformation'] = false;
        $_SESSION['tmpval']['display_blob'] = '';
        $_SESSION['tmpval']['display_binary'] = '';
        $_SESSION['tmpval']['relational_display'] = '';
        $_SESSION['tmpval']['possible_as_geometry'] = '';
        $_SESSION['tmpval']['pftext'] = '';
        $_SESSION['tmpval']['max_rows'] = 25;
        $_SESSION['tmpval']['pos'] = 0;
        $_SESSION['tmpval']['repeat_cells'] = 0;
        $_SESSION['tmpval']['query']['f2a8e80312ca180031ad773b573adbe1']['max_rows'] = 25;

        $dtResult = $dbi->tryQuery($query);

        $displayParts = DisplayParts::fromArray([
            'hasEditLink' => false,
            'deleteLink' => DisplayParts::NO_DELETE,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => false,
            'hasPrintLink' => true,
        ]);

        $this->assertNotFalse($dtResult);
        $actual = $object->getTable($dtResult, $displayParts, $statementInfo);

        $template = new Template();

        $tableHeadersForColumns = $template->render('display/results/table_headers_for_columns', [
            'is_sortable' => true,
            'columns' => [
                [
                    'column_name' => 'Rows',
                    'order_link' => '<a href="index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table'
                        . '&sql_query=SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table'
                        . '%60+GROUP+BY+%60name%60++%0AORDER+BY+%60Rows%60+ASC&sql_signature='
                        . '8412b2f6bb4473905c68b2612d95d0020dda32282b3f5bf7a63fbaa98163016e&session_max_rows=25'
                        . '&is_browse_distinct=1&server=0&lang=en" class="sortlink">Rows<input type="hidden" value="'
                        . 'index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60++%0AORDER+BY+%60name%60+ASC%2C+%60Rows%60+ASC&sql_signature='
                        . '6077a1df2401b3fa1ca67a940e3bb3cf6ff126ee5245137b07d68b1e7fe4075a&session_max_rows=25'
                        . '&is_browse_distinct=1&server=0&lang=en"></a><input type="hidden" name="url-remove-order"'
                        . ' value="index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+%60name'
                        . '%60+ORDER+BY+%60name%60+ASC&sql_signature='
                        . 'a6daf20f5593bc5d7c62fdb7dc564994f9e4a928f4488ab41b653c264bed70e7&session_max_rows=25'
                        . '&is_browse_distinct=1&server=0&lang=en">' . "\n"
                        . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60++%0AORDER+BY+%60name%60+ASC%2C+%60Rows%60+ASC&sql_signature='
                        . '6077a1df2401b3fa1ca67a940e3bb3cf6ff126ee5245137b07d68b1e7fe4075a&session_max_rows=25'
                        . '&is_browse_distinct=1&server=0&lang=en">',
                    'comments' => '',
                    'is_browse_pointer_enabled' => true,
                    'is_browse_marker_enabled' => true,
                    'is_column_hidden' => false,
                    'is_column_numeric' => true,
                ],
                [
                    'column_name' => 'name',
                    'order_link' => '<a href="index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table'
                        . '&sql_query=SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table'
                        . '%60+GROUP+BY+%60name%60++%0AORDER+BY+%60name%60+DESC&sql_signature='
                        . 'de2cda64ffdeae7d1181feb386c1c47acea4de444235f1cdc29cf4556d4bae4c&session_max_rows=25'
                        . '&is_browse_distinct=1&server=0&lang=en" class="sortlink">name <img src="themes/dot.gif"'
                        . ' title="" alt="Ascending" class="icon ic_s_asc soimg"> <img src="themes/dot.gif" title=""'
                        . ' alt="Descending" class="icon ic_s_desc soimg hide"> <small>1</small><input type="hidden"'
                        . ' value="index.php?route=/sql&server=0&lang=en&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60++%0AORDER+BY+%60name%60+DESC&sql_signature='
                        . 'de2cda64ffdeae7d1181feb386c1c47acea4de444235f1cdc29cf4556d4bae4c&session_max_rows=25'
                        . '&is_browse_distinct=1&server=0&lang=en"></a><input type="hidden" name="url-remove-order"'
                        . ' value="index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60&sql_signature=1e391c9073b55f6d88696ff3b6991df45636bd24c32e7c235c8ff7ef640161ce'
                        . '&session_max_rows=25&is_browse_distinct=1&server=0&lang=en'
                        . '&discard_remembered_sort=1">' . "\n" . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60++%0AORDER+BY+%60name%60+DESC&sql_signature='
                        . 'de2cda64ffdeae7d1181feb386c1c47acea4de444235f1cdc29cf4556d4bae4c&session_max_rows=25'
                        . '&is_browse_distinct=1&server=0&lang=en">',
                    'comments' => '',
                    'is_browse_pointer_enabled' => true,
                    'is_browse_marker_enabled' => true,
                    'is_column_hidden' => false,
                    'is_column_numeric' => false,
                ],
            ],
        ]);

        $tableTemplate = $template->render('display/results/table', [
            'sql_query_message' => Generator::getMessage(
                Message::success('Showing rows 0 -  1 (2 total, Query took 1.2340 seconds.)'),
                $query,
                'success'
            ),
            'navigation' => [
                'page_selector' => '',
                'number_total_page' => 1,
                'has_show_all' => true,
                'hidden_fields' => [
                    'db' => $GLOBALS['db'],
                    'table' => $GLOBALS['table'],
                    'server' => 1,
                    'sql_query' => $query,
                    'is_browse_distinct' => true,
                    'goto' => '',
                ],
                'session_max_rows' => 'all',
                'is_showing_all' => false,
                'max_rows' => 25,
                'pos' => 0,
                'sort_by_key' => [],
                'is_last_page' => true,
            ],
            'headers' => [
                'column_order' => [],
                'options' => '$optionsBlock',
                'has_bulk_actions_form' => false,
                'button' => '<thead><tr>' . "\n",
                'table_headers_for_columns' => $tableHeadersForColumns,
                'column_at_right_side' => "\n" . '<td class="d-print-none"></td>',
            ],
            'body' => '<tr><td data-decimals="0" data-type="int" class="'
                . 'text-end data not_null text-nowrap">2</td>' . "\n"
                . '<td data-decimals="0" data-type="string" data-originallength="4" class="'
                . 'data not_null relation text pre_wrap"><a href="index.php?route=/sql&server=0&lang=en'
                . '&db=test_db&table=test_table&pos=0&sql_signature='
                . '435bef10ad40031af7da88ea735cdc55ee91ac589b93adf10a10101b00e4d7ac&sql_query='
                . 'SELECT+%2A+FROM+%60test_db%60.%60test_table%60+WHERE+%60name%60+%3D+%27abcd%27&server=0&lang=en'
                . '" title="abcd">abcd</a></td>' . "\n"
                . '</tr>' . "\n"
                . '<tr><td data-decimals="0" data-type="int" class="'
                . 'text-end data not_null text-nowrap">1</td>' . "\n"
                . '<td data-decimals="0" data-type="string" data-originallength="3" class="'
                . 'data not_null relation text pre_wrap"><a href="index.php?route=/sql&server=0&lang=en&db=test_db'
                . '&table=test_table&pos=0&sql_signature='
                . '8b25f948acdbde1631297c34c6fe773c1751dfed5e59a30e3ee909773512e297&sql_query='
                . 'SELECT+%2A+FROM+%60test_db%60.%60test_table%60+WHERE+%60name%60+%3D+%27foo%27&server=0&lang=en"'
                . ' title="foo">foo</a></td>' . "\n"
                . '</tr>' . "\n",
            'bulk_links' => [],
            'operations' => [
                'has_procedure' => false,
                'has_geometry' => false,
                'has_print_link' => true,
                'has_export_link' => true,
                'url_params' => [
                    'db' => $GLOBALS['db'],
                    'table' => $GLOBALS['table'],
                    'printview' => '1',
                    'sql_query' => $query,
                    'single_table' => 'true',
                    'unlim_num_rows' => 2,
                ],
            ],
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
            'unique_id' => 1234567890,
            'sql_query' => $query,
            'goto' => '',
            'unlim_num_rows' => 2,
            'displaywork' => false,
            'relwork' => false,
            'save_cells_at_once' => false,
            'default_sliders_state' => 'closed',
            'text_dir' => 'ltr',
        ]);

        $this->assertEquals($tableTemplate, $actual);
    }
}
