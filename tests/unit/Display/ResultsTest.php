<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Display;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Display\DeleteLinkEnum;
use PhpMyAdmin\Display\DisplayParts;
use PhpMyAdmin\Display\Results as DisplayResults;
use PhpMyAdmin\Display\SortExpression;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Plugins\Transformations\Output\Text_Plain_External;
use PhpMyAdmin\Plugins\Transformations\Text_Plain_Link;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\SqlParser\Utils\StatementFlags;
use PhpMyAdmin\SqlParser\Utils\StatementInfo;
use PhpMyAdmin\SqlParser\Utils\StatementType;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\FieldHelper;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionProperty;
use stdClass;

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
use const MYSQLI_TYPE_TIME;
use const MYSQLI_TYPE_TIMESTAMP;

#[CoversClass(DisplayResults::class)]
class ResultsTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected DisplayResults $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setLanguage();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
        Current::$server = 2;
        Current::$database = 'db';
        Current::$table = 'table';
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        $this->object = new DisplayResults($this->dbi, $config, 'as', '', 2, '', '');
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
        self::assertTrue(
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'isSelect',
                [Query::getAll('SELECT * FROM pma')],
            ),
        );
    }

    public function testGetClassForDateTimeRelatedFieldsCase1(): void
    {
        self::assertSame(
            'datetimefield',
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getClassForDateTimeRelatedFields',
                [FieldHelper::fromArray(['type' => MYSQLI_TYPE_TIMESTAMP])],
            ),
        );
    }

    public function testGetClassForDateTimeRelatedFieldsCase2(): void
    {
        self::assertSame(
            'datefield',
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getClassForDateTimeRelatedFields',
                [FieldHelper::fromArray(['type' => MYSQLI_TYPE_DATE])],
            ),
        );
    }

    public function testGetClassForDateTimeRelatedFieldsCase3(): void
    {
        self::assertSame(
            'text',
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getClassForDateTimeRelatedFields',
                [FieldHelper::fromArray(['type' => MYSQLI_TYPE_STRING])],
            ),
        );
    }

    /**
     * Test for getOffsets - case 1
     */
    public function testGetOffsetsCase1(): void
    {
        $_SESSION['tmpval']['max_rows'] = DisplayResults::ALL_ROWS;
        self::assertSame(
            [0, 0],
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getOffsets',
                [],
            ),
        );
    }

    /**
     * Test for getOffsets - case 2
     */
    public function testGetOffsetsCase2(): void
    {
        $_SESSION['tmpval']['max_rows'] = 5;
        $_SESSION['tmpval']['pos'] = 4;
        self::assertSame(
            [9, 0],
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getOffsets',
                [],
            ),
        );
    }

    /**
     * Data provider for testGetSpecialLinkUrl
     *
     * @return array<int, array{string, string, string, array<string, string>, string, string}>
     */
    public static function dataProviderForTestGetSpecialLinkUrl(): array
    {
        return [
            [
                'information_schema',
                'routines',
                'circumference',
                ['routine_name' => 'circumference', 'routine_schema' => 'data', 'routine_type' => 'FUNCTION'],
                'routine_name',
                'index.php?route=/database/routines&item_name=circumference&db=data'
                . '&item_type=FUNCTION&server=2&lang=en',
            ],
            [
                'information_schema',
                'routines',
                'area',
                ['routine_name' => 'area', 'routine_schema' => 'data', 'routine_type' => 'PROCEDURE'],
                'routine_name',
                'index.php?route=/database/routines&item_name=area&db=data&item_type=PROCEDURE&server=2&lang=en',
            ],
        ];
    }

    /**
     * Test getSpecialLinkUrl
     *
     * @param string                $db          the database name
     * @param string                $table       the table name
     * @param string                $columnValue column value
     * @param array<string, string> $rowInfo     information about row
     * @param string                $fieldName   column name
     * @param string                $output      output of getSpecialLinkUrl
     */
    #[DataProvider('dataProviderForTestGetSpecialLinkUrl')]
    public function testGetSpecialLinkUrl(
        string $db,
        string $table,
        string $columnValue,
        array $rowInfo,
        string $fieldName,
        string $output,
    ): void {
        $specialSchemaLinks = [
            'information_schema' => [
                'routines' => [
                    'routine_name' => [
                        'link_param' => 'item_name',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'routine_schema'],
                            ['param_info' => 'item_type', 'column_name' => 'routine_type'],
                        ],
                        'default_page' => 'index.php?route=/database/routines',
                    ],
                ],
                'columns' => [
                    'column_name' => [
                        'link_param' => 'table_schema',
                        'link_dependancy_params' => [
                            ['param_info' => 'db', 'column_name' => 'table_schema'],
                            ['param_info' => 'db2', 'column_name' => 'table_schema'],
                        ],
                        'default_page' => 'index.php',
                    ],
                ],
            ],
        ];

        self::assertSame(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getSpecialLinkUrl',
                [$specialSchemaLinks[$db][$table][$fieldName], $columnValue, $rowInfo],
            ),
        );
    }

    /**
     * Data provider for testGetRowInfoForSpecialLinks
     *
     * @return mixed[] parameters and output
     */
    public static function dataProviderForTestGetRowInfoForSpecialLinks(): array
    {
        $columnNames = ['host', 'db', 'user', 'select_privilages'];
        $fieldsMeta = [];

        foreach ($columnNames as $columnName) {
            $fieldMeta = new stdClass();
            $fieldMeta->orgname = $columnName;
            $fieldsMeta[] = $fieldMeta;
        }

        return [
            [
                $fieldsMeta,
                ['localhost', 'phpmyadmin', 'pmauser', 'Y'],
                ['host' => 'localhost', 'select_privilages' => 'Y', 'db' => 'phpmyadmin', 'user' => 'pmauser'],
            ],
        ];
    }

    /**
     * Test getRowInfoForSpecialLinks
     *
     * @param FieldMetadata[]       $fieldsMeta meta information about fields
     * @param string[]              $row        current row data
     * @param array<string, string> $output     output of getRowInfoForSpecialLinks
     */
    #[DataProvider('dataProviderForTestGetRowInfoForSpecialLinks')]
    public function testGetRowInfoForSpecialLinks(
        array $fieldsMeta,
        array $row,
        array $output,
    ): void {
        (new ReflectionProperty(DisplayResults::class, 'fieldsMeta'))->setValue($this->object, $fieldsMeta);

        self::assertEquals(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getRowInfoForSpecialLinks',
                [$row],
            ),
        );
    }

    public function testSetHighlightedColumnGlobalField(): void
    {
        $query = 'SELECT * FROM db_name WHERE `db_name`.`tbl`.id > 0 AND `id` < 10';
        $this->callFunction(
            $this->object,
            DisplayResults::class,
            'setHighlightedColumnGlobalField',
            [Query::getAll($query)],
        );

        self::assertSame([
            'db_name' => true,
            'tbl' => true,
            'id' => true,
        ], (new ReflectionProperty(DisplayResults::class, 'highlightColumns'))->getValue($this->object));
    }

    /**
     * Data provider for testGetPartialText
     *
     * @return array<int, array{string, int, string, string}>
     */
    public static function dataProviderForTestGetPartialText(): array
    {
        return [
            ['P', 10, 'foo', 'foo'],
            ['P', 1, 'foo', 'f...'],
            ['F', 10, 'foo', 'foo'],
            ['F', 1, 'foo', 'foo'],
        ];
    }

    /**
     * Test getPartialText
     *
     * @param string $pftext     Partial or Full text
     * @param int    $limitChars Partial or Full text
     * @param string $str        the string to be tested
     * @param string $output     return value of getPartialText
     */
    #[DataProvider('dataProviderForTestGetPartialText')]
    public function testGetPartialText(string $pftext, int $limitChars, string $str, string $output): void
    {
        $_SESSION['tmpval']['pftext'] = $pftext;
        Config::getInstance()->settings['LimitChars'] = $limitChars;
        self::assertSame(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'getPartialText',
                [$str],
            ),
        );
    }

    /**
     * @return mixed[][]
     * @psalm-return array<array{
     *   bool,
     *   bool,
     *   string,
     *   string|null,
     *   TransformationsPlugin|null,
     *   array|object,
     *   object,
     *   array<string, string>,
     *   bool,
     *   string
     * }>
     */
    public static function dataProviderForTestHandleNonPrintableContents(): array
    {
        $transformationPlugin = new Text_Plain_Link();
        $meta = FieldHelper::fromArray(['type' => MYSQLI_TYPE_BLOB, 'orgtable' => 'bar']);
        $urlParams = ['db' => 'foo', 'table' => 'bar', 'where_clause' => 'where_clause'];

        return [
            [true, true, 'BLOB', '1001', null, [], $meta, $urlParams, false, 'class="disableAjax">1001</a>'],
            [
                true,
                true,
                'BLOB',
                (string) hex2bin('123456'),
                null,
                [],
                $meta,
                $urlParams,
                false,
                'class="disableAjax">0x123456</a>',
            ],
            [true, false, 'BLOB', '1001', null, [], $meta, $urlParams, false, 'class="disableAjax">[BLOB - 4 B]</a>'],
            [false, false, 'BINARY', '1001', $transformationPlugin, [], $meta, $urlParams, false, '1001'],
            [false, true, 'GEOMETRY', null, null, [], $meta, $urlParams, false, '[GEOMETRY - NULL]'],
        ];
    }

    /**
     * @param bool                  $displayBinary    show binary contents?
     * @param bool                  $displayBlob      show blob contents?
     * @param string                $category         BLOB|BINARY|GEOMETRY
     * @param string|null           $content          the binary content
     * @param mixed[]|object        $transformOptions transformation parameters
     * @param object                $meta             the meta-information about the field
     * @param array<string, string> $urlParams        parameters that should go to the download link
     * @param bool                  $isTruncated      the result is truncated or not
     * @param string                $output           the output of this function
     */
    #[DataProvider('dataProviderForTestHandleNonPrintableContents')]
    public function testHandleNonPrintableContents(
        bool $displayBinary,
        bool $displayBlob,
        string $category,
        string|null $content,
        TransformationsPlugin|null $transformationPlugin,
        array|object $transformOptions,
        object $meta,
        array $urlParams,
        bool $isTruncated,
        string $output,
    ): void {
        $_SESSION['tmpval']['display_binary'] = $displayBinary;
        $_SESSION['tmpval']['display_blob'] = $displayBlob;
        Config::getInstance()->settings['LimitChars'] = 50;
        self::assertStringContainsString(
            $output,
            $this->callFunction(
                $this->object,
                DisplayResults::class,
                'handleNonPrintableContents',
                [$category, $content, $transformationPlugin, $transformOptions, $meta, $urlParams, &$isTruncated],
            ),
        );
    }

    /**
     * @return array<array{
     *   string,
     *   string|null,
     *   string,
     *   object,
     *   array{},
     *   string[],
     *   bool,
     *   TransformationsPlugin|null,
     *   string[],
     *   string
     * }>
     */
    public static function dataProviderForTestGetDataCellForNonNumericColumns(): array
    {
        $transformationPlugin = new Text_Plain_Link();
        $transformationPluginExternal = new Text_Plain_External();

        $meta = FieldHelper::fromArray([
            'type' => MYSQLI_TYPE_BLOB,
            'table' => 'tbl',
            'orgtable' => 'tbl',
            'name' => 'tblob',
            'orgname' => 'tblob',
            'charsetnr' => 63,
        ]);
        $meta2 = FieldHelper::fromArray([
            'type' => MYSQLI_TYPE_STRING,
            'table' => 'tbl',
            'orgtable' => 'tbl',
            'name' => 'varchar',
            'orgname' => 'varchar',
        ]);
        $meta3 = FieldHelper::fromArray([
            'type' => MYSQLI_TYPE_DATETIME,
            'table' => 'tbl',
            'orgtable' => 'tbl',
            'name' => 'datetime',
            'orgname' => 'datetime',
        ]);

        $urlParams = ['db' => 'foo', 'table' => 'tbl', 'where_clause' => 'where_clause'];

        return [
            [
                'all',
                '1001',
                'grid_edit',
                $meta,
                [],
                $urlParams,
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
                $urlParams,
                false,
                $transformationPlugin,
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
                $urlParams,
                false,
                $transformationPlugin,
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
                $urlParams,
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
                $urlParams,
                false,
                $transformationPluginExternal,
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
                $urlParams,
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
     * @param string      $protectBinary    all|blob|noblob|no
     * @param string|null $column           the relevant column in data row
     * @param string      $class            the html class for column
     * @param object      $meta             the meta-information about the field
     * @param mixed[]     $map              the list of relations
     * @param mixed[]     $urlParams        the parameters for generate url
     * @param bool        $conditionField   the column should highlighted or not
     * @param string[]    $transformOptions the transformation parameters
     * @param string      $output           the output of this function
     */
    #[DataProvider('dataProviderForTestGetDataCellForNonNumericColumns')]
    public function testGetDataCellForNonNumericColumns(
        string $protectBinary,
        string|null $column,
        string $class,
        object $meta,
        array $map,
        array $urlParams,
        bool $conditionField,
        TransformationsPlugin|null $transformationPlugin,
        array $transformOptions,
        string $output,
    ): void {
        $_SESSION['tmpval']['display_binary'] = true;
        $_SESSION['tmpval']['display_blob'] = false;
        $_SESSION['tmpval']['relational_display'] = false;
        $config = Config::getInstance();
        $config->settings['LimitChars'] = 50;
        $config->settings['ProtectBinary'] = $protectBinary;
        $statementInfo = new StatementInfo(new Parser(), null, new StatementFlags(), [], []);
        self::assertStringContainsString(
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
                    $urlParams,
                    $conditionField,
                    $transformationPlugin,
                    $transformOptions,
                    $statementInfo,
                ],
            ),
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
        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'db',
            RelationParameters::MIME_WORK => true,
            RelationParameters::COLUMN_INFO => 'column_info',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
        $config = Config::getInstance();

        // Basic data
        $query = 'SELECT 1';
        $this->object = new DisplayResults($this->dbi, $config, 'db', '', 2, '', '');

        // Field meta information
        (new ReflectionProperty(DisplayResults::class, 'fieldsMeta'))->setValue($this->object, [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_LONG,
                'flags' => MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG,
                'table' => 'table',
                'orgtable' => 'table',
                'name' => '1',
                'orgname' => '1',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_LONG,
                'flags' => MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG,
                'table' => 'table',
                'orgtable' => 'table',
                'name' => '2',
                'orgname' => '2',
            ]),
        ]);

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        // MIME transformations
        $dbi->expects(self::exactly(1))
            ->method('fetchResult')
            ->willReturn(
                [
                    'db.table.1' => ['mimetype' => '', 'transformation' => 'output/text_plain_dateformat.php'],
                    'db.table.2' => ['mimetype' => '', 'transformation' => 'output/text_plain_bool2text.php'],
                ],
            );

        DatabaseInterface::$instance = $dbi;

        $transformations = new Transformations($dbi, new Relation($dbi));
        (new ReflectionProperty(DisplayResults::class, 'mediaTypeMap'))->setValue(
            $this->object,
            $transformations->getMime('db', 'table'),
        );

        // Actually invoke tested method
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'getRowValues',
            [
                ['3600', 'true'],
                0,
                false,
                [],
                'disabled',
                false,
                $query,
                Query::getAll($query),
            ],
        );

        // Dateformat
        self::assertStringContainsString('Jan 01, 1970 at 01:00 AM', $output);
        // Bool2Text
        self::assertStringContainsString('>T<', $output);
    }

    /** @return mixed[][] */
    public static function dataProviderGetSortOrderHiddenInputs(): array
    {
        // SQL to add the column
        // SQL to remove the column
        // The URL params
        // The column name
        return [
            ['', '', ['sql_query' => ''], 'colname', ''],
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

    /** @param array<string, string> $urlParams */
    #[DataProvider('dataProviderGetSortOrderHiddenInputs')]
    public function testGetSortOrderHiddenInputs(
        string $sqlAdd,
        string $sqlRemove,
        array $urlParams,
        string $colName,
        string $urlParamsRemove,
    ): void {
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'getSortOrderHiddenInputs',
            [$urlParams, $colName],
        );
        $out = urldecode(htmlspecialchars_decode($output));
        self::assertStringContainsString(
            'name="url-remove-order" value="index.php?route=/sql&sql_query=' . $sqlRemove,
            $out,
            'The remove query should be found',
        );

        self::assertStringContainsString(
            'name="url-add-order" value="index.php?route=/sql&sql_query=' . $sqlAdd,
            $out,
            'The add query should be found',
        );

        $firstLine = explode("\n", $out)[0];
        self::assertStringContainsString(
            'url-remove-order',
            $firstLine,
            'The first line should contain url-remove-order input',
        );
        self::assertStringNotContainsString(
            'url-add-order',
            $firstLine,
            'The first line should contain NOT url-add-order input',
        );

        self::assertStringContainsString($urlParamsRemove, $firstLine, 'The first line should contain the URL params');
    }

    /** @see https://github.com/phpmyadmin/phpmyadmin/issues/16836 */
    public function testBuildValueDisplayNoTrainlingSpaces(): void
    {
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'buildValueDisplay',
            ['my_class', false, '  special value  '],
        );
        self::assertSame('<td class="text-start my_class">  special value  </td>' . "\n", $output);
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'buildValueDisplay',
            ['my_class', false, '0x11e6ac0cfb1e8bf3bf48b827ebdafb0b'],
        );
        self::assertSame('<td class="text-start my_class">0x11e6ac0cfb1e8bf3bf48b827ebdafb0b</td>' . "\n", $output);
        $output = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'buildValueDisplay',
            [
                'my_class',
                true,// condition mode
                '0x11e6ac0cfb1e8bf3bf48b827ebdafb0b',
            ],
        );
        self::assertSame(
            '<td class="text-start my_class condition">0x11e6ac0cfb1e8bf3bf48b827ebdafb0b</td>' . "\n",
            $output,
        );
    }

    public function testPftextConfigParam(): void
    {
        $db = 'test_db';
        $table = 'test_table';
        $config = Config::getInstance();

        $query = 'ANALYZE FORMAT=JSON SELECT * FROM test_table';
        [$statementInfo] = ParseAnalyze::sqlQuery($query, $db);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com');

        $object = new DisplayResults($this->dbi, $config, $db, $table, 2, '', $query);
        $object->setConfigParamsForDisplayTable($request, $statementInfo);

        self::assertSame('F', $_SESSION['tmpval']['pftext']);

        $query = 'ANALYZE NO_WRITE_TO_BINLOG TABLE test_table';
        [$statementInfo] = ParseAnalyze::sqlQuery($query, $db);

        $object = new DisplayResults($this->dbi, $config, $db, $table, 2, '', $query);
        $object->setConfigParamsForDisplayTable($request, $statementInfo);

        self::assertSame('P', $_SESSION['tmpval']['pftext']);
    }

    /**
     * @param array<string, array<string, array<string, array<string, bool|int|string>>>|string> $session
     * @param array<string, string>                                                              $queryParams
     * @param array<string, string>                                                              $parsedBody
     * @param array<string, bool|array<string, array<string, bool|int|string>>|string|int>       $expected
     */
    #[DataProvider('providerSetConfigParamsForDisplayTable')]
    public function testSetConfigParamsForDisplayTable(
        array $session,
        array $queryParams,
        array $parsedBody,
        array $expected,
    ): void {
        $_SESSION = $session;

        $db = 'test_db';
        $table = 'test_table';
        $query = 'SELECT * FROM `test_db`.`test_table`;';
        [$statementInfo] = ParseAnalyze::sqlQuery($query, $db);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com')
            ->withQueryParams($queryParams)
            ->withParsedBody($parsedBody);

        $object = new DisplayResults($this->dbi, Config::getInstance(), $db, $table, 2, '', $query);
        $object->setConfigParamsForDisplayTable($request, $statementInfo);

        self::assertArrayHasKey('tmpval', $_SESSION);
        self::assertIsArray($_SESSION['tmpval']);
        self::assertSame($expected, $_SESSION['tmpval']);
    }

    /** @return mixed[][] */
    public static function providerSetConfigParamsForDisplayTable(): array
    {
        $cfg = ['RelationalDisplay' => DisplayResults::RELATIONAL_KEY, 'MaxRows' => 25, 'RepeatCells' => 100];

        return [
            'default values' => [
                [' PMA_token ' => 'token'],
                [],
                [],
                [
                    'query' => [
                        '2b7b3faf4b48255e47876f6d5bd2da35' => [
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
                            '2b7b3faf4b48255e47876f6d5bd2da35' => [
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
                    ' PMA_token ' => 'token',
                ],
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
                        '2b7b3faf4b48255e47876f6d5bd2da35' => [
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
                [' PMA_token ' => 'token'],
                ['session_max_rows' => '28'],
                [
                    'session_max_rows' => '27',
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
                        '2b7b3faf4b48255e47876f6d5bd2da35' => [
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
                            '2b7b3faf4b48255e47876f6d5bd2da35' => [
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
                    ' PMA_token ' => 'token',
                ],
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
                        '2b7b3faf4b48255e47876f6d5bd2da35' => [
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
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        Current::$server = 2;
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $query = 'SELECT * FROM `test_db`.`test_table`;';

        $object = new DisplayResults(
            $this->dbi,
            $config,
            Current::$database,
            Current::$table,
            Current::$server,
            '',
            $query,
        );

        (new ReflectionProperty(DisplayResults::class, 'uniqueId'))->setValue($object, 1234567890);

        [$statementInfo] = ParseAnalyze::sqlQuery($query, Current::$database);
        $fieldsMeta = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_DECIMAL,
                'flags' => MYSQLI_PRI_KEY_FLAG | MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG,
                'name' => 'id',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'flags' => MYSQLI_NOT_NULL_FLAG,
                'name' => 'name',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_DATETIME,
                'flags' => MYSQLI_NOT_NULL_FLAG,
                'name' => 'datetimefield',
            ]),
        ];

        $object->setProperties(
            3,
            $fieldsMeta,
            $statementInfo->flags->isCount,
            $statementInfo->flags->isExport,
            $statementInfo->flags->isFunc,
            $statementInfo->flags->isAnalyse,
            3,
            1.234,
            $statementInfo->flags->isMaint,
            $statementInfo->flags->queryType === StatementType::Explain,
            $statementInfo->flags->queryType === StatementType::Show,
            false,
            true,
            false,
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
        $_SESSION['tmpval']['query']['2b7b3faf4b48255e47876f6d5bd2da35']['max_rows'] = 25;

        $dtResult = $this->dbi->tryQuery($query);

        $displayParts = DisplayParts::fromArray([
            'hasEditLink' => true,
            'deleteLink' => DeleteLinkEnum::DELETE_ROW,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => false,
            'hasPrintLink' => true,
        ]);

        self::assertNotFalse($dtResult);
        $actual = $object->getTable($dtResult, $displayParts, $statementInfo);

        $template = new Template();

        $tableHeadersForColumns = $template->render('display/results/table_headers_for_columns', [
            'is_sortable' => true,
            'columns' => [
                [
                    'column_name' => 'id',
                    'order_link' => '<a href="index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+ORDER+BY+%60id%60+ASC'
                        . '&sql_signature=73befb6d61047cd774152967254d1efb6a8bf79e01c0cabfd7f434bb43094dc1'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en" class="sortlink">id'
                        . '<input type="hidden" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+ORDER+BY+%60id%60+ASC'
                        . '&sql_signature=73befb6d61047cd774152967254d1efb6a8bf79e01c0cabfd7f434bb43094dc1'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en"></a>'
                        . '<input type="hidden" name="url-remove-order" value="index.php?route=/sql&db=test_db'
                        . '&table=test_table&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60'
                        . '&sql_signature=61b0c8c5657483469636496ed02311acefd66dda3892b0d5b23d23c621486dd7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en'
                        . '&discard_remembered_sort=1">' . "\n"
                        . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+ORDER+BY+%60id%60+ASC'
                        . '&sql_signature=73befb6d61047cd774152967254d1efb6a8bf79e01c0cabfd7f434bb43094dc1'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en">',
                    'comments' => '',
                    'is_browse_pointer_enabled' => true,
                    'is_browse_marker_enabled' => true,
                    'is_column_hidden' => false,
                    'is_column_numeric' => true,
                ],
                [
                    'column_name' => 'name',
                    'order_link' => '<a href="index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+ORDER+BY+%60name%60+ASC'
                        . '&sql_signature=848cc55530c90276732424e88b62d91144cc2a8c827e006c196cdcf744e457b7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en" class="sortlink">name'
                        . '<input type="hidden" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+ORDER+BY+%60name%60+ASC'
                        . '&sql_signature=848cc55530c90276732424e88b62d91144cc2a8c827e006c196cdcf744e457b7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en"></a>'
                        . '<input type="hidden" name="url-remove-order" value="index.php?route=/sql&db=test_db'
                        . '&table=test_table&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60'
                        . '&sql_signature=61b0c8c5657483469636496ed02311acefd66dda3892b0d5b23d23c621486dd7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en'
                        . '&discard_remembered_sort=1">' . "\n"
                        . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+ORDER+BY+%60name%60+ASC'
                        . '&sql_signature=848cc55530c90276732424e88b62d91144cc2a8c827e006c196cdcf744e457b7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en">',
                    'comments' => '',
                    'is_browse_pointer_enabled' => true,
                    'is_browse_marker_enabled' => true,
                    'is_column_hidden' => false,
                    'is_column_numeric' => false,
                ],
                [
                    'column_name' => 'datetimefield',
                    'order_link' => '<a href="index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+'
                        . 'ORDER+BY+%60datetimefield%60+DESC'
                        . '&sql_signature=c9a3b990f85df73464dafec8ecf477df6165d4d51eba1e94ba87c484b97b175c'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en" class="sortlink">datetimefield'
                        . '<input type="hidden" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+'
                        . 'ORDER+BY+%60datetimefield%60+DESC'
                        . '&sql_signature=c9a3b990f85df73464dafec8ecf477df6165d4d51eba1e94ba87c484b97b175c'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en"></a>'
                        . '<input type="hidden" name="url-remove-order" value="index.php?route=/sql&db=test_db'
                        . '&table=test_table&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60'
                        . '&sql_signature=61b0c8c5657483469636496ed02311acefd66dda3892b0d5b23d23c621486dd7'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en'
                        . '&discard_remembered_sort=1">' . "\n"
                        . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+%2A+FROM+%60test_db%60.%60test_table%60+'
                        . 'ORDER+BY+%60datetimefield%60+DESC'
                        . '&sql_signature=c9a3b990f85df73464dafec8ecf477df6165d4d51eba1e94ba87c484b97b175c'
                        . '&session_max_rows=25&is_browse_distinct=0&server=2&lang=en">',
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
                MessageType::Success,
            ),
            'navigation' => [
                'page_selector' => '',
                'number_total_page' => 1,
                'has_show_all' => true,
                'hidden_fields' => [
                    'db' => Current::$database,
                    'table' => Current::$table,
                    'server' => 2,
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
                        'db' => Current::$database,
                        'table' => Current::$table,
                        'server' => 2,
                        'sort_by_key' => '1',
                        'session_max_rows' => 25,
                    ],
                    'options' => [
                        [
                            'value' => 'SELECT * FROM `test_db`.`test_table` ORDER BY `id` ASC',
                            'content' => 'PRIMARY (ASC)',
                            'is_selected' => false,
                        ],
                        [
                            'value' => 'SELECT * FROM `test_db`.`test_table` ORDER BY `id` DESC',
                            'content' => 'PRIMARY (DESC)',
                            'is_selected' => false,
                        ],
                        [
                            'value' => 'SELECT * FROM `test_db`.`test_table`',
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
                'options' => [
                    'geo_option' => null,
                    'hide_transformation' => null,
                    'display_blob' => null,
                    'display_binary' => null,
                    'relational_display' => null,
                    'possible_as_geometry' => null,
                    'pftext' => null,
                ],
                'has_bulk_actions_form' => false,
                'button' => '',
                'table_headers_for_columns' => $tableHeadersForColumns,
                'column_at_right_side' => "\n" . '<td class="position-sticky bg-body d-print-none"></td>',
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
                    'db' => Current::$database,
                    'table' => Current::$table,
                    'printview' => '1',
                    'sql_query' => $query,
                    'single_table' => 'true',
                    'unlim_num_rows' => 3,
                ],
            ],
            'db' => Current::$database,
            'table' => Current::$table,
            'unique_id' => 1234567890,
            'sql_query' => $query,
            'goto' => '',
            'unlim_num_rows' => 3,
            'displaywork' => false,
            'relwork' => false,
            'save_cells_at_once' => false,
            'default_sliders_state' => 'closed',
        ]);

        self::assertSame($tableTemplate, $actual);
    }

    public function testGetTable2(): void
    {
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = true;

        Current::$server = 2;
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        $query = 'SELECT COUNT(*) AS `Rows`, `name` FROM `test_table` GROUP BY `name` ORDER BY `name`';

        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $object = new DisplayResults($dbi, $config, Current::$database, Current::$table, 2, '', $query);

        (new ReflectionProperty(DisplayResults::class, 'uniqueId'))->setValue($object, 1234567890);

        [$statementInfo] = ParseAnalyze::sqlQuery($query, Current::$database);
        $fieldsMeta = [
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_LONG,
                'flags' => MYSQLI_NUM_FLAG | MYSQLI_NOT_NULL_FLAG,
                'name' => 'Rows',
            ]),
            FieldHelper::fromArray([
                'type' => MYSQLI_TYPE_STRING,
                'flags' => MYSQLI_NOT_NULL_FLAG,
                'name' => 'name',
            ]),
        ];

        $dummyDbi->addResult($query, [['2', 'abcd'], ['1', 'foo']], ['Rows', 'name'], $fieldsMeta);

        $object->setProperties(
            2,
            $fieldsMeta,
            $statementInfo->flags->isCount,
            $statementInfo->flags->isExport,
            $statementInfo->flags->isFunc,
            $statementInfo->flags->isAnalyse,
            2,
            1.234,
            $statementInfo->flags->isMaint,
            $statementInfo->flags->queryType === StatementType::Explain,
            $statementInfo->flags->queryType === StatementType::Show,
            false,
            true,
            true,
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
        $_SESSION['tmpval']['query']['5ce1ef88afb4e13d3b8c0a55c2c9657a']['max_rows'] = 25;

        $dtResult = $dbi->tryQuery($query);

        $displayParts = DisplayParts::fromArray([
            'hasEditLink' => false,
            'deleteLink' => DeleteLinkEnum::NO_DELETE,
            'hasSortLink' => true,
            'hasNavigationBar' => true,
            'hasBookmarkForm' => true,
            'hasTextButton' => false,
            'hasPrintLink' => true,
        ]);

        self::assertNotFalse($dtResult);
        $actual = $object->getTable($dtResult, $displayParts, $statementInfo);

        $template = new Template();

        $tableHeadersForColumns = $template->render('display/results/table_headers_for_columns', [
            'is_sortable' => true,
            'columns' => [
                [
                    'column_name' => 'Rows',
                    'order_link' => '<a href="index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table'
                        . '%60+GROUP+BY+%60name%60+ORDER+BY+%60Rows%60+ASC&sql_signature='
                        . '5384a639efa206f521eeb74e4d4d9aff0b53cc19093bd1b406b088a082e9fedc&session_max_rows=25'
                        . '&is_browse_distinct=1&server=2&lang=en" class="sortlink">Rows<input type="hidden" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60+ORDER+BY+%60name%60+ASC%2C+%60Rows%60+ASC&sql_signature='
                        . '82594c89d410923f7ded15d65c798b4f1c1325d9453cfd450e20f8c941f2a5e6&session_max_rows=25'
                        . '&is_browse_distinct=1&server=2&lang=en"></a><input type="hidden" name="url-remove-order"'
                        . ' value="index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+%60name'
                        . '%60+ORDER+BY+%60name%60+ASC&sql_signature='
                        . 'a6daf20f5593bc5d7c62fdb7dc564994f9e4a928f4488ab41b653c264bed70e7&session_max_rows=25'
                        . '&is_browse_distinct=1&server=2&lang=en">' . "\n"
                        . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60+ORDER+BY+%60name%60+ASC%2C+%60Rows%60+ASC&sql_signature='
                        . '82594c89d410923f7ded15d65c798b4f1c1325d9453cfd450e20f8c941f2a5e6&session_max_rows=25'
                        . '&is_browse_distinct=1&server=2&lang=en">',
                    'comments' => '',
                    'is_browse_pointer_enabled' => true,
                    'is_browse_marker_enabled' => true,
                    'is_column_hidden' => false,
                    'is_column_numeric' => true,
                ],
                [
                    'column_name' => 'name',
                    'order_link' => '<a href="index.php?route=/sql&db=test_db&table=test_table'
                        . '&sql_query=SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table'
                        . '%60+GROUP+BY+%60name%60+ORDER+BY+%60name%60+DESC&sql_signature='
                        . '7157bdc2027884de0d40394faa9780e4e15f4343f564869fc18e5b17206eecfd&session_max_rows=25'
                        . '&is_browse_distinct=1&server=2&lang=en" class="sortlink">name <img src="themes/dot.gif"'
                        . ' title="" alt="Ascending" class="icon ic_s_asc soimg"> <img src="themes/dot.gif" title=""'
                        . ' alt="Descending" class="icon ic_s_desc soimg hide"> <small>1</small><input type="hidden"'
                        . ' value="index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60+ORDER+BY+%60name%60+DESC&sql_signature='
                        . '7157bdc2027884de0d40394faa9780e4e15f4343f564869fc18e5b17206eecfd&session_max_rows=25'
                        . '&is_browse_distinct=1&server=2&lang=en"></a><input type="hidden" name="url-remove-order"'
                        . ' value="index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60&sql_signature=1e391c9073b55f6d88696ff3b6991df45636bd24c32e7c235c8ff7ef640161ce'
                        . '&session_max_rows=25&is_browse_distinct=1&server=2&lang=en'
                        . '&discard_remembered_sort=1">' . "\n" . '<input type="hidden" name="url-add-order" value="'
                        . 'index.php?route=/sql&db=test_db&table=test_table&sql_query='
                        . 'SELECT+COUNT%28%2A%29+AS+%60Rows%60%2C+%60name%60+FROM+%60test_table%60+GROUP+BY+'
                        . '%60name%60+ORDER+BY+%60name%60+DESC&sql_signature='
                        . '7157bdc2027884de0d40394faa9780e4e15f4343f564869fc18e5b17206eecfd&session_max_rows=25'
                        . '&is_browse_distinct=1&server=2&lang=en">',
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
                MessageType::Success,
            ),
            'navigation' => [
                'page_selector' => '',
                'number_total_page' => 1,
                'has_show_all' => true,
                'hidden_fields' => [
                    'db' => Current::$database,
                    'table' => Current::$table,
                    'server' => 2,
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
                'options' => [
                    'geo_option' => null,
                    'hide_transformation' => null,
                    'display_blob' => null,
                    'display_binary' => null,
                    'relational_display' => null,
                    'possible_as_geometry' => null,
                    'pftext' => null,
                ],
                'has_bulk_actions_form' => false,
                'button' => '',
                'table_headers_for_columns' => $tableHeadersForColumns,
                'column_at_right_side' => "\n" . '<td class="position-sticky bg-body d-print-none"></td>',
            ],
            'body' => '<tr><td data-decimals="0" data-type="int" class="'
                . 'text-end data not_null text-nowrap">2</td>' . "\n"
                . '<td data-decimals="0" data-type="string" data-originallength="4" class="'
                . 'data not_null relation text pre_wrap"><a href="index.php?route=/sql'
                . '&db=test_db&table=test_table&pos=0&sql_signature='
                . '435bef10ad40031af7da88ea735cdc55ee91ac589b93adf10a10101b00e4d7ac&sql_query='
                . 'SELECT+%2A+FROM+%60test_db%60.%60test_table%60+WHERE+%60name%60+%3D+%27abcd%27&server=2&lang=en'
                . '" title="abcd">abcd</a></td>' . "\n"
                . '</tr>' . "\n"
                . '<tr><td data-decimals="0" data-type="int" class="'
                . 'text-end data not_null text-nowrap">1</td>' . "\n"
                . '<td data-decimals="0" data-type="string" data-originallength="3" class="'
                . 'data not_null relation text pre_wrap"><a href="index.php?route=/sql&db=test_db'
                . '&table=test_table&pos=0&sql_signature='
                . '8b25f948acdbde1631297c34c6fe773c1751dfed5e59a30e3ee909773512e297&sql_query='
                . 'SELECT+%2A+FROM+%60test_db%60.%60test_table%60+WHERE+%60name%60+%3D+%27foo%27&server=2&lang=en"'
                . ' title="foo">foo</a></td>' . "\n"
                . '</tr>' . "\n",
            'bulk_links' => [],
            'operations' => [
                'has_procedure' => false,
                'has_geometry' => false,
                'has_print_link' => true,
                'has_export_link' => true,
                'url_params' => [
                    'db' => Current::$database,
                    'table' => Current::$table,
                    'printview' => '1',
                    'sql_query' => $query,
                    'single_table' => 'true',
                    'unlim_num_rows' => 2,
                ],
            ],
            'db' => Current::$database,
            'table' => Current::$table,
            'unique_id' => 1234567890,
            'sql_query' => $query,
            'goto' => '',
            'unlim_num_rows' => 2,
            'displaywork' => false,
            'relwork' => false,
            'save_cells_at_once' => false,
            'default_sliders_state' => 'closed',
        ]);

        self::assertSame($tableTemplate, $actual);
    }

    /** @return array<string, array{string, string, int}> */
    public static function dataProviderSortOrder(): array
    {
        return [
            'Default date' => [
                'SMART',
                'DESC',// date types are DESC in SMART mode
                MYSQLI_TYPE_DATE,
            ],
            'ASC date' => [
                'ASC',
                'ASC',// do as config says
                MYSQLI_TYPE_DATE,
            ],
            'DESC date' => [
                'DESC',
                'DESC',// do as config says
                MYSQLI_TYPE_DATE,
            ],
            'Default date-time' => [
                'SMART',
                'DESC',// date time types are DESC in SMART mode
                MYSQLI_TYPE_DATETIME,
            ],
            'ASC date-time' => [
                'ASC',
                'ASC',// do as config says
                MYSQLI_TYPE_DATETIME,
            ],
            'DESC date-time' => [
                'DESC',
                'DESC',// do as config says
                MYSQLI_TYPE_DATETIME,
            ],
            'Default time' => [
                'SMART',
                'DESC',// time types are DESC in SMART mode
                MYSQLI_TYPE_TIME,
            ],
            'ASC time' => [
                'ASC',
                'ASC',// do as config says
                MYSQLI_TYPE_TIME,
            ],
            'DESC time' => [
                'DESC',
                'DESC',// do as config says
                MYSQLI_TYPE_TIME,
            ],
            'Default timestamp' => [
                'SMART',
                'DESC',// timestamp types are DESC in SMART mode
                MYSQLI_TYPE_TIMESTAMP,
            ],
            'ASC timestamp' => [
                'ASC',
                'ASC',// do as config says
                MYSQLI_TYPE_TIMESTAMP,
            ],
            'DESC timestamp' => [
                'DESC',
                'DESC',// do as config says
                MYSQLI_TYPE_TIMESTAMP,
            ],
            'Default string' => [
                'SMART',
                'ASC',// string types are ASC in SMART mode
                MYSQLI_TYPE_STRING,
            ],
            'ASC string' => [
                'ASC',
                'ASC',// do as config says
                MYSQLI_TYPE_STRING,
            ],
            'DESC string' => [
                'DESC',
                'DESC',// do as config says
                MYSQLI_TYPE_STRING,
            ],
        ];
    }

    #[DataProvider('dataProviderSortOrder')]
    public function testGetSingleAndMultiSortUrls(
        string $orderSetting,
        string $querySortDirection,
        int $metaType,
    ): void {
        Config::getInstance()->settings['Order'] = $orderSetting;

        $data = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'getSingleAndMultiSortUrls',
            [
                [new SortExpression('Country', 'Code', 'ASC', '`Country`.`Code`')],
                'Country',
                'FoundedIn',
                FieldHelper::fromArray(['type' => $metaType]),
            ],
        );

        self::assertSame([
            'ORDER BY `Country`.`FoundedIn` ' . $querySortDirection, // singleSortOrder
            'ORDER BY `Country`.`Code` ASC, `Country`.`FoundedIn` ' . $querySortDirection, // sortOrderColumns
            '', // orderImg
        ], $data);

        $data = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'getSingleAndMultiSortUrls',
            [
                [new SortExpression('Country', 'Code', 'ASC', '`Country`.`Code`')],
                'Country',
                'Code2',
                FieldHelper::fromArray(['type' => $metaType]),
            ],
        );

        self::assertSame([
            'ORDER BY `Country`.`Code2` ' . $querySortDirection, // singleSortOrder
            'ORDER BY `Country`.`Code` ASC, `Country`.`Code2` ' . $querySortDirection, // sortOrderColumns
            '', // orderImg
        ], $data);

        $data = $this->callFunction(
            $this->object,
            DisplayResults::class,
            'getSingleAndMultiSortUrls',
            [
                [
                    new SortExpression('Country', 'Continent', 'DESC', '`Country`.`Continent`'),
                    new SortExpression('Country', 'Region', 'ASC', '`Country`.`Region`'),
                    new SortExpression('Country', 'Population', 'ASC', '`Country`.`Population`'),
                ],
                'Country',
                'Code2',
                FieldHelper::fromArray(['type' => $metaType]),
            ],
        );

        self::assertSame([
            'ORDER BY `Country`.`Code2` ' . $querySortDirection, // singleSortOrder
            'ORDER BY `Country`.`Continent` DESC, `Country`.`Region` ASC'
                . ', `Country`.`Population` ASC, `Country`.`Code2` ' . $querySortDirection, // sortOrderColumns
            '', // orderImg
        ], $data);
    }
}
