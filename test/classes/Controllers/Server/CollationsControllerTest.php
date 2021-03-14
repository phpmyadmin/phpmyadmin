<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response;

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
        parent::defineVersionConstants();
        parent::setTheme();
        $GLOBALS['PMA_Config']->enableBc();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testIndexAction(): void
    {
        $response = new Response();

        $controller = new CollationsController($response, new Template(), $GLOBALS['dbi']);

        $controller->index();
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString(
            '<table class="table table-light table-striped table-hover table-sm w-auto">',
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
            'utf8_general_ci',
            $actual
        );
        $this->assertStringContainsString('<span class="sr-only">(default)</span>', $actual);
        $this->assertStringContainsString(
            '<td>Unicode, case-insensitive</td>',
            $actual
        );
        $this->assertStringContainsString(
            '<em>cp1252 West European</em>',
            $actual
        );
        $this->assertStringContainsString(
            'latin1_swedish_ci',
            $actual
        );
        $this->assertStringContainsString(
            '<td>Swedish, case-insensitive</td>',
            $actual
        );
    }
}
