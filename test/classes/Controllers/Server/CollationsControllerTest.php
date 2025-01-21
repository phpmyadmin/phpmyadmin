<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Server\CollationsController
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
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testIndexAction(): void
    {
        $response = new ResponseRenderer();

        $controller = new CollationsController($response, new Template(), $GLOBALS['dbi']);

        $this->dummyDbi->addSelectDb('mysql');
        $controller();
        $this->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        self::assertStringContainsString('<div><strong>latin1</strong></div>', $actual);
        self::assertStringContainsString('<div>cp1252 West European</div>', $actual);
        self::assertStringContainsString('<div><strong>latin1_swedish_ci</strong></div>', $actual);
        self::assertStringContainsString('<div>Swedish, case-insensitive</div>', $actual);
        self::assertStringContainsString('<span class="badge bg-secondary">default</span>', $actual);
        self::assertStringContainsString('<div><strong>utf8</strong></div>', $actual);
        self::assertStringContainsString('<div>UTF-8 Unicode</div>', $actual);
        self::assertStringContainsString('<div><strong>utf8_bin</strong></div>', $actual);
        self::assertStringContainsString('<div>Unicode, binary</div>', $actual);
        self::assertStringContainsString('<div><strong>utf8_general_ci</strong></div>', $actual);
        self::assertStringContainsString('<div>Unicode, case-insensitive</div>', $actual);
    }
}
