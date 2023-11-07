<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html;

use PhpMyAdmin\Config;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(MySQLDocumentation::class)]
class MySQLDocumentationTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setTheme();
    }

    public function testShowDocumentation(): void
    {
        $GLOBALS['server'] = '99';
        Config::getInstance()->settings['ServerDefault'] = 1;

        $this->assertEquals(
            '<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen'
            . '%2Flatest%2Fpage.html%23anchor" target="documentation"><svg fill="currentColor" role="img"'
            . ' aria-label="Documentation" alt="Documentation" class="icon ic_b_help">'
            . '<use xlink:href="./themes/pmahomme/img/icons.svg#b_help"/></svg></a>',
            MySQLDocumentation::showDocumentation('page', 'anchor'),
        );
    }
}
