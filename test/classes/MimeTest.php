<?php
/**
 * tests for PhpMyAdmin\Mime
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Mime;
use PHPUnit\Framework\TestCase;
use function chr;

/**
 * Test for mime detection.
 */
class MimeTest extends TestCase
{
    /**
     * Test for Mime::detect
     *
     * @param string $test   MIME to test
     * @param string $output Expected output
     *
     * @return void
     *
     * @dataProvider providerForTestDetect
     */
    public function testDetect($test, $output): void
    {
        $this->assertEquals(
            Mime::detect($test),
            $output
        );
    }

    /**
     * Provider for testDetect
     *
     * @return array data for testDetect
     */
    public function providerForTestDetect()
    {
        return [
            [
                'pma',
                'application/octet-stream',
            ],
            [
                'GIF',
                'image/gif',
            ],
            [
                "\x89PNG",
                'image/png',
            ],
            [
                chr(0xff) . chr(0xd8),
                'image/jpeg',
            ],
        ];
    }
}
