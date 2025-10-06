<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Mime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function chr;

#[CoversClass(Mime::class)]
class MimeTest extends AbstractTestCase
{
    /**
     * Test for Mime::detect
     *
     * @param string $test   MIME to test
     * @param string $output Expected output
     */
    #[DataProvider('providerForTestDetect')]
    public function testDetect(string $test, string $output): void
    {
        self::assertSame(
            Mime::detect($test),
            $output,
        );
    }

    /**
     * Provider for testDetect
     *
     * @return string[][] data for testDetect
     */
    public static function providerForTestDetect(): array
    {
        return [
            ['pma', 'application/octet-stream'],
            ['GIF', 'image/gif'],
            ["\x89PNG", 'image/png'],
            [chr(0xff) . chr(0xd8), 'image/jpeg'],
        ];
    }
}
