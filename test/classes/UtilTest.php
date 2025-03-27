<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\SessionCache;
use PhpMyAdmin\Version;

use function __;
use function _setlocale;
use function count;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function file_exists;
use function floatval;
use function htmlspecialchars;
use function ini_get;
use function ini_set;
use function str_repeat;
use function str_replace;
use function strlen;
use function trim;

use const LC_ALL;
use const MYSQLI_NUM_FLAG;
use const MYSQLI_PRI_KEY_FLAG;
use const MYSQLI_TYPE_BIT;
use const MYSQLI_TYPE_GEOMETRY;
use const MYSQLI_TYPE_LONG;
use const MYSQLI_TYPE_SHORT;
use const MYSQLI_TYPE_STRING;
use const MYSQLI_UNIQUE_KEY_FLAG;

const FIELD_TYPE_INTEGER = 1;
const FIELD_TYPE_VARCHAR = 253;
const FIELD_TYPE_UNKNOWN = -1;

/**
 * @covers \PhpMyAdmin\Util
 */
class UtilTest extends AbstractTestCase
{
    /**
     * init data for the test
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setTheme();
    }

    /**
     * Test for listPHPExtensions
     *
     * @requires extension mysqli
     * @requires extension curl
     * @requires extension mbstring
     * @requires extension sodium
     */
    public function testListPHPExtensions(): void
    {
        self::assertSame([
            'mysqli',
            'curl',
            'mbstring',
            'sodium',
        ], Util::listPHPExtensions());
    }

    public function testGetUniqueCondition(): void
    {
        $GLOBALS['db'] = 'db';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;

        $actual = Util::getUniqueCondition(0, [], []);
        self::assertSame(['', false, []], $actual);

        $actual = Util::getUniqueCondition(0, [], [], true);
        self::assertSame(['', true, []], $actual);
    }

    public function testGetUniqueConditionWithMultipleFields(): void
    {
        $meta = [
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field1',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field2',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            new FieldMetadata(MYSQLI_TYPE_SHORT, MYSQLI_NUM_FLAG, (object) [
                'name' => 'field3',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            new FieldMetadata(MYSQLI_TYPE_LONG, MYSQLI_NUM_FLAG, (object) [
                'name' => 'field4',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field5',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 63, // binary
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field6',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 63, // binary
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field7',
                'table' => 'table',
                'orgtable' => 'table',
                'numeric' => false,
                'type' => 'blob',
                'charsetnr' => 32, // armscii8_general_ci
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field8',
                'table' => 'table',
                'orgtable' => 'table',
                'numeric' => false,
                'type' => 'blob',
                'charsetnr' => 48, // latin1_general_ci
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field9',
                'table' => 'table',
                'orgtable' => 'table',
                'numeric' => false,
                'type' => 'blob',
                'charsetnr' => 63, // binary
            ]),
            new FieldMetadata(MYSQLI_TYPE_GEOMETRY, 0, (object) [
                'name' => 'field10',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field11',
                'table' => 'table2',
                'orgtable' => 'table2',
            ]),
            new FieldMetadata(MYSQLI_TYPE_BIT, 0, (object) [
                'name' => 'field12',
                'table' => 'table',
                'orgtable' => 'table',
                'length' => 4,
            ]),
        ];

        $actual = Util::getUniqueCondition(count($meta), $meta, [
            null,
            'value\'s',
            123456,
            123.456,
            'value',
            str_repeat('*', 1001),
            'value',
            'value',
            'value',
            'value',
            'value',
            0x1,
        ], false, 'table');
        self::assertSame([
            '`table`.`field1` IS NULL AND `table`.`field2` = \'value\\\'s\' AND `table`.`field3` = 123456'
            . ' AND `table`.`field4` = 123.456 AND `table`.`field5` = CAST(0x76616c7565 AS BINARY)'
            . ' AND `table`.`field7` = \'value\' AND `table`.`field8` = \'value\''
            . ' AND `table`.`field9` = CAST(0x76616c7565 AS BINARY)'
            . ' AND `table`.`field10` = CAST(0x76616c7565 AS BINARY)'
            . ' AND `table`.`field12` = b\'0001\'',
            false,
            [
                '`table`.`field1`' => 'IS NULL',
                '`table`.`field2`' => '= \'value\\\'s\'',
                '`table`.`field3`' => '= 123456',
                '`table`.`field4`' => '= 123.456',
                '`table`.`field5`' => '= CAST(0x76616c7565 AS BINARY)',
                '`table`.`field7`' => '= \'value\'',
                '`table`.`field8`' => '= \'value\'',
                '`table`.`field9`' => '= CAST(0x76616c7565 AS BINARY)',
                '`table`.`field10`' => '',
                '`table`.`field12`' => '= b\'0001\'',
            ],
        ], $actual);
    }

    public function testGetUniqueConditionWithSingleBigBinaryField(): void
    {
        $meta = [
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field',
                'table' => 'table',
                'orgtable' => 'table',
                'charsetnr' => 63, // binary
            ]),
        ];

        $actual = Util::getUniqueCondition(1, $meta, [str_repeat('*', 1001)]);
        self::assertSame(['CHAR_LENGTH(`table`.`field`)  = 1001', false, ['`table`.`field`' => ' = 1001']], $actual);
    }

