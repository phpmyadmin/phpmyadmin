<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Import\Ajax;
use PHPUnit\Framework\TestCase;

/** @covers \PhpMyAdmin\Import\Ajax */
class AjaxTest extends TestCase
{
    public function testNopluginCheck(): void
    {
        $this->assertTrue(Ajax::nopluginCheck());
    }
}
