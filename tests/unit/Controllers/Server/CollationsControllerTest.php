<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Server\CollationsController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CollationsController::class)]
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

        $this->setGlobalConfig();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;

        Current::$database = 'db';
        Current::$table = 'table';
        Config::getInstance()->selectedServer['DisableIS'] = false;
    }

    public function testIndexAction(): void
    {
        $response = new ResponseRenderer();

        $controller = new CollationsController($response, DatabaseInterface::getInstance(), Config::getInstance());

        $this->dummyDbi->addSelectDb('mysql');
        $controller(self::createStub(ServerRequest::class));
        $this->dummyDbi->assertAllSelectsConsumed();
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
