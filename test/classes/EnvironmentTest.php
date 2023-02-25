<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use function version_compare;

use const PHP_VERSION;

/** @coversNothing */
class EnvironmentTest extends AbstractTestCase
{
    /**
     * Tests PHP version
     */
    public function testPhpVersion(): void
    {
        $this->assertTrue(
            version_compare('8.1.0', PHP_VERSION, '<='),
            'phpMyAdmin requires PHP 8.1.0 or above',
        );
    }
}
