<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */
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
    public function setUp()
    {
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
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
    public function testGetCollationDescr($collation, $desc)
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
        return array(
            array('binary', 'Binary'),
            array('foo_bulgarian_bar', 'Bulgarian'),
            array('gb2312_chinese', 'Simplified Chinese'),
            array('gbk_chinese', 'Simplified Chinese'),
            array('big5_chinese_ci', 'Traditional Chinese, case-insensitive'),
            array('big5_chinese', 'Traditional Chinese'),
            array('foo_ci_bar', 'Unknown, case-insensitive'),
            array('foo_croatian_bar', 'Croatian'),
            array('foo_czech_bar', 'Czech'),
            array('foo_danish_bar', 'Danish'),
            array('foo_english_bar', 'English'),
            array('foo_esperanto_bar', 'Esperanto'),
            array('foo_estonian_bar', 'Estonian'),
            array('foo_german1_bar', 'German (dictionary order)'),
            array('foo_german2_bar', 'German (phone book order)'),
            array('foo_hungarian_bar', 'Hungarian'),
            array('foo_icelandic_bar', 'Icelandic'),
            array('foo_japanese_bar', 'Japanese'),
            array('foo_latvian_bar', 'Latvian'),
            array('foo_lithuanian_bar', 'Lithuanian'),
            array('foo_korean_bar', 'Korean'),
            array('foo_persian_bar', 'Persian'),
            array('foo_polish_bar', 'Polish'),
            array('foo_roman_bar', 'West European'),
            array('foo_romanian_bar', 'Romanian'),
            array('foo_slovak_bar', 'Slovak'),
            array('foo_slovenian_bar', 'Slovenian'),
            array('foo_spanish_bar', 'Spanish (modern)'),
            array('foo_spanish2_bar', 'Spanish (traditional)'),
            array('foo_swedish_bar', 'Swedish'),
            array('foo_thai_bar', 'Thai'),
            array('foo_turkish_bar', 'Turkish'),
            array('foo_ukrainian_bar', 'Ukrainian'),
            array('foo_unicode_bar', 'Unicode'),
            array('ucs2', 'Unicode'),
            array('utf8', 'Unicode'),
            array('ascii', 'West European'),
            array('cp850', 'West European'),
            array('dec8', 'West European'),
            array('hp8', 'West European'),
            array('latin1', 'West European'),
            array('cp1250', 'Central European'),
            array('cp852', 'Central European'),
            array('latin2', 'Central European'),
            array('macce', 'Central European'),
            array('cp866', 'Russian'),
            array('koi8r', 'Russian'),
            array('gb2312', 'Simplified Chinese'),
            array('gbk', 'Simplified Chinese'),
            array('sjis', 'Japanese'),
            array('ujis', 'Japanese'),
            array('cp932', 'Japanese'),
            array('eucjpms', 'Japanese'),
            array('cp1257', 'Baltic'),
            array('latin7', 'Baltic'),
            array('armscii8', 'Armenian'),
            array('armscii', 'Armenian'),
            array('big5', 'Traditional Chinese'),
            array('cp1251', 'Cyrillic'),
            array('cp1256', 'Arabic'),
            array('euckr', 'Korean'),
            array('hebrew', 'Hebrew'),
            array('geostd8', 'Georgian'),
            array('greek', 'Greek'),
            array('keybcs2', 'Czech-Slovak'),
            array('koi8u', 'Ukrainian'),
            array('latin5', 'Turkish'),
            array('swe7', 'Swedish'),
            array('tis620', 'Thai'),
            array('foobar', 'Unknown'),
            array('foo_test_bar', 'Unknown'),
            array('foo_bin_bar', 'Unknown, binary'),
            array('utf8mb4_0900_ai_ci', 'Unicode (UCA 9.0.0), accent-insensitive, case-insensitive'),
            array('utf8mb4_unicode_520_ci', 'Unicode (UCA 5.2.0), case-insensitive'),
            array('utf8mb4_unicode_ci', 'Unicode (UCA 4.0.0), case-insensitive'),
            array('utf8mb4_tr_0900_ai_ci', 'Turkish (UCA 9.0.0), accent-insensitive, case-insensitive'),
            array('utf8mb4_turkish_ci', 'Turkish (UCA 4.0.0), case-insensitive'),
            array('utf32_thai_520_w2', 'Thai (UCA 5.2.0), multi-level'),
            array('utf8mb4_czech_ci', 'Czech (UCA 4.0.0), case-insensitive'),
            array('cp1250_czech_cs', 'Czech, case-sensitive'),
            array('latin1_general_ci', 'West European, case-insensitive'),
            array('utf8mb4_bin', 'Unicode (UCA 4.0.0), binary'),
            array('utf8mb4_croatian_mysql561_ci', 'Croatian (MySQL 5.6.1), case-insensitive'),
            array('ucs2_general_mysql500_ci', 'Unicode (MySQL 5.0.0), case-insensitive'),
            array('utf32_general_ci', 'Unicode, case-insensitive'),
            array('utf8mb4_es_trad_0900_ai_ci', 'Spanish (traditional) (UCA 9.0.0), accent-insensitive, case-insensitive'),
            array('utf8mb4_es_0900_ai_ci', 'Spanish (modern) (UCA 9.0.0), accent-insensitive, case-insensitive'),
            array('utf8mb4_de_pb_0900_ai_ci', 'German (phone book order) (UCA 9.0.0), accent-insensitive, case-insensitive'),
            array('utf8mb4_de_0900_ai_ci', 'German (dictionary order) (UCA 9.0.0), accent-insensitive, case-insensitive'),
        );
    }

    /**
     * Test for getCollationDropdownBox
     *
     * @return void
     * @test
     */
    public function testGetCollationDropdownBox()
    {
        $result = Charsets::getCollationDropdownBox(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS']
        );

        $this->assertContains('name="collation"', $result);
        $this->assertNotContains('id="', $result);
        $this->assertNotContains('class="autosubmit"', $result);
        $this->assertContains('<option value="">Collation', $result);
        $this->assertContains('<option value=""></option>', $result);
        $this->assertContains('<optgroup label="latin1', $result);
        $this->assertNotContains('<optgroup label="latin2', $result);
        $this->assertContains('title="cp1252', $result);
        $this->assertNotContains('value="latin2_general1_ci"', $result);
        $this->assertContains('title="Swedish', $result);
    }

    /**
     * Test for getCharsetDropdownBox
     *
     * @return void
     * @test
     */
    public function testGetCharsetDropdownBox()
    {
        $result = Charsets::getCharsetDropdownBox(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            null,
            "test_id",
            "latin1",
            false,
            true
        );
        $this->assertContains('name="character_set"', $result);
        $this->assertNotContains('Charset</option>', $result);
        $this->assertContains('class="autosubmit"', $result);
        $this->assertContains('id="test_id"', $result);
        $this->assertContains('selected="selected">latin1', $result);
    }
}
