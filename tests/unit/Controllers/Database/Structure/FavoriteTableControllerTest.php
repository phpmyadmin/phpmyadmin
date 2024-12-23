<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Database\Structure\FavoriteTableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use ReflectionProperty;

use function json_encode;

#[CoversClass(FavoriteTableController::class)]
final class FavoriteTableControllerTest extends AbstractTestCase
{
    public function testSynchronizeFavoriteTables(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $dbiDummy->addResult('SELECT 1 FROM `test_db`.`test_table` LIMIT 1;', [['1']], ['1']);

        (new ReflectionProperty(RecentFavoriteTables::class, 'instances'))->setValue(null, []);
        $favoriteInstance = RecentFavoriteTables::getInstance(TableType::Favorite);

        $controller = new FavoriteTableController(
            new ResponseStub(),
            new Template(),
            new Relation($dbi),
            new DbTableExists($dbi),
        );

        // The user hash for test
        $user = 'abcdefg';
        $favoriteTable = [$user => [['db' => 'test_db', 'table' => 'test_table']]];

        $_SESSION['tmpval'] = ['favorites_synced' => [Current::$server => null]];

        $method = new ReflectionMethod(FavoriteTableController::class, 'synchronizeFavoriteTables');
        $json = $method->invokeArgs($controller, [$favoriteInstance, $user, $favoriteTable]);

        self::assertIsArray($json);
        self::assertSame(json_encode($favoriteTable), $json['favoriteTables'] ?? '');
        self::assertArrayHasKey('list', $json);
        /**
         * @psalm-suppress TypeDoesNotContainType
         * @phpstan-ignore-next-line
         */
        self::assertTrue($_SESSION['tmpval']['favorites_synced'][Current::$server]);

        $dbiDummy->assertAllQueriesConsumed();
    }
}
