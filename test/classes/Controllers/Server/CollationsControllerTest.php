<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Server\CollationsController */
class CollationsControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    /**
     * Prepares environment for the test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['text_dir'] = 'ltr';

        parent::setGlobalConfig();

        parent::setTheme();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;

        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testIndexAction(): void
    {
        $response = new ResponseRenderer();

        $controller = new CollationsController($response, new Template(), $GLOBALS['dbi']);

        $this->dummyDbi->addSelectDb('mysql');
        $controller($this->createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
        $actual = $response->getHTMLResult();

        $this->assertStringContainsString('<div><strong>latin1</strong></div>', $actual);
        $this->assertStringContainsString('<div>cp1252 West European</div>', $actual);
        $this->assertStringContainsString('<div><strong>latin1_swedish_ci</strong></div>', $actual);
        $this->assertStringContainsString('<div>Swedish, case-insensitive</div>', $actual);
        $this->assertStringContainsString('<span class="badge bg-secondary text-dark">default</span>', $actual);
        $this->assertStringContainsString('<div><strong>utf8</strong></div>', $actual);
        $this->assertStringContainsString('<div>UTF-8 Unicode</div>', $actual);
        $this->assertStringContainsString('<div><strong>utf8_bin</strong></div>', $actual);
        $this->assertStringContainsString('<div>Unicode, binary</div>', $actual);
        $this->assertStringContainsString('<div><strong>utf8_general_ci</strong></div>', $actual);
        $this->assertStringContainsString('<div>Unicode, case-insensitive</div>', $actual);
    }
}