    public function testGetUniqueConditionWithPrimaryKey(): void
    {
        $meta = [
            new FieldMetadata(MYSQLI_TYPE_LONG, MYSQLI_PRI_KEY_FLAG | MYSQLI_NUM_FLAG, (object) [
                'name' => 'id',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
        ];

        $actual = Util::getUniqueCondition(count($meta), $meta, [1, 'value']);
        self::assertSame(['`table`.`id` = 1', true, ['`table`.`id`' => '= 1']], $actual);
    }

    public function testGetUniqueConditionWithUniqueKey(): void
    {
        $meta = [
            new FieldMetadata(MYSQLI_TYPE_STRING, MYSQLI_UNIQUE_KEY_FLAG, (object) [
                'name' => 'id',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
            new FieldMetadata(MYSQLI_TYPE_STRING, 0, (object) [
                'name' => 'field',
                'table' => 'table',
                'orgtable' => 'table',
            ]),
        ];

        $actual = Util::getUniqueCondition(count($meta), $meta, ['unique', 'value']);
        self::assertSame(['`table`.`id` = \'unique\'', true, ['`table`.`id`' => '= \'unique\'']], $actual);
    }

    /**
     * Test for Util::getUniqueCondition
     * note: GROUP_FLAG = MYSQLI_NUM_FLAG = 32769
     *
     * @param FieldMetadata[] $meta     Meta Information for Field
     * @param array           $row      Current Ddata Row
     * @param array           $expected Expected Result
     * @psalm-param array<int, mixed> $row
     * @psalm-param array{string, bool, array<string, string>} $expected
     *
     * @dataProvider providerGetUniqueConditionForGroupFlag
     */
    public function testGetUniqueConditionForGroupFlag(array $meta, array $row, array $expected): void
    {
        $fieldsCount = count($meta);
        $actual = Util::getUniqueCondition($fieldsCount, $meta, $row);

        self::assertSame($expected, $actual);
    }

    /**
     * Provider for testGetUniqueConditionForGroupFlag
     *
     * @return array<string, array{FieldMetadata[], array<int, mixed>, array{string, bool, array<string, string>}}>
     */
    public static function providerGetUniqueConditionForGroupFlag(): array
    {
        return [
            'field type is integer, value is number - not escape string' => [
                [
                    new FieldMetadata(FIELD_TYPE_INTEGER, MYSQLI_NUM_FLAG, (object) [
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                [123],
                [
                    '`table`.`col` = 123',
                    false,
                    ['`table`.`col`' => '= 123'],
                ],
            ],
            'field type is unknown, value is string - escape string' => [
                [
                    new FieldMetadata(FIELD_TYPE_UNKNOWN, MYSQLI_NUM_FLAG, (object) [
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ['test'],
                [
                    "`table`.`col` = 'test'",
                    false,
                    ['`table`.`col`' => "= 'test'"],
                ],
            ],
            'field type is varchar, value is string - escape string' => [
                [
                    new FieldMetadata(FIELD_TYPE_VARCHAR, MYSQLI_NUM_FLAG, (object) [
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ['test'],
                [
                    "`table`.`col` = 'test'",
                    false,
                    ['`table`.`col`' => "= 'test'"],
                ],
            ],
            'field type is varchar, value is string with double quote - escape string' => [
                [
                    new FieldMetadata(FIELD_TYPE_VARCHAR, MYSQLI_NUM_FLAG, (object) [
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ['"test"'],
                [
                    "`table`.`col` = '\\\"test\\\"'",
                    false,
                    ['`table`.`col`' => "= '\\\"test\\\"'"],
                ],
            ],
            'field type is varchar, value is string with single quote - escape string' => [
                [
                    new FieldMetadata(FIELD_TYPE_VARCHAR, MYSQLI_NUM_FLAG, (object) [
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ["'test'"],
                [
                    "`table`.`col` = '\'test\''",
                    false,
                    ['`table`.`col`' => "= '\'test\''"],
                ],
            ],
            'group by multiple columns and field type is mixed' => [
                [
                    new FieldMetadata(FIELD_TYPE_VARCHAR, MYSQLI_NUM_FLAG, (object) [
                        'name' => 'col',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                    new FieldMetadata(FIELD_TYPE_INTEGER, MYSQLI_NUM_FLAG, (object) [
                        'name' => 'status_id',
                        'table' => 'table',
                        'orgtable' => 'table',
                    ]),
                ],
                ['test', 2],
                [
                    "`table`.`col` = 'test' AND `table`.`status_id` = 2",
                    false,
                    [
                        '`table`.`col`' => "= 'test'",
                        '`table`.`status_id`' => '= 2',
                    ],
                ],
            ],
        ];
    }

    /**
     * Test for Page Selector
     */
    public function testPageSelector(): void
    {
        self::assertStringContainsString(
            '<select class="pageselector ajax" name="pma" >',
            Util::pageselector('pma', 3)
        );

        // If pageNow > nbTotalPage, show the pageNow number to avoid confusion
        self::assertStringContainsString(
            '<option selected="selected" style="font-weight: bold" value="297">100</option>',
            Util::pageselector('pma', 3, 100, 50)
        );
    }

    /**
     * Test for getCharsetQueryPart
     *
     * @param string $collation Collation
     * @param string $expected  Expected Charset Query
     *
     * @dataProvider charsetQueryData
     */
    public function testGenerateCharsetQueryPart(string $collation, string $expected): void
    {
        self::assertSame($expected, Util::getCharsetQueryPart($collation));
    }

    /**
     * Data Provider for testgetCharsetQueryPart
     *
     * @return array test data
     */
    public static function charsetQueryData(): array
    {
        return [
            [
                'a_b_c_d',
                ' CHARSET=a COLLATE a_b_c_d',
            ],
            [
                'a_',
                ' CHARSET=a COLLATE a_',
            ],
            [
                'a',
                ' CHARSET=a',
            ],
        ];
    }

    /**
     * Test for random generation
     */
    public function testGenerateRandom(): void
    {
        self::assertSame(32, strlen(Util::generateRandom(32)));
        self::assertSame(16, strlen(Util::generateRandom(16)));
    }

    public function testClearUserCache(): void
    {
        $GLOBALS['server'] = 'server';
        SessionCache::set('is_superuser', 'yes');
        self::assertSame('yes', $_SESSION['cache']['server_server']['is_superuser']);

        SessionCache::set('mysql_cur_user', 'mysql');
        self::assertSame('mysql', $_SESSION['cache']['server_server']['mysql_cur_user']);

        Util::clearUserCache();
        self::assertArrayNotHasKey('is_superuser', $_SESSION['cache']['server_server']);
        self::assertArrayNotHasKey('mysql_cur_user', $_SESSION['cache']['server_server']);
    }

    public function testCheckParameterMissing(): void
    {
        parent::setGlobalConfig();
        $_REQUEST = [];
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['AllowThirdPartyFraming'] = false;
        ResponseRenderer::getInstance()->setAjax(false);

        $this->expectOutputRegex('/Missing parameter: field/');

        Util::checkParameters(
            [
                'db',
                'table',
                'field',
            ]
        );
    }

    public function testCheckParameter(): void
    {
        parent::setGlobalConfig();
        $GLOBALS['cfg'] = ['ServerDefault' => 1];
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = Core::getenv('PHP_SELF');
        $GLOBALS['db'] = 'dbDatabase';
        $GLOBALS['table'] = 'tblTable';
        $GLOBALS['field'] = 'test_field';
        $GLOBALS['sql_query'] = 'SELECT * FROM tblTable;';

        $this->expectOutputString('');
        Util::checkParameters(
            [
                'db',
                'table',
                'field',
                'sql_query',
            ]
        );
    }

    /**
     * Test for Util::convertBitDefaultValue
     *
     * @param string|null $bit Value
     * @param string      $val Expected value
     *
     * @dataProvider providerConvertBitDefaultValue
     */
    public function testConvertBitDefaultValue(?string $bit, string $val): void
    {
        self::assertSame($val, Util::convertBitDefaultValue($bit));
    }

    /**
     * Provider for testConvertBitDefaultValue
     *
     * @return array
     */
    public static function providerConvertBitDefaultValue(): array
    {
        return [
            [
                null,
                '',
            ],
            [
                "b'",
                '',
            ],
            [
                "b'01'",
                '01',
            ],
            [
                "b'010111010'",
                '010111010',
            ],
            'database name starting with b' => [
                'big database',
                'big database',
            ],
            "database name containing b'" => [
                "a b'ig database",
                "a b'ig database",
            ],
            'database name in single quotes' => [
                "'a*database*name'",
                "'a*database*name'",
            ],
            "database name with multiple b'" => [
                "b'ens datab'ase'",
                "b'ens datab'ase'",
            ],
        ];
    }

    /**
     * data provider for testEscapeMysqlWildcards and testUnescapeMysqlWildcards
     *
     * @return array
     */
    public static function providerUnEscapeMysqlWildcards(): array
    {
        return [
            [
                '\_test',
                '_test',
            ],
            [
                '\_\\',
                '_\\',
            ],
            [
                '\\_\%',
                '_%',
            ],
            [
                '\\\_',
                '\_',
            ],
            [
                '\\\_\\\%',
                '\_\%',
            ],
            [
                '\_\\%\_\_\%',
                '_%__%',
            ],
            [
                '\%\_',
                '%_',
            ],
            [
                '\\\%\\\_',
                '\%\_',
            ],
        ];
    }

    /**
     * PhpMyAdmin\Util::escapeMysqlWildcards tests
     *
     * @param string $a Expected value
     * @param string $b String to escape
     *
     * @dataProvider providerUnEscapeMysqlWildcards
     */
    public function testEscapeMysqlWildcards(string $a, string $b): void
    {
        self::assertSame($a, Util::escapeMysqlWildcards($b));
    }

    /**
     * PhpMyAdmin\Util::unescapeMysqlWildcards tests
     *
     * @param string $a String to unescape
     * @param string $b Expected value
     *
     * @dataProvider providerUnEscapeMysqlWildcards
     */
    public function testUnescapeMysqlWildcards(string $a, string $b): void
    {
        self::assertSame($b, Util::unescapeMysqlWildcards($a));
    }

    /**
     * Test case for expanding strings
     *
     * @param string $in  string to evaluate
     * @param string $out expected output
     *
     * @dataProvider providerExpandUserString
     */
    public function testExpandUserString(string $in, string $out): void
    {
        parent::setGlobalConfig();
        $GLOBALS['cfg'] = [
            'Server' => [
                'host' => 'host&',
                'verbose' => 'verbose',
            ],
        ];
        $GLOBALS['db'] = 'database';
        $GLOBALS['table'] = 'table';

        self::assertSame($out, Util::expandUserString($in));

        self::assertSame(htmlspecialchars($out), Util::expandUserString(
            $in,
            'htmlspecialchars'
        ));
    }

    /**
     * Data provider for testExpandUserString
     *
     * @return array
     */
    public static function providerExpandUserString(): array
    {
        return [
            [
                '@SERVER@',
                'host&',
            ],
            [
                '@VSERVER@',
                'verbose',
            ],
            [
                '@DATABASE@',
                'database',
            ],
            [
                '@TABLE@',
                'table',
            ],
            [
                '@IGNORE@',
                '@IGNORE@',
            ],
            [
                '@PHPMYADMIN@',
                'phpMyAdmin ' . Version::VERSION,
            ],
        ];
    }

    /**
     * Test case for parsing SHOW COLUMNS output
     *
     * @param string $in  Column specification
     * @param array  $out Expected value
     *
     * @dataProvider providerExtractColumnSpec
     */
    public function testExtractColumnSpec(string $in, array $out): void
    {
        $GLOBALS['cfg']['LimitChars'] = 1000;

        self::assertEquals($out, Util::extractColumnSpec($in));
    }

    /**
     * Data provider for testExtractColumnSpec
     *
     * @return array
     */
    public static function providerExtractColumnSpec(): array
    {
        return [
            [
                "SET('a','b')",
                [
                    'type' => 'set',
                    'print_type' => "set('a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a','b'",
                    'enum_set_values' => [
                        'a',
                        'b',
                    ],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('a', 'b')",
                ],
            ],
            [
                "SET('\'a','b')",
                [
                    'type' => 'set',
                    'print_type' => "set('\'a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'\'a','b'",
                    'enum_set_values' => [
                        "\'a",
                        'b',
                    ],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('\'a', 'b')",
                ],
            ],
            [
                "SET('''a','b')",
                [
                    'type' => 'set',
                    'print_type' => "set('''a', 'b')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'''a','b'",
                    'enum_set_values' => [
                        "'a",
                        'b',
                    ],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "set('''a', 'b')",
                ],
            ],
            [
                "ENUM('a&b','b''c\\'d','e\\\\f')",
                [
                    'type' => 'enum',
                    'print_type' => "enum('a&b', 'b''c\\'d', 'e\\\\f')",
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => "'a&b','b''c\\'d','e\\\\f'",
                    'enum_set_values' => [
                        'a&b',
                        'b\'c\\\'d',
                        'e\\f',
                    ],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => "enum('a&amp;b', 'b''c\\'d', 'e\\\\f')",
                ],
            ],
            [
                'INT UNSIGNED zerofill',
                [
                    'type' => 'int',
                    'print_type' => 'int',
                    'binary' => false,
                    'unsigned' => true,
                    'zerofill' => true,
                    'spec_in_brackets' => '',
                    'enum_set_values' => [],
                    'attribute' => 'UNSIGNED ZEROFILL',
                    'can_contain_collation' => false,
                    'displayed_type' => 'int',
                ],
            ],
            [
                'VARCHAR(255)',
                [
                    'type' => 'varchar',
                    'print_type' => 'varchar(255)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '255',
                    'enum_set_values' => [],
                    'attribute' => ' ',
                    'can_contain_collation' => true,
                    'displayed_type' => 'varchar(255)',
                ],
            ],
            [
                'VARBINARY(255)',
                [
                    'type' => 'varbinary',
                    'print_type' => 'varbinary(255)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '255',
                    'enum_set_values' => [],
                    'attribute' => ' ',
                    'can_contain_collation' => false,
                    'displayed_type' => 'varbinary(255)',
                ],
            ],
            [
                'varchar(11) /*!100301 COMPRESSED*/',
                [
                    'type' => 'varchar',
                    'print_type' => 'varchar(11)',
                    'binary' => false,
                    'unsigned' => false,
                    'zerofill' => false,
                    'spec_in_brackets' => '11',
                    'enum_set_values' => [],
                    'attribute' => 'COMPRESSED=zlib',
                    'can_contain_collation' => true,
                    'displayed_type' => 'varchar(11)',
                ],
            ],
        ];
    }

    /**
     * Test case for parsing ENUM values
     *
     * @param string[] $out
     *
     * @dataProvider providerParseEnumSetValues
     */
    public function testParseEnumSetValues(string $in, bool $escapeHTML, array $out): void
    {
        self::assertSame($out, Util::parseEnumSetValues($in, $escapeHTML));
    }

    /**
     * Data provider for testParseEnumSetValues
     *
     * @return iterable<int, array{string, bool, string[]}>
     */
    public static function providerParseEnumSetValues(): iterable
    {
        $enumSpec = "enum('a&b','b''c''d','e\\f')";

        yield [
            $enumSpec,
            false,
            [
                'a&b',
                'b\'c\'d',
                'e\\f',
            ],
        ];

        yield [
            $enumSpec,
            true,
            [
                'a&amp;b',
                'b&#039;c&#039;d',
                'e\\f',
            ],
        ];

        $enumSpec = "set('<script>alert(\"ok\")</script>','a&b','b&c','vrai&amp','','漢字','''','\\\\','\"\\\\''')";

        yield [
            $enumSpec,
            false,
            [
                '<script>alert("ok")</script>',
                'a&b',
                'b&c',
                'vrai&amp',
                '',
                '漢字',
                "'",
                '\\',
                '"\\\'',
            ],
        ];

        yield [
            $enumSpec,
            true,
            [
                '&lt;script&gt;alert(&quot;ok&quot;)&lt;/script&gt;',
                'a&amp;b',
                'b&amp;c',
                'vrai&amp;amp',
                '',
                '漢字',
                '&#039;',
                '\\',
                '&quot;\&#039;',
            ],
        ];

        $enumSpec = "enum('1','2,','3''','''4')";

        yield [
            $enumSpec,
            false,
            [
                '1',
                '2,',
                '3\'',
                '\'4',
            ],
        ];

        yield [
            $enumSpec,
            true,
            [
                '1',
                '2,',
                '3&#039;',
                '&#039;4',
            ],
        ];

        $enumSpec = "enum('''','''''','\"','\\\\','\\\\''','\\\\\"',',','()')";

        yield [
            $enumSpec,
            false,
            [
                "'",
                "''",
                '"',
                '\\',
                "\\'",
                '\\"',
                ',',
                '()',
            ],
        ];
    }

    /**
     * Test for Util::extractValueFromFormattedSize
     *
     * @param int|string $size     Size
     * @param int|float  $expected Expected value (float on some cpu architectures)
     *
     * @dataProvider providerExtractValueFromFormattedSize
     */
    public function testExtractValueFromFormattedSize($size, $expected): void
    {
        self::assertSame($expected, Util::extractValueFromFormattedSize($size));
    }

    /**
     * Data provider for testExtractValueFromFormattedSize
     *
     * @return array
     */
    public static function providerExtractValueFromFormattedSize(): array
    {
        return [
            [
                100,
                -1,
            ],
            [
                '10GB',
                10737418240,
            ],
            [
                '15MB',
                15728640,
            ],
            [
                '256K',
                262144,
            ],
        ];
    }

    /**
     * format byte test, globals are defined
     *
     * @param float|int|string $a Value to format
     * @param int              $b Sensitiveness
     * @param int              $c Number of decimals to retain
     * @param array            $e Expected value
     *
     * @dataProvider providerFormatByteDown
     */
    public function testFormatByteDown($a, int $b, int $c, array $e): void
    {
        $result = Util::formatByteDown($a, $b, $c);
        self::assertIsArray($result);
        $result[0] = trim($result[0]);
        self::assertSame($e, $result);
    }

    /**
     * format byte down data provider
     *
     * @return array
     */
    public static function providerFormatByteDown(): array
    {
        return [
            [
                '0',
                6,
                0,
                [
                    '0',
                    __('B'),
                ],
            ],
            [
                'A4',
                6,
                0,
                [
                    '0',
                    __('B'),
                ],
            ],
            [
                10,
                2,
                2,
                [
                    '10',
                    __('B'),
                ],
            ],
            [
                100,
                2,
                0,
                [
                    '0',
                    __('KiB'),
                ],
            ],
            [
                100,
                3,
                0,
                [
                    '100',
                    __('B'),
                ],
            ],
            [
                100,
                2,
                2,
                [
                    '0.10',
                    __('KiB'),
                ],
            ],
            [
                1034,
                3,
                2,
                [
                    '1.01',
                    __('KiB'),
                ],
            ],
            [
                100233,
                3,
                3,
                [
                    '97.884',
                    __('KiB'),
                ],
            ],
            [
                '100233',
                3,
                3,
                [
                    '97.884',
                    __('KiB'),
                ],
            ],
            [
                '102400K',
                3,
                3,
                [
                    '100.000',
                    __('KiB'),
                ],
            ],
            [
                '102401K',
                3,
                3,
                [
                    '100.001',
                    __('KiB'),
                ],
            ],
            [
                '153600K',
                3,
                3,
                [
                    '150.000',
                    __('KiB'),
                ],
            ],
            [
                '153600K',
                3,
                0,
                [
                    '150',
                    __('KiB'),
                ],
            ],
            [
                102400 * 1024,
                3,
                0,
                [
                    '100',
                    __('MiB'),
                ],
            ],
            [
                2206451,
                1,
                2,
                [
                    '2.10',
                    __('MiB'),
                ],
            ],
            [
                21474836480,
                4,
                0,
                [
                    '20',
                    __('GiB'),
                ],
            ],
            [
                floatval(52) + floatval(2048),
                3,
                1,
                [
                    '2.1',
                    'KiB',
                ],
            ],
            [
                '' . (floatval(52) + floatval(2048)),
                3,
                1,
                [
                    '2.1',
                    'KiB',
                ],
            ],
        ];
    }

    /**
     * Core test for formatNumber
     *
     * @param float|int|string $a Value to format
     * @param int              $b Sensitiveness
     * @param int              $c Number of decimals to retain
     * @param string           $d Expected value
     */
    private function assertFormatNumber($a, int $b, int $c, string $d): void
    {
        self::assertSame($d, (string) Util::formatNumber(
            $a,
            $b,
            $c,
            false
        ));
    }

    /**
     * format number test, globals are defined
     *
     * @param float|int|string $a Value to format
     * @param int              $b Sensitiveness
     * @param int              $c Number of decimals to retain
     * @param string           $d Expected value
     *
     * @dataProvider providerFormatNumber
     */
    public function testFormatNumber($a, int $b, int $c, string $d): void
    {
        $this->assertFormatNumber($a, $b, $c, $d);

        // Test with various precisions
        $old_precision = (string) ini_get('precision');
        try {
            ini_set('precision', '20');
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', '14');
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', '10');
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', '5');
            $this->assertFormatNumber($a, $b, $c, $d);
            ini_set('precision', '-1');
            $this->assertFormatNumber($a, $b, $c, $d);
        } finally {
            ini_set('precision', $old_precision);
        }

        // Test with different translations
        $translator = Loader::getInstance()->getTranslator();

        try {
            // German
            $translator->setTranslation(',', '.');
            $translator->setTranslation('.', ',');
            $expected = str_replace([',', 'X'], ['.', ','], str_replace('.', 'X', $d));
            $this->assertFormatNumber($a, $b, $c, $expected);

            // Czech
            $translator->setTranslation(',', ' ');
            $translator->setTranslation('.', ',');
            $expected = str_replace([',', 'X'], [' ', ','], str_replace('.', 'X', $d));
            $this->assertFormatNumber($a, $b, $c, $expected);
        } finally {
            // Restore
            $translator->setTranslation(',', ',');
            $translator->setTranslation('.', '.');
        }
    }

    /**
     * format number data provider
     *
     * @return array
     */
    public static function providerFormatNumber(): array
    {
        return [
            [
                10,
                2,
                2,
                '10  ',
            ],
            [
                100,
                2,
                0,
                '100  ',
            ],
            [
                100,
                2,
                2,
                '100  ',
            ],
            [
                '100',
                2,
                2,
                '100  ',
            ],
            [
                -1000.454,
                4,
                2,
                '-1,000.45  ',
            ],
            [
                '-1000.454',
                4,
                2,
                '-1,000.45  ',
            ],
            [
                0.00003,
                3,
                2,
                '30 µ',
            ],
            [
                0.003,
                3,
                3,
                '3 m',
            ],
            [
                -0.003,
                6,
                0,
                '-3,000 µ',
            ],
            [
                100.98,
                0,
                2,
                '100.98',
            ],
            [
                21010101,
                0,
                2,
                '21,010,101.00',
            ],
            [
                1100000000,
                5,
                0,
                '1,100 M',
            ],
            [
                '1100000000',
                5,
                0,
                '1,100 M',
            ],
            [
                20000,
                2,
                2,
                '20 k',
            ],
            [
                20011,
                2,
                2,
                '20.01 k',
            ],
            [
                123456789,
                6,
                0,
                '123,457 k',
            ],
            [
                -123456789,
                4,
                2,
                '-123.46 M',
            ],
            [
                0,
                6,
                0,
                '0',
            ],
        ];
    }

    /**
     * Test for Util::getFormattedMaximumUploadSize
     *
     * @param int|float $size Size (float on some cpu architectures)
     * @param string    $unit Unit
     * @param string    $res  Result
     *
     * @dataProvider providerGetFormattedMaximumUploadSize
     */
    public function testGetFormattedMaximumUploadSize($size, string $unit, string $res): void
    {
        self::assertSame('(' . __('Max: ') . $res . $unit . ')', Util::getFormattedMaximumUploadSize($size));
    }

    /**
     * Data provider for testGetFormattedMaximumUploadSize
     *
     * @return array
     */
    public static function providerGetFormattedMaximumUploadSize(): array
    {
        return [
            [
                10,
                __('B'),
                '10',
            ],
            [
                100,
                __('B'),
                '100',
            ],
            [
                1024,
                __('B'),
                '1,024',
            ],
            [
                102400,
                __('KiB'),
                '100',
            ],
            [
                10240000,
                __('MiB'),
                '10',
            ],
            [
                2147483648,
                __('MiB'),
                '2,048',
            ],
            [
                21474836480,
                __('GiB'),
                '20',
            ],
            [
                '153600K',
                __('KiB'),
                '150',
            ],
            [
                '157286400',
                __('MiB'),
                '150',
            ],
            [
                // Equals to Core::getRealSize of '102400K'
                // according to PHP FAQ on "shorthandbytes"
                102400 * 1024,
                __('MiB'),
                '100',
            ],
        ];
    }

    /**
     * Test for Util::getTitleForTarget
     *
     * @param string $target Target
     * @param string $result Expected value
     *
     * @dataProvider providerGetTitleForTarget
     */
    public function testGetTitleForTarget(string $target, string $result): void
    {
        self::assertSame($result, Util::getTitleForTarget($target));
    }

    /**
     * Data provider for testGetTitleForTarget
     *
     * @return array
     */
    public static function providerGetTitleForTarget(): array
    {
        return [
            [
                'structure',
                __('Structure'),
            ],
            [
                'sql',
                __('SQL'),
            ],
            [
                'search',
                __('Search'),
            ],
            [
                'insert',
                __('Insert'),
            ],
            [
                'browse',
                __('Browse'),
            ],
            [
                'operations',
                __('Operations'),
            ],
        ];
    }

    /**
     * localised date test, globals are defined
     *
     * @param int    $a      Current timestamp
     * @param string $b      Format
     * @param string $e      Expected output
     * @param string $tz     Timezone to set
     * @param string $locale Locale to set
     *
     * @dataProvider providerLocalisedDate
     */
    public function testLocalisedDate(int $a, string $b, string $e, string $tz, string $locale): void
    {
        // A test case for #15830 could be added for using the php setlocale on a Windows CI
        // See https://github.com/phpmyadmin/phpmyadmin/issues/15830
        _setlocale(LC_ALL, $locale);
        $tmpTimezone = date_default_timezone_get();
        date_default_timezone_set($tz);

        self::assertSame($e, Util::localisedDate($a, $b));

        date_default_timezone_set($tmpTimezone);
        _setlocale(LC_ALL, 'en');
    }

    /**
     * data provider for localised date test
     *
     * @return array
     */
    public static function providerLocalisedDate(): array
    {
        $hasJaTranslations = file_exists(LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo');

        return [
            [
                1227455558,
                '',
                'Nov 23, 2008 at 03:52 PM',
                'Europe/London',
                'en',
            ],
            [
                1227455558,
                '%Y-%m-%d %H:%M:%S %a',
                '2008-11-23 15:52:38 Sun',
                'Europe/London',
                'en',
            ],
            [
                1227455558,
                '%Y-%m-%d %H:%M:%S %a',
                '2008-11-23 16:52:38 Sun',
                'Europe/Paris',
                'en',
            ],
            [
                1227455558,
                '%Y-%m-%d %H:%M:%S %a',
                '2008-11-24 00:52:38 Mon',
                'Asia/Tokyo',
                'en',
            ],
            [
                1227455558,
                '%a %A %b %B',
                'Mon Mon Nov Nov',
                'Asia/Tokyo',
                'en',
            ],
            [
                1227455558,
                '%a %A %b %B %P',
                'Mon Mon Nov Nov AM',
                'Asia/Tokyo',
                'en',
            ],
            [
                1227455558,
                '%Y-%m-%d %H:%M:%S %a',
                $hasJaTranslations ? '2008-11-24 00:52:38 月' : '2008-11-24 00:52:38 Mon',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '%a %A %b %B',
                $hasJaTranslations ? '月 月 11 月 11 月' : 'Mon Mon Nov Nov',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '%a %A %b %B %P',
                $hasJaTranslations ? '月 月 11 月 11 月 午前' : 'Mon Mon Nov Nov AM',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '月月',
                '月月',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '%Y 年 2 月 %d 日 %H:%M',
                '2008 年 2 月 24 日 00:52',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1227455558,
                '%Y 年 2 � %d 日 %H:%M',
                '2008 年 2 � 24 日 00:52',
                'Asia/Tokyo',
                'ja',
            ],
            [
                1617153941,
                'H:i:s Y-d-m',
                'H:i:s Y-d-m',// Not a valid strftime format
                'Europe/Paris',
                'fr',
            ],
            [
                1617153941,
                '',
                'mer. 31 mars 2021 à 03:25',// No format uses format "%B %d, %Y at %I:%M %p"
                'Europe/Paris',
                'fr',
            ],
        ];
    }

    /**
     * localised timestamp test, globals are defined
     *
     * @param int    $a Timespan in seconds
     * @param string $e Expected output
     *
     * @dataProvider providerTimespanFormat
     */
    public function testTimespanFormat(int $a, string $e): void
    {
        $GLOBALS['timespanfmt'] = '%s days, %s hours, %s minutes and %s seconds';
        $tmpTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/London');

        self::assertSame($e, Util::timespanFormat($a));

        date_default_timezone_set($tmpTimezone);
    }

    /**
     * data provider for localised timestamp test
     *
     * @return array
     */
    public static function providerTimespanFormat(): array
    {
        return [
            [
                1258,
                '0 days, 0 hours, 20 minutes and 58 seconds',
            ],
            [
                821958,
                '9 days, 12 hours, 19 minutes and 18 seconds',
            ],
        ];
    }

    /**
     * test for generating string contains printable bit value of selected data
     *
     * @param int    $a Value
     * @param int    $b Length
     * @param string $e Expected output
     *
     * @dataProvider providerPrintableBitValue
     */
    public function testPrintableBitValue(int $a, int $b, string $e): void
    {
        self::assertSame($e, Util::printableBitValue($a, $b));
    }

    /**
     * data provider for printable bit value test
     *
     * @return array
     */
    public static function providerPrintableBitValue(): array
    {
        return [
            [
                20131009,
                64,
                '0000000000000000000000000000000000000001001100110010110011000001',
            ],
            [
                5,
                32,
                '00000000000000000000000000000101',
            ],
        ];
    }

    /**
     * PhpMyAdmin\Util::unQuote test
     *
     * @param string $param    String
     * @param string $expected Expected output
     *
     * @dataProvider providerUnQuote
     */
    public function testUnQuote(string $param, string $expected): void
    {
        self::assertSame($expected, Util::unQuote($param));
    }

    /**
     * data provider for PhpMyAdmin\Util::unQuote test
     *
     * @return array
     */
    public static function providerUnQuote(): array
    {
        return [
            [
                '"test\'"',
                "test'",
            ],
            [
                "'test''",
                "test'",
            ],
            [
                "`test'`",
                "test'",
            ],
            [
                "'test'test",
                "'test'test",
            ],
        ];
    }

    /**
     * PhpMyAdmin\Util::unQuote test with chosen quote
     *
     * @param string $param    String
     * @param string $expected Expected output
     *
     * @dataProvider providerUnQuoteSelectedChar
     */
    public function testUnQuoteSelectedChar(string $param, string $expected): void
    {
        self::assertSame($expected, Util::unQuote($param, '"'));
    }

    /**
     * data provider for PhpMyAdmin\Util::unQuote test with chosen quote
     *
     * @return array
     */
    public static function providerUnQuoteSelectedChar(): array
    {
        return [
            [
                '"test\'"',
                "test'",
            ],
            [
                "'test''",
                "'test''",
            ],
            [
                "`test'`",
                "`test'`",
            ],
            [
                "'test'test",
                "'test'test",
            ],
        ];
    }

    /**
     * @dataProvider providerForTestBackquote
     */
    public function testBackquote(?string $entry, string $expectedNoneOutput, string $expectedMssqlOutput): void
    {
        self::assertSame($expectedNoneOutput, Util::backquote($entry));
        self::assertEquals($entry, Util::backquoteCompat($entry, 'NONE', false));
        self::assertEquals($entry, Util::backquoteCompat($entry, 'MSSQL', false));
        self::assertSame($expectedNoneOutput, Util::backquoteCompat($entry, 'NONE'));
        self::assertSame($expectedMssqlOutput, Util::backquoteCompat($entry, 'MSSQL'));
    }

    /**
     * @return array<int|string, string|null>[]
     */
    public static function providerForTestBackquote(): array
    {
        return [
            [
                '0',
                '`0`',
                '"0"',
            ],
            [
                'test',
                '`test`',
                '"test"',
            ],
            [
                'te`st',
                '`te``st`',
                '"te`st"',
            ],
            [
                'te"st',
                '`te"st`',
                '"te\"st"',
            ],
            [
                '',
                '',
                '',
            ],
            [
                '*',
                '*',
                '*',
            ],
            [
                null,
                '',
                '',
            ],
        ];
    }

    /**
     * backquoteCompat test with forbidden words
     */
    public function testBackquoteForbidenWords(): void
    {
        foreach (Context::$KEYWORDS as $keyword => $type) {
            if ($type & Token::FLAG_KEYWORD_RESERVED) {
                self::assertSame('`' . $keyword . '`', Util::backquoteCompat($keyword, 'NONE', false));
            } else {
                self::assertSame($keyword, Util::backquoteCompat($keyword, 'NONE', false));
            }
        }
    }

    /**
     * test of generating user dir, globals are defined
     *
     * @param string $a String
     * @param string $e Expected output
     *
     * @dataProvider providerUserDir
     */
    public function testUserDir(string $a, string $e): void
    {
        $GLOBALS['cfg']['Server']['user'] = 'root';

        self::assertSame($e, Util::userDir($a));
    }

    /**
     * data provider for PhpMyAdmin\Util::userDir test
     *
     * @return array
     */
    public static function providerUserDir(): array
    {
        return [
            [
                '/var/pma_tmp/%u/',
                '/var/pma_tmp/root/',
            ],
            [
                '/home/%u/pma',
                '/home/root/pma/',
            ],
        ];
    }

    /**
     * duplicate first newline test
     *
     * @param string $a String
     * @param string $e Expected output
     *
     * @dataProvider providerDuplicateFirstNewline
     */
    public function testDuplicateFirstNewline(string $a, string $e): void
    {
        self::assertSame($e, Util::duplicateFirstNewline($a));
    }

    /**
     * data provider for duplicate first newline test
     *
     * @return array
     */
    public static function providerDuplicateFirstNewline(): array
    {
        return [
            [
                'test',
                'test',
            ],
            [
                "\r\ntest",
                "\n\r\ntest",
            ],
            [
                "\ntest",
                "\ntest",
            ],
            [
                "\n\r\ntest",
                "\n\r\ntest",
            ],
        ];
    }

    public function testUnsupportedDatatypes(): void
    {
        $no_support_types = [];
        self::assertSame($no_support_types, Util::unsupportedDatatypes());
    }

    public function testGetPageFromPosition(): void
    {
        self::assertSame(Util::getPageFromPosition(0, 1), 1);
        self::assertSame(Util::getPageFromPosition(1, 1), 2);
        self::assertSame(Util::getPageFromPosition(1, 2), 1);
        self::assertSame(Util::getPageFromPosition(1, 6), 1);
    }

    /**
     * Test for Util::isInteger
     *
     * @param bool  $expected Expected result for a given input
     * @param mixed $input    Input data to check
     *
     * @dataProvider providerIsInteger
     */
    public function testIsInteger(bool $expected, $input): void
    {
        $isInteger = Util::isInteger($input);
        self::assertSame($expected, $isInteger);
    }

    /**
     * Data provider for Util::isInteger test
     *
     * @return array
     */
    public static function providerIsInteger(): array
    {
        return [
            [
                true,
                1000,
            ],
            [
                true,
                '1000',
            ],
            [
                false,
                1000.1,
            ],
            [
                false,
                '1000.1',
            ],
            [
                false,
                'input',
            ],
        ];
    }

    /**
     * Test for Util::getProtoFromForwardedHeader
     *
     * @param string $header The http Forwarded header
     * @param string $proto  The protocol http/https
     *
     * @dataProvider providerForwardedHeaders
     */
    public function testGetProtoFromForwardedHeader(string $header, string $proto): void
    {
        $protocolDetected = Util::getProtoFromForwardedHeader($header);
        self::assertSame($proto, $protocolDetected);
    }

    /**
     * Data provider for Util::getProtoFromForwardedHeader test
     *
     * @return array
     *
     * @source https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Forwarded MDN docs
     * @source https://www.nginx.com/resources/wiki/start/topics/examples/forwarded/ Nginx docs
     */
    public static function providerForwardedHeaders(): array
    {
        return [
            [
                '',
                '',
            ],
            [
                '=',
                '',
            ],
            [
                'https',
                '',
            ],
            [
                'https',
                '',
            ],
            [
                '=https',
                '',
            ],
            [
                '=http',
                '',
            ],
            [
                'For="[2001:db8:cafe::17]:4711"',
                '',
            ],
            [
                'for=192.0.2.60;proto=http;by=203.0.113.43',
                'http',
            ],
            [
                'for=192.0.2.43, for=198.51.100.17',
                '',
            ],
            [
                'for=123.34.567.89',
                '',
            ],
            [
                'for=192.0.2.43, for="[2001:db8:cafe::17]"',
                '',
            ],
            [
                'for=12.34.56.78;host=example.com;proto=https, for=23.45.67.89',
                'https',
            ],
            [
                'for=12.34.56.78, for=23.45.67.89;secret=egah2CGj55fSJFs, for=10.1.2.3',
                '',
            ],
            [
                'for=injected;by="',
                '',
            ],
            [
                'for=injected;by=", for=real',
                '',
            ],
            [
                'for=192.0.2.60;proto=http;by=203.0.113.43',
                'http',
            ],
            [
                'for=192.0.2.60;proto=htTp;by=203.0.113.43',
                'http',
            ],
            [
                'for=192.0.2.60;proto=HTTP;by=203.0.113.43',
                'http',
            ],
            [
                'for=192.0.2.60;proto= http;by=203.0.113.43',
                'http',
            ],
            [
                'for=12.34.45.67;secret="special;proto=abc;test=1";proto=http,for=23.45.67.89',
                'http',
            ],
            [
                'for=12.34.45.67;secret="special;proto=abc;test=1";proto=418,for=23.45.67.89',
                '',
            ],
            /*[ // this test case is very special and would need a different implementation
                'for=12.34.45.67;secret="special;proto=http;test=1";proto=https,for=23.45.67.89',
                'https'
            ]*/
        ];
    }

    public function testCurrentUserHasPrivilegeSkipGrantTables(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['', '']));

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        self::assertTrue(Util::currentUserHasPrivilege('EVENT'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasUserPrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with(
                'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
            )
            ->will($this->returnValue('EVENT'));

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        self::assertTrue(Util::currentUserHasPrivilege('EVENT'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    public function testCurrentUserHasNotUserPrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->once())
            ->method('fetchValue')
            ->with(
                'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
            )
            ->will($this->returnValue(false));

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        self::assertFalse(Util::currentUserHasPrivilege('EVENT'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @requires PHPUnit < 10
     */
    public function testCurrentUserHasNotUserPrivilegeButDbPrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->onlyMethods(['getCurrentUserAndHost', 'fetchValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->exactly(2))
            ->method('fetchValue')
            ->withConsecutive(
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA`",
                ]
            )
            ->willReturnOnConsecutiveCalls(false, 'EVENT');

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        self::assertTrue(Util::currentUserHasPrivilege('EVENT', 'my_data_base'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @requires PHPUnit < 10
     */
    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->onlyMethods(['getCurrentUserAndHost', 'fetchValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->exactly(2))
            ->method('fetchValue')
            ->withConsecutive(
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA`",
                ]
            )
            ->willReturnOnConsecutiveCalls(false, false);

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        self::assertFalse(Util::currentUserHasPrivilege('EVENT', 'my_data_base'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @requires PHPUnit < 10
     */
    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilegeButTablePrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->onlyMethods(['getCurrentUserAndHost', 'fetchValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->exactly(3))
            ->method('fetchValue')
            ->withConsecutive(
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA`",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA` AND TABLE_NAME='my_data_table'",
                ]
            )
            ->willReturnOnConsecutiveCalls(false, false, 'EVENT');

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        self::assertTrue(Util::currentUserHasPrivilege('EVENT', 'my_data_base', 'my_data_table'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @requires PHPUnit < 10
     */
    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilegeAndNotTablePrivilege(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->onlyMethods(['getCurrentUserAndHost', 'fetchValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('getCurrentUserAndHost')
            ->will($this->returnValue(['groot_%', '%']));
        $dbi->expects($this->exactly(3))
            ->method('fetchValue')
            ->withConsecutive(
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA`",
                ],
                [
                    'SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES`'
                . " WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'"
                . " AND 'my_data_base' LIKE `TABLE_SCHEMA` AND TABLE_NAME='my_data_table'",
                ]
            )
            ->willReturnOnConsecutiveCalls(false, false, false);

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        self::assertFalse(Util::currentUserHasPrivilege('EVENT', 'my_data_base', 'my_data_table'));
        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * @return array[]
     */
    public static function dataProviderScriptNames(): array
    {
        // target
        // location
        // function output
        return [
            [
                'structure', // Notice the typo on db_structure.php
                'databasesss',
                'index.php?route=/&lang=en', // Fallback to the base route
            ],
            [
                'db_structures.php', // Notice the typo on databases
                'database',
                'index.php?route=/&lang=en', // Fallback to the base route
            ],
            [
                'tbl_structure.php', // Support the legacy value
                'table',
                'index.php?route=/table/structure&lang=en',
            ],
            [
                'structure',
                'table',
                'index.php?route=/table/structure&lang=en',
            ],
            [
                'tbl_sql.php', // Support the legacy value
                'table',
                'index.php?route=/table/sql&lang=en',
            ],
            [
                'sql',
                'table',
                'index.php?route=/table/sql&lang=en',
            ],
            [
                'tbl_select.php', // Support the legacy value
                'table',
                'index.php?route=/table/search&lang=en',
            ],
            [
                'search',
                'table',
                'index.php?route=/table/search&lang=en',
            ],
            [
                'tbl_change.php', // Support the legacy value
                'table',
                'index.php?route=/table/change&lang=en',
            ],
            [
                'insert',
                'table',
                'index.php?route=/table/change&lang=en',
            ],
            [
                'sql.php', // Support the legacy value
                'table',
                'index.php?route=/sql&lang=en',
            ],
            [
                'browse',
                'table',
                'index.php?route=/sql&lang=en',
            ],
            [
                'db_structure.php', // Support the legacy value
                'database',
                'index.php?route=/database/structure&lang=en',
            ],
            [
                'structure',
                'database',
                'index.php?route=/database/structure&lang=en',
            ],
            [
                'db_sql.php', // Support the legacy value
                'database',
                'index.php?route=/database/sql&lang=en',
            ],
            [
                'sql',
                'database',
                'index.php?route=/database/sql&lang=en',
            ],
            [
                'db_search.php', // Support the legacy value
                'database',
                'index.php?route=/database/search&lang=en',
            ],
            [
                'search',
                'database',
                'index.php?route=/database/search&lang=en',
            ],
            [
                'db_operations.php', // Support the legacy value
                'database',
                'index.php?route=/database/operations&lang=en',
            ],
            [
                'operations',
                'database',
                'index.php?route=/database/operations&lang=en',
            ],
            [
                'index.php', // Support the legacy value
                'server',
                'index.php?route=/&lang=en',
            ],
            [
                'welcome',
                'server',
                'index.php?route=/&lang=en',
            ],
            [
                'server_databases.php', // Support the legacy value
                'server',
                'index.php?route=/server/databases&lang=en',
            ],
            [
                'databases',
                'server',
                'index.php?route=/server/databases&lang=en',
            ],
            [
                'server_status.php', // Support the legacy value
                'server',
                'index.php?route=/server/status&lang=en',
            ],
            [
                'status',
                'server',
                'index.php?route=/server/status&lang=en',
            ],
            [
                'server_variables.php', // Support the legacy value
                'server',
                'index.php?route=/server/variables&lang=en',
            ],
            [
                'variables',
                'server',
                'index.php?route=/server/variables&lang=en',
            ],
            [
                'server_privileges.php', // Support the legacy value
                'server',
                'index.php?route=/server/privileges&lang=en',
            ],
            [
                'privileges',
                'server',
                'index.php?route=/server/privileges&lang=en',
            ],
        ];
    }

    /**
     * @dataProvider dataProviderScriptNames
     */
    public function testGetScriptNameForOption(string $target, string $location, string $finalLink): void
    {
        self::assertSame($finalLink, Util::getScriptNameForOption($target, $location));
    }

    /**
     * Tests for Util::testIsUUIDSupported() method.
     *
     * @param bool $isMariaDB True if mariadb
     * @param int  $version   Database version as integer
     * @param bool $expected  Expected Result
     *
     * @dataProvider provideForTestIsUUIDSupported
     */
    public function testIsUUIDSupported(bool $isMariaDB, int $version, bool $expected): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('isMariaDB')
            ->will($this->returnValue($isMariaDB));

        $dbi->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue($version));

        $oldDbi = $GLOBALS['dbi'];
        $GLOBALS['dbi'] = $dbi;
        self::assertSame(Util::isUUIDSupported(), $expected);
        $GLOBALS['dbi'] = $oldDbi;
    }

    /**
     * Data provider for isUUIDSupported() tests.
     *
     * @return array
     * @psalm-return array<int, array{bool, int, bool}>
     */
    public static function provideForTestIsUUIDSupported(): array
    {
        return [
            [
                false,
                60100,
                false,
            ],
            [
                false,
                100700,
                false,
            ],
            [
                true,
                60100,
                false,
            ],
            [
                true,
                100700,
                true,
            ],
        ];
    }
}
