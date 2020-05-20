<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Abstract class to hold some usefull methods used in tests
 * And make tests clean
 */
abstract class AbstractTestCase extends TestCase
{
    /**
     * Prepares environment for the test.
     * Clean all variables
     */
    protected function setUp(): void
    {
    }

    /**
     * Desctroys the environment built for the test.
     * Clean all variables
     */
    protected function tearDown(): void
    {
    }
}
