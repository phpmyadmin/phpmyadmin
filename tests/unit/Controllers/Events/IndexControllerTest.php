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

    public function testExportEvent(): void
    {
        Current::$database = 'test_db';

        // phpcs:disable Generic.Files.LineLength.TooLong
        $createEventStatement = <<<'SQL'
            CREATE DEFINER=`test_user`@`localhost` EVENT `test_event` ON SCHEDULE AT '2026-10-11 17:56:42' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN END
            SQL;
        // phpcs:enable

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW CREATE EVENT `test_db`.`test_event`', [[$createEventStatement]], ['Create Event']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();
        $config = new Config();
        $template = new Template($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'item_name' => 'test_event', 'export_item' => '1']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            $dbi,
            new DbTableExists($dbi),
        ))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $expected = '<div class="container"><h2>Export of event `test_event`</h2><div class="card"><div class="card-body"><textarea rows="15" class="form-control">CREATE DEFINER=`test_user`@`localhost` EVENT `test_event` ON SCHEDULE AT &#039;2026-10-11 17:56:42&#039; ON COMPLETION NOT PRESERVE ENABLE DO BEGIN END</textarea></div></div></div>';
        self::assertSame($expected, (string) $response->getBody());
    }

    public function testExportEventAjax(): void
    {
        Current::$database = 'test_db';

        // phpcs:disable Generic.Files.LineLength.TooLong
        $createEventStatement = <<<'SQL'
            CREATE DEFINER=`test_user`@`localhost` EVENT `test_event` ON SCHEDULE AT '2026-10-11 17:56:42' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN END
            SQL;
        // phpcs:enable

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW CREATE EVENT `test_db`.`test_event`', [[$createEventStatement]], ['Create Event']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();
        $config = new Config();
        $template = new Template($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withQueryParams(['db' => 'test_db', 'item_name' => 'test_event', 'export_item' => '1']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            $dbi,
            new DbTableExists($dbi),
        ))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
        self::assertSame(
            ['message' => $createEventStatement, 'title' => 'Export of event `test_event`'],
            $responseRenderer->getJSONResult(),
        );
    }

    public function testExportEventWithoutEvent(): void
    {
        Current::$database = 'test_db';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW CREATE EVENT `test_db`.`test_event`', [], ['Create Event']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();
        $config = new Config();
        $template = new Template($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'item_name' => 'test_event', 'export_item' => '1']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            $dbi,
            new DbTableExists($dbi),
        ))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="alert alert-danger" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Error in processing request: No event with name `test_event` found in database `test_db`.
            </div>

            HTML;
        // phpcs:enable

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame($expected, (string) $response->getBody());
    }

    public function testExportEventWithoutEventAjax(): void
    {
        Current::$database = 'test_db';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW CREATE EVENT `test_db`.`test_event`', [], ['Create Event']);
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();
        $config = new Config();
        $template = new Template($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withQueryParams(['db' => 'test_db', 'item_name' => 'test_event', 'export_item' => '1']);

        $response = (new IndexController(
            $responseRenderer,
            $template,
            new Events($dbi, $config),
            $dbi,
            new DbTableExists($dbi),
        ))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();

        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="alert alert-danger" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Error in processing request: No event with name `test_event` found in database `test_db`.
            </div>

            HTML;
        // phpcs:enable

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
        self::assertSame(['message' => $expected], $responseRenderer->getJSONResult());
    }
}
