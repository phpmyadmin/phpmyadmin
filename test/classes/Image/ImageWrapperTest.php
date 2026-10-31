<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Image;

use PhpMyAdmin\Image\ImageWrapper;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

/**
 * @covers \PhpMyAdmin\Image\ImageWrapper
 */
#[CoversClass(ImageWrapper::class)]
class ImageWrapperTest extends AbstractTestCase
{
    /**
     * @requires extension gd
     */
    #[RequiresPhpExtension('gd')]
    public function testFromStringWithInvalidData(): void
    {
        self::assertNull(ImageWrapper::fromString("\0"));
    }
}
