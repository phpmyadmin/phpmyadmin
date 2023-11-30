<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Import\Ajax;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Ajax::class)]
class AjaxTest extends TestCase
{
    public function testNopluginCheck(): void
    {
        $this->assertTrue(Ajax::nopluginCheck());
    }
}
