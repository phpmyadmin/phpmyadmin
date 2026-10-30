<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Image;

use PhpMyAdmin\Image\Color;
use PhpMyAdmin\Image\ImageWrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function ob_get_clean;
use function ob_start;
use function restore_error_handler;
use function set_error_handler;

use const E_WARNING;

#[CoversClass(ImageWrapper::class)]
#[RequiresPhpExtension('gd')]
final class ImageWrapperTest extends TestCase
{
    public function testBasicImage(): void
    {
        $actualImage = ImageWrapper::create(21, 12);
        self::assertNotNull($actualImage);

        ob_start();
        $result = $actualImage->png();
        $actual = ob_get_clean();

        self::assertTrue($result);
        self::assertNotFalse($actual);
        self::assertStringEqualsFile(__DIR__ . '/_data/image-basic.png', $actual);
    }

    public function testImageWrapper(): void
    {
        $width = 155;
        $height = 35;
        $background = new Color(108, 120, 175);
        $points = [16, 2, 12, 12, 2, 12, 10, 19, 6, 30, 16, 24, 26, 30, 22, 19, 30, 12, 20, 12];

        $actualImage = ImageWrapper::create($width, $height, $background);
        self::assertNotNull($actualImage);
        self::assertSame($width, $actualImage->width());
        self::assertSame($height, $actualImage->height());

        $color = $actualImage->colorAllocate(new Color(248, 156, 14));
        self::assertNotFalse($color);
        self::assertTrue($actualImage->string(5, 35, 8, 'phpMyAdmin', $color));
        self::assertTrue($actualImage->filledPolygon($points, $color));
        self::assertTrue($actualImage->line(35, 25, 123, 25, $color));
        self::assertTrue($actualImage->arc(140, 18, 20, 20, 0, 360, $color));

        ob_start();
        $result = $actualImage->png();
        $actual = ob_get_clean();

        self::assertTrue($result);
        self::assertNotFalse($actual);
        self::assertStringEqualsFile(__DIR__ . '/_data/image.png', $actual);
    }

    public function testFromString(): void
    {
        $file = file_get_contents(__DIR__ . '/_data/image-basic.png');
        self::assertNotFalse($file);
        $actualImage = ImageWrapper::fromString($file);
        self::assertNotNull($actualImage);

        ob_start();
        $result = $actualImage->png();
        $actual = ob_get_clean();

        self::assertTrue($result);
        self::assertNotFalse($actual);
        self::assertStringEqualsFile(__DIR__ . '/_data/image-basic.png', $actual);
    }

    public function testFromStringWithInvalidString(): void
    {
        $message = '';
        set_error_handler(static function (int $errno, string $errstr) use (&$message): bool {
            $message = $errstr;

            return false;
        }, E_WARNING);

        $actualImage = ImageWrapper::fromString('invalid string');
        restore_error_handler();

        self::assertNull($actualImage);
        self::assertSame('imagecreatefromstring(): Data is not in a recognized format', $message);
    }

    public function testCopyResampled(): void
    {
        $file = file_get_contents(__DIR__ . '/_data/image.png');
        self::assertNotFalse($file);
        $image = ImageWrapper::fromString($file);
        self::assertNotNull($image);

        $width = $image->width();
        $height = $image->height();
        $newWidth = $width * 2;
        $newHeight = $height * 2;

        $newImage = ImageWrapper::create($newWidth, $newHeight);
        self::assertNotNull($newImage);
        self::assertTrue($newImage->copyResampled($image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height));

        ob_start();
        $result = $newImage->png();
        $actual = ob_get_clean();

        self::assertTrue($result);
        self::assertNotFalse($actual);
        self::assertStringEqualsFile(__DIR__ . '/_data/image-resized.png', $actual);
    }
}
