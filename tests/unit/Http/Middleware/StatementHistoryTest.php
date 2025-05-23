<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Console\History;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Middleware\StatementHistory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(StatementHistory::class)]
final class StatementHistoryTest extends AbstractTestCase
{
    public function testStatementHistory(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';
        Current::$sqlQuery = 'SELECT 1;';

        $config = new Config();
        $config->selectedServer['user'] = 'test_user';
        $dbi = self::createStub(DatabaseInterface::class);
        $dbi->method('isConnected')->willReturn(true);
        $history = self::createMock(History::class);
        $history->expects(self::once())->method('setHistory')->with(
            self::identicalTo('test_db'),
            self::identicalTo('test_table'),
            self::identicalTo('test_user'),
            self::identicalTo('SELECT 1;'),
        );
        $statementHistory = new StatementHistory($config, $history, $dbi);

        $response = self::createStub(ResponseInterface::class);
        $handler = self::createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/');

        self::assertSame($response, $statementHistory->process($request, $handler));
    }

    #[TestWith(['true', 'SELECT 1;', true])]
    #[TestWith([null, '', true])]
    #[TestWith([null, 'SELECT 1;', false])]
    public function testSkipHistory(string|null $noHistoryParam, string $sqlQuery, bool $isConnected): void
    {
        Current::$sqlQuery = $sqlQuery;

        $dbi = self::createStub(DatabaseInterface::class);
        $dbi->method('isConnected')->willReturn($isConnected);
        $history = self::createMock(History::class);
        $history->expects(self::never())->method('setHistory');
        $statementHistory = new StatementHistory(new Config(), $history, $dbi);

        $response = self::createStub(ResponseInterface::class);
        $handler = self::createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $parsedBody = $noHistoryParam !== null ? ['no_history' => $noHistoryParam] : [];
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody($parsedBody);

        self::assertSame($response, $statementHistory->process($request, $handler));
    }
}
