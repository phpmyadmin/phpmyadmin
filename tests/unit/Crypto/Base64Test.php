<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Crypto;

use PhpMyAdmin\Crypto\Base64;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function hex2bin;

#[CoversClass(Base64::class)]
final class Base64Test extends TestCase
{
    public function testOriginalVariant(): void
    {
        $base64 = 'rrZ5j+9OUYT/O1WWr3U0hw==';
        $binary = hex2bin('aeb6798fef4e5184ff3b5596af753487');
        self::assertIsString($binary);
        self::assertSame($base64, Base64::encode($binary));
        self::assertSame($binary, Base64::decode($base64));
        self::assertSame($binary, Base64::decode(' ' . $base64 . "\n", " \n"));
    }

    public function testUrlSafeNoPaddingVariant(): void
    {
        $base64 = 'rrZ5j-9OUYT_O1WWr3U0hw';
        $binary = hex2bin('aeb6798fef4e5184ff3b5596af753487');
        self::assertIsString($binary);
        self::assertSame($base64, Base64::encodeUrlSafeNoPadding($binary));
        self::assertSame($binary, Base64::decodeUrlSafeNoPadding($base64));
        self::assertSame($binary, Base64::decodeUrlSafeNoPadding(' ' . $base64 . "\n", " \n"));
    }
}
