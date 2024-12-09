<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use DateTimeImmutable;
use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\MoTranslator\Loader;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Token;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\SessionCache;
use PhpMyAdmin\Version;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psr\Http\Message\ServerRequestInterface;

use function __;
use function _setlocale;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function file_exists;
use function htmlspecialchars;
use function ini_get;
use function ini_set;
use function is_readable;
use function str_replace;
use function strlen;

use const LC_ALL;
use const LOCALE_PATH;

#[CoversClass(Util::class)]
class UtilTest extends AbstractTestCase
{
    /**
     * Test for listPHPExtensions
     */
    #[RequiresPhpExtension('curl')]
    #[RequiresPhpExtension('mbstring')]
    #[RequiresPhpExtension('mysqli')]
    #[RequiresPhpExtension('sodium')]
    public function testListPHPExtensions(): void
    {
        self::assertSame(
            ['mysqli', 'curl', 'mbstring', 'sodium'],
            Util::listPHPExtensions(),
        );
    }

    /**
     * Test for Page Selector
     */
    public function testPageSelector(): void
    {
        self::assertStringContainsString(
            '<select class="pageselector ajax" name="pma" >',
            Util::pageselector('pma', 3),
        );

        // If pageNow > nbTotalPage, show the pageNow number to avoid confusion
        self::assertStringContainsString(
            '<option selected="selected" style="font-weight: bold" value="297">100</option>',
            Util::pageselector('pma', 3, 100, 50),
        );
    }

    /**
     * Test for getCharsetQueryPart
     *
     * @param string $collation Collation
     * @param string $expected  Expected Charset Query
     */
    #[DataProvider('charsetQueryData')]
    public function testGenerateCharsetQueryPart(string $collation, string $expected): void
    {
        self::assertSame(
            $expected,
            Util::getCharsetQueryPart($collation),
        );
    }

    /**
     * Data Provider for testgetCharsetQueryPart
     *
     * @return mixed[] test data
     */
    public static function charsetQueryData(): array
    {
        return [['a_b_c_d', ' CHARSET=a COLLATE a_b_c_d'], ['a_', ' CHARSET=a COLLATE a_'], ['a', ' CHARSET=a']];
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
        Config::getInstance()->selectedServer['user'] = null;
        Current::$server = 2;
        SessionCache::set('is_superuser', 'yes');
        self::assertSame('yes', $_SESSION['cache']['server_2']['is_superuser']);

        SessionCache::set('mysql_cur_user', 'mysql');
        self::assertSame('mysql', $_SESSION['cache']['server_2']['mysql_cur_user']);

        Util::clearUserCache();
        self::assertArrayNotHasKey('is_superuser', $_SESSION['cache']['server_2']);
        self::assertArrayNotHasKey('mysql_cur_user', $_SESSION['cache']['server_2']);
    }

    /**
     * Test for Util::convertBitDefaultValue
     *
     * @param string|null $bit Value
     * @param string      $val Expected value
     */
    #[DataProvider('providerConvertBitDefaultValue')]
    public function testConvertBitDefaultValue(string|null $bit, string $val): void
    {
        self::assertSame(
            $val,
            Util::convertBitDefaultValue($bit),
        );
    }

    /**
     * Provider for testConvertBitDefaultValue
     *
     * @return mixed[]
     */
    public static function providerConvertBitDefaultValue(): array
    {
        return [
            [null, ''],
            ["b'", ''],
            ["b'01'", '01'],
            ["b'010111010'", '010111010'],
            'database name starting with b' => ['big database', 'big database'],
            "database name containing b'" => ["a b'ig database", "a b'ig database"],
            'database name in single quotes' => ["'a*database*name'", "'a*database*name'"],
            "database name with multiple b'" => ["b'ens datab'ase'", "b'ens datab'ase'"],
        ];
    }

    /**
     * Test case for expanding strings
     *
     * @param string $in  string to evaluate
     * @param string $out expected output
     */
    #[DataProvider('providerExpandUserString')]
    public function testExpandUserString(string $in, string $out): void
    {
        $this->setGlobalConfig();

        $config = Config::getInstance();
        $config->selectedServer['host'] = 'host&';
        $config->selectedServer['verbose'] = 'verbose';
        Current::$database = 'database';
        Current::$table = 'table';

        self::assertSame(
            $out,
            Util::expandUserString($in),
        );

        self::assertSame(
            htmlspecialchars($out),
            Util::expandUserString(
                $in,
                'htmlspecialchars',
            ),
        );
    }

    /**
     * Data provider for testExpandUserString
     *
     * @return array<int, string[]>
     */
    public static function providerExpandUserString(): array
    {
        return [
            ['@SERVER@', 'host&'],
            ['@VSERVER@', 'verbose'],
            ['@DATABASE@', 'database'],
            ['@TABLE@', 'table'],
            ['@IGNORE@', '@IGNORE@'],
            ['@PHPMYADMIN@', 'phpMyAdmin ' . Version::VERSION],
        ];
    }

