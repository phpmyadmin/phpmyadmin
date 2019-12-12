<?php
/**
 * Test for \PhpMyAdmin\Html\MySQLDocumentation class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html;

use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Test for \PhpMyAdmin\Html\MySQLDocumentation class
 *
 * @package PhpMyAdmin-test
 */
class MySQLDocumentationTest extends PmaTestCase
{
    /**
     * Test for \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation
     *
     * @return void
     *
     * @covers \PhpMyAdmin\Html\MySQLDocumentation::showDocumentation
     */
    public function testShowDocumentation(): void
    {
        $GLOBALS['server'] = '99';
        $GLOBALS['cfg']['ServerDefault'] = 1;

        $this->assertEquals(
            '<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Fpage.html%23anchor" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a>',
            MySQLDocumentation::showDocumentation('page', 'anchor')
        );
    }
}
