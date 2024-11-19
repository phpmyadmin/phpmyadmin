<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/**
 * @covers \PhpMyAdmin\Controllers\Server\EnginesController
 */
class EnginesControllerTest extends AbstractTestCase
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

    public function testIndex(): void
    {
        global $dbi;

        $response = new ResponseRenderer();

        $controller = new EnginesController($response, new Template(), $dbi);

        $this->dummyDbi->addSelectDb('mysql');
        $controller->__invoke();
        $this->assertAllSelectsConsumed();

        $actual = $response->getHTMLResult();

        self::assertStringContainsString('<th scope="col">Storage Engine</th>', $actual);
        self::assertStringContainsString('<th scope="col">Description</th>', $actual);

        self::assertStringContainsString('<td>Federated MySQL storage engine</td>', $actual);
        self::assertStringContainsString('FEDERATED', $actual);
        self::assertStringContainsString('index.php?route=/server/engines/FEDERATED', $actual);

        self::assertStringContainsString('<td>dummy comment</td>', $actual);
        self::assertStringContainsString('dummy', $actual);
        self::assertStringContainsString('index.php?route=/server/engines/dummy', $actual);
    }
}
