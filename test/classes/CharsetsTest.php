<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Charsets;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */
class CharsetsTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    /**
     * @return void
     */
    public function testFindCollationByName(): void
    {
        $this->assertNull(Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            ''
        ));

        $this->assertNull(Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            'invalid'
        ));

        $actual = Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            'utf8_general_ci'
        );

        $this->assertInstanceOf(Charsets\Collation::class, $actual);

        $this->assertSame('utf8_general_ci', $actual->getName());
    }

    /**
     * Test case for getCollationDescr()
     *
     * @param string $collation Collation for which description is reqd
     * @param string $desc      Expected Description
     *
     * @return void
     * @test
     * @dataProvider collationDescr
     */
    public function testGetCollationDescr($collation, $desc): void
    {
        $this->assertEquals(
            $desc,
            Charsets::getCollationDescr($collation)
        );
    }

    /**
     * Data Provider for testGetCollationDescr()
     *
     * @return array Test data for testGetCollationDescr()
     */
    public function collationDescr()
    {
        return [
            [
                'binary',
                'Binary',
            ],
            [
                'foo_bulgarian_bar',
                'Bulgarian',
            ],
            [
                'gb2312_chinese',
                'Simplified Chinese',
            ],
            [
                'gbk_chinese',
                'Simplified Chinese',
            ],
            [
                'big5_chinese_ci',
                'Traditional Chinese, case-insensitive',
            ],
            [
                'big5_chinese',
                'Traditional Chinese',
            ],
            [
                'foo_ci_bar',
                'Unknown, case-insensitive',
            ],
            [
                'foo_croatian_bar',
                'Croatian',
            ],
            [
                'foo_czech_bar',
                'Czech',
            ],
            [
                'foo_danish_bar',
                'Danish',
            ],
            [
                'foo_english_bar',
                'English',
            ],
            [
                'foo_esperanto_bar',
                'Esperanto',
            ],
            [
                'foo_estonian_bar',
                'Estonian',
            ],
            [
                'foo_german1_bar',
                'German (dictionary order)',
            ],
            [
                'foo_german2_bar',
                'German (phone book order)',
            ],
            [
                'foo_hungarian_bar',
                'Hungarian',
            ],
            [
                'foo_icelandic_bar',
                'Icelandic',
            ],
            [
                'foo_japanese_bar',
                'Japanese',
            ],
            [
                'foo_latvian_bar',
                'Latvian',
            ],
            [
                'foo_lithuanian_bar',
                'Lithuanian',
            ],
            [
                'foo_korean_bar',
                'Korean',
            ],
            [
                'foo_persian_bar',
                'Persian',
            ],
            [
                'foo_polish_bar',
                'Polish',
            ],
            [
                'foo_roman_bar',
                'West European',
            ],
            [
                'foo_romanian_bar',
                'Romanian',
            ],
            [
                'foo_slovak_bar',
                'Slovak',
            ],
            [
                'foo_slovenian_bar',
                'Slovenian',
            ],
            [
                'foo_spanish_bar',
                'Spanish (modern)',
            ],
            [
                'foo_spanish2_bar',
                'Spanish (traditional)',
            ],
            [
                'foo_swedish_bar',
                'Swedish',
            ],
            [
                'foo_thai_bar',
                'Thai',
            ],
            [
                'foo_turkish_bar',
                'Turkish',
            ],
            [
                'foo_ukrainian_bar',
                'Ukrainian',
            ],
            [
                'foo_unicode_bar',
                'Unicode',
            ],
            [
                'ucs2',
                'Unicode',
            ],
            [
                'utf8',
                'Unicode',
            ],
            [
                'ascii',
                'West European',
            ],
            [
                'cp850',
                'West European',
            ],
            [
                'dec8',
                'West European',
            ],
            [
                'hp8',
                'West European',
            ],
            [
                'latin1',
                'West European',
            ],
            [
                'cp1250',
                'Central European',
            ],
            [
                'cp852',
                'Central European',
            ],
            [
                'latin2',
                'Central European',
            ],
            [
                'macce',
                'Central European',
            ],
            [
                'cp866',
                'Russian',
            ],
            [
                'koi8r',
                'Russian',
            ],
            [
                'gb2312',
                'Simplified Chinese',
            ],
            [
                'gbk',
                'Simplified Chinese',
            ],
            [
                'sjis',
                'Japanese',
            ],
            [
                'ujis',
                'Japanese',
            ],
            [
                'cp932',
                'Japanese',
            ],
            [
                'eucjpms',
                'Japanese',
            ],
            [
                'cp1257',
                'Baltic',
            ],
            [
                'latin7',
                'Baltic',
            ],
            [
                'armscii8',
                'Armenian',
            ],
            [
                'armscii',
                'Armenian',
            ],
            [
                'big5',
                'Traditional Chinese',
            ],
            [
                'cp1251',
                'Cyrillic',
            ],
            [
                'cp1256',
                'Arabic',
            ],
            [
                'euckr',
                'Korean',
            ],
            [
                'hebrew',
                'Hebrew',
            ],
            [
                'geostd8',
                'Georgian',
            ],
            [
                'greek',
                'Greek',
            ],
            [
                'keybcs2',
                'Czech-Slovak',
            ],
            [
                'koi8u',
                'Ukrainian',
            ],
            [
                'latin5',
                'Turkish',
            ],
            [
                'swe7',
                'Swedish',
            ],
            [
                'tis620',
                'Thai',
            ],
            [
                'foobar',
                'Unknown',
            ],
            [
                'foo_test_bar',
                'Unknown',
            ],
            [
                'foo_bin_bar',
                'Unknown, binary',
            ],
            [
                'utf8mb4_0900_ai_ci',
                'Unicode (UCA 9.0.0), accent-insensitive, case-insensitive',
            ],
            [
                'utf8mb4_unicode_520_ci',
                'Unicode (UCA 5.2.0), case-insensitive',
            ],
            [
                'utf8mb4_unicode_ci',
                'Unicode (UCA 4.0.0), case-insensitive',
            ],
            [
                'utf8mb4_tr_0900_ai_ci',
                'Turkish (UCA 9.0.0), accent-insensitive, case-insensitive',
            ],
            [
                'utf8mb4_turkish_ci',
                'Turkish (UCA 4.0.0), case-insensitive',
            ],
            [
                'utf32_thai_520_w2',
                'Thai (UCA 5.2.0), multi-level',
            ],
            [
                'utf8mb4_czech_ci',
                'Czech (UCA 4.0.0), case-insensitive',
            ],
            [
                'cp1250_czech_cs',
                'Czech, case-sensitive',
            ],
            [
                'latin1_general_ci',
                'West European, case-insensitive',
            ],
            [
                'utf8mb4_bin',
                'Unicode (UCA 4.0.0), binary',
            ],
            [
                'utf8mb4_croatian_mysql561_ci',
                'Croatian (MySQL 5.6.1), case-insensitive',
            ],
            [
                'ucs2_general_mysql500_ci',
                'Unicode (MySQL 5.0.0), case-insensitive',
            ],
            [
                'utf32_general_ci',
                'Unicode, case-insensitive',
            ],
            [
                'utf8mb4_es_trad_0900_ai_ci',
                'Spanish (traditional) (UCA 9.0.0), accent-insensitive, case-insensitive',
            ],
            [
                'utf8mb4_es_0900_ai_ci',
                'Spanish (modern) (UCA 9.0.0), accent-insensitive, case-insensitive',
            ],
            [
                'utf8mb4_de_pb_0900_ai_ci',
                'German (phone book order) (UCA 9.0.0), accent-insensitive, case-insensitive',
            ],
            [
                'utf8mb4_de_0900_ai_ci',
                'German (dictionary order) (UCA 9.0.0), accent-insensitive, case-insensitive',
            ],
        ];
    }
}
