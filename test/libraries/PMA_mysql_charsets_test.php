<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests for MySQL Charsets
 *
 * @package PhpMyAdmin-test
 */
class PMA_MySQL_Charsets_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_generateCharsetQueryPart
     *
     * @param bool   $drizzle   Value for PMA_DRIZZLE
     * @param string $collation Collation
     * @param string $expected  Expected Charset Query
     *
     * @return void
     * @test
     * @dataProvider charsetQueryData
     */
    public function testGenerateCharsetQueryPart(
        $drizzle, $collation, $expected
    ) {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped(
                'Cannot redefine constant - missing runkit extension'
            );
        }

        $restoreDrizzle = '';

        if (defined('PMA_DRIZZLE')) {
            $restoreDrizzle = PMA_DRIZZLE;
            runkit_constant_redefine('PMA_DRIZZLE', $drizzle);
        } else {
            $restoreDrizzle = 'PMA_TEST_CONSTANT_REMOVE';
            define('PMA_DRIZZLE', $drizzle);
        }

        $this->assertEquals(
            $expected,
            PMA_generateCharsetQueryPart($collation)
        );

        if ($restoreDrizzle === 'PMA_TEST_CONSTANT_REMOVE') {
            runkit_constant_remove('PMA_DRIZZLE');
        } else {
            runkit_constant_redefine('PMA_DRIZZLE', $restoreDrizzle);
        }
    }

    /**
     * Data Provider for testPMA_generateCharsetQueryPart
     *
     * @return array test data
     */
    public function charsetQueryData()
    {
        return array(
            array(false, "a_b_c_d", " CHARACTER SET a COLLATE a_b_c_d"),
            array(false, "a_", " CHARACTER SET a COLLATE a_"),
            array(false, "a", " CHARACTER SET a"),
            array(true, "a_b_c_d", " COLLATE a_b_c_d")
        );
    }


    /**
     * Test for PMA_getDbCollation
     *
     * @return void
     * @test
     */
    public function testGetDbCollation()
    {
        if (! PMA_HAS_RUNKIT) {
            $this->markTestSkipped(
                'Cannot redefine constant - missing runkit extension'
            );
        } else {
            $GLOBALS['server'] = 1;
            // test case for system schema
            $this->assertEquals(
                'utf8_general_ci',
                PMA_getDbCollation("information_schema")
            );

            $restoreDrizzle = '';

            // test case with no pma drizzle
            if (defined('PMA_DRIZZLE')) {
                $restoreDrizzle = PMA_DRIZZLE;
                runkit_constant_redefine('PMA_DRIZZLE', false);
            } else {
                $restoreDrizzle = 'PMA_TEST_CONSTANT_REMOVE';
                define('PMA_DRIZZLE', false);
            }

            $GLOBALS['cfg']['Server']['DisableIS'] = false;
            $GLOBALS['cfg']['DBG']['sql'] = false;

            $this->assertEquals(
                'utf8_general_ci',
                PMA_getDbCollation('pma_test')
            );

            // test case with pma drizzle as true
            runkit_constant_redefine('PMA_DRIZZLE', true);
            $this->assertEquals(
                'utf8_general_ci_pma_drizzle',
                PMA_getDbCollation('pma_test')
            );

            $GLOBALS['cfg']['Server']['DisableIS'] = true;
            $GLOBALS['db'] = 'pma_test2';
            $this->assertEquals(
                'bar',
                PMA_getDbCollation('pma_test')
            );
            $this->assertNotEquals(
                'pma_test',
                $GLOBALS['dummy_db']
            );

            if ($restoreDrizzle === 'PMA_TEST_CONSTANT_REMOVE') {
                runkit_constant_remove('PMA_DRIZZLE');
            } else {
                runkit_constant_redefine('PMA_DRIZZLE', $restoreDrizzle);
            }
        }
    }

    /**
     * Test case for PMA_getCollationDescr()
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
            PMA_getCollationDescr($collation)
        );
    }

    /**
     * Data Provider for testPMA_getCollationDescr()
     *
     * @return array Test data for testPMA_getCollationDescr()
     */
    public function collationDescr()
    {
        return array(
            array('binary', 'Binary'),
            array('foo_bulgarian_bar', 'Bulgarian'),
            array('gb2312_chinese', 'Simplified Chinese'),
            array('gbk_chinese', 'Simplified Chinese'),
            array('big5_chinese', 'Traditional Chinese'),
            array('foo_ci_bar', 'unknown, case-insensitive'),
            array('foo_cs_bar', 'unknown, case-sensitive'),
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
            array('foo_bin_bar', 'unknown, Binary')
        );
    }

    /**
     * Test for PMA_generateCharsetDropdownBox
     *
     * @return void
     * @test
     */
    public function testGenerateCharsetDropdownBox()
    {
        $GLOBALS['mysql_charsets'] = array('latin1', 'latin2', 'latin3');
        $GLOBALS['mysql_charsets_available'] = array(
            'latin1' => true,
            'latin2' => false,
            'latin3' => true
        );
        $GLOBALS['mysql_charsets_descriptions'] = array(
            'latin1' => 'abc',
            'latin2' => 'def'
        );
        $GLOBALS['mysql_collations'] = array(
            'latin1' => array(
                'latin1_german1_ci',
                'latin1_swedish1_ci'
            ),
            'latin2' => array('latin1_general_ci'),
            'latin3' => array()
        );
        $GLOBALS['mysql_collations_available'] = array(
             'latin1_german1_ci' => true,
             'latin1_swedish1_ci' => false,
             'latin2_general_ci' => true
        );
        $result = PMA_generateCharsetDropdownBox();

        $this->assertContains('name="collation"', $result);
        $this->assertNotContains('id="', $result);
        $this->assertNotContains('class="autosubmit"', $result);
        $this->assertContains('<option value="">Collation', $result);
        $this->assertContains('<option value=""></option>', $result);
        $this->assertContains('<optgroup label="latin1', $result);
        $this->assertNotContains('<optgroup label="latin2', $result);
        $this->assertContains('title="latin3', $result);
        $this->assertContains('title="abc', $result);
        $this->assertNotContains('value="latin1_swedish1_ci"', $result);
        $this->assertContains('value="latin1_german1_ci"', $result);
        $this->assertNotContains('value="latin2_general1_ci"', $result);
        $this->assertContains('title="German', $result);

        $result = PMA_generateCharsetDropdownBox(
            2, null, "test_id", "latin1", false, true
        );
        $this->assertContains('name="character_set"', $result);
        $this->assertNotContains('Charset</option>', $result);
        $this->assertContains('class="autosubmit"', $result);
        $this->assertContains('id="test_id"', $result);
        $this->assertContains('selected="selected">latin1', $result);
    }

    /**
     * Test for PMA_getServerCollation
     *
     * @return void
     * @test
     */
    public function testGetServerCollation()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $this->assertEquals('utf8_general_ci', PMA_getServerCollation());
    }
}
?>
