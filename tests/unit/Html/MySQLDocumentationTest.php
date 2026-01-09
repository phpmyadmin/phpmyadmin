<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html;

use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MySQLDocumentation::class)]
class MySQLDocumentationTest extends AbstractTestCase
{
    public function testShowDocumentation(): void
    {
        self::assertSame(
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen'
            . '%2Flatest%2Fpage.html%23anchor" target="documentation"><img src="themes/dot.gif"'
            . ' title="Documentation" alt="Documentation" class="icon ic_b_help"></a>',
            MySQLDocumentation::showDocumentation('page', 'anchor'),
        );
    }
}
