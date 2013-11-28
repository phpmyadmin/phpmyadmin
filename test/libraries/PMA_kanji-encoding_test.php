<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Kanji Encoding Library
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/kanji-encoding.lib.php';

/**
 * Tests for Kanji Encoding Library
 *
 * @package PhpMyAdmin-test
 */
class PMA_Kanji_Encoding_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_Kanji_checkEncoding
     *
     * @param string $encoding Encoding to set
     * @param string $expected Expected encoding list
     *
     * @return void
     * @test
     * @dataProvider checkEncodingData
     */
    public function testCheckEncoding($encoding, $expected)
    {
        mb_internal_encoding($encoding);
        $this->assertTrue(PMA_Kanji_checkEncoding());
        $this->assertEquals($expected, $GLOBALS['kanji_encoding_list']);
    }

    /**
     * Data provider for testPMA_Kanji_checkEncoding
     *
     * @return array Test data
     */
    public function checkEncodingData()
    {
        return array(
            array('UTF-8', 'ASCII,SJIS,EUC-JP,JIS'),
            array('EUC-JP', 'ASCII,EUC-JP,SJIS,JIS')
        );
    }

    /**
     * Test for PMA_Kanji_changeOrder
     *
     * @param string $kanji_test_list current list
     * @param string $expected        expected list
     *
     * @return void
     * @test
     * @dataProvider changeOrderData
     */
    public function testChangeOrder($kanji_test_list, $expected)
    {
        $GLOBALS['kanji_encoding_list'] = $kanji_test_list;
        $this->assertTrue(PMA_Kanji_changeOrder());
        $this->assertEquals($expected, $GLOBALS['kanji_encoding_list']);
    }

    /**
     * Data Provider for testPMA_Kanji_changeOrder
     *
     * @return array Test data
     */
    public function changeOrderData()
    {
        return array(
            array('ASCII,SJIS,EUC-JP,JIS', 'ASCII,EUC-JP,SJIS,JIS'),
            array('ASCII,EUC-JP,SJIS,JIS', 'ASCII,SJIS,EUC-JP,JIS')
        );
    }

    /**
     * Test for PMA_Kanji_strConv
     *
     * @return void
     * @test
     */
    public function testStrConv()
    {
        $this->assertEquals(
            'test',
            PMA_Kanji_strConv('test', '', '')
        );

        $GLOBALS['kanji_encoding_list'] = 'ASCII,SJIS,EUC-JP,JIS';

        $this->assertEquals(
            'test è',
            PMA_Kanji_strConv('test è', '', '')
        );

        $this->assertEquals(
            mb_convert_encoding('test è', 'ASCII', 'SJIS'),
            PMA_Kanji_strConv('test è', 'ASCII', '')
        );

        $this->assertEquals(
            mb_convert_kana('全角', 'KV', 'SJIS'),
            PMA_Kanji_strConv('全角', '', 'kana')
        );
    }


    /**
     * Test for PMA_Kanji_fileConv
     *
     * @return void
     * @test
     */
    public function testFileConv()
    {
        $file_str = "教育漢字常用漢字";
        $filename = 'test.kanji';
        $file = fopen($filename, 'w');
        fputs($file, $file_str);
        fclose($file);
        $GLOBALS['kanji_encoding_list'] = 'ASCII,EUC-JP,SJIS,JIS';

        $result = PMA_Kanji_fileConv($filename, 'JIS', 'kana');

        $string = file_get_contents($result);
        PMA_Kanji_changeOrder();
        $expected = PMA_Kanji_strConv($file_str, 'JIS', 'kana');
        PMA_Kanji_changeOrder();
        $this->assertEquals($string, $expected);
        unlink($result);
    }


    /**
     * Test for PMA_Kanji_encodingForm
     *
     * @return void
     * @test
     */
    public function testEncodingForm()
    {
        $actual = PMA_Kanji_encodingForm();
        $this->assertContains(
            '<input type="radio" name="knjenc"',
            $actual
        );
        $this->assertContains(
            'type="radio" name="knjenc"',
            $actual
        );
        $this->assertContains(
            '<input type="radio" name="knjenc" value="EUC-JP" id="kj-euc" />',
            $actual
        );
        $this->assertContains(
            '<input type="radio" name="knjenc" value="SJIS" id="kj-sjis" />',
            $actual
        );
        $this->assertContains(
            '<input type="checkbox" name="xkana" value="kana" id="kj-kana" />',
            $actual
        );
    }
}
?>
