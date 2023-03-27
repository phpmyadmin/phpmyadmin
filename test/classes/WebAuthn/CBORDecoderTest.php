<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\WebAuthn;

use PhpMyAdmin\WebAuthn\CBORDecoder;
use PhpMyAdmin\WebAuthn\DataStream;
use PhpMyAdmin\WebAuthn\WebAuthnException;
use PHPUnit\Framework\TestCase;

use function base64_decode;
use function hex2bin;

use const INF;

/**
 * @covers \PhpMyAdmin\WebAuthn\CBORDecoder
 * @covers \PhpMyAdmin\WebAuthn\DataStream
 */
class CBORDecoderTest extends TestCase
{
    /** @dataProvider dataProviderForTestDecode */
    public function testDecode(string $encoded, mixed $expected): void
    {
        $decoder = new CBORDecoder();
        $data = hex2bin($encoded);
        $this->assertNotFalse($data);
        $this->assertSame($expected, $decoder->decode(new DataStream($data)));
    }

    /** @psalm-return iterable<int, array{string, mixed}> */
    public static function dataProviderForTestDecode(): iterable
    {
        return [
            ['00', 0],
            ['01', 1],
            ['0a', 10],
            ['17', 23],
            ['1818', 24],
            ['1819', 25],
            ['1864', 100],
            ['1903e8', 1000],
            ['1a000f4240', 1000000],
            //['1b000000e8d4a51000', 1000000000000],
            //['1bffffffffffffffff', 18446744073709551615],
            //['c249010000000000000000', 18446744073709551616],
            //['3bffffffffffffffff', -18446744073709551616],
            //['c349010000000000000000', -18446744073709551617],
            ['20', -1],
            ['29', -10],
            ['3863', -100],
            ['3903e7', -1000],
            ['f90000', 0.0],
            ['f98000', -0.0],
            ['f93c00', 1.0],
            ['fb3ff199999999999a', 1.1],
            ['f93e00', 1.5],
            ['f97bff', 65504.0],
            ['fa47c35000', 100000.0],
            ['fa7f7fffff', 3.4028234663852886e+38],
            ['fb7e37e43c8800759c', 1.0e+300],
            ['f90001', 5.960464477539063e-8],
            ['f90400', 0.00006103515625],
            ['f9c400', -4.0],
            ['fbc010666666666666', -4.1],
            ['f97c00', INF],
            ['f9fc00', -INF],
            ['fa7f800000', INF],
            ['faff800000', -INF],
            ['fb7ff0000000000000', INF],
            ['fbfff0000000000000', -INF],
            ['f4', true],
            ['f5', false],
            ['f6', null],
            //['f7', 'undefined'],
            ['f0', 16],
            ['f818', 24],
            ['f8ff', 255],
            ['c074323031332d30332d32315432303a30343a30305a', '2013-03-21T20:04:00Z'],
            ['c11a514b67b0', 1363896240],
            ['c1fb41d452d9ec200000', 1363896240.5],
            ['d74401020304', hex2bin('01020304')],
            ['d818456449455446', hex2bin('6449455446')],
            ['d82076687474703a2f2f7777772e6578616d706c652e636f6d', 'http://www.example.com'],
            ['40', hex2bin('')],
            ['4401020304', hex2bin('01020304')],
            ['60', ''],
            ['6161', 'a'],
            ['6449455446', 'IETF'],
            ['62225c', '"\\'],
            ['62c3bc', "\u{00fc}"],
            ['63e6b0b4', "\u{6c34}"],
            ['64f0908591', "\u{10151}"], // "\u{d800}\u{dd51}"
            ['80', []],
            ['83010203', [1, 2, 3]],
            ['8301820203820405', [1, [2, 3], [4, 5]]],
            [
                '98190102030405060708090a0b0c0d0e0f101112131415161718181819',
                [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25],
            ],
            ['a0', []],
            ['a201020304', [1 => 2, 3 => 4]],
            ['a26161016162820203', ['a' => 1, 'b' => [2, 3]]],
            ['826161a161626163', ['a', ['b' => 'c']]],
            [
                'a56161614161626142616361436164614461656145',
                ['a' => 'A', 'b' => 'B', 'c' => 'C', 'd' => 'D', 'e' => 'E'],
            ],
            ['a1646e616d656441636d65', ['name' => 'Acme']],
            [
                'a462696458203082019330820138a0030201023082019330820138a003020102'
                . '3082019330826469636f6e782b68747470733a2f2f706963732e6578616d706c'
                . '652e636f6d2f30302f702f61426a6a6a707150622e706e67646e616d65766a6f'
                . '686e70736d697468406578616d706c652e636f6d6b646973706c61794e616d65'
                . '6d4a6f686e20502e20536d697468a462696458203082019330820138a0030201'
                . '023082019330820138a0030201023082019330826469636f6e782b6874747073'
                . '3a2f2f706963732e6578616d706c652e636f6d2f30302f702f61426a6a6a7071'
                . '50622e706e67646e616d65766a6f686e70736d697468406578616d706c652e63'
                . '6f6d6b646973706c61794e616d656d4a6f686e20502e20536d697468',
                [
                    'id' => base64_decode('MIIBkzCCATigAwIBAjCCAZMwggE4oAMCAQIwggGTMII='),
                    'icon' => 'https://pics.example.com/00/p/aBjjjpqPb.png',
                    'name' => 'johnpsmith@example.com',
                    'displayName' => 'John P. Smith',
                ],
            ],
            [
                '82a263616c672664747970656a7075626C69632D6B6579a263616c6739010064747970656a7075626C69632D6B6579',
                [['alg' => -7,'type' => 'public-key'], ['alg' => -257,'type' => 'public-key']],
            ],
            [
                'A501020326200121582065eda5a12577c2bae829437fe338701a10aaa375e1bb5b5de108de439c08551d'
                . '2258201e52ed75701163f7f9e40ddf9f341b3dc9ba860af7e0ca7ca7e9eecd0084d19c',
                [
                    1 => 2,
                    3 => -7,
                    -1 => 1,
                    -2 => hex2bin('65eda5a12577c2bae829437fe338701a10aaa375e1bb5b5de108de439c08551d'),
                    -3 => hex2bin('1e52ed75701163f7f9e40ddf9f341b3dc9ba860af7e0ca7ca7e9eecd0084d19c'),
                ],
            ],
        ];
    }

