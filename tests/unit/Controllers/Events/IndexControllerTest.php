<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Events;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Events\IndexController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Events\Events;
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
            [['event_scheduler', 'ON']],
            ['Variable_name', 'Value'],
        );
        // phpcs:enable
        $dbi = $this->createDatabaseInterface($dummyDbi, $config);
        $responseRenderer = new ResponseRenderer();
        $template = new Template($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
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
        $dbi = $this->createDatabaseInterface($dummyDbi, $config);
        $responseRenderer = new ResponseRenderer();
        $template = new Template($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
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

    public function testErrorWithoutDbParam(): void
    {
        $config = new Config();
        $config->set('PmaAbsoluteUri', 'https://example.com/');
        Config::$instance = $config;
        $dbi = $this->createDatabaseInterface(config: $config);
        $template = new Template($config);
        $responseRenderer = new ResponseRenderer($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/');

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            new DbTableExists($dbi),
        ))($request);

        self::assertSame(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        self::assertSame(
            '/index.php?route=/&reload=1&message=No+database+selected.&lang=en',
            $response->getHeaderLine('Location'),
        );
        self::assertSame('', (string) $response->getBody());
    }

    public function testErrorWithoutDbParamAjax(): void
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface(config: $config);
        $template = new Template($config);
        $responseRenderer = new ResponseRenderer();
        $responseRenderer->setAjax(true);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest');

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            new DbTableExists($dbi),
        ))($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(
            '{"success":false,"error":"<div class=\"alert alert-danger\" role=\"alert\">\n'
            . '  <img src=\"themes\/dot.gif\" title=\"\" alt=\"\" class=\"icon ic_s_error\"> No database selected.\n'
            . '<\/div>\n"}',
            (string) $response->getBody(),
        );
        self::assertFalse($responseRenderer->hasSuccessState());
    }

    public function testCreateEvent(): void
    {
        Current::$server = 2;
        $config = new Config();
        $config->selectedServer['DisableIS'] = true;

        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dbiDummy->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EVENT'",
            [['EVENT']],
            ['PRIVILEGE_TYPE'],
        );
        $dbiDummy->addResult(
            "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
            [['event_scheduler', 'ON']],
            ['Variable_name', 'Value'],
        );
        // phpcs:enable

        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $responseRenderer = new ResponseRenderer();
        $template = new Template($config);

        $_REQUEST['add_item'] = '1';
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => 'test_db', 'add_item' => '1']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            new DbTableExists($dbi),
        ))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Index-testCreateEvent.html',
            (string) $response->getBody(),
        );
    }

    public function testWithEventSchedulerDisabled(): void
    {
        $dbiDummy = $this->createDbiDummy();
        // phpcs:disable Generic.Files.LineLength.TooLong
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['definer@localhost']], ['CURRENT_USER()']);
        $dbiDummy->addResult(
            "SELECT `PRIVILEGE_TYPE` FROM `INFORMATION_SCHEMA`.`USER_PRIVILEGES` WHERE GRANTEE='''definer''@''localhost''' AND PRIVILEGE_TYPE='EVENT'",
            [['EVENT']],
            ['PRIVILEGE_TYPE'],
        );
        $dbiDummy->addResult(
            "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
            [['event_scheduler', 'DISABLED']],
            ['Variable_name', 'Value'],
        );
        // phpcs:enable

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => 'test_db']);

        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $template = new Template($config);
        $response = (new IndexController(
            new ResponseRenderer($config),
            $template,
            new Events($dbi, $config),
            new DbTableExists($dbi),
        ))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();
        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertStringEqualsFile(
            __DIR__ . '/Fixtures/Index-testWithEventSchedulerDisabled.html',
            (string) $response->getBody(),
        );
    }
}
