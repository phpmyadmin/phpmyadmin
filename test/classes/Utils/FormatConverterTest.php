<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Utils\FormatConverter;
use function hex2bin;

class FormatConverterTest extends AbstractTestCase
{
    /**
     * Test for binaryToIp
     *
     * @param string $expected Expected result given an input
     * @param string $input    Input to convert
     * @param bool   $isBinary The data is binary data
     *
     * @dataProvider providerBinaryToIp
     */
    public function testBinaryToIp(string $expected, string $input, bool $isBinary): void
    {
        $result = FormatConverter::binaryToIp($input, $isBinary);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for binaryToIp
     *
     * @return array
     */
    public function providerBinaryToIp(): array
    {
        // expected
        // input
        // isBinary
        return [
            [
                '10.11.12.13',
                '0x0a0b0c0d',
                false,
            ],
            [
                'my ip',
                'my ip',
                false,
            ],
            [
                '10.11.12.13',
                '0x0a0b0c0d',
                true,
            ],
            [
                '6d79206970',
                'my ip',
                true,
            ],
            [
                '10.11.12.13',
                '0x0a0b0c0d',
                true,
            ],
            [
                '666566',
                'fef',
                true,
            ],
            [
                '0ded',
                hex2bin('0DED'),
                true,
            ],
            [
                '127.0.0.1',
                hex2bin('30783766303030303031'),
                true,
            ],
        ];
    }

    /**
     * Test for ipToBinary
     *
     * @param string $expected Expected result given an input
     * @param string $input    Input to convert
     *
     * @dataProvider providerIpToBinary
     */
    public function testIpToBinary(string $expected, string $input): void
    {
        $result = FormatConverter::ipToBinary($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for ipToBinary
     *
     * @return array
     */
    public function providerIpToBinary(): array
    {
        return [
            [
                '0x0a0b0c0d',
                '10.11.12.13',
            ],
            [
                'my ip',
                'my ip',
            ],
        ];
    }

    /**
     * Test for ipToLong
     *
     * @param string $expected Expected result given an input
     * @param string $input    Input to convert
     *
     * @dataProvider providerIpToLong
     */
    public function testIpToLong(string $expected, string $input): void
    {
        $result = FormatConverter::ipToLong($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for ipToLong
     *
     * @return array
     */
    public function providerIpToLong(): array
    {
        return [
            [
                '168496141',
                '10.11.12.13',
            ],
            [
                'my ip',
                'my ip',
            ],
        ];
    }

    /**
     * Test for longToIp
     *
     * @param string $expected Expected result given an input
     * @param string $input    Input to convert
     *
     * @dataProvider providerLongToIp
     */
    public function testLongToIp(string $expected, string $input): void
    {
        $result = FormatConverter::longToIp($input);
        $this->assertEquals($expected, $result);
    }

    /**
     * Data provider for longToIp
     *
     * @return array
     */
    public function providerLongToIp(): array
    {
        return [
            [
                '10.11.12.13',
                '168496141',
            ],
            [
                'my ip',
                'my ip',
            ],
        ];
    }
}
