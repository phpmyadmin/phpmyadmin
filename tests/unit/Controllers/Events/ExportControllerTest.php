<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Events;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Events\ExportController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Events\Events;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ExportController::class)]
final class ExportControllerTest extends AbstractTestCase
{
    public function testExportEvent(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $createEventStatement = <<<'SQL'
            CREATE DEFINER=`test_user`@`localhost` EVENT `test_event` ON SCHEDULE AT '2026-10-11 17:56:42' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN END
            SQL;
        // phpcs:enable

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW CREATE EVENT `test_db`.`test_event`', [[$createEventStatement]], ['Create Event']);
        $dbiDummy->addResult(
            "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
            [['event_scheduler', 'ON']],
            ['Variable_name', 'Value'],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();
        $events = new Events($dbi, new Config());

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'item_name' => 'test_event']);

        $response = (new ExportController($responseRenderer, $events, new DbTableExists($dbi)))($request);

        $dbiDummy->assertAllSelectsConsumed();
        $dbiDummy->assertAllQueriesConsumed();

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        // phpcs:ignore Generic.Files.LineLength.TooLong
        $expected = '<div class="container"><h2>Export of event `test_event`</h2><div class="card"><div class="card-body"><textarea rows="15" class="form-control">CREATE DEFINER=`test_user`@`localhost` EVENT `test_event` ON SCHEDULE AT &#039;2026-10-11 17:56:42&#039; ON COMPLETION NOT PRESERVE ENABLE DO BEGIN END</textarea></div></div></div>';
        self::assertSame($expected, (string) $response->getBody());
    }

    public function testExportEventAjax(): void
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $createEventStatement = <<<'SQL'
            CREATE DEFINER=`test_user`@`localhost` EVENT `test_event` ON SCHEDULE AT '2026-10-11 17:56:42' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN END
            SQL;
        // phpcs:enable

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW CREATE EVENT `test_db`.`test_event`', [[$createEventStatement]], ['Create Event']);
        $dbiDummy->addResult(
            "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
            [['event_scheduler', 'ON']],
            ['Variable_name', 'Value'],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();
        $events = new Events($dbi, new Config());

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withQueryParams(['db' => 'test_db', 'item_name' => 'test_event']);

        $response = (new ExportController($responseRenderer, $events, new DbTableExists($dbi)))($request);

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
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW CREATE EVENT `test_db`.`test_event`', [], ['Create Event']);
        $dbiDummy->addResult(
            "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
            [['event_scheduler', 'ON']],
            ['Variable_name', 'Value'],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();
        $events = new Events($dbi, new Config());

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db', 'item_name' => 'test_event']);

        $response = (new ExportController($responseRenderer, $events, new DbTableExists($dbi)))($request);

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
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->removeDefaultResults();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('SHOW CREATE EVENT `test_db`.`test_event`', [], ['Create Event']);
        $dbiDummy->addResult(
            "SHOW GLOBAL VARIABLES LIKE 'event_scheduler'",
            [['event_scheduler', 'ON']],
            ['Variable_name', 'Value'],
        );
        $dbi = $this->createDatabaseInterface($dbiDummy);
        $responseRenderer = new ResponseRenderer();
        $events = new Events($dbi, new Config());

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withQueryParams(['db' => 'test_db', 'item_name' => 'test_event']);

        $response = (new ExportController($responseRenderer, $events, new DbTableExists($dbi)))($request);

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

    public function testErrorWithoutDbParam(): void
    {
        $config = new Config();
        $config->set('PmaAbsoluteUri', 'https://example.com/');
        Config::$instance = $config;
        $dbi = $this->createDatabaseInterface(config: $config);
        $events = new Events($dbi, $config);
        $responseRenderer = new ResponseRenderer($config);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/');

        $response = (new ExportController($responseRenderer, $events, new DbTableExists($dbi)))($request);

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
        $events = new Events($dbi, $config);
        $responseRenderer = new ResponseRenderer();

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest');

        $response = (new ExportController($responseRenderer, $events, new DbTableExists($dbi)))($request);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame('', (string) $response->getBody());
        self::assertSame(['message' => 'No database selected.'], $responseRenderer->getJSONResult());
        self::assertFalse($responseRenderer->hasSuccessState());
    }
}
