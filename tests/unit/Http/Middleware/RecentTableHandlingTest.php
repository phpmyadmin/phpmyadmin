<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Http\Middleware;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Middleware\RecentTableHandling;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;

#[CoversClass(RecentTableHandling::class)]
final class RecentTableHandlingTest extends AbstractTestCase
{
    public function testProcess(): void
    {
        $dbiDummy = $this->createDbiDummy();
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']]);

        $config = new Config();
        $config->settings['NumRecentTables'] = 10;

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/')
            ->withQueryParams(['db' => 'test_db', 'table' => 'test_table']);

        $response = ResponseFactory::create()->createResponse();
        $handler = self::createMock(RequestHandlerInterface::class);
        $handler->method('handle')->with($request)->willReturn($response);

        $reflectionProperty = new ReflectionProperty(RecentFavoriteTables::class, 'instances');
        $reflectionProperty->setValue(null, []);
        $recentTables = RecentFavoriteTables::getInstance(TableType::Recent);
        self::assertSame([], $recentTables->getTables());

        $actualResponse = (new RecentTableHandling($config))->process($request, $handler);

        self::assertSame($response, $actualResponse);
        self::assertSame('', (string) $actualResponse->getBody());
        self::assertEquals(
            [new RecentFavoriteTable(DatabaseName::from('test_db'), TableName::from('test_table'))],
            $recentTables->getTables(),
        );

        $dbiDummy->assertAllQueriesConsumed();
        $reflectionProperty->setValue(null, []);
    }
}
