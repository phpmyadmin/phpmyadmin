<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Version;
use function defined;

class VersionTest extends AbstractTestCase
{
    /**
     * Validate the current version
     */
    public function testValidateVersion(): void
    {
        $this->assertIsString(Version::VERSION);
        $this->assertNotEmpty(Version::VERSION);
        $this->assertStringContainsString(Version::SERIES, Version::VERSION, 'x.y must be found in x.y.z');
        $this->assertIsInt(Version::MAJOR);
        $this->assertIsInt(Version::MINOR);
        $this->assertIsInt(Version::PATCH);
        $this->assertTrue(Version::MAJOR >= 5);// @phpstan-ignore-line Just checking
        $this->assertTrue(Version::MINOR >= 0);// @phpstan-ignore-line Just checking
        $this->assertTrue(Version::PATCH >= 0);// @phpstan-ignore-line Just checking
        $this->assertTrue(Version::ID >= 50000);// @phpstan-ignore-line Just checking
        if (defined('VERSION_SUFFIX')) {
            $this->assertIsString(VERSION_SUFFIX);
        }
        $this->assertIsInt(Version::ID);
        $this->assertIsString(Version::PRE_RELEASE_NAME);
        $this->assertIsBool(Version::IS_DEV);
    }
}
