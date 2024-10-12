<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Version;

use function defined;

/**
 * @covers \PhpMyAdmin\Version
 */
class VersionTest extends AbstractTestCase
{
    /**
     * Validate the current version
     */
    public function testValidateVersion(): void
    {
        self::assertIsString(Version::VERSION);
        self::assertNotEmpty(Version::VERSION);
        self::assertStringContainsString(Version::SERIES, Version::VERSION, 'x.y must be found in x.y.z');
        self::assertIsInt(Version::MAJOR);
        self::assertIsInt(Version::MINOR);
        self::assertIsInt(Version::PATCH);
        self::assertTrue(Version::MAJOR >= 5);// @phpstan-ignore-line Just checking
        self::assertTrue(Version::MINOR >= 0);// @phpstan-ignore-line Just checking
        self::assertTrue(Version::PATCH >= 0);// @phpstan-ignore-line Just checking
        self::assertTrue(Version::ID >= 50000);// @phpstan-ignore-line Just checking
        if (defined('VERSION_SUFFIX')) {
            self::assertIsString(VERSION_SUFFIX);
        }

        self::assertIsInt(Version::ID);
        self::assertIsString(Version::PRE_RELEASE_NAME);
        self::assertIsBool(Version::IS_DEV);
    }
}
