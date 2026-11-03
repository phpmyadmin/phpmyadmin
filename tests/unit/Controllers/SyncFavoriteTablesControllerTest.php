<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Controllers\SyncFavoriteTablesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Favorites\RecentFavoriteTable;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function json_decode;
use function json_encode;

#[CoversClass(SyncFavoriteTablesController::class)]
final class SyncFavoriteTablesControllerTest extends AbstractTestCase
{
    public function testSynchronizeFavoriteTables(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        (new ReflectionProperty(RecentFavoriteTables::class, 'instances'))->setValue(null, []);

        $recentFavoriteTable = new RecentFavoriteTable(DatabaseName::from('test_db'), TableName::from('test_table'));
        $dbiDummy->addResult('SHOW TABLES FROM `test_db` LIKE \'test\\\\_table\';', [['test_table']]);
        $dbiDummy->addResult('SELECT `tables` FROM `pmadb`.`favorite` WHERE `username` = \'root\'', []);
        $dbiDummy->addResult(
            "REPLACE INTO `pmadb`.`favorite` (`username`, `tables`) VALUES ('root', "
            . $dbi->quoteString((string) json_encode([$recentFavoriteTable])) . ')',
            true,
        );

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::DATABASE => 'pmadb',
            RelationParameters::FAVORITE_WORK => 'favoritework',
            RelationParameters::FAVORITE => 'favorite',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);

        $config = Config::$instance = new Config();
        $config->selectedServer['user'] = 'root';
        $responseRenderer = new ResponseRenderer();
        $responseRenderer->setAjax(true);
        $controller = new SyncFavoriteTablesController($responseRenderer, new Relation($dbi, $config), $config);

        // The user hash for test
        $user = 'dc76e9f0c0006e8f919e0c515c66dbba3982f785';
        $favoriteTable = json_encode([$user => [['db' => 'test_db', 'table' => 'test_table']]]);

        $_SESSION['tmpval'] = ['favorites_synced' => [Current::$server => null]];

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withParsedBody(['db' => 'test_db', 'favoriteTables' => $favoriteTable]);
        $response = $controller($request);
        $json = json_decode((string) $response->getBody(), true);

        self::assertIsArray($json);
        self::assertArrayHasKey('favoriteTables', $json);
        self::assertSame($favoriteTable, $json['favoriteTables']);
        self::assertArrayHasKey('list', $json);
        /**
         * @psalm-suppress TypeDoesNotContainType
         * @phpstan-ignore-next-line
         */
        self::assertTrue($_SESSION['tmpval']['favorites_synced'][Current::$server]);

        $dbiDummy->assertAllQueriesConsumed();
    }
}
