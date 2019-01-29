<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds ServerCollationsControllerTest class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\ServerCollationsController;
use PhpMyAdmin\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ServerCollationsController class
 *
 * @package PhpMyAdmin-test
 */
class ServerCollationsControllerTest extends TestCase
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

        $controller = new ServerCollationsController(
            Response::getInstance(),
            $GLOBALS['dbi'],
            $charsets,
            $charsetsDescriptions,
            $collations,
            $defaultCollations
        );

        $actual = $controller->indexAction();

        $this->assertContains(
            '<div id="div_mysql_charset_collations">',
            $actual
        );
        $this->assertContains(
            __('Collation'),
            $actual
        );
        $this->assertContains(
            __('Description'),
            $actual
        );
        $this->assertContains(
            '<em>PMA_armscii8_general_ci</em>',
            $actual
        );
        $this->assertContains(
            '<td>armscii8</td>',
            $actual
        );
        $this->assertContains(
            '<td>' .  Charsets::getCollationDescr('armscii8') . '</td>',
            $actual
        );
        $this->assertContains(
            '<em>PMA_ascii_general_ci</em>',
            $actual
        );
        $this->assertContains(
            '<td>ascii</td>',
            $actual
        );
        $this->assertContains(
            '<em>PMA_big5_general_ci</em>',
            $actual
        );
        $this->assertContains(
            '<td>big5</td>',
            $actual
        );
    }
}
