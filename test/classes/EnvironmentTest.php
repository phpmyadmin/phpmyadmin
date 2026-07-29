<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use function version_compare;

use const PHP_VERSION;

/**
 * @coversNothing
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
class EnvironmentTest extends AbstractTestCase
{
    /**
     * Tests PHP version
     */
    public function testPhpVersion(): void
    {
        self::assertTrue(version_compare('7.2.5', PHP_VERSION, '<='), 'phpMyAdmin requires PHP 7.2.5 or above');
    }
}
