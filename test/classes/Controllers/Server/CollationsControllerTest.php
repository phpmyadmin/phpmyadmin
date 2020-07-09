<?php
/**
 * Holds CollationsControllerTest class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;

/**
 * Tests for CollationsController class
 */
class CollationsControllerTest extends AbstractTestCase
{
    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['text_dir'] = 'ltr';
        parent::setGlobalConfig();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testIndexAction(): void
    {
        $responseRenderer = new Response();

        $psr17Factory = new Psr17Factory();
        $creator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        $request = $creator->fromGlobals();
        $response = $psr17Factory->createResponse();

        $controller = new CollationsController(
            $responseRenderer,
            $GLOBALS['dbi'],
            new Template()
        );

        $controller->index($request, $response);
        $actual = $responseRenderer->getHTMLResult();

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