    public function testDecodeForNaNValues(): void
    {
        $decoder = new CBORDecoder();
        $nanValues = ['f97e00', 'fa7fc00000', 'fb7ff8000000000000'];
        foreach ($nanValues as $value) {
            $data = hex2bin($value);
            $this->assertNotFalse($data);
            $this->assertNan($decoder->decode(new DataStream($data)));
        }
    }

    /** @dataProvider indefiniteLengthValuesProvider */
    public function testDecodeForNotSupportedValues(string $encoded): void
    {
        $decoder = new CBORDecoder();
        $data = hex2bin($encoded);
        $this->assertNotFalse($data);
        $this->expectException(WebAuthnException::class);
        $decoder->decode(new DataStream($data));
    }

    /** @psalm-return iterable<int, array{string}> */
    public static function indefiniteLengthValuesProvider(): iterable
    {
        return [
            ['5f42010243030405ff'], // (_ h'0102', h'030405')
            ['7f657374726561646d696e67ff'], // (_ "strea", "ming")
            ['9fff'], // [_ ]
            ['9f018202039f0405ffff'], // [_ 1, [2, 3], [_ 4, 5]]
            ['9f01820203820405ff'], // [_ 1, [2, 3], [4, 5]]
            ['83018202039f0405ff'], // [1, [2, 3], [_ 4, 5]]
            ['83019f0203ff820405'], // [1, [_ 2, 3], [4, 5]]
            // [_ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25]
            ['9f0102030405060708090a0b0c0d0e0f101112131415161718181819ff'],
            ['bf61610161629f0203ffff'], // {_ "a": 1, "b": [_ 2, 3]}
            ['826161bf61626163ff'], // ["a", {_ "b": "c"}]
            ['bf6346756ef563416d7421ff'], // {_ "Fun": true, "Amt": -2}
        ];
    }
}
