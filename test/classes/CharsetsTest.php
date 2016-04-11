<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */

/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */
class CharsetsTest extends PHPUnit_Framework_TestCase
{

    /**
     * Test case for PMA\libraries\Charsets::getCollationDescr()
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
            PMA\libraries\Charsets::getCollationDescr($collation)
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
            array('big5_chinese', 'Traditional Chinese'),
            array('foo_ci_bar', 'unknown, case-insensitive collation'),
            array('foo_cs_bar', 'unknown, case-sensitive collation'),
            array('foo_croatian_bar', 'Croatian'),
            array('foo_czech_bar', 'Czech'),
            array('foo_danish_bar', 'Danish'),
            array('foo_english_bar', 'English'),
            array('foo_esperanto_bar', 'Esperanto'),
            array('foo_estonian_bar', 'Estonian'),
            array('foo_german1_bar', 'German (dictionary)'),
            array('foo_german2_bar', 'German (phone book)'),
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
            array('foo_spanish_bar', 'Spanish'),
            array('foo_spanish2_bar', 'Traditional Spanish'),
            array('foo_swedish_bar', 'Swedish'),
            array('foo_thai_bar', 'Thai'),
            array('foo_turkish_bar', 'Turkish'),
            array('foo_ukrainian_bar', 'Ukrainian'),
            array('foo_unicode_bar', 'Unicode (multilingual)'),
            array('ucs2', 'Unicode (multilingual)'),
            array('utf8', 'Unicode (multilingual)'),
            array('ascii', 'West European (multilingual)'),
            array('cp850', 'West European (multilingual)'),
            array('dec8', 'West European (multilingual)'),
            array('hp8', 'West European (multilingual)'),
            array('latin1', 'West European (multilingual)'),
            array('cp1250', 'Central European (multilingual)'),
            array('cp852', 'Central European (multilingual)'),
            array('latin2', 'Central European (multilingual)'),
            array('macce', 'Central European (multilingual)'),
            array('cp866', 'Russian'),
            array('koi8r', 'Russian'),
            array('gb2312', 'Simplified Chinese'),
            array('gbk', 'Simplified Chinese'),
            array('sjis', 'Japanese'),
            array('ujis', 'Japanese'),
            array('cp932', 'Japanese'),
            array('eucjpms', 'Japanese'),
            array('cp1257', 'Baltic (multilingual)'),
            array('latin7', 'Baltic (multilingual)'),
            array('armscii8', 'Armenian'),
            array('armscii', 'Armenian'),
            array('big5', 'Traditional Chinese'),
            array('cp1251', 'Cyrillic (multilingual)'),
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
            array('foobar', 'unknown'),
            array('foo_test_bar', 'unknown'),
            array('foo_bin_bar', 'unknown, binary collation')
        );
    }

    /**
     * Test for PMA\libraries\Charsets::getCollationDropdownBox
     *
     * @return void
     * @test
     */
    public function testGetCollationDropdownBox()
    {
        $result = PMA\libraries\Charsets::getCollationDropdownBox();

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
     * Test for PMA\libraries\Charsets::getCharsetDropdownBox
     *
     * @return void
     * @test
     */
    public function testGetCharsetDropdownBox()
    {
        $result = PMA\libraries\Charsets::getCharsetDropdownBox(
            null, "test_id", "latin1", false, true
        );
        $this->assertContains('name="character_set"', $result);
        $this->assertNotContains('Charset</option>', $result);
        $this->assertContains('class="autosubmit"', $result);
        $this->assertContains('id="test_id"', $result);
        $this->assertContains('selected="selected">latin1', $result);
    }
}

