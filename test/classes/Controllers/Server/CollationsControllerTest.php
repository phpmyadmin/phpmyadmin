<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds CollationsControllerTest class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PHPUnit\Framework\TestCase;

/**
 * Tests for CollationsController class
 *
 * @package PhpMyAdmin-test
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

    /**
     * @return void
     */
    public function testIndexAction(): void
    {
        $charsets = [
            'armscii8',
            'ascii',
            'big5',
            'binary',
        ];
        $charsetsDescriptions = [
            'armscii8' => 'PMA_armscii8_general_ci',
            'ascii' => 'PMA_ascii_general_ci',
            'big5' => 'PMA_big5_general_ci',
            'binary' => 'PMA_binary_general_ci',
        ];
        $collations = [
            'armscii8' => ['armscii8'],
            'ascii' => ['ascii'],
            'big5' => ['big5'],
            'binary' => ['binary'],
        ];
        $defaultCollations = [
            'armscii8' => 'armscii8',
            'ascii' => 'ascii',
            'big5' => 'big5',
            'binary' => 'binary',
        ];

        $controller = new CollationsController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            new Template(),
            $charsets,
            $charsetsDescriptions,
            $collations,
            $defaultCollations
        );

        $actual = $controller->indexAction();

        $this->assertStringContainsString(
            '<div id="div_mysql_charset_collations">',
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
            '<em>PMA_armscii8_general_ci</em>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>armscii8</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>' . Charsets::getCollationDescr('armscii8') . '</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<em>PMA_ascii_general_ci</em>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>ascii</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<em>PMA_big5_general_ci</em>',
            $actual
        );
        $this->assertStringContainsString(
            '<td>big5</td>',
            $actual
        );
    }
}
