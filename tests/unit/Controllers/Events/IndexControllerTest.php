<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Events;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Events\IndexController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Events;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(IndexController::class)]
#[CoversClass(Events::class)]
final class IndexControllerTest extends AbstractTestCase
{
    public function testWithEvents(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        $config = new Config();
        $config->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SHOW EVENTS FROM `test_db`',
            [['test_db', 'test_event', 'definer@localhost', 'ONE TIME', 'ENABLED']],
            ['Db', 'Name', 'Definer', 'Type', 'Status'],
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EVENT'",
            [['EVENT']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            'SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'',
            [['event_scheduler', 'OFF']],
            ['Variable_name', 'Value'],
        );
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $responseRenderer = new ResponseRenderer();
        $template = new Template($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            $dbi,
            new DbTableExists($dbi),
        ))($request);

        $dummyDbi->assertAllSelectsConsumed();
        $dummyDbi->assertAllQueriesConsumed();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Index-testWithEvents.html',
            (string) $response->getBody(),
        );
    }

    public function testWithoutEvents(): void
    {
        Current::$server = 2;
        Current::$database = 'test_db';
        $config = new Config();
        $config->selectedServer['DisableIS'] = true;

        $dummyDbi = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dummyDbi->removeDefaultResults();
        $dummyDbi->addSelectDb('test_db');
        $dummyDbi->addResult(
            'SHOW EVENTS FROM `test_db`',
            [],
            ['Db', 'Name', 'Definer', 'Type', 'Status'],
        );
        $dummyDbi->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dummyDbi->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EVENT'",
            [['EVENT']],
            ['PRIVILEGE_TYPE'],
        );
        $dummyDbi->addResult(
            'SHOW GLOBAL VARIABLES LIKE \'event_scheduler\'',
            [['event_scheduler', 'OFF']],
            ['Variable_name', 'Value'],
        );
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $responseRenderer = new ResponseRenderer();
        $template = new Template($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            $dbi,
            new DbTableExists($dbi),
        ))($request);

        $dummyDbi->assertAllSelectsConsumed();
        $dummyDbi->assertAllQueriesConsumed();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Index-testWithoutEvents.html',
            (string) $response->getBody(),
        );
    }
}