    /**
     * Test case for parsing SHOW COLUMNS output
     *
     * @param string  $in  Column specification
     * @param mixed[] $out Expected value
     */
    #[DataProvider('providerExtractColumnSpec')]
    public function testExtractColumnSpec(string $in, array $out): void
    {
        Config::getInstance()->settings['LimitChars'] = 1000;

        self::assertEquals(
            $out,
            Util::extractColumnSpec($in),
        );
    }

    /**
     * Data provider for testExtractColumnSpec
     *
     * @return mixed[]
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
                    'enum_set_values' => ['a', 'b'],
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
                    'enum_set_values' => ["\'a", 'b'],
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
                    'enum_set_values' => ["'a", 'b'],
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
                    'enum_set_values' => ['a&b', 'b\'c\\\'d', 'e\\f'],
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
     */
    #[DataProvider('providerParseEnumSetValues')]
    public function testParseEnumSetValues(string $in, bool $escapeHTML, array $out): void
    {
        self::assertSame(
            $out,
            Util::parseEnumSetValues($in, $escapeHTML),
        );
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
     */
    #[DataProvider('providerExtractValueFromFormattedSize')]
    public function testExtractValueFromFormattedSize(int|string $size, int|float $expected): void
    {
        self::assertSame(
            $expected,
            Util::extractValueFromFormattedSize($size),
        );
    }

    /**
     * Data provider for testExtractValueFromFormattedSize
     *
     * @return mixed[]
     */
    public static function providerExtractValueFromFormattedSize(): array
    {
        return [[100, -1], ['10GB', 10737418240], ['15MB', 15728640], ['256K', 262144]];
    }

    /**
     * format byte test, globals are defined
     *
     * @param float|int|string|null $a Value to format
     * @param int                   $b Sensitiveness
     * @param int                   $c Number of decimals to retain
     * @param string[]|null         $e Expected value
     */
    #[DataProvider('providerFormatByteDown')]
    public function testFormatByteDown(float|int|string|null $a, int $b, int $c, array|null $e): void
    {
        $result = Util::formatByteDown($a, $b, $c);
        self::assertSame($e, $result);
    }

    /**
     * format byte down data provider
     *
     * @psalm-return list<array{float|int|string|null, int, int, string[]|null}>
     */
    public static function providerFormatByteDown(): array
    {
        return [
            [null, 0, 0, null],
            ['0', 6, 0, ['0', __('B')]],
            ['A4', 6, 0, ['0', __('B')]],
            [10, 2, 2, ['10', __('B')]],
            [100, 2, 0, ['0', __('KiB')]],
            [100, 3, 0, ['100', __('B')]],
            [100, 2, 2, ['0.10', __('KiB')]],
            [1034, 3, 2, ['1.01', __('KiB')]],
            [100233, 3, 3, ['97.884', __('KiB')]],
            ['100233', 3, 3, ['97.884', __('KiB')]],
            ['102400K', 3, 3, ['100.000', __('KiB')]],
            ['102401K', 3, 3, ['100.001', __('KiB')]],
            ['153600K', 3, 3, ['150.000', __('KiB')]],
            ['153600K', 3, 0, ['150', __('KiB')]],
            [102400 * 1024, 3, 0, ['100', __('MiB')]],
            [2206451, 1, 2, ['2.10', __('MiB')]],
            [21474836480, 4, 0, ['20', __('GiB')]],
            [(float) 52 + (float) 2048, 3, 1, ['2.1', 'KiB']],
            ['' . ((float) 52 + (float) 2048), 3, 1, ['2.1', 'KiB']],
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
    private function assertFormatNumber(float|int|string $a, int $b, int $c, string $d): void
    {
        self::assertSame(
            $d,
            Util::formatNumber(
                $a,
                $b,
                $c,
                false,
            ),
        );
    }

    /**
     * format number test, globals are defined
     *
     * @param float|int|string $a Value to format
     * @param int              $b Sensitiveness
     * @param int              $c Number of decimals to retain
     * @param string           $d Expected value
     */
    #[DataProvider('providerFormatNumber')]
    public function testFormatNumber(float|int|string $a, int $b, int $c, string $d): void
    {
        $this->assertFormatNumber($a, $b, $c, $d);

        // Test with various precisions
        $oldPrecision = (string) ini_get('precision');
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
            ini_set('precision', $oldPrecision);
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
     * @return mixed[]
     */
    public static function providerFormatNumber(): array
    {
        return [
            [10, 2, 2, '10  '],
            [100, 2, 0, '100  '],
            [100, 2, 2, '100  '],
            ['100', 2, 2, '100  '],
            [-1000.454, 4, 2, '-1,000.45  '],
            ['-1000.454', 4, 2, '-1,000.45  '],
            [0.00003, 3, 2, '30 µ'],
            [0.003, 3, 3, '3 m'],
            [-0.003, 6, 0, '-3,000 µ'],
            [100.98, 0, 2, '100.98'],
            [21010101, 0, 2, '21,010,101.00'],
            [1100000000, 5, 0, '1,100 M'],
            ['1100000000', 5, 0, '1,100 M'],
            [20000, 2, 2, '20 k'],
            [20011, 2, 2, '20.01 k'],
            [123456789, 6, 0, '123,457 k'],
            [-123456789, 4, 2, '-123.46 M'],
            [0, 6, 0, '0'],
        ];
    }

    /**
     * Test for Util::getFormattedMaximumUploadSize
     *
     * @param int|float|string $size Size (float on some cpu architectures)
     * @param string           $unit Unit
     * @param string           $res  Result
     */
    #[DataProvider('providerGetFormattedMaximumUploadSize')]
    public function testGetFormattedMaximumUploadSize(int|float|string $size, string $unit, string $res): void
    {
        self::assertSame(
            '(' . __('Max: ') . $res . $unit . ')',
            Util::getFormattedMaximumUploadSize($size),
        );
    }

    /**
     * Data provider for testGetFormattedMaximumUploadSize
     *
     * @return mixed[]
     */
    public static function providerGetFormattedMaximumUploadSize(): array
    {
        return [
            [10, __('B'), '10'],
            [100, __('B'), '100'],
            [1024, __('B'), '1,024'],
            [102400, __('KiB'), '100'],
            [10240000, __('MiB'), '10'],
            [2147483648, __('MiB'), '2,048'],
            [21474836480, __('GiB'), '20'],
            ['153600K', __('KiB'), '150'],
            ['157286400', __('MiB'), '150'],
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
     */
    #[DataProvider('providerGetTitleForTarget')]
    public function testGetTitleForTarget(string $target, string $result): void
    {
        self::assertSame(
            $result,
            Util::getTitleForTarget($target),
        );
    }

    /**
     * Data provider for testGetTitleForTarget
     *
     * @return mixed[]
     */
    public static function providerGetTitleForTarget(): array
    {
        return [
            ['structure', __('Structure')],
            ['sql', __('SQL')],
            ['search', __('Search')],
            ['insert', __('Insert')],
            ['browse', __('Browse')],
            ['operations', __('Operations')],
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
     */
    #[DataProvider('providerLocalisedDate')]
    public function testLocalisedDate(int $a, string $b, string $e, string $tz, string $locale): void
    {
        if (! is_readable(LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo')) {
            self::markTestSkipped('Missing compiled locales.');
        }

        $this->setLanguage();

        // A test case for #15830 could be added for using the php setlocale on a Windows CI
        // See https://github.com/phpmyadmin/phpmyadmin/issues/15830
        _setlocale(LC_ALL, $locale);
        $tmpTimezone = date_default_timezone_get();
        date_default_timezone_set($tz);

        self::assertSame($e, Util::localisedDate((new DateTimeImmutable())->setTimestamp($a), $b));

        date_default_timezone_set($tmpTimezone);
        _setlocale(LC_ALL, 'en');
    }

    /**
     * data provider for localised date test
     *
     * @return mixed[]
     */
    public static function providerLocalisedDate(): array
    {
        $hasJaTranslations = file_exists(LOCALE_PATH . '/cs/LC_MESSAGES/phpmyadmin.mo');

        return [
            [1227455558, '', 'Nov 23, 2008 at 03:52 PM', 'Europe/London', 'en'],
            [1227455558, '%Y-%m-%d %H:%M:%S %a', '2008-11-23 15:52:38 Sun', 'Europe/London', 'en'],
            [1227455558, '%Y-%m-%d %H:%M:%S %a', '2008-11-23 16:52:38 Sun', 'Europe/Paris', 'en'],
            [1227455558, '%Y-%m-%d %H:%M:%S %a', '2008-11-24 00:52:38 Mon', 'Asia/Tokyo', 'en'],
            [1227455558, '%a %A %b %B', 'Mon Mon Nov Nov', 'Asia/Tokyo', 'en'],
            [1227455558, '%a %A %b %B %P', 'Mon Mon Nov Nov AM', 'Asia/Tokyo', 'en'],
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
            [1227455558, '月月', '月月', 'Asia/Tokyo', 'ja'],
            [1227455558, '%Y 年 2 月 %d 日 %H:%M', '2008 年 2 月 24 日 00:52', 'Asia/Tokyo', 'ja'],
            [1227455558, '%Y 年 2 � %d 日 %H:%M', '2008 年 2 � 24 日 00:52', 'Asia/Tokyo', 'ja'],
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
     */
    #[DataProvider('providerTimespanFormat')]
    public function testTimespanFormat(int $a, string $e): void
    {
        $GLOBALS['timespanfmt'] = '%s days, %s hours, %s minutes and %s seconds';
        $tmpTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/London');

        self::assertSame(
            $e,
            Util::timespanFormat($a),
        );

        date_default_timezone_set($tmpTimezone);
    }

    /**
     * data provider for localised timestamp test
     *
     * @return mixed[]
     */
    public static function providerTimespanFormat(): array
    {
        return [
            [1258, '0 days, 0 hours, 20 minutes and 58 seconds'],
            [821958, '9 days, 12 hours, 19 minutes and 18 seconds'],
        ];
    }

    /**
     * test for generating string contains printable bit value of selected data
     *
     * @param int    $a Value
     * @param int    $b Length
     * @param string $e Expected output
     */
    #[DataProvider('providerPrintableBitValue')]
    public function testPrintableBitValue(int $a, int $b, string $e): void
    {
        self::assertSame(
            $e,
            Util::printableBitValue($a, $b),
        );
    }

    /**
     * data provider for printable bit value test
     *
     * @return mixed[]
     */
    public static function providerPrintableBitValue(): array
    {
        return [
            [20131009, 64, '0000000000000000000000000000000000000001001100110010110011000001'],
            [5, 32, '00000000000000000000000000000101'],
        ];
    }

    /**
     * PhpMyAdmin\Util::unQuote test
     *
     * @param string $param    String
     * @param string $expected Expected output
     */
    #[DataProvider('providerUnQuote')]
    public function testUnQuote(string $param, string $expected): void
    {
        self::assertSame(
            $expected,
            Util::unQuote($param),
        );
    }

    /**
     * data provider for PhpMyAdmin\Util::unQuote test
     *
     * @return mixed[]
     */
    public static function providerUnQuote(): array
    {
        return [['"test\'"', "test'"], ["'test''", "test'"], ["`test'`", "test'"], ["'test'test", "'test'test"]];
    }

    /**
     * PhpMyAdmin\Util::unQuote test with chosen quote
     *
     * @param string $param    String
     * @param string $expected Expected output
     */
    #[DataProvider('providerUnQuoteSelectedChar')]
    public function testUnQuoteSelectedChar(string $param, string $expected): void
    {
        self::assertSame(
            $expected,
            Util::unQuote($param, '"'),
        );
    }

    /**
     * data provider for PhpMyAdmin\Util::unQuote test with chosen quote
     *
     * @return mixed[]
     */
    public static function providerUnQuoteSelectedChar(): array
    {
        return [['"test\'"', "test'"], ["'test''", "'test''"], ["`test'`", "`test'`"], ["'test'test", "'test'test"]];
    }

    #[DataProvider('providerForTestBackquote')]
    public function testBackquote(string $entry, string $expectedNoneOutput, string $expectedMssqlOutput): void
    {
        self::assertSame($expectedNoneOutput, Util::backquote($entry));
        self::assertSame($entry, Util::backquoteCompat($entry, 'NONE', false));
        self::assertSame($entry, Util::backquoteCompat($entry, 'MSSQL', false));
        self::assertSame($expectedNoneOutput, Util::backquoteCompat($entry, 'NONE'));
        self::assertSame($expectedMssqlOutput, Util::backquoteCompat($entry, 'MSSQL'));
    }

    /** @return array<int|string, string|null>[] */
    public static function providerForTestBackquote(): array
    {
        return [
            ['0', '`0`', '"0"'],
            ['test', '`test`', '"test"'],
            ['te`st', '`te``st`', '"te`st"'],
            ['te"st', '`te"st`', '"te\"st"'],
            ['', '', ''],
            ['*', '*', '*'],
        ];
    }

    public function testBackquoteCompatWithReservedKeywords(): void
    {
        Context::load();
        foreach (Context::$keywords as $keyword => $type) {
            $expected = $keyword;
            if (($type & Token::FLAG_KEYWORD_RESERVED) !== 0) {
                $expected = '`' . $keyword . '`';
            }

            self::assertSame($expected, Util::backquoteCompat($keyword, 'NONE', false));
        }
    }

    /**
     * test of generating user dir, globals are defined
     *
     * @param string $a String
     * @param string $e Expected output
     */
    #[DataProvider('providerUserDir')]
    public function testUserDir(string $a, string $e): void
    {
        Config::getInstance()->selectedServer['user'] = 'root';

        self::assertSame($e, Util::userDir($a));
    }

    /**
     * data provider for PhpMyAdmin\Util::userDir test
     *
     * @return mixed[]
     */
    public static function providerUserDir(): array
    {
        return [['/var/pma_tmp/%u/', '/var/pma_tmp/root/'], ['/home/%u/pma', '/home/root/pma/']];
    }

    /**
     * duplicate first newline test
     *
     * @param string $a String
     * @param string $e Expected output
     */
    #[DataProvider('providerDuplicateFirstNewline')]
    public function testDuplicateFirstNewline(string $a, string $e): void
    {
        self::assertSame(
            $e,
            Util::duplicateFirstNewline($a),
        );
    }

    /**
     * data provider for duplicate first newline test
     *
     * @return mixed[]
     */
    public static function providerDuplicateFirstNewline(): array
    {
        return [['test', 'test'], ["\r\ntest", "\n\r\ntest"], ["\ntest", "\ntest"], ["\n\r\ntest", "\n\r\ntest"]];
    }

    public function testUnsupportedDatatypes(): void
    {
        $noSupportTypes = [];
        self::assertSame(
            $noSupportTypes,
            Util::unsupportedDatatypes(),
        );
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
     */
    #[DataProvider('providerIsInteger')]
    public function testIsInteger(bool $expected, mixed $input): void
    {
        $isInteger = Util::isInteger($input);
        self::assertSame($expected, $isInteger);
    }

    /**
     * Data provider for Util::isInteger test
     *
     * @return mixed[]
     */
    public static function providerIsInteger(): array
    {
        return [[true, 1000], [true, '1000'], [false, 1000.1], [false, '1000.1'], [false, 'input']];
    }

    /**
     * Test for Util::getProtoFromForwardedHeader
     *
     * @param string $header The http Forwarded header
     * @param string $proto  The protocol http/https
     */
    #[DataProvider('providerForwardedHeaders')]
    public function testGetProtoFromForwardedHeader(string $header, string $proto): void
    {
        $protocolDetected = Util::getProtoFromForwardedHeader($header);
        self::assertSame($proto, $protocolDetected);
    }

    /**
     * Data provider for Util::getProtoFromForwardedHeader test
     *
     * @return mixed[]
     *
     * @source https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Forwarded MDN docs
     * @source https://www.nginx.com/resources/wiki/start/topics/examples/forwarded/ Nginx docs
     */
    public static function providerForwardedHeaders(): array
    {
        return [
            ['', ''],
            ['=', ''],
            ['https', ''],
            ['https', ''],
            ['=https', ''],
            ['=http', ''],
            ['For="[2001:db8:cafe::17]:4711"', ''],
            ['for=192.0.2.60;proto=http;by=203.0.113.43', 'http'],
            ['for=192.0.2.43, for=198.51.100.17', ''],
            ['for=123.34.567.89', ''],
            ['for=192.0.2.43, for="[2001:db8:cafe::17]"', ''],
            ['for=12.34.56.78;host=example.com;proto=https, for=23.45.67.89', 'https'],
            ['for=12.34.56.78, for=23.45.67.89;secret=egah2CGj55fSJFs, for=10.1.2.3', ''],
            ['for=injected;by="', ''],
            ['for=injected;by=", for=real', ''],
            ['for=192.0.2.60;proto=http;by=203.0.113.43', 'http'],
            ['for=192.0.2.60;proto=htTp;by=203.0.113.43', 'http'],
            ['for=192.0.2.60;proto=HTTP;by=203.0.113.43', 'http'],
            ['for=192.0.2.60;proto= http;by=203.0.113.43', 'http'],
            ['for=12.34.45.67;secret="special;proto=abc;test=1";proto=http,for=23.45.67.89', 'http'],
            ['for=12.34.45.67;secret="special;proto=abc;test=1";proto=418,for=23.45.67.89', ''],
            /*[ // this test case is very special and would need a different implementation
                'for=12.34.45.67;secret="special;proto=http;test=1";proto=https,for=23.45.67.89',
                'https'
            ]*/
        ];
    }

    public function testCurrentUserHasPrivilegeSkipGrantTables(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT CURRENT_USER();', []);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        self::assertTrue(Util::currentUserHasPrivilege('EVENT'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testCurrentUserHasUserPrivilege(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $globalPrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'
SQL;
        // phpcs:enable
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['groot_%@%']]);
        $dbiDummy->addResult($globalPrivilegeQuery, [['EVENT']]);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        self::assertTrue(Util::currentUserHasPrivilege('EVENT'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testCurrentUserHasNotUserPrivilege(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $globalPrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'
SQL;
        // phpcs:enable
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['groot_%@%']]);
        $dbiDummy->addResult($globalPrivilegeQuery, []);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        self::assertFalse(Util::currentUserHasPrivilege('EVENT'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testCurrentUserHasNotUserPrivilegeButDbPrivilege(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $globalPrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'
SQL;
        $databasePrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT' AND 'my_data_base' LIKE `TABLE_SCHEMA`
SQL;
        // phpcs:enable
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['groot_%@%']]);
        $dbiDummy->addResult($globalPrivilegeQuery, []);
        $dbiDummy->addResult($databasePrivilegeQuery, [['EVENT']]);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        self::assertTrue(Util::currentUserHasPrivilege('EVENT', 'my_data_base'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilege(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $globalPrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'
SQL;
        $databasePrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT' AND 'my_data_base' LIKE `TABLE_SCHEMA`
SQL;
        // phpcs:enable
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['groot_%@%']]);
        $dbiDummy->addResult($globalPrivilegeQuery, []);
        $dbiDummy->addResult($databasePrivilegeQuery, []);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        self::assertFalse(Util::currentUserHasPrivilege('EVENT', 'my_data_base'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilegeButTablePrivilege(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $globalPrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'
SQL;
        $databasePrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT' AND 'my_data_base' LIKE `TABLE_SCHEMA`
SQL;
        $tablePrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT' AND 'my_data_base' LIKE `TABLE_SCHEMA` AND TABLE_NAME='my_data_table'
SQL;
        // phpcs:enable
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['groot_%@%']]);
        $dbiDummy->addResult($globalPrivilegeQuery, []);
        $dbiDummy->addResult($databasePrivilegeQuery, []);
        $dbiDummy->addResult($tablePrivilegeQuery, [['EVENT']]);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        self::assertTrue(Util::currentUserHasPrivilege('EVENT', 'my_data_base', 'my_data_table'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    public function testCurrentUserHasNotUserPrivilegeAndNotDbPrivilegeAndNotTablePrivilege(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $globalPrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT'
SQL;
        $databasePrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`SCHEMA_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT' AND 'my_data_base' LIKE `TABLE_SCHEMA`
SQL;
        $tablePrivilegeQuery = <<<'SQL'
SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`TABLE_PRIVILEGES` WHERE GRANTEE='''groot_%''@''%''' AND PRIVILEGE_TYPE='EVENT' AND 'my_data_base' LIKE `TABLE_SCHEMA` AND TABLE_NAME='my_data_table'
SQL;
        // phpcs:enable
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['groot_%@%']]);
        $dbiDummy->addResult($globalPrivilegeQuery, []);
        $dbiDummy->addResult($databasePrivilegeQuery, []);
        $dbiDummy->addResult($tablePrivilegeQuery, []);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        self::assertFalse(Util::currentUserHasPrivilege('EVENT', 'my_data_base', 'my_data_table'));
        $dbiDummy->assertAllQueriesConsumed();
    }

    /** @return mixed[][] */
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
            ['structure', 'table', 'index.php?route=/table/structure&lang=en'],
            [
                'tbl_sql.php', // Support the legacy value
                'table',
                'index.php?route=/table/sql&lang=en',
            ],
            ['sql', 'table', 'index.php?route=/table/sql&lang=en'],
            [
                'tbl_select.php', // Support the legacy value
                'table',
                'index.php?route=/table/search&lang=en',
            ],
            ['search', 'table', 'index.php?route=/table/search&lang=en'],
            [
                'tbl_change.php', // Support the legacy value
                'table',
                'index.php?route=/table/change&lang=en',
            ],
            ['insert', 'table', 'index.php?route=/table/change&lang=en'],
            [
                'sql.php', // Support the legacy value
                'table',
                'index.php?route=/sql&lang=en',
            ],
            ['browse', 'table', 'index.php?route=/sql&lang=en'],
            [
                'db_structure.php', // Support the legacy value
                'database',
                'index.php?route=/database/structure&lang=en',
            ],
            ['structure', 'database', 'index.php?route=/database/structure&lang=en'],
            [
                'db_sql.php', // Support the legacy value
                'database',
                'index.php?route=/database/sql&lang=en',
            ],
            ['sql', 'database', 'index.php?route=/database/sql&lang=en'],
            [
                'db_search.php', // Support the legacy value
                'database',
                'index.php?route=/database/search&lang=en',
            ],
            ['search', 'database', 'index.php?route=/database/search&lang=en'],
            [
                'db_operations.php', // Support the legacy value
                'database',
                'index.php?route=/database/operations&lang=en',
            ],
            ['operations', 'database', 'index.php?route=/database/operations&lang=en'],
            [
                'index.php', // Support the legacy value
                'server',
                'index.php?route=/&lang=en',
            ],
            ['welcome', 'server', 'index.php?route=/&lang=en'],
            [
                'server_databases.php', // Support the legacy value
                'server',
                'index.php?route=/server/databases&lang=en',
            ],
            ['databases', 'server', 'index.php?route=/server/databases&lang=en'],
            [
                'server_status.php', // Support the legacy value
                'server',
                'index.php?route=/server/status&lang=en',
            ],
            ['status', 'server', 'index.php?route=/server/status&lang=en'],
            [
                'server_variables.php', // Support the legacy value
                'server',
                'index.php?route=/server/variables&lang=en',
            ],
            ['variables', 'server', 'index.php?route=/server/variables&lang=en'],
            [
                'server_privileges.php', // Support the legacy value
                'server',
                'index.php?route=/server/privileges&lang=en',
            ],
            ['privileges', 'server', 'index.php?route=/server/privileges&lang=en'],
        ];
    }

    #[DataProvider('dataProviderScriptNames')]
    public function testGetScriptNameForOption(string $target, string $location, string $finalLink): void
    {
        $GLOBALS['lang'] = 'en';
        self::assertSame(
            $finalLink,
            Util::getScriptNameForOption($target, $location),
        );
    }

    public function testShowIcons(): void
    {
        $config = Config::getInstance();
        $config->settings['ActionLinksMode'] = 'icons';
        self::assertTrue(Util::showIcons('ActionLinksMode'));
        $config->settings['ActionLinksMode'] = 'both';
        self::assertTrue(Util::showIcons('ActionLinksMode'));
        $config->settings['ActionLinksMode'] = 'text';
        self::assertFalse(Util::showIcons('ActionLinksMode'));
    }

    public function testShowText(): void
    {
        $config = Config::getInstance();
        $config->settings['ActionLinksMode'] = 'text';
        self::assertTrue(Util::showText('ActionLinksMode'));
        $config->settings['ActionLinksMode'] = 'both';
        self::assertTrue(Util::showText('ActionLinksMode'));
        $config->settings['ActionLinksMode'] = 'icons';
        self::assertFalse(Util::showText('ActionLinksMode'));
    }

    #[DataProvider('providerForTestGetMySQLDocuURL')]
    public function testGetMySQLDocuURL(string $link, string $anchor, string $version, string $expected): void
    {
        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $dbi->setVersion(['@@version' => $version, '@@version_comment' => 'MySQL Community Server (GPL)']);
        self::assertSame($expected, Util::getMySQLDocuURL($link, $anchor));
    }

    /** @return array<int, array{string, string, string, string}> */
    public static function providerForTestGetMySQLDocuURL(): array
    {
        return [
            [
                'ALTER_TABLE',
                'alter-table-index',
                '8.0.0',
                'index.php?route=/url&url='
                . 'https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F8.0%2Fen%2Falter-table.html%23alter-table-index',
            ],
            [
                'ALTER_TABLE',
                'alter-table-index',
                '5.7.0',
                'index.php?route=/url&url='
                . 'https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Falter-table.html%23alter-table-index',
            ],
            [
                '',
                'alter-table-index',
                '5.6.0',
                'index.php?route=/url&url='
                . 'https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.6%2Fen%2Findex.html%23alter-table-index',
            ],
            [
                'ALTER_TABLE',
                '',
                '5.5.0',
                'index.php?route=/url&url='
                . 'https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Falter-table.html',
            ],
            [
                '',
                '',
                '5.7.0',
                'index.php?route=/url&url='
                . 'https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.7%2Fen%2Findex.html',
            ],
        ];
    }

    public function testGetDocuURL(): void
    {
        $dbi = $this->createDatabaseInterface();
        $dbi->setVersion(['@@version' => '5.5.0']);
        DatabaseInterface::$instance = $dbi;
        self::assertSame(
            'index.php?route=/url&url=https%3A%2F%2Fmariadb.com%2Fkb%2Fen%2Fdocumentation%2F',
            Util::getDocuURL(true),
        );
        self::assertSame(
            'index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Findex.html',
            Util::getDocuURL(false),
        );
        self::assertSame(
            'index.php?route=/url&url=https%3A%2F%2Fdev.mysql.com%2Fdoc%2Frefman%2F5.5%2Fen%2Findex.html',
            Util::getDocuURL(),
        );
    }

    public function testSplitURLQuery(): void
    {
        $actual = Util::splitURLQuery('');
        self::assertSame([], $actual);
        $actual = Util::splitURLQuery('index.php');
        self::assertSame([], $actual);
        $actual = Util::splitURLQuery('index.php?route=/table/structure&db=sakila&table=address');
        self::assertSame(['route=/table/structure', 'db=sakila', 'table=address'], $actual);
    }

    public function testGetDbInfo(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SHOW TABLES FROM `test_db`;', [['test_table']], ['Tables_in_test_db']);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        $tableInfo = [
            'Name' => 'test_table',
            'Engine' => 'InnoDB',
            'Version' => '10',
            'Row_format' => 'Dynamic',
            'Rows' => '3',
            'Avg_row_length' => '5461',
            'Data_length' => '16384',
            'Max_data_length' => '0',
            'Index_length' => '0',
            'Data_free' => '0',
            'Auto_increment' => '4',
            'Create_time' => '2011-12-13 14:15:16',
            'Update_time' => null,
            'Check_time' => null,
            'Collation' => 'utf8mb4_general_ci',
            'Checksum' => null,
            'Create_options' => '',
            'Comment' => '',
            'Max_index_length' => '0',
            'Temporary' => 'N',
            'Type' => 'InnoDB',
            'TABLE_SCHEMA' => 'test_db',
            'TABLE_NAME' => 'test_table',
            'ENGINE' => 'InnoDB',
            'VERSION' => '10',
            'ROW_FORMAT' => 'Dynamic',
            'TABLE_ROWS' => '3',
            'AVG_ROW_LENGTH' => '5461',
            'DATA_LENGTH' => '16384',
            'MAX_DATA_LENGTH' => '0',
            'INDEX_LENGTH' => '0',
            'DATA_FREE' => '0',
            'AUTO_INCREMENT' => '4',
            'CREATE_TIME' => '2011-12-13 14:15:16',
            'UPDATE_TIME' => null,
            'CHECK_TIME' => null,
            'TABLE_COLLATION' => 'utf8mb4_general_ci',
            'CHECKSUM' => null,
            'CREATE_OPTIONS' => '',
            'TABLE_COMMENT' => '',
            'TABLE_TYPE' => 'BASE TABLE',
        ];
        $expected = [['test_table' => $tableInfo], 1];
        $actual = Util::getDbInfo(self::createStub(ServerRequest::class), 'test_db');
        self::assertSame($expected, $actual);
    }

    public function testGetTableListPosition(): void
    {
        // Default 0
        $actual = Util::getTableListPosition(self::createStub(ServerRequest::class), 'test_db');
        self::assertSame(0, $actual);

        // From POST
        $requestStub = self::createStub(ServerRequestInterface::class);
        $requestStub->method('getQueryParams')->willReturn([]);
        $requestStub->method('getParsedBody')->willReturn(['pos' => '250']);
        $request = new ServerRequest($requestStub);
        $actual = Util::getTableListPosition($request, 'test_db');
        self::assertSame(250, $actual);

        // From SESSION
        $actual = Util::getTableListPosition(self::createStub(ServerRequest::class), 'test_db');
        self::assertSame(250, $actual);
    }

    /**
     * Tests for Util::testIsUUIDSupported() method.
     *
     * @param bool $isMariaDB True if mariadb
     * @param int  $version   Database version as integer
     * @param bool $expected  Expected Result
     */
    #[DataProvider('provideForTestIsUUIDSupported')]
    public function testIsUUIDSupported(bool $isMariaDB, int $version, bool $expected): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects(self::any())
            ->method('isMariaDB')
            ->willReturn($isMariaDB);

        $dbi->expects(self::any())
            ->method('getVersion')
            ->willReturn($version);

        DatabaseInterface::$instance = $dbi;
        self::assertSame(Util::isUUIDSupported(), $expected);
        DatabaseInterface::$instance = null;
    }

    /**
     * Data provider for isUUIDSupported() tests.
     *
     * @return mixed[]
     * @psalm-return array<int, array{bool, int, bool}>
     */
    public static function provideForTestIsUUIDSupported(): array
    {
        return [[false, 60100, false], [false, 100700, false], [true, 60100, false], [true, 100700, true]];
    }

    #[DataProvider('providerForTestGetLowerCaseNames')]
    public function testGetCollateForIS(string $lowerCaseTableNames, string $expected): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SELECT @@lower_case_table_names', [[$lowerCaseTableNames]], ['@@lower_case_table_names']);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);
        self::assertSame($expected, Util::getCollateForIS());
        $dbiDummy->assertAllQueriesConsumed();
    }

    /** @return iterable<string, array{string, string}> */
    public static function providerForTestGetLowerCaseNames(): iterable
    {
        yield 'lower_case_table_names=0' => ['0', 'COLLATE utf8_bin'];
        yield 'lower_case_table_names=1' => ['1', ''];
        yield 'lower_case_table_names=2' => ['2', 'COLLATE utf8_general_ci'];
    }

    public function testGetSupportedDatatypes(): void
    {
        $dbiDummy = $this->createDbiDummy();
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);
        $expected = [
            'INT',
            'VARCHAR',
            'TEXT',
            'DATE',
            'TINYINT',
            'SMALLINT',
            'MEDIUMINT',
            'INT',
            'BIGINT',
            'DECIMAL',
            'FLOAT',
            'DOUBLE',
            'REAL',
            'BIT',
            'BOOLEAN',
            'SERIAL',
            'DATE',
            'DATETIME',
            'TIMESTAMP',
            'TIME',
            'YEAR',
            'CHAR',
            'VARCHAR',
            'TINYTEXT',
            'TEXT',
            'MEDIUMTEXT',
            'LONGTEXT',
            'BINARY',
            'VARBINARY',
            'TINYBLOB',
            'BLOB',
            'MEDIUMBLOB',
            'LONGBLOB',
            'ENUM',
            'SET',
            'GEOMETRY',
            'POINT',
            'LINESTRING',
            'POLYGON',
            'MULTIPOINT',
            'MULTILINESTRING',
            'MULTIPOLYGON',
            'GEOMETRYCOLLECTION',
            'JSON',
        ];
        self::assertSame($expected, Util::getSupportedDatatypes());
    }

    /**
     * @param mixed[]        $array
     * @param (string|int)[] $path
     */
    #[DataProvider('providerForTestGetValueByKey')]
    public function testGetValueByKey(mixed $expected, array $array, array $path, mixed $default = null): void
    {
        self::assertSame($expected, Util::getValueByKey($array, $path, $default));
    }

    /** @return iterable<string, array{mixed, mixed[], (string|int)[], mixed}> */
    public static function providerForTestGetValueByKey(): iterable
    {
        yield 'array_has_all_keys' => [
            'foo',
            ['key1' => ['key2' => 'foo']],
            ['key1', 'key2'],
            null,
        ];

        yield 'key_not_found' => [
            'def',
            ['key1' => ['key2' => 'foo']],
            ['key1', 'key3'],
            'def',
        ];

        yield 'subarray_requested' => [
            ['key2' => 'foo'],
            ['key1' => ['key2' => 'foo']],
            ['key1'],
            'def',
        ];

        yield 'no_keys_specified' => [
            ['key1' => ['key2' => 'foo']],
            ['key1' => ['key2' => 'foo']],
            [],
            'def',
        ];
    }
}
