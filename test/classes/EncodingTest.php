<?php
/**
 * Tests for Charset Conversions
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Encoding;
use const PHP_INT_SIZE;
use function fclose;
use function file_get_contents;
use function fopen;
use function function_exists;
use function fwrite;
use function mb_convert_encoding;
use function mb_convert_kana;
use function unlink;
use function setlocale;
use const LC_ALL;

/**
 * Tests for Charset Conversions
 */
class EncodingTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Encoding::initEngine();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Encoding::initEngine();
    }

    /**
     * Test for Encoding::convertString
     *
     * @group medium
     */
    public function testNoConversion(): void
    {
        $this->assertEquals(
            'test',
            Encoding::convertString('UTF-8', 'UTF-8', 'test')
        );
    }

    public function testInvalidConversion(): void
    {
        // Invalid value to use default case
        Encoding::setEngine(-1);
        $this->assertEquals(
            'test',
            Encoding::convertString('UTF-8', 'anything', 'test')
        );
    }

    public function testRecode(): void
    {
        if (! function_exists('recode_string')) {
            $this->markTestSkipped('recode extension missing');
        }

        Encoding::setEngine(Encoding::ENGINE_RECODE);
        $this->assertEquals(
            'Only That ecole & Can Be My Blame',
            Encoding::convertString(
                'UTF-8',
                'flat',
                'Only That école & Can Be My Blame'
            )
        );
    }

    /**
     * This group is used on debian packaging to exclude the test
     *
     * @see https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=854821#27
     *
     * @group extension-iconv
     */
    public function testIconv(): void
    {
        if (! function_exists('iconv')) {
            $this->markTestSkipped('iconv extension missing');
        }

        // Set PHP native locale
        if (function_exists('setlocale')) {
            if (setlocale(0, 'POSIX') === false) {
                $this->markTestSkipped('native setlocale failed');
            }
        }

        _setlocale(LC_ALL, 'POSIX');

        if (PHP_INT_SIZE === 8) {
            $GLOBALS['cfg']['IconvExtraParams'] = '//TRANSLIT';
            Encoding::setEngine(Encoding::ENGINE_ICONV);
            $this->assertEquals(
                "This is the Euro symbol 'EUR'.",
                Encoding::convertString(
                    'UTF-8',
                    'ISO-8859-1',
                    "This is the Euro symbol '€'."
                )
            );
        } elseif (PHP_INT_SIZE === 4) {
            // NOTE: this does not work on 32bit systems and requires "//IGNORE"
            // NOTE: or it will throw "iconv(): Detected an illegal character in input string"
            $GLOBALS['cfg']['IconvExtraParams'] = '//TRANSLIT//IGNORE';
            Encoding::setEngine(Encoding::ENGINE_ICONV);
            $this->assertEquals(
                "This is the Euro symbol ''.",
                Encoding::convertString(
                    'UTF-8',
                    'ISO-8859-1',
                    "This is the Euro symbol '€'."
                )
            );
        }
    }

    public function testMbstring(): void
    {
        Encoding::setEngine(Encoding::ENGINE_MB);
        $this->assertEquals(
            "This is the Euro symbol '?'.",
            Encoding::convertString(
                'UTF-8',
                'ISO-8859-1',
                "This is the Euro symbol '€'."
            )
        );
    }

    /**
     * Test for kanjiChangeOrder
     */
    public function testChangeOrder(): void
    {
        $this->assertEquals('ASCII,SJIS,EUC-JP,JIS', Encoding::getKanjiEncodings());
        Encoding::kanjiChangeOrder();
        $this->assertEquals('ASCII,EUC-JP,SJIS,JIS', Encoding::getKanjiEncodings());
        Encoding::kanjiChangeOrder();
        $this->assertEquals('ASCII,SJIS,EUC-JP,JIS', Encoding::getKanjiEncodings());
    }

    /**
     * Test for Encoding::kanjiStrConv
     */
    public function testKanjiStrConv(): void
    {
        $this->assertEquals(
            'test',
            Encoding::kanjiStrConv('test', '', '')
        );

        $GLOBALS['kanji_encoding_list'] = 'ASCII,SJIS,EUC-JP,JIS';

        $this->assertEquals(
            'test è',
            Encoding::kanjiStrConv('test è', '', '')
        );

        $this->assertEquals(
            mb_convert_encoding('test è', 'ASCII', 'SJIS'),
            Encoding::kanjiStrConv('test è', 'ASCII', '')
        );

        $this->assertEquals(
            mb_convert_kana('全角', 'KV', 'SJIS'),
            Encoding::kanjiStrConv('全角', '', 'kana')
        );
    }

    /**
     * Test for Encoding::kanjiFileConv
     */
    public function testFileConv(): void
    {
        $file_str = '教育漢字常用漢字';
        $filename = 'test.kanji';
        $file = fopen($filename, 'w');
        $this->assertNotFalse($file);
        fwrite($file, $file_str);
        fclose($file);
        $GLOBALS['kanji_encoding_list'] = 'ASCII,EUC-JP,SJIS,JIS';

        $result = Encoding::kanjiFileConv($filename, 'JIS', 'kana');

        $string = file_get_contents($result);
        Encoding::kanjiChangeOrder();
        $expected = Encoding::kanjiStrConv($file_str, 'JIS', 'kana');
        Encoding::kanjiChangeOrder();
        $this->assertEquals($string, $expected);
        unlink($result);
    }

    /**
     * Test for Encoding::kanjiEncodingForm
     */
    public function testEncodingForm(): void
    {
        $actual = Encoding::kanjiEncodingForm();
        $this->assertStringContainsString(
            '<input type="radio" name="knjenc"',
            $actual
        );
        $this->assertStringContainsString(
            'type="radio" name="knjenc"',
            $actual
        );
        $this->assertStringContainsString(
            '<input type="radio" name="knjenc" value="EUC-JP" id="kj-euc">',
            $actual
        );
        $this->assertStringContainsString(
            '<input type="radio" name="knjenc" value="SJIS" id="kj-sjis">',
            $actual
        );
        $this->assertStringContainsString(
            '<input type="checkbox" name="xkana" value="kana" id="kj-kana">',
            $actual
        );
    }

    public function testListEncodings(): void
    {
        $GLOBALS['cfg']['AvailableCharsets'] = ['utf-8'];
        $result = Encoding::listEncodings();
        $this->assertContains('utf-8', $result);
    }
}
