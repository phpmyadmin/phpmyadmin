<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;

use function version_compare;

use const PHP_VERSION;

#[CoversNothing]
class EnvironmentTest extends AbstractTestCase
{
    /**
     * Tests PHP version
     */
    public function testPhpVersion(): void
    {
        self::assertTrue(
            version_compare('8.2.0', PHP_VERSION, '<='),
            'phpMyAdmin requires PHP 8.2.0 or above',
        );
    }
}
