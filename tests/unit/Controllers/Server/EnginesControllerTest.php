<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\EnginesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(EnginesController::class)]
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

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        Current::$database = 'db';
        Current::$table = 'table';
        Config::getInstance()->selectedServer['DisableIS'] = false;
    }

    public function testIndex(): void
    {
        $response = new ResponseRenderer();

        $controller = new EnginesController($response, DatabaseInterface::getInstance());

        $this->dummyDbi->addSelectDb('mysql');
        $controller->__invoke(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();

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
