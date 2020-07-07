<?php
/**
 * tests for environment like OS, PHP, modules, ...
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use const PHP_VERSION;
use function version_compare;

/**
 * Environment tests
 */
class EnvironmentTest extends AbstractTestCase
{
    /**
     * Tests PHP version
     */
    public function testPhpVersion(): void
    {
        $this->assertTrue(
            version_compare('7.1.3', PHP_VERSION, '<='),
            'phpMyAdmin requires PHP 7.1.3 or above'
        );
    }
}
