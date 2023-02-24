<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

/** @covers \PhpMyAdmin\Controllers\Server\EnginesController */
class EnginesControllerTest extends AbstractTestCase
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

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new EnginesController($response, new Template(), $GLOBALS['dbi']);

        $this->dummyDbi->addSelectDb('mysql');
        $controller->__invoke($this->createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();

        $actual = $response->getHTMLResult();

        $this->assertStringContainsString('<th scope="col">Storage Engine</th>', $actual);
        $this->assertStringContainsString('<th scope="col">Description</th>', $actual);

        $this->assertStringContainsString('<td>Federated MySQL storage engine</td>', $actual);
        $this->assertStringContainsString('FEDERATED', $actual);
        $this->assertStringContainsString('index.php?route=/server/engines/FEDERATED', $actual);

        $this->assertStringContainsString('<td>dummy comment</td>', $actual);
        $this->assertStringContainsString('dummy', $actual);
        $this->assertStringContainsString('index.php?route=/server/engines/dummy', $actual);
    }
}
