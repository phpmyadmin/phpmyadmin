<?php
/**
 * Holds CollationsControllerTest class
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CollationsController class
 */
class CollationsControllerTest extends TestCase
{
    /**
     * Prepares environment for the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['PMA_Config'] = new Config();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testIndexAction(): void
    {
        $controller = new CollationsController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Template()
        );

        $actual = $controller->index();

        $this->assertStringContainsString(
            '<div id="div_mysql_charset_collations" class="row">',
            $actual
        );
        $this->assertStringContainsString(
            __('Collation'),
            $actual
        );
        $this->assertStringContainsString(
            __('Description'),
            $actual
        );
        $this->assertStringContainsString(
            '<em>UTF-8 Unicode</em>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>utf8_general_ci</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>Unicode, case-insensitive</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<em>cp1252 West European</em>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>latin1_swedish_ci</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>Swedish, case-insensitive</td>',
            $actual
        );
    }
}
