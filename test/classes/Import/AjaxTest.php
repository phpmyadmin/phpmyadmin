<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Import\Ajax;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpMyAdmin\Import\Ajax
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\PhpMyAdmin\Import\Ajax::class)]
class AjaxTest extends TestCase
{
    public function testNopluginCheck(): void
    {
        self::assertTrue(Ajax::nopluginCheck());
    }
}
