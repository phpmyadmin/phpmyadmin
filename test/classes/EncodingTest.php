<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Encoding;

use function _setlocale;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function mb_convert_encoding;
use function mb_convert_kana;
use function setlocale;
use function unlink;

use const LC_ALL;
use const PHP_INT_SIZE;

/**
 * @covers \PhpMyAdmin\Encoding
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
        self::assertSame('test', Encoding::convertString('UTF-8', 'UTF-8', 'test'));
    }

    public function testInvalidConversion(): void
    {
        // Invalid value to use default case
        Encoding::setEngine(-1);
        self::assertSame('test', Encoding::convertString('UTF-8', 'anything', 'test'));
    }

    /**
     * @requires extension recode
     */
    public function testRecode(): void
    {
        Encoding::setEngine(Encoding::ENGINE_RECODE);
        self::assertSame('Only That ecole & Can Be My Blame', Encoding::convertString(
            'UTF-8',
            'flat',
            'Only That école & Can Be My Blame'
        ));
    }

    /**
     * This group is used on debian packaging to exclude the test
     *
     * @see https://bugs.debian.org/cgi-bin/bugreport.cgi?bug=854821#27
     *
     * @group extension-iconv
     * @requires extension iconv
     */
    public function testIconv(): void
    {
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
            self::assertSame("This is the Euro symbol 'EUR'.", Encoding::convertString(
                'UTF-8',
                'ISO-8859-1',
                "This is the Euro symbol '€'."
            ));
        } elseif (PHP_INT_SIZE === 4) {
            // NOTE: this does not work on 32bit systems and requires "//IGNORE"
            // NOTE: or it will throw "iconv(): Detected an illegal character in input string"
            $GLOBALS['cfg']['IconvExtraParams'] = '//TRANSLIT//IGNORE';
            Encoding::setEngine(Encoding::ENGINE_ICONV);
            self::assertSame("This is the Euro symbol ''.", Encoding::convertString(
                'UTF-8',
                'ISO-8859-1',
                "This is the Euro symbol '€'."
            ));
        }
    }

    public function testMbstring(): void
    {
        Encoding::setEngine(Encoding::ENGINE_MB);
        self::assertSame("This is the Euro symbol '?'.", Encoding::convertString(
            'UTF-8',
            'ISO-8859-1',
            "This is the Euro symbol '€'."
        ));
    }

    /**
     * Test for kanjiChangeOrder
     */
    public function testChangeOrder(): void
    {
        self::assertSame('ASCII,SJIS,EUC-JP,JIS', Encoding::getKanjiEncodings());
        Encoding::kanjiChangeOrder();
        self::assertSame('ASCII,EUC-JP,SJIS,JIS', Encoding::getKanjiEncodings());
        Encoding::kanjiChangeOrder();
        self::assertSame('ASCII,SJIS,EUC-JP,JIS', Encoding::getKanjiEncodings());
    }

    /**
     * Test for Encoding::kanjiStrConv
     */
    public function testKanjiStrConv(): void
    {
        self::assertSame('test', Encoding::kanjiStrConv('test', '', ''));

        $GLOBALS['kanji_encoding_list'] = 'ASCII,SJIS,EUC-JP,JIS';

        self::assertSame('test è', Encoding::kanjiStrConv('test è', '', ''));

        self::assertSame(
            mb_convert_encoding('test è', 'ASCII', 'SJIS'),
            Encoding::kanjiStrConv('test è', 'ASCII', '')
        );

        self::assertSame(mb_convert_kana('全角', 'KV', 'SJIS'), Encoding::kanjiStrConv('全角', '', 'kana'));
    }

    /**
     * Test for Encoding::kanjiFileConv
     */
    public function testFileConv(): void
    {
        $file_str = '教育漢字常用漢字';
        $filename = 'test.kanji';
        self::assertNotFalse(file_put_contents($filename, $file_str));
        $GLOBALS['kanji_encoding_list'] = 'ASCII,EUC-JP,SJIS,JIS';

        $result = Encoding::kanjiFileConv($filename, 'JIS', 'kana');

        $string = file_get_contents($result);
        Encoding::kanjiChangeOrder();
        $expected = Encoding::kanjiStrConv($file_str, 'JIS', 'kana');
        Encoding::kanjiChangeOrder();
        self::assertSame($string, $expected);
        unlink($result);
    }

    /**
     * Test for Encoding::kanjiEncodingForm
     */
    public function testEncodingForm(): void
    {
        $actual = Encoding::kanjiEncodingForm();
        self::assertStringContainsString('<input type="radio" name="knjenc"', $actual);
        self::assertStringContainsString('type="radio" name="knjenc"', $actual);
        self::assertStringContainsString('<input type="radio" name="knjenc" value="EUC-JP" id="kj-euc">', $actual);
        self::assertStringContainsString('<input type="radio" name="knjenc" value="SJIS" id="kj-sjis">', $actual);
        self::assertStringContainsString('<input type="checkbox" name="xkana" value="kana" id="kj-kana">', $actual);
    }

    public function testListEncodings(): void
    {
        $GLOBALS['cfg']['AvailableCharsets'] = ['utf-8'];
        $result = Encoding::listEncodings();
        self::assertContains('utf-8', $result);
    }

    public function testListEncodingsForIso2022CnExt(): void
    {
        Encoding::setEngine(Encoding::ENGINE_ICONV);
        $GLOBALS['cfg']['AvailableCharsets'] = [
            'utf-8',
            'ISO-2022-CN',
            'ISO2022CN',
            'ISO-2022-CN-EXT',
            'ISO2022CNEXT',
            ' iso-2022-cn-ext ',
            'ISO-2022-CN-EXT//TRANSLIT',
            ' I S O - 2 0 2 2 - C N - E X T ',
            ' I S O 2 0 2 2 C N E X T ',
            'IS%O-20(22-CN-E$XT',
        ];

        self::assertSame(['utf-8', 'ISO-2022-CN', 'ISO2022CN'], Encoding::listEncodings());
    }
}
